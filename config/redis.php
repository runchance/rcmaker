<?php
return [
	'default_frame' => rcEnv('redis.default_frame', 'raw'),
    'default' => [
        'type' =>rcEnv('redis.type', ''),
        'host' => rcEnv('redis.host', '127.0.0.1'),
        'password' => rcEnv('redis.password', null),
        'port' => rcEnv('redis.port', 6379),
        'database' => rcEnv('redis.database', 0),
    ],
    'database' => [
        'type' =>rcEnv('redis-1.type', ''),
        'host' => rcEnv('redis-1.host', '127.0.0.1'),
        'password' => rcEnv('redis-1.password', null),
        'port' => rcEnv('redis-1.port', 6379),
        'database' => rcEnv('redis-1.database', 2),
    ],
    //集群配置 集群配置必须带type=>'cluster'
    'cluster'=>[
        'type' => rcEnv('redis-cluster.type', 'cluster'),
        'host'    => rcEnv('redis-cluster.host', []),
        'timeout' => rcEnv('redis-cluster.timeout', 2),
        'password'    => rcEnv('redis-cluster.timeout', null),
    ]
];
