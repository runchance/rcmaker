# 支持库及用法

rcmaker 支持 `medoo`、`thinkORM`、`laravelORM` 作为数据库独立组件使用。

在使用前，请确保 `./.env` 的 `[bootstrap]` 下加载了对应的数据库支持类，例如：

```text
load[] = RC\Helper\Db\Medoo
load[] = RC\Helper\Db\Think
load[] = RC\Helper\Db\Laravel
```

如果没有加载对应支持类，调用 `DB()` 或 `database()` 时会直接报错。

如果你启用了 `RC\Helper\Db\Laravel`，但没有安装 `illuminate/database`，启动阶段也会直接报错，而不是静默跳过。

开启 `RC\Helper\Db\Laravel` 需要先安装 `illuminate/database`：

```bash
composer require illuminate/database
```

如果你还要在 `laravelORM` 下使用 MongoDB，还需要安装：

```bash
composer require jenssegers/mongodb
```

并且 `./.env` 的 `[db]` 下要正确配置对应数据库。


如何在应用中使用并切换支持库，使用助手函数 `database()` 或者 `DB()` 示例如下。

### medoo

```php
<?php
namespace app\index\controller;

class index{
	public function index($req)
    {
        $db = DB(); //默认不填参数则使用 db.default_frame 当前配置的数据库框架
        $user = $db->get('user','*',['id'=>1]);
        return $req->json($user);
    }
}
?>
```

更多 `medoo` 的用法请参考 [medoo官方文档](https://medoo.in/doc) 或者 [medoo中文文档](https://medoo.lvtao.net/1.2/doc.php)。

### thinkORM

```php
<?php
namespace app\index\controller;

class index{
	public function index($req)
    {
        $db = DB('think'); // 使用 thinkORM
        $user = $db::table('user')->where('id',1)->find();
        return $req->json($user);
    }
}
?>
```

更多 `thinkORM` 的用法请参考 [thinkORM官方文档](https://www.kancloud.cn/manual/think-orm/1257998)。

### laravelORM

```php
<?php
namespace app\index\controller;

class index{
	public function index($req)
    {
        $db = DB('laravel'); // 使用 laravelORM
        $user = $db::table('user')->where('id',1)->first();
        return $req->json($user);
    }
}
?>
```

这里的 `DB('laravel')` 属于原生数据库支持层用法，文档统一按 `$db::...` 方式调用。

更多 `laravelORM` 的用法请参考 [laravelORM官方文档](https://laravel.com/docs/8.x/database) 或者 [laravelORM中文文档](https://learnku.com/docs/laravel/8.x/database)。


## 如何切不同的数据库

`medoo` 支持 `mysql`,`sqlite`,`pgsql`,`sqlsrv`,`oracle`,`sybase`

`thinkORM` 支持 `mysql`,`sqlite`,`pgsql`,`sqlsrv`,`mongodb`,`oracle`

`LaravelORM` 支持 `mysql`,`sqlite`,`pgsql`,`sqlsrv`,`mongodb`,`oracle`

`DB()` 的第一个参数用于选择数据库框架，第二个参数用于选择数据库驱动。不填写第二个参数时，默认使用 `db.default` 配置的数据库类型。

如果同时加载了多个数据库框架，建议把 `db.default` 设为这些框架都支持的数据库类型。例如 `mongodb` 不适用于 `medoo`，`sybase` 不适用于 `thinkORM` 和 `laravelORM`。

示例如下。

修改 `./.env`，新增 sqlite 的配置：

```TEXT
[sqlite]
;路径为你的实际路径
database = '/home/ubuntu/rcmaker/sqlite.db'
username = ''
password = ''
charset = utf8
options[] = 
prefix = ''
```

```php
<?php
namespace app\index\controller;

class index{
    public function index($req)
    {
        $db = DB('medoo','sqlite'); // 使用 medoo 连接 sqlite
        $user = $db->get('user','*',['id'=>1]);
        return $req->json($user);
    }
}
?>
```

如果你要在 `medoo` 下使用 `sybase`，当前默认配置文件也已经提供了 `sybase` 配置模板；只需在 `db.driver.sybase` 或 `./.env` 中填写对应连接参数即可。
