# HTTP 进程

如果该端口需要使用与主 APP 相同的路由、中间件、控制器、Session 和异常处理，请优先使用 [独立 APP 进程](/md/process.md#独立-app-进程)。下面的普通 HTTP 进程用于完全自行处理原始 HTTP 请求，不会自动进入主 APP 请求链。

`./config/process.php` 加入如下配置

```php
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
            RC\Helper\Db\Medoo::class,
        ]
    ],
```

新建进程处理类

`./support/process/Http.php`

```php
<?php
namespace support\process;
class Http{
	protected $_timer = null;
    protected $_work = null;
    protected $_type = 'workerman';
    public function __construct($type,$work,$timer){
        $this->_type = $type;
        $this->_timer = $timer;
        $this->_work = $work;
    }

    public function onMessage($connection,$request){

    	if($this->_type=='workerman'){
    		$connection->send('hello world');
    	}
    	if($this->_type=='swoole'){
    		$db = \simple_database(null);
    		$user = $db->table("user")->where('id',1)->find();
    		if($user){
    			$name = $user['name'];
    		}
    		
    		$connection->end('hello world');
    	}
    	return null;
    }

    public function onRequest($request, $response){
        return $this->onMessage($response,$request);
    }

    public function handle($request,$connection){
        return $this->onMessage($connection,$request);
    }
}
?>
```
重启 `rcmaker`

访问 `http://localhost:8681/` 显示 `hello world`
