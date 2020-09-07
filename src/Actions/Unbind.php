<?php
namespace Dcat\Admin\Extension\GoogleAuthenticator\Actions;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Form\AbstractTool;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Unbind extends AbstractTool
{

    protected $style = 'btn btn-sm btn-warning btn-unbind';
    /**
     * 按钮标题
     *
     * @return string
     */
    protected $title = '解绑谷歌帐号';

    /**
     * 处理请求，如果不需要接口处理，请直接删除这个方法
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        // 获取主键
        $key = $this->getKey();

        $res = Administrator::where('id', $key)
            ->update(['google_auth' => '']);
        if($res !== false) {
            return $this->response()
                ->success('解绑操作成功')
                ->refresh();

        } else {
            return $this->render('解绑出错');
        }

    }

    /**
     * 如果只是a标签跳转，则在这里返回跳转链接即可
     *
     * @return string|void
     */
//    protected function href()
//    {
//        // 获取主键
//        $key = $this->getKey();
//
//        // 获取当前页其他字段
//        $username = $this->parent->model()->username;
//
//        // return admin_url('auth/users');
//    }


    /**
     * 确认弹窗信息，如不需要可以删除此方法
     *
     * @return string|array|void
     */
    public function confirm()
    {
         return ['提示', '确认要解绑该用户的谷歌验证吗？确定后该页面未保存的编辑内容将会清空！'];
    }

    /**
     * 权限判断，如不需要可以删除此方法
     *
     * @param Model|Authenticatable|HasPermissions|null $user
     *
     * @return bool
     */
//    protected function authorize($user): bool
//    {
//        return true;
//    }

    /**
     * 返回请求接口的参数，如不需要可以删除此方法
     *
     * @return array
     */
    protected function parameters()
    {
        return [
            'aa' => 'bb'
        ];
    }

}
