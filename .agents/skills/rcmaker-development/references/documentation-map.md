# 官方文档导航

当前 V3 文档位于 `official/doc/md/`。遇到不熟悉的能力时，从本页选择原文，再与当前源码核对。V2 项目只使用 `official/doc/V2/`，不要混用 V3 配置和 API。

## 入门与总体结构

| 文档 | 何时读取 |
| --- | --- |
| `official/doc/md/install.md` | 安装、Linux/Windows、x86_64/AArch64 运行时 |
| `official/doc/md/directory.md` | 目录职责和项目结构 |
| `official/doc/md/demo.md` | 最小控制器、JSON/XML/JSONP/视图示例 |
| `official/doc/md/Helper.md` | 全局助手、简写函数和 Request 映射 |

## 应用与 HTTP

| 文档 | 何时读取 |
| --- | --- |
| `official/doc/md/apps.md` | 多应用、域名、路径、Query 模式、自定义目录 |
| `official/doc/md/controller.md` | 控制器、返回值、路由参数、Hook 和常驻注意事项 |
| `official/doc/md/route.md` | 自动/自定义路由、分组、中间件、fallback、静态路由 |
| `official/doc/md/request.md` | GET/POST/Header/Cookie/文件、IP、原生请求和映射 |
| `official/doc/md/response.md` | JSON、Header、Cookie、文件、下载和响应对象 |
| `official/doc/md/middleware.md` | 全局/应用/路由/静态中间件、顺序和请求属性 |
| `official/doc/md/view.md` | Raw、Smarty、Think、Blade、Twig 和视图复用 |
| `official/doc/md/exception.md` | 异常 handler、report/render、debug 和日志 |
| `official/doc/md/cookie.md` | Cookie 生命周期和安全属性 |
| `official/doc/md/session.md` | Session 后端和操作 API |

## 运行、静态和部署入口

| 文档 | 何时读取 |
| --- | --- |
| `official/doc/md/cli.md` | Workerman/Swoole 启动和引擎选择 |
| `official/doc/md/fpm.md` | PHP-FPM/PHP-MOD 和反向代理 CLI |
| `official/doc/md/mix.md` | Web 与 CLI 混合模式 |
| `official/doc/md/ssl.md` | 主服务和自定义进程 SSL |
| `official/doc/md/log.md` | CLI 访问日志、内部日志进程和日志位置 |
| `official/doc/md/static.md` | Workerman/Swoole 静态文件开关 |
| `official/doc/md/static-directory.md` | 应用静态根目录、gzip 和预加载 |

## 数据库

| 文档 | 何时读取 |
| --- | --- |
| `official/doc/md/db/config.md` | DB 连接、默认引擎、bootstrap 和自定义进程 |
| `official/doc/md/db/frame.md` | Medoo/Think/Laravel 原生支持层与切换 |
| `official/doc/md/db/sdb.md` | 统一链式查询、CRUD、分页、事务和底层绑定 |
| `official/doc/md/db/model.md` | Think/Laravel Model、关系和初始化 |
| `official/doc/md/db/redis.md` | Redis raw/mix、多连接、事务、pipeline、订阅 |
| `official/doc/md/db/mongo.md` | MongoDB 依赖、配置和 ORM 示例 |
| `official/doc/md/ttt.md` | 空间查询 SQL 示例；属于专项示例，不是通用 DB 规范 |

## 业务组件

| 文档 | 能力 |
| --- | --- |
| `official/doc/md/module/validation.md` | Validator 和上传文件验证 |
| `official/doc/md/module/autoform.md` | 自动 CRUD、判重、回调、事务和分页 |
| `official/doc/md/module/cache.md` | Cache、TTL、remember、标签 |
| `official/doc/md/module/paginator.md` | HTML/JSON 分页和 SDB 联动 |
| `official/doc/md/module/token.md` | Token、刷新、guard、证书和单设备登录 |
| `official/doc/md/module/captcha.md` | 图形验证码、存储和校验 |
| `official/doc/md/module/sms.md` | 短信验证码、scene、有效期和服务商 |
| `official/doc/md/module/throttler.md` | 缓存令牌桶限流和 key 设计 |
| `official/doc/md/module/curl.md` | 单请求、并发、JSON、下载和回调 |
| `official/doc/md/module/mailer.md` | SMTP/邮件、HTML、附件和状态复用 |
| `official/doc/md/module/excel.md` | Excel 读写 |
| `official/doc/md/module/pdf.md` | PDF 初始化和输出 |
| `official/doc/md/module/qrcode.md` | 二维码格式、保存和输出 |
| `official/doc/md/module/pinyin.md` | 拼音、声调和首字母 |
| `official/doc/md/module/stopWatch.md` | 性能计时和诊断 |

## 进程与队列

| 文档 | 何时读取 |
| --- | --- |
| `official/doc/md/process.md` | 普通自定义进程、APP 进程和配置参数 |
| `official/doc/md/app-process.md` | 应用绑定、独立端口/资源、静态应用和反向代理 |
| `official/doc/md/queue.md` | 投递、消费者、重试、多连接和消费进程 |
| `official/doc/md/process/http.md` | 普通 HTTP 自定义进程 |
| `official/doc/md/process/tcp.md` | TCP 进程 |
| `official/doc/md/process/text.md` | Text 协议进程 |
| `official/doc/md/process/websocket.md` | WebSocket 进程 |
| `official/doc/md/process/crontab.md` | 定时任务 |
| `official/doc/md/process/filemonitor.md` | 文件监控与重载 |

普通 HTTP 进程只有其 handler 能力；需要完整应用路由、中间件、Session、异常和静态能力时使用 `type=app`。

## 脚本、打包与运维

| 文档 | 何时读取 |
| --- | --- |
| `official/doc/md/scripts/buildBin.md` | rcmaker 单文件二进制、架构和加密 |
| `official/doc/md/scripts/encryptPhp.md` | 通用 PHP 源码加密和独立脚本打包 |
| `official/doc/md/scripts/systemd.md` | PHP/二进制注册 Linux 服务 |
| `official/doc/md/scripts/tokenKey.md` | Token 算法证书生成 |

## 阅读规则

- 文档链接中的 `/md/...` 是站点路由；本地读取使用本表中的真实文件路径。
- 示例可能为了演示而简化错误处理、状态码或安全校验，生成生产代码时仍遵守 Skill 的安全和框架优先规则。
- 文档若使用项目中不存在的 helper 或与源码签名不符，以当前源码为准并修正文档。
- 涉及第三方 ORM/模板/邮件库时，同时核对 `composer.lock` 对应版本的上游文档。
