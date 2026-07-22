# 故障诊断

先定位层级，再修改代码。rcmaker 问题可能来自项目配置、框架、运行引擎、Composer 依赖、操作系统、反向代理或打包环境。

## 基础信息

排障前收集：

```text
rcmaker-framework 版本
PHP 版本与 CPU 架构
Workerman/Swoole 版本
CLI、FPM 还是混合模式
主 APP 还是哪个 type=app 进程组
监听 IP:端口、Host 和反向代理路径
相关 config、.env 和启动命令
完整错误、请求响应、socket error 和进程状态
```

不要只看“访问失败”，必须确认请求实际进入了哪个端口和进程组。

## 启动失败

### 运行引擎不可用

- Swoole 模式确认扩展已加载、版本兼容及 CLI 配置完整。
- Workerman 模式确认间接依赖已经正确安装，CLI 所需函数和扩展可用。
- FPM 不会启动 `config/process.php`；不要在 FPM 下等待 APP 进程组、队列消费者或定时任务出现。
- Swoole 非协程模式不能运行独立 APP 进程组，框架应明确拒绝启动。

### 监听失败或 Connection refused

检查顺序：

1. 目标进程是否出现在启动列表且状态正常。
2. `config/process.php` 是否启用、进程名是否正确。
3. `listen` 的协议、地址和端口是否合法。
4. 端口是否被占用、防火墙是否放行、容器端口是否映射。
5. 新增/删除进程组后是否完整 stop/start；普通 reload 不负责改变进程拓扑。

### Windows 子进程异常退出

Windows 启动器会把主 APP 和各自定义进程作为独立子进程运行。某个 `RCmaker_logger` 或 APP 子进程失败，不等于其他进程一定失败；应按进程名读取它的完整异常。

多个子进程启动时，预热日志可能插入 Worker 状态表输出，看起来像表格被打断。这通常是并发标准输出的展示问题；先看对应 Worker 最终状态和端口是否可用，再判断是否启动失败。要改善展示应在启动器层汇总/延迟子进程日志，不要删除预热功能。

若出现类似：

```text
Call to undefined function Workerman\ouch()
```

先打开报错的 Workerman 文件确认该行。若源码字面上是 `ouch()` 而预期应为 PHP 的 `touch()`，通常属于 vendor 文件损坏、误编辑或错误发布，不是 rcmaker 应补一个 `ouch()` 函数。对照 `composer.lock`、已发布包和干净安装恢复依赖，再验证。

PHP 新版本出现第三方库弃用提示，例如旧 Workerman `case ...;` 语法，应判断为 PHP/依赖兼容问题。优先升级到兼容版本或在依赖上游修复，不通过关闭全部错误报告掩盖真实问题。直接改 vendor 只能作为受控补丁，并要能在安装时重复应用。

## 应用返回 404

按此矩阵检查：

| 现象 | 常见原因 |
| --- | --- |
| 主 APP 端口访问绑定应用 404 | 正常隔离；应用只属于 `bind_process` 指定组 |
| 自定义 APP 端口访问 404 | 进程名与 `bind_process` 不一致，或 Host/路径未匹配 |
| 绑定进程未启动 | 应用不生效，不回退到主 APP |
| 域名 404、IP 正常 | `domains` 或代理转发 Host 不一致 |
| 根路径静态站 404 | `document_root`、`index_default` 或文件不存在 |
| 静态资源存在仍 404 | 请求没进入拥有该应用的进程组，或真实路径不在允许根目录 |
| `static_only=true` 下动态路由 404 | 设计如此；静态未命中后不进入控制器 |
| `default_app` 启动报错 | 默认应用没有绑定到当前 APP 进程组 |

同时从目标机器执行携带 Host 的请求，排除 DNS 和代理干扰：

```bash
curl -i -H 'Host: api.example.com' http://127.0.0.1:8081/path
```

## 相同端口和 `reusePort`

不同 APP 进程组不能靠相同 IP:端口的 `reusePort` 按域名分流。内核在读取 HTTP Host 前就把连接交给某个 socket；Keep-Alive 后续请求继续停留在该连接。

如果请求看起来“总进入主 APP”，不要试图调整应用匹配概率。使用不同内部端口，再由 Nginx/Apache/负载均衡器按域名分发。`reusePort` 只适合同一能力、同一应用所有权的一组 Worker。

## 静态预热异常

### 多次出现预热日志

- Linux 预期是 fork 前全局预热一次，然后由子 Worker 继承。
- Windows 无 fork，每个拥有静态应用的进程组需要自己的预热，因此不同进程组分别打印日志是正常的。
- 同一真实根目录在一次预热流程中应去重。
- `reason=time_limit` 表示达到扫描时限，不表示已加载条目失效。

### 文件更新后仍返回旧内容

预热缓存保存了文件正文和 gzip 内容，不是每次请求检查并重读磁盘。更新预热文件后重启相应进程，或实现经过测试的显式缓存重建机制。

### 内存增长

检查预热扩展、根目录、文件数量、压缩副本和 Worker/进程组数量。Windows 每个进程组持有独立副本；Linux Copy-on-Write 只有未修改页面能有效共享。

## 请求状态污染

典型症状：后一个请求读到前一个请求的 Header、参数、用户、属性或响应状态。

检查：

1. 是否在静态属性、单例、中间件/控制器属性保存了请求值。
2. Swoole 是否错误地按 `$fd` 复用 Request/Response。
3. Workerman 新增的请求缓存是否在 `set()`/`unset()` 清理。
4. 异常路径和提前返回是否仍进入 `finally`。
5. 延迟回调、Timer、Queue 是否闭包捕获 Request。

测试时在同一 Keep-Alive 连接连续发送身份、Cookie、Header、参数完全不同的请求，并加入并发请求验证。

## 数据库或组件不可用

### `DB()` / `SDB()` 报支持类未加载

- `db.default_frame` 必须与 `.env [bootstrap]` 或进程 `bootstrap` 中载入的类一致。
- Model 使用前需要执行 `DB('think')` 或 `DB('laravel')` 初始化对应 ORM。
- 普通自定义进程要显式配置它需要的 bootstrap。
- Laravel/MongoDB 等可选能力要确认 Composer 依赖存在。

### Redis/Cache/Session 在不同 Worker 不一致

- PHP 静态数组只在当前进程有效。
- 确认各 APP 进程使用相同后端、连接配置、前缀、Cookie 和 Session 配置。
- key 必须包含业务隔离维度，TTL 和失效逻辑一致。

## `.env` 没有生效

框架只会读取配置文件明确调用的 `rcEnv()` 键。例如 `max_request` 是否可由 `.env` 覆盖，要检查 `config/worker.php` / `config/swoole.php` 对该项的写法，而不是假设任意同名变量都会自动映射。

检查环境变量层级名称、类型转换、缓存/重启，以及运行的是源码目录还是打包产物旁的 `.env`。

## 压测结果异常

### `wrk` 出现大量 socket read errors

先不要使用该轮 RPS 评价框架。read error 常见于服务端主动关闭连接、Keep-Alive 不一致、请求回收、协议响应或系统资源限制。确认：

- `Socket errors` 是否为 0。
- 显式 `Connection: keep-alive` 后错误是否消失。
- 完成请求数与正确响应数是否一致。
- 服务端是否因 `max_request`、异常或资源限制重启。

### 公平对比

rcmaker 与其他框架对比时固定：PHP 版本、运行引擎、事件循环、进程数、线程/连接数、Keep-Alive、路由、中间件、响应体和日志开关。预热后交替运行多轮，报告中位数、P99、max、CPU、内存及错误数。

本机空响应达到很高 RPS 只能说明该路径的框架开销低。还应测试 JSON、路由、中间件、静态文件、数据库和真实业务链路。

## 二进制或架构错误

- `php85` / `php85_aarch64` 是独立 PHP 可执行文件。
- `php85.micro.sfx` / `php85.micro.aarch64.sfx` 是构建单文件程序的 micro SFX。
- `rcmakerbeast` / `rcmakerbeast_aarch64` 是代码保护工具。
- x86_64 和 AArch64 不可仅通过改文件名互换。

出现 Exec format error、扩展缺失或打包后路径异常时，检查目标架构、libc、执行权限、依赖扩展、`is_phar()` 和路径助手，不要先改业务逻辑。

## 排障完成标准

- 已明确问题所属层级和最小复现。
- 修复没有绕过 rcmaker 原生能力或破坏另一运行引擎。
- 配置类问题有启动前校验或文档提示。
- 生命周期问题有 keep-alive/并发回归测试。
- 依赖问题通过可重复的 Composer/补丁流程解决，不依赖手工修改线上 vendor。
- 性能结论包含错误数和可复现命令。
