# 数据库配置

## 数据库配置文件

数据库配置文件为 `./config/db.php`，具体连接参数通常通过 `./.env` 覆盖。

> [!IMPORTANT]
> Windows 开发可以使用 SQLite，Linux 生产可以切换到 MySQL 或 PostgreSQL。连接和驱动只在 `.env`、`config/db.php` 与 bootstrap 中配置，业务代码不要写死 DSN、驱动名或连接地址。查询优先使用 AutoForm、SDB 等框架能力；确需厂商专用 SQL 时应隔离差异，并在实际生产数据库上验证锁、事务、排序、大小写与类型转换行为。

```php
<?php
return [
  //默认框架
   'default_frame' => rcEnv('db.default_frame','medoo'),
  //默认数据库驱动
  'default' => rcEnv('db.default','mysql'),
  //数据库驱动列表
  'driver' => [
     'mysql' => [
        'host' => rcEnv('mysql.host','127.0.0.1'),
        'port' => rcEnv('mysql.port','3306'),
        'database' => rcEnv('mysql.database','test'),
        'username' => rcEnv('mysql.username',''),
        'password' => rcEnv('mysql.password',''),
        'charset' => rcEnv('mysql.charset',''),
        'prefix' => rcEnv('mysql.prefix',''),
        'options' => rcEnv('mysql.options',[]),
        
        
     ],
     'sqlite'=>[
        'database' => rcEnv('sqlite.database',BASE_PATH.'/RCMAKER.db'),
        'prefix' => rcEnv('sqlite.prefix',''),
        'username' => rcEnv('sqlite.username',''),
        'password'=>rcEnv('sqlite.password',''),
        'options' => rcEnv('sqlite.options',[])
     ],
     'pgsql'=>[
        'host' => rcEnv('pgsql.host','127.0.0.1'),
        'port' => rcEnv('pgsql.port','5432'),
        'database' => rcEnv('pgsql.database',''),
        'username' => rcEnv('pgsql.username',''),
        'password' => rcEnv('pgsql.password',''),
        'prefix' => rcEnv('pgsql.prefix',''),
        'options' => rcEnv('pgsql.options',[])
     ],
      'mongodb' => [
        'host' => rcEnv('mongodb.host','127.0.0.1'),
        'port' =>  rcEnv('mongodb.port','27017'),
        'database' => rcEnv('mongodb.database','test'),
        'username' => rcEnv('mongodb.username',''),
        'password' => rcEnv('mongodb.password',''),
        'prefix' => rcEnv('mongodb.prefix',''),
        'options' => rcEnv('mongodb.options',[])
      ],
     'sqlsrv'=>[
        'host' => rcEnv('sqlsrv.host','localhost'),
        'port' => rcEnv('sqlsrv.port','1433'),
        'database' => rcEnv('sqlsrv.database',''),
        'username' => rcEnv('sqlsrv.username',''),
        'password' => rcEnv('sqlsrv.password',''),
        'prefix' => rcEnv('sqlsrv.prefix',''),
        'options' => rcEnv('sqlsrv.options',[])
        
     ],
     'oracle'=>[
        'host' => rcEnv('oracle.host','localhost'),
        'port' => rcEnv('oracle.port','1521'),
        'database' => rcEnv('oracle.database',''),
        'username' => rcEnv('oracle.username',''),
        'password' => rcEnv('oracle.password',''),
        'charset' => rcEnv('oracle.charset','utf8'),
        'prefix' => rcEnv('oracle.prefix',''),
        'options' => rcEnv('oracle.options',[])
        
     ],
     'sybase'=>[
        'host' => rcEnv('sybase.host','localhost'),
        'port' => rcEnv('sybase.port','5000'),
        'database' => rcEnv('sybase.database',''),
        'username' => rcEnv('sybase.username',''),
        'password' => rcEnv('sybase.password',''),
        'charset' => rcEnv('sybase.charset','utf8'),
        'prefix' => rcEnv('sybase.prefix',''),
        'options' => rcEnv('sybase.options',[])
     ],
  ]
];
?>
```


默认数据库框架应与实际加载的数据库支持类保持一致。当前可直接使用的数据库框架是 `medoo`、`think`、`laravel`，默认值建议使用 `medoo`。

如果只修改了 `db.default_frame`，但没有在 `bootstrap.load[]` 或进程 `bootstrap` 中加载对应数据库支持类，调用 `DB()` 或 `SDB()` 时仍然会报错。

如果同时加载多个数据库框架，`db.default` 也要注意兼容范围。例如：

- `mongodb` 不能作为 `medoo` 的默认驱动
- `sybase` 不能作为 `thinkORM` 或 `laravelORM` 的默认驱动

例如修改 `mysql` 的配置，编辑 `./.env` 文件如下：

```text
[db]

;默认框架, 支持 medoo,think,laravel
default_frame = medoo
;默认数据库驱动
default = mysql

[mysql]
host = localhost
port = 3306
database = test
username = test
password = 123456
charset = utf8
options[] = 
prefix = ''
```


## 选择需要的数据库框架并启动载入

例如需要 `medoo` 和 `thinkORM` 支持库，修改 `./.env` 文件如下：

```text
[bootstrap]

load[] = RC\Helper\Db\Medoo
load[] = RC\Helper\Db\Think
```

> 这样 rcmaker 就会在进程启动时自动载入 `medoo` 和 `thinkORM` 支持库。当然你也可以载入 `Laravel` 支持库，但需要手动安装 Laravel 相关组件。

> `composer require illuminate/database`、`composer require jenssegers/mongodb`、`composer require illuminate/pagination`

```text
[bootstrap]
load[] = RC\Helper\Db\Laravel
```


至此数据库配置完成。如果需要配置 `sqlite`、`sqlsrv` 或其他数据库，请参照 `mysql` 的配置方式修改 `./.env`。

>注意：数据库配置项 `options[]` 可以根据你选择的数据库框架灵活定制。例如你选择 `medoo` 框架，需要配置 `command` 选项时可以这样写。

```text
[mysql]
host = localhost
port = 3306
database = test
username = test
password = 123456
charset = utf8
options[command] = 'SET SQL_MODE=ANSI_QUOTE'
prefix = ''
```


## 自定义进程载入数据库框架

如果你在自定义进程里使用数据库组件，也需要在对应进程的 `bootstrap` 中显式加载数据库支持类。例如需要 `medoo` 和 `thinkORM` 支持库时，可修改 `./config/process.php`：
```php
return [
	'RC_HTTP'  => [
        'handler'  => support\process\Http::class,
        'reusePort' => true,
        'listen' => 'http://0.0.0.0:8681',
        'ssl'=>false,
        //上下文传递
        'context' => [
            'ssl'=>[
                'local_cert' =>'/YourPath/server.crt',
                'local_pk' => '/YourPath/server.key',
                'verify_peer'  => false,
                'allow_self_signed' => true

            ] 
        ],
        'count'  => 8,
        'bootstrap'=>[
            RC\Helper\Db\Medoo::class, //载入 medoo
            RC\Helper\Db\Think::class, //载入 thinkORM
        ]
    ]
];
```

   这样在你的 `support\process\Http.php` 回调中就可以使用助手函数 `DB()` 调用数据库操作，写法和控制器一致。参看 [支持库与用法](md/db/frame.md) 或 [统一语法查询库](md/db/sdb.md)。




