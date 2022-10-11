<?php

return [
    'msg' => [
    	'key_not_exist'=>'缺少key',
    	'signature_verification_failed'=>'token验证失败',
    	'signature_verification_before_invalid'=>'token签名尚未生效',
    	'access_expired'=>'token已过期！',
    	'token_format_error'=>'token格式错误',
    	'signed_in_on_another_device'=>'token在其他设备使用',
    	'request_without_info'=>'请求未携带信息',
    	'illegal_info'=>'非法的信息',
    	'refresh_token_valid'=>'用于刷新的token无效',
    	'refresh_token_invalid_yet'=>'用于刷新的token尚未生效',
    	'refresh_token_expired'=>'用于刷新的token已经过期',
    	'refresh_token_format_error'=>'用于刷新的token格式错误'
    ],
    'default'=>'api',
    'api' => [
        // 算法类型 HS256、HS384、HS512、RS256、RS384、RS512、ES256、ES384、EdDSA(Ed25519)
        'signer' => 'HS256',
        // 客户端校验类型 Bearer、 Header、 Get、 Post、 Cookie、 Session
        'type' => 'Session',
        //传值校验
        'keyName' => 'token',
        // access令牌秘钥
        'access_secret_key' => 'rcmaker2022authaccess8f9g9i',
        // access令牌过期时间，单位：秒。默认 2 小时
        'access_expired' => 7200,
        // refresh令牌秘钥
        'refresh_secret_key' => 'rcmaker2022authrefresh5v6g8y',
        // refresh令牌过期时间，单位：秒。默认 7 天
        'refresh_expired' => 604800,
        // refresh 令牌是否禁用，默认不禁用 false
        'refresh_disable' => false,
        // 令牌签发者
        'iss' => 'rcmaker.runchance.com',
        // 时钟偏差冗余时间，单位秒。建议这个余地应该不大于几分钟。
        'leeway' => 60,
        // 单设备登录
        'is_single_device' => true,
        // 缓存令牌时间，单位：秒。默认 7 天
        'cache_token_time' => 604800,
        // 缓存令牌前缀
        'cache_token_prefix' => 'RC:AUTH:TOKEN:',
        /**
         * access令牌私钥
         */
        'access_private_key' => null,

        /**
         * access令牌公钥
         */
        'access_public_key' => null,

        /**
         * refresh令牌私钥
         */
        'refresh_private_key' => null,

        /**
         * refresh令牌公钥
         */
        'refresh_public_key' => null,
    ],
    'user'=> [

    ],
    'admin'=> [

    ]
];