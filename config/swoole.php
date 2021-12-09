<?php
return [
    //名称
    'name'                 => 'RC_Swoole',
	//监听地址
    'listen'               => rcEnv('swoole.listen', 'http://0.0.0.0'),
    //监听端口
    'port'                 => rcEnv('swoole.port', 8680),
    //协程风格
    'coroutine'            => rcEnv('swoole.coroutine', true),
    //swoole运行模式
    'run_model'            => SWOOLE_PROCESS,
    //是否开启SSL,如果开启必须配置SSL上下文context.ssl.local_pk 及 context.ssl.local_cert
    'ssl'                  => rcEnv('swoole.ssl', false),
    //是否不同协议允许共用端口
    'enable_reuse_port'    => rcEnv('swoole.enable_reuse_port', false) ? true : false,
    //启用压缩
    'http_compression'     => rcEnv('swoole.http_compression', true) ? true : false,
    //静态文件请求路径
    'document_root'        => BASE_PATH . '/public',
    //Swoole自带静态文件访问控制,最好不要开启,4.X版严重影响并发，框架带有CLI自动寻找静态文件（设置enable_static_file）
    'enable_static_handler'=> rcEnv('swoole.enable_static_handler', false) ? true : false,
    //静态文件访问控制(框架内置)
    'enable_static_file'   => rcEnv('swoole.enable_static_file', true),
    //是否支持静态文件访问php
    'enable_static_php'    => rcEnv('swoole.enable_static_php', false),
    //守护进程化 
    'daemonize'            => rcEnv('swoole.daemonize', false) ? true : false,
    //线程数 默认1
    'reactor_num'          => rcEnv('swoole.reactor_num', 1),
    //进程数量,默认CPU核数x2
    'worker_num'           => rcEnv('swoole.worker_num', 8),
    //最大连接数
    'max_conn'             => rcEnv('swoole.max_conn', 0),
    //是否开启协程支持
    'enable_coroutine'     => rcEnv('swoole.enable_coroutine', true) ? true : false,
    //上下文传递
    'context'              => [
        'ssl'=>[
            'local_cert'  => rcEnv('ssl.local_cert', 'server.crt'),
            'local_pk'  => rcEnv('ssl.local_pk', 'server.key'),
            'verify_peer'  => rcEnv('ssl.verify_peer', false) ? true : false,
            'allow_self_signed' => rcEnv('ssl.allow_self_signed', true) ? true : false,
        ]
    ],
    //子进程的所属用户
    'user'                 => rcEnv('swoole.user', ''),
    //子进程的所属用户组
    'group'                => rcEnv('swoole.group', ''),
    //pid文件路径
    'pid_file'             => BASE_PATH . '/runtime/RC_Swoole.pid',
    //最大请求数,超出后会自动杀死进程并重新拉取一个新的进程
    'max_request'          => rcEnv('swoole.max_request', 1000000),
    //日志文件路径
    'log_file'             => BASE_PATH . '/runtime/logs/RC_Swoole.log',
    //日志等级
    'log_level'            => SWOOLE_LOG_INFO,
    //设置最大数据包尺寸，单位为字节
    'package_max_length'   => 10 * 1024 * 1024,
    //配置客户端连接的缓存区长度，单位为字节
    'socket_buffer_size'   => 512 * 1024 * 1024,
    //table内存最大使用行数
    'table_size'   => 1024 * 1024
];
?>