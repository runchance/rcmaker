# rcmaker 框架能力目录

本文件是所有 rcmaker 编码任务的必读入口。先通过这里定位能力，再打开对应文档核实参数和行为。不要根据其他 PHP 框架经验推测 rcmaker API。

## 强制复用规则

本目录列出的能力不是可选建议。只要当前 rcmaker 版本已经覆盖需求，`apps/`、`support/`、生产进程和业务脚本就必须使用对应框架 API，禁止另写原生 PHP 版本或安装重复组件。独立测试文件可以使用原生客户端、PDO 或底层运行时从外部验证结果，但测试例外不得被生产代码引用。

判断“框架没有能力”必须同时满足：项目内没有现成用法、`.agents/doc/` 对应文档没有能力、当前框架源码也没有公开助手或组件。任何一项尚未检查，都不能开始自定义实现。

## 事实来源优先级

1. 当前项目中已经运行的相邻代码和测试。
2. 当前项目的 `config/`、`.env.example`、`composer.json`。
3. `.agents/doc/md/` 下与任务对应的文档。
4. `vendor/runchance/rcmaker-framework/src/` 当前安装版本源码。

文档描述用途，源码决定当前版本实际行为。两者冲突时先按源码处理，并指出文档差异。

## 项目能力地图

| 领域 | 项目入口 | 文档入口 |
| --- | --- | --- |
| 应用与域名 | `config/app.php`, `apps/` | `.agents/doc/md/apps.md` |
| 控制器 | `apps/<app>/controller` | `.agents/doc/md/controller.md` |
| 路由 | `config/route.php` | `.agents/doc/md/route.md` |
| 请求与响应 | `RC\Request`, Response 映射 | `request.md`, `response.md`, `Helper.md` |
| 中间件 | `config/middleware.php`, `support/middleware` | `middleware.md` |
| 视图 | `config/view.php`, `view/` | `view.md` |
| 数据库 | `config/db.php`, `.env [db]` | `db/config.md`, `db/frame.md` |
| 模型 | `apps/<app>/model` | `db/model.md` |
| Redis | `config/redis.php` | `db/redis.md` |
| Session | `config/session.php` | `session.md` |
| 缓存 | `config/cache.php` | `module/cache.md` |
| Token | `config/token.php` | `module/token.md` |
| 队列 | `config/queue.php`, `support/queue` | `queue.md` |
| 自定义进程 | `config/process.php`, `support/process` | `process.md`, `app-process.md` |
| 异常 | `config/exception.php`, `support/exception` | `exception.md` |
| 日志 | `config/app.php`, `runtime/logs` | `log.md` |
| 运行引擎 | `config/worker.php`, `config/swoole.php` | `cli.md`, `fpm.md` |
| 混合运行 | Web 入口 + CLI 服务 | `mix.md` |
| SSL/HTTPS | Worker/Swoole/进程 context | `ssl.md` |
| 静态资源 | `public/`, 应用 `document_root` | `static.md`, `static-directory.md` |
| 打包、加密与交付 | `RC\Cli`, `php index.php interact` | `interact.md`；`scripts/` 文档仅为随时可能删除的兼容备选 |

以上省略了共同前缀 `.agents/doc/md/`。

## Request 输入能力

控制器第一个参数由框架注入。默认通过 `$req` 使用：

```php
$query = $req->get();
$page = (int) $req->get('page', 1);
$data = $req->post();
$name = $req->post('name', '');
$body = $req->rawBody();
$accept = $req->header('accept', '');
$cookie = $req->cookie('name', '');
$file = $req->file('upload');
$host = $req->host(true);
$method = $req->method();
$path = $req->path();
$ip = $req->ip();
```

补充能力：`queryString()`、`isAjax()`、`isPjax()`、`expectsJson()`、`acceptJson()`、`protocol()`、`remoteIp()`、`session()`。

只有框架适配层没有提供所需能力时才考虑 `$req->raw()` 或 `$req->getRequest()`。不得把用户输入作为 `raw()` 调用目标。业务代码不要直接依赖 Swoole 或 Workerman 原生请求，否则会降低跨引擎兼容性。

## Response 输出能力

Request 通过 `__call()` 映射需要请求上下文的全局助手。控制器优先写法：

```php
return $req->json(['code' => 0, 'msg' => 'ok', 'data' => $data]);
return $req->response('Hello', 200, ['X-App' => 'rcmaker']);
return $req->xml($xml);
return $req->jsonp($data, 'callback');
return $req->redirect('/login', 302);
return $req->V('user/detail', ['user' => $user]);
return $req->response()->file($path);
return $req->response()->download($path, $downloadName);
return $req->D($path, $downloadName);
```

`$req->json()` 自动编码并设置 JSON Content-Type。HTTP JSON 响应不要改写成 `json_encode()`、手工 Header 或 `echo`。`json_encode()` 只适用于存储、签名、日志、消息协议等非 HTTP 响应场景。

需要细调响应时使用响应对象的 `withHeader()`、`withHeaders()`、`withStatus()`、`withBody()`、`cookie()`、`file()`、`download()`，不要直接调用 PHP 原生响应函数。

## 路由、控制器与中间件

- 自动寻址由 `config/app.php` 的 `route` 控制。
- 自定义路由由 `with_custom_route` 和 `config/route.php` 控制。
- 路由支持 `Route::get/post/put/patch/delete/head/options/add/any/group/fallback`。
- 路由参数放在控制器第一个 `$req` 参数之后。
- 中间件实现 `handle($request, callable $next)`，通过 `$next($request)` 继续链路。
- 请求临时数据放入请求属性；中间件实例可能常驻，不能把用户状态保存在中间件对象属性中。
- JSON API 保持项目已有 envelope、状态码和异常处理约定。

## 数据访问选择

新业务数据库操作按以下顺序选择，不得从熟悉程度出发跳级：

| 优先级 | 场景 | 必须使用 |
| --- | --- | --- |
| 1 | 标准新增、修改、删除、单条查询、列表、分页、切换字段、字段校验和判重 | `$req->AF($vars)` / AutoForm |
| 2 | AutoForm 不适合表达的普通链式查询、聚合、关联或事务操作 | `$req->SDB()` / `SDB($req)` |
| 3 | AutoForm 与 SDB 都无法合理表达的复杂 SQL 或底层数据库特性 | `DB()` / `database()`，并记录下沉理由 |

不得使用 PDO、mysqli 或自建连接。不得把 `DB()` 当作普通查询的默认入口；仅“写起来更短”不构成复杂 SQL。Model 属于框架能力事实，但新业务数据操作仍遵守 AutoForm -> SDB -> DB 的层级。数据库支持类必须通过 `.env [bootstrap]`、`config/bootstrap.php` 或自定义进程 `bootstrap` 载入。详细规则见 [data-access.md](data-access.md)。

## 内置组件选择

| 需求 | 首选 API | 配置/文档 |
| --- | --- | --- |
| 多字段验证（外部输入必须使用） | `validator()->input()` | `module/validation.md` |
| 单字段验证（外部输入必须使用） | `validator()->check()` | `module/validation.md` |
| 标准数据库操作 | `$req->AF($vars)->handle()` | `module/autoform.md` |
| 缓存 | `cache()->get/set/remember()` | `config/cache.php`, `module/cache.md` |
| Redis | `redis()` / `RD()` | `config/redis.php`, `db/redis.md` |
| Session | `$req->session()` / `$req->S()` | `config/session.php`, `session.md` |
| Cookie | `$req->cookie()`, `$req->SC()` | `cookie.md` |
| Token/JWT | `$req->token()` | `config/token.php`, `module/token.md` |
| 验证码 | `$req->captcha()`, `$req->captchaCheck()` | `config/captcha.php`, `module/captcha.md` |
| 短信验证码 | `$req->sms()` | `config/sms.php`, `module/sms.md` |
| 邮件 | `mailer()` / `ML()` | `config/mailer.php`, `module/mailer.md` |
| 队列 | `queue()->send()` | `config/queue.php`, `queue.md` |
| 分页 | SDB `paginate()` / `RC\Helper\Paginator` | `module/paginator.md` |
| 外部 HTTP | `curl()` / `curl(true)` | `module/curl.md` |
| Excel | `xlsx()` / `X()` | `module/excel.md` |
| PDF | `$req->pdf()` / `$req->P()` | `module/pdf.md` |
| 二维码 | `$req->qrcode()` / `$req->Q()` | `module/qrcode.md` |
| 拼音 | `pinyin()` / `PY()` | `module/pinyin.md` |
| 性能计时 | `stopwatch()` | `module/stopWatch.md` |
| 限流 | `RC\Middleware\Throttler` | `module/throttler.md` |

## 数据采集和外部网络

网页抓取、第三方 REST API、Webhook 外发、远程文件下载、接口轮询和批量采集统一使用：

- 单请求：`curl()`。
- 并发请求：`curl(true)`。
- 入站 Webhook：使用 `$req` 读取请求，再用框架 Response 返回。

生产代码禁止使用 `curl_init()`、Guzzle、URL `file_get_contents()`、URL `fopen()`、自写 HTTP Socket、shell `curl` 或 `wget`。即使项目已经安装同类第三方客户端，也要先使用 rcmaker `curl()`；只有目标协议或认证流程被当前组件明确证明不支持时，才能说明证据并评估补充实现。

采集程序需要独立运行时，放入普通自定义进程或队列消费者；需要完整 HTTP APP 能力且要独立端口/资源时才使用 `type=app`。网络调用必须设置超时、处理状态码和错误，并控制并发、重试、退避、限速与幂等。

组件的完整方法、依赖、配置和返回值必须到对应文档核对。不要根据表格自行发明链式方法。

## 全局助手和 Request 映射

常用的请求上下文助手既可全局调用，也可通过 `$req` 映射：

| 全局助手 | Request 映射 |
| --- | --- |
| `response($req, ...)` / `R()` | `$req->response()` / `$req->R()` |
| `json($req, ...)` | `$req->json()` |
| `view($req, ...)` / `V()` | `$req->view()` / `$req->V()` |
| `model($req, ...)` / `M()` | `$req->model()` / `$req->M()` |
| `sessions($req, ...)` / `S()` | `$req->sessions()` / `$req->S()` |
| `autoForm($req, ...)` / `AF()` | `$req->autoForm()` / `$req->AF()` |
| `simple_database($req, ...)` / `SDB()` | `$req->simple_database()` / `$req->SDB()` |
| `token($req, ...)` / `T()` | `$req->token()` / `$req->T()` |
| `download($req, ...)` / `D()` | `$req->download()` / `$req->D()` |

控制器中优先使用 `$req` 映射，能明确体现请求上下文；没有 Request 映射的组件使用全局助手，例如 `DB()`、`cache()`、`redis()`、`queue()`、`curl()`、`mailer()`、`validator()`。

## 运行模式和进程能力

- CLI 可使用 Workerman 或 Swoole；配置分别位于 `config/worker.php`、`config/swoole.php`。
- FPM/MOD 使用 Web 入口，不启动 `config/process.php` 中的自定义进程。
- `type=app` 进程组获得完整 APP HTTP 能力，可独立端口、进程数、重启和资源参数。
- 普通自定义进程用于 HTTP/TCP/WebSocket/Text、定时器、文件监控、队列消费者等非 APP 场景。
- 应用通过 `config/app.php` 的 `bind_process` 唯一归属到 APP 进程组。
- 不使用同端口 `reusePort` 做按 Host 的应用归属分流。
- 静态能力包括应用级 `document_root`、静态 gzip、静态预加载和 `static_only`。
- 普通自定义监听进程支持 HTTP、TCP、WebSocket、Text 等协议，配置示例在 `.agents/doc/md/process/`。
- 定时任务和文件监控也属于普通自定义进程，不应伪装成 APP 进程组。
- HTTPS 优先在反向代理终止；需要框架直连 SSL 时按运行引擎配置证书和 context。
- 混合模式可以让传统 Web 入口与 CLI 常驻服务并存，先明确请求究竟由哪一侧处理。

详细约束见 [runtime-and-processes.md](runtime-and-processes.md)。

## 路径与运行助手

使用框架助手构造项目路径，避免依赖当前工作目录：

| 助手 | 用途 |
| --- | --- |
| `base_path()` | 项目根目录 |
| `runtime_path()` | 运行时目录 |
| `public_path()` | 公共资源目录 |
| `view_path()` | 视图目录 |
| `ssl_path()` | SSL 目录 |
| `is_phar()` | 判断是否在 PHAR/封装环境 |
| `getFilesize()` | 格式化文件大小 |

打包后工作目录可能变化。文件访问应从这些根路径出发，再进行真实路径和允许目录校验。

## Bootstrap、自动加载、日志与异常

- `config/bootstrap.php` 和 `.env [bootstrap]` 用于加载数据库、Redis 等启动能力。
- `config/autoload.php` 用于项目额外自动加载；先沿用当前规则，不另写重复 autoloader。
- 普通自定义进程需要的启动类放入该进程的 `bootstrap`，不要假设主 APP 已载入的所有状态都自动可用。
- CLI 访问日志由 `config/app.php` 的日志开关和框架日志进程控制，文档见 `.agents/doc/md/log.md`。
- 日志开关和稳定配置可在 Worker 启动时缓存，避免每请求重复解析；请求内容、用户信息和敏感 Header 不进入长期全局状态。
- 异常处理通过 `config/exception.php` 与 `support/exception` 统一管理。生产环境输出通用错误，详细信息写入受控日志。
- 不为普通业务错误调用 `var_dump()`、`echo` 或直接输出堆栈。

## 能力缺失时的处理顺序

1. 搜索当前项目和框架源码，确认不是遗漏了助手或组件。
2. 检查 Composer 是否已经安装合适依赖。
3. 评估第三方包的维护状态、许可证、常驻内存复用和 Swoole 协程安全。
4. 把第三方能力封装在项目服务层，保留 rcmaker Request/Response、配置和进程边界。
5. 只有逻辑很小且不存在可靠组件时才自行实现，并补测试。

## 常见错误信号

看到以下代码先暂停并核对：

- 控制器里 `json_encode()`、`header()`、`echo`。
- `new PDO()`、`mysqli_*`、每请求 `new Redis()`。
- `$_GET`、`$_POST`、`$_SESSION`、`setcookie()`、`readfile()`。
- 自己维护 JWT、验证码、分页元数据、Redis 队列协议或文件缓存。
- 使用没有在 rcmaker 源码或项目中定义的 `config()`、容器、Facade 或其他框架助手。
- 把 Request、Response、用户信息放进静态属性或单例。

在 rcmaker 生产业务代码中，这些属于阻断性信号：能由框架覆盖的必须改为框架能力。只有非 HTTP 序列化等用途或框架确无能力时才允许保留，并应留下清楚依据。测试目录不受此语法限制。
