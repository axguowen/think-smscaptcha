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
use BaiduBce\BceClientConfigOptions;
use BaiduBce\Services\Sms\SmsClient;

class BaiduBce extends Platform
{
	/**
     * 平台配置参数
     * @var array
     */
    protected $options = [
        // 公钥
        'access_key' => '',
        // 私钥
        'secret_key' => '',
        // 模板ID
        'template_id' => '',
        // 签名ID
        'signature_id' => '',
        // 服务接入点, 默认smsv3.bj.baidubce.com
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

        try{
            // 实例化要请求产品的 client 对象
            $smsClient = new SmsClient([
                BceClientConfigOptions::PROTOCOL => 'https',
                BceClientConfigOptions::REGION => 'bj',
                BceClientConfigOptions::CREDENTIALS => [
                    'ak' => $this->options['access_key'],
                    'sk' => $this->options['secret_key']
                ],
                BceClientConfigOptions::ENDPOINT => $this->options['endpoint'] ?: 'smsv3.bj.baidubce.com',
            ]);
            // 发送短信
            $response = $smsClient->sendMessage($mobile, $this->options['signature_id'], $this->options['template_id'], $captchaData);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果发送失败
        if($response->code != 1000){
            // 返回错误信息
            return [null, new \Exception($response->message)];
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