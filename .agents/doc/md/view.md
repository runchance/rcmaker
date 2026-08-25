# 视图与模板

rcmaker 支持多种模板引擎，默认使用原生 PHP 模板 `Raw`。内置支持：

| 引擎 | 类名 | 是否内置 | 常用模板文件 |
| --- | --- | --- | --- |
| Raw | `RC\Helper\View\Raw` | 是 | `view/{app}/{template}.html` |
| Smarty | `RC\Helper\View\Smarty` | 是 | `view/{app}/{template}.html` |
| ThinkPHP | `RC\Helper\View\ThinkPHP` | 是 | `view/{app}/{template}.html` |
| Blade | `RC\Helper\View\Blade` | 否 | `view/{app}/{template}.blade.php` |
| Twig | `RC\Helper\View\Twig` | 否 | `view/{app}/{template}.html` |

`Blade` 和 `Twig` 需要额外安装：

```bash
composer require jenssegers/blade
composer require twig/twig
```

## 配置视图引擎

配置文件：`config/view.php`

```php
<?php
use RC\Helper\View\Raw;
use RC\Helper\View\ThinkPHP;
use RC\Helper\View\Smarty;
use RC\Helper\View\Twig;
use RC\Helper\View\Blade;

return [
    'handler' => Raw::class,
    'suffix' => 'html',
    'options' => [
        // 不同模板引擎支持的配置不同，请参考对应引擎文档。
    ],
];
```

配置说明：

| 配置 | 说明 |
| --- | --- |
| `handler` | 当前使用的模板引擎类。 |
| `suffix` | Raw、Smarty、ThinkPHP、Twig 默认模板后缀。 |
| `options` | 传递给模板引擎的配置。 |

视图根目录默认为项目根目录下的 `view`，即 `view_path()` 返回的目录。

## 控制器中使用视图

控制器：`apps/index/controller/index.php`

```php
<?php
namespace app\index\controller;

class index
{
    public function view($req)
    {
        return $req->view('index', [
            'name' => 'rcmaker',
        ]);
    }
}
```

也可以使用简写：

```php
return $req->V('index', ['name' => 'rcmaker']);
```

模板文件：`view/index/index.html`

```php
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>RCmaker</title>
</head>
<body>
hello <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
</body>
</html>
```

访问：

```text
http://127.0.0.1:8680/index/view
```

会渲染：

```text
view/index/index.html
```

`$req->view('index')` 中的 `index` 是模板名，不需要带文件后缀。

## Raw 模板

Raw 是默认模板引擎，直接使用 PHP 原生语法。

配置：

```php
<?php
use RC\Helper\View\Raw;

return [
    'handler' => Raw::class,
    'suffix' => 'html',
    'options' => [],
];
```

模板：`view/index/index.html`

```php
hello <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
```

Raw 不会自动转义输出，输出用户输入时应使用 `htmlspecialchars()`。

## Smarty 模板

配置：

```php
<?php
use RC\Helper\View\Smarty;

return [
    'handler' => Smarty::class,
    'suffix' => 'html',
    'options' => [
        // 'cache_dir' => runtime_path().'/views/smarty/cache',
        // 'compile_dir' => runtime_path().'/views/smarty/compile',
    ],
];
```

模板：`view/index/index.html`

```smarty
hello {$name}
```

## ThinkPHP 模板

配置：

```php
<?php
use RC\Helper\View\ThinkPHP;

return [
    'handler' => ThinkPHP::class,
    'suffix' => 'html',
    'options' => [
        // 'cache_path' => runtime_path().'/views/think/',
    ],
];
```

模板：`view/index/index.html`

```html
hello {$name}
```

## Blade 模板

安装：

```bash
composer require jenssegers/blade
```

配置：

```php
<?php
use RC\Helper\View\Blade;

return [
    'handler' => Blade::class,
    'suffix' => 'html',
    'options' => [],
];
```

模板：`view/index/index.blade.php`

```blade
hello {{ $name }}
```

Blade 使用 `.blade.php` 作为模板后缀。控制器中仍然使用模板名：

```php
return $req->V('index', ['name' => 'rcmaker']);
```

## Twig 模板

安装：

```bash
composer require twig/twig
```

配置：

```php
<?php
use RC\Helper\View\Twig;

return [
    'handler' => Twig::class,
    'suffix' => 'html',
    'options' => [
        // 'cache' => runtime_path().'/views/twig',
        // 'debug' => false,
    ],
];
```

模板：`view/index/index.html`

```twig
hello {{ name }}
```

## 公共模板赋值

除了在 `$req->view()` 第二个参数中传值，也可以使用 `RC\Helper\View::assign()` 设置公共变量：

```php
<?php
namespace app\index\controller;

use RC\Helper\View;

class index
{
    public function view($req)
    {
        View::assign([
            'site_name' => 'RCmaker',
            'version' => '1.0',
        ]);

        View::assign('name', 'rcmaker');

        return $req->V('index');
    }
}
```

`View::assign()` 适合设置当前请求内的公共模板变量。框架会在渲染结束或渲染异常后清理这些变量，避免 Workerman/Swoole 长驻进程中串到下一个请求。

如果大量控制器都需要相同变量，可以在中间件中统一赋值：

```php
<?php
namespace support\middleware;

use RC\Helper\View;

class ViewData
{
    public function handle($request, callable $next)
    {
        View::assign('request_id', $request->id());
        return $next($request);
    }
}
```

## 自定义路由中的视图

自动控制器寻址时，框架会根据当前应用渲染对应目录，例如 `index` 应用会使用：

```text
view/index/{template}.html
```

自定义路由闭包没有明确控制器应用时，不建议直接依赖 `$req->view()` 自动推断应用。需要在自定义路由中渲染视图时，建议转到控制器方法，或确认当前请求已经有正确的应用上下文。

## 异常处理

模板文件不存在、模板语法错误、依赖缺失等错误会抛出异常，由框架自身异常处理流程接管。生产环境应关闭 `debug`，通过 `config/exception.php` 和 `config/app.php` 中的 `error_msg` 控制对外错误信息。

视图层不直接输出错误页，也不吞掉异常。

## 性能建议

建议开启 OPcache：

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=80000
opcache.revalidate_freq=3
```

JIT 是否开启取决于业务负载，不建议在没有压测的情况下直接作为默认配置。

在 cli 模式下，模板引擎实例会被复用。修改 `config/view.php`、模板引擎 options 或 Composer 依赖后，应重启服务。

## 安全建议

- 模板名不要直接来自用户输入，例如不要直接使用 `$req->get('tpl')` 作为模板名。
- Raw 模板输出用户输入时必须手动转义。
- 公开页面推荐使用默认转义能力更明确的 Blade 或 Twig。
- 模板异常由框架异常处理器接管，生产环境不要向用户暴露绝对路径和堆栈信息。
- 不要在模板变量中传递敏感对象、数据库连接或包含密钥的配置数组。
