# 异常处理

rcmaker 提供统一的异常处理入口，支持全局异常处理和按应用定制异常处理类。


## 配置文件

`./config/exception.php`

```php
<?php
return [
	'' => support\exception\Handler::class, //全局异常处理
];
?>
```

默认定义了全局异常处理，处理类通常为 `support\exception\Handler::class`。该类一般继承框架内置的 `RC\Exception\ExceptionHandler`。

你也可以为每个应用定制不同的异常处理类，例如：


```php
<?php
return [
	'' => support\exception\Handler::class, //全局异常处理
	'index' => support\exception\IndexHandler::class //应用异常处理
];
?>
```

这样当用户访问 `index` 应用时，如果请求处理过程中抛出异常，则由 `support\exception\IndexHandler::class` 接管。

如果你使用的是 `support/exception/{handle}.php` 这种文件约定方式，也可以写成：

```php
<?php
return [
	'' => support\exception\Handler::class,
	'index' => ['handle' => 'IndexHandler'],
];
?>
```

## 自定义异常处理类要求

自定义异常处理类不是“实现一个接口”即可，而是需要满足当前框架的调用约定。

最稳妥的方式是直接继承 `RC\Exception\ExceptionHandler`：

```php
<?php
namespace support\exception;

use RC\Exception\ExceptionHandler;

class Handler extends ExceptionHandler
{
}
?>
```

如果不继承该基类，则至少需要保证：

- 构造方法能够接受框架注入的 `logger`、`debug`、`error_msg` 参数。
- 提供 `report(\Throwable $exception)` 方法。
- 提供 `render(\Throwable $exception, $request): array` 方法。

其中 `render()` 必须返回三元数组：

```php
[$statusCode, $headers, $body]
```

否则异常处理流程本身可能再次抛错。

## report 和 render

- `report()` 用于记录异常、上报监控或执行其他异常副作用。
- `render()` 用于生成最终响应内容。

如果自定义异常类只是简单继承 `RC\Exception\ExceptionHandler`，则默认的日志记录和响应渲染逻辑仍然可用。

## 默认处理方式

- 如果 `app.debug=true`，框架会返回详细异常信息。
- 如果 `app.debug=false`，普通响应会返回 `app.error_msg:时间戳`，JSON 响应也会返回统一错误结构。
- 默认情况下异常仍会记录到日志。

JSON 请求下，默认响应结构类似：

```json
{
    "code": 500,
    "msg": "error message"
}
```

调试模式下还会附带异常堆栈字符串。

## 默认日志位置

默认日志保存在 `./runtime/logs/` 目录。

默认文件名格式：

- cli 模式：`rcmaker[CLI]_error_年-月-日.log`
- PHP-FPM 模式：`rcmaker[fpm]_error_年-月-日.log`

如果项目接入了自定义 logger，`report()` 会优先走 logger，不再使用默认文件追加方式。
