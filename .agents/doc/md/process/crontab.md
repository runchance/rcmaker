# 定时任务

`./config/process.php` 加入如下配置

```php
'RC_Crontab_Task'  => [
   'handler'  => support\process\Crontab::class,
],
```

新建进程处理类

`./support/process/Crontab.php`

```php
<?php
namespace support\process;
use RC\Helper\Crontab\Crontab as CrontabObj;
class Crontab
{


    protected $_timer = null;
    protected $_work = null;
    protected $_type = 'workerman';
    public function __construct($type,$work,$timer){
        $this->_type = $type;
        $this->_timer = $timer;
        $this->_work = $work;
        $this->start();
    }

    public function start(){
        $type = $this->_type;
        new CrontabObj('1 * * * * *',function(){
           echo date('Y-m-d H:i:s')."\n";
       
       },$type);

        new CrontabObj('5 * * * * *',function(){
           echo date('Y-m-d H:i:s')."\n";
       
       },$type);

       // // 每天的7点50执行，注意这里省略了秒位.
       // new CrontabObj('50 7 * * *',function(){
       //     echo date('Y-m-d H:i:s')."\n";

       // },$this->_type);
    }
}
?>
```


重启 `rcmaker`

每一分钟的第1秒和第5秒会输出时间,当然 你可以用它实际处理响应的业务
