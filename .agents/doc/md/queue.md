# 队列与消费

## 简介

rcmaker 集成了队列投递与消费能力，当前内置的是基于 Redis 的队列实现。

- 生产端通过助手函数 `queue()` 投递消息。
- cli 模式下可以根据配置自动启动消费进程。
- 当前支持的连接类型为 `redis` 和 `redisCluster`。

如果你需要的是 RabbitMQ、Kafka 之类的独立消息系统，这页文档不适用。


## 配置文件

配置文件在 `config/queue.php`，示例如下：

```php
<?php
return [
	//队列设置
	'enable' => false, //是否开启redis队列
    'connection' => [
    	'default' => [
    		'type'=>'redis',
	        'host' => '127.0.0.1',
	        'port' => 6379,
	        'expire' => 0,
	        'queue' => [     
	        	'prefix' => '', // key 前缀    
	            'max_attempts'  => 5, // 消费失败后，重试次数
	            'retry_seconds' => 5, // 重试间隔，单位秒
	        ]
	    ]
    ],
    //消费进程设置
    'consumer_process'=>[
    	'RC_consumer'  => [
	        'handler'     => RC\Helper\Process\QueueConsumer::class,
	        'count'       => 8, // 可以设置多进程同时消费
	        'bootstrap' => [
	        	RC\Helper\Redis\Raw::class //消费进程需要同时加载redis
	        ],
	        'constructor' => [
	            // 消费者类目录
	            'consumer_dir' => BASE_PATH . '/support/queue'
	        ]
	    ]
    ],
    
];
```

其中：

- `enable` 为 `true` 时，cli 模式下会自动把 `consumer_process` 中定义的消费进程并入启动流程。
- `connection` 为连接配置。
- `consumer_process` 为消费进程配置。

### connection 说明

- `type`：当前支持 `redis`、`redisCluster`。
- `host`：当 `type=redis` 时为单机地址；当 `type=redisCluster` 时为地址数组。
- `port`：单机 Redis 端口。
- `queue.prefix`：队列 key 前缀。
- `queue.max_attempts`：消费失败后的最大重试次数。
- `queue.retry_seconds`：重试基础间隔秒数。

你也可以继续配置其他 Redis 连接参数，例如：`timeout`、`read_timeout`、`persistent`、`password`、`database`。

### consumer_process 说明

`consumer_process` 用于定义队列消费进程。常用参数有：

- `handler`：通常使用 `RC\Helper\Process\QueueConsumer::class`。
- `count`：消费进程数量。
- `bootstrap`：消费进程启动时额外加载的类。默认场景通常需要包含 `RC\Helper\Redis\Raw::class`。
- `constructor.consumer_dir`：消费者类目录。

框架会递归扫描 `consumer_dir` 下的 PHP 文件，并按文件相对路径推导类名。因此消费者类的命名空间需要和目录结构保持一致。

### 消费失败与重试

如果消费失败，也就是消费者 `handle()` 中抛出了异常或 `Error`，消息会进入延迟队列等待重试。

重试次数通过 `max_attempts` 控制，重试间隔按当前尝试次数线性递增：

- 第 1 次重试：`1 * retry_seconds`
- 第 2 次重试：`2 * retry_seconds`
- 第 3 次重试：`3 * retry_seconds`

以此类推，直到超过 `max_attempts`。

超过最大重试次数后，消息会进入失败队列：

```text
{prefix}{redis-queue}-failed
```

其中 `prefix` 就是 `queue.prefix` 配置值。

## 向队列投递消息

可以使用助手函数 `queue()` 获取队列客户端。

```php
<?php
namespace app\index\controller;
class test{

    public function queueSend($req){
         // 队列名
        $queue = 'send-mail';
        // 数据，可以直接传数组，无需序列化
        $data = ['to' => 'someBody@mail.com', 'content' => 'hello'];
        // 投递消息
        queue()->send($queue,$data);
        
        /*
        queue()->send($queue,$data,60); //60秒后延时投递消息
        */
        return 'redis queue send test ok';
    }
}
```

常规同步投递成功时通常不返回数据；如果底层 Redis 调用失败，调用侧应按实际运行结果进行异常处理与验证。

也可以传入延迟秒数：

```php
queue()->send($queue, $data, 60);
```

## 在其他项目投递

在非 rcmaker 环境下，只要消息结构保持一致，也可以直接按 Redis key 约定投递。

```php
<?php
namespace app\index\controller;
class test{

    private function redis_queue_send($redis, $queue, $data, $delay = 0) {
        $queue_waiting = '{redis-queue}-waiting';
        $queue_delay = '{redis-queue}-delayed';
        $now = time();
        $package_str = json_encode([
            'id'       => rand(),
            'time'     => $now,
            'delay'    => $delay,
            'attempts' => 0,
            'queue'    => $queue,
            'data'     => $data
        ]);
        if ($delay) {
            return $redis->zAdd($queue_delay, $now + $delay, $package_str);
        }
        return $redis->lPush($queue_waiting.$queue, $package_str);
    }

    public function queueSend($req){
         // 队列名
        $queue = 'send-mail';
        // 数据，可以直接传数组，无需序列化
        $data = ['to' => 'someBody@mail.com', 'content' => 'hello'];
        //实例化redis 如果在非rcmaker框架 可以自己实现  例如 【$redis = new Redis;  $redis->connect('127.0.0.1', 6379);】
        $redis = redis();
        // 投递消息
        $send = $this->redis_queue_send($redis,$queue,$data,0);
        return $send ? '投递成功' : '投递失败';
    }
}
```

## 消费

消费者目录由 `config/queue.php` 中 `consumer_process[*].constructor.consumer_dir` 指定。

例如新建消费者处理类 `./support/queue/MyMailSend.php`：

```php
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
        var_export($data); // 输出 ['to' => 'someBody@mail.com', 'content' => 'hello']
    }
}
```

说明：

- `queue` 表示该消费者订阅的队列名。
- `connection` 表示使用哪个连接，默认是 `default`。
- `worker_id` 会在消费进程启动后由框架写入。
- `handle($data)` 直接收到反序列化后的消息数据。

当前消费流程相当于自动 ack。只有 `handle()` 正常执行完成，消息才算消费成功；如果抛出异常或 `Error`，当前消息会进入重试或失败流程。


## 根据适用场景配置多个消费进程

有时候我们需要根据队列处理的任务场景不同分配不同的消费消费进程进行处理，例如我们处理耗时任务的进程需要和一般消费比较快的进程分开（避免耗时消费影响常规消费），我们就可以设置不同的消费进程应对此问题

例如修改配置文件 `config/queue.php`：
```php
    ...其他配置省略参考上文

 //消费进程设置
    'consumer_process'=>[
    	'RC_consumer_slow'  => [ //开启4个消费线程处理耗时任务
	        'handler'     => RC\Helper\Process\QueueConsumer::class,
	        'count'       => 4, // 可以设置多进程同时消费
	        'bootstrap' => [
	        	RC\Helper\Redis\Raw::class //消费进程需要同时加载redis
	        ],
	        'constructor' => [
	            // 消费者类目录
	            'consumer_dir' => BASE_PATH . '/support/queue/slow'
	        ]
	    ],
	    'RC_consumer_fast'  => [ //开启8个消费线程处理常规任务
	        'handler'     => RC\Helper\Process\QueueConsumer::class,
	        'count'       => 8, // 可以设置多进程同时消费
	        'bootstrap' => [
	        	RC\Helper\Redis\Raw::class //消费进程需要同时加载redis
	        ],
	        'constructor' => [
	            // 消费者类目录
	            'consumer_dir' => BASE_PATH . '/support/queue/fast'
	        ]
	    ]
    ]
```

如上设置后，可以把耗时消费者放入 `/support/queue/slow`，把常规消费者类放入 `/support/queue/fast`。

注意：如果 `consumer_dir` 目录不存在，消费进程会输出提示并直接返回，不会自动创建目录。



## 多连接队列配置

我们可以配置多个连接并切换连接投递消息

例如修改配置文件 `config/queue.php`：
```php
<?php
return [
	//队列设置
	'enable' => true, //是否开启redis队列
    'connection' => [
    	'default' => [
    		'type'=>'redis',
	        'host' => '127.0.0.1',
	        'port' => 6379,
	        'queue' => [     
	        	'prefix' => '', // key 前缀    
	            'max_attempts'  => 5, // 消费失败后，重试次数
	            'retry_seconds' => 5, // 重试间隔，单位秒
	        ]
	    ],
	    'other' => [
    		'type'=>'redis',
	        'host' => '127.0.0.1',
	        'port' => 6379,
	        'database'=>2,
	        'queue' => [     
	        	'prefix' => '', // key 前缀    
	            'max_attempts'  => 5, // 消费失败后，重试次数
	            'retry_seconds' => 5, // 重试间隔，单位秒
	        ]
	    ],
	    'redisCluster' => [
	    	'type'=>'redisCluster',
	        'host' => ['127.0.0.1:9000','127.0.0.1:9001','127.0.0.1:9002','127.0.0.1:9003','127.0.0.1:9004','127.0.0.1:9005'],
	        'timeout' => 2,
	        'expire' => 0,
	        'queue' => [     
	        	'prefix' => '', // key 前缀    
	            'max_attempts'  => 5, // 消费失败后，重试次数
	            'retry_seconds' => 5, // 重试间隔，单位秒
	        ]
	    ]
    ]
```

如上配置后，就有三个连接：`default`、`other`、`redisCluster`。

### 多连接队列投递消息
```php
queue()->send($queue,$data); //默认  `default`
queue('other')->send($queue,$data); //切换为 `other` 并投递消息
queue('redisCluster')->send($queue,$data); //切换为 `redisCluster` 并投递消息
```

### 多连接队列消费

```php
<?php
namespace support\queue;
class MyMailSend
{
    // 要消费的队列名
    public $queue = 'send-mail';

    // 连接名
	public $connection = 'other'; // 设置为 `other`，代表只消费 `queue('other')` 投递的消息

    public $worker_id = 0;

    // 消费
    public function handle($data)
    {
        // 无需反序列化
        var_export($data); // 输出 ['to' => 'someBody@mail.com', 'content' => 'hello']
    }
}
```

## 注意事项

- 队列消费依赖 cli 模式运行；普通 PHP-FPM 请求本身不会常驻消费消息。
- 消费者类目录和命名空间要对应，否则框架按路径推导类名时会找不到类。
- 消费者里的异常不要无声吞掉，否则失败重试机制不会生效。
- 如果消息体很大或投递频率很高，要同时评估 Redis 内存占用和失败队列堆积情况。
