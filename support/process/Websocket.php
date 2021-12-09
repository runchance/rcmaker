<?php
namespace support\process;
class Websocket
{
    protected $_timer = null;
    protected $_work = null;
    protected $_type = 'workerman';
    public function __construct($type,$work,$timer){
        $this->_type = $type;
        $this->_timer = $timer;
        $this->_work = $work;
    }

    public function onHandshake($request,$response)
    {
        // websocket握手连接算法验证
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }
        $key = base64_encode(
            sha1(
                $request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
                true
            )
        );

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
        ];

        // WebSocket connection to 'ws://127.0.0.1:9502/'
        // failed: Error during WebSocket handshake:
        // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        $response->status(101);
        $response->end();
    }

    public function onConnect($connection,$fd=null, $reactor_id=0)
    {
        echo "onConnect\n";
    }



    public function onMessage($connection, $frame)
    {
        if($this->_type=='workerman'){
            $connection->send('Hello '.$frame);
        }
        if($this->_type=='swoole'){
            $connection->push($frame->fd, 'Hello ' . $frame->data);
        }
        return null;
        
    }

    public function onClose($connection)
    {
        echo "onClose\n";
    }

    public function handle($request,$ws){
        $ws->upgrade();
        while (true) {
            $frame = $ws->recv();
            if ($frame === '') {
                $ws->close();
                break;
            } else if ($frame === false) {
                echo 'errorCode: ' . swoole_last_error() . "\n";
                $ws->close();
                break;
            } else {
                if ($frame->data == 'close' || get_class($frame) === CloseFrame::class) {
                    $ws->close();
                    break;
                }
                $ws->push("Hello {$frame->data}!");
                $ws->push("How are you, {$frame->data}?");
            }
        }
    }
    
}
?>