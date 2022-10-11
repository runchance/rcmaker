<?php
namespace support\queue;
class MyMailSend
{
    // 要消费的队列名
    public $queue = 'send-mail';

    // 连接名
    public $connection = 'default';

    public $worker_id = 0;

    // 消费
    public function handle($data)
    {
        // 无需反序列化
        var_export($data); // 输出 ['to' => 'tom@gmail.com', 'content' => 'hello']
    }
}