<?php
namespace support\service;
class User
{
    public function get($uid)
    {
        return json_encode([
            'uid'  => $uid,
            'name' => 'tom',
            'email' => 'tom@gmail.com'
        ]);
    }
}