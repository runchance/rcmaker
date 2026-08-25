# 多应用说明

rcmaker 默认采用多应用目录结构，业务代码统一放在 `apps` 目录下。每个应用拥有独立目录，例如 `apps/index`、`apps/api`，控制器默认位于应用目录的 `controller` 子目录中。

目录结构说明可参考 [目录结构](md/directory.md)。

## 基础目录

典型多应用目录如下：

```text
apps
├── index
│   └── controller
│       └── index.php
└── api
  └── controller
    └── index.php
```

对应控制器命名空间示例：

```php
namespace app\index\controller;

class index
{
  public function index($req)
  {
    return 'hello rcmaker';
  }
}
```

## 配置入口

多应用配置位于 `config/app.php`。其中 `count`、`route`、`with_custom_route`、`index` 可以作为全局配置，也可以在单个应用中单独覆盖。

示例：

```php
'default_app' => 'index',
'app' => [
  'index' => [
    'domains' => [],
  ],
  'api' => [
	'bind_process' => 'RC_APP_API',
    'count' => false,
    'route' => true,
    'with_custom_route' => false,
    'index' => ['index', 'api'],
    'domains' => ['api.test.com'],
  ],
]
```

常用配置说明：

| 配置 | 作用 |
| --- | --- |
| `default_app` | 多应用模式下的默认应用。URL 第一段不是应用名时，会尝试进入该应用。 |
| `route` | 是否开启自动寻址路由。 |
| `with_custom_route` | 是否启用 `config/route.php` 中的自定义路由。 |
| `index` | 默认控制器和默认方法，例如 `['index', 'index']`。 |
| `domains` | 应用绑定域名。绑定后，该应用只能通过绑定域名访问。 |
| `bind_process` | 绑定 `config/process.php` 中 `type => app` 的进程组；未设置时只属于主 APP 进程组。 |
| `static_only` | 只允许静态文件响应；文件未命中时直接返回 404，不再执行动态路由。 |

## 绑定 APP 进程组

> [!TIP]
> 这里介绍最基本的绑定方式。单应用独立端口、同组多应用、纯静态进程、Nginx 分流、预热策略和故障排查请参考 [应用进程组](md/app-process.md)。

应用可以通过 `bind_process` 绑定独立 APP 进程组：

```php
// config/app.php
'app' => [
  'index' => [], // 未设置 bind_process，由主 APP 进程组处理
  'api' => [
    'bind_process' => 'RC_APP_API',
  ],
]
```

```php
// config/process.php
'RC_APP_API' => [
  'type' => 'app',
  'listen' => 'http://0.0.0.0:8682',
  'count' => 4,
  'default_app' => 'api',
],
```

绑定后，`api` 的自动路由、绑定域名、静态目录和应用中间件只在 `RC_APP_API` 中生效，主 APP 端口不会回退处理该应用。指定的进程组未配置或没有启动时，应用保持不可用。

一个进程组可以绑定多个应用。`default_app` 用于决定该独立端口根路径进入哪个应用；未设置时优先沿用全局默认应用，如果不属于当前进程组，则使用该组配置中的第一个应用。

> [!IMPORTANT]
> `bind_process` 必须与 `config/process.php` 的数组键名完全一致，并且目标进程必须设置 `type => app`。

## 单应用模式

如果不配置 `app` 项，系统会认为当前只有一个应用，默认应用名为 `index`。此时开启寻址路由后，访问地址中不需要写应用名。

```text
http://127.0.0.1:8680/{控制器}/{方法}
```

例如访问根路径：

```text
http://127.0.0.1:8680/
```

等效于访问：

```text
http://127.0.0.1:8680/index/index
```

实际会调用：

```text
apps/index/controller/index.php
app\index\controller\index::index()
```

## 多应用寻址

配置多个应用后，如果应用没有绑定域名，并且 `route=true`，URL 第一段会作为应用名。

```text
http://127.0.0.1:8680/{应用名}/{控制器}/{方法}
```

例如：

```text
http://127.0.0.1:8680/index/index/index
```

表示访问：

```text
apps/index/controller/index.php
app\index\controller\index::index()
```

如果访问：

```text
http://127.0.0.1:8680/index
```

这里的 `index` 表示应用名，不是控制器名。控制器和方法会继续使用配置中的默认 `index`。

如果 URL 第一段不是已配置的应用名，则会进入 `default_app`。例如：

```php
'default_app' => 'index',
'app' => [
  'index' => [],
  'api' => [],
]
```

访问：

```text
http://127.0.0.1:8680/user/profile
```

会被解析为：

```text
apps/index/controller/user.php
app\index\controller\user::profile()
```

如果要访问 `api` 应用，则仍然使用应用名前缀：

```text
http://127.0.0.1:8680/api/index/index
```

## 绑定域名

应用可以通过 `domains` 绑定域名。绑定后，该应用只能通过绑定域名访问，不再通过普通域名加应用名访问。

```php
'api' => [
  'route' => true,
  'index' => ['index', 'api'],
  'domains' => ['api.test.com'],
]
```

访问：

```text
http://api.test.com:8680/
```

实际会调用：

```text
apps/api/controller/index.php
app\api\controller\index::api()
```

绑定域名后，可以省略 URL 中的应用名：

```text
http://api.test.com:8680/{控制器}/{方法}
```

域名匹配会忽略大小写，并自动去掉常见的端口部分。例如 `API.TEST.COM:8680` 会按 `api.test.com` 匹配。

## 静态目录

如果某个绑定域名不走控制器，而是主要提供纯静态页面，请参考 [静态目录](md/static-directory.md)。

## Query 模式

当 `route=false` 时，默认寻址路由关闭，需要通过 query 参数指定应用、控制器和方法。

```text
http://127.0.0.1:8680/?a={应用名}&c={控制器}&m={方法}
```

例如：

```text
http://127.0.0.1:8680/?a=api&c=index&m=api
```

## 配置规则速查

| 场景 | 访问方式 |
| --- | --- |
| 未配置 `app`，且 `route=true` | `/{控制器}/{方法}` |
| 配置多个应用，应用未绑定域名，且 `route=true` | `/{应用名}/{控制器}/{方法}` |
| 配置多个应用，URL 第一段不是应用名 | `/{控制器}/{方法}` 进入 `default_app` |
| 应用绑定域名，且 `route=true` | 使用绑定域名访问 `/{控制器}/{方法}` |
| `route=false` | `/?a={应用名}&c={控制器}&m={方法}` |

## 自定义路径和命名空间

`config/app.php` 支持自定义应用目录和命名空间前缀：

```php
'apps_path' => '/absolute/path/to/apps',
'app_name' => 'app',
```

- `apps_path`：应用目录绝对路径，默认是项目根目录下的 `apps`。
- `app_name`：应用命名空间前缀，默认是 `app`。

## 多级应用

多级应用适合版本化 API 或模块拆分场景。例如：

```
apps
└── api                       
    ├── controller            
    │    └── index.php
    └── v2
        ├── controller
        └── model
       

```

可以在 `config/app.php` 中配置多级应用名。应用名可以使用反斜杠或斜杠，框架会统一转换。

```php
'app' => [
  'api\\v2' => [
    'route' => true,
    'index' => ['index', 'index'],
  ],
]
```

访问：

```text
http://127.0.0.1:8680/api/v2/index/test
```

实际会调用：

```text
apps/api/v2/controller/index.php
app\api\v2\controller\index::test()
```

多级应用匹配采用最长前缀优先。如果同时配置 `api` 和 `api\v2`，访问 `/api/v2/...` 会优先匹配 `api\v2`。

## 配合自定义路由

多级应用可以直接通过寻址路由访问。如果需要更短或更自由的 URL，可以继续配合自定义路由。

第一步：确保 `.env` 文件或 `config/app.php` 中开启 `with_custom_route`。

第二步：配置 `config/route.php`。

```php
<?php
use RC\Route;
Route::any('/api-test', [app\api\v2\controller\index::class,'test']);
```

第三步：创建 `apps/api/v2/controller/index.php`。

```php
<?php
namespace app\api\v2\controller;

class index{
	public function test($req){
	    return 'api test';
	}
}
?>
```

访问测试：

```text
http://localhost:8680/api-test
```

返回：

```text
api test
```

更多路由设置请参考 [路由](md/route.md)。



