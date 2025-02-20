<?php 
return [
  //是否开启app进程,如果设置为false则不开启app进程,在cli模式下就只启动config/process定义的进程,适合做微服务和特殊任务
  'start_app' => rcEnv('app.start_app',true) ? true : false,
  //cli模式使用的框架,目前支持workerman和swoole[全局]
  'cli_frame' => rcEnv('app.cli_frame','workerman'),
  //CLI模式下是否开启日志[全局]
  'cli_log' => rcEnv('app.cli_log',false) ? true : false,
  //默认时区[全局]
  'default_timezone' => rcEnv('app.default_timezone','Asia/Shanghai'),
  //全局框架统计,启用后 可以用 stopwatch方法获取控制器运行时间 和 内存占用,建议生产环境不要开启[全局+app]
  'count' => rcEnv('app.count',false) ? true : false,
  //是否开启dubug模式,如果关闭，所有异常只显示配置error_msg,建议生产环境关闭[全局]
  'debug' => rcEnv('app.debug',false) ? true : false,
  //错误报告的级别[全局]
  'error_types'=>E_ALL &~E_NOTICE &~E_DEPRECATED,
  //PHP错误输出,关闭debug后调用显示[全局]
  'error_msg' => rcEnv('app.error_msg','page error!'),
  //是否开启自动寻址路由,开启后/index.php?a=app&c=controller&m=method将失效 由 /app/controller/method 替代[全局+app]
  'route' => rcEnv('app.route',true) ? true : false,
  //是否开启自定义路由,开启后会匹配/config/route.php里的规则[全局+app]
  'with_custom_route' => rcEnv('app.with_custom_route',false) ? true : false,
  //默认入口控制器(controller,method)[全局+app]
  'index' => rcEnv('app.index',['index','index']),
  //public路径，如果自定义改动请用绝对路径
  'public_path' => rcEnv('app.public_path',null),
  //runtime路径，如果自定义改动请用绝对路径
  'runtime_path' => rcEnv('app.public_path',null),
  //ssl路径，如果自定义改动请用绝对路径
  'runtime_path' => rcEnv('app.ssl_path',null),
  //独立APP设置
  /*
  'app' => [
    //APP名称 对应./apps/index,设置独立,可继承以上部分设置（index,with_custom_route,route,count），绑定域名请设置 domains
    'index' => [
      //单应用绑定域名,如果设置该项,则全局路由 /app/controller/action中的 app将失效，
      //只能用绑定域名的 http(s)://domain/controller/action
      "domains"=>['localhost','127.0.0.1'],
    ],
    'api' =>[
      "domains"=>['api.test.com'],
    ]
  ]
  */
];
?>