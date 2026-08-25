# 限流组件

rcmaker 内置了 `RC\Helper\Throttler`，用于对接口或动作做访问频率限制。当前实现采用令牌桶算法，不是固定窗口计数。

常见用途：

- 限制接口被高频爬取
- 防止登录、发送验证码等动作被恶意刷请求
- 对不同路由或动作施加不同频率上限

## 前置条件

`Throttler` 依赖缓存存储桶状态，因此在实际使用前需要保证 `cache()` 可用。通常意味着你已经载入了缓存引导类，例如：

```ini
[bootstrap]
load[] = RC\Helper\Cache\Raw
```

## 核心参数

调用签名：

```php
$throttler->check($key, $capacity, $seconds, $cost)
```

参数说明：

参数 | 类型 | 说明
---|---|---
$key | string | 当前桶的唯一标识，例如 IP、用户 ID、路由名等
$capacity | int | 桶的最大令牌数
$seconds | int | 桶从空到满需要的秒数
$cost | int | 当前动作消耗的令牌数

几个关键点：

- `capacity` 越大，短时间内允许的突发请求越多。
- `seconds` 越大，令牌恢复越慢。
- `cost` 可以用来区分动作成本，例如普通查询消耗 `1`，短信发送消耗 `5`。
- 当前实现要求 `capacity > 0`、`seconds > 0`、`cost > 0`，且 `cost` 不能大于 `capacity`。

当 `check()` 返回 `false` 时，可以通过 `getTokenTime()` 拿到“至少还要等待多少秒才可能再次通过”。

## 使用方法1：路由中间件

当前项目可以参考 `support/middleware/ThrottlerMiddleware.php` 这类中间件文件来组织限流逻辑。

在路由里直接挂载：

```php
Route::any('/test/api/{name}', [app\index\controller\test::class, 'api'])->middleware([
    support\middleware\ThrottlerMiddleware::class,
]);
```

控制器示例：

```php
<?php
namespace app\index\controller;

class test
{
    public function api($req)
    {
        return $req->get('name');
    }
}
```

中间件示例：

```php
<?php
namespace support\middleware;

use RC\Helper\Throttler;
use RC\Container;

class ThrottlerMiddleware
{
    public function handle($request, callable $next)
    {
        static $throttler;
        $throttler = $throttler ?? Container::make(Throttler::class, [cache()]);

        $key = $request->ip();
        $capacity = 60;
        $seconds = 60;
        $cost = 1;

        if ($throttler->check($key, $capacity, $seconds, $cost) === false) {
            return $request->response(
                json_encode([
                    'success' => false,
                    'msg' => '请求此时太频繁',
                    'retry_after' => $throttler->getTokenTime(),
                ], JSON_UNESCAPED_UNICODE),
                429,
                ['Content-Type' => 'application/json']
            );
        }

        return $next($request);
    }
}
```

达到限流标准后会返回 `429`。

## 使用方法2：中间件配置

也可以在 `config/middleware.php` 中全局或按应用挂载中间件：

```php
<?php
return [
    'middleware' => [
        '' => [
        ],
        'index' => [
            support\middleware\ThrottlerMiddleware::class,
        ],
    ],
];
```

这样会对整个 `index` 应用生效。

## 只限制特定动作

如果只想限制固定 action，可以在中间件里组合 action 名和 IP 作为桶 key：

```php
<?php
namespace support\middleware;

use RC\Helper\Throttler;
use RC\Container;

class ThrottlerMiddleware
{
    public function handle($request, callable $next)
    {
        static $throttler;
        $throttler = $throttler ?? Container::make(Throttler::class, [cache()]);

        [, , $action] = array_values($request->app);
        $throttlerActions = ['login', 'api'];
        $key = $action . ':' . $request->ip();
        $capacity = 60;
        $seconds = 60;
        $cost = 1;

        if (in_array($action, $throttlerActions, true)
            && $throttler->check($key, $capacity, $seconds, $cost) === false) {
            return $request->response(
                json_encode([
                    'success' => false,
                    'msg' => '请求此时太频繁',
                    'retry_after' => $throttler->getTokenTime(),
                ], JSON_UNESCAPED_UNICODE),
                429,
                ['Content-Type' => 'application/json']
            );
        }

        return $next($request);
    }
}
```

此时仅对 `login` 和 `api` 进行限流。

## 常见 key 设计

不同业务可以使用不同的 key 组合：

- 仅按 IP：`$request->ip()`
- 按动作 + IP：`$action . ':' . $request->ip()`
- 按用户 ID：`'user:' . $userId`
- 按手机号：`'sms:' . $mobile`

key 设计越精细，限流粒度越可控。

## 清理限流状态

如果你需要手动清空某个桶，可以调用：

```php
$throttler->remove($key);
```

这会删除当前 key 对应的令牌数和时间状态。
