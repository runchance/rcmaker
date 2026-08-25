# 自动表单

rcmaker 提供自动表单处理组件，可以快速完成数据的增、删、改、查。该组件依赖 `SDB` 和验证器组件，使用前建议先熟悉 [SDB](md/db/sdb.md) 与 [验证器](md/module/validation.md) 的基本用法。

> 自动表单可以通过助手函数 `autoForm()` 或 `AF()` 初始化，具体用法参考 [助手函数](md/Helper.md)。

## 添加数据

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
       $vars = [
            "type" => "add",
            "table"=> "users",
            "method" => "post",
            "name"=>"用户",
            "data" => [
                "user_name"=>['rule'=>'alnum','name'=>'用户名','len'=>[6,30]],
                "user_email"=>['rule'=>'email','name'=>'邮箱地址','len'=>[1,50]],
                "user_password"=>['rule'=>'string','name'=>'密码','len'=>[8,50]],
                "user_phone"=>['rule'=>'string','key'=>'mobile','name'=>'手机','len'=>[0,50]], //指定 key 则直接校验 $req->post('mobile') 的传值
                "user_sex"=>['rule'=>'nnint','name'=>'性别','range'=>[0,1]]
            ]
        ];
        try{
          $autoForm = $req->AF($vars);
          
          $user_password = $req->post('user_password');
          $autoForm->setData('user_password',md5($user_password));//对字段进行二次处理
          
          $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok', 'id'=>$autoForm->id]);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```

访问 `http://localhost:8680/test/autoform` 默认不传入POST数据会返回异常

通过 jquery 访问

```
$.ajax({
  url : '/test/autoform',
  type : "post",
  dataType:'json',
  data : {user_name:'test001', user_email:'test@mail.com', user_password: '12345678', mobile: '13888888888', user_sex: 0}
});
```

返回 `{"code":0,"msg":"ok","id":1}`

其中 `data.{key}` 定义数据要求的字段, autoform 会根据 `method` 定义的传输方法获取到传输的数据


### 数据判重

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
       $vars = [
            "type" => "add", //操作方法
            "table"=> "users", //操作表
            "method" => "post", //传值方法
            "name"=>"用户", //表别名
            "data" => [ //数据与校验
                "user_name"=>['rule'=>'alnum','name'=>'用户名','len'=>[6,30]],
                "user_email"=>['rule'=>'email','name'=>'邮箱地址','len'=>[1,50]],
                "user_password"=>['rule'=>'string','name'=>'密码','len'=>[8,50]],
                "user_phone"=>['rule'=>'string','key'=>'mobile','name'=>'手机','len'=>[0,50]],
                "user_sex"=>['rule'=>'nnint','name'=>'性别','range'=>[0,1]],
                "user_parent"=>['rule'=>'pint','name'=>'推荐人']
            ],
            "check" => [ //数据检查
            	["user_name",'repeat'], //判断 user_name 是否重复
            	["user_email",'repeat'], //判断 user_email 是否重复
            	["user_parent",'exist','user_id'], //判断 user_parent 是否存在, 指定 user_id 则会查 user_id = user_parent 的值是否存在
            ]

        ];
        try{
          $autoForm = $req->AF($vars);
          $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok', 'id'=>$autoForm->id]);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```


## 修改数据

> 支持自定义条件 `where` 和 `whereExp`

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
       $vars = [
            "type" => "update",
            "table"=> "users",
            "method" => "post",
            "id" => 1, //传入id
            "index" => 'user_id', //表索引
            "name"=>"用户",
            "data" => [
                "user_name"=>['rule'=>'alnum','name'=>'用户名','len'=>[6,30]],
                "user_email"=>['rule'=>'email','name'=>'邮箱地址','len'=>[1,50]],
                "user_password"=>['rule'=>'string','name'=>'密码','len'=>[8,50]],
                "user_phone"=>['rule'=>'string','key'=>'mobile','name'=>'手机','len'=>[0,50]],
                "user_sex"=>['rule'=>'nnint','name'=>'性别','range'=>[0,1]],
                "user_parent"=>['rule'=>'pint','name'=>'推荐人']
            ],
            "check" => [
            	["user_name",'repeat'], //判断 user_name 是否重复
            	["user_email",'repeat'], //判断 user_email 是否重复
            	["user_parent",'exist','user_id'], //判断 user_parent 是否存在, 指定 user_id 则会查 user_id = user_parent 的值是否存在
            ]

        ];
        try{
          $autoForm = $req->AF($vars);
          $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok']);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```


## 删除数据

> 支持自定义条件 `where` 和 `whereExp`

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
       $vars = [
            "type" => "delete",
            "table"=> "users",
            "method" => "post",
            "id" => [1,2,3], //传入需要删除的ID
            "index" => 'user_id', //表索引
            "name"=>"用户"
        ];
        try{
          $autoForm = $req->AF($vars);
          $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok']);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
       $vars = [
            "type" => "delete",
            "table"=> "users",
            "method" => "post",
            "id" => 1, //传入id
            "index" => 'user_id', //表索引
            "name"=>"用户"
        ];
        try{
          $autoForm = $req->AF($vars);
          $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok']);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```


## 切换字段开关

> 只适用于0,1的字段, 比如 user 表有个 `user_sex` 代表用户性别 0 代表男, 1代表女

> 支持自定义条件 `where` 和 `whereExp`

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
       $vars = [
            "type" => "toggle",
            "table"=> "users",
            "id"=> 1,
            "index" => 'user_id', //表索引
            "name"=>"用户",
            "data" => [
                "user_sex",
            ],
        ];
        try{
          $autoForm = $req->AF($vars);
          $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok']);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```

这样就会自动切换 data定义的字段, 之前是0就切换为1,之前是1就切换为0

如果你需要拿到本次切换后的结果，可以在 `handle()` 之后读取：

```php
$toggle = $autoForm->getToggle();
```

> 当 `toggle` 命中多条记录时，当前实现要求可以定位出每条记录的索引字段值，因此建议同时提供 `index`，或让自定义条件最终能查出包含索引字段的数据。



## 回调函数 `before` 与 `after`

有时候增删改查需要联动其他数据进行操作,这时候就需要

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
       $vars = [
            "type" => "add",
            "trans"=> true, //全程开启事务,开启后handle()前的所有操作均包裹于事务内,具备原子性
            "table"=> "users",
            "method" => "post",
            "name"=>"用户",
            "data" => [
                "user_name"=>['rule'=>'alnum','name'=>'用户名','len'=>[6,30]],
                "user_email"=>['rule'=>'email','name'=>'邮箱地址','len'=>[1,50]],
                "user_password"=>['rule'=>'string','name'=>'密码','len'=>[8,50]],
                "user_phone"=>['rule'=>'string','key'=>'mobile','name'=>'手机','len'=>[0,50]], //指定 key 则直接校验 $req->post('mobile') 的传值
                "user_sex"=>['rule'=>'nnint','name'=>'性别','range'=>[0,1]]
            ]
        ];
        try{
          $autoForm = $req->AF($vars);

          $autoForm->before(function() use ($autoForm){
          		$autoForm->check(['user_name','repeat']); //检查用户名是否唯一
          });

          $autoForm->after(function() use ($autoForm){
                $autoForm->update(['table'=>'count','data'=>['user_count'=>1],'where'=>['id',1]]); //修改统计人数
          		$autoForm->add(['table'=>'log','data'=>['user_id'=>$autoForm->id,'event'=>'user add']]); //新增日志
          		$lod_id = $autoForm->db->id();
          		
          });
          $autoForm->handle();
          
          $autoForm->commit(); //如果设置开启事务最后需要执行 $autoForm->commit();
          return $req->json(['code' => 0, 'msg' => 'ok']);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```

> 注意：设置 `"trans"=> true` 后，最后需要调用 `$autoForm->commit()` 提交事务。

如果 `before`、`after`、`check()`、`add()`、`update()`、`delete()` 或 `toggle` 中抛出异常，当前实现会自动回滚；如果你在外层捕获异常后想主动终止事务，也可以手动调用：

```php
$autoForm->rollback();
```

## 在回调中终止操作

`before` 或 `after` 回调需要终止操作时，可以调用自动表单检查能力；检查失败会抛出异常并进入回滚流程。
```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
       $vars = [
            "type" => "add",
            "trans"=> true, //全程开启事务,开启后handle()前的所有操作均包裹于事务内,具备原子性
            "table"=> "users",
            "method" => "post",
            "name"=>"用户",
            "data" => [
                "user_name"=>['rule'=>'alnum','name'=>'用户名','len'=>[6,30]],
                "user_email"=>['rule'=>'email','name'=>'邮箱地址','len'=>[1,50]],
                "user_password"=>['rule'=>'string','name'=>'密码','len'=>[8,50]],
                "user_phone"=>['rule'=>'string','key'=>'mobile','name'=>'手机','len'=>[0,50]], //指定 key 则直接校验 $req->post('mobile') 的传值
                "user_sex"=>['rule'=>'nnint','name'=>'性别','range'=>[0,1]]
            ]
        ];
        try{
          $autoForm = $req->AF($vars);
          $user_name = $req->post('user_name');
          $autoForm->before(function() use ($autoForm,$user_name){
                //复用自动表单的判重能力，不需要单独查询数据库
                $autoForm->check(['user_name','repeat',$user_name]);
          });

          $autoForm->after(function() use ($autoForm){
          		
          });
          $autoForm->handle();
          $autoForm->commit(); //如果设置开启事务最后需要执行 $autoForm->commit();
          return $req->json(['code' => 0, 'msg' => 'ok']);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
    }
}
?>
```


独立方法合集


`$autoForm->add($creator)`  例 `$creator = ['table'=>'user','data'=>['name'=>'rcmaker','age'=12]]`  

`$autoForm->update($creator)`  例 `$creator = ['table'=>'user','data'=>['name'=>'rcmaker','age'=12],'where'=>['id','>',0],'whereExp'=>['wb','id',[1,2,3]]]`  

`$autoForm->delete($deletor)`  例 `$creator = ['table'=>'user','where'=>['id','>',0],'whereExp'=>['wb','id',[1,2,3]]]`  

`$autoForm->check($checkor)` 

1、判断数据是否存在

`$checkor = ['user_name','exist','user_name','Jack']`  如果第一个参数和数据库字段名称不一样,第三个参数(非必填)用于指定数据库字段名,第四个参数(非必填)用于直接指定值


2、判断数据是否重复

`$checkor = ['user_name','repeat','Jack']`  第三个参数(非必填)用于直接指定值

3、验证器

`$checkor = ['user_name','',['rule'=>'alnum','name'=>'用户名','len'=>[6,30]]]` 

## 自定义传入数据

某些情况下不一定非要使用 `$req->get()` 或 `$req->post()` 传入数据，也可以直接通过 `transferData` 传入。

> `transferData` 的键名遵循最终取值键名。如果某个字段配置了 `key`，那么 `transferData` 里也应该使用这个 `key` 对应的名称。

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
        $vars = [
            "type" => "add",
            "table"=> "users",
            "method" => "post",
            "name"=>"用户",
            "transferData" => [
            	'user_name'=>'test001',
            	'user_email'=>'test@mail.com',
            	'user_password'=>'12345678',
                	'mobile'=>'13888888888',
            	'user_sex'=>0
            ],
            "data" => [
                "user_name"=>['rule'=>'alnum','name'=>'用户名','len'=>[6,30]],
                "user_email"=>['rule'=>'email','name'=>'邮箱地址','len'=>[1,50]],
                "user_password"=>['rule'=>'string','name'=>'密码','len'=>[8,50]],
                "user_phone"=>['rule'=>'string','key'=>'mobile','name'=>'手机','len'=>[0,50]], //指定 key 则直接校验 $req->post('mobile') 的传值
                "user_sex"=>['rule'=>'nnint','name'=>'性别','range'=>[0,1]]
            ]
        ];
        try{
          $autoForm = $req->AF($vars);
          $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok', 'id'=>(int)$autoForm->id]);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```


## 获取单条数据信息

> 支持自定义条件 `where` 和 `whereExp`

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
        $vars = [
            "type" => "get",
            "table"=> "users",
            "id"=>1,
            "index"=>'user_id',
            "name"=>"用户"
        ];
        try{
          $autoForm = $req->AF($vars);
          $data = $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok', 'data'=>$data]);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```


## 获取多条数据信息

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
        $vars = [
            "type" => "list",
            "table"=> "users",
            "limit"=>5,
            "where"=>[['user_id','>',0]],
            "name"=>"用户"
        ];
        try{
          $autoForm = $req->AF($vars);
          $data = $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok', 'data'=>$data]);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```


## 获取带分页的多条数据信息

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
        $vars = [
            "type" => "paginate",
            "table"=> "users",
            "limit"=>5,
            "where"=>[['user_id','>',0]],
            "name"=>"用户"
        ];
        try{
          $autoForm = $req->AF($vars);
          $data = $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok', 'data'=>$data,'paginate'=>$data->render()]);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```

或者可以使用参考 `page` 传参， 具体含义可以参考 [SDB分页](md/db/sdb.md?id=分页数据)

```php
<?php
namespace app\index\controller;
class test{
	public function autoform($req)
    {
        $vars = [
            "type" => "paginate",
            "table"=> "users",
            "page"=>[
                "listRows"=>[
                    'query'     => [], //url额外参数
                    'fragment'  => '', //url锚点
                    'var_page'  => 'page', //分页变量
                    'list_rows' => 5, //每页数量    
                ],
                "simple"=>true
            ],
            "where"=>[['user_id','>',0]],
            "name"=>"用户"
        ];
        try{
          $autoForm = $req->AF($vars);
          $data = $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok', 'data'=>$data,'paginate'=>$data->render()]);
        }catch(\Throwable $ex){
            return $req->json(['code' => -1, 'msg' => 'fail', 'msg'=>$ex->getMessage()]);
        }
	   
    }
}
?>
```


### 获取数据条件

1、`limit` 获取数量限制

```php
$vars = [
    "type" => "list",
    "table"=> "users",
    "limit"=>5,
    "where"=>[['user_id','>',0]],
    "name"=>"用户"
];
```

2、`order`  排序

```php
$vars = [
    "type" => "list",
    "table"=> "users",
    "order"=>[['user_id','ASC']],
    "where"=>['user_id','>',0],
    "name"=>"用户"
];
```

3、`page`  该参数主要是传入两个参数[`'listRows'`,`'simple'`] 到 `paginate` 方法具体可以参考 [SDB分页](md/db/sdb.md?id=分页数据)

注意：使用 `page` 传参后 `limit` 传参将失效,limit将由`page['listRows']['list_rows']`代替

```php
$vars = [
    "type" => "paginate",
    "table"=> "users",
    "page"=>[
        "listRows"=>[
            'query'     => [], //url额外参数
            'fragment'  => '', //url锚点
            'var_page'  => 'page', //分页变量
            'list_rows' => 5, //每页数量    
        ],
        "simple"=>true
    ],
    "where"=>[['user_id','>',0]],
    "name"=>"用户"
];
```

4、`group` 按分组获取数据

```php
$vars = [
    "type" => "list",
    "table"=> "users",
    "group"=>'user_name',
    "where"=>[['user_id','>',0]],
    "name"=>"用户"
];
```

5、`fields` 定义数据需要的字段,不设置默认获取全部字段

```php
$vars = [
    "type" => "list",
    "table"=> "users",
    "fields"=>'user_id,user_name,user_email',
    "where"=>[['user_id','>',0]],
    "name"=>"用户"
];
```

6、`where` 自定义查询条件

```php
$vars = [
    "type" => "list",
    "table"=> "users",
    "where"=>[['user_id','>',0]],
    "name"=>"用户"
];
```

7、`whereExp` 自定义高级查询条件 用法可以参考 [高级查询](md/db/sdb.md?id=高级查询)

```php
$vars = [
    "type" => "list",
    "table"=> "users",
    "whereExp"=>[['wb','user_id',[1,2,3,4,5]]],
    "name"=>"用户"
];
```

### 带条件获取数据集

`query` 检索条件

第一个参数: `POST 或 GET 传入的名称`

第二个参数: `操作符号` 支持 `=` `eq`(全等) `like`(全匹配)、 `like%`(左匹配)、`%like`(右匹配)、`in`、`>`、`>=`、`<`、`<=`

第三个参数: 默认不填则表示 表字段和传值字段一致，如果有值则表示传值字段查询的是该指定的字段 例如 `['begin','>','user_reg_date']`

第四个参数：闭包回调方法, 某些时候我们需要对传入的值进行一道处理才与数据库字段进行运算比较。

```php
<?php
namespace app\index\controller;
class test{
public function autoform($req)
    {
        $vars = [
            "type" => "paginate",
            "table"=> "users",
            "method" => "get",
            "limit"=>5,
            "where"=>[['user_id','>',0]],
            "name"=>"用户",
            "data" => [
                "user_name"=>['rule'=>'alnum','name'=>'用户名','len'=>[6,30]],
                "user_email"=>['rule'=>'email','name'=>'邮箱地址','len'=>[1,50]],
                "begin"=>['rule'=>'date','name'=>'起始时间','len'=>[10]],
                "end"=>['rule'=>'date','name'=>'结束时间','len'=>[10]],
                "user_parent"=>['rule'=>'pint','name'=>'推荐人']
            ],
            "query" =>[
                ['user_name','like%'],
                ['user_email','%like'],
                ['begin','>','user_reg_date',function($data){return strtotime($data);}],
                ['end','<','user_reg_date',function($data){return strtotime($data);}],
                ['user_parent','in',null,function($data){
                    return explode(',',$data);
                }
                ],
            ] 
        ];
        
          $autoForm = $req->AF($vars);
          $data = $autoForm->handle(); 
          return $req->json(['code' => 0, 'msg' => 'ok', 'data'=>$data,'paginate'=>$data->render()]);
        
	   
    }
}
?>
```

访问 `http://localhost:8680/test/autoform` 传入 `data` 定义的字段 就可以根据 `query` 定义的字段条件进行检索, 例如 `http://localhost:8680/test/autoform?user_name=test&begin=2021-01-01`

> 查询传值方式 由 `method` 决定 你也可以用post方式传入查询参数


## 事务

自动表单组件如果开启事务选项 `"trans"=> true`，则在 `$autoForm->commit()` 之前的所有操作都运行在同一个事务中；如果某项操作不成功会自动 `rollback`，更新数据时也会自动加锁。

> 开启事务后需要使用 `$autoForm->commit()` 提交自动表单的所有操作。

如果你在业务层捕获了异常但不想继续提交，也可以手动执行 `$autoForm->rollback()`。


## 运行时补充

1、`get` 类型在传入 `id + index` 时，会按该索引读取指定记录；如果同时传了 `where` / `whereExp`，则以自定义条件为准。

2、`toggle` 在 `id + index`、`where`、`whereExp` 三种作用域下都可以使用；如果命中多条记录，建议确保结果里包含索引字段，这样框架才能逐条回写切换结果。

3、`where()` 和 `whereExp()` 也可以在初始化后继续追加条件，例如：

```php
$autoForm = $req->AF($vars);
$autoForm->where(['status', '=', 1])->whereExp(['wb', 'user_id', [1, 2, 3]]);
$data = $autoForm->handle();
```





