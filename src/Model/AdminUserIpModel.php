<?php
/**
 * Created by PhpStorm
 * USER lcz
 * Date 2021/11/26   8:34 下午
 */

namespace Dcat\Admin\Extension\GoogleAuthenticator\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class AdminUserIpModel extends Model
{
    protected $table = 'admin_user_ip';

    protected $fillable = ['admin_id', 'ip', 'status', 'address', 'updated_at'];


    public function beforeSave() {

    }

    /**
     * 发送登录验证码
     * @param $adminUser
     * @param string $event
     */
    public static function sendLoginCode($adminUser, string $event = 'login')
    {
        $code = rand(100000, 999999);
        $text = '你的验证码是【' . $code . '】,有效时间5分钟。如果不是您本人操作，你的密码可能已经泄露。';

        $key = md5('sms_' . $event . '_' . $adminUser->id . '_code');
        //过期了才重新发
        if (!Cache::get($key)) {
            Cache::put($key, $code, 300);
            Mail::raw($text, function ($message) use ($adminUser) {
                $message->to($adminUser->email)
                    ->cc([env('MAIL_FROM_ADDRESS')])
                    ->subject(env('MAIL_FROM_ADDRESS') . '后台登录安全检验');
            });
        }
    }

    /**
     * 检测验证码
     * @param $adminUser
     * @param $code
     * @param string $event
     * @return bool|string
     */
    public static function checkLoginCode($adminUser, $code, string $event = 'login')
    {
        $key = md5('sms_' . $event . '_' . $adminUser->id . '_code');
        $cacheCode = Cache::get($key);
        if (!$cacheCode) {
            return '验证码已过期,请刷新页面重试';
        }

        if ($cacheCode != $code) {
            return '验证码不正确';
        }

        //检验成功，删除验证码
        Cache::forget($key);
        return true;
    }
}
