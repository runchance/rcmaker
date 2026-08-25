# Text 进程

内置示例用 text 协议做了一个简易的 RPC 服务

`./config/process.php` 加入如下配置

```php
'RC_RPC'  => [
    'handler'  => support\process\Rpc::class,
    'listen'  => 'text://0.0.0.0:8684',
    'count'  => 8
],
```

新建进程处理类

`./support/process/Rpc.php`

```php
<?php
namespace support\process;
class Rpc
{
    protected $_timer = null;
    protected $_work = null;
    protected $_type = 'workerman';
    public function __construct($type,$work,$timer){
        $this->_type = $type;
        $this->_timer = $timer;
        $this->_work = $work;
    }
    public function handle($connection){
        $this->onReceive($connection);
    }

     public function onMessage($connection,$data){

        return $this->onReceive($connection,null,null,$data);
    }

    public function onReceive($server, $fd=null, $reactor_id=null, $data=null){

        if($this->_type=='workerman'){
            $server->send($this->rpcParse($data));
        }
        if($this->_type=='swoole'){
            if($fd===null){
                while (true) {
                    $data = $server->recv(10);
                    if ($data === '' || $data === false) {
                        $server->close();
                        break;
                    }
                    //发送数据
                    $server->send($this->rpcParse($data)."\r\n");
                    //$server->close();
                }
                
                return null;
            }
            $server->send($fd, $this->rpcParse($data)."\r\n");
            //$server->close($fd);
        }
        return null;
    }

    protected function rpcParse($data)
    {
        static $instances = [];
        $data = json_decode($data, true);
        // 生产环境请改成白名单映射，避免客户端直接指定任意类和方法。
        $class = 'support\\service\\'.$data['class'];
        $method = $data['method'];
        $args = $data['args'];
        if (!isset($instances[$class])) {
            $instances[$class] = new $class; // 缓存类实例，避免重复初始化
        }
        return call_user_func_array([$instances[$class], $method], $args);
    }
}
```


新建RPC服务类

`./support/service/User.php`

```php
<?php
namespace support\service;
class User
{
    public function get($uid)
    {
        return json_encode([
            'uid'  => $uid,
            'name' => 'tom',
            'email' => 'tom@gmail.com'
        ]);
    }
}
```

控制器访问RPC

`./apps/index/controller/test.php`

```php
<?php
namespace app\index\controller;
class test{
    public function rpc($req)
    {
        $client = stream_socket_client('tcp://127.0.0.1:8684');
		$request = [
		    'class'   => 'User',
		    'method'  => 'get',
		    'args'    => [1001], // 1001 是 $uid
		];
		fwrite($client, json_encode($request)."\r\n");
		$result = fgets($client, 10240000);
		
		$result = json_decode($result, true);
		return $req->json($result);
    }
}
?>
```

重启 `rcmaker`

访问 `http://localhost:8680/test/rpc` 显示 `{"uid":1001,"name":"tom","email":"tom@gmail.com"}`

> 安全提示：上面的 RPC 示例用于说明 text 协议通信方式。生产环境不要直接信任客户端传入的 `class` 和 `method`，应使用白名单映射允许调用的服务和方法。
