# 监控器

rcmaker 内置了监控器组件 `stopwatch`，主要用于观察一段代码的执行耗时和内存占用。

rcmaker 在框架本身已经集成了 `stopwatch`，因此可以查看框架阶段和控制器阶段的统计信息。

说明：

- `time` 的单位是毫秒。
- 当前实现返回的是“事件总耗时”，不是“事件结束时刻”。
- `memory` 返回的是事件结束时记录到的内存占用值，更适合做观察和对比，不是严格意义上的内存增量。

1、监控框架本身(没有实际意义)

`./.env` 设置 `app.count = true` (一般情况不用开启)

```php
<?php
namespace app\index\controller;
class test{
    public function stopwatch($req){
       $frameStopWatch =  stopwatch('__frame__'); //获取框架监控数据
       $controllerStopWatch =  stopwatch('__controller__'); //获取控制器监控数据

       return $req->json(['__frame__'=>$frameStopWatch ,'__controller__'=>$controllerStopWatch]);
    }
}
?>
```

返回结构示例：

```php
[
    'time' => 12,     // 毫秒
    'memory' => 2097152, // 字节
]
```


2、监控控制器某段程序执行

```php
<?php
namespace app\index\controller;
use RC\Stopwatch;
class test{
    public function stopwatch($req){
       	
       	//开始自定义监测1
        Stopwatch::start('user_custom_stopwatch_1');

        // $curl->get('https://www.baidu.com/');

        // $baidu = $curl->response;
        $curl = curl();
        for($i=0;$i<10;$i++){
            
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER,FALSE);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST,0);
            $curl->get('https://www.qq.com/?q='.$i);
            $qq = $curl->response;
        }
        

        //结束自定义监测1
        $count1 = stopwatch('user_custom_stopwatch_1');
        $time1 = intval($count1['time'])/1000;
        $memory1 = intval($count1['memory'])/1024/1024;
        $res = ' <p> user_custom_stopwatch_1 load time is '. $time1.'s AND load memory is '.$memory1.'M';
        
        //开始自定义监测2
        Stopwatch::start('user_custom_stopwatch_2');
        
        for($i=0;$i<10;$i++){
            
            $curl->setOpt(CURLOPT_SSL_VERIFYPEER,FALSE);
            $curl->setOpt(CURLOPT_SSL_VERIFYHOST,0);
            $curl->get('https://www.baidu.com/?q='.$i);
            $baidu = $curl->response;
        }
        
        //结束自定义监测2
        $count2 = stopwatch('user_custom_stopwatch_2');
        $time2 = intval($count2['time'])/1000;
        $memory2 = intval($count2['memory'])/1024/1024;
        $res.= ' <p> user_custom_stopwatch_2 load time is '. $time2.'s AND load memory is '.$memory2.'M';

        return $res;
    }
}
?>
```

## 常用静态方法

当前常用入口有：

- `RC\Stopwatch::start($eventName)` 开始一个计时事件
- `RC\Stopwatch::lap($eventName)` 结束当前周期并立即开始下一周期
- `stopwatch($eventName)` 停止并读取统计结果

示例：

```php
use RC\Stopwatch;

Stopwatch::start('task');
// ...
$result = stopwatch('task');
```

## 注意事项

- `stopwatch($eventName)` 调用时会停止该事件并返回结果，因此它更适合“一次开始，一次读取”的用法。
- 如果你要分别统计多段逻辑，建议使用不同的事件名。
- `app.count = true` 主要用于观察框架和控制器阶段，生产环境通常不建议长期开启。

