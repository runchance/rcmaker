<?php
//自定义进程
return [
    // 文件更新检测
    'RC_monitor' => [
        'handler'     => RC\Helper\Process\FileMonitor::class,
        'reloadable'  => false,
        'constructor' => [
            // 监控这些目录
            'monitor_dir' => [
                BASE_PATH . '/apps',
                BASE_PATH . '/config',
                BASE_PATH . '/support',
                BASE_PATH . '/view',
                BASE_PATH . '/.env'
            ],
            // 监控这些后缀的文件
            'monitor_extensions' => [
                'php', 'html', 'htm'
            ]
        ],
    ],

    //http进程
    
    /*
    'RC_HTTP'  => [
        'handler'  => support\process\Http::class,
        'reusePort' => true,
        'listen' => 'http://0.0.0.0:8681',
        'ssl'=>false,
        //上下文传递
        'context' => [
            'ssl'=>[
                'local_cert' =>'/YourPath/server.crt',
                'local_pk' => '/YourPath/server.key',
                'verify_peer'  => false,
                'allow_self_signed' => true

            ] 
        ],
        'count'  => 8,
        'bootstrap'=>[
            RC\Helper\Db\Laravel::class,
        ]
    ],
    */

    //rpc进程
    
    /*
    'RC_RPC'  => [
        'handler'  => support\process\Rpc::class,
        'listen'  => 'text://0.0.0.0:8684',
        'count'  => 8
    ],
    */

    // tcp进程
    
    /*
    'RC_TCP'  => [
        'handler'  => support\process\Tcp::class,
        'reusePort' => true,
        'listen' => 'tcp://0.0.0.0:8683',
        'count'  => 2,
    ],
    */
    

    // websocke进程
    
    /*
    'RC_websocket'  => [
        'handler'  => support\process\Websocket::class,
        'reusePort' => true,
        'listen' => 'websocket://0.0.0.0:8682',
        'count'  => 3
    ],
    */
    
    //定时任务

    /*
    'RC_Crontab_Task'  => [
       'handler'  => support\process\Crontab::class,
    ],
    */
    
];
