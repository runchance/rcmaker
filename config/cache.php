<?php
return [
    'default_frame' => 'raw',
    'default' => 'redis',
    'driver' => [
        'file' => [
            'type' => 'File',
            // 缓存保存目录
            'path' => runtime_path() . '/cache/',
            // 缓存前缀
            'prefix' => 'RCCache_',
            // 缓存有效期 0表示永久缓存
            'expire' => 0,
        ],
        'redis' => [
            'type' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
            'prefix' => 'RCCache_',
            'expire' => 0,
        ],
        'rediscluster'=>[
            'type' => 'redis',
            'host'    => ['127.0.0.1:9000', '127.0.0.1:9001', '127.0.0.1:9002', '127.0.0.1:9003', '127.0.0.1:9004', '127.0.0.1:9005'],
            'prefix' => 'RCCache_',
            'expire' => 0
        ]
    ],
];