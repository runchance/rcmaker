## 应用进程组

应用进程组用于把 `apps` 目录中的一个或多个应用，交给指定的独立 HTTP 进程组运行。

它适合这样的项目：业务仍然放在同一个 rcmaker 工程中，共享 Composer 依赖和框架配置，但不同应用需要独立端口、进程数量、内存限制或请求回收策略。

```text
同一个 rcmaker 项目
├── 主 APP 进程组       处理未绑定的应用
├── RC_APP_API          处理 api 应用
├── RC_APP_ADMIN        处理 admin 应用
└── RC_STATIC           处理 website 静态应用
```

> [!TIP]
> 应用进程组仍然使用完整的 rcmaker 请求链，包括路由、中间件、控制器、Session、异常处理和静态文件。它与配置了 `handler` 的普通 HTTP 自定义进程不是同一种进程。

### 两个配置入口

应用和进程组分别在两个文件中配置：

| 配置文件 | 负责内容 |
| --- | --- |
| `config/app.php` | 声明应用属于哪个进程组，并配置域名、路由和静态目录。 |
| `config/process.php` | 声明进程组的监听端口、进程数和资源参数。 |

它们通过进程组名称连接：

```php
// config/app.php
'bind_process' => 'RC_APP_API',
```

```php
// config/process.php
'RC_APP_API' => [
    'type' => 'app',
    // ...
],
```

`RC_APP_API` 必须完全一致，包括大小写。

### 绑定规则

框架按照下面的规则决定应用在哪里生效：

| 应用配置 | 生效位置 |
| --- | --- |
| 未设置 `bind_process` | 只在主 APP 进程组生效。 |
| `bind_process => RC_APP_API` | 只在 `RC_APP_API` 生效。 |
| 目标进程组未配置或未启动 | 应用不可用，不会回退主 APP。 |
| `start_app=false` 且应用未绑定进程组 | 应用不可用，因为主 APP 没有启动。 |

当前 `bind_process` 接收一个进程组名称，同一个应用不能同时绑定多个不同进程组。需要提高并发时，应增加所属进程组的 `count`，或者在进程组前部署多个相同实例并交给负载均衡器。

绑定同时限制：

- 自动寻址路由
- 应用绑定域名
- 控制器和应用中间件
- 应用静态目录
- 静态预热缓存的访问

进程是否存活不会在每个请求中实时查询。绑定关系在 Worker 启动时确定，因此目标进程崩溃后，应用也不会临时转移到主 APP。

### 快速开始：独立 API

假设项目包含两个应用：

```text
apps
├── index
│   └── controller
│       └── index.php
└── api
    └── controller
        └── index.php
```

主应用继续监听 `8680`，API 使用独立的 `8681`。

第一步，在 `config/app.php` 绑定 API：

```php
'default_app' => 'index',

'app' => [
    'index' => [
        'domains' => ['www.rcmaker.com'],
    ],

    'api' => [
        'domains' => ['api.rcmaker.com'],
        'bind_process' => 'RC_APP_API',
    ],
],
```

第二步，在 `config/process.php` 创建 APP 进程组：

```php
'RC_APP_API' => [
    'type' => 'app',
    'listen' => 'http://0.0.0.0:8681',
    'count' => 4,
    'default_app' => 'api',
    'max_request' => 500000,
    'memory_limit' => '256M',
],
```

第三步，停止并重新启动 rcmaker。新增或删除进程组属于进程拓扑变化，不应只执行普通 reload。

本机测试时，可以直接指定 `Host`：

```bash
curl -H "Host: api.rcmaker.com" http://127.0.0.1:8681/
```

此时的访问边界是：

| 请求 | 结果 |
| --- | --- |
| `api.rcmaker.com:8681` | 进入 `api` 应用。 |
| `api.rcmaker.com:8680` | 返回 404，主 APP 不处理已绑定的 API。 |
| `www.rcmaker.com:8680` | 进入 `index` 应用。 |
| 8681 没有进程监听 | 连接被拒绝或超时，不会回退 8680。 |

### 一个进程组绑定多个应用

多个应用可以填写相同的 `bind_process`。它们共享端口和 Worker 资源，但仍然按照域名或应用路径选择应用。

#### 使用不同域名

```php
// config/app.php
'app' => [
    'api' => [
        'domains' => ['api.rcmaker.com'],
        'bind_process' => 'RC_APP_SERVICE',
    ],
    'admin' => [
        'domains' => ['admin.rcmaker.com'],
        'bind_process' => 'RC_APP_SERVICE',
    ],
],
```

```php
// config/process.php
'RC_APP_SERVICE' => [
    'type' => 'app',
    'listen' => 'http://0.0.0.0:8681',
    'count' => 8,
    'default_app' => 'api',
    'memory_limit' => '512M',
],
```

访问结果：

```text
api.rcmaker.com:8681    -> api
admin.rcmaker.com:8681  -> admin
```

两个应用共用 `count=8` 的 Worker 池，并不是每个应用各有 8 个 Worker。重启这个进程组会同时影响两个应用。

#### 不绑定域名

不设置 `domains` 时，同一进程组中的多个应用可以使用应用名前缀：

```php
'app' => [
    'api' => [
        'bind_process' => 'RC_APP_SERVICE',
    ],
    'admin' => [
        'bind_process' => 'RC_APP_SERVICE',
    ],
],
```

```text
http://127.0.0.1:8681/api/index/index
http://127.0.0.1:8681/admin/index/index
```

当 URL 第一段不是应用名时，会进入当前进程组的 `default_app`。一个进程组包含多个应用时，建议始终显式配置 `default_app`。

### 独立静态应用

静态应用也可以绑定独立进程组：

```php
// config/app.php
'website' => [
    'domains' => ['admin.rcmaker.com'],
    'bind_process' => 'RC_STATIC',
    'document_root' => 'website',
    'index_default' => 'index.html',
    'enable_static_file' => true,
    'enable_static_gzip' => true,
    'enable_static_preload' => true,
    'static_preload_time_limit' => 0.5,
    'static_only' => true,
],
```

```php
// config/process.php
'RC_STATIC' => [
    'type' => 'app',
    'listen' => 'http://0.0.0.0:8682',
    'count' => 2,
    'default_app' => 'website',
    'memory_limit' => '256M',
],
```

对应目录：

```text
public
└── website
    ├── index.html
    ├── assets
    └── js
```

访问根路径 `/` 时，框架查找：

```text
public/website/index.html
```

`static_only=true` 表示文件未命中时直接返回 404，不再执行控制器、自定义动态路由或 PHP 静态文件。它适合文档站、H5、前端构建产物和纯资源服务。

> [!IMPORTANT]
> 目录存在不代表根路径一定可访问。访问 `/` 时，目录下必须存在 `index_default` 指定的文件；默认值是 `index.html`。

如果应用还需要执行 API 或控制器，不要开启 `static_only`：

```php
'enable_static_file' => true,
'static_only' => false,
```

### 公网域名与反向代理

不同 APP 进程组应使用不同的内部端口，再由 Nginx、Caddy 或负载均衡器按照域名转发。

```nginx
server {
    listen 80;
    server_name www.rcmaker.com;

    location / {
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_pass http://127.0.0.1:8680;
    }
}

server {
    listen 80;
    server_name api.rcmaker.com;

    location / {
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_pass http://127.0.0.1:8681;
    }
}

server {
    listen 80;
    server_name admin.rcmaker.com;

    location / {
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_pass http://127.0.0.1:8682;
    }
}
```

外部用户仍然访问标准端口：

```text
http://www.rcmaker.com
http://api.rcmaker.com
http://admin.rcmaker.com
```

内部的 rcmaker 端口保持独立，便于分别配置进程数和资源限制。

### 不要用 reusePort 做域名分流

主 APP 和自定义 APP 处理不同应用时，不要让它们通过 `reusePort` 监听相同的 `IP:端口`。

```php
// 不推荐：主 APP 和 RC_APP_API 都监听 8680
'listen' => 'http://0.0.0.0:8680',
'reusePort' => true,
```

`reusePort` 在 TCP 连接建立时分配监听 socket，不会读取 HTTP `Host`。连接一旦进入主 APP，`api.rcmaker.com` 也只会得到主 APP 的 404；Keep-Alive 会让后续请求继续停留在同一进程组。

`reusePort` 适合处理能力完全相同的一组 Worker。扩展同一个 APP 进程组时，直接增加它的 `count`：

```php
'RC_APP_API' => [
    'type' => 'app',
    'listen' => 'http://0.0.0.0:8681',
    'count' => 16,
],
```

### 进程组配置参数

APP 进程组默认继承当前运行引擎的主配置：Workerman 继承 `config/worker.php`，Swoole 继承 `config/swoole.php`。进程组中的同名配置会覆盖主配置。

| 参数 | 说明 |
| --- | --- |
| `type` | 必须是 `app`，不需要设置 `handler`。 |
| `listen` | 独立 HTTP 或 HTTPS 监听地址。 |
| `count` | 该进程组的 Worker 数量。 |
| `default_app` | 该组的默认应用，必须绑定到当前进程组。 |
| `max_request` | 单个 Worker 达到请求数后执行回收。 |
| `memory_limit` | 当前 Worker 的 PHP 内存限制。 |
| `reusePort` | 相同处理能力的 Worker 端口复用选项，不能用于域名分流。 |
| `ssl` / `context` | HTTPS 开关及证书上下文。 |
| `bootstrap` | 在全局启动类基础上追加当前组需要的启动类。 |
| `autoload` | 在全局自动加载文件基础上追加文件。 |
| `default_timezone` | 单独覆盖当前组的时区。 |

应用级配置仍然放在 `config/app.php`，例如 `route`、`domains`、`document_root` 和静态文件相关配置。

### 数据与内存边界

同一项目的 APP 进程组共享代码和配置来源，但每个 Worker 都是独立 PHP 进程：

- PHP 静态属性和普通内存变量不跨进程共享。
- 数据库、Redis 和外部服务可以使用相同配置。
- Session 是否跨进程共享，取决于是否使用相同的 Session 存储和 Cookie 配置。
- 一个进程组绑定多个应用时，它们共享该组的 Worker 数量和资源上限。
- 应用绑定控制请求入口，不等同于操作系统级安全沙箱。

请求级数据不要保存在会跨请求复用的静态属性或单例属性中。

### 静态预热策略

静态预热会遵守应用的 `bind_process`：

| 平台 | 预热方式 |
| --- | --- |
| Linux | 主控进程在 fork 前全局预热一次，APP Worker 通过 Copy-on-Write 继承缓存。 |
| Windows 主 APP | 只预热未设置 `bind_process` 的应用。 |
| Windows 自定义 APP | 只预热绑定到当前进程组的应用。 |

Windows 没有 fork，同一个静态应用如果由多个独立进程组处理，每个进程组仍然需要自己的缓存。更多配置参考 [静态目录 / 应用](md/static-directory.md)。

### 运行引擎限制

| 运行方式 | 支持情况 |
| --- | --- |
| Workerman CLI | 支持独立 APP 进程组。 |
| Swoole 协程模式 | 支持，每个 APP 监听地址使用独立进程池。 |
| Swoole 非协程模式 | 不支持独立 APP 进程组，框架会拒绝启动。 |
| PHP-FPM / PHP-MOD | 不启动 `config/process.php`，绑定到自定义进程组的应用不会由 FPM 主请求入口处理。 |

Windows 可以用于开发测试；需要多进程和稳定资源治理时，生产环境建议使用 Linux。

> [!IMPORTANT]
> Windows 单进程运行通过，只能说明本地开发流程可用，不能替代 Linux 多进程验证。不要用静态属性、全局数组、单例字段或本地临时文件保存需要跨请求、跨 Worker 共享的状态；这类状态应交给框架 Cache、Redis、Session 或数据库组件。进程数、端口、驱动和连接信息应通过 `.env` 与配置文件切换，不要写进业务代码。上线前请在 Linux 的实际进程组和进程数下验证并发、状态共享与重启行为。

### 常见问题

| 现象 | 检查方法 |
| --- | --- |
| 自定义端口连接被拒绝 | 检查目标进程组是否存在、是否启动，以及 `listen` 端口是否被占用。 |
| 主 APP 访问绑定应用返回 404 | 这是正常隔离行为，应访问应用绑定的进程组端口。 |
| 自定义端口访问应用返回 404 | 检查 `bind_process` 是否与进程组数组键完全一致。 |
| 使用域名访问返回 404 | 检查 `domains` 和请求的 `Host`，反向代理必须传递原始 Host。 |
| 根路径静态应用返回 404 | 检查 `document_root/index_default` 文件是否真实存在。 |
| 相同端口始终进入主 APP | 不要使用 `reusePort` 在不同 APP 进程组之间做域名分流，改用不同内部端口。 |
| 修改进程配置后没有新进程 | 停止后重新启动；普通 reload 不负责新增或删除进程组。 |
| Windows 出现多次静态预热 | 确认使用的是按进程组预热的新版本；多个进程组确实绑定同一静态应用时仍会分别缓存。 |
| `default_app` 启动报错 | 确认默认应用的 `bind_process` 指向当前进程组。 |

### 配置检查清单

启动前可以按顺序检查：

- `bind_process` 与 `config/process.php` 的数组键完全一致。
- 目标进程配置了 `type => app`，没有配置普通进程的 `handler`。
- 每个不同能力的 APP 进程组使用不同内部端口。
- `default_app` 属于当前进程组。
- 域名应用的 `domains` 与反向代理 `Host` 一致。
- 静态应用的 `document_root` 和 `index_default` 文件存在。
- 新增、删除或调整进程组后执行完整重启。

多应用的一般寻址规则参考 [多应用](md/apps.md)，普通自定义进程参考 [自定义进程](md/process.md)。
