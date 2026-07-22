# 框架内部机制

修改 `vendor/runchance/rcmaker-framework`、排查生命周期问题或做性能优化时必须阅读本文件，并以当前源码为最终依据。不要把这里的实现细节当成永远不变的公共 API。

## 请求执行链

CLI APP 的主链路可以概括为：

```text
读取运行引擎配置
-> 创建主 APP/自定义 APP Worker
-> 合并进程组配置
-> Worker 启动时加载 autoload/bootstrap/middleware
-> 收到请求并创建请求作用域
-> Controller 选择当前进程组允许的应用
-> 静态文件或路由/中间件/控制器
-> Response 适配到底层运行时
-> finally 清理 Request/Response
-> 按 max_request 决定平滑回收
```

关键源码：

- `src/Worker.php`：运行引擎、进程、请求作用域、回收和日志进程。
- `src/Controller.php`：应用归属、路由、静态文件、预热、异常入口。
- `src/Request.php`：跨引擎请求适配、属性容器、助手映射和清理。
- `src/Response.php` 与 `src/Http/Workerman/Response.php`：响应发送和响应对象。
- `src/Middleware.php`、`src/Route.php`、`src/Config.php`：中间件、路由和配置。

以上源码路径均相对于 `vendor/runchance/rcmaker-framework/src/`。

## APP 进程配置继承

`type=app` 进程组不是普通 `handler` 进程。框架会：

1. Workerman 下以 `config/worker.php` 为基础；Swoole 下以 `config/swoole.php` 为基础。
2. 移除进程配置中的 `type`、`handler` 后，用进程配置覆盖主运行配置。
3. 在 Worker 启动时重新加载应用配置。
4. 设置当前 APP 进程组名称和可选 `default_app`。
5. 合并全局与进程级 `autoload`，去重后 `include_once`。
6. 合并全局与进程级 `bootstrap`，去重后调用各启动类 `start($worker)`。
7. 应用进程级 `memory_limit`、`default_timezone`，重新加载中间件，并设置 `max_request`。

因此：

- 进程组只需配置差异项，未写项继承主运行配置。
- `bootstrap` 和 `autoload` 是追加关系，不是完全替换。
- 业务依赖应在 Worker 启动阶段初始化，不在每请求重复创建。
- 配置在进程内通常是启动期状态，修改后重启对应 Worker。

## 应用归属和默认应用

框架用当前进程组名称过滤 `config/app.php`：

- 主 APP 的进程组标识为 `null`，只允许未设置 `bind_process` 的应用。
- 自定义 APP 只允许 `bind_process` 严格等于当前进程组名的应用。
- 不属于当前组的应用及域名会被记录为外部归属，但不会由当前组执行。
- `default_app` 若显式配置，必须属于当前进程组，否则启动阶段抛出异常。
- 未显式指定且全局默认应用不属于当前组时，可用应用集合中的第一个应用作为该组默认应用。

这解释了为什么绑定应用从主端口访问会 404，也解释了为什么目标进程停止时不会自动回退到主 APP。

## Request/Response 作用域

### Swoole

每次请求都创建新的 `RC\Request` 和框架 Response 包装对象，即使 `$fd` 与上一次相同也不能复用。原因：

- Keep-Alive 会让多个请求使用同一连接描述符。
- 协程可能让同一进程内同时存在多个活动请求。
- `$fd` 表示连接，不表示唯一请求。

不得恢复为按 `$fd` 缓存 Request/Response 的设计。

### Workerman

框架可按连接 ID 复用包装对象以降低分配，但每次请求开始执行 `set()`，请求结束的 `finally` 执行 `unset()`：

- 清理底层 Request。
- 清理 Response 引用。
- 清理 Request 自定义属性。
- 连接关闭后移除对应缓存对象。

修改 Request 新增任何请求级缓存时，必须同时加入 `set()`/`unset()` 清理并增加 keep-alive 污染测试。

### `finally` 是安全边界

控制器或异常处理无论成功、抛错还是提前返回，作用域清理都必须发生在 `finally`。不得把清理放到只有成功响应才执行的分支。

## `max_request` 回收

`max_request` 控制单 Worker 处理一定请求量后的回收，用于限制长期内存增长：

- 主 APP 从 `config/worker.php` 或 `config/swoole.php` 读取。
- APP 进程组可以通过同名选项独立覆盖。
- 是否读取 `.env` 取决于对应配置文件是否使用 `rcEnv()`；框架不会绕过配置文件自动猜环境变量。
- Workerman 达到阈值后走平滑退出逻辑。
- Swoole 协程模式先标记待重启，等待活动请求数归零后再 shutdown，避免主动中断正在处理的请求。

修改回收逻辑时至少测试：边界计数、并发活动请求、异常请求、阈值后的新请求以及不同 APP 进程组的独立配置。

## 静态文件与内存预热

静态预热不是跨进程共享缓存服务。它把符合扩展名的文件读取为进程内数组项：

```text
document root + absolute file path
-> body
-> MIME
-> mtime
-> 可选 gzip body
```

请求命中时可直接从内存返回，支持 Last-Modified；客户端接受 gzip 且不是 Range 请求时可返回预压缩内容。

预热行为：

- 每个真实 `document_root` 在一次预热中去重。
- 只加载 `static_preload_extensions` 允许的扩展名。
- 达到 `static_preload_time_limit` 后停止继续扫描，已经加载的条目仍可使用。
- 文件正文、MIME、mtime 和 gzip 都占用内存；评估的是每个主进程/Worker 的实际驻留成本。
- 预热后的文件内容不会因磁盘文件变化自动刷新，静态资源更新后应按运行方式重启或触发可靠的缓存重建。

平台差异：

- Linux 在 fork 前全局预热，子 Worker 通过 Copy-on-Write 继承内存页。之后某个进程修改对应内存页才会产生私有副本。
- Windows 没有 fork，共享不了该 PHP 数组；主 APP 只预热未绑定应用，自定义 APP 只预热绑定到自身的应用。
- Windows 启动多个应用进程时，出现多个进程自己的预热日志是合理的；同一进程组不应无原因重复预热同一根目录。

`static_only=true` 表示静态文件未命中后直接 404，不进入动态路由、控制器或 PHP 静态执行。

## 日志开关和热路径

CLI 访问日志开启时框架会自动合并内部 `RCmaker_logger` 进程，队列开启时也会合并消费者进程。不要在 `config/process.php` 重复声明内部日志进程。

性能修改原则：

- 稳定配置和日志开关在 Worker 启动时读取/缓存。
- 不在每请求重新扫描目录、解析 `.env`、构建路由表或加载类文件。
- 只缓存不可变元数据；用户态数据进入请求上下文或共享后端。
- 直接属性访问可用于明确的底层热路径，但不得绕过生命周期清理。
- 所有微优化用相同负载、相同响应和多轮数据验证，不能凭单次 RPS 判断。

## 异常边界

请求链抛出的 `Throwable` 进入统一异常响应；异常处理器根据当前应用、debug 和 error message 配置渲染。框架源码修改必须保证：

- 静态文件响应状态不会污染异常响应。
- debug 关闭时不暴露内部消息。
- 异常处理本身失败时仍能返回可发送的 500。
- Request/Response 清理不依赖异常处理成功。

## 修改框架源码的要求

1. 先判断问题属于项目配置、框架、Workerman/Swoole 还是第三方组件。
2. 修改公共生命周期前搜索 Workerman、Swoole、FPM 和 Windows 四条路径。
3. 保持公共 API 兼容，避免只修当前引擎。
4. 增加针对请求隔离、APP 归属、静态预热或回收逻辑的测试。
5. 运行 `tests/WorkerAppProcessTest.php` 及受影响的真实引擎冒烟测试。
6. 不用压测结果代替正确性测试。
