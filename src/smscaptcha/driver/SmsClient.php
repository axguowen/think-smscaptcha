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
use think\facade\SmsClient as Client;
use think\facade\Cache;
use think\helper\Str;

class SmsClient extends Platform
{
	/**
     * 配置参数
     * @var array
     */
    protected $options = [
        // 选择要使用的平台
        'platform' => null,
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
        $captchaData = empty($this->options['var_name']) ? [$code] : [$this->options['var_name'] => $code];

        // 发送短信
        $response = Client::platform($this->options['platform'])->send($mobile, $captchaData);

        // 发送失败
        if(is_null($response[0])){
            // 返回结果
            return $response;
        }
        // 获取短信发送结果
        $sendResult = $response[0][0];
        // 如果发送失败
        if($sendResult['send_status'] != 1){
            return [null, new \Exception('短信发送失败, 请检查手机号是否正确')];
        }
        // 构造返回数据
        $resultData = [
            'driver' => $response[0][0]['driver'],
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