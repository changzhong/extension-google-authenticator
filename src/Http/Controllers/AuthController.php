<?php

namespace Dcat\Admin\Extension\GoogleAuthenticator\Http\Controllers;

use Dcat\Admin\Extension\GoogleAuthenticator\GoogleAuthenticator;
use Dcat\Admin\Extension\GoogleAuthenticator\Model\AdminUserIpModel;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Widgets\Box;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Dcat\Admin\Controllers\AuthController as BaseAuthController;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use SimpleSoftwareIO\QrCode\QrCodeInterface;

class AuthController extends BaseAuthController
{

    protected $loginView = GoogleAuthenticator::NAME . '::login';

    protected $googleView = GoogleAuthenticator::NAME . '::google';

    /**
     * {@inheritdoc}
     */
    public function getLogin(Content $content)
    {
        if ($this->guard()->check()) {
            return redirect($this->redirectPath());
        }

        //登录页面炫酷js
        $threeJs = 'vendors/dcat-admin-extensions/' . GoogleAuthenticator::NAME . '/three.min.js';
        if (file_exists(public_path($threeJs))) {
//            Admin::booting(function () use ($threeJs) {
            Admin::js($threeJs);
            Admin::js('vendors/dcat-admin-extensions/' . GoogleAuthenticator::NAME . '/login-three.js');
//            });
        }
        $createSecret = google_create_secret(32, '', env('APP_NAME') . '-admin');
        return $content->full()->body(view($this->loginView, ['createSecret' => $createSecret]));

    }

    /**
     * 登录请求
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function postLogin(Request $request)
    {
        $type = $request->get('type', 'login');
        if ($type == 'bind') {
            return $this->bindGoogle($request);
        }

        $admin = Administrator::where(['username' => $request->get($this->username())])->first();
        if (!$admin) {
            return $this->validationErrorsResponse(['username' => '用户不存在']);
        }

        if($admin->enabled == 0) {
            return $this->error("账户已禁用，请联系管理员");
        }


        $google = $admin->google_auth;
        $is_open_google_auth = $admin->is_open_google_auth;

        //判断是否需要谷歌验证码登录
        if ($is_open_google_auth) {

            if (!$google) {
                //还没绑定谷歌验证码，提示绑定和返回绑定二维码
                $createSecret = google_create_secret(32, '', env('APP_NAME') . '-' . $admin->username);
                return response()->json([
                    'status' => false,
                    'message' => '请先绑定谷歌验证',
                    'code' => 201,
                    'qrcode' => QrCode::encoding('UTF-8')->size(200)->margin(1)->errorCorrection('H')->generate($createSecret["codeurl"]),
                    'secret' => $google = $createSecret['secret']
                ]);
            }
            $onecode = (string)$request->get('onecode');
            $secretKey = env('APP_KEY');
            if (empty($onecode) && strlen($onecode) != 6 || !google_check_code((string)$google ?? encryptDecrypt($secretKey, $admin->google_secret, true), $onecode, 1)) {
                if ($smsCode) {
                    return response()->json(['message' => 'Google 验证码错误', 'code' => 203]);
                }
                return $this->error('Google 验证码错误');
            }
        }

        return parent::postLogin($request);
    }

    public function bindGoogle(Request $request)
    {
        $onecode = (string)$request->get('code');
        $admin = Administrator::where(['username' => $request->get($this->username())])->first();
        if (!$admin) {
            return $this->validationErrorsResponse(['username' => '用户不存在']);
        }

        if (!Hash::check($request->get('password'), $admin->password)) {
            return $this->validationErrorsResponse(['password' => '密码不正确']);
        }
        if ($admin->enabled == 0) {
            return $this->error("账户已禁用，请联系管理员");
        }

        $secret = (string)$request->get('secret');

        if (empty($onecode) && strlen($onecode) != 6 || !google_check_code((string)$secret, $onecode, 1)) {
            return $this->validationErrorsResponse(['code' => 'Google 验证码错误|' . date('Y-m-d H:i:s')]);
        }

        $credentials = $request->only([$this->username(), 'password']);
        $remember = (bool)$request->input('remember', false);

        $validator = Validator::make($credentials, [
            $this->username() => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorsResponse($validator);
        }

        if ($this->guard()->attempt($credentials, $remember)) {
            //绑定谷歌
            Administrator::where('id', $admin->id)
                ->update([
                    'is_open_google_auth' => 1,
                    'google_auth' => $secret
                ]);

            return $this->sendLoginResponse($request);
        }

        return $this->validationErrorsResponse([
            $this->username() => $this->getFailedLoginMessage(),
        ]);
    }

    /**
     * 谷歌验证绑定相关页面
     * @return string
     */
    public function google()
    {

        $secret = auth('admin')->user()->google_auth ?? '';
        $createSecret = google_create_secret(32, $secret, env('APP_NAME') .'-'.Admin::user()->username);
        $box = new Box('Google 验证绑定', view($this->googleView, ['createSecret' => $createSecret, 'id' => Admin::user()->id]));
        $box->style('info');
        return $box->render();
    }

    /**
     * 测试看验证码是否正确
     * @param Request $request
     * @return JsonResponse
     */
    public function googlePost(Request $request)
    {
        $onecode = (string)$request->onecode;
        if (empty($onecode) && strlen($onecode) != 6) {
            admin_toastr('请正确输入手机上google验证码 !', 'error');
            return response()->json(['message' => '请正确输入手机上google验证码 !', 'status' => FALSE,]);
        }
        // google密钥，绑定的时候为生成的密钥；如果是绑定后登录，从数据库取以前绑定的密钥
        $google = $request->google;


        // 验证验证码和密钥是否相同
        if (google_check_code((string)$google, $onecode, 1)) {
            return response()->json(['message' => '验证码测试通过 !', 'status' => TRUE,]);
        } else {
            return response()->json(['message' => '验证码错误，请输入正确的谷歌验证码 !' . $onecode, 'status' => FALSE,]);
        }
    }

    public function setGoogleAuth(Request $request)
    {
        $is_open_google_auth = $request->get('is_open_google_auth');
        $id = $request->id;
        $google = $request->google;
        $onecode = (string)$request->onecode;

//        if (empty($onecode) && strlen($onecode) != 6) {
//            admin_toastr('请正确输入手机上google验证码 !', 'error');
//            return response()->json(['message' => '请正确输入手机上google验证码 !']);
//        }
//
//        // 验证验证码和密钥是否相同
//        if (!google_check_code((string)$google, $onecode, 1)) {
//            admin_toastr('验证码错误，请输入正确的验证码 !', 'error');
//            return response()->json(['message' => '验证码错误，请输入正确的验证码 !', 'status' => FALSE,]);
//        }

        $admi_user = Administrator::query()->where('id', $id)->first();
        $admi_user->google_auth = $google;
        $admi_user->is_open_google_auth = $is_open_google_auth;
        $admi_user->save();
        admin_toastr('设置成功');
        return response()->json(['message' => '设置成功 !', 'status' => TRUE,]);
    }


    /**
     * Model-form for user setting.
     *
     * @return Form
     */
    protected function settingForm()
    {
        $form = new Form(new \Dcat\Admin\Models\Repositories\Administrator());

        $form->action(admin_url('auth/setting'));

        $form->disableCreatingCheck();
        $form->disableEditingCheck();
        $form->disableViewCheck();

        $form->tools(function (Form\Tools $tools) {
            $tools->disableView();
            $tools->disableDelete();
        });

        $form->display('username', trans('admin.username'));
        $form->text('name', trans('admin.name'))->required();
        $form->image('avatar', trans('admin.avatar'));

        $form->password('old_password', trans('admin.old_password'));

        $form->password('password', trans('admin.password'))
            ->minLength(5)
            ->maxLength(20)
            ->customFormat(function ($v) {
                if ($v == $this->password) {
                    return;
                }

                return $v;
            });
        $form->password('password_confirmation', trans('admin.password_confirmation'))->same('password');

        $form->ignore(['password_confirmation', 'old_password']);

        $form->saving(function (Form $form) {
            if ($form->password && $form->model()->password != $form->password) {
                $form->password = bcrypt($form->password);
            }

            if (!$form->password) {
                $form->deleteInput('password');
            }
        });

        $form->saved(function (Form $form) {
            return $form->redirect(
                admin_url('auth/setting'),
                trans('admin.update_succeeded')
            );
        });

        return $form;
    }


}

