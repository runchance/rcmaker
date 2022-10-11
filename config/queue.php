<?php
return [
	//队列设置
	'enable' => true, //是否开启redis队列
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
	    ],
	    'other' => [
    		'type'=>'redis',
	        'host' => '127.0.0.1',
	        'port' => 6379,
	        'expire' => 0,
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