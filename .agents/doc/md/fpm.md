# PHP-FPM / PHP-MOD 模式

FPM / PHP-MOD 模式适合把 rcmaker 作为传统 PHP Web 项目运行，由 Nginx、Apache 等 Web 容器处理入口和静态资源。

## 环境要求

1. PHP 版本 8.1 及以上。
2. 安装 Apache 2.4、Nginx 1.x 或 Tengine 2.x。
3. Web 站点根目录应指向项目的 `./public` 目录。
4. `./runtime/logs` 需要可写权限。如果目录所属用户和 PHP-FPM 运行用户不同，需要同步调整目录权限或所有者。

如果开启了 `open_basedir`，需要同时放行项目根目录、`public` 目录和临时目录。例如：

```ini
open_basedir=/home/ubuntu/rcmaker/public/:/home/ubuntu/rcmaker/:/tmp/
```

## Nginx 示例

```nginx
listen 80;
server_name 127.0.0.1;
index index.php index.html index.htm default.php default.htm default.html;
root /YourPath/rcmaker/public;

location ~* /(.git|.svn|.env) {
    deny all;
}

location / {
    if (!-e $request_filename) {
        rewrite ^(.*)$ /index.php?s=/$1 last;
    }
}
```

## Apache 示例

```apache
<VirtualHost *:80>
    ServerAdmin webmaster@example.com
    DocumentRoot "/YourPath/rcmaker/public"
    ServerName 127.0.0.1
    ServerAlias *

    <Files ~ (\.user.ini|\.htaccess|\.git|\.svn|\.project|LICENSE|README.md)$>
        Order allow,deny
        Deny from all
    </Files>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/tmp/php-cgi-80.sock|fcgi://localhost"
    </FilesMatch>

    <Directory "/YourPath/rcmaker/public">
        SetOutputFilter DEFLATE
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php index.html index.htm default.php default.html default.htm
    </Directory>
</VirtualHost>
```

`.htaccess` 示例：

```apache
<IfModule mod_rewrite.c>
RewriteEngine on
RewriteRule ^.git - [F,L]
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ /index.php/$1 [QSA,PT,L]
</IfModule>
```

## 反向代理 CLI 服务

如果希望 Apache、Nginx 作为入口代理到 CLI 模式的 rcmaker 服务，可以这样处理：

1. 先用 CLI 模式启动 rcmaker：

```shell
php index.php start
```

2. 配置反向代理到默认监听地址 `http://localhost:8680`。

### Apache 代理示例

需要启用 `rewrite`、`proxy`、`proxy_http` 等模块。

项目根目录新建 `.htaccess`：

```apache
<IfModule mod_rewrite.c>
Options +FollowSymlinks
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ http://localhost:8680/$1 [QSA,P,L]
</IfModule>
```

### Nginx 代理示例

```nginx
location / {
    proxy_http_version 1.1;
    proxy_set_header Connection "keep-alive";
    proxy_set_header X-Real-IP $remote_addr;
    if (!-f $request_filename) {
        proxy_pass http://localhost:8680;
    }
}
```
