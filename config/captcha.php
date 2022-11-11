<?php
return [
    'default' => [
        'name'=>'capcha',
        'length'=>5,
        'store'=>'cache',
        'phrase'=>[
            'width'=>150,
            'height'=>40,
            'font'=>null,
            'fingerprint'=>null
        ],
        'charset'=>'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'return'=>'image'
    ],
    'session' => [
        'name'=>'capcha',
        'length'=>4,
        'store'=>'session',
        'return'=>'image'
    ],
    'closure' => [
        'name'=>'capcha',
        'length'=>5,
        'store'=>'closure',
        'return'=>'image'
    ]
];
