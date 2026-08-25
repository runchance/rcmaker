# 路由

rcmaker 支持两类路由：

- 自动寻址路由：根据 URL 自动定位应用、控制器和方法。
- 自定义路由：通过 `config/route.php` 手动定义 URL 规则。

自动寻址由 `config/app.php` 中的 `route` 控制，自定义路由由 `with_custom_route` 控制。

```php
'route' => true,
'with_custom_route' => false,
```

## 自动寻址路由

默认开启自动寻址路由。单应用常见访问格式：

```text
http://127.0.0.1:8680/{控制器}/{方法}
```

例如：

```text
http://127.0.0.1:8680/index/index
```

会调用：

```text
app\index\controller\index::index()
```

多应用时，未绑定域名的应用可以通过应用名前缀访问：

```text
http://127.0.0.1:8680/{应用名}/{控制器}/{方法}
```

多应用、绑定域名、`default_app` 和 query 模式请参考 [多应用](md/apps.md)。

## Query 模式

当 `route=false` 时，自动寻址路由关闭，需要使用 query 参数指定应用、控制器和方法：

```text
http://127.0.0.1:8680/?a=index&c=index&m=index
```

参数含义：

| 参数 | 说明 |
| --- | --- |
| `a` | 应用名。 |
| `c` | 控制器名。 |
| `m` | 方法名。 |

## 自定义路由

自定义路由默认关闭。需要在 `config/app.php` 开启：

```php
'with_custom_route' => true,
```

然后在 `config/route.php` 中定义规则：

```php
<?php
use RC\Route;

Route::get('/hello', function ($req) {
    return 'hello route';
});
```

访问：

```text
http://127.0.0.1:8680/hello
```

会返回：

```text
hello route
```

## 路由匹配顺序

开启 `with_custom_route=true` 后，请求会先尝试匹配 `config/route.php` 中的自定义路由。

```text
自定义路由匹配成功 -> 执行自定义路由
自定义路由未匹配 -> 继续自动控制器寻址
控制器寻址也失败 -> 执行 fallback 或返回 404
```

因此，`Route::fallback()` 是最终兜底处理，不会阻止正常的自动寻址路由。

## 控制器路由

```php
<?php
use RC\Route;

Route::any('/testcontroller', [app\index\controller\index::class, 'test']);
```

控制器示例：

```php
<?php
namespace app\index\controller;

class index
{
    public function test($req)
    {
        return $req->response('hello controller route');
    }
}
```

访问：

```text
http://127.0.0.1:8680/testcontroller
```

会调用：

```text
app\index\controller\index::test()
```

## 闭包路由

```php
<?php
use RC\Route;

Route::any('/ping', function ($req) {
    return $req->json([
        'code' => 0,
        'msg' => 'pong',
    ]);
});
```

闭包路由也会经过路由中间件和全局中间件。

## 参数路由

```php
<?php
use RC\Route;

Route::get('/user/{id}', [app\index\controller\user::class, 'profile']);
```

控制器方法从第二个参数开始接收路由参数：

```php
<?php
namespace app\index\controller;

class user
{
    public function profile($req, $id)
    {
        return $req->json([
            'id' => $id,
            'same' => $req->get('id'),
        ]);
    }
}
```

路由参数也会写入 GET 参数，所以可以通过 `$req->get('id')` 获取。

## 参数约束

使用 FastRoute 参数约束可以限制参数格式：

```php
Route::get('/user/{id:\d+}', [app\index\controller\user::class, 'profile']);
```

该规则只匹配数字 ID：

```text
/user/100     匹配
/user/tom     不匹配
```

更多参数写法：

```php
// 只匹配单段路径，例如 /user/tom，不匹配 /user/tom/avatar
Route::get('/user/{name}', [app\index\controller\user::class, 'profile']);

// 匹配多段路径，例如 /page/a/b/c
Route::any('/page/{path:.+}', [app\index\controller\page::class, 'show']);

// 可选参数，匹配 /user 和 /user/tom
Route::any('/user[/{name}]', [app\index\controller\user::class, 'profile']);
```

FastRoute 的可选片段必须放在路由末尾，下面这种写法无效：

```php
Route::any('/user[/{id:\d+}]/{name}', [app\index\controller\user::class, 'profile']);
```

## 请求方法

```php
Route::get('/users', $callback);
Route::post('/users', $callback);
Route::put('/users/{id:\d+}', $callback);
Route::patch('/users/{id:\d+}', $callback);
Route::delete('/users/{id:\d+}', $callback);
Route::head('/users', $callback);
Route::options('/users', $callback);
```

匹配多个请求方法：

```php
Route::add(['GET', 'POST'], '/submit', $callback);
```

匹配常用全部方法：

```php
Route::any('/submit', $callback);
```

`Route::any()` 包含：`GET`、`POST`、`PUT`、`DELETE`、`PATCH`、`HEAD`、`OPTIONS`。

## 路由分组

路由分组可以为多条路由添加共同前缀：

```php
<?php
use RC\Route;

Route::group('/user', function () {
    Route::post('/login', [app\index\controller\user::class, 'login']);
    Route::post('/register', [app\index\controller\user::class, 'register']);
    Route::get('/profile/{id:\d+}', [app\index\controller\user::class, 'profile']);
});
```

等效于：

```php
Route::post('/user/login', [app\index\controller\user::class, 'login']);
Route::post('/user/register', [app\index\controller\user::class, 'register']);
Route::get('/user/profile/{id:\d+}', [app\index\controller\user::class, 'profile']);
```

## 路由中间件

可以给单条路由设置中间件：

```php
Route::any('/api/auth', [app\api\controller\index::class, 'auth'])->middleware([
    support\middleware\AuthCheck::class,
]);
```

也可以给路由分组设置中间件：

```php
Route::group('/admin', function () {
    Route::get('/index', [app\index\controller\admin::class, 'index']);
    Route::post('/save', [app\index\controller\admin::class, 'save']);
})->middleware([
    support\middleware\AuthCheck::class,
    support\middleware\ThrottlerMiddleware::class,
]);
```

路由中间件会和全局中间件、应用中间件一起进入执行链，适合放鉴权、限流、参数校验等与具体路由强相关的逻辑。完整执行顺序请参考 [中间件](md/middleware.md)。

中间件写法请参考 [中间件](md/middleware.md)。

## fallback 兜底路由

当自定义路由没有命中，并且自动控制器寻址也失败时，可以使用 fallback 统一处理：

```php
<?php
use RC\Route;

Route::fallback(function ($req) {
    return $req->json([
        'code' => 404,
        'msg' => 'not found',
    ]);
});
```

也可以重定向：

```php
Route::fallback(function ($req) {
    return $req->redirect('/index');
});
```

如果没有 fallback，框架会返回 `404`。如果 `public/404.html` 存在，会优先返回该 HTML 内容。

## 静态文件路由

可以把某个 URL 映射到固定静态文件：

```php
<?php
use RC\Route;

Route::get('/logo', ['__static__', public_path().'/logo.png']);
```

访问：

```text
http://127.0.0.1:8680/logo
```

会返回：

```text
public/logo.png
```

静态文件必须位于配置的 `document_root` 内。框架会使用真实路径校验边界，避免通过 `../` 访问公开目录之外的文件。

不要把未经校验的用户输入直接拼接成静态文件路径。需要按用户输入选择文件时，建议使用白名单映射：

```php
$files = [
    'logo' => public_path().'/logo.png',
    'favicon' => public_path().'/favicon.ico',
];
```

## API 速查

```php
Route::group($path, $callbacks);

Route::get($path, $callback);
Route::post($path, $callback);
Route::put($path, $callback);
Route::patch($path, $callback);
Route::delete($path, $callback);
Route::head($path, $callback);
Route::options($path, $callback);

Route::add($method, $path, $callback);
Route::any($path, $callback);

Route::fallback($callback);
```

路由和路由分组返回的对象都支持：

```php
->middleware($middleware);
```

## 安全建议

- 开放 API 建议优先使用明确的请求方法，例如 `GET`、`POST`，不要所有接口都用 `Route::any()`。
- 路由参数尽量加正则约束，例如 `{id:\d+}`。
- 登录态、权限、限流和跨域逻辑放到中间件中统一处理。
- 静态路由只映射固定文件或白名单文件，不要直接拼接用户输入。
- fallback 不要泄露异常详情，生产环境建议返回统一 JSON 或统一错误页。

