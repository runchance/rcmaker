# rcmaker目录结构

```
.
├── apps                          多应用目录
│   ├── api                       API应用目录(演示应用目录可以删除)
│   │   └── controller            应用控制器目录
│   │       └── index.php         index控制器文件
│   ├── index                     INDEX应用目录(默认应用目录)
│   │   ├──controller             应用控制器目录
│   │   │  └── index.php          index控制器文件
│   │   └──model                  模型目录
│   │      ├── log.php            Log模型(演示模型文件,可以删除)
│   │      └── user.php           User模型(演示模型文件,可以删除)
│   ├── base.php                  基础函数库(用于框架函数的简写映射)
│   └── functions.php             用户自定义函数库 
│  
├── config                        配置目录
│   ├── app.php                   应用配置
│   ├── autoload.php              这里配置的文件会被自动加载
│   ├── banner.php                CLI启动Banner与进程列表配置
│   ├── bootstrap.php             进程启动时载入的回调类
│   ├── cache.php                 缓存组件配置文件
│   ├── db.php                    数据库配置
│   ├── exception.php             异常配置
│   ├── middleware.php            中间件配置
│   ├── process.php               自定义进程配置
│   ├── queue.php                 队列配置文件
│   ├── redis.php                 redis配置
│   ├── route.php                 路由配置
│   ├── session.php               session配置
│   ├── sms.php                   短信验证码组件配置文件
│   ├── swoole.php                CLI 模式下 Swoole 配置
│   ├── token.php                 令牌组件配置文件
│   ├── view.php                  视图配置
│   └── worker.php                CLI模式下workerman配置
│
├── public                        静态资源目录(fpm模式根目录)
│
├── runtime                       应用的运行时目录，需要可写权限
│
├── scripts                       传统兼容脚本目录（备选，随时可能删除；项目工具优先使用 interact）
│
├── support                       项目支持基础库
│   ├── exception                 异常相关
│   │   └── Handler.php           业务异常捕获处理类
│   │
│   ├── middleware                中间件目录
│   │   ├── AuthCheck.php         登录检查（演示文件）
│   │   ├── Hook.php              控制器勾子（演示文件）
│   │   └── StaticFile.php        静态文件中间件（演示文件）
│   │
│   ├── process                   子定义进程目录
│   │   ├── Crontab.php           定时器任务进程（演示示例文件）
│   │   ├── Http.php              独立HTTP|HTTPS协议服务进程（演示示例文件）
│   │   ├── Rpc.php               独立RPC服务进程（演示示例文件）
│   │   ├── Tcp.php               独立TCP协议服务进程（演示示例文件）
│   │   └── Websocket.php         Websocket协议服务进程（演示示例文件）
│   │
│   ├── queue                     队列消费者目录
│   │   └── MyMailSend.php        队列消费者类（演示示例文件）
│   │
│   └── service                   RPC服务目录
│       └── User.php              RPC User服务（演示示例文件）
│
├── tools                         项目部署演示、配置、工具类（仅供参考、可以删除,没有文件运用到运行时）
│
├── vendor                        composer安装的第三方类库目录
│
└── view                          视图模板目录
    └── index                     index应用视图模板目录
        ├── blade.blade.php       blade视图模板（演示示例文件）
        ├── index.html            原生视图模板（演示示例文件）
        ├── smarty.html           smarty视图模板（演示示例文件）
        ├── think.html            ThinkPHP视图模板（演示示例文件）
        ├── twig.html             twig视图模板（演示示例文件）
        └── upload.html           文件上传演示模板（演示示例文件）
```
