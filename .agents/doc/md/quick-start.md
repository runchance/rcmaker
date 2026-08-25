# 5 分钟开始

这页只做一件事：把项目跑起来，并返回第一段 JSON。

## 1. 准备环境

已经可以运行 `php` 和 `composer` 时，直接检查版本：

```shell
php -v
composer --version
```

PHP 支持 `8.1` 至 `8.5`，新项目推荐 `8.5`。全新环境用下面的一条命令同时安装 PHP 8.5 和 Composer。

Linux / macOS：

```shell
curl -fsSL https://rcmaker.runchance.com/download/install-php.sh | sh
```

Windows PowerShell：

```powershell
irm https://rcmaker.runchance.com/download/install-php.ps1 | iex
```

Windows 安装目录位于 `%LOCALAPPDATA%\rcmaker\bin`，不需要把 `php.exe` 复制到新项目。详细参数和单独安装 Composer 的方法见 [安装与启动](md/install.md)。

## 2. 创建项目

Linux 和 macOS：

```shell
composer create-project runchance/rcmaker
cd rcmaker
cp .env.example .env
```

Windows：

```bat
composer create-project runchance/rcmaker
cd rcmaker
copy .env.example .env
```

## 3. 启动服务

Linux 和 macOS：

```shell
php index.php start
```

Windows：

```bat
windows.bat
```

浏览器访问：

```text
http://127.0.0.1:8680/
```

看到 `Hello rcmaker!`，说明项目已经正常运行。

## 4. 返回第一段 JSON

打开 `apps/index/controller/index.php`，将 `index()` 改为：

```php
public function index($req)
{
    return $req->json([
        'code' => 0,
        'msg' => 'rcmaker is ready',
    ]);
}
```

重启服务后刷新首页，将得到：

```json
{"code":0,"msg":"rcmaker is ready"}
```

这里直接使用框架的 `$req->json()`，不需要自己调用 `json_encode()` 或手工设置响应头。

## 接下来做什么

- 读取 GET、POST、Header 和上传文件：[请求对象](md/request.md)
- 返回 JSON、文件、重定向和页面：[响应对象](md/response.md)
- 增加控制器和自定义路由：[控制器](md/controller.md)、[路由](md/route.md)
- 使用数据库、Redis 和模型：[数据库配置](md/db/config.md)
- 打包、加密或注册系统服务：[交互式项目工具](md/interact.md)

> [!NOTE]
> rcmaker 是常驻内存框架。开发过程中修改 PHP 文件后，如果未启用文件监控，请重启服务再验证结果。
