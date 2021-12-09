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
        $class = 'support\\service\\'.$data['class'];
        $method = $data['method'];
        $args = $data['args'];
        if (!isset($instances[$class])) {
            $instances[$class] = new $class; // 缓存类实例，避免重复初始化
        }
        return call_user_func_array([$instances[$class], $method], $args);
    }
}