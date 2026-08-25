# 数据访问与 CRUD

新业务数据库代码必须复用 rcmaker，并按固定层级选择：

```text
标准 CRUD、单条、列表、分页 -> AutoForm
AutoForm 不适合的普通数据库操作 -> SDB()
AutoForm 和 SDB 都无法合理表达的复杂 SQL -> DB()
```

不得因为写法更熟悉或更短而跳级。生产业务代码禁止 PDO、mysqli、自建连接和自建查询构造器；测试文件可用独立连接核对结果。

## 实施前检查

1. 读取 `.agents/doc/md/module/autoform.md`、`.agents/doc/md/db/sdb.md`、`.agents/doc/md/db/frame.md`。
2. 检查 `config/db.php`、`.env`、bootstrap 和相邻控制器。
3. 所有外部输入使用 `validator()`，或写入 AutoForm 的 `data` 规则。
4. 核实表名、索引、字段、事务和当前数据库引擎，不猜参数结构。

普通自定义进程不一定继承主 APP bootstrap；`type=app` 进程组按 APP 规则继承或覆盖配置。配置变化后重启对应进程组。

## 跨数据库兼容

Windows 开发可能使用 SQLite，Linux 生产通常使用 MySQL 或 PostgreSQL。数据库连接与驱动必须由 `.env`、`config/db.php` 和框架 bootstrap 决定，业务代码不得写死 DSN、账号、主机、端口或驱动名。

- 优先使用 AutoForm 和 SDB，让查询通过框架数据层适配当前数据库。
- 不依赖某个数据库独有的标识符引号、日期函数、JSON 函数、字段类型、布尔值、自增或 UPSERT 语法。
- 只有复杂需求确实需要 `DB()` 和厂商专用 SQL 时，才允许隔离方言差异，并记录及测试每个正式支持的数据库分支。
- SQLite 测试通过不代表 MySQL/PostgreSQL 的锁、并发、事务隔离、排序、大小写和类型转换行为相同；上线前必须在实际生产数据库上验证。

## AutoForm

以下场景必须使用 `$req->AF($vars)` / `autoForm()`：

- `add`、`update`、`delete`、`toggle`。
- 单条 `get`、列表 `list`、分页 `paginate`。
- 字段验证、查询参数验证、判重和存在性检查。
- `where`、`whereExp`、`query`、`fields`、`order`、`group`、`limit` 能表达的操作。
- 通过 `before`、`after`、独立增删改方法和事务完成的组合写入。

新增示例：

```php
$form = $req->AF([
    'type' => 'add',
    'table' => 'users',
    'method' => 'post',
    'name' => '用户',
    'data' => [
        'user_name' => ['rule' => 'alnum', 'name' => '用户名', 'len' => [6, 30]],
        'user_email' => ['rule' => 'email', 'name' => '邮箱', 'len' => [1, 120]],
        'password' => ['rule' => 'string', 'name' => '密码', 'len' => [8, 100]],
    ],
    'check' => [
        ['user_name', 'repeat'],
        ['user_email', 'repeat'],
    ],
]);
$form->setData('password', password_hash($req->post('password'), PASSWORD_DEFAULT));
$form->handle();

return $req->json(['code' => 0, 'msg' => 'ok', 'id' => (int) $form->id]);
```

单条查询：

```php
$id = validator()->check($req->get('id'), ['rule' => 'pint', 'name' => '用户ID']);
$data = $req->AF([
    'type' => 'get',
    'table' => 'users',
    'id' => $id,
    'index' => 'user_id',
    'fields' => 'user_id,user_name,user_email',
    'name' => '用户',
])->handle();
```

列表和分页使用 `list` / `paginate`，筛选输入写进 `data` 与 `query`，固定条件使用 `where` / `whereExp`。更新和删除必须有经过验证的 `id + index` 或明确条件，禁止无边界写操作。

开启 `'trans' => true` 后必须调用 `commit()`；失败路径调用 `rollback()` 或让组件异常流程回滚。事务中不执行远程 HTTP、邮件或长耗时任务。

## SDB

AutoForm 不适合字段驱动表达时，普通数据库操作使用 `$req->SDB()` / `SDB($req)`，例如复杂链式组合、关联、聚合或需要精细控制的事务步骤。

```php
$rows = $req->SDB()
    ->table('orders')
    ->where('status', 1)
    ->order([['order_id', 'DESC']])
    ->limit(100)
    ->select('order_id,user_id,total');
```

使用前必须能说明 AutoForm 为什么不适合；“SDB 写起来更快”不是理由。完整查询、关联、聚合、锁、事务和底层绑定见 `.agents/doc/md/db/sdb.md`。列表必须有上限或分页，事务始终使用同一个 SDB 实例。

## DB

只有 SDB 也无法合理表达的复杂 SQL、数据库厂商专用能力或底层驱动接口，才允许 `DB()` / `database()`。使用时必须：

- 说明 AutoForm 和 SDB 不适用的具体原因。
- 核对当前 `default_frame`、文档和 Helper 源码的方法签名。
- 参数绑定数据值，白名单化动态表名或字段名。
- 控制结果集大小，不把 DB 当作普通查询入口。

Medoo、Think、Laravel 等支持层 API 和返回类型不同，因此不要复制其他项目的 DB 写法。Model 可用于理解遗留项目，但新功能仍遵守 AutoForm -> SDB -> DB 的层级。

## 完成检查

- 输入使用 Validator 或 AutoForm `data` 规则。
- 标准 CRUD、单条、列表和分页使用 AutoForm。
- SDB 和 DB 的下沉理由明确且符合层级。
- 没有 PDO、mysqli、拼接用户输入的 SQL 或重复数据层。
- 写操作条件明确，事务完整，列表有界。
- HTTP 结果使用 `$req->json()`。
- 开发与生产数据库可通过配置切换，业务代码未写死驱动或 SQL 方言。
