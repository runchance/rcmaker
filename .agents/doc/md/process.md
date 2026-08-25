# 自定义进程

rcmaker 在 CLI 模式下支持自定义进程，配置文件是 `./config/process.php`。

注意：

- 修改 `./config/process.php` 后，通常需要先停止再重新启动，新增或删除进程不会靠普通 reload 自动生效。
- 自定义进程大致分为两类：监听进程、非监听进程。

## 监听进程

监听进程会绑定一个协议地址，接收外部请求后交给你的处理类。

常见协议示例：

- `http://0.0.0.0:8681`
- `https://0.0.0.0:8681`
- `websocket://0.0.0.0:8682`
- `tcp://0.0.0.0:8683`
- `udp://0.0.0.0:8684`
- `text://0.0.0.0:8685`

## 非监听进程

非监听进程一般不监听端口，而是在进程启动后执行常驻任务，例如：

- 定时任务
- 文件监控
- 队列消费
- 自定义后台任务

不同 CLI 运行引擎下，监听类回调签名可能略有差异，写业务时优先参考对应示例页和 `support/process` 示例类。

## 配置项

`./config/process.php` 返回一个数组，数组键名就是进程名称。

常用配置项如下：

参数 | 说明
---|---
`handler` | 进程处理类。可以直接写完整类名，也可以写 `support/process` 下的短类名。
`listen` | 监听地址。未设置时表示非监听进程。
`count` | 进程数量。
`ssl` | 是否启用 SSL，通常只对监听进程有意义。
`context` | 上下文参数，常用于传递 SSL 证书配置。
`user` | 启动用户，一般不需要设置。
`group` | 启动用户组，一般不需要设置。
`reloadable` | 是否允许重载。
`reusePort` | 是否开启端口复用。
`bootstrap` | 进程启动前先执行的启动类，常用于初始化数据库、Redis 等依赖。
`constructor` | 传给进程处理类构造函数的自定义参数。
`autoload` | 进程启动时额外加载的文件列表。
`default_timezone` | 单独为该进程设置时区。

## 高阶能力

这页最容易漏掉的是下面这些能力，它们都已经在当前实现里支持：

### 1. `handler` 支持完整类名和短类名

下面两种写法都可以：

```php
'handler' => support\process\Http::class,
```

```php
'handler' => 'Http',
```

第二种写法会自动去 `./support/process/Http.php` 里加载 `support\process\Http`。

### 2. `constructor` 不只是自定义参数

框架在实例化处理类时，会先自动注入这些参数：

- `type`
- `worker`
- `timer`

然后再把 `constructor` 里的配置合并进去。

也就是说，你的构造函数除了自定义参数外，还可以直接拿到当前进程对象和计时器类。

### 3. `bootstrap` 适合做依赖预热

如果进程里需要用数据库、Redis、日志客户端或其它启动类，建议通过 `bootstrap` 提前加载，而不是在每次回调里临时初始化。

```php
'bootstrap' => [
    RC\Helper\Db\Laravel::class,
    RC\Helper\Redis\Raw::class,
],
```

### 4. `autoload` 可加载额外脚本

如果某些进程依赖单独的函数文件、兼容层或初始化脚本，可以在启动时额外引入：

```php
'autoload' => [
    BASE_PATH . '/support/process/helpers.php',
],
```

### 5. `default_timezone` 可单独覆盖时区

某些定时任务或第三方对接要求固定时区时，可以只改当前进程：

```php
'default_timezone' => 'Asia/Shanghai',
```

### 6. 队列消费进程可以自动并入

当 `config/queue.php` 里的 `enable` 为 `true` 时，`consumer_process` 中定义的消费进程会自动合并到进程配置里，不需要再重复写到 `config/process.php`。

这类消费进程默认就是通过 `RC\Helper\Process\QueueConsumer` 完成订阅和消费。

## 配置示例

### 独立 APP 进程

当一个 HTTP 端口需要拥有与主 APP 完全相同的路由、中间件、控制器、Session、静态文件、异常处理和全局启动能力，同时又需要独立的进程数及资源配置时，将进程的 `type` 设置为 `app`。APP 进程不需要配置 `handler`：

> [!TIP]
> 从应用绑定开始的完整配置教程、同组多应用、静态应用和反向代理示例，请参考 [应用进程组](md/app-process.md)。

```php
'RC_APP_API' => [
    'type' => 'app',
    'listen' => 'http://0.0.0.0:8682',
    'count' => 4,
	'default_app' => 'api',
    'max_request' => 500000,
    'memory_limit' => '256M',
    'reusePort' => true,
],
```

APP 进程默认继承当前运行引擎的主配置：Workerman 使用 `config/worker.php`，Swoole 使用 `config/swoole.php`。进程配置中出现的同名选项会覆盖主配置，因此通常只需要填写 `type`、`listen` 和 `count`。

应用通过 `config/app.php` 中的 `bind_process` 绑定该进程组：

```php
'app' => [
    'index' => [],
    'api' => [
        'bind_process' => 'RC_APP_API',
    ],
],
```

未设置 `bind_process` 的应用只由主 APP 进程组处理。设置后，应用不会再被主 APP 或其他 APP 进程组访问；目标进程组没有启动时也不会自动回退。

可独立覆盖的常用选项包括：

参数 | 说明
---|---
`listen` | 独立监听地址，APP 进程必须使用 HTTP 或 HTTPS。
`count` | 独立进程数量。
`max_request` | 当前 APP Worker 的最大请求数。
`memory_limit` | 当前 APP Worker 的 PHP 内存限制，例如 `256M`。
`reusePort` | 是否启用端口复用；不能用来把同一端口按域名分给不同 APP 进程组。
`ssl` / `context` | HTTPS 开关和证书上下文。
`bootstrap` / `autoload` | 在全局配置基础上，为该 APP 进程追加启动类或文件。
`default_timezone` | 只覆盖该 APP 进程的时区。
`default_app` | 当前 APP 进程组的默认应用，必须是绑定到该组的应用。

> [!IMPORTANT]
> Swoole 下只有协程模式能够为每个 APP 监听端口分配独立的进程池。使用 `type => app` 时必须保持 `swoole.coroutine = true`；非协程模式会明确拒绝启动，防止多个端口实际共享同一组 Worker。

> [!NOTE]
> APP 进程与主 APP 共享项目配置和 Session 存储，但拥有独立监听端口、Worker 数量、进程内存及重启生命周期。Windows 下受 Workerman 单机运行机制影响，生产环境的多进程部署仍建议使用 Linux。

> [!TIP]
> Linux 下静态预热在 fork 前全局执行一次，各 APP Worker 通过写时复制继承预热内容；请求阶段只允许访问当前进程组绑定应用的静态目录。Windows 不支持 fork，主 APP 子进程只预热未绑定应用，自定义 APP 子进程只预热 `bind_process` 与自身进程名一致的应用。

```php
<?php
// 自定义进程
return [
    // 文件更新检测
    'RC_monitor' => [
        'handler' => RC\Helper\Process\FileMonitor::class,
        'reloadable' => false,
        'constructor' => [
            'monitor_dir' => [
                BASE_PATH . '/apps',
                BASE_PATH . '/config',
                BASE_PATH . '/support',
                BASE_PATH . '/view',
                BASE_PATH . '/.env',
            ],
            'monitor_extensions' => [
                'php', 'html', 'htm',
            ],
        ],
    ],

    // HTTP 监听进程
    'RC_HTTP' => [
        'handler' => support\process\Http::class,
        'listen' => 'http://0.0.0.0:8681',
        'count' => 8,
        'reusePort' => true,
        'ssl' => false,
        'context' => [
            'ssl' => [
                'local_cert' => '/YourPath/server.crt',
                'local_pk' => '/YourPath/server.key',
                'verify_peer' => false,
                'allow_self_signed' => true,
            ],
        ],
        'bootstrap' => [
            RC\Helper\Db\Laravel::class,
        ],
        'autoload' => [
            BASE_PATH . '/support/process/http_helpers.php',
        ],
        'default_timezone' => 'Asia/Shanghai',
    ],

    // text 协议进程
    'RC_RPC' => [
        'handler' => support\process\Rpc::class,
        'listen' => 'text://0.0.0.0:8684',
        'count' => 8,
    ],

    // TCP 进程
    'RC_TCP' => [
        'handler' => support\process\Tcp::class,
        'listen' => 'tcp://0.0.0.0:8683',
        'count' => 2,
        'reusePort' => true,
    ],

    // WebSocket 进程
    'RC_websocket' => [
        'handler' => support\process\Websocket::class,
        'listen' => 'websocket://0.0.0.0:8682',
        'count' => 3,
        'reusePort' => true,
    ],

    // 定时任务
    'RC_Crontab_Task' => [
        'handler' => support\process\Crontab::class,
    ],
];
```

## 内置能力

框架自带几个常用进程能力：

- 文件监控：见 `process/filemonitor.md`
- 定时任务：见 `process/crontab.md`
- 队列消费：通过 `RC\Helper\Process\QueueConsumer` 接入

如果开启了 CLI 访问日志，框架还会自动注入内部日志进程，不需要手动配置。

## 建议

- 需要数据库、Redis 或其它连接时，优先放到 `bootstrap` 里初始化。
- 监听进程尽量保持无状态，不要把请求级数据挂在单例或静态属性上。
- 文件监控、消费进程这类常驻任务，构造函数里应尽快启动订阅、定时器或监听逻辑。
- 配置了 SSL 时，优先使用正式证书，不要把关闭校验当成默认方案。
