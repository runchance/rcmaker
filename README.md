# rcmaker

http://rcmaker.runchance.com

## LICENSE
Apache2.0

## 项目简介

Github: https://github.com/runchance/rcmaker **帮忙点点小星星哦**

rcmaker 是一个面向项目落地的 PHP 框架，强调“一套代码、两种运行形态、内置常用能力、可交付部署”。
它既可以在传统 FPM 环境中使用，也可以在 CLI 常驻模式下运行，用同一套业务代码覆盖接口服务、静态应用、自定义进程、RPC、任务调度和源码保护等场景。

## 快速入口

| 目标 | 文档 |
| --- | --- |
| 安装运行环境并启动项目 | [安装与启动](md/install.md) |
| 了解项目目录和入口文件 | [目录结构](md/directory.md) |
| 编写接口和页面控制器 | [控制器](md/controller.md) / [路由](md/route.md) |
| 使用请求、响应、模板能力 | [请求对象](md/request.md) / [响应对象](md/response.md) / [视图与模板](md/view.md) |
| 打包、加密和注册服务 | [二进制打包](md/scripts/buildBin.md) / [PHP 加密与打包](md/scripts/encryptPhp.md) / [Linux 服务注册](md/scripts/systemd.md) |

## 适合场景

- 需要快速交付的业务系统
- 需要源码加密、混合分发或二进制打包的项目
- 需要按应用、按域名、按目录绑定静态入口的项目
- 需要自定义进程、RPC、定时任务和长驻服务的项目
- 需要把常见基础能力直接收进框架，而不是每个项目重复封装

## 核心优势

### 1. 源码加密与交付

- 支持项目源码加密与运行时解密/加载，便于交付和源码保护
- 加密和解密需要配合 rcmaker 提供的 PHP 独立执行程序使用，其中内置了 rcmaker 加密解密扩展
- 可配合打包流程生成可分发的运行包
- 支持明文代码和加密代码混合部署，兼顾灵活性和保护强度

### 2. 静态应用绑定

- 支持按应用、域名、静态目录绑定独立站点
- 支持多级应用结构，例如 `api/v2` 这类目录层级
- 支持 `document_root`、`index_default` 等独立静态配置
- 适合官网、活动页、后台静态资源、前后端分离静态站点

### 3. 静态资源能力

- 支持静态文件直出
- 支持 gzip 响应
- 支持启动时预热和缓存
- 支持按类型控制预热，避免无意义扫描和压缩
- 支持静态首页回退和多应用目录隔离

### 4. CLI 常驻能力

- 支持自定义进程、RPC、定时任务、队列消费等场景
- 适合内部服务、消息处理和高频接口服务
- CLI 模式下可以把业务代码长期驻留，减少重复启动开销

### 5. 常用组件内置

- `Validator`：参数和业务校验
- `AutoForm`：常见表单增删改查
- `Token` / `Captcha` / `Sms` / `Mailer`：认证、验证码和消息能力
- `Db` / `Redis`：数据访问和缓存封装
- `Qrcode` / `Xlsx` / `Pdf` / `Pinyin` / `Stopwatch` / `Curl`：常见业务工具
- 这些能力尽量以轻量方式封装，减少项目里重复造轮子

### 6. PHP 版本兼容

- 持续兼容 PHP 8.1 - 8.5
- 重点处理 nullable、签名、返回类型和动态属性等升级问题
- 让老项目升级时更少踩弃用告警

### 7. 安全与性能

- 路径、文件和静态资源处理都尽量做白名单和 `realpath` 校验
- 避免在请求热路径做多余 IO 和重型反射
- 优先做低开销优化，避免为了“封装感”牺牲性能

## 一个静态绑定示例

```php
return [
	'app' => [
		'domains' => ['file.test.com'],
		'document_root' => 'file', //绑定静态目录(./public/file)
		'index_default' => 'index.html', //绑定静态首页(./public/file/index.html)
		'static_preload_extensions' => ['css', 'js', 'html'],//预热静态文件类型
		'enable_static_file' => true,//启用静态文件直出
		'enable_static_gzip' => true,//启用静态文件 gzip 压缩
		'enable_static_preload' => true //启用静态文件预热
	],
];
```

这种方式适合把不同应用、不同域名和不同静态入口拆开管理，静态目录和默认首页都可以独立配置。

## 进一步了解

- 详细手册见：<http://rcmaker.runchance.com>
- 如果你更关注具体能力的使用方式，可以继续查看仓库内的文档和示例代码

## rcmaker能做什么

**1、作为独立的高性能 web 容器**
> 只要操作系统中装有 PHP 即可，不依赖 nginx、apache 等容器即可原生支持 HTTP|HTTPS 协议对外提供 web 服务。
> 使用 nginx、apache 等容器反向代理可以获得第三方容器的高兼容性、丰富的组件。
> CLI 模式支持常驻进程、自定义进程、RPC 和任务调度。

**2、作为 web 容器下的 PHP 独立框架**
> 使用 nginx、apache 作为 web 容器为项目提供基础框架支持。

**3、自定义进程**
> 定时任务。

> RPC 服务。

> web 服务。

> websocket 服务。

> 物联网、游戏、TCP 服务、UDP 服务、unix socket 服务。

> 其他定制协议服务。

## 压测

<details>
<summary>展开压测截图</summary>

虚拟机 VM 8 核 16G 操作系统 Ubuntu 20.04.3 LTS

**CLI 模式无业务压测**
**[workerman]**

![](http://rcmaker.runchance.com/benchmarks/workerman.png)

ab

![](http://rcmaker.runchance.com/benchmarks/ab_cli_workerman.png)

autocannon

![](http://rcmaker.runchance.com/benchmarks/cli_workerman.png)

**CLI 模式无业务压测**
**[swoole]**

![](http://rcmaker.runchance.com/benchmarks/swoole.png)

ab

![](http://rcmaker.runchance.com/benchmarks/ab_cli_swoole.png)

autocannon

![](http://rcmaker.runchance.com/benchmarks/cli_swoole.png)

</details>








