# 内置组件使用指南

使用组件前先看对应配置文件和官方文档。下面提供选择规则和基础模板，不替代组件完整文档。

## 组件优先规则

1. 输入进入业务前使用 Validator。
2. 标准 CRUD 优先评估 AutoForm；数据访问细节见 [data-access.md](data-access.md)。
3. 跨请求或跨进程状态使用 Cache、Redis、Session 或数据库，不使用 PHP 静态数组。
4. 身份令牌使用 Token；图形/短信验证码分别使用 Captcha/SMS。
5. 耗时或可重试任务投递 Queue，不阻塞 HTTP 请求。
6. 外部 HTTP 使用 `curl()`，统一设置超时并处理错误。
7. Excel、PDF、二维码、拼音、邮件等使用框架封装，不直接拼底层库初始化流程。

## Validator

助手：`validator()`，简写 `VD()`。多字段使用 `input()`：

```php
$data = validator()->input($req->post(), [
    'id' => [
        'rule' => 'pint',
        'name' => 'ID',
    ],
    'email' => [
        'rule' => 'email',
        'name' => '邮箱',
        'len' => [1, 120],
        'options' => ['filter' => 'trim'],
    ],
    'nickname' => [
        'rule' => 'string',
        'name' => '昵称',
        'len' => [1, 50],
        'required' => false,
    ],
]);

return $req->json(['code' => 0, 'data' => $data]);
```

单字段使用 `check()`：

```php
$id = validator()->check($req->get('id'), [
    'rule' => 'pint',
    'name' => 'ID',
]);
```

支持规则和文件校验以 `official/doc/md/module/validation.md` 为准。不要假设 Laravel 风格的字符串规则语法；rcmaker 当前验证规则采用数组配置。

## Cache

配置：`config/cache.php`。助手：`cache()`。

```php
$cache = cache();
$cache->set('user:'.$id, $user, 3600);
$user = $cache->get('user:'.$id);
$user = $cache->remember('user:'.$id, function () use ($id) {
    return DB()->get('users', '*', ['id' => $id]);
});
$cache->delete('user:'.$id);
```

常用能力包括 `get`、`set`、`delete`、`pull`、`clear`、`remember`、`inc`、`dec`、`push` 和标签。完整行为见 `official/doc/md/module/cache.md`。

缓存设计必须说明 key 维度、TTL 和失效时机。用户、租户、权限或语言影响结果时必须进入 key。`clear()` 属于高影响操作，不能用于普通业务失效。

## Redis

配置：`config/redis.php`，并在 bootstrap 中加载当前 Redis 引擎。助手：`redis()`，简写 `RD()`。

```php
$redis = redis();
$redis->setex('user:'.$id, 3600, $payload);
$payload = $redis->get('user:'.$id);
$redis->del('user:'.$id);
```

支持 raw/mix 引擎、多连接、事务、pipeline、订阅等，准确接口见 `official/doc/md/db/redis.md`。不要每个请求 `new Redis()` 并 connect；不要在请求热路径使用 `keys('*')`。

Redis 是跨 Worker 共享状态；普通 PHP 数组和静态属性不是。分布式锁、计数器、限流、Session、Token 状态要考虑原子性和过期时间。

## Session 与 Cookie

Session 配置：`config/session.php`。优先使用对象：

```php
$session = $req->session();
$session->set('user_id', $userId);
$userId = $session->get('user_id');
$session->put(['name' => 'tom', 'role' => 'admin']);
$session->delete('user_id');
$all = $session->all();
```

简写映射 `$req->S()`：数组表示设置，字符串表示读取，`null` 表示读取全部。完整行为见 `official/doc/md/session.md`。

读取 Cookie：

```php
$theme = $req->cookie('theme', 'light');
```

设置 Cookie：

```php
$req->SC(['theme' => 'dark']);
return $req->response('ok');
```

或使用响应对象 `cookie()`。安全 Cookie 要根据部署设置 Secure、HttpOnly、SameSite、Domain、Path 和有效期，见 `official/doc/md/cookie.md`。不得使用原生 `session_start()`、`$_SESSION` 或 `setcookie()` 绕过运行时适配。

## Token 鉴权

配置：`config/token.php`。Request 映射：`$req->token($guard)` / `$req->T($guard)`。

生成：

```php
$result = $req->token()->set([
    'key' => 'user_'.$userId,
    'user_id' => $userId,
]);
return $req->json($result);
```

验证：

```php
use RC\Exception\AuthException;

try {
    $userId = $req->token()->get('user_id');
} catch (AuthException $exception) {
    $response = $req->json(['code' => 401, 'msg' => $exception->getMessage()]);
    $response->withStatus(401);
    return $response;
}
```

刷新使用 `reSet()`；不同 guard 使用 `$req->token('admin')`。Token 来源、算法、证书、刷新和单设备登录见 `official/doc/md/module/token.md` 与 `official/doc/md/scripts/tokenKey.md`。不要自行实现 JWT 签发、刷新和互斥登录状态。

## Captcha 与 SMS

图形验证码配置：`config/captcha.php`。

```php
public function image($req)
{
    return $req->C('login_'.$req->ip());
}

public function verify($req)
{
    $ok = $req->CC('login_'.$req->ip(), $req->post('captcha'));
    return $req->json(['code' => $ok ? 0 : 400, 'msg' => $ok ? 'ok' : 'captcha fail']);
}
```

校验值为空可能抛异常。存储方式、连接和闭包参数见 `official/doc/md/module/captcha.md`。

短信验证码配置：`config/sms.php`。

```php
$sms = $req->sms()->mobile($mobile)->scene('login');
$code = $sms->create();
$ok = $sms->code($inputCode)->check();
```

场景要区分登录、注册、找回密码等；发送前配合 Throttler。验证码生成、自动删除、有效期和短信服务商接入见 `official/doc/md/module/sms.md`。

## Queue

配置：`config/queue.php`。投递数组，无需手动序列化：

```php
queue()->send('send-mail', [
    'user_id' => $userId,
    'template' => 'welcome',
]);

queue()->send('send-mail', $payload, 60); // 延迟 60 秒
```

消费者放在 `config/queue.php` 指定目录，通常为 `support/queue`：

```php
namespace support\queue;

class SendMail
{
    public $queue = 'send-mail';
    public $connection = 'default';
    public $worker_id = 0;

    public function handle($data)
    {
        // 根据标识重新读取权威数据并执行幂等操作。
    }
}
```

消费者正常结束才确认成功；抛出异常进入重试/失败流程。消息只携带可序列化标识和必要参数，不携带 Request、连接或服务容器。幂等、重试、多连接和消费进程配置见 `official/doc/md/queue.md`。

## Throttler

限流器通常放在中间件。使用框架缓存作为共享状态：

```php
use RC\Container;
use RC\Helper\Throttler;

class ApiThrottle
{
    public function handle($request, callable $next)
    {
        static $throttler;
        $throttler = $throttler ?? Container::make(Throttler::class, [cache()]);

        $key = 'api:'.$request->ip();
        if (!$throttler->check($key, 60, 60, 1)) {
            $response = $request->json([
                'code' => 429,
                'msg' => '请求过于频繁',
                'retry_after' => $throttler->getTokenTime(),
            ]);
            $response->withStatus(429);
            return $response;
        }

        return $next($request);
    }
}
```

`capacity`、`seconds`、`cost` 必须大于 0，且 cost 不大于 capacity。key 应按动作、用户、手机号或 IP 设计，见 `official/doc/md/module/throttler.md`。

## Curl HTTP 客户端

`curl()` 返回单请求实例，`curl(true)` 返回并发客户端；它们不是 Request 映射。

```php
$client = curl();
$client->setTimeout(10);
$client->setHeader('Accept', 'application/json');
$client->setHeader('Content-Type', 'application/json');
$client->post($url, $payload);

if ($client->error) {
    return $req->json([
        'code' => 502,
        'msg' => $client->errorMessage,
        'status' => $client->httpStatusCode,
    ]);
}

return $req->json(['code' => 0, 'data' => $client->response]);
```

每次调用 `curl()` 都是新实例，不会串联上次 Header、Cookie 或回调。始终设置合理超时，处理 HTTP 状态和错误，不在响应中泄露上游凭据。并发和下载见 `official/doc/md/module/curl.md`。

## Mailer

配置：`config/mailer.php`。助手：`mailer($connection)` / `ML()`。

```php
$mail = mailer();
$sent = $mail->from('system@example.com', 'System')
    ->to($email)
    ->subject('Welcome')
    ->body('Hello')
    ->send();

if (!$sent) {
    throw new \RuntimeException($mail->e());
}
```

`mailer()` 按连接缓存 helper，但当前 `send()` 后会重置本次消息状态。大量或可重试邮件应投递 Queue。附件、HTML、抄送和错误处理见 `official/doc/md/module/mailer.md`。

## Paginator

数据查询优先使用 SDB `paginate()`。手工数据源可构造 `RC\Helper\Paginator`，其 JSON 输出包含 total、per_page、current_page、last_page、has_more、data。API 直接交给 `$req->json($paginator)`，不要手写每套分页元数据。见 `official/doc/md/module/paginator.md`。

## Excel、PDF、二维码与拼音

- Excel：`xlsx()` / `X()`，读写接口见 `module/excel.md`。
- PDF：`$req->pdf()` / `$req->P()`，输出模式见 `module/pdf.md`。
- 二维码：`$req->qrcode($text, ...)` / `$req->Q()`，见 `module/qrcode.md`。
- 拼音：`pinyin()` / `PY()`，见 `module/pinyin.md`。

生成文件应写入明确的可写目录，并通过 `$req->D()` 或响应对象下载。校验文件路径，清理临时文件，避免把用户输入直接拼成路径或文件名。

## StopWatch

使用 `stopwatch()` 或组件文档中的静态接口进行定向分析。只在诊断需要时启用，不在生产热路径长期输出明细。见 `official/doc/md/module/stopWatch.md`。

## 组件完成检查

- 已读取组件配置和对应官方文档。
- 没有安装与现有组件重复的 Composer 包。
- 没有自行实现 JSON HTTP、数据库连接、缓存、Session、Token、验证码、分页或队列协议。
- 组件实例的复用符合文档；没有跨请求残留收件人、用户、Header 或回调状态。
- 所有网络、缓存、队列和文件操作都有超时、TTL、边界或失败处理。
- Swoole 协程模式已核实底层依赖是否协程安全。
