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
use TencentCloud\Common\Credential;
use TencentCloud\Sms\V20210111\SmsClient;
use TencentCloud\Sms\V20210111\Models\SendSmsRequest;

class TencentCloud extends Platform
{
	/**
     * 平台配置参数
     * @var array
     */
    protected $options = [
        // 公钥
        'secret_id' => '',
        // 私钥
        'secret_key' => '',
        // 短信应用ID
        'sdk_app_id' => '',
        // 模板ID
        'template_id' => '',
        // 已审核的签名
        'sign_name' => '',
        // 服务接入地域, 支持的地域列表参考 https://cloud.tencent.com/document/api/382/52071#.E5.9C.B0.E5.9F.9F.E5.88.97.E8.A1.A8
        'endpoint' => '',
        // 验证码位数
        'length' => 6,
        // 验证码有效期
        'expire' => 900,
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
        $captchaData = [$code];

        // 实例化授权对象
        $credential = new Credential($this->options['secret_id'], $this->options['secret_key']);
        // 实例化要请求产品的 client 对象
        $smsClient = new SmsClient($credential, $this->options['endpoint']);

		// 请求对象
		$request = new SendSmsRequest();

		// 填充请求参数,这里request对象的成员变量即对应接口的入参
		// 短信应用ID: 短信SdkAppId在 [短信控制台] 添加应用后生成的实际SdkAppId，示例如1400006666
		// 应用 ID 可前往 [短信控制台](https://console.cloud.tencent.com/smsv2/app-manage) 查看
		$request->SmsSdkAppId = $this->options['sdk_app_id'];
		/* 短信签名内容: 使用 UTF-8 编码，必须填写已审核通过的签名 */
		$request->SignName = $this->options['sign_name'];
		/* 模板 ID: 必须填写已审核通过的模板 ID */
		$request->TemplateId = $this->options['template_id'];
		/* 模板参数: 模板参数的个数需要与 TemplateId 对应模板的变量个数保持一致，若无模板参数，则设置为空*/
		$request->TemplateParamSet = $captchaData;
		/* 下发手机号码，采用 E.164 标准，+[国家或地区码][手机号]
		* 示例如：+8613711112222， 其中前面有一个+号 ，86为国家码，13711112222为手机号，最多不要超过200个手机号*/
		$request->PhoneNumberSet = $mobile;
		// 通过client对象调用SendSms方法发起请求。注意请求方法名与请求对象是对应的
		// 返回的response是一个SendSmsResponse类的实例，与请求对象对应
        try{
            $response = $smsClient->SendSms($request);
        } catch (\Exception $e) {
            // 返回错误
            return [null, $e];
        }

        // 如果存在错误信息
        if(isset($response->Error)){
            // 返回错误信息
            return [null, new \Exception($response->Error->Message)];
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