# Session 管理

## Session 配置文件

Session 配置文件为 `./config/session.php`。

```php
<?php
use RC\Http\FileSessionHandler;
use RC\Http\RedisSessionHandler;
use RC\Http\RedisClusterSessionHandler;

return [
    'handler' => FileSessionHandler::class,
    'config' => [
        'file' => [
            'save_path' => runtime_path() . '/sessions',
        ],
        'redis' => [
            'host' => '192.168.128.132',
            'port' => 6379,
            'auth' => '',
            'timeout' => 2,
            'database' => '',
            'prefix' => 'redis_session_',
        ],
        'redis_cluster' => [
            'host' => ['127.0.0.1:9000', '127.0.0.1:9001', '127.0.0.1:9002', '127.0.0.1:9003', '127.0.0.1:9004', '127.0.0.1:9005'],
            'timeout' => 2,
            'auth' => '',
            'prefix' => 'redis_session_',
        ],
    ],
    'session_name' => 'PHPSID',
];
```

可以设置三种 Session 管理方式：

- 文件形式：`FileSessionHandler::class`
- Redis 形式：`RedisSessionHandler::class`
- Redis 集群形式：`RedisClusterSessionHandler::class`

## Session 操作示例

```php
<?php
namespace app\index\controller;

class index
{
    public function session_set($req)
    {
        $id = $req->get('id');
        $session = $req->session();
        if ($id) {
            $session->set('id', $id);
        }
        return $session->get('id');
    }

    public function session_set_array($req)
    {
        $session = $req->session();
        $session->put(['name' => 'tom', 'age' => 12]);
        return 'hello ' . $session->get('name') . ' age ' . $session->get('age');
    }

    public function session_get_all($req)
    {
        return $req->json($req->session()->all());
    }

    public function session_del_one($req)
    {
        $session = $req->session();
        $session->delete('id');
        // 或者：$session->forget('name');
        return $req->json($session->all());
    }

    public function session_del_array($req)
    {
        $session = $req->session();
        $session->forget(['id', 'name', 'age']);
        return $req->json($session->all());
    }

    public function session_del_return($req)
    {
        return $req->session()->pull('name');
    }

    public function session_has($req)
    {
        $session = $req->session();
        $hasName = $session->has('name');

        // 如果 name => null 也要返回 true，请使用：
        // $hasName = $session->exists('name');

        return $hasName ? 'exists' : 'not exists';
    }
}
```

## 助手函数

`session($key = null, $default = null)` 或 `S($key = null, $default = null)`。

一般建议使用 `Request` 函数映射，参看 [助手函数映射](md/request.md?id=助手函数映射)。

```php
<?php
namespace app\index\controller;

class index
{
    public function session_set($req)
    {
        $name = $req->get('name');
        if ($name) {
            $req->S(['name' => $name]);
        }
        return $req->S('name', 'tom');
    }

    public function session_get_all($req)
    {
        return $req->json($req->S());
    }
}
```

> 当第一个参数为数组时表示设置 Session；当第一个参数为字符串时表示获取 Session 值；如果设置了第二个参数 `$default`，获取不到时会返回 `$default`。

> 如果第一个参数为 `null`，默认获取所有 Session 值。
