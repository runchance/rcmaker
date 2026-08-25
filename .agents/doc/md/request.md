# 请求对象

`Request` 对象会作为控制器方法的第一个参数自动注入，用于读取请求参数、Header、Cookie、上传文件、客户端信息，以及调用需要请求上下文的助手函数。

## 基础示例

```php
<?php
namespace app\index\controller;

class test
{
    public function hello($req)
    {
        $name = $req->get('name', 'World');
        return 'Hello '.$name;
    }
}
```

访问：

```text
http://localhost:8680/test/hello?name=tom
```

返回：

```text
Hello tom
```

## 常用方法速查

| 方法 | 说明 |
| --- | --- |
| `$req->get()` | 获取 GET 参数数组。 |
| `$req->get('key', 'default')` | 获取指定 GET 参数。 |
| `$req->post()` | 获取 POST 参数数组。 |
| `$req->post('key', 'default')` | 获取指定 POST 参数。 |
| `$req->rawBody()` | 获取原始请求体。 |
| `$req->header()` | 获取 Header 数组。 |
| `$req->header('accept', '')` | 获取指定 Header。 |
| `$req->cookie()` | 获取 Cookie 数组。 |
| `$req->cookie('name', '')` | 获取指定 Cookie。 |
| `$req->file()` | 获取上传文件数组。 |
| `$req->file('upload')` | 获取指定上传字段。 |
| `$req->host()` | 获取 Host。 |
| `$req->host(true)` | 获取不带端口的 Host。 |
| `$req->method()` | 获取请求方法。 |
| `$req->path()` | 获取请求路径。 |
| `$req->ip()` | 获取客户端 IP。 |
| `$req->queryString()` | 获取原始 query string。 |
| `$req->isAjax()` | 判断是否为 Ajax 请求。 |
| `$req->isPjax()` | 判断是否为 Pjax 请求。 |
| `$req->expectsJson()` | 判断客户端是否更期待 JSON 响应。 |
| `$req->acceptJson()` | 判断 `Accept` Header 是否包含 JSON。 |

## GET 和 POST 参数

获取整个 GET 参数数组：

```php
$query = $req->get();
```

获取指定 GET 参数，并设置默认值：

```php
$page = $req->get('page', 1);
```

获取整个 POST 参数数组：

```php
$data = $req->post();
```

获取指定 POST 参数，并设置默认值：

```php
$name = $req->post('name', 'guest');
```

获取原始请求体：

```php
$body = $req->rawBody();
```

## Header

获取整个 Header 数组：

```php
$headers = $req->header();
```

获取指定 Header：

```php
$accept = $req->header('accept', '');
```

判断 JSON 请求：

```php
if ($req->acceptJson()) {
    return $req->json(['code' => 0, 'msg' => 'ok']);
}
```

不同运行模式下，底层 Header 结构可能不同。业务代码读取单个 Header 时，建议使用小写名称，例如 `accept`、`authorization`、`x-requested-with`。

## Cookie

获取整个 Cookie 数组：

```php
$cookies = $req->cookie();
```

获取指定 Cookie：

```php
$name = $req->cookie('name', 'guest');
```

设置并读取 Cookie：

```php
<?php
namespace app\index\controller;

class test
{
    public function setCookie($req)
    {
        $req->SC(['name' => 'tom']);
        return 'ok';
    }

    public function getCookie($req)
    {
        return 'Hello '.$req->cookie('name', 'guest');
    }
}
```

访问：

```text
http://localhost:8680/test/setCookie
http://localhost:8680/test/getCookie
```

## 上传文件

获取所有上传文件：

```php
$files = $req->file();
```

获取指定上传字段：

```php
$file = $req->file('upload1');
```

如果没有上传文件，`$req->file()` 返回空数组，`$req->file('upload1')` 返回 `null`。

上传表单示例：

```html
<form action="/test/file" method="post" enctype="multipart/form-data">
  <input type="file" name="upload1" />
  <input type="submit" value="上传文件" />
</form>
```

控制器示例：

```php
<?php
namespace app\index\controller;

class test
{
    public function file($req)
    {
        $file = $req->file('upload1');
        if (!$file) {
            return $req->json(['code' => 1, 'msg' => 'file not found']);
        }

        $file->move(public_path().'/files/'.$file->getUploadName());
        return $req->json(['code' => 0, 'msg' => 'upload success']);
    }
}
```

注意事项：

- cli 模式上传大小受 `config/worker.php` 或 `config/swoole.php` 中的 `max_package_size`、`package_max_length` 影响。
- PHP-FPM 模式上传大小受 `php.ini` 中的 `upload_max_filesize` 和 `post_max_size` 影响。
- 上传文件名来自客户端，保存文件前建议做扩展名、MIME、大小和文件名校验。
- 请求结束后，临时文件可能会被运行环境清理，需要在当前请求内完成移动或保存。

## Host 和请求信息

获取 Host：

```php
$host = $req->host();
```

获取不带端口的 Host：

```php
$host = $req->host(true);
```

获取请求方法：

```php
$method = $req->method();
```

获取请求路径：

```php
$path = $req->path();
```

获取 query string：

```php
$query = $req->queryString();
```

## 客户端 IP

```php
$ip = $req->ip();
```

`ip()` 会读取常见代理 Header，例如 `x-real-ip`、`x-forwarded-for`。如果服务直接暴露公网，客户端可能伪造这些 Header。安全敏感场景建议配合可信代理配置使用，或优先使用真实连接 IP。

## 当前路由信息

控制器执行时，可以通过 `$req->app` 获取当前解析到的应用、控制器、方法和类名：

```php
$app = $req->app['app'];
$controller = $req->app['controller'];
$action = $req->app['action'];
$class = $req->app['class'];
```

## 原生请求对象

cli 模式下可以通过 `raw()` 调用底层原生请求对象方法，或读取原生数组属性。

```php
$uri = $req->raw('uri');
$host = $req->raw('host', true);
$requestTime = $req->raw('server', 'request_time');
```

安全提示：不要把用户输入直接作为 `raw()` 的方法名或属性名。`raw()` 适合框架内部或可信业务代码读取底层能力。

## 助手函数映射

部分助手函数需要传入 `Request` 对象。为了在控制器里写法更简洁，可以直接通过 `$req` 调用映射方法。

普通助手函数写法：

```php
return json($req, [
    'code' => 0,
    'msg' => 'ok',
]);
```

请求对象映射写法：

```php
return $req->json([
    'code' => 0,
    'msg' => 'ok',
]);
```

## 映射列表

| 说明 | 助手函数 | Request 映射 | 简写函数 | 简写映射 |
| --- | --- | --- | --- | --- |
| 响应器 | `response($request, ...)` | `$req->response(...)` | `R($request, ...)` | `$req->R(...)` |
| JSON 响应 | `json($request, ...)` | `$req->json(...)` | 无 | 无 |
| JSONP 响应 | `jsonp($request, ...)` | `$req->jsonp(...)` | 无 | 无 |
| 重定向响应 | `redirect($request, ...)` | `$req->redirect(...)` | 无 | 无 |
| PDF 对象 | `pdf($request, ...)` | `$req->pdf(...)` | `P($request, ...)` | `$req->P(...)` |
| 视图 | `view($request, ...)` | `$req->view(...)` | `V($request, ...)` | `$req->V(...)` |
| 模型 | `model($request, ...)` | `$req->model(...)` | `M($request, ...)` | `$req->M(...)` |
| 二维码对象 | `qrcode($request, ...)` | `$req->qrcode(...)` | `Q($request, ...)` | `$req->Q(...)` |
| 设置 Cookie | `setcookies($request, ...)` | `$req->setcookies(...)` | `SC($request, ...)` | `$req->SC(...)` |
| 获取 Cookie | `getcookies($request, ...)` | `$req->getcookies(...)` | `GC($request, ...)` | `$req->GC(...)` |
| Session 操作 | `sessions($request, ...)` | `$req->sessions(...)` | `S($request, ...)` | `$req->S(...)` |
| 验证码 | `captcha($request, ...)` | `$req->captcha(...)` | `C($request, ...)` | `$req->C(...)` |
| 验证码校验 | `captchaCheck($request, ...)` | `$req->captchaCheck(...)` | `CC($request, ...)` | `$req->CC(...)` |
| 下载响应 | `download($request, ...)` | `$req->download(...)` | `D($request, ...)` | `$req->D(...)` |
| 自动表单组件 | `autoForm($request, ...)` | `$req->autoForm(...)` | `AF($request, ...)` | `$req->AF(...)` |
| SDB 对象 | `simple_database($request, ...)` | `$req->simple_database(...)` | `SDB($request, ...)` | `$req->SDB(...)` |
| Token 对象 | `token($request, ...)` | `$req->token(...)` | `T($request, ...)` | `$req->T(...)` |
| 短信验证码组件 | `sms($request, ...)` | `$req->sms(...)` | 无 | 无 |
