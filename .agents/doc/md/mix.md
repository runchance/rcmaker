# 混合模式

混合模式适合这种场景：Web 请求由 Nginx、Apache、PHP-FPM 等传统 Web 容器处理，但仍然希望使用 rcmaker 的自定义进程、定时任务、队列消费、RPC 等 CLI 常驻能力。

## 配置方式

在 `./.env` 中关闭 CLI 主 Web 服务进程：

```ini
app.start_app = false
```

然后按 CLI 模式启动项目：

```shell
php index.php start
```

此时 rcmaker 只启动自定义进程，不再启动主 HTTP 服务。

## Web 入口

如果 Web 请求仍由 Apache 或 Nginx 处理，请按 [PHP-FPM / PHP-MOD 模式](md/fpm.md) 配置站点入口。
