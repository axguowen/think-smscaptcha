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

namespace think\smscaptcha;

use think\facade\Cache;

/**
 * 平台抽象类
 */
abstract class Platform implements PlatformInterface
{
	/**
     * 平台配置参数
     * @var array
     */
	protected $options = [];

	/**
     * 架构函数
     * @access public
     * @param array $options 平台配置参数
     */
    public function __construct(array $options = [])
    {
        // 合并配置参数
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        // 初始化
        $this->init();
    }

	/**
     * 动态设置平台配置参数
     * @access public
     * @param array $options 平台配置
     * @return $this
     */
    public function setConfig(array $options)
    {
        // 合并配置
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        // 返回
        return $this->init();
    }

	/**
     * 初始化
     * @access protected
     * @return $this
     */
    protected function init()
    {
        // 获取缓存标识前缀为空
        if(empty($this->options['cache_tag'])){
            $this->options['cache_tag'] = hash('md5', \think\facade\Request::ip() . \think\facade\Request::server('HTTP_USER_AGENT'));
        }
        // 返回
        return $this;
    }

    /**
     * 验证码校验
     * @access public
     * @param string $mobile
     * @param string $code
     * @return bool
     */
    public function verify(string $mobile, string $code)
    {
        // 获取缓存中的验证码
        $captchaData = Cache::get('smscaptcha_data_' . $this->options['cache_tag'] . '_' . $mobile);
        // 如果不存在
        if(is_null($captchaData)){
            return [null, new \Exception('验证码错误或已过期')];
        }
        // 如果验证码错误
        if($captchaData['code'] == $code){
            // 删除缓存
            Cache::delete('smscaptcha_data_' . $this->options['cache_tag'] . '_' . $mobile);
            // 缓存已发送状态
            Cache::delete('smscaptcha_sended_' . $this->options['cache_tag'] . '_' . $mobile);
            // 返回成功
            return ['验证通过', null];
        }
        // 返回失败
        return [null, new \Exception('验证码错误或已过期')];
    }
}