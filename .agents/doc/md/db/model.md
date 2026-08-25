# 模型

rcmaker 支持 `thinkORM` 和 `laravelORM(Eloquent ORM)`。每个数据库表都可以对应一个模型类，通过模型完成查询、写入、关联等操作。

在使用前，请先通过 `./.env` 的 `[bootstrap]` 配置，或 `config/bootstrap.php` 载入 `RC\Helper\Db\Think` 或者 `RC\Helper\Db\Laravel`。

```
[bootstrap]
load[] = RC\Helper\Db\Think
```

```
[bootstrap]
load[] = RC\Helper\Db\Laravel
```

如果使用 `RC\Helper\Db\Laravel`，需要先安装 `illuminate/database`。

```bash
composer require illuminate/database
```

如果 `laravelORM` 要连接 `mongodb`，还需要额外安装 `jenssegers/mongodb`。

```bash
composer require jenssegers/mongodb
```

并且需要在 `./.env` 的 `[db]` 下正确配置相应数据库。

> Eloquent ORM 要支持模型观察者，还需要额外安装 `illuminate/events`

```bash
composer require illuminate/events
```

> 只载入 bootstrap 还不够。模型首次使用前，仍然要先执行一次 `DB('think')` 或 `DB('laravel')`，这样才会把当前 ORM 的连接和模型运行环境初始化好。`DB()` 属于原生数据库支持层，用法可参考 [md/db/frame.md](md/db/frame.md)

`thinkORM` 使用请参考 [thinkORM](https://www.kancloud.cn/manual/think-orm/1258043)，`Eloquent ORM` 使用请参考 [Eloquent ORM 官网](https://laravel.com/docs/8.x/eloquent) 或 [Eloquent ORM 中文](https://learnku.com/docs/laravel/8.x/eloquent/9406)。

## `thinkORM` 模型示例

1、新建模型文件 `./apps/index/model/user.php`

```php
<?php
namespace app\index\model;
use RC\Model\Think as ThinkModel;

class user extends ThinkModel
{
    protected $table = 'users';
    protected $pk = 'id';
    public $timestamps = false;

    public function log()
    {
        return $this->hasOne('app\index\model\log', 'user_id');
    }
}
?>
```

> 如果当前模型使用 `thinkORM`，就继承 `RC\Model\Think`；如果使用 `Eloquent ORM`，就继承 `RC\Model\Laravel`。上面这个示例采用的是 `thinkORM`，所以只保留 `thinkORM` 相关属性。

2、新建模型文件 `./apps/index/model/log.php`

```php
<?php
namespace app\index\model;
use RC\Model\Think as ThinkModel;

class log extends ThinkModel
{
    protected $table = 'log';
    protected $pk = 'id';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo('app\index\model\user', 'user_id', 'id');
    }
}
?>
```

> 至此就完成了模型 `user` 和 `log` 的关联。

如果要改成 `Eloquent ORM`，则 `./apps/index/model/log.php` 和 `./apps/index/model/user.php` 需要同时改为继承 `LaravelModel`，并把主键属性改为 `protected $primaryKey = 'id';`。

```php
<?php
namespace app\index\model;
use RC\Model\Laravel as LaravelModel;

class user extends LaravelModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
?>
```

3、控制器中调用模型 `./apps/index/controller/test.php`

```php
<?php
namespace app\index\controller;
use app\index\model\user;

class test{
	public function index($req)
    {
        DB('think'); // 初始化 thinkORM
        $userinfo = user::find(1); // 获取 ID 为 1 的 user 表数据
        $log = user::find(1)->log; // 获取关联 log 表的数据
        return $req->json($log);
    }
}
?>
```

你也可以用助手函数调用模型。

```php
<?php
namespace app\index\controller;

class test{
	public function index($req)
    {
		DB('think'); // 初始化 thinkORM
		$User = $req->M('user');
        $userinfo = $User::find(1); // 获取 ID 为 1 的 user 表数据
        $log = $User::find(1)->log; // 获取关联 log 表的数据
        return $req->json($log);
    }
}
?>
```

如果当前模型继承的是 `RC\Model\Laravel`，这里就要把初始化改成：

```php
DB('laravel');
```

> 注意：rcmaker 启动后默认不会直接初始化模型所需的数据库连接。使用模型前需要先初始化对应 ORM；如果控制器里用到模型的地方比较多，可以通过中间件统一初始化，参考 [md/middleware.md](md/middleware.md)





