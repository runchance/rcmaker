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