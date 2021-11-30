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

        //判断登录次数
        $date = date('Y-m-d');
        $loginFailureMaxNumber = config('admin.login_failure', 5);
        $admin->login_failure = ($admin->updated_at >= $date ? $admin->login_failure + 1 : 1);
        if ($admin->updated_at >= $date && $admin->login_failure > $loginFailureMaxNumber) {
            //错误次数加1
            DB::table(config('admin.database.users_table'))
                ->where('id', $admin->id)
                ->update([
                    'login_failure' => $admin->login_failure,
                ]);

            return $this->error("密码已连续输入错误{$loginFailureMaxNumber}次，请明日再试或联系管理员");
        }

        if (!Hash::check($request->get('password'), $admin->password)) {
            //错误次数加1
            DB::table(config('admin.database.users_table'))
                ->where('id', $admin->id)
                ->update([
                    'login_failure' => $admin->login_failure,
                ]);

            $lastNumber = config('admin.login_failure') - $admin->login_failure;
            $tips = $lastNumber == 0 ? "密码已连续输入错误{$loginFailureMaxNumber}次，请明日再试或联系管理员" : ($lastNumber <= 2 ? '密码不正确，再错误' . $lastNumber . '次后今天将不能登录' : '密码不正确');
            return $this->error($tips);
        }

        if($admin->enabled == 0) {
            return $this->error("账户已禁用，请联系管理员");
        }

        $ip = self::getClientIp();
        $smsCode = $request->get('sms_code');
        if ($type == 'ip') {
            //判断邮箱验证码是否正确
            $smsRes = $this->checkSmsCode($admin, $smsCode, $ip);
            if($smsRes !== true) {
                return $this->error($smsRes);
            }
        }

        //判断是否常用IP
        $ipRes = $this->checkIp($admin);
        if($ipRes !== true) {
            return $ipRes;
        }

        $google = $admin->google_auth;

        $is_open_google_auth = $admin->is_open_google_auth;

        //判断是否需要谷歌验证码登录
        if ($is_open_google_auth) {

            if (!$google) {
                //还没绑定谷歌验证码，提示绑定和返回绑定二维码
                $createSecret = google_create_secret(32, '', env('APP_NAME') .'-'.$admin->username);
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
                if($smsCode) {
                    return response()->json(['message' => 'Google 验证码错误', 'code' => 203]);
                }
                return $this->error('Google 验证码错误');
            }
        }

        DB::table(config('admin.database.users_table'))
            ->where('id', $admin->id)
            ->update([
                'login_at' => now(),
                'login_ip' => $ip,
                'login_failure' => 0
            ]);

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
        if($admin->enabled == 0) {
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
        $form->text('email', trans('admin.email'))->required();
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

    /**
     * 检测登录IP
     * @param $adminUser
     * @return bool|JsonResponse|RedirectResponse|Redirector
     */
    private function checkIp($adminUser)
    {

        if (!$adminUser->email) {
            return response()->json([
                'code' => 40000,
                'message' => '未绑定邮箱，请与管理员联系'
            ]);
        }

        $userIpList = AdminUserIpModel::where('admin_id', $adminUser->id)
            ->where('status', 1)
            ->pluck('ip')->toArray();

        $ip = self::getClientIp();
        if (!$userIpList || !in_array($ip, $userIpList)) {
            //如果没有登录过或者当前登录IP不在常用IP列表内且已有IP数量小于配置的最大数量，发送验证码
            $title = '首次登录需进行安全验证';
            if (!in_array($ip, $userIpList)) {
                $title = '当前登录IP【' . $ip . '】非常用IP,需进行安全验证';
            }

            //发送邮箱验证码
            AdminUserIpModel::sendLoginCode($adminUser);

            // 生成 22*****@qq.com的显示邮箱
            $emailArray = explode('@', $adminUser->email);
            $prefix = substr($emailArray[0] ?? "", 0, 2);
            $secretEmail = $prefix . '*****@' . ($emailArray[1] ?? '');
            return response()->json([
                'code' => 202,
                'message' => $title,
                'email' => "邮箱验证码已发送至{$secretEmail},请注意查收"
            ]);
        }

        //之前已经最大登录设备了，直接禁用
        if (count($userIpList) > config('admin.max_ip_number')) {
            //设备
            if ($adminUser->enabled == 1) {
                $adminUser->enabled = 0;
                $adminUser->save();
            }
            return $this->error('不同设备登录次数过多，您的账号已被禁用，请联系管理员！');
        }

        return true;
    }


    /**
     * 不存在的创建
     * @param $adminId
     * @param $ip
     * @return mixed
     */
    public function saveIp($adminId, $ip)
    {
        $res = AdminUserIpModel::getIpAddress($ip);
        $address = '未知';
        if ($res) {
            $address = $res['country'] . ' ' . $res['regionName'] . ' ' . $res['city'];
        }
        return AdminUserIpModel::updateOrCreate(
            ['admin_id' => $adminId, 'ip' => $ip, 'status' => 1],
            ['updated_at' => now(), 'address' => $address]
        );
    }

    /**
     * 检验邮箱验证码是否正确
     * @param $adminUser
     * @param $smsCode
     * @param $ip
     * @return bool|JsonResponse|RedirectResponse|Redirector
     */
    private function checkSmsCode($adminUser, $smsCode, $ip)
    {
        //判断IP是否已经存在，如果存在了不用检验
        $ipRow = AdminUserIpModel::where('admin_id', $adminUser->id)
            ->where('ip', $ip)
            ->where('status', 1)
            ->first();
        if ($ipRow) {
            return true;
        }

        $res = AdminUserIpModel::checkLoginCode($adminUser, $smsCode);
        if($res !== true) {
            return $res;
        }

        $this->saveIp($adminUser->id, $ip);
        return true;
    }



    public static function getClientIp() {
        $ips = request()->getClientIps();
        return $ips[1] ?? $ips[0];
    }
}

