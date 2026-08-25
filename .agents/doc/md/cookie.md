# Cookie 管理

## 设置 Cookie

设置 Cookie 的本质是在 HTTP 响应头中返回 `Set-Cookie` 声明，由客户端或浏览器保存这些数据。

助手函数：

```php
setcookies($request, $keyvalue = [], $expires = 0, $path = '', $domain = '', $secure = false, $http_only = false)
SC($request, $keyvalue = [], $expires = 0, $path = '', $domain = '', $secure = false, $http_only = false)
```

参数说明：

- `$keyvalue`：要写入的 Cookie 键值数组。
- `$expires`：Cookie 过期参数。
- `$path`：Cookie 生效路径。
- `$domain`：Cookie 生效域名。
- `$secure`：是否只通过 HTTPS 发送。
- `$http_only`：是否禁止前端 JavaScript 读取。

### expires 参数说明

`$expires` 在不同运行模式下的语义并不完全一致：

- PHP-FPM 模式下，最终调用的是 PHP 原生 `setcookie()`，这里通常传的是 Unix 时间戳。
- CLI 模式下，框架会把该参数透传到底层响应对象处理；当前更接近 `Max-Age` / 过期秒数语义，实际效果应以目标运行环境验证为准。

因此，如果项目需要同时兼容多种运行模式，不建议依赖默认值或模糊传值，最好显式传入你需要的过期参数，并在目标运行模式下实际验证。

如果只是设置当前会话有效的 Cookie，建议先在目标运行模式下确认浏览器实际表现。

### SameSite 说明

`setcookies()` / `SC()` 当前不提供 `SameSite` 参数。

如果需要设置 `SameSite`，请直接使用响应对象的 `cookie()` 方法：

```php
<?php
namespace app\index\controller;

class index
{
    public function setcookie($req)
    {
        $response = $req->response('ok');
        $response->cookie('name1', 'Tom', 3600, '/', '', false, true, 'Lax');
        return $response;
    }
}
```

一般建议使用 `Request` 函数映射，参看 [助手函数映射](md/request.md?id=助手函数映射)。

```php
<?php
namespace app\index\controller;

class index
{
    public function setcookie($req)
    {
        $req->SC(['name1' => 'Tom', 'name2' => 'Jack']);
        return 'ok';
    }
}
```

### 删除 Cookie

删除 Cookie 的本质是用同名、同路径、同域重新发送一个已过期的 Cookie。

```php
<?php
namespace app\index\controller;

class index
{
    public function delcookie($req)
    {
        $req->SC(['name1' => ''], time() - 3600, '/');
        return 'ok';
    }
}
```

如果设置 Cookie 时额外指定了 `domain`、`path`、`secure` 等参数，删除时应保持一致，否则浏览器可能不会删除原来的 Cookie。

## 获取 Cookie

获取 Cookie 的本质是客户端把已保存的 Cookie 通过 HTTP 请求头提交到服务端，服务端再解析请求头得到相关数据。

请求对象写法：

```php
<?php
namespace app\index\controller;

class index
{
    public function getcookie($req)
    {
        $name = $req->cookie('name1', 'nobody');
        return $name;
    }

    public function getcookies($req)
    {
        return $req->json($req->cookie());
    }
}
```

助手函数：

```php
getcookies($request, $key = null, $default = null)
GC($request, $key = null, $default = null)
```

```php
<?php
namespace app\index\controller;

class index
{
    public function getcookie($req)
    {
        $name = $req->GC('name1', 'nobody');
        return $name;
    }

    public function getcookies($req)
    {
        return $req->json($req->GC());
    }
}
```
