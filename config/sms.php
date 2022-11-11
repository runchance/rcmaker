<?php
//手机验证码校验配置文件

return [
	'type'=>1, //验证码类型
	'length'=>4, //验证码长度
	'expire'=>180, //验证码过期时间（秒）
	'ipCheck'=>true, //是否验证IP
	'mobileKey'=>'mobile', //获取前端传入的手机字段名
	'codeKey'=>'code', //获取前端传入的短信验证码名
	'autoDelte'=>true //验证成功后是否自动删除缓存
];
?>