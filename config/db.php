<?php
return [
  //默认框架
  'default_frame' => rcEnv('db.default_frame','raw'),
  //默认数据库驱动
  'default' => rcEnv('db.default','mysql'),
  //数据库驱动列表
  'driver' => [
     'mysql' => [
        'host' => rcEnv('mysql.host','127.0.0.1'),
        'port' => rcEnv('mysql.port','3306'),
        'database' => rcEnv('mysql.database','test'),
        'username' => rcEnv('mysql.username',''),
        'password' => rcEnv('mysql.password',''),
        'charset' => rcEnv('mysql.charset',''),
        'prefix' => rcEnv('mysql.prefix',''),
        'options' => rcEnv('mysql.options',[]),
        
        
     ],
     'sqlite'=>[
        'database' => rcEnv('sqlite.database',BASE_PATH.'/RCMAKER.db'),
        'prefix' => rcEnv('sqlite.prefix',''),
        'username' => rcEnv('sqlite.username',''),
        'password'=>rcEnv('sqlite.password',''),
        'prefix' => rcEnv('sqlite.prefix',''),
        'options' => rcEnv('sqlite.options',[])
     ],
     'pgsql'=>[
        'host' => rcEnv('pgsql.host','127.0.0.1'),
        'port' => rcEnv('pgsql.port','5432'),
        'database' => rcEnv('pgsql.database',''),
        'username' => rcEnv('pgsql.username',''),
        'password' => rcEnv('pgsql.password',''),
        'prefix' => rcEnv('pgsql.prefix',''),
        'options' => rcEnv('pgsql.options',[])
     ],
      'mongodb' => [
        'host' => rcEnv('mongodb.host','127.0.0.1'),
        'port' =>  rcEnv('mongodb.port','27017'),
        'database' => rcEnv('mongodb.database','test'),
        'username' => rcEnv('mongodb.username',''),
        'password' => rcEnv('mongodb.password',''),
        'prefix' => rcEnv('mongodb.prefix',''),
        'options' => rcEnv('mongodb.options',[])
      ],
     'sqlsrv'=>[
        'host' => rcEnv('sqlsrv.host','localhost'),
        'port' => rcEnv('sqlsrv.port','1433'),
        'database' => rcEnv('sqlsrv.database',''),
        'username' => rcEnv('sqlsrv.username',''),
        'password' => rcEnv('sqlsrv.password',''),
        'prefix' => rcEnv('sqlsrv.prefix',''),
        'options' => rcEnv('sqlsrv.options',[])
        
     ],
     'oracle'=>[
        'host' => rcEnv('oracle.host','localhost'),
        'port' => rcEnv('oracle.port','1521'),
        'database' => rcEnv('oracle.database',''),
        'username' => rcEnv('oracle.username',''),
        'password' => rcEnv('oracle.password',''),
        'charset' => rcEnv('oracle.charset','utf8'),
        'prefix' => rcEnv('oracle.prefix',''),
        'options' => rcEnv('oracle.options',[])
        
     ],
  ]
];
?>