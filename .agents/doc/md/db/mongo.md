# MongoDB

rcmaker 的 `thinkORM` 和 `laravelORM` 数据库支持层可以连接 MongoDB。使用前需要同时确认 PHP 扩展、数据库支持类和 `.env` 配置都已经准备好。

## 前置条件

1. 当前运行环境已经安装 PHP `mongodb` 扩展。
2. `.env` 的 `[bootstrap]` 中已经载入 `RC\Helper\Db\Think` 或 `RC\Helper\Db\Laravel`。
3. `.env` 的 `[mongodb]` 中已经填写连接参数。

检查扩展：

```shell
php -m | grep mongodb
```

> CLI 模式检查 CLI 使用的 PHP；FPM 模式检查 PHP-FPM 使用的 PHP。两者可能不是同一个 PHP 环境。

MongoDB 扩展可参考 [PECL mongodb](http://pecl.php.net/package/mongoDB)。

## Laravel ORM 依赖

如果使用 `laravelORM`，需要安装：

```shell
composer require illuminate/database
composer require jenssegers/mongodb
composer require illuminate/pagination
```

如果只使用 `thinkORM`，不需要安装这些 Laravel 组件，但仍然需要 PHP `mongodb` 扩展。

## MongoDB 配置

载入数据库支持类：

```ini
[bootstrap]
load[] = RC\Helper\Db\Think
```

或：

```ini
[bootstrap]
load[] = RC\Helper\Db\Laravel
```

配置 MongoDB 连接：

```ini
[mongodb]
host = 127.0.0.1
port = 27017
database = test
username = ''
password = ''
```

## 示例

```php
<?php
namespace app\index\controller;

class test
{
    public function laravel_mongo($req)
    {
        $db = DB('laravel', 'mongodb');
        $db::collection('test')->insert([1, 2, 3]);
        return $req->json($db::collection('test')->get());
    }

    public function think_mongo($req)
    {
        $db = DB('think', 'mongodb');
        $db::table('test')->insert([
            'name' => 'tom',
            'age' => 23,
            'email' => 'tom@mail.com',
        ]);
        return $req->json($db::table('test')->select());
    }
}
```
