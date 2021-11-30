<?php
/**
 * Created by PhpStorm
 * USER lcz
 * Date 2021/11/26   8:34 下午
 */

namespace Dcat\Admin\Extension\GoogleAuthenticator\Model;
use App\Models\AdminUserIp;
use App\Observers\AdminUserIpObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminUserIpModel extends Model
{
    protected $table = 'admin_user_ip';

    protected $fillable = ['admin_id', 'ip', 'status', 'address', 'updated_at'];


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


    /**
     * 根据IP获取地区
     * @param $ip
     * @return false|mixed
     */
    public static function getIpAddress($ip)
    {
        try {
            $url = "http://ip-api.com/json/{$ip}?lang=zh-CN";
            $result = self::curl_ssl($url);
            $res = json_decode($result, true);
            $status = $res['status'] ?? 'fail';
            if ($status == 'success') {
                return $res;
            }
            throw new \Exception('接口处理失败');
        } catch (\Exception $e) {
            Log::error('根据ip获取地址异常: ', [$ip, $e->getMessage()]);
        }
        return false;
    }



    #get请求
    public static function curl_ssl($url, $is_ssl = false, $my_proxy = false)
    {
        $user_agent = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.202 Safari/535.1";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        @curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        $is_proxy = config("is_proxy");

        if ($is_proxy && $my_proxy) {
            $proxy = config("proxy_ip");
            if ($proxy) {
                $proxy_index = rand(0, count($proxy) - 1);
                $proxy_ip = $proxy[$proxy_index] ?? '';
                curl_setopt($curl, CURLOPT_PROXY, $proxy_ip);
                curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            }
        }

        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');

        curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $is_ssl);//这个是重点。
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $is_ssl);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60); // 设置超时限制防止死循环

        $data = curl_exec($curl);

        if (curl_errno($curl)) {
            return false;
        }

        curl_close($curl);

        return $data;
    }

}
