# 中间件

中间件用于在控制器执行前后处理请求和响应，常见场景包括登录校验、权限控制、跨域 Header、限流、日志记录和静态文件访问控制。

中间件类放在 `support/middleware` 目录，命名空间为 `support\middleware`，并提供 `handle($request, callable $next)` 方法。

```php
<?php
namespace support\middleware;

class Example
{
    public function handle($request, callable $next)
    {
        return $next($request);
    }
}
```

`$next($request)` 表示继续执行后续中间件或控制器。如果中间件直接返回响应对象，则后续流程会被终止。

如果中间件类需要成员属性，例如白名单、只读配置或错误消息，请先显式声明属性。PHP 8.2+ 不应依赖未声明的动态属性。

## 配置文件

中间件配置位于 `config/middleware.php`：

```php
<?php
return [
    'middleware' => [
        '' => [
            // 全局中间件
        ],
        'index' => [
            // index 应用中间件
        ],
    ],
    'static_middleware' => [
        // 静态文件中间件
    ],
];
```

## 全局中间件

全局中间件对所有控制器请求生效：

```php
<?php
return [
    'middleware' => [
        '' => [
            support\middleware\AuthCheck::class,
        ],
    ],
];
```

## 应用中间件

应用中间件只对指定应用生效：

```php
<?php
return [
    'middleware' => [
        'api' => [
            support\middleware\ApiAuth::class,
        ],
    ],
];
```

当请求进入 `api` 应用时，会执行 `ApiAuth`。多应用配置请参考 [多应用](md/apps.md)。

## 登录拦截示例

文件 `support/middleware/AuthCheck.php`：

```php
<?php
namespace support\middleware;

class AuthCheck
{
    public function handle($request, callable $next)
    {
        if (!$request->S('userinfo')) {
            return $request->redirect('/user/login');
        }

        return $next($request);
    }
}
```

配置为全局中间件：

```php
<?php
return [
    'middleware' => [
        '' => [
            support\middleware\AuthCheck::class,
        ],
    ],
];
```

访问任意控制器时，如果 Session 中没有 `userinfo`，会重定向到 `/user/login`。

## 按应用、控制器和方法判断

中间件可以通过 `$request->app` 获取当前应用、控制器、方法和控制器类名：

```php
<?php
namespace support\middleware;

class AuthCheck
{
    public function handle($request, callable $next)
    {
        $app = $request->app['app'] ?? '';
        $controller = $request->app['controller'] ?? '';
        $action = $request->app['action'] ?? '';

        if ($app === 'index' && $controller === 'admin' && $action !== 'login') {
            if (!$request->S('userinfo')) {
                return $request->redirect('/admin/login');
            }
        }

        return $next($request);
    }
}
```

这种写法适合少量判断。权限规则较多时，建议拆成独立中间件并配置到对应应用或路由上。

## 中间件内部临时状态

如果中间件内部需要返回校验结果或错误消息，建议通过局部变量或私有方法返回结构化结果，不要依赖 `$this->msg`、`$this->data` 这类未声明动态属性。

```php
<?php
namespace support\middleware;

class AuthCheck
{
    public function handle($request, callable $next)
    {
        $check = $this->checkToken($request);
        if (!$check['ok']) {
            return $request->json([
                'code' => 401,
                'msg' => $check['msg'],
                'data' => [],
            ]);
        }

        $user = $check['data'];
        return $next($request);
    }

    private function checkToken($request): array
    {
        try {
            return [
                'ok' => true,
                'msg' => '',
                'data' => $request->token()->get(),
            ];
        } catch (\Exception $exception) {
            return [
                'ok' => false,
                'msg' => $exception->getMessage(),
                'data' => [],
            ];
        }
    }
}
```

如果确实要把只读配置保存在中间件对象上，也请先用 `protected` 或 `private` 显式声明属性。

## 跨域中间件

文件 `support/middleware/Cors.php`：

```php
<?php
namespace support\middleware;

class Cors
{
    public function handle($request, callable $next)
    {
        if ($request->method() === 'OPTIONS') {
            $response = $request->response('', 204);
        } else {
            $nextResponse = $next($request);
            $response = $nextResponse instanceof \RC\Http\Workerman\Response
                ? $nextResponse
                : $request->response($nextResponse);
        }

        $response->withHeaders([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET,POST,PUT,DELETE,PATCH,OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type,Authorization,X-Requested-With,Accept,Origin',
        ]);

        return $response;
    }
}
```

配置到 `api` 应用：

```php
<?php
return [
    'middleware' => [
        'api' => [
            support\middleware\Cors::class,
        ],
    ],
];
```

生产环境不建议无条件使用 `Access-Control-Allow-Origin: *`。如果接口需要携带 Cookie 或 Token，应按业务白名单校验 Origin。

## 路由中间件

自定义路由支持给单条路由绑定中间件：

```php
use RC\Route;

Route::any('/api/auth', [app\api\controller\index::class, 'auth'])->middleware([
    support\middleware\ApiAuth::class,
]);
```

也可以给路由组绑定中间件：

```php
use RC\Route;

Route::group('/admin', function () {
    Route::get('/index', [app\index\controller\admin::class, 'index']);
    Route::post('/save', [app\index\controller\admin::class, 'save']);
})->middleware([
    support\middleware\AuthCheck::class,
    support\middleware\ThrottlerMiddleware::class,
]);
```

路由中间件只对对应自定义路由生效。自定义路由配置请参考 [路由](md/route.md)。

## 静态文件中间件

静态文件中间件只处理框架内置静态文件分发，通常只在 cli 模式下生效。普通 PHP-FPM 场景中，静态文件多由 Nginx 或 Apache 直接返回，不会进入 PHP 中间件。

文件 `support/middleware/StaticFile.php`：

```php
<?php
namespace support\middleware;

class StaticFile
{
    public function handle($request, callable $next)
    {
        if ($request->path() === '/private.txt') {
            return $request->response('file not allowed', 403);
        }

        return $next($request);
    }
}
```

配置：

```php
<?php
return [
    'static_middleware' => [
        support\middleware\StaticFile::class,
    ],
];
```

静态文件必须位于 `document_root` 内。不要把用户输入直接拼接为文件路径。

## 执行顺序

控制器请求的执行顺序：

```text
全局中间件 -> 应用中间件 -> 路由中间件 -> 控制器
```

静态文件请求的执行顺序：

```text
全局中间件 -> 静态文件中间件 -> 静态文件响应
```

同一组中间件按配置顺序执行：

```php
'middleware' => [
    '' => [
        support\middleware\MiddlewareA::class,
        support\middleware\MiddlewareB::class,
    ],
],
```

执行顺序为：

```text
MiddlewareA before -> MiddlewareB before -> 控制器 -> MiddlewareB after -> MiddlewareA after
```

示例：

```php
public function handle($request, callable $next)
{
    // before
    $response = $next($request);
    // after
    return $response;
}
```

## 与控制器共享数据

框架的 `RC\Request` 现在提供了请求级自定义属性容器，可以在同一条请求链路中由中间件写入、控制器读取：

```php
<?php
namespace support\middleware;

class RequestData
{
    public function handle($request, callable $next)
    {
        $request->user_id = 1001;
        $request->tenant_id = 'tenant-a';
        return $next($request);
    }
}
```

控制器读取：

```php
<?php
namespace app\index\controller;

class index
{
    public function index($req)
    {
        return $req->json([
            'user_id' => $req->user_id,
            'tenant_id' => $req->tenant_id,
        ]);
    }
}
```

这些自定义属性只在当前请求内有效，框架会在请求开始和结束时清空，不会跨请求保留。使用时仍建议遵循下面的边界：

- 当前请求所需的用户身份、权限、租户信息，优先从 token、Session、数据库或缓存重新读取。
- 需要跨请求保留的数据，使用 Session、缓存或数据库，不要挂到中间件对象或 Request 对象上。
- 只在同一请求链路中临时传递的数据，才适合写到 `$request->xxx` 自定义属性中。

## 数组配置写法

如果中间件类没有被自动加载，也可以使用数组配置，让框架从 `support/middleware/{handle}.php` 加载：

```php
<?php
return [
    'middleware' => [
        '' => [
            ['handle' => 'AuthCheck'],
        ],
    ],
];
```

对应文件：

```text
support/middleware/AuthCheck.php
```

对应类：

```php
support\middleware\AuthCheck
```

## 长驻进程注意事项

在 cli 模式下，中间件对象会通过容器复用，不会每个请求都重新创建。

- 不要把 `$request`、用户信息、上传文件、响应对象保存到中间件属性或静态属性中。
- 每个请求都应从 `$request`、Session、数据库或缓存中重新读取当前状态。
- 缓存配置、正则、白名单等只读数据可以保存在属性中，但要先显式声明为 `protected` 或 `private`。
- 写到 `Request` 自定义属性中的值只在当前请求内有效，不应当作跨请求状态容器使用。

## 安全建议

- 鉴权、限流、跨域、管理后台访问控制等通用逻辑优先放到中间件。
- 重定向地址来自用户输入时，只允许站内路径或可信域名。
- CORS 不要在生产环境无条件放开所有 Origin。
- 静态文件中间件只能拦截进入框架的静态文件请求，不能替代 Web 服务器层面的访问控制。
- 中间件返回值应是字符串、数字或响应对象；返回数组时请使用 `$request->json()`。

