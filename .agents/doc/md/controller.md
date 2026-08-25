# 控制器

控制器负责接收 `RC\Request` 请求对象，执行业务逻辑，并返回字符串、数字或响应对象。数组、对象等结构化数据建议使用 `$req->json()` 返回。

## 创建控制器

新建文件 `apps/index/controller/test.php`：

```php
<?php
namespace app\index\controller;

class test
{
    public function index($req)
    {
        return $req->response('hello test');
    }

    public function hello($req)
    {
        return $req->response('hello rcmaker');
    }
}
```

访问：

```text
http://127.0.0.1:8680/test
```

会调用：

```text
app\index\controller\test::index()
```

访问：

```text
http://127.0.0.1:8680/test/hello
```

会调用：

```text
app\index\controller\test::hello()
```

当 URL 没有指定方法时，框架会使用默认方法 `index`。

## 命名规则

| 项目 | 规则 | 示例 |
| --- | --- | --- |
| 应用名 | 字母、数字、下划线，支持多级应用命名空间 | `index`、`api\v2` |
| 控制器名 | 字母或下划线开头，后接字母、数字、下划线 | `test`、`user_api` |
| 方法名 | 字母或下划线开头，后接字母、数字、下划线 | `index`、`profile` |
| 控制器命名空间 | `app\{应用名}\controller` | `app\index\controller` |

框架会校验应用名、控制器名和方法名，不合法时直接返回 `404`，避免通过路径构造加载任意类或文件。

## 默认入口

默认入口位于 `config/app.php`：

```php
'index' => ['index', 'index'],
'default_app' => 'index',
```

含义：

```text
默认控制器：index
默认方法：index
默认应用：index
```

`index` 也兼容字符串写法：

```php
'index' => 'index/index',
```

多应用寻址、绑定域名和 `default_app` 的完整说明请参考 [多应用](md/apps.md)。

## 请求对象

控制器方法的第一个参数固定为请求对象：

```php
public function profile($req)
{
    $id = $req->get('id', 0);
    return $req->json(['id' => $id]);
}
```

常用请求方法：

| 方法 | 说明 |
| --- | --- |
| `$req->get('name', '')` | 获取 GET 参数。 |
| `$req->post('name', '')` | 获取 POST 参数。 |
| `$req->header('name', '')` | 获取 Header。 |
| `$req->cookie('name', '')` | 获取 Cookie。 |
| `$req->file('upload')` | 获取上传文件。 |
| `$req->ip()` | 获取客户端 IP。 |
| `$req->method()` | 获取请求方法。 |

更多请求接口请参考 [请求对象](md/request.md)。

## 返回值

控制器可以返回字符串、数字或响应对象：

```php
return 'hello';
return 123;
return $req->response('ok');
return $req->json(['code' => 0, 'msg' => 'ok']);
```

不建议直接返回数组或普通对象。需要返回结构化数据时，使用 `$req->json()`：

```php
public function info($req)
{
    return $req->json([
        'code' => 0,
        'data' => [
            'name' => 'rcmaker',
        ],
    ]);
}
```

常用响应助手：

| 方法 | 说明 |
| --- | --- |
| `$req->response($body, $status, $headers)` | 普通响应。 |
| `$req->json($data)` | JSON 响应。 |
| `$req->xml($xml)` | XML 响应。 |
| `$req->jsonp($data, $callback)` | JSONP 响应。 |
| `$req->redirect($url, $status)` | 重定向。 |
| `$req->V($template, $vars)` | 渲染视图。 |
| `$req->D($file, $name)` | 下载文件。 |

更多响应接口请参考 [响应对象](md/response.md)。

## 路由参数

使用自定义路由时，路由参数会传入控制器方法，也会写入 GET 参数：

```php
use RC\Route;

Route::get('/user/{id:\d+}', [app\index\controller\user::class, 'profile']);
```

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

更多路由写法请参考 [路由](md/route.md)。

## 控制器钩子

在 cli 模式下，控制器对象可能常驻内存。不要把每次请求的临时状态长期保存在控制器属性里，也不要依赖 `__construct()` 处理每个请求的初始化逻辑。

如果需要在控制器方法前后执行逻辑，可以通过中间件实现 `beforeAction()` 和 `afterAction()`。

## 创建 Hook 中间件

文件 `support/middleware/Hook.php`：

```php
<?php
namespace support\middleware;

use RC\Container;
use RC\Http\Workerman\Response;

class Hook
{
    public function handle($request, callable $next)
    {
        if (!empty($request->app['controller']) && !empty($request->app['class'])) {
            if ($request->app['action'] === 'beforeAction' || $request->app['action'] === 'afterAction') {
                return $request->response('<h1>404 Not Found</h1>', 404);
            }

            $controller = Container::get($request->app['class']);

            if (method_exists($controller, 'beforeAction')) {
                $before_response = call_user_func([$controller, 'beforeAction'], $request);
                if ($before_response instanceof Response) {
                    return $before_response;
                }
            }

            $response = $next($request);

            if (method_exists($controller, 'afterAction')) {
                $after_response = call_user_func([$controller, 'afterAction'], $request, $response);
                if ($after_response instanceof Response) {
                    return $after_response;
                }
            }

            return $response;
        }

        return $next($request);
    }
}
```

## 启用 Hook 中间件

在 `config/middleware.php` 中添加：

```php
<?php
return [
    'middleware' => [
        'index' => [
            support\middleware\Hook::class,
        ],
    ],
];
```

## 控制器中使用钩子

```php
<?php
namespace app\index\controller;

class hook
{
    public function index($req)
    {
        return $req->response('hook', 200, [
            'X-Hook' => 'ok',
        ]);
    }

    public function beforeAction($req)
    {
        if (!$req->cookie('user_id')) {
            return $req->redirect('/login');
        }
    }

    public function afterAction($req, $response)
    {
        if ($response instanceof \RC\Http\Workerman\Response) {
            $response->withHeader('X-After-Action', '1');
        }
        return $response;
    }
}
```

`beforeAction()` 返回响应对象时，会终止原控制器方法执行。没有返回值时，请求继续进入原控制器方法。

`afterAction()` 会收到原控制器返回值。可以修改响应对象，也可以返回新的响应对象替代原响应。

## 安全建议

- 控制器、方法和应用名只使用字母、数字、下划线，避免依赖特殊字符。
- 用户输入不要直接拼接为类名、方法名、文件路径或跳转地址。
- 文件下载和文件响应应限定在业务允许目录内，避免路径穿越。
- 登录态、权限、跨域等通用逻辑优先放到中间件中，不要散落在每个控制器方法里。
- CLI 常驻模式下不要把请求对象、用户信息、上传文件等请求级数据保存到控制器静态属性或长期属性中。

## 静态文件与控制器

框架会在控制器寻址前尝试处理静态文件。静态文件必须位于配置的 `document_root` 内，框架会使用真实路径校验边界，避免通过 `../` 访问公开目录之外的文件。

静态文件配置请参考 `config/worker.php`、`config/swoole.php` 和 [静态文件](md/static.md)。如果需要为应用单独绑定静态目录，请参考 [静态目录](md/static-directory.md)。

