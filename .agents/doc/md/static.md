# 静态文件

静态文件配置主要用于 CLI 模式。FPM 模式下，静态资源通常由 Nginx 或 Apache 直接处理。

如果需要应用级静态目录、gzip 或预加载能力，请参考 [静态目录](md/static-directory.md)。

## Swoole 模式

当 `app.cli_frame = swoole` 时，配置文件为 `./config/swoole.php`，也可以在 `./.env` 的 `[swoole]` 配置段中覆盖。

建议关闭 Swoole 自带静态文件处理，使用框架内置静态文件能力：

```ini
swoole.enable_static_handler = false
swoole.enable_static_file = true
```

## Workerman 模式

当 `app.cli_frame = workerman` 时，配置文件为 `./config/worker.php`，也可以在 `./.env` 的 `[workerman]` 配置段中覆盖。

开启框架内置静态文件能力：

```ini
workerman.enable_static_file = true
```

## 访问示例

默认静态目录为 `./public`。

| 访问地址 | 实际文件 |
| --- | --- |
| `http://localhost:8680/1.jpg` | `./public/1.jpg` |
| `http://localhost:8680/test.css` | `./public/test.css` |
