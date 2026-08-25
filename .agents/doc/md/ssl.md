# SSL / HTTPS

CLI 模式下，主 HTTP 服务和自定义监听进程都可以配置 SSL。证书路径需要按实际部署环境填写。

## 主服务启用 HTTPS

### Swoole

在 `./config/swoole.php` 或 `.env` 中开启：

```ini
swoole.ssl = true
```

### Workerman

在 `./config/worker.php` 或 `.env` 中设置传输层为 `ssl`：

```ini
workerman.transport = ssl
```

### 证书配置

在 `.env` 中配置证书和私钥：

```ini
ssl.local_cert = /YourPath/server.crt
ssl.local_pk = /YourPath/server.key
ssl.verify_peer = false
ssl.allow_self_signed = true
```

访问测试：

```text
https://localhost:8680
```

## 自定义进程启用 SSL

在 `./config/process.php` 中为对应进程配置：

```php
'ssl' => true,
'context' => [
    'ssl' => [
        'local_cert' => '/YourPath/server.crt',
        'local_pk' => '/YourPath/server.key',
        'verify_peer' => false,
        'allow_self_signed' => true,
    ],
],
```

可以参考 `RC_HTTP` 示例配置。
