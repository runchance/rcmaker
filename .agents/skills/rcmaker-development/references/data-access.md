# 数据访问与 CRUD

数据库任务必须先检查项目正在使用的引擎和写法。不要在同一应用中随意混用 Medoo、Think、Laravel、SDB 和手写 PDO。

## 实施前检查

依次检查：

1. `config/db.php` 中的 `default_frame`、`default` 和驱动配置。
2. `.env` 的 `[db]`、具体连接段和 `[bootstrap]`。
3. `config/bootstrap.php` 与目标 APP/自定义进程的 `bootstrap`。
4. `apps/<app>/model` 和相邻控制器实际使用 `DB()`、`SDB()` 还是 Model。
5. `composer.json` 是否安装 Think/Laravel/MongoDB 所需依赖。

不得把连接凭据写入控制器、模型或 Skill 生成的示例文件。

## 数据层选型顺序

1. 项目已经形成统一数据层时，继续使用该数据层。
2. 简单且需要保持底层原生能力时使用 `DB()`。
3. 希望 Medoo、Think、Laravel 间保持常用链式语法一致时使用 `$req->SDB()`。
4. 已有模型、关联、观察者或领域行为时使用 Model。
5. 标准增删改查、字段验证、判重、分页和事务流程可使用 AutoForm。
6. SDB 不支持的高级查询先用 `bind()` 或 `instance()` 回到底层框架，不要直接新建 PDO。

## 启动数据库支持

`DB()` 和 `SDB()` 依赖数据库支持类。按实际引擎载入：

```ini
[bootstrap]
load[] = RC\Helper\Db\Medoo
;load[] = RC\Helper\Db\Think
;load[] = RC\Helper\Db\Laravel

[db]
default = mysql
default_frame = medoo
```

- Laravel 支持需要 `illuminate/database`。
- Laravel MongoDB 还需要项目文档指定的 MongoDB 包。
- 自定义普通进程不一定继承主 APP bootstrap；在 `config/process.php` 对应进程的 `bootstrap` 中显式加载。
- `type=app` 进程组按当前框架的 APP 启动规则继承/覆盖配置，修改后重启目标进程组。
- 只修改 `default_frame` 而未载入支持类会在调用时失败。

完整配置见 `official/doc/md/db/config.md`。

## 使用原生支持层 `DB()`

### 默认/Medoo

```php
public function detail($req)
{
    $id = validator()->check($req->get('id'), [
        'rule' => 'pint',
        'name' => '用户ID',
    ]);

    $db = DB();
    $user = $db->get('users', '*', ['id' => $id]);

    return $req->json([
        'code' => 0,
        'msg' => 'ok',
        'data' => $user,
    ]);
}
```

`DB()` 默认使用 `db.default_frame`。明确指定 Medoo 可使用 `DB('medoo')`；指定其他驱动可传第二个参数，例如 `DB('medoo', 'sqlite')`。

### Think

```php
$db = DB('think');
$user = $db::table('users')->where('id', $id)->find();
return $req->json(['code' => 0, 'data' => $user]);
```

### Laravel Database

```php
$db = DB('laravel');
$user = $db::table('users')->where('id', $id)->first();
return $req->json(['code' => 0, 'data' => $user]);
```

不同底层库的查询、返回类型和事务 API 不完全相同。打开 `official/doc/md/db/frame.md`，并以项目安装版本的官方 ORM 文档为准。

## 使用统一查询层 `SDB()`

控制器优先使用 Request 映射：

```php
$db = $req->SDB();
```

也可使用全局写法 `SDB($req)`。指定引擎/驱动示例：

```php
$db = $req->SDB('think', 'sqlsrv');
```

### 查询

```php
$users = $req->SDB()
    ->table('users')
    ->where('status', 1)
    ->order([['id', 'DESC']])
    ->limit(20)
    ->select('id,name,email');

$user = $req->SDB()
    ->table('users')
    ->where('id', $id)
    ->find('*');
```

### 新增、修改、删除

```php
$db = $req->SDB();

$inserted = $db->table('users')->add([
    'name' => $name,
    'email' => $email,
]);
$id = $db->id();

$updated = $db->table('users')
    ->where('id', $id)
    ->update(['name' => $name]);

$deleted = $db->table('users')
    ->where('id', $id)
    ->delete();
```

写操作前必须完成验证和权限检查。更新、删除必须有明确且经过验证的条件，禁止无意中的全表操作。

### 分页

```php
$list = $req->SDB()
    ->table('users')
    ->order([['id', 'DESC']])
    ->paginate('*', [
        'path' => '/users?page=[PAGE]',
        'list_rows' => 20,
        'var_page' => 'page',
        'query' => ['keyword' => $req->get('keyword', '')],
    ]);

return $req->json([
    'code' => 0,
    'msg' => 'ok',
    'data' => $list->items(),
    'page' => $list,
]);
```

不需要总数时使用文档中的 simple paginate，避免不必要的 count 查询。

### 事务

```php
$db = $req->SDB();
$db->startTrans();

try {
    $db->table('accounts')->where('id', $fromId)->lock(true)->find();
    // 在同一个 $db 事务对象上完成相关写操作。
    $db->commit();
} catch (\Throwable $exception) {
    $db->rollback();
    throw $exception;
}
```

不要在事务中混入其他连接实例。事务尽量短，不在事务内执行远程 HTTP、邮件或长耗时任务。

### 超出 SDB 能力

- 先看 `bind($method, ...$arguments)` 是否可调用底层查询方法。
- 再用 `instance()` 获取已配置的底层连接。
- 仍不满足时才评估项目服务层中的专用实现。

完整 where、join、group、having、聚合、锁和绑定语法见 `official/doc/md/db/sdb.md`，不要猜缩写或参数结构。

## 使用 Model

Think 模型：

```php
namespace app\index\model;

use RC\Model\Think as ThinkModel;

class user extends ThinkModel
{
    protected $table = 'users';
    protected $pk = 'id';
    public $timestamps = false;
}
```

Laravel 模型继承 `RC\Model\Laravel`，主键属性使用对应 ORM 约定。首次使用模型前初始化引擎：

```php
DB('think');
$user = \app\index\model\user::find($id);
return $req->json(['code' => 0, 'data' => $user]);
```

也可以通过 `$req->M('user')` / `$req->model()` 取得当前应用模型。模型关系、观察者和初始化要求见 `official/doc/md/db/model.md`。

## 使用 AutoForm

AutoForm 适合字段驱动的标准 CRUD；它集成 SDB 和 Validator。复杂权限、跨聚合状态机、外部副作用或难以表达的事务，不要强行塞入 AutoForm。

新增示例：

```php
$vars = [
    'type' => 'add',
    'table' => 'users',
    'method' => 'post',
    'name' => '用户',
    'data' => [
        'user_name' => ['rule' => 'alnum', 'name' => '用户名', 'len' => [6, 30]],
        'user_email' => ['rule' => 'email', 'name' => '邮箱', 'len' => [1, 120]],
    ],
    'check' => [
        ['user_name', 'repeat'],
        ['user_email', 'repeat'],
    ],
];

try {
    $form = $req->AF($vars);
    $form->handle();

    return $req->json(['code' => 0, 'msg' => 'ok', 'id' => $form->id]);
} catch (\Throwable $exception) {
    return $req->json(['code' => -1, 'msg' => $exception->getMessage()]);
}
```

`type` 可覆盖 `add`、`update`、`delete`、`get`、`list`、`paginate` 等文档场景。开启 `'trans' => true` 时必须按文档调用 `commit()`，失败路径调用 `rollback()` 或让组件异常流程处理。密码必须使用合适的密码哈希，不能照抄旧示例中的 MD5。

完整字段规则、`check`、`where`、`before/after`、事务和分页见 `official/doc/md/module/autoform.md`。

## 数据层完成检查

- 没有新增 `new PDO()`、`mysqli_*` 或每请求连接代码。
- 数据层与当前项目已加载的引擎一致。
- 输入先验证，动态标识符使用白名单，值使用底层库的参数绑定能力。
- 列表有上限或分页，没有 N+1 查询和无界结果集。
- 更新/删除条件明确，事务可以在所有失败路径回滚。
- 响应使用 `$req->json()`，没有手工 JSON HTTP 输出。
