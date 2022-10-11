<?php
//手机验证码校验配置文件

return [
	'type'=>1, //验证码类型
	'length'=>4, //验证码长度
	'expire'=>180, //验证码过期时间（秒）
	'mobileKey'=>'mobile', //获取前端传入的手机字段名
	'codeKey'=>'code' //获取前端传入的短信验证码名
];
?>