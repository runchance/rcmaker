---
name: rcmaker-development
description: Develop, extend, debug, optimize, test, and package rcmaker PHP applications under mandatory framework-native reuse rules. Use for rcmaker controllers, APIs, CRUD, routes, middleware, request/response handling, data collection, databases, models, SDB, AutoForm, validation, cache, Redis, sessions, tokens, queues, built-in components, multi-application configuration, APP process groups, Workerman or Swoole runtimes, static preload, performance, source protection, buildBin, x86_64, or AArch64 delivery.
---

# rcmaker 快速开发

使用 rcmaker 已有能力完成需求。目标不是写出“能运行的通用 PHP”，而是写出符合当前 rcmaker 项目约定、可在常驻内存和多进程环境中稳定运行的代码。

> **硬性契约：框架已经提供的能力，业务代码必须复用，禁止自行实现同类功能。** 不知道 API、觉得原生写法更快、习惯其他框架、想减少查文档时间，都不是绕过 rcmaker 的理由。AI 生成的独立测试文件和测试夹具可以使用原生能力验证框架行为，但例外不得进入生产目录或被业务代码引用。

> **绝对禁止修改 `vendor/`。** `vendor/` 仅用于只读检索、接口核对和问题定位；任何时候都不得直接编辑、覆盖、删除或新增其中的文件，框架开发、兼容修复和紧急排障也没有例外。需要修复框架或第三方依赖时，必须修改对应的可维护源仓库、依赖声明或上游版本，再通过规范的依赖安装流程更新项目。

原生 PHP 语法可以正常使用，包括控制流、数组与字符串处理、领域算法、数据转换和框架未覆盖的业务逻辑。禁止的是用原生 PHP 或第三方包重复实现 rcmaker 已有的请求、响应、验证、数据库层、缓存、会话、Token、队列、HTTP 客户端等能力。

## 不可跳过的原则

1. **先查框架，再写代码。** 实现请求、响应、数据库、验证、CRUD、缓存、会话、鉴权、队列、分页、数据抓取、HTTP 客户端、文件或文档功能前，必须先确认 rcmaker 是否已有能力。在完成能力检索前，不得开始写替代实现。
2. **框架能力不是“优先项”，而是唯一默认实现。** rcmaker 已提供合适能力时必须使用；禁止因为更熟悉原生 PHP、PDO、cURL、第三方 SDK 或其他框架而重复实现。
3. **使用 AI 本地文档。** 项目已在 `./.agents/doc/` 提供完整 V3 文档，所有框架任务必须优先读取这里的对应 Markdown；接口仍不明确时查看 `vendor/runchance/rcmaker-framework/src/`。仅本地文档确实缺失时才访问 `https://rcmaker.runchance.com/doc/`，不要猜方法名或参数。
4. **验证必须使用验证器。** 所有外部输入校验必须使用 `validator()` / `VD()` 的 `input()`、`check()`，或 AutoForm 的 `data` 验证规则；不得用散落的正则、`filter_var()` 或类型判断替代验证器组件。
5. **数据库按固定层级选择。** 标准 CRUD、列表、分页和字段驱动操作使用 AutoForm；不适合 AutoForm 的普通数据库操作使用 `SDB()`；只有 AutoForm 与 SDB 都无法合理表达的复杂 SQL 才允许使用 `DB()` / `database()`。业务代码不得直接创建 PDO 或 mysqli 连接。
6. **第三方包是补充。** 只有框架没有对应能力、现有能力明显不适合需求，或用户明确指定时，才引入 Composer 包。先检查 `composer.json` 和现有依赖。
7. **不得静默绕过。** 只有当前版本确实没有所需能力时，才允许新增实现。实施前必须给出项目搜索、文档和源码三项核对结果，说明缺口、替代方案及常驻进程风险。
8. **测试例外必须隔离。** `tests/`、`test/`、测试夹具和一次性诊断脚本可以使用原生 HTTP、PDO 或底层运行时 API 做独立验证；不得把测试回退代码复制到 `apps/`、`support/` 或生产进程中。
9. **开发环境不能决定业务实现。** Windows 通常只是单进程开发环境，可能使用 SQLite 和 file 缓存；正式环境通常是 Linux 多进程，并切换到 Redis、MySQL 或 PostgreSQL。业务代码必须通过 rcmaker 配置与组件保持可移植，禁止写死平台、进程数、数据库驱动、SQL 方言、缓存驱动、连接地址或本机路径。
10. **自定义能力必须遵守项目目录边界。** 只有确认 rcmaker 没有对应能力后，才能新增实现；新增文件必须按职责放入现有 `apps/`、`support/`、`config/`、`public/`、`view/`、`tests/`、`scripts/` 或文档目录。禁止为了省事在项目根目录创建控制器、服务、模型、任务、脚本、测试、样例或临时文件。开发人员在当前交互中明确指定路径或明确批准例外时，可在授权范围内偏离本条目录限制；不得自行推定、扩大或延续该特许。
11. **代码必须便于人类阅读和审查。** 新增或修改的类、方法必须有准确注释，说明职责、调用场景和使用方法；公开接口还要写清参数、返回值、异常和必要示例。代码必须正常换行和格式化，使用可理解的命名与结构，禁止压缩成难以阅读的一行代码、堆叠表达式或仅追求短小的写法。
12. **`vendor/` 永远只读。** 可以阅读当前安装版本源码来确认行为，但不得把任何修复直接写入 `vendor/`。发现框架或依赖缺陷时，记录所属包与最小复现，在对应源仓库修复并发布版本，或调整 Composer 依赖后重新安装；不得把手工修改安装产物当作解决方案。

## 每次实现前必须读取

- 所有编码任务：先读 [framework-capabilities.md](references/framework-capabilities.md)。
- HTTP、控制器、路由、中间件、请求或响应：再读 [project-workflow.md](references/project-workflow.md)。
- 数据库、模型、CRUD、列表或分页：再读 [data-access.md](references/data-access.md)。
- 验证、缓存、Redis、Session、Token、队列或其他组件：再读 [components.md](references/components.md)。
- 多应用、APP 进程组、Workerman、Swoole、静态应用：读 [runtime-and-processes.md](references/runtime-and-processes.md)。
- 开发管理控制台、后台管理、运营平台或内部工作台：如果用户没有指定前端框架且项目没有既有前端框架，同时读取 `../pear-admin-product-ui/SKILL.md`，按其中流程下载并使用 Pear Admin 模板；用户指定的技术选型和项目既有选型优先。
- 新建管理控制台的后端默认使用 rcmaker Token 组件提供 JWT 鉴权；静态控制台必须绑定独立 `type=app` 进程组并完成核心资源预加载，不得与主 APP 进程组混合运行。具体配置与验收读取 Pear Admin Skill 的 `references/rcmaker-patterns.md` 和 `references/acceptance-checklist.md`。
- 修改框架源码、请求生命周期、配置继承、Worker 回收或静态预热机制：读 [framework-internals.md](references/framework-internals.md)。
- 性能、并发、安全或代码审查：读 [quality-and-performance.md](references/quality-and-performance.md)。
- 启动失败、404、端口、Windows、依赖兼容、状态污染或压测异常：读 [diagnostics.md](references/diagnostics.md)。
- 打包、加密、独立运行或不同 CPU 架构：读 [delivery.md](references/delivery.md)。
- 不确定能力或文档位置：读 [documentation-map.md](references/documentation-map.md)，再打开对应原文。

不要只凭本文件中的速查表直接猜复杂组件用法。涉及组件时打开 `.agents/doc/` 中的对应文档并核对当前源码。

## 编码前能力闸门

任何生产代码实现前，必须按顺序完成：

1. 搜索目标应用和 `support/` 的相邻实现，确认项目已经采用的框架写法。
2. 在 [framework-capabilities.md](references/framework-capabilities.md) 定位能力，并打开对应 `.agents/doc/md/` 原文。
3. 对方法签名、返回类型或常驻复用行为不确定时，检查 `src/Start.php`、`src/Request.php` 和对应 Helper 源码。
4. 写出本次能力映射，例如“抓取=`curl()`、验证=`validator()`、数据=`AutoForm` 或 `SDB()`、响应=`$req->json()`”。
5. 仅当前三步均确认没有能力时，才评估现有 Composer 依赖、新依赖或小型自定义实现。
6. 自定义实现开始前先确定文件职责和目标目录；不能明确归属时继续阅读项目目录文档和相邻代码，不得以项目根目录作为默认位置。

“搜索不到自己猜测的方法名”不等于框架没有能力。应按需求关键词、助手函数、组件类名和文档目录继续查找。

修改框架文档时，`official/doc/` 与 `.agents/doc/` 下的同路径 Markdown 必须在同一次任务中同步更新；`.agents/doc/` 是后续 AI 成员的本地事实来源，不能滞后。

## 框架原生基线

| 需求 | 默认使用 | 不应作为首选 |
| --- | --- | --- |
| 读取请求 | `$req->get()`、`post()`、`header()`、`cookie()`、`file()`、`rawBody()` | 直接读取 `$_GET`、`$_POST`、`$_SERVER` |
| JSON 响应 | `return $req->json($data)` | `json_encode()` + 手工 Header/echo |
| 普通响应 | `$req->response($body, $status, $headers)` | 直接 `header()`、`http_response_code()`、`echo` |
| 视图 | `$req->V($template, $vars)` | 控制器内手工 `include` 模板 |
| 所有外部输入验证 | `validator()` / `VD()` 的 `input()`、`check()`；AutoForm 字段使用 `data` 规则 | 手工正则、`filter_var()`、散落类型判断代替验证器 |
| 标准 CRUD、列表、分页 | `$req->AF($vars)` / `autoForm()` | 手写重复增删改查或直接下沉数据库连接 |
| AutoForm 不适合的普通数据库操作 | `$req->SDB()` / `SDB($req)` | PDO、mysqli、自建查询构造器 |
| SDB 无法表达的复杂 SQL | `DB()` / `database()`，并说明为什么 SDB 不适用 | 把 DB 当作普通查询默认入口 |
| 缓存 | `cache()` | 自制文件缓存或进程内数组冒充共享缓存 |
| Redis | `redis()` / `RD()` | 每次请求 `new Redis()` 并重新连接 |
| Session | `$req->session()` / `$req->S()` | `session_start()`、直接操作 `$_SESSION` |
| Cookie | `$req->cookie()`、`$req->SC()`、响应 `cookie()` | 直接 `setcookie()` |
| Token 鉴权 | `$req->token()` / `$req->T()` | 自行拼装 JWT 流程 |
| 队列 | `queue()->send()` + `support/queue` 消费者 | 请求里同步执行耗时工作或自造队列协议 |
| 分页 | SDB `paginate()` / `RC\Helper\Paginator` | 每个接口重新实现页码和元数据 |
| 数据抓取、外部 API、Webhook 回调外发、文件下载 | `curl()`；并发使用 `curl(true)` | `curl_init()`、Guzzle、自写 Socket、URL `file_get_contents()`、shell `curl/wget` |
| 下载/文件响应 | `$req->D()` 或 `$req->response()->file()/download()` | `readfile()` + 手工响应头 |
| 环境值 | `rcEnv($name, $default)` 和现有配置文件 | 业务代码硬编码端口、凭据或路径 |

## 强制执行流程

### 1. 确定边界

明确目标应用、控制器/进程、入口路由、输入、输出、持久化、权限、运行引擎和部署方式。检查：

- `composer.json`、`.env.example` 和相关 `config/*.php`。
- 目标 `apps/<app>/`、`support/` 及相邻实现。
- 对应官方文档和框架源码。
- 现有测试、响应结构、数据库引擎和命名习惯。

### 2. 输出能力选择

编码前在内部形成一份能力选择：

```text
请求=$req API
响应=$req->json()
验证=validator()->input()
数据=AutoForm -> SDB() -> 复杂 SQL 才 DB() 的三级选择
缓存/会话/鉴权/队列=对应 rcmaker 组件
```

若某项不用框架能力，先核实框架确实不支持，不能直接省略这一步。

### 多应用、多进程和静态应用决策

- 普通 HTTP 应用默认属于主 APP 进程组；不设置 `bind_process`。
- 需要独立端口、进程数、重启或资源配额时，在 `config/process.php` 创建 `type => 'app'` 进程组，并在 `config/app.php` 用 `bind_process` 绑定。
- 多个应用可以绑定同一个 APP 进程组；绑定组未启用或未启动时，这些应用不生效，不得自动回退主 APP。
- 不得让主 APP 和自定义 APP 依靠同端口 `reusePort` 按 Host 分流；使用不同内部端口和反向代理。
- 队列消费者、定时任务、采集守护进程、TCP/WebSocket/Text 服务使用普通自定义进程，不伪装成 `type=app`。
- AI 新建静态应用时，默认显式设置 `enable_static_file => true` 和 `enable_static_preload => true`；纯静态站同时设置 `static_only => true`。只有资源很大、频繁变化或内存预算不允许时才关闭预加载，并说明原因。
- 静态应用可用 `bind_process` 独立到专用 APP 进程组。Linux 全局预热后 fork 并通过写时复制继承；Windows 按应用所属进程组分别预热，不是假设存在跨进程共享缓存。

### Windows 开发与 Linux 生产兼容

- Windows 不支持或未启用多进程时，只能证明单进程开发流程可用，不能证明跨 Worker 状态、锁、事务、缓存一致性和并发行为正确。
- 不得把可变业务状态保存在静态属性、全局数组、单例字段或本地临时文件中。需要跨请求、跨 Worker 或跨机器共享的状态，必须通过 `cache()`、Session、Redis 或数据库等框架能力保存。
- SQLite、file 缓存只可作为可配置的开发驱动。通过 `.env` 和 `config/*.php` 选择驱动与连接，业务代码不得出现固定 DSN、Redis 地址、缓存目录或数据库厂商判断。
- 优先使用 AutoForm、SDB 和 `cache()` 等抽象，避免依赖 SQLite/MySQL/PostgreSQL 特有的引号、函数、字段类型、布尔值、日期、UPSERT 或自增语义。确需厂商专用复杂 SQL 时，将差异隔离并明确测试目标数据库。
- 文件路径使用项目路径助手和跨平台路径处理；业务逻辑不得写死盘符、反斜杠、`/tmp`、用户目录或 Linux 命令。
- 上线前必须在 Linux 的实际进程数、数据库和 Redis/cache 驱动下验证启动、并发、事务、状态共享和重启；Windows + SQLite + file cache 的测试不能替代这一步。

### 3. 实现最小完整链路

按“配置 -> 路由/控制器 -> 验证 -> 服务/数据 -> 框架响应 -> 测试”完成垂直功能。控制器负责传输边界，复杂业务放入项目现有服务层；不要为简单功能发明新架构。

文件放置遵守以下边界：

- 应用控制器、模型和应用内代码：`apps/<app>/` 的现有分层。
- 跨应用服务、中间件、进程、队列消费者、定时任务和启动类：`support/` 下对应现有目录。
- 应用、路由、中间件、进程和组件配置：`config/`。
- 模板与公开资源：`view/`、`public/`；运行生成物只进入 `runtime/`。
- 测试、夹具和诊断程序：`tests/`；短期工作文件使用项目已有 `.tmp/` 并在任务结束时清理。
- 维护或交付脚本：仅在确有必要时放入 `scripts/` 或项目已有 `tools/`，不得放在根目录。
- 根目录仅保留框架既有入口、项目级清单和用户明确要求的仓库级入口。新增根目录文件必须说明为什么现有职责目录都不适用。

开发人员明确要求非标准目录或根目录文件时，以本次明确授权为准，并在交付说明中记录文件用途。目录特许只作用于指定文件或任务，不代表可以绕过框架能力复用、验证、数据库、运行安全或其他规则；这些规则需要分别得到明确授权才可调整。

### 4. 保持代码可读、可懂、可审查

- 每个新增或修改的类都要有类级 PHPDoc，说明它负责什么、在 rcmaker 哪一层使用，以及典型创建或调用方式。
- 每个新增或修改的方法都要有方法级 PHPDoc。公开方法说明用途、调用时机、参数、返回值、可能抛出的异常和使用方式；受保护或私有方法说明内部职责及由哪个流程调用。
- 使用 `@param`、`@return`、`@throws` 等准确标注非显而易见的契约；类型已由 PHP 签名完整表达时仍需用自然语言说明业务含义，不能只重复类型。
- 简单公开方法可以在类级示例中统一展示，复杂方法应在自身 PHPDoc、对应文档或测试中给出最小调用示例。示例必须使用 rcmaker 正确接口，不能展示被禁止的原生替代方案。
- 变量、类和方法名要表达业务含义。复杂条件、状态转换、并发边界或兼容处理前写简短原因注释；不要写“给变量赋值”一类无信息注释。
- 一条语句正常占一行，数组、条件、链式调用和参数列表按项目风格展开。禁止压缩 PHP、删除必要空白、连续堆叠三元表达式、滥用单字母变量或把完整业务流程塞进巨大闭包。
- 方法保持单一职责。出现多阶段处理时拆成有名字的方法，让审查者可以沿“入口 -> 验证 -> 业务 -> 数据 -> 响应”理解流程；不要为了减少文件或行数牺牲结构。
- 注释必须与代码同步更新。过期、误导或复制粘贴的注释属于缺陷；不能用大段注释掩盖命名和结构问题。

### 5. 检查常驻内存安全

- 不在静态属性、全局变量、单例或长期回调里保存 Request、Response、连接、Session、用户或上传文件。
- Swoole 每个请求使用新的 rcmaker Request/Response 包装对象，禁止按 `$fd` 复用。
- Workerman 的复用必须依赖框架 `set()` / `unset()` 完整清理请求状态。
- Swoole 协程模式要确认数据库、Redis、HTTP 客户端和第三方 SDK 的协程安全性。

### 6. 验证框架使用情况

先运行 Skill 自带审计脚本检查业务目录，再检查本次改动：

```shell
php .agents/skills/rcmaker-development/scripts/audit-framework-usage.php apps support
```

审计命中必须逐项处理；测试目录会自动跳过。必要时再搜索通用 PHP 回退写法：

```powershell
rg -n "json_encode\s*\(|new\s+\\?PDO|mysqli_|session_start\s*\(|setcookie\s*\(|curl_init\s*\(|header\s*\(" apps support
```

命中不一定错误，例如签名、日志或队列内部序列化可以使用 `json_encode()`；但每个生产代码命中都要给出用途，确认不是在重新实现 rcmaker 已提供的请求、响应或组件能力。数据抓取中的原生网络调用不属于可接受回退，必须改用 `curl()`，除非已经证明框架客户端不支持目标协议。

### 7. 测试和交付

- 对修改的 PHP 文件运行 `php -l`。
- 运行最接近改动的测试；生命周期变化要增加 keep-alive、跨请求隔离或进程归属测试。
- 配置、bootstrap、路由或进程变化后重启对应进程组。
- 打包变化需在目标架构做独立二进制冒烟测试。

## 明确禁止

- 不得用 `json_encode()`、手工 Content-Type 和 `echo` 代替 `$req->json()` 返回 HTTP JSON。
- 不得在 rcmaker 业务代码中自行创建 PDO、mysqli 或 Redis 长连接。
- 不得跳过数据库三级选择：能用 AutoForm 不直接写 SDB，能用 SDB 不直接写 DB；使用 DB 时必须说明复杂 SQL 需求。
- 不得用手工正则、`filter_var()` 或散落类型判断替代框架验证器处理外部输入。
- 不得直接使用 PHP 原生 Session、Cookie、上传和响应 API 绕过 Request/Response 适配层。
- 不得在已有 Validator、AutoForm、Paginator、Cache、Token、Queue、Curl 等组件满足需求时自行复制一套。
- 不得在数据采集、网页抓取、第三方 API、文件下载或回调通知中使用原生 cURL、Guzzle、URL 流或 shell 网络命令；统一使用 `curl()` / `curl(true)`。
- 不得为了一个功能随意安装与框架已有能力重复的 Composer 包。
- 不得假设其他框架的 `config()`、`request()`、`response()`、ORM 或容器 API 在 rcmaker 中存在。
- 不得以任何理由修改 `vendor/` 下的文件；框架本身的任务、兼容修复、临时调试和紧急排障均不例外。只允许读取和检索。
- 未经开发人员明确特许，不得在项目根目录随意创建业务 PHP、服务、模型、任务、测试、演示、下载文件或临时输出；必须移动到职责目录，或在任务结束前删除。
- 不得提交压缩、混淆、无正常换行、命名含糊或需要反向推理才能看懂的业务代码；交付二进制或加密产物不影响源代码必须可读的要求。
- 不得省略新增或修改类、方法的职责和使用说明，也不得用机械注释代替真实的业务契约。

## 完成标准

- 使用了当前项目和 rcmaker 的原生接口，而不是通用 PHP 替代实现。
- 原生 PHP 仅用于正常语言逻辑和框架未覆盖的领域实现，没有重复实现框架组件。
- 已列出本次能力映射，并通过框架复用审计；所有生产代码命中均已消除或具有“框架当前不支持”的源码证据。
- 每个复杂组件的调用签名均已从当前文档或源码核实。
- 输入经过验证，输出使用框架响应，数据层与项目配置一致。
- 无跨请求、跨协程、跨应用或跨进程状态污染。
- Windows 开发配置与 Linux 生产配置可以仅通过环境和配置切换；业务代码没有写死数据库、缓存、路径或单进程假设。
- 所有新增文件均位于正确职责目录；没有无理由新增根目录文件，也没有遗留临时、演示或诊断文件。
- 新增或修改的每个类、方法都有准确说明和使用方式；代码格式、命名和分层可以让开发人员直接阅读并完成审查。
- 没有引入重复组件、无界缓存、无界查询、同步耗时任务或不必要的热路径开销。
- 测试通过；无法运行的检查、需要的重启和部署条件已明确说明。
