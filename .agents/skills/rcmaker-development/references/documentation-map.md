# AI 本地文档导航

当前项目已经把完整 V3 文档放在 `./.agents/doc/`，供 AI 开发成员直接读取。遇到不熟悉的能力时，从本页选择 `.agents/doc/md/` 下的原文，再与当前源码核对；不要先访问官网，也不要根据其他版本或其他 PHP 框架推测 API。

`official/doc/` 是官网发布源，`.agents/doc/` 是 AI 本地文档镜像。修改任一框架 Markdown 时，必须同步更新另一目录的同路径文件，并检查内容一致。

## 入门与总体结构

| 文档 | 何时读取 |
| --- | --- |
| `.agents/doc/md/install.md` | Linux、macOS、Windows 安装与启动 |
| `.agents/doc/md/download.md` | 独立 PHP、Micro SFX、源码保护工具的版本与平台矩阵 |
| `.agents/doc/md/directory.md` | 目录职责和项目结构 |
| `.agents/doc/md/demo.md` | 最小控制器、JSON/XML/JSONP/视图示例 |
| `.agents/doc/md/Helper.md` | 全局助手、简写函数和 Request 映射 |

## 应用与 HTTP

| 文档 | 何时读取 |
| --- | --- |
| `.agents/doc/md/apps.md` | 多应用、域名、路径、Query 模式、自定义目录 |
| `.agents/doc/md/controller.md` | 控制器、返回值、路由参数、Hook 和常驻注意事项 |
| `.agents/doc/md/route.md` | 自动/自定义路由、分组、中间件、fallback、静态路由 |
| `.agents/doc/md/request.md` | GET/POST/Header/Cookie/文件、IP、原生请求和映射 |
| `.agents/doc/md/response.md` | JSON、Header、Cookie、文件、下载和响应对象 |
| `.agents/doc/md/middleware.md` | 全局/应用/路由/静态中间件、顺序和请求属性 |
| `.agents/doc/md/view.md` | Raw、Smarty、Think、Blade、Twig 和视图复用 |
| `.agents/doc/md/exception.md` | 异常 handler、report/render、debug 和日志 |
| `.agents/doc/md/cookie.md` | Cookie 生命周期和安全属性 |
| `.agents/doc/md/session.md` | Session 后端和操作 API |

## 运行、静态和部署入口

| 文档 | 何时读取 |
| --- | --- |
| `.agents/doc/md/cli.md` | Workerman/Swoole 启动和引擎选择 |
| `.agents/doc/md/banner.md` | 默认及自定义启动 Banner、变量、颜色和进程列表 |
| `.agents/doc/md/fpm.md` | PHP-FPM/PHP-MOD 和反向代理 CLI |
| `.agents/doc/md/mix.md` | Web 与 CLI 混合模式 |
| `.agents/doc/md/ssl.md` | 主服务和自定义进程 SSL |
| `.agents/doc/md/log.md` | CLI 访问日志、内部日志进程和日志位置 |
| `.agents/doc/md/static.md` | Workerman/Swoole 静态文件开关 |
| `.agents/doc/md/static-directory.md` | 应用静态根目录、gzip 和预加载 |

## 数据库

| 文档 | 何时读取 |
| --- | --- |
| `.agents/doc/md/db/config.md` | DB 连接、默认引擎、bootstrap 和自定义进程 |
| `.agents/doc/md/db/frame.md` | Medoo/Think/Laravel 原生支持层与切换 |
| `.agents/doc/md/db/sdb.md` | 统一链式查询、CRUD、分页、事务和底层绑定 |
| `.agents/doc/md/db/model.md` | Think/Laravel Model、关系和初始化 |
| `.agents/doc/md/db/redis.md` | Redis raw/mix、多连接、事务、pipeline、订阅 |
| `.agents/doc/md/db/mongo.md` | MongoDB 依赖、配置和 ORM 示例 |
| `.agents/doc/md/ttt.md` | 空间查询 SQL 示例；属于专项示例，不是通用 DB 规范 |

## 业务组件

| 文档 | 能力 |
| --- | --- |
| `.agents/doc/md/module/validation.md` | Validator 和上传文件验证 |
| `.agents/doc/md/module/autoform.md` | 自动 CRUD、判重、回调、事务和分页 |
| `.agents/doc/md/module/cache.md` | Cache、TTL、remember、标签 |
| `.agents/doc/md/module/paginator.md` | HTML/JSON 分页和 SDB 联动 |
| `.agents/doc/md/module/token.md` | Token、刷新、guard、证书和单设备登录 |
| `.agents/doc/md/module/captcha.md` | 图形验证码、存储和校验 |
| `.agents/doc/md/module/sms.md` | 短信验证码、scene、有效期和服务商 |
| `.agents/doc/md/module/throttler.md` | 缓存令牌桶限流和 key 设计 |
| `.agents/doc/md/module/curl.md` | 单请求、并发、JSON、下载和回调 |
| `.agents/doc/md/module/mailer.md` | SMTP/邮件、HTML、附件和状态复用 |
| `.agents/doc/md/module/excel.md` | Excel 读写 |
| `.agents/doc/md/module/pdf.md` | PDF 初始化和输出 |
| `.agents/doc/md/module/qrcode.md` | 二维码格式、保存和输出 |
| `.agents/doc/md/module/pinyin.md` | 拼音、声调和首字母 |
| `.agents/doc/md/module/stopWatch.md` | 性能计时和诊断 |

## 进程与队列

| 文档 | 何时读取 |
| --- | --- |
| `.agents/doc/md/process.md` | 普通自定义进程、APP 进程和配置参数 |
| `.agents/doc/md/app-process.md` | 应用绑定、独立端口/资源、静态应用和反向代理 |
| `.agents/doc/md/queue.md` | 投递、消费者、重试、多连接和消费进程 |
| `.agents/doc/md/process/http.md` | 普通 HTTP 自定义进程 |
| `.agents/doc/md/process/tcp.md` | TCP 进程 |
| `.agents/doc/md/process/text.md` | Text 协议进程 |
| `.agents/doc/md/process/websocket.md` | WebSocket 进程 |
| `.agents/doc/md/process/crontab.md` | 定时任务 |
| `.agents/doc/md/process/filemonitor.md` | 文件监控与重载 |

普通 HTTP 进程只有其 handler 能力；需要完整应用路由、中间件、Session、异常和静态能力时使用 `type=app`。

## 项目工具、打包与运维

| 文档 | 何时读取 |
| --- | --- |
| `.agents/doc/md/interact.md` | 必须优先读取；框架原生构建、加密、systemd 服务和签名密钥操作 |
| `.agents/doc/md/scripts/buildBin.md` | 仅维护随时可能删除的传统二进制兼容脚本时读取 |
| `.agents/doc/md/scripts/encryptPhp.md` | 仅维护随时可能删除的传统加密兼容脚本时读取 |
| `.agents/doc/md/scripts/systemd.md` | 仅维护随时可能删除的传统 systemd 兼容脚本时读取 |
| `.agents/doc/md/scripts/tokenKey.md` | 仅维护随时可能删除的传统密钥兼容脚本时读取 |

## 阅读规则

- 文档链接中的 `/md/...` 是站点路由；本地读取使用本表中的真实文件路径。
- 示例可能为了演示而简化错误处理、状态码或安全校验，生成生产代码时仍遵守 Skill 的安全和框架优先规则。
- 文档若使用项目中不存在的 helper 或与源码签名不符，以当前源码为准并修正文档。
- 涉及第三方 ORM/模板/邮件库时，同时核对 `composer.lock` 对应版本的上游文档。
