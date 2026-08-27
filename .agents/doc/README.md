# rcmaker 文档

**用 PHP 快速开发，像 Go 应用一样交付。**

如果是第一次使用，先看 [5 分钟开始](md/quick-start.md)。它只保留跑起项目需要的步骤；遇到环境问题，再回到 [安装与启动](md/install.md) 查看对应平台的处理方法。

<div class="doc-home-grid">
  <a href="#/md/quick-start"><strong>5 分钟开始</strong><span>创建项目、启动服务、返回第一段 JSON</span></a>
  <a href="#/md/install"><strong>安装与启动</strong><span>Linux、macOS、Windows 安装方法</span></a>
  <a href="#/md/interact"><strong>交互式项目工具</strong><span>打包、加密、服务注册和密钥生成</span></a>
  <a href="#/md/overview"><strong>为什么用 rcmaker</strong><span>性能、进程组、静态预热和独立交付</span></a>
</div>

## 第一次使用

1. 运行一键环境安装命令，或确认 `php -v` 和 `composer --version` 可以运行。
2. 使用 Composer 创建项目并复制 `.env`。
3. 启动服务，访问 `http://127.0.0.1:8680/`。
4. 修改默认控制器，返回字符串、JSON 或页面。

```shell
composer create-project runchance/rcmaker
cd rcmaker
cp .env.example .env
php index.php start
```

Windows 将复制命令换成：

```bat
copy .env.example .env
php index.php start
```

> [!TIP]
> 没有 PHP 或 Composer 时，不必分别查找安装包。前往 [安装与启动](md/install.md)，运行对应平台的一键安装命令即可。

> [!NOTE]
> rcmaker 项目自带 AI 开发 Skill、本地框架文档和主流编程 Agent 入口，帮助 AI 先查框架、复用现有组件，减少重复实现。

## 按需要继续

| 现在要做什么 | 从这里开始 |
| --- | --- |
| 写接口或页面 | [控制器](md/controller.md)、[请求对象](md/request.md)、[响应对象](md/response.md) |
| 配置路由和多应用 | [路由](md/route.md)、[多应用](md/apps.md) |
| 访问数据库和缓存 | [数据库配置](md/db/config.md)、[Redis](md/db/redis.md)、[模型](md/db/model.md) |
| 使用框架内置组件 | [验证器](md/module/validation.md)、[令牌与鉴权](md/module/token.md)、[全部组件](md/module/autoform.md) |
| 拆分独立端口和进程 | [应用进程组](md/app-process.md)、[自定义进程](md/process.md) |
| 配置静态站点和预热 | [静态目录与应用](md/static-directory.md)、[静态文件](md/static.md) |
| 生成独立可执行程序 | [交互式项目工具](md/interact.md) |

## 使用原则

- 业务代码优先使用框架已经提供的请求、响应、数据库和组件能力。
- 新项目优先使用 `php index.php interact`，传统 `scripts/*.php` 仅用于兼容旧流程。
- 常驻内存模式下，修改配置、路由、模板引擎选项或依赖后要重启服务。
- 多应用需要独立资源时使用 APP 进程组，不需要隔离时继续使用主进程组即可。

完整的设计、适用场景和性能说明见 [框架概览](md/overview.md)。源码位于 [GitHub](https://github.com/runchance/rcmaker)，采用 Apache License 2.0。
