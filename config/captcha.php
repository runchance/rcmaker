<?php
return [
    'default' => [
        'expire'=>300,
        'namePrefix'=>'RC_CAPTCHA_',
        'length'=>5,
        'store'=>'cache',
        'phrase'=>[
            'width'=>150,
            'height'=>40,
            'font'=>null,
            'fingerprint'=>null
        ],
        'charset'=>'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'autoDelete'=>true, //验证成功后是否自动删除缓存
        'return'=>'image'
    ],
    'session' => [
        'expire'=>300,
        'length'=>4,
        'store'=>'session',
        'return'=>'image'
    ],
    'closure' => [
        'expire'=>300,
        'length'=>5,
        'store'=>'closure',
        'return'=>'image'
    ]
];
