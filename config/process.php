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

    //该进程是独立的APP监听器，它继承了主APP的运行时，并且只覆盖了这里声明的选项。
    // 该进程可以单独运行，独立于主APP进程，适用于需要独立处理请求的场景。
    
    /*
    'RC_APP_API' => [
        'type' => 'app',
		'listen' => 'http://0.0.0.0:8682',
        'count' => 4,
		'default_app' => 'api',
        'max_request' => 500000,
        'memory_limit' => '256M',
        'reusePort' => true,
    ],
    */

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
