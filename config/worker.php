<?php
return [
	//监听地址:端口
    'listen'               => rcEnv('workerman.listen', 'http://0.0.0.0:8680'),
    //协议 如果设置为 ssl 则需要正确设置上下文 context.ssl
    'transport'            => rcEnv('workerman.transport', 'tcp'),
    //上下文传递
    'context'              => [   
        'ssl'=>[
            'local_cert'  => rcEnv('ssl.local_cert', 'server.crt'),
            'local_pk'  => rcEnv('ssl.local_pk', 'server.key'),
            'verify_peer'  => rcEnv('ssl.verify_peer', false) ? true : false,
            'allow_self_signed' => rcEnv('ssl.allow_self_signed', true) ? true : false,
        ]
    ],
    //名称
    'name'                 => rcEnv('workerman.name', 'RC_workerman'),
    //进程数量,默认CPU核数x2
    'count'                => rcEnv('workerman.count', 16),
    //是否不同协议允许共用端口
    'reusePort'            => rcEnv('workerman.reusePort', true) ? true : false,
    //子进程的所属用户
    'user'                 => rcEnv('workerman.user', ''),
    //子进程的所属用户组
    'group'                => rcEnv('workerman.group', ''),
    //静态文件请求路径
    'document_root'        => BASE_PATH . '/public',
    //静态文件访问控制(内置)
    'enable_static_file'   => rcEnv('workerman.enable_static_file', true) ? true : false,
    //是否支持静态文件访问php
    'enable_static_php'    => rcEnv('workerman.enable_static_php', false) ? true : false,
    //pid文件路径
    'pid_file'             => BASE_PATH . '/runtime/RC_Workerman.pid',
    //最大请求数,超出后会自动杀死进程并重新拉取一个新的进程
    'max_request'          => 1000000,
    //日志文件路径
    'stdout_file'          => BASE_PATH . '/runtime/logs/RC_Workerman.log',
    //设置最大数据包尺寸，单位为字节
    'max_package_size'     => 10*1024*1024,
    //日志记录监听
    'logger_listen'     => rcEnv('workerman.logger_listen', 'Text://127.0.0.1:8689')
];
?>