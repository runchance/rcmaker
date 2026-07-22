---
name: rcmaker-development
description: Develop, extend, debug, optimize, test, and package rcmaker PHP applications with framework-native APIs. Use for rcmaker controllers, APIs, CRUD, routes, middleware, request/response handling, databases, models, SDB, AutoForm, validation, cache, Redis, sessions, tokens, queues, built-in components, multi-application configuration, APP process groups, Workerman or Swoole runtimes, static preload, performance, source protection, buildBin, x86_64, or AArch64 delivery.
---

# rcmaker 快速开发

使用 rcmaker 已有能力完成需求。目标不是写出“能运行的通用 PHP”，而是写出符合当前 rcmaker 项目约定、可在常驻内存和多进程环境中稳定运行的代码。

## 不可跳过的原则

1. **先查框架，再写代码。** 实现请求、响应、数据库、验证、CRUD、缓存、会话、鉴权、队列、分页、HTTP 客户端、文件或文档功能前，必须先确认 rcmaker 是否已有能力。
2. **框架能力优先。** rcmaker 已提供合适能力时必须使用；不能因为更熟悉原生 PHP、PDO 或其他框架而重复实现。
3. **源码是运行事实。** 先看当前项目用法和 `official/doc/md/`，接口仍不明确时查看 `vendor/runchance/rcmaker-framework/src/`。不要猜方法名或参数。
4. **第三方包是补充。** 只有框架没有对应能力、现有能力明显不适合需求，或用户明确指定时，才引入 Composer 包。先检查 `composer.json` 和现有依赖。
5. **不得静默绕过。** 若决定不使用已有能力，实施前必须说明不适用原因、替代方案及常驻进程风险。

## 每次实现前必须读取

- 所有编码任务：先读 [framework-capabilities.md](references/framework-capabilities.md)。
- HTTP、控制器、路由、中间件、请求或响应：再读 [project-workflow.md](references/project-workflow.md)。
- 数据库、模型、CRUD、列表或分页：再读 [data-access.md](references/data-access.md)。
- 验证、缓存、Redis、Session、Token、队列或其他组件：再读 [components.md](references/components.md)。
- 多应用、APP 进程组、Workerman、Swoole、静态应用：读 [runtime-and-processes.md](references/runtime-and-processes.md)。
- 修改框架源码、请求生命周期、配置继承、Worker 回收或静态预热机制：读 [framework-internals.md](references/framework-internals.md)。
- 性能、并发、安全或代码审查：读 [quality-and-performance.md](references/quality-and-performance.md)。
- 启动失败、404、端口、Windows、依赖兼容、状态污染或压测异常：读 [diagnostics.md](references/diagnostics.md)。
- 打包、加密、独立运行或不同 CPU 架构：读 [delivery.md](references/delivery.md)。
- 不确定能力或文档位置：读 [documentation-map.md](references/documentation-map.md)，再打开对应原文。

不要只凭本文件中的速查表直接猜复杂组件用法。涉及组件时打开对应官方文档并核对当前源码。

## 框架原生基线

| 需求 | 默认使用 | 不应作为首选 |
| --- | --- | --- |
| 读取请求 | `$req->get()`、`post()`、`header()`、`cookie()`、`file()`、`rawBody()` | 直接读取 `$_GET`、`$_POST`、`$_SERVER` |
| JSON 响应 | `return $req->json($data)` | `json_encode()` + 手工 Header/echo |
| 普通响应 | `$req->response($body, $status, $headers)` | 直接 `header()`、`http_response_code()`、`echo` |
| 视图 | `$req->V($template, $vars)` | 控制器内手工 `include` 模板 |
| 输入验证 | `validator()` / `VD()` 后调用 `input()` 或 `check()` | 控制器里散落正则和类型判断 |
| 默认数据库 | `DB()` / `database()` | `new PDO()`、`mysqli_connect()` |
| 统一链式查询 | `$req->SDB()` / `SDB($req)` | 自建查询构造器 |
| ORM 模型 | `RC\Model\Think` / `RC\Model\Laravel`，使用前初始化相应 `DB()` | 自建 Active Record 基类 |
| 常规 CRUD | `$req->AF($vars)` / `autoForm()` | 重复编写通用增删改查流程 |
| 缓存 | `cache()` | 自制文件缓存或进程内数组冒充共享缓存 |
| Redis | `redis()` / `RD()` | 每次请求 `new Redis()` 并重新连接 |
| Session | `$req->session()` / `$req->S()` | `session_start()`、直接操作 `$_SESSION` |
| Cookie | `$req->cookie()`、`$req->SC()`、响应 `cookie()` | 直接 `setcookie()` |
| Token 鉴权 | `$req->token()` / `$req->T()` | 自行拼装 JWT 流程 |
| 队列 | `queue()->send()` + `support/queue` 消费者 | 请求里同步执行耗时工作或自造队列协议 |
| 分页 | SDB `paginate()` / `RC\Helper\Paginator` | 每个接口重新实现页码和元数据 |
| 外部 HTTP | `curl()` | 零散 `curl_init()` 或无超时的网络调用 |
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
数据=DB()/SDB()/Model/AutoForm 中与项目一致的一种
缓存/会话/鉴权/队列=对应 rcmaker 组件
```

若某项不用框架能力，先核实框架确实不支持，不能直接省略这一步。

### 3. 实现最小完整链路

按“配置 -> 路由/控制器 -> 验证 -> 服务/数据 -> 框架响应 -> 测试”完成垂直功能。控制器负责传输边界，复杂业务放入项目现有服务层；不要为简单功能发明新架构。

### 4. 检查常驻内存安全

- 不在静态属性、全局变量、单例或长期回调里保存 Request、Response、连接、Session、用户或上传文件。
- Swoole 每个请求使用新的 rcmaker Request/Response 包装对象，禁止按 `$fd` 复用。
- Workerman 的复用必须依赖框架 `set()` / `unset()` 完整清理请求状态。
- Swoole 协程模式要确认数据库、Redis、HTTP 客户端和第三方 SDK 的协程安全性。

### 5. 验证框架使用情况

检查新增代码中的通用 PHP 回退写法：

```powershell
rg -n "json_encode\s*\(|new\s+\\?PDO|mysqli_|session_start\s*\(|setcookie\s*\(|curl_init\s*\(|header\s*\(" apps support
```

命中不一定错误，例如签名、日志或队列内部序列化可以使用 `json_encode()`；但每个命中都要确认不是在重新实现 rcmaker 已提供的请求、响应或组件能力。

### 6. 测试和交付

- 对修改的 PHP 文件运行 `php -l`。
- 运行最接近改动的测试；生命周期变化要增加 keep-alive、跨请求隔离或进程归属测试。
- 配置、bootstrap、路由或进程变化后重启对应进程组。
- 打包变化需在目标架构做独立二进制冒烟测试。

## 明确禁止

- 不得用 `json_encode()`、手工 Content-Type 和 `echo` 代替 `$req->json()` 返回 HTTP JSON。
- 不得在 rcmaker 业务代码中自行创建 PDO、mysqli、Redis 长连接；优先复用 `DB()`、`SDB()`、Model、`redis()`。
- 不得直接使用 PHP 原生 Session、Cookie、上传和响应 API 绕过 Request/Response 适配层。
- 不得在已有 Validator、AutoForm、Paginator、Cache、Token、Queue、Curl 等组件满足需求时自行复制一套。
- 不得为了一个功能随意安装与框架已有能力重复的 Composer 包。
- 不得假设其他框架的 `config()`、`request()`、`response()`、ORM 或容器 API 在 rcmaker 中存在。
- 不得只修改 `vendor` 来完成项目业务需求；框架本身的任务除外。

## 完成标准

- 使用了当前项目和 rcmaker 的原生接口，而不是通用 PHP 替代实现。
- 每个复杂组件的调用签名均已从当前文档或源码核实。
- 输入经过验证，输出使用框架响应，数据层与项目配置一致。
- 无跨请求、跨协程、跨应用或跨进程状态污染。
- 没有引入重复组件、无界缓存、无界查询、同步耗时任务或不必要的热路径开销。
- 测试通过；无法运行的检查、需要的重启和部署条件已明确说明。
