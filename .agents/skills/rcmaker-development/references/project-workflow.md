# HTTP 项目开发流程

## 项目目录

| 路径 | 职责 |
| --- | --- |
| `apps/<app>/controller` | 控制器和 HTTP 编排 |
| `apps/<app>/model` | 应用模型 |
| `config/app.php` | 应用、域名、路由、静态根目录和进程绑定 |
| `config/route.php` | 自定义路由 |
| `config/middleware.php` | 全局、应用和静态中间件 |
| `support/middleware` | 中间件实现 |
| `support/service` | 可复用业务服务 |
| `view` | 模板文件 |
| `public` | 公共静态资源 |
| `runtime` | 日志、缓存和生成数据，不放业务源码 |

先阅读目标应用相邻代码，保持其命名、响应 envelope、异常和服务分层。rcmaker 默认命名空间前缀是 `app`，不是 `apps`。

## 自定义能力的目录约束

框架确实没有对应能力时，可以新增业务实现，但不能改变 rcmaker 的目录组织方式：

| 新增内容 | 应放位置 |
| --- | --- |
| 应用控制器、模型或应用专属代码 | `apps/<app>/` 下与相邻代码一致的目录 |
| 跨应用服务、进程、中间件、队列消费者、定时任务、启动类 | `support/` 下对应职责目录 |
| 配置 | `config/` |
| 模板、前端构建产物、静态文件 | `view/`、`public/` |
| 自动化测试、夹具、诊断程序 | `tests/` |
| 维护和交付脚本 | `scripts/` 或项目已有 `tools/` |
| 临时工作文件 | `.tmp/`，任务结束前清理 |

项目根目录不是通用源码目录。除框架既有入口、Composer/项目清单、AI 工具入口以及用户明确要求的仓库级文件外，不在根目录新增文件。尤其禁止生成根级 `test.php`、`demo.php`、`service.php`、下载结果、数据库文件或调试输出。若现有目录不能承载需求，应先说明原因并沿用项目命名方式创建职责目录，而不是退回根目录。

## 选择路由模式

rcmaker 支持自动寻址和自定义路由：

- 自动寻址由 `config/app.php` 的 `route` 控制。
- 自定义路由由 `with_custom_route` 控制，规则在 `config/route.php`。
- 启用自定义路由后，匹配顺序是：自定义路由 -> 自动寻址 -> fallback/404。

自动寻址适合后台和快速 CRUD；公开 API 推荐使用明确的自定义路由和 HTTP 方法。

```php
<?php

use RC\Route;

Route::get('/users/{id:\d+}', [app\api\controller\user::class, 'detail']);
Route::post('/users', [app\api\controller\user::class, 'create']);

Route::group('/admin', function () {
    Route::get('/users', [app\admin\controller\user::class, 'index']);
})->middleware([
    support\middleware\AdminAuth::class,
]);
```

支持 `get/post/put/patch/delete/head/options/add/any/group/fallback`。不要把所有公开 API 都写成 `Route::any()`。路由参数从控制器第二个参数开始传入，也会写入 GET 参数。

## 控制器模板

```php
<?php

namespace app\api\controller;

use RC\Request;

class user
{
    public function detail(Request $req, $id)
    {
        $id = validator()->check($id, [
            'rule' => 'pint',
            'name' => '用户ID',
        ]);

        $user = $req->AF([
            'type' => 'get',
            'table' => 'users',
            'id' => $id,
            'index' => 'id',
            'name' => '用户',
        ])->handle();

        if (!$user) {
            $response = $req->json(['code' => 404, 'msg' => '用户不存在']);
            $response->withStatus(404);
            return $response;
        }

        return $req->json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $user,
        ]);
    }
}
```

要求：

- Request 是第一个参数；路由参数随后。
- 输入使用 `$req` 与 Validator。
- 数据库按 AutoForm -> SDB -> 复杂 SQL 才 DB 的层级选择；标准 CRUD、列表和分页使用 AutoForm。
- JSON 使用 `$req->json()`，不要手工编码。
- 业务错误遵循项目已有 code 和 HTTP 状态约定。
- 控制器对象在 CLI 下可能复用，不在属性中保存请求临时状态，也不依赖构造函数做“每请求初始化”。

## 请求 API

```php
$query = $req->get();
$keyword = $req->get('keyword', '');
$post = $req->post();
$name = $req->post('name', '');
$rawBody = $req->rawBody();
$authorization = $req->header('authorization', '');
$cookie = $req->cookie('theme', 'light');
$file = $req->file('upload');
$host = $req->host(true);
$method = $req->method();
$path = $req->path();
$ip = $req->ip();
```

使用 `$req->acceptJson()`、`expectsJson()`、`isAjax()` 等判断请求偏好。业务代码原则上不读取 PHP 超全局变量，也不直接依赖底层 Workerman/Swoole 请求。

上传文件必须验证大小、MIME、扩展名和文件名，在当前请求内移动到允许目录。CLI 和 FPM 的上传大小限制来源不同，见 `.agents/doc/md/request.md`。

## 响应 API

```php
return $req->json($data);
return $req->response($body, 200, ['X-App' => 'rcmaker']);
return $req->xml($xml);
return $req->jsonp($data, 'callback');
return $req->redirect('/login', 302);
return $req->V('user/detail', ['user' => $user]);
return $req->response()->file($path);
return $req->D($path, $downloadName);
```

设置 JSON HTTP 状态：

```php
$response = $req->json(['code' => 422, 'msg' => '参数错误']);
$response->withStatus(422);
return $response;
```

需要 Header 时使用响应对象 `withHeader()` / `withHeaders()`。需要 Cookie 时使用响应对象 `cookie()` 或 `$req->SC()`。不要直接 `header()`、`echo`、`setcookie()`、`readfile()`。

## 中间件

中间件标准结构：

```php
namespace support\middleware;

class Auth
{
    public function handle($request, callable $next)
    {
        $userId = $request->token()->get('user_id');
        $request->user_id = $userId;

        $nextResponse = $next($request);
        $response = $nextResponse instanceof \RC\Http\Workerman\Response
            ? $nextResponse
            : $request->response($nextResponse);
        $response->withHeader('X-App', 'rcmaker');
        return $response;
    }
}
```

执行顺序：

```text
全局中间件 -> 应用中间件 -> 路由中间件 -> 控制器
```

同组中间件按洋葱模型执行。静态请求是全局中间件 -> 静态中间件 -> 静态响应。

Request 自定义属性适合在同一次请求内共享用户、租户或追踪信息，框架会在请求开始/结束清理。跨请求状态必须进入 Session、Cache、Redis 或数据库。中间件对象会复用，不把 Request、Response 或用户信息存入对象属性/静态属性。

## 视图

视图配置在 `config/view.php`，支持 Raw 以及项目安装的 Smarty、Think、Blade、Twig 等引擎：

```php
return $req->V('user/detail', [
    'user' => $user,
]);
```

使用项目已配置的模板引擎，不在控制器手工 `include`。模板输出默认按引擎规则处理，用户内容要正确转义。模板引擎实例在 CLI 下可能复用，修改配置或依赖后重启服务。

## 异常和错误

- 通用异常处理配置在 `config/exception.php`，实现放在 `support/exception`。
- 业务可预期错误按项目 envelope 返回；系统异常让统一异常处理接管或捕获后转换为安全响应。
- 生产环境不返回堆栈、凭据、SQL、绝对路径或上游敏感响应。
- Token 鉴权失败优先捕获 `RC\Exception\AuthException`。
- 不用大范围 `catch (\Throwable)` 把所有错误伪装成成功；若捕获，保持正确 HTTP 状态并记录必要上下文。

## 配置

- 环境差异放 `.env`，通过 `rcEnv($name, $default)` 和项目配置读取。
- 环境值通常起始为字符串，布尔值和整数显式转换。
- 不默认生产密钥，不把端口、路径和凭据硬编码进业务代码。
- 不假设其他框架的 `config()` helper 存在；先搜索当前项目和 rcmaker 源码。
- 配置、路由、bootstrap、中间件或进程变化后重启相关 Worker。

## HTTP 功能检查

1. 路由方法、参数约束和应用归属正确。
2. 输入只通过 Request 获取并已验证。
3. 使用框架数据层和组件。
4. JSON、文件、下载、重定向使用 Response 能力。
5. 中间件没有跨请求可变状态。
6. HTTP 状态、Header、CORS、Cookie 和错误信息正确。
7. 运行 `php -l`、相关测试，并通过真实引擎请求验证。

详细文档：`.agents/doc/md/apps.md`、`controller.md`、`route.md`、`request.md`、`response.md`、`middleware.md`、`view.md`、`exception.md`。
