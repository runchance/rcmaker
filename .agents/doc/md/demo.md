# 简单示例

下面用一个控制器演示字符串、JSON、XML、JSONP 和模板响应。文件放在：

```text
apps/index/controller/test.php
```

## 创建控制器

```php
<?php
namespace app\index\controller;

class test
{
    public function index($req)
    {
        $name = $req->get('name', 'World');
        return 'Hello ' . $name;
    }

    public function json($req)
    {
        return $req->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $req->get('name', 'World'),
        ]);
    }

    public function xml($req)
    {
        $name = htmlspecialchars($req->get('name', 'World'), ENT_XML1);
        return $req->xml(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<developer><name>' . $name . '</name></developer>'
        );
    }

    public function jsonp($req)
    {
        return $req->jsonp([
            'code' => 0,
            'msg' => 'ok',
        ], 'callback');
    }

    public function page($req)
    {
        return $req->V('hello', [
            'name' => $req->get('name', 'World'),
        ]);
    }
}
```

框架会把 URL 中的控制器和方法映射到对应类方法：

| 访问地址 | 返回内容 |
| --- | --- |
| `/test` | `Hello World` |
| `/test?name=rcmaker` | `Hello rcmaker` |
| `/test/json?name=rcmaker` | JSON |
| `/test/xml?name=rcmaker` | XML |
| `/test/jsonp?callback=showResult` | JSONP |
| `/test/page?name=rcmaker` | 模板页面 |

完整地址以默认端口为例：`http://127.0.0.1:8680/test/json?name=rcmaker`。

> [!TIP]
> 返回 JSON 时直接使用 `$req->json()`。它会完成编码并设置响应头，不需要自己调用 `json_encode()`。

## 创建模板

新建 `view/index/hello.html`：

```html
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>rcmaker</title>
</head>
<body>
    <h1>Hello <?= htmlspecialchars($name) ?></h1>
</body>
</html>
```

默认为原生 PHP 模板。Blade、Twig 和模板配置见 [视图与模板](md/view.md)。

## 下一步

- 请求参数、Header、Cookie 和上传文件：[请求对象](md/request.md)
- 状态码、响应头、文件下载和重定向：[响应对象](md/response.md)
- 自定义路由和路由参数：[路由](md/route.md)
- 多应用和域名绑定：[多应用](md/apps.md)
