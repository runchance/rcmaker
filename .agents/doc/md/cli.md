# PHP-CLI 模式

## 环境要求

Linux、macOS 和 Windows 都统一使用 `php index.php start`。Windows 由框架内置主控按进程组启动主 APP、自定义 APP 和普通进程，适合本地开发验证；正式的多进程部署和资源治理仍建议使用 Linux。

1. PHP 版本 8.1 及以上。
2. Linux 多进程模式必须安装 [pcntl 扩展](http://cn2.php.net/manual/zh/book.pcntl.php)；Windows 不需要。
3. Linux 多进程模式必须安装 [posix 扩展](http://cn2.php.net/manual/zh/book.posix.php)；Windows 不需要。
4. 推荐安装 [event 扩展](http://php.net/manual/zh/book.event.php)。
5. 需要启用以下函数。某些集成环境可能会默认禁用其中一部分函数：

```text
stream_socket_server,
stream_socket_client

pcntl_signal_dispatch,
pcntl_signal,
pcntl_alarm,
pcntl_fork

posix_getuid,
posix_getpwuid,
posix_kill,
posix_setsid,
posix_getpid,
posix_getppid,
posix_getpwnam,
posix_getgrnam,
posix_getgid,
posix_setgid,
posix_initgroups,
posix_setuid,
posix_isatty
```

## 启动项目

以 debug（调试）方式启动：

```shell
php index.php start
```

终止运行：

```text
ctrl + c
```

以 daemon（守护进程）方式启动：

```shell
php index.php start -d
```

终止运行：

```shell
php index.php stop
```

平滑重启：

```shell
php index.php reload
```

> 以 debug 方式启动时，代码中的 `echo`、`var_dump`、`print` 等输出会直接显示在终端。

> 以 daemon 方式启动后，代码中的 `echo`、`var_dump`、`print` 等输出会默认重定向到 `./runtime/logs/RC_Workerman(Swoole).log`。

## 启动 Banner

`config/app.php` 中 `cli_banner=true` 时，框架使用 Workerman 5.x 风格的默认启动 Banner。这个开关不读取 `.env`；项目可以在 `config/banner.php` 预设产品名称、版本、多行颜色、运行变量和可选进程列表，完整说明参考 [启动 Banner](md/banner.md)。

## 支持库

### Workerman

Workerman 依赖已内置。

在 `./config/app.php` 或 `.env` 中配置：

```ini
app.cli_frame = workerman
```

Workerman 配置文件为 `./config/worker.php`。

### Swoole

Swoole 需要独立安装 Swoole 扩展，请参考 [安装 Swoole](http://wiki.swoole.com/#/environment)。

在 `./config/app.php` 或 `.env` 中配置：

```ini
app.cli_frame = swoole
```

Swoole 配置文件为 `./config/swoole.php`。

## Swoole 模式说明

Swoole 支持异步和协程模式，默认使用协程模式。协程模式下进程相对独立，`bootstrap.php` 中的回调类不共享；如果自定义进程也需要载入回调类，请在 `./config/process.php` 中为对应进程配置 `bootstrap`，可参考示例 `RC_HTTP`。

切换为异步模式时，可以在 `./config/swoole.php` 或 `.env` 中配置：

```ini
swoole.coroutine = false
```

## Workerman 命令

当以 daemon 方式启动项目后，支持以下命令：

查看状态：

```shell
php index.php status
```

查看连接状态：

```shell
php index.php connections
```
