# ThinkPHP 短信验证码扩展

一个简单的 ThinkPHP 短信验证码扩展

主要功能：短信验证码发送和验证

## 安装

~~~
composer require axguowen/think-smscaptcha
~~~

## 用法示例

首先配置config目录下的smscaptcha.php配置文件，然后可以按照下面的用法使用。

生成并发送短信验证码

~~~php

use think\facade\SmsCaptcha;

// 手机号
$mobile = '188****8888';
// 生成发送短信验证码
$sendResult = SmsCaptcha::send($mobile);
// 如果成功
if(!is_null($sendResult[0])){
    echo '成功';
}
// 失败
else{
    echo $sendResult[1]->getMessage();
}

~~~

校验短信验证码

~~~php

use think\facade\SmsCaptcha;

// 验证码校验
$verifyResult = SmsCaptcha::verify('188****8888', '486936');
// 验证通过
if(!is_null($verifyResult[0])){
    echo '通过';
}
// 错误
else{
    echo $sendResult[1]->getMessage();
}

~~~