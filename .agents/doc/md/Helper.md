# 助手函数

> 注意：绝大部分助手函数需要传入 `Request` 对象，建议直接使用 `Request` 的助手函数映射写法，参考 [助手函数映射](md/request.md?id=助手函数映射)。本篇主要说明全局助手函数，因此示例以助手函数原样调用为主。

## 下载文件
`download($request,$file,$download_name='')`  简写映射函数 `D(...)`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function download($req){
        return download($req,public_path() . '/1.jpg','demo.jpg');
        /*or 
        return $req->download(public_path() . '/1.jpg','demo.jpg');
        */
    }

}
?>
```

## 获取runtime路径
`runtime_path()`  简写映射函数 `无`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function hello($req)
    {
        $runtime_path = runtime_path();
        return $runtime_path;
    }
}
```

## 获取public路径
`public_path()`  简写映射函数 `无`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function hello($req)
    {
        $public_path = public_path();
        return $public_path;
    }
}
```

## 获取视图路径
`view_path()`  简写映射函数 `无`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function hello($req)
    {
        $view_path = view_path();
        return $view_path;
    }
}
```

## 格式化文件尺寸
`getFilesize($size,$decimals=2)`  简写映射函数 `无`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function hello($req)
    {
        $file_size = 18400000;
        return getFilesize($file_size);
    }
}
```

## 返回 session 管理对象
`sessions($request,$key = null, $default = null)`  简写映射函数 `S(...)`

示例

```php
<?php
namespace app\index\controller;
class index{
     //单独设置session
	 public function session_set($req){
        $name = $req->get('name');
     
        if($name){
          S($req,['name'=>$name]);
        }
        return S($req,'name','tom');
     }

     //获取所有session值
     public function session_get_all($req){
        $session = S($req);
        return json($req,$session);
     }    
}
?>
```
关于session的更多操作 请参看基础功能 [session管理](md/session.md)

## 设置cookie
`setcookies($request,$keyvalue = [], $expires = 0, $path = '', $domain = '', $secure = false, $http_only = false)`  简写映射函数 `SC(...)`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function setcookie($req){
        SC($req,['name1'=>'Tom','name2'=>'Jack']);
        return 'ok';
    }
}
?>
```
关于cookie的更多操作 请参看基础功能 [cookie管理](md/cookie.md)



## 获取 Response 响应对象
`response($request,$body = '', $status = 200, $headers = array())`  简写映射函数 `R(...)`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function index($req){
        $response = R($req,'hello rcmaker');
        return $response;
    }
}
?>
```
关于响应对象的更多操作 请参看基础功能 [响应对象](md/response.md)

## JSON（返回 Response 响应对象）
`json($request,$data, $options = JSON_UNESCAPED_UNICODE)`  简写映射函数 `无`

示例

```php
<?php
namespace app\index\controller;
class index{
	public function index($req){
		$user = [
            'uid'  => 100,
            'name' => 'tom',
            'email' => 'tom@gmail.com'
        ];
        return json($req,$user);
    }}
?>
```

## XML（返回 Response 响应对象）
`xml($request,$xml)`  简写映射函数 `无`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function hello($req)
    {
        $xml = <<<XML
               <?xml version='1.0' standalone='yes'?>
               <values>
                   <truevalue>1</truevalue>
                   <falsevalue>0</falsevalue>
               </values>
               XML;
        return xml($req,$xml);
    }
}
```

## jsonp（返回 Response 响应对象）
`jsonp($request,$data, $callback_name = 'callback')`  简写映射函数 `无`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function hello($req)
    {
        $user = [
            'uid'  => 100,
            'name' => 'tom',
            'email' => 'tom@gmail.com'
        ];
        return jsonp($req,$user,'callback');
    }
}
```

## 页面重定向（返回 Response 响应对象）
`redirect($request,$location, $status = 302, $headers = [])`  简写映射函数 `无`

示例

```php
<?php
namespace app\index\controller;
class index{
	public function index($req){
        return 'Hello rcmaker';
    }
    public function user($req){
        return redirect($req,'/');
    }
}
?>
```

## 获取队列客户端
`queue($connection = 'default')`  简写映射函数 `无`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function queueSend($req){
        queue()->send('send-mail', ['to' => 'someBody@mail.com']);
        return 'ok';
    }
}
?>
```

关于队列的更多操作请参看内置组件 [队列与消费](md/queue.md)

## 获取cookie
`getcookies($request,$key = null, $default = null)`  简写映射函数 `GC(...)`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function getcookie($req){
        $name = GC($req,'name1','nobody');
        return $name;
    }

    //获取全部cookie
    public function getcookies($req){
        $cookies = GC($req);
        return json($req,$cookies);
    }
}
?>
```
关于cookie的更多操作 请参看基础功能 [cookie管理](md/cookie.md)

## 返回 验证码 组件对象
`captcha($request, $name = '', $connect = 'default', $closure = null,$cache=null)`  简写映射函数 `C(...)`


## 返回 验证码校验 组件对象
`captchaCheck($request,$name = '', $value = '',$connect = 'default', $cache=null)`  简写映射函数 `CC(...)`

```php
<?php
namespace app\index\controller;
class index{
    public function captcha($req)
    {
      $key = 'user_id_10000';
      $captcha = $req->captcha($key);
      return $captcha;
    }

    public function captcha_check($req)
    {
      $key = 'user_id_10000';
      $value = $req->get('value');
      return $req->captchaCheck($key,$value) ? 'ok' : 'fail';
    }
}
?>
```
关于验证码的更多操作 请参看内置组件 [验证码](md/module/captcha.md)

## 返回 缓存 组件对象
`cache($engine='',$type=null,$id=1,$class=null,$config=null)` 

```php
namespace app\index\controller;
class test{
    public function cache($req){
        $cache = cache();
        $cache->set('foo', 'bar'); //如果设置成功返回true，否则返回false。
        $value = $cache->get('foo');
        return $value;
    }
}
```
关于缓存组件的更多操作 请参看内置组件 [缓存](md/module/cache.md)


## 返回 token 组件对象
`token($request,$guard = null,$cache = null)`  简写映射函数 `T($request,$guard = null,$cache = null)`

```php
namespace app\index\controller;
class test{
    public function token_set($req){
        $token = $req->token();
        $data = [
            'key'=>'user_id_123',
            'user_id'=>123,
            'user_name'=>'rcmaker'
        ];
        $access_token = $token->set($data);
        return $req->json($access_token);
    }
}
```
关于token组件的更多操作 请参看内置组件 [token](md/module/token.md)


## 返回 短信验证码 组件对象
`sms($request,$method = 'get',$config = array(),$cache = null)` 

```php
namespace app\index\controller;
class test{
    public function getSmsCode($req){
        $sms = sms($req,'get');
        $code = $sms->create();
        return $code;
    }
}
```
关于短信验证码组件的更多操作 请参看内置组件 [短信验证码](md/module/sms.md)

## 返回 邮件发送 组件对象
`mailer($connect=null)`  简写映射函数 `ML($connect=null)`

```php
namespace app\index\controller;
class test{
    public function sendMail($req){
        $mail = mailer();
        $send = $mail->from('sendTest@gmail.com','tom')
        ->to('receiveTest@gmail.com')
        ->subject('RC Mailer Test')
        ->isHtml(true)
        ->msgHTML(file_get_contents(public_path().'/mailertest.html'), public_path())
        ->send();
        if (!$send) {
            return 'Mailer Error: ' . $mail->e();
        } else {
            return 'Message sent!';
        }
    }
}
```
关于邮件发送组件的更多操作 请参看内置组件 [邮件发送](md/module/mailer.md)

## 返回 `验证器` 组件对象
`validator()`  简写映射函数 `VD()`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function validator($req){
       $v = validator();
       $msg = '';
       $data = $v->input($req->get(), [
           'id' => ['rule'=>'int','name'=>'标识符'],
           'email' => ['rule'=>'email','len'=>[1,120],'name'=>'邮箱','options'=>['filter'=>'trim']],
           'name' => ['rule'=>'alnum','name'=>'昵称','options'=>['attach'=>['-','_']]],
       ]);
       if(!$data){
         return json($req,$msg);
       }
       return json($req,$data);
    }
}
?>
```

## 返回 EXCEL 操作对象
`xlsx()`  简写映射函数 `X()`

示例

```php
<?php
namespace app\index\controller;
class index{
     public function excel($req){
        $writer = X();
        $header = array(
          'created'=>'date',
          'product_id'=>'integer',
          'quantity'=>'#,##0',
          'amount'=>'price',
          'description'=>'string',
          'tax'=>'[$$-1009]#,##0.00;[RED]-[$$-1009]#,##0.00',
        );
        $data = array(
            array('2015-01-01',873,1,'44.00','misc','=D2*0.05'),
            array('2015-01-12',324,2,'88.00','none','=D3*0.05'),
        );

        $writer->writeSheetHeader('Sheet1', $header);
        foreach($data as $row){
            $writer->writeSheetRow('Sheet1', $row);
        }
        $filename = public_path().'/example1.xlsx';
        $writer->writeToFile($filename);
        return D($req,$filename,'example1.xlsx');
    }
}
?>
```
关于excel的更多操作 请参看内置组件 [excel](md/module/excel.md)

## 返回 二维码 操作对象
`qrcode($request,$text,$format = 'png',$outfile = false, $level = 0, $size = 3, $margin = 4,$saveandprint=false)`  简写映射函数 `Q(...)`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function Qrcode($req){
        $qrcode = Q($req,'Hello RCmaker','png');

        return $qrcode;
    }
}
?>
```
关于二维码的更多操作 请参看内置组件 [二维码](md/module/qrcode.md)

## 返回 pdf 操作对象
`pdf($request,$config=['orientation'=>'P','unit'=>'mm','format'=>'A4','unicode'=>true,'encoding'=>'UTF-8','diskcache'=>false])`  简写映射函数 `P(...)`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function pdf($req){
        $pdf = P($req);
        
        $pdf->SetCreator('vsa');
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('TCPDF Example 002');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

        // remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('times', 'BI', 20);
        $pdf->AddPage();
        $txt = 'Hello RCmaker';
        $pdf->Write(0, $txt, '', 0, 'C', true, 0, false, false, 0);
        return $pdf->Output(public_path().'/example_002.pdf','FI');
    }
}
?>
```
关于pdf的更多操作 请参看内置组件 [pdf](md/module/pdf.md)

## 获取环境变量
`rcEnv(string $name = null, $default = null)`  简写映射函数 `无`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function getBootstrap($req){
        $bootstrap = rcEnv('bootstrap.load',null);
        return json($req,$bootstrap);
    }
}
?>
```

关于验证器的操作请参看内置组件 [验证器](md/module/validation.md)。

## 返回 `自动表单管理` 组件对象
`autoForm($request,$vars)`  简写映射函数 `AF($request,$vars)`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function autoform($req){
        $vars = [
            "type" => "paginate",
            "table"=> "users",
            "method" => "get",
            "name"=>"用户",
            "group" => 'parent',
            "data" => [
                "parent"=>['rule'=>'pint','name'=>'父ID'],
                "name"=>['name'=>'名称','len'=>[1,50]],
            ],
            "query" =>[
                ['name','like%']
            ] 
        ];
        $autoForm = AF($req,$vars);
        $info = $autoForm->handle();
        return json($req,['code' => 0, 'msg' => 'ok', 'info'=>$info]);
    }

}
?>
```

关于自动表单的操作请参看内置组件 [自动表单](md/module/autoform.md)。


## 获取模型实例 
`model($request,$model=null,$app=null,$constructor=[])`  简写映射函数 `M(...)`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function autoform($req){
        $user = model($req,'user');
        /* or
        $user = model($req, app\index\model\user::class);
         */
        $userinfo = $user->get_userinfo(12);
        return json($req,$userinfo);
    }

}
?>
```
## 获取redis操作对象 
`redis($engine='',$type=null,$id=1,$class=null,$clusterclass=null,$config=null)`  简写映射函数 `RD(...)`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function redis($req){
        $rds = RD();
        $rds->set('foo','01234');
        //$rds->delete('foo');
        $value = $rds->get('foo');
        return $value;
    }

}
?>
```

关于 Redis 操作对象的更多说明请参看 [Redis](md/db/redis.md)。


## 获取database操作对象 
`database($engine='',$type=null,$id=1,$class=null,$config=null,$support=null)`  简写映射函数 `DB(...)`

示例

```php
<?php
namespace app\index\controller;
class index{
    public function db($req){
        $db = DB(); //默认不填参数则使用配置文件db.default_frame定义的medoo框架
        $user = $db->get('user','*',['id'=>1]);
        return $req->json($user);
    }

}
?>
```

关于 database 操作对象的更多说明请参看 [支持库及用法](md/db/frame.md)。


## 获取simple database操作对象 
`simple_database($request,...$config)`  简写映射函数 `SDB($request,...)`

示例

```php
<?php
namespace app\index\controller;

class index{
    public function index($req)
    {
        $db = SDB($req);
        $user = $db->table('user')->select();
        /*OR
           $user = $db->t('user')->s(); 
        */
        return $req->json($user);
    }
}
?>
```

关于 simple database 操作对象的更多说明请参看 [SDB](md/db/sdb.md)。

