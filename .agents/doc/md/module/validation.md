# 验证器

rcmaker 内置了验证器 `RC\Helper\Validator`。你可以直接使用助手函数 `validator()` / `VD()`，也可以手动实例化 `new \RC\Helper\Validator()`。它可以快速校验 GET、POST 以及上传文件数据。

## 快速开始

初始化验证器可以使用助手函数 `validator()` 或 `VD()`，具体用法参考 [助手函数](md/Helper.md)。

示例：为了演示方便，下面传入的值都来自 `get`；实际使用中也可以直接校验 `$req->post()`。

```php
<?php
namespace app\index\controller;
class test{
	public function validator($req)
    {
         $v = validator();
       	 $data = $v->input($req->get(), [
           'id' => ['rule'=>'int','name'=>'id'],
           'email' => ['rule'=>'email','len'=>[1,120],'name'=>'邮箱','options'=>['filter'=>'trim']],
           'name' => ['rule'=>'alnum','name'=>'昵称','options'=>['attach'=>['-','_']]],
         ]);
	     return $req->json($data);
    }
}
?>
```

访问 `http://localhost:8680/test/validator`

返回 异常数据, 说明校验不通过

> 如果不想让验证失败直接抛异常，可以传入回调函数自行接管失败信息。

```php
<?php
namespace app\index\controller;
class test{
	public function validator($req)
    {
         $v = validator();
	     $msg = '';
       	 $data = $v->input($req->get(), [
           'id' => ['rule'=>'int','name'=>'id'],
           'email' => ['rule'=>'email','len'=>[1,120],'name'=>'邮箱','options'=>['filter'=>'trim']],
           'name' => ['rule'=>'alnum','name'=>'昵称','options'=>['attach'=>['-','_']]],
         ],function($field,$fail) use (&$msg){
         	 $msg = $fail['msg'];
         });

         if(!$data){
         	return $msg;
         }
	     return $req->json($data);
    }
}
?>
```

访问 `http://localhost:8680/test/validator`

返回捕获的的 `$msg`, 不在有异常数据


## 验证单条数据

> 有时候我们只需要对单条数据进行验证

```php
<?php
namespace app\index\controller;
class test{
	public function validator($req)
    {
       $v = validator();
	   $msg = '';
       	   
       $name = $v->check($req->get('name'),['name'=>'昵称','len'=>[3,20]]);
       return $name;
	   
    }
}
?>
```

访问 `http://localhost:8680/test/validator`

返回 异常数据, 说明校验不通过

>如果我们不想返回异常,可以捕获异常

```php
<?php
namespace app\index\controller;
class test{
	public function validator($req)
    {
       $v = validator();
	   $msg = '';
       	   
       $name = $v->check($req->get('name'),['name'=>'标识符','len'=>[3,20]],function($field,$fail) use (&$msg){
          //$msg = $fail['msg'];
       });
       if(!$name){
          return $v->fail();
       }else{
          return $name;
       }
	   
    }
}
?>
```

访问 `http://localhost:8680/test/validator`

返回 `{"rule":"string","msg":"[标识符] 校验失败, 不是合法的字符串","type":"type","exp":"String"}`

默认情况下，`$v->fail()` 会返回当前失败详情。你也可以在校验失败回调里自定义 `$msg`，甚至根据 `$fail['rule']` 输出多语言错误消息。

例如;

```php
<?php
namespace app\index\controller;
class test{
	public function validator($req)
    {
       $v = validator();
	   $msg = '';
       	   
       $name = $v->check($req->get('name'),['name'=>'nickname','len'=>[3,20]],function($field,$fail) use (&$msg){
       	  if($fail['rule']=='string'){
       	  	$msg = $field . ' Is not a valid string ';
       	  }
          
       });
       if(!$name){
          return $msg;
       }else{
          return $name;
       }
	   
    }
}
?>
```

其中 `$fail['rule']` 是校验规则, `$fail['msg']` 是默认校验不通过返回的消息, `$fail['type']` 是告诉你校验不通过的细节,比如 `$fail['type']=='length equal'` 是说明长度校验不匹配

`$fail['type']` 可以是 `length equal`(长度不匹配)、`empty`(为空)、`length less`(长度小于)、`length greater`(长度大于)、`range equal`(只能等于)、`range less`(不能小于范围约定)、`range greater`(不能大于范围约定)

`$fail['exp']` 是表示规则预期的值


## 校验文件&校验上传文件

> `Validator` 可以直接校验文件，例如文件类型、扩展名、mime、大小，以及图片宽高和真实图片类型。

示例:

```php
<?php
namespace app\index\controller;
class test{
	public function file($req)
    {
        $files = $req->file();

        if ($files) {
            
            try {
                $v = validator();
                /* 一条规则校验全部上传的文件
                $files = $v->check($files,['rule'=>'file','name'=>'上传文件','options'=>['size'=>10240000,'ext'=>['jpg'],'mime'=>['image/jpeg'],'image'=>[null,null,null]]]);
                */

                //不同的上传name不同的校验规则
                $files = $v->input($files,
                    [
                        'upload1'=>['rule'=>'file','name'=>'身份证正面','options'=>['size'=>10240000,'ext'=>['jpg'],'mime'=>['image/jpeg'],'image'=>true]],
                        'upload2'=>['rule'=>'file','name'=>'身份证反面','options'=>['size'=>10240000,'ext'=>['jpg'],'mime'=>['image/jpeg'],'image'=>true]],
                        'upload'=>['rule'=>'file','name'=>'头像组','options'=>['size'=>10240000,'ext'=>['jpg'],'mime'=>['image/jpeg'],'image'=>[1280,720,'jpg']]]
                    ]
                    
                );
                if(is_array($files)){
                    foreach($files as $name=>$file){
                        //多文件上传
                        if(is_array($file)){
                            foreach($file as $n=>$f){
                                $f->move(public_path().'/files/myfile['.$f->getUploadName().'].'.$f->getUploadExtension());
                            }
                        }else{
                            $file->move(public_path().'/files/myfile['.$file->getUploadName().'].'.$file->getUploadExtension());  
                        }
                    }
                }else{
                   $files->move(public_path().'/files/myfile['.$files->getUploadName().'].'.$files->getUploadExtension());
                }
            }catch(\Throwable $ex){
                return nl2br($ex->getMessage());
            }
            return $req->json(['code' => 0, 'msg' => 'upload success']);
        }
        
        return $req->json(['code' => 1, 'msg' => 'file not found']);
    }
}
?>
```

`options` 包括我们对文件校验的所有需求,`options.size` 是文件大小限制字节, `options.ext` 是文件扩展名需要在指定的扩展名列表里, `options.mime` 是文件的 mime 需要在指定的mime列表里,`options.image` 可以为 `true` 表示我们只想验证是否是图片, 为数组 就是 第一个参数是验证`宽`, 第二个参数验证`高`,第三个参数图片真实的类型,如果只想验证某一项或者某两项,其他设置为NULL即可  比如 `'image'=>[1280,null,null]` 表示只想验证是否`宽为1280`的图片。

`options.filter` 当前支持 `trim`、`htmlspecialchars`、`addslashes`、`strip_tags`。其中 `alpha`、`alnum`、`string`、`email`、`date`、`phone`、`ip`、`ipv6`、`domain`、`url`、`mac`、`chinese` 等字符串类规则都可以配合使用。



## 验证规则

>'rule'=>'{规则}'

### int

验证是否是整数

支持 范围设置 【`range`】  `['rule'=>'int','name'=>'id','range'=>23]` 表示id只能为23,  `['rule'=>'int','name'=>'id','range'=>[1,100]]` 表示ID必须大于等于1,小于等于100

支持 长度设置 【`len`】 `['rule'=>'int','name'=>'id','len'=>2]` 表示id长度只能为2位, `['rule'=>'int','name'=>'id','len'=>[1,3]]` 表示id长度只能是1-3位

支持 必要性检查设置 【`required`】 `['rule'=>'int','name'=>'id','required'=>false]` 表示只有 `$req->get('id')` 有传值时候才进行校验。无传值时不进行校验, 一般用于非必填字段的检查。


### float

验证是否是浮点数

支持 范围设置 【`range`】

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

### pint

验证是否是正整数

支持 范围设置 【`range`】

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

### npint

验证是否是非正整数(负整数和0)

支持 范围设置 【`range`】

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

### nint

验证是否是负整数

支持 范围设置 【`range`】

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

### nnint

验证是否是非负整数(正整数和0)

支持 范围设置 【`range`】

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

### pfloat

验证是否是正浮点数

支持 范围设置 【`range`】

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

### npfloat

验证是否是非正浮点数(负浮点数和0)

支持 范围设置 【`range`】

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

### nfloat

验证是否是负浮点数

支持 范围设置 【`range`】

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

### nnfloat

验证是否是非负浮点数(正浮点数和0)

支持 范围设置 【`range`】

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

### alpha

验证是否是纯字母

支持 长度设置 【`len`】 `['rule'=>'alpha','name'=>'用户名','len'=>6]` 表示用户名长度只能为6位, `['rule'=>'int','name'=>'id','len'=>[6,20]]` 表示用户名长度只能是6-20位

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`

1、`options.attach` 是排除规则,某些情况下例如我们需要验证是否是纯字母,比如想排斥`-`和`_`符号,比如让 `user_name-name`通过校验我们就可有定义 `options.attach`

`['rule'=>'alpha','name'=>'昵称','options'=>['attach'=>['-','_']]]`

2、`options.filter` 是字符过滤, 某些情况下例如我们不管用户输入什么,我们需要对用户数据的数据去除前后空格,就可以使用 `options.filter`

`['rule'=>'alpha','name'=>'昵称','options'=>['filter'=>'trim']]`

`options.filter` 支持 `trim`(去除字符串首尾处的空白字符（或者其他字符) 、 `htmlspecialchars`(将特殊字符转换为 HTML 实体)、 `addslashes`(使用反斜线引用字符串)、 `strip_tags`(从字符串中去除 HTML 和 PHP 标记)


### alnum
验证是否是字母数字组合

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`

### chinese
验证是否是中文

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`

### ip
验证是否是ip地址

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`


### ipv6
验证是否是ipv6地址

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`

### domain
验证是否是域名地址

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`

### email
验证是否是邮箱地址

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`

### url
验证是否是url地址

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`

### mac
验证是否是mac地址

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`

### string
验证是否是字符串

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`

### date
验证是否是字符串

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`、 `options.format`

1、`options.format` 是定义日期的格式化标准

`['rule'=>'date','name'=>'日期','options'=>['format'=>'Y-m-d H:i:s']]`  这就要求验证的数据必须满足格式 `Y-m-d H:i:s`  比如`2021-10-10 10:30:00`可以通过验证，`2021-10-10 10:30` 不能通过验证。


### phone
验证是否是手机号码

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.attach`、 `options.filter`

### file
验证是否是文件

支持 长度设置 【`len`】

支持 必要性检查设置 【`required`】

支持 自定义选项 【`options`】  `options.size`、 `options.ext`、 `options.mime`、 `options.image`

1、 `options.size` 限制文件的大小

`['rule'=>'file','name'=>'头像','options'=>['size'=>10485760]]` 不能大于 `10M`

2、 `options.ext` 限制文件的扩展名 (可以被伪造、但相对安全)

`['rule'=>'file','name'=>'头像','options'=>['ext'=>['jpg','png']]]` 文件扩展名只能为 `jpg`或`png`

3、 `options.mime` 限制文件的格式 (可以被伪造、不安全)

`['rule'=>'file','name'=>'头像','options'=>['mime'=>['image/jpeg','image/x-png']]]` 文件格式只能为 `image/jpeg`或`image/x-png`

4、 `options.image` 限制文件是否是图片 (绝对安全)

`options.image = true` 则代表验证文件是否是有效的图片

`['rule'=>'file','name'=>'头像','options'=>['image'=>true]]` 文件只能是图片形式

`options.image = array` 则代表验证图片的宽、高、和实际图片类型

`['rule'=>'file','name'=>'头像','options'=>['image'=>[236,null,null]]]` 图片宽必须为236

`['rule'=>'file','name'=>'头像','options'=>['image'=>[236,236,null]]]` 图片宽高必须为236

`['rule'=>'file','name'=>'头像','options'=>['image'=>[null,null,'gif']]]` 图片必须是gif格式

`options.image` 为数组时 第三个参数支持 `gif`、`jpg`、`png`、`swf`、`psd`、`bmp`、`tiff(intel byte order)`、`tiff(motorola byte order)`、`jpc`、`jp2`、`jpx`、`jb2`、`swc`、`iff`、`wbmp`、`xbm`、`webp`













