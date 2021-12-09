<?php
namespace support\process;
class Tcp{
	protected $_timer = null;
    protected $_work = null;
    protected $_type = 'workerman';
    public function __construct($type,$work,$timer){
        $this->_type = $type;
        $this->_timer = $timer;
        $this->_work = $work;
    }

     public function onMessage($connection,$data){
       
       return $this->onReceive($connection,null,null,$data);
    }

    public function onReceive($server, $fd=null, $reactor_id=null, $data=null){

    	if($this->_type=='workerman'){
    		$server->send('hello ' . $data);
    	}
    	if($this->_type=='swoole'){
            if($fd===null){
                while (true) {
                    $data = $server->recv(10);
                     if ($data === '' || $data === false) {
                        $errCode = swoole_last_error();
                        $errMsg = socket_strerror($errCode);
                        echo "errCode: {$errCode}, errMsg: {$errMsg}\n";
                        $server->close();
                        break;
                    }
                    //发送数据
                    $server->send('hello: '.$data);
                }
                return null;
            }
    		$server->send($fd, 'hello: '.$data);
            $server->close($fd);
    	}
    	return null;
    }

    public function onClose($connection)
    {
        echo "onClose\n";
    }

    public function onConnect($connection,$fd=null)
    {
        echo "onConnect\n";
    }

    public function handle($connection){
        $this->onReceive($connection);
    }
}
?>