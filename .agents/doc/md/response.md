# 响应对象

响应对象用于控制 HTTP 响应的状态码、Header、Cookie、Body、重定向和文件输出。框架会在 cli 模式和普通 PHP-FPM 模式下自动完成响应发送。

## 快速示例

```php
<?php
namespace app\index\controller;

class test
{
    public function hello($req)
    {
        return $req->response('Hello World');
    }
}
```

指定状态码和 Header：

```php
return $req->response('Hello World', 200, [
    'X-App' => 'rcmaker',
]);
```

## 常用响应方法

| 方法 | 说明 |
| --- | --- |
| `$req->response($body, $status, $headers)` | 创建普通响应。 |
| `$req->json($data)` | 返回 JSON 响应。 |
| `$req->xml($xml)` | 返回 XML 响应。 |
| `$req->jsonp($data, $callback)` | 返回 JSONP 响应。 |
| `$req->redirect($url, $status)` | 返回重定向响应。 |
| `$req->V($template, $vars)` | 返回模板视图。 |
| `$req->response()->file($path)` | 返回文件流。 |
| `$req->response()->download($path, $name)` | 返回下载文件流。 |
| `$req->D($path, $name)` | 下载文件助手函数简写。 |

## JSON 响应

```php
<?php
namespace app\index\controller;

class test
{
    public function hello($req)
    {
        return $req->json([
            'code' => 0,
            'msg' => 'ok',
        ]);
    }
}
```

等价的普通响应写法：

```php
$data = ['code' => 0, 'msg' => 'ok'];
return $req->response(
    json_encode($data, JSON_UNESCAPED_UNICODE),
    200,
    ['Content-Type' => 'application/json']
);
```

`$req->json()` 会自动设置 `Content-Type: application/json`。如果 JSON 编码失败，框架会返回一个包含编码错误信息的 JSON 结构，避免响应体为空。

## JSONP 响应

```php
return $req->jsonp(['code' => 0], 'callback');
```

`callback` 名称只允许 JavaScript 标识符或点号链，例如 `callback`、`app.callback`。如果传入非法 callback，框架会自动回退为 `callback`，避免脚本注入风险。

## XML 响应

```php
$xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<values>
    <code>0</code>
    <msg>ok</msg>
</values>
XML;

return $req->xml($xml);
```

当前 XML 响应使用 `Content-Type: text/xml`。

## 模板视图

```php
return $req->V('index', [
    'name' => 'rcmaker',
]);
```

## 重定向

```php
return $req->redirect('/index');
```

指定状态码和 Header：

```php
return $req->redirect('/index', 302, [
    'X-Redirect-From' => 'login',
]);
```

重定向地址会清理换行字符，避免 Header 注入。业务中如果重定向地址来自用户输入，仍建议只允许站内路径或可信域名。

## Header 设置

创建响应时直接传入 Header：

```php
return $req->response('Hello', 200, [
    'Content-Type' => 'text/plain; charset=UTF-8',
    'X-Header-One' => 'Header Value',
]);
```

也可以先创建响应对象再设置：

```php
$response = $req->response();
$response->withHeader('Content-Type', 'application/json');
$response->withHeaders([
    'X-Header-One' => 'Header Value 1',
    'X-Header-Two' => 'Header Value 2',
]);
$response->withBody('{"code":0,"msg":"ok"}');
return $response;
```

## Cookie 设置

```php
$response = $req->response('ok');
$response->cookie('name', 'tom');
return $response;
```

也可以使用请求对象映射的助手函数：

```php
$req->SC(['name' => 'tom']);
return $req->response('ok');
```

## 文件响应

```php
return $req->response()->file(public_path().'/favicon.ico');
```

文件响应会处理 `If-Modified-Since`，当文件未修改时返回 `304`，减少重复传输。

安全提示：不要把未经校验的用户输入直接拼接为文件路径。建议限定在业务允许的目录内，并确认文件存在。

## 下载响应

```php
return $req->response()->download(public_path().'/favicon.ico', 'custom.ico');
```

助手函数简写：

```php
return $req->D(public_path().'/favicon.ico', 'custom.ico');
```

下载文件名会清理换行和双引号，并使用 `basename()` 去除路径部分，避免响应头注入和路径泄露。

## 响应对象接口

```php
$response = $req->response();

$response->withHeader($name, $value);
$response->withHeaders($headers);
$response->withoutHeader($name);
$response->getHeader($name);
$response->getHeaders();

$response->withStatus($code, $reason_phrase = null);
$response->getStatusCode();
$response->getReasonPhrase();
$response->withProtocolVersion($version);

$response->withBody($body);
$response->rawBody();

$response->withFile($file, $offset = 0, $length = 0);
$response->file($file);
$response->download($file, $download_name = '');

$response->cookie(
    $name,
    $value = '',
    $max_age = 0,
    $path = '',
    $domain = '',
    $secure = false,
    $http_only = false,
    $same_site = false
);
```

## 运行模式说明

- cli 模式下，框架会根据底层运行时自动适配响应发送流程。
- PHP-FPM 模式下，框架会通过 `header()`、`http_response_code()` 和 `echo` 输出响应。

框架会保留响应 Header 中包含冒号的值，例如 `Location: http://example.com/path`，不会因为冒号截断 Header 值。
