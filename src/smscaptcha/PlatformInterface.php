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

/**
 * Platform interface
 */
interface PlatformInterface
{
    /**
     * 发送验证码
     * @access public
     * @param string $mobile
     * @param string $code
     * @return array
     */
    public function send(string $mobile, string $code = null);
}
