# SDB

SDB 是 `simple_database` 的简写，`SDB()` 方法也是 `simple_database()` 的请求对象映射写法。

SDB 用于在 `medoo`、`thinkORM`、`laravelORM` 等数据库组件之间提供一层常用查询语法包装，减少简单业务查询在不同底层框架之间的写法差异。

> 运用 `SDB` 可以在常见查询场景下减少不同数据库框架之间的语法差异，但它并不等于完全无差别兼容所有底层能力。

> `SDB()` 方法和 `DB()` 方法类似，也可以通过参数切换数据库框架和数据库类型，例如 `$db = $req->SDB('think','sqlsrv')`。

> `DB()` 属于原生数据库支持层，`SDB()` 属于统一语法包装层。两者不要混用理解。

## 查询构造

### 获取所有行 

```php
<?php
namespace app\index\controller;

class index{
	public function index($req)
    {
        $db = $req->SDB();
        $user = $db->table('user')->select();
        /*OR
           $user = $db->t('user')->s(); 
        */
        return $req->json($user);
    }
}
?>
```


### 获取一行

```php
<?php
namespace app\index\controller;

class index{
	public function index($req)
    {
        $db = $req->SDB();
        $user = $db->table('user')->find();
        return $req->json($user);
    }
}
?>
```

### 获取指定的字段

```php
<?php
namespace app\index\controller;

class index{
	public function index($req)
    {
        $db = $req->SDB();
        $user = $db->table('user')->select('id,name');
        return $req->json($user);
    }
}
?>
```

### debug 开启调试

> 某些情况下需要调试 SQL 语句，可以预先调用 `debug()`。

```php
<?php
namespace app\index\controller;

class index{
	public function index($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->table('user')->select('id,name');
        return $db->sql();
    }
}
?>
```

### error 显示错误

> 某些 SQL 错误会抛出异常，SDB 可以收集错误信息并通过 `error()` 返回。

```php
<?php
namespace app\index\controller;

class index{
	public function index($req)
    {
        $db = $req->SDB();
        $user = $db->table('user_error')->select('id,name');
        if(!$user){
        	return $db->error();
        }
        return $req->json($user);
    }
}
?>
```

以上示例将会输出类似 `Table 'user_error' doesn't exist`


### 操作数据的主要方法 
```php
//获取一条数据(常见场景下返回一维数组，未命中时可能返回 null)
find($field='*');
f($field='*');

//获取多条数据(常见场景下返回二维数组)
select($field='*');
s($field='*');

//删除数据
delete();
d();

//添加数据
add(array $data);
a(array $data);

//修改数据
update(array $data);
u(array $data);

//返回带分页的数据列表
paginate($field='*',$listRows = null,$simple = false);
p($field='*',$listRows = null,$simple = false);

//获取数量
count($field='*');
c($field='*');

//聚合
agg($function,$field);

```

### JOIN 语句
> join($joinTable,$joinTableOn,$tableOn,$joinType)  $joinType 默认为 `INNER` 也可以设置为 `LEFT` 或 `RIGHT`

> 简写 j($joinTable,$joinTableOn,$tableOn,$joinType) 

```php
<?php
namespace app\index\controller;

class index{
	public function index($req)
    {
        $db = $req->SDB();
        $user = $db->table('user')
        ->join('group','group.user_id','user.id')
        ->j('user_extend','user_extend.user_id','user.id')
        ->where('user.id',1)
        ->find();
        return $req->json($user);
    }
}
?>
```

```php
<?php
namespace app\index\controller;

class index{
	public function index($req)
    {
        $db = $req->SDB();
        $user = $db->table('user AS u')
        ->join('group AS g','g.user_id','u.id','LEFT')
        ->join('user_extend AS e','e.user_id','u.id','LEFT')
        ->where('u.id',1)
        ->find();
        return $req->json($user);
    }
}
?>
```

### where

你可以使用多个where进行链式查询

> where($field,$op,$value,$whereType)

> 简写  w($field,$op,$value,$whereType)

其中 $op 为操作方法,缺省为`=`, 不区分大小写 支持`=`、`>`、`<`、`>=`、`<=`、`<>`、`[NOT ]LIKE`、`[NOT ]between`、`[NOT ]in`、`[NOT ]NULL`等

$whereType 模式为 `AND` 如果设置为 `OR`, 则构造器会拼接为 `OR $where`

实际上如果切换不同的数据库框架，where 操作还是会有差异。例如 thinkORM 中 `where('id','IN',[1,2,3,4,5,6])` 这种写法可能不会按预期解析，需要改成 `where('id','IN','1,2,3,4,5,6')`。因此 SDB 更适合常见查询场景，不建议把它当成所有底层语义都完全一致的抽象层。

如果你切换  `$db = SDB('think')` [请参考](https://www.kancloud.cn/manual/think-orm/1258007) 或 $db = SDB('laravel') [请参考](https://learnku.com/docs/laravel/8.x/queries/9401#3cec6d)

```php
<?php
namespace app\index\controller;

class index{
	public function where($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->table('user')->where('id',1)->where('id','<',1000)->where('id','>',0)->where('name','LIKE','王%','OR')->find();
        var_dump($db->sql());
        return $req->json($user);
    }
    public function wherein($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->t('user')->w('id','IN',[1,2,3,4,5,6])->s();
        var_dump($db->sql());
        return $req->json($user);
    }
}
?>
```

#### whereOr

你可以使用多个whereOr进行链式查询

> whereOr($field,$op,$value)

> 简写  wo($field,$op,$value)

```php
$user = $db->table('user')->whereOr('username','tom')->whereOr('mobile','13888888888')->where('password','123456')->find();
```

以上语句等同于下

```php
$user = $db->table('user')->where('username','tom','OR')->where('mobile','13888888888','OR')->where('password','123456')->find();
```

值得注意的是: 在`SDB`中多个`whereOr`会自动进行括号归集并自动与其他 `where` 条件进行 `AND` 组合,这也是SDB有别于`ThinkORM`和`LaravelORM`的用法;

以上链式操作对应的SQL语句类似：`SELECT * FROM user WHERE (username = 'tom' OR mobile = '13888888888') AND password = '123456' LIMIT 1`

### where 或 whereOr 闭包查询
> 需要 `runchance/rcmaker-framework >=2.8.7`

> where(Closure)
> 简写 w(Closure)

> whereOr(Closure)
> 简写 wo(Closure)

有时候查询语句需要比较复杂的`OR`和`AND`嵌套,这时候单纯使用whereOr和where就很难达到目的,此时可以运用where闭包达到目的。

案例1：`SELECT * FROM users WHERE (user_id BETWEEN 1 AND 10 OR user_id BETWEEN 50 AND 70) AND (user_name LIKE 'a%' OR user_name LIKE 'b%') AND active >= 1`

以上案例是较复杂的条件嵌套查询,在SDB中可以这样实现：

```php
        $query = $db->t('users')
        ->w(function($q){
            $q->whereOr('user_id','bt',[1,10])->whereOr('user_id','bt',[50,70]);
        })
        ->w(function($q){
            $q->whereOr('user_name','LIKE','a%')->whereOr('user_name','LIKE','b%');
        })
        ->w('active','>=',1)
        ->s();
```

案例2: `SELECT * FROM users WHERE (user_name = '北乔峰' AND password = '123456') OR (user_name = '南慕容' AND password = '123456') LIMIT 1`

```php
        $query = $db->t('users')
        ->whereOr(function($q){
            $q->where('user_name','北乔峰')->where('password','123456');
        })
        ->wo(function($q){
            $q->where('user_name','南慕容')->where('password','123456');
        })
        ->find('');
```

注意： `whereOr(Closure)` 会把包内条件合并然后通过`OR`与包外条件进行组合,  `where(Closure)` 会把包内条件合并然后通过`AND`与包外条件进行组合

> 以上示例仅适用于 `medoo` 框架, 如果你在SDB中使用`thinkORM`或`laravelORM`框架也同样支持闭包用法，但是解释和语意略有所不同，而且在包体内的`$q`对象不是`SDB`，具体用法请参看 [`thinkORM`](https://www.kancloud.cn/manual/thinkphp6_0/1037566), [`laravelORM`](https://laravel.com/docs/10.x/queries#or-where-clauses)


### 高级查询

> whereExp($op,$field,$value,$whereType)

> 简写 we($op,$field,$value,$whereType)

$whereType 模式为 `AND` 如果设置为 `OR`, 则构造器会拼接为 `OR $where`

```php
<?php
namespace app\index\controller;

class index{
	public function where($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->table('user')->whereExp('wb','id',[1,1000])->select();
        var_dump($db->sql());
        return $req->json($user);
    }
    public function where1($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->t('user')->we('wl','name','王%')->we('wni','id',[1,2,3,4,5],'OR')->s();
        var_dump($db->sql());
        return $req->json($user);
    }
    public function where2($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->table('user')->where('id',1)->whereExp('wnn','name','OR')->select();
        var_dump($db->sql());
        return $req->json($user);
    }
}
?>
```

如上面的例子 实际上指向的是 `whereBetween`

使用不同的数据库框架 whereExp 中支持的操作方式不太相同, $op可以是全写也可以为简写 例如 `whereExp('wb','user_id',[1,1000])` 和 `whereExp('whereBetween','user_id',[1,1000])` 是一样的

1、medoo 支持的操作方法

```
'wb'=>'whereBetween',
'wnb'=>'whereNotBetween',
'wi'=>'whereIn',
'wni'=>'whereNotIn',
'wl'=>'whereLike',
'wnl'=>'whereNotLike',
'wn'=>'whereNull',
'wnn'=>'whereNotNull'
```

2、thinkORM 支持的操作方法

```
'or'=>'whereOr',
'wb'=>'whereBetween',
'wnb'=>'whereNotBetween',
'wi'=>'whereIn',
'wni'=>'whereNotIn',
'wl'=>'whereLike',
'wnl'=>'whereNotLike',
'we'=>'whereExists',
'wne'=>'whereNotExists',
'wn'=>'whereNull',
'wnn'=>'whereNotNull',
'wt'=>'whereTime',
'wbt'=>'whereBetweenTime',
'wnbt'=>'whereNotBetweenTime',
'wc'=>'whereColumn'
```

3、laravelORM 支持的操作方法

```
'or'=>'orWhere',
'wb'=>'whereBetween',
'orwb'=>'orWhereBetween',
'wnb'=>'whereNotBetween',
'orwnb'=>'orWhereNotBetween',
'wc'=>'whereColumn',
'orwc'=>'orWhereColumn',
'wt'=>'whereTime',
'orwt'=>'orWhereTime',
'wd'=>'whereDay',
'orwd'=>'orWhereDay',
'wdt'=>'whereDate',
'orwdt'=>'orWhereDate',
'wm'=>'whereMonth',
'orwm'=>'orWhereMonth',
'wy'=>'whereYear',
'orwy'=>'orWhereYear',
'wi'=>'whereIn',
'orwi'=>'orWhereIn', 
'wni'=>'whereNotIn',
'orwni'=>'orWhereNotIn',
'wn'=>'whereNull',
'orwn'=>'orWhereNull',
'wnn'=>'whereNotNull',
'orwnn'=>'orWhereNotNull'
```

> laravelORM 有大量的 or{$op}方法, 其实 `whereExp('wb','user_id',[1,1000],'OR')` 与 `whereExp('orwb','user_id',[1,1000])` 是一样的;


### order 排序语句

> `order($order)`  `$order` 可以为数组或字符串

> 简写 `o($order)`

```php
<?php
namespace app\index\controller;

class index{
	//单字段排序 默认为升序 ASC
	public function order($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->table('user')->where('id','>',0)->order('id')->select();
        var_dump($db->sql());
        return $req->json($user);
    }

    //多字段排序
	public function order1($req)
    {
        $db = $req->SDB()->debug();
        $order = [
          ['id','ASC'],
          ['name','DESC'],
        ];
        $user = $db->table('user')->where('id','>',0)->order($order)->select();
        var_dump($db->sql());
        return $req->json($user);
    }

    //随机排序
	public function order2($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->t('user')->w('id','>',0)->o('rand()')->s();
        var_dump($db->sql());
        return $req->json($user);
    }
}
?>
```

### limit 语句

> `limit($limit=10,$offset=null)`

> 简写 `l($limit=10,$offset=null)`

```php
<?php
namespace app\index\controller;

class index{

	//限制1条,偏移为0
	public function limit1($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->table('user')->where('id','>',0)->limit(1)->select();
        var_dump($db->sql());
        return $req->json($user);
    }

    //限制2条,偏移为2(从排序的第二条读取)
	public function limit2($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->t('user')->w('id','>',0)->l(2,2)->s();
        var_dump($db->sql());
        return $req->json($user);
    }

}
?>
```

### having 语句, 对分组的结果集进行筛选

> `having($having)`

> 简写 `h($having)`

```php
<?php
namespace app\index\controller;

class index{

	public function group1($req)
    {
        $db = $req->SDB()->debug();
        $user = $db->table('user')->where('id','>',0)->group('name')->having('age > 13')->select();
        var_dump($db->sql());
        return $req->json($user);
    }

}
?>
```


### count() 方法 获取查询的行数
> `count($field='*')`

> 简写 `c($field='*')`

```php
<?php
namespace app\index\controller;

class index{

	//获取查询的数量
	public function avg($req)
    {
        $db = $req->SDB();
        $count = $db->table('user')->where('age','>',12)->count();
        return $count;
    }

}
?>
```



### 聚合方法

> `agg($function,$field)`

```php
<?php
namespace app\index\controller;

class index{

	//求平均值
	public function avg($req)
    {
        $db = $req->SDB();
        $avg = $db->table('user')->where('age','>',12)->agg('avg','age');
        return $avg;
    }

    //获取最大值
	public function max($req)
    {
        $db = $req->SDB();
        $max = $db->table('user')->where('age','>',12)->agg('max','age');
        return $max ;
    }

    //获取最小值
	public function min($req)
    {
        $db = $req->SDB();
        $min = $db->table('user')->where('age','>',12)->agg('min','age');
        return $min;
    }

    //获取累加值
	public function sum($req)
    {
        $db = $req->SDB();
        $sum = $db->table('user')->where('age','>',12)->agg('sum','age');
        return $sum;
    }

}
?>
```

### 添加数据

```php
<?php
namespace app\index\controller;

class index{

	public function add($req)
    {
        $db = $req->SDB()->debug();
        $adduser = $db->table('user')->add(['name'=>'jack','age'=>12]);
        $id = $db->id();
        var_dump($db->sql());
        return $adduser ? 'insert ok id is'.$id : $db->error();
    }

}
?>
```

### 修改数据

```php
<?php
namespace app\index\controller;

class index{

	public function update($req)
    {
        $db = $req->SDB()->debug();
        $updator = $db->table('user')->where('age','>',99)->update(['name'=>'oldman']);
        var_dump($db->sql());
        return $updator ? 'update ok' : $db->error();
    }

}
?>
```

### 删除数据

```php
<?php
namespace app\index\controller;

class index{

	public function del($req)
    {
        $db = $req->SDB()->debug();
        $deletor = $db->table('user')->where('id','in',[100,101,102,103])->delete();
        var_dump($db->sql());
        return $deletor ? 'delete ok' : $db->error();
    }

}
?>
```

### 分页数据

```php
<?php
namespace app\index\controller;

class test{

    public function paginate($req)
    {
        $db = $req->SDB()->debug();
        $data = $db->table('user')->where('id','>',0)->paginate();
        return $req->json(['code' => 0, 'msg' => 'ok', 'data'=>$data,'paginate'=>$data->render()]);
    }

}
?>
```

分页参数设置

`paginate($field='*',$listRows = null,$simple = false);`

`$field` 为查询的字段

`$listRows` 为数字时候表示 查询的条目限制

`$simple` 是否开启简单分也 , 开启后不显示总数,只能进行上一页和下一页操作

```php
$listRows = [
    'query'     => [], //url额外参数(medoo不生效)
    'fragment'  => '', //url锚点(只针对thinkorm)设置
    'var_page'  => 'page', //分页变量
    'list_rows' => 15, //每页数量
    'path' => '/?page=[PAGE]'
];
```




### 原生查询

> 有时候复杂的SQL语句需要原生查询,可以调用 `$db->query($sql,$fetch)` 进行,原生查询也支持包裹在事务里,原生查询需要注意避免`SQL注入`问题

1、执行只返回`true`和`false`的sql,比如 `creat table` 、`update table`、 `delete table` 等, `$fetch` 参数缺省或设置为`false`

```php
<?php
namespace app\index\controller;

class index{

    public function update($req)
    {
        $db = $req->SDB();
        $updator = $db->query('UPDATE user SET name=\'Jack\' WHERE id=1');
        return $updator ? 'update ok' : $db->error();
    }

}
?>
```

2、执行需要返回结果集的SQL, 比如`select table`,`$fetch` 参数缺省或设置为`true`

```php
<?php
namespace app\index\controller;

class index{

    public function getUser($req)
    {
        $db = $req->SDB();
        $users = $db->query('SELECT * FROM user WHERE id>100',true);
        if(!$users){
            return $db->error();
        }
        return $req->json($users);
    }

}
?>
```






### 事务以及锁

> `lock($lock)` 为悲观锁  `$lock = true` 则默认为 `'LOCK FOR UPDATE'`, $lock 也可以为字符串 比如 `lock('lock in share mode')`

> 简写 `lc($lock)`

```php
<?php
namespace app\index\controller;

class index{

	public function group1($req)
    {
        $db = $req->SDB();
        //开启事务
        $db->startTrans();
        //or $db->st();
        $queryUser = $db->table('user')->where('age','>',12)->lock(true)->select();
        if($queryUser){
        	foreach($queryUser as $user){
        		if((int)$user['age'] > 99){
        			$update = $db->table('user')->where('id',(int)$user['id'])->update(['name'=>'oldman']);
        			if(!$update){
        				//事务回退
        				$db->rollback();
                        //or $db->rb();
        				return 'update fail!';
        			}
        		}
        	}
        }
        //事务提交
        $db->commit();
        //or $db->cm();
        return 'update ok';
    }

}
?>
```

### bind 方法

> 如果使用 `thinkORM` 或者 `laravelORM` 本身框架带了很多实用的链式方法进行查询和操作, 但是SDB不可能一一进行适配, 这时候就可以用 bind 方法调用 源框架的链式方法

比如想用 `laravelORM` 的 `CrossJoin` 你可以这样写：

> 简写 `b($exp, ...$bind)`


```php
<?php
namespace app\index\controller;

class index{

	public function CrossJoin($req)
    {
        $db = $req->SDB('laravel');
        $user = $db->table('user')->bind('crossJoin','colors')->where('age','>',12)->lock(true)->select();
    }

}
?>
```

又或者想用 `thinkORM` 的 `union` 你可以这样写：

```php
<?php
namespace app\index\controller;
class test{
	public function index($req)
    {
    	$db = $req->SDB('think')->debug();
        $user = $db->t('user')->b('union','SELECT name FROM user_1')->s();
        var_dump($db->sql());
        return $req->json($user);
    }
}
?>
```


### 返回框架原生 connect 对象

有时候SDB 不能满足我们对数据的查询或者操作，这个时候可以用 instance() 方法返回原生框架的 connect 对象 继而进行原生框架的语法查询和操作


```php
<?php
namespace app\index\controller;
class test{
	public function index($req)
    {
    	$db = $req->SDB('laravel')->debug();
        $user = $db->table('user')->where('id','>',0)->select();
        $laravelDB = $db->instance();
        $laravelDB::table('user')->updateOrInsert(
	        ['email' => 'john@example.com', 'name' => 'John'],
	        ['votes' => '2']
	    );

        return 'ok';
    }
}
?>
```
