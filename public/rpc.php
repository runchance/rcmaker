<?php
$client = stream_socket_client('tcp://127.0.0.1:8684');
$request = [
    'class'   => 'User',
    'method'  => 'get',
    'args'    => [1001], // 100 是 $uid
];
fwrite($client, json_encode($request)."\r\n");
$result = fgets($client, 10240000);
$result = json_decode($result, true);
print_r($result);