# 日志

rcmaker 内置了 cli 模式访问日志功能，用于记录请求访问明细。


## 功能说明

这里的内置日志主要指访问日志，不是通用的应用日志组件。

- 只在 cli 模式下生效。
- 默认关闭。
- 开启后会自动注册日志处理进程，不需要手动在 `config/process.php` 中额外配置。
- 日志文件默认写入 `./runtime/logs/` 目录。

如果你需要的是异常日志，请参考 [异常处理](md/exception.md)。



## 日志记录开关

配置文件 `config/app.php` 中的 `app.cli_log = true` 则开启访问日志功能。

示例：

```php
<?php
return [
	'cli_log' => true,
];
?>
```

也可以通过环境变量控制：

```ini
app.cli_log = true
```

> 日志记录会自动开启独立的进程进行处理,用户不需要进行进程配置。

## 日志文件位置

用户访问 `http://localhost:8680/index` 后，会在 `./runtime/logs/` 内生成访问日志。

默认文件名格式：

```text
rcmaker_access_[应用名]_年-月-日.log
```

例如：

```text
runtime/logs/rcmaker_access_[index]_2026-07-01.log
```

## 日志内容

每条访问日志默认会记录这些信息：

- 请求时间
- 运行标记
- 客户端 IP
- 请求方法
- 请求路径
- 协议
- 响应状态码
- Referer
- User-Agent

日志内容示意：

```text
2026-07-01 12:00:00[CLI] - [127.0.0.1] - "GET /index HTTP/1.1" - 200 "https://example.com" "Mozilla/5.0"
```

## 运行说明

- cli 模式下，框架会在响应发送阶段收集访问数据。
- 底层运行时不同，日志采集实现也不同，但对业务侧的开关和文件结果是统一的。
- 当 `app.start_app = false` 时，如果没有启动应用 HTTP 进程，就不会产生应用访问日志。

## workerman 配置补充

如果当前 cli 底层使用的是 workerman，日志进程默认监听地址来自 `config/worker.php` 中的 `logger_listen` 配置：

```php
'logger_listen' => 'Text://127.0.0.1:8689'
```

一般不需要修改，只有在端口冲突或日志进程通信需要调整时再改。

## 注意事项

- 这项功能记录的是访问日志，不会自动替代业务审计日志、SQL 日志或异常日志。
- 高频访问场景下，开启访问日志会带来额外 I/O 开销，生产环境建议按需开启。
- 日志文件会按日期持续追加，生产环境建议配合系统级日志清理策略。
- 如果代理层会改写真实 IP，建议同时确认请求 IP 获取逻辑是否符合你的部署环境。

如下图:

![](../img/log.png)


![](../img/log_detail.png)

