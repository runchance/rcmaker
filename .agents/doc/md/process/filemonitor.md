# 文件监控

在CLI模式启动下PHP由于是常驻内存的,改动文件后不会立马生效,就需要做一个文件监听进程来监控文件改动,发现文件改动后会向主进程发送 `reload` 请求,以达到改动代码不重启立即看见效果

`./config/process.php` 加入如下配置

```php
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
```

处理类`RC\Helper\Process\FileMonitor::class` rcmaker已经集成,用户不需要再次创建,当然如果愿意你也可以新建一个处理类来处理

重启 `rcmaker`



