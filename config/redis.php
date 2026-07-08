<?php
return [
	'default_frame' => rcEnv('redis.default_frame', 'raw'),
    'default' => [
        'type' =>rcEnv('redis.type', ''),
        'host' => rcEnv('redis.host', '127.0.0.1'),
        'password' => rcEnv('redis.password', ''),
        'port' => rcEnv('redis.port', 6379),
        'database' => rcEnv('redis.database', 0),
        'timeout' => rcEnv('redis.timeout', 5),
        'retryInterval' => rcEnv('redis.retryInterval', 0),
        'readTimeout' => rcEnv('redis.readTimeout', -1),
        'persistent' => rcEnv('redis.persistent', false),
        'select' => rcEnv('redis.select', 'x'),
        'prefix' => rcEnv('redis.prefix', ''),
    ],
    'database' => [
        'type' =>rcEnv('redis-1.type', ''),
        'host' => rcEnv('redis-1.host', '127.0.0.1'),
        'password' => rcEnv('redis-1.password', null),
        'port' => rcEnv('redis-1.port', 6379),
        'database' => rcEnv('redis-1.database', 2),
        'timeout' => rcEnv('redis-1.timeout', 5),
        'retryInterval' => rcEnv('redis-1.retryInterval', 0),
        'readTimeout' => rcEnv('redis-1.readTimeout', -1),
        'persistent' => rcEnv('redis-1.persistent', false),
        'select' => rcEnv('redis-1.select', 'x'),
        'prefix' => rcEnv('redis-1.prefix', ''),
    ],
    //集群配置 集群配置必须带type=>'cluster'
    'cluster'=>[
        'type' => rcEnv('redis-cluster.type', 'cluster'),
        'host'    => rcEnv('redis-cluster.host', []),
        'timeout' => rcEnv('redis-cluster.timeout', 2),
        'readTimeout' => rcEnv('redis-cluster.readTimeout', 2),
        'persistent' => rcEnv('redis-cluster.persistent', false),
        'password'    => rcEnv('redis-cluster.password', null),
        'prefix' => rcEnv('redis-cluster.prefix', ''),
    ]
];
