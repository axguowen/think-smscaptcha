<?php
// +----------------------------------------------------------------------
// | ThinkPHP SmsCaptcha [Simple SMS Captcha For ThinkPHP]
// +----------------------------------------------------------------------
// | ThinkPHP 短信验证码扩展
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: axguowen <axguowen@qq.com>
// +----------------------------------------------------------------------

namespace think\smscaptcha\driver;

use think\smscaptcha\Platform;
use think\facade\Cache;
use think\helper\Str;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi;
use AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest;

class Aliyun extends Platform
{
	/**
     * 平台配置参数
     * @var array
     */
    protected $options = [
        // 公钥
        'access_id' => '',
        // 私钥
        'access_secret' => '',
        // 模板ID
        'template_id' => '',
        // 已审核的签名
        'sign_name' => '',
        // 服务接入点, 默认dysmsapi.aliyuncs.com
        'endpoint' => '',
        // 验证码位数
        'length' => 6,
        // 验证码有效期
        'expire' => 900,
        // 模板变量名
        'var_name' => 'code',
        // 缓存标识
        'cache_tag' => '',
        // 重复发送时间间隔
        'resend_limit' => 60,
    ];

	/**
     * 发送验证码
     * @access public
     * @param string $mobile
     * @param string $code
     * @return array
     */
    public function send(string $mobile, string $code = null)
	{
        // 如果手机号为空
        if(empty($mobile)){
            return [null, new \Exception('手机号不能为空')];
        }

        // 如果模板变量名为空
        if(empty($this->options['var_name'])){
            return [null, new \Exception('模板变量名不能为空')];
        }

        // 读取发送缓存
        $sendCache = Cache::get('smscaptcha_sended_' . $this->options['cache_tag'] . '_' . $mobile);
        // 如果60秒内已发送过
        if(!empty($sendCache)){
            return [null, new \Exception('短信发送过于频繁')];
        }

        // 如果指定的验证码为空
        if(is_null($code)){
            // 生成验证码
            $code = Str::random($this->options['length'], 1);
        }

        // 验证码数据
        $captchaData = [$this->options['var_name'] => $code];

        // 实例化请求类
        $request = new SendSmsRequest([
            'phoneNumbers' => $mobile,
            'signName' => $this->options['sign_name'],
            'templateCode' => $this->options['template_id'],
            'templateParam' => json_encode($captchaData),
        ]);

        try{
            // 实例化认证对象
            $config = new \Darabonba\OpenApi\Models\Config([
                'accessKeyId' => $this->options['access_id'],
                'accessKeySecret' => $this->options['access_secret'],
                'endpoint' => $this->options['endpoint'] ?: 'dysmsapi.aliyuncs.com',
            ]);
            // 实例化要请求产品的 client 对象
            $smsClient = new Dysmsapi($config);
            // 发送短信
            $response = $smsClient->sendSms($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果发送失败
        if(is_null($response->body->Code != 'OK')){
            // 返回错误
            return [null, new \Exception($response->body->Message)];
        }

        // 构造返回数据
        $resultData = [
            'driver' => static::class,
            'mobile' => $mobile,
            'code' => $code,
        ];
        // 缓存验证码数据
        Cache::set('smscaptcha_data_' . $this->options['cache_tag'] . '_' . $mobile, $resultData, $this->options['expire']);
        // 缓存已发送状态
        Cache::set('smscaptcha_sended_' . $this->options['cache_tag'] . '_' . $mobile, '1', $this->options['resend_limit']);
        // 返回成功
        return [$resultData, null];
	}
}