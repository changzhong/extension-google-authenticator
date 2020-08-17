<?php

namespace Dcat\Admin\Extension\GoogleAuthenticator\Http\Controllers;

use Dcat\Admin\Extension\GoogleAuthenticator\GoogleAuthenticator;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Widgets\Box;
use Illuminate\Http\Request;
use Dcat\Admin\Controllers\AuthController as BaseAuthController;
use Illuminate\Support\Facades\DB;

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
        $threeJs = 'vendors/dcat-admin-extensions/'.GoogleAuthenticator::NAME.'/three.min.js';
        if (file_exists(public_path($threeJs))) {
//            Admin::booting(function () use ($threeJs) {
                Admin::js($threeJs);
                Admin::js('vendors/dcat-admin-extensions/'.GoogleAuthenticator::NAME.'/login-three.js');
//            });
        }

        return $content->full()->body(view($this->loginView));

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
        $admin = Administrator::query()->where(['username' => $request->get($this->username())]);
        $google = $admin->value('google_auth');

        $is_open_google_auth = $admin->value('is_open_google_auth');

        if ($is_open_google_auth) {
            $onecode = (string)$request->get('onecode');

            if (empty($onecode) && strlen($onecode) != 6 || !google_check_code((string)$google, $onecode, 1)) {
                return $this->validationErrorsResponse(['onecode' => 'Google 验证码错误']);
            }
        }

        return parent::postLogin($request);
    }

    /**
     * 谷歌验证绑定相关页面
     * @return string
     */
    public function google() {

        $secret = auth('admin')->user()->google_auth ?? '';
        $createSecret = google_create_secret(32, $secret, Admin::user()->username);

        $box = new Box('Google 验证绑定', view($this->googleView, ['createSecret' => $createSecret, 'id' => Admin::user()->id]));
        $box->style('info');
        return $box->render();
    }

    /**
     * 个人设置
     * @param Content $content
     * @return Content
     */
    public function getSetting(Content $content) {
        $form = $this->settingForm();
        $form->tools(
            function (Form\Tools $tools) {
                $tools->disableList();
            }
        );

        return $content
            ->title(trans('admin.user_setting'))
            ->row(function (Row $row) use ($form) {

                $row->column(9, function (Column $column) use ($form) {
                    $column->append($form->edit(Admin::user()->getKey()));
                });

                $row->column(3, function (Column $column) {
                    $column->append($this->google());
                });

            });
    }


    /**
     * 测试看验证码是否正确
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
            return response()->json(['message' => '验证码错误，请输入正确的谷歌验证码 !'.$onecode, 'status' => FALSE,]);
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

            if (! $form->password) {
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

