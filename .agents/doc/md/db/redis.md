# redis

rcmaker 提供了内置的 Redis 接入层，但使用前仍然需要先给当前 PHP 运行环境安装 `redis` 扩展。

> 使用 Redis 之前，必须先确认当前运行环境已经装好 `redis` 扩展。请按实际运行环境检查，例如 PHP-FPM 场景检查 FPM，cli 模式检查 CLI。

```bash
php -m | findstr redis
```

Redis 扩展安装请参考 [redis 官方包](http://pecl.php.net/package/redis)。

rcmaker 当前内置了两种 Redis 引擎：

- `raw`：直接基于 `ext-redis` 的 `Redis` / `RedisCluster`
- `mix`：框架封装的连接对象，接口风格接近 `ext-redis`，支持自动重连和连接池能力

## redis 配置

默认情况下，`./config/redis.php` 会读取 `./.env` 中的 Redis 配置。

如果你使用 `raw` 引擎，请在 bootstrap 中载入 `RC\Helper\Redis\Raw`；如果你使用 `mix` 引擎，请载入 `RC\Helper\Redis\Mix`。

```
[bootstrap]
load[] = RC\Helper\Redis\Raw
```

```
[bootstrap]
load[] = RC\Helper\Redis\Mix
```

> `default_frame` 不是 Redis 的“数据库类型”，而是 Redis 的客户端引擎，当前支持 `raw` 和 `mix`。

```
[redis]
default_frame = raw
type = 
host = 127.0.0.1
password = 
port = 6379
database = 0
timeout = 5
retryInterval = 0
readTimeout = -1
persistent = false
select = x
prefix = 
```

Redis 配置和 database 配置类似，既可以配置多个连接，也可以配置集群。

字段说明：

- `default_frame`：Redis 引擎，支持 `raw` / `mix`
- `type`：普通连接留空，集群连接填 `cluster`
- `database`：普通 Redis 连接使用的逻辑库编号
- `timeout`：连接超时秒数
- `retryInterval`：重连间隔，仅部分连接器会使用
- `readTimeout`：读超时秒数
- `persistent`：是否使用持久连接，仅 `raw` 引擎普通连接会使用
- `select`：持久连接标识，用于区分不同持久连接池
- `prefix`：键名前缀

至此 Redis 配置完毕。


## redis 示例 快速开始

```php
<?php
namespace app\index\controller;
use app\index\model\user;
class test{
	public function redis1($req)
    {
        $redis = RD();
        $redis->set('foo', 'bar');
        $value = $redis->get('foo');
        return $value;
    }
}
?>
```

`RD()` 是 `redis()` 的别名，因此下面两种写法等价：

```php
$redis = RD();
$redis = redis();
```

## redis 接口
```php
/*1.字符串String，键值对，创建更新同操作*/
$redis->setOption(Redis::OPT_PREFIX,'hf_');//设置表前缀为hf_
$redis->set('key',1);//设置key=aa value=1 [true]
$redis->setBit($key, $offset, $value);//按位设置key
$redis->mset($arr);//设置一个或多个键值[true]
$redis->setnx('key','value');//key=value,key存在返回false[|true]
$redis->setRange($key, $offset, $value); //修改字符串的一部分
$redis->mSetNx($pairs); //命令用于所有给定 key 都不存在时，同时设置一个或多个 key-value 对。
$redis->get('key');//获取key [value]
$redis->getBit($key, $offset); //按位获取
$redis->getRange($key, $start, $end); //返回字符串的一部分
$redis->mget($arr);//(string|arr),返回所查询键的值
$redis->getMultiple(array $keys);//(arr),返回所查询键的值
$redis->del($key_arr);//(string|arr)删除key，支持数组批量删除【返回删除个数】
$redis->delete($key_str,$key2,$key3);//删除keys,[del_num]
$redis->exists($keys);//验证指定的$key是否存在
$redis->getset('old_key','new_value');//先获得key的值，然后重新赋值,[old_value | false]
$redis->strlen('key');//获取当前key的长度
$redis->append('key','string');//把string追加到key现有的value中[追加后的个数]
$redis->bitCount($key);//计算字符串中的占位数
$redis->incr('key');//自增1，如不存在key,赋值为1(只对整数有效,存储以10进制64位，redis中为str)[new_num | false]
$redis->incrby('key',$num);//自增$num,不存在为赋值,值需为整数[new_num | false]
$redis->incrByFloat($key, $value);//自增浮点数
$redis->decr('key');//自减1，[new_num | false]
$redis->decrby('key',$num);//自减$num，[ new_num | false]
$redis->setex('key',10,'value');//key=value，有效期为10秒[true]
$redis->pSetEx($key, $ttl, $value); //设置一个有生命周期的KEY-VALUE,psetex()使用的周期单位为毫秒。
$redis->keys('*'); //遍历所有的键名
/*2.列表List栈的结构,注意表头表尾,创建更新分开操作*/
$redis->lpush('key','value'); //增，将value值插入列表表头。
$redis->rpush('key','value');//增，只能将一个值value插入到列表key的表尾 [列表的长度 |false]
$redis->lInsert('key',Redis::AFTER,'value','new_value');//增，将值value插入到列表key当中，位于值value之前或之后。[new_len | false]
$redis->lpushx('key','value');//增，只能将一个值value插入到列表key的表头，不存在不创建 [列表的长度 |false]
$redis->rpushx('key','value');//增，只能将一个值value插入到列表key的表尾，不存在不创建 [列表的长度 |false]
$redis->lpop('key');//删，移除并返回列表key的头元素,[被删元素 | false]
$redis->rpop('key');//删，移除并返回列表key的尾元素,[被删元素 | false]
$redis->brpop('key',)//删，移除并返回列表key的尾元素。第二个参数表示等待时长，超过时长返回nil。
$redis->blpop('key',1); //删，移除并返回列表key的头元素。堵塞元素。[被删除元素||false]
$redis->lrem('key','value',0);//删，根据参数count的值，移除列表中与参数value相等的元素count=(0|-n表头向尾|+n表尾向头移除n个value) [被移除的数量 | 0]
$redis->ltrim('key',start,end);//删，列表修剪，保留(start,end)之间的值 [true|false]
$redis->lset('key',index,'new_v');//改，从表头数，将列表key下标为第index的元素的值为new_v, [true | false]
$redis->lindex('key',index);//查，返回列表key中，下标为index的元素[value|false]
$redis->lrange('key',0,-1);//查，(start,stop|0,-1)返回列表key中指定区间内的元素，区间以偏移量start和stop指定。[array|false]
$redis->llen('key');//返回列表key的长度,不存在key返回0， [ len | 0]
/*3.集合Set，没有重复的member，创建更新同操作*/
$redis->sadd('key','value1','value2','valuen');//增，改，将一个或多个member元素加入到集合key当中，已经存在于集合的member元素将被忽略。[insert_num]
$redis->srem('key','value1','value2','valuen');//删，移除集合key中的一个或多个member元素，不存在的member元素会被忽略 [del_num | false]
$redis->smembers('key');//查，返回集合key中的所有成员 [array | '']
$redis->sismember('key','member');//判断member元素是否是集合key的成员 [1 | 0]
$redis->spop('key');//删，移除并返回集合中的一个随机元素 [member | false]
$redis->srandmember('key');//查，返回集合中的一个随机元素 [member | false]
$redis->sinter('key1','key2','keyn');//查，返回所有给定集合的交集 [array | false]
$redis->sunion('key1','key2','keyn');//查，返回所有给定集合的并集 [array | false]
$redis->sdiff('key1','key2','keyn');//查，返回所有给定集合的差集 [array | false]
$redis->scard('key');//返回集合key的基数(集合中元素的数量)。[num | 0]
$redis->sMove('key1','key2','member');//移动，将member元素从key1集合移动到key2集合。[1 | 0]
/*4.有序集合Zset，没有重复的member，有排序顺序,创建更新同操作*/
$redis->zAdd('key',$score1,$member1,$scoreN,$memberN);//增，改，将一个或多个member元素及其score值加入到有序集key当中。[num | 0]
$redis->zrem('key','member1','membern');//删，移除有序集key中的一个或多个成员，不存在的成员将被忽略。[del_num | 0]
$redis->zscore('key','member');//查,通过值反拿权 [num | null]
$redis->zrange('key',$start,$stop);//查，通过(score从小到大)【排序名次范围】拿member值，返回有序集key中，【指定区间内】的成员 [array | null]
$redis->zrevrange('key',$start,$stop);//查，通过(score从大到小)【排序名次范围】拿member值，返回有序集key中，【指定区间内】的成员 [array | null]
$redis->zrangebyscore('key',$min,$max[,$config]);//查，通过scroe权范围拿member值，返回有序集key中，指定区间内的(从小到大排)成员[array | null]
$redis->zrevrangebyscore('key',$max,$min[,$config]);//查，通过scroe权范围拿member值，返回有序集key中，指定区间内的(从大到小排)成员[array | null]
$redis->zrank('key','member');//查，通过member值查(score从小到大)排名结果中的【member排序名次】[order | null]
$redis->zrevrank('key','member');//查，通过member值查(score从大到小)排名结果中的【member排序名次】[order | null]
$redis->ZINTERSTORE();//交集
$redis->ZUNIONSTORE();//差集
$redis->zcard('key');//返回集合key的基数(集合中元素的数量)。[num | 0]
$redis->zcount('key',0,-1);//返回有序集key中，score值在min和max之间(默认包括score值等于min或max)的成员。[num | 0]
/*5.哈希Hash，表结构，创建更新同操作*/
$redis->hset('key','field','value');//增，改，将哈希表key中的域field的值设为value,不存在创建,存在就覆盖【1 | 0】
$redis->hget('key','field');//查，取值【value|false】
$arr = array('one'=>1,2,3);$arr2 = array('one',0,1);
$redis->hmset('key',$arr);//增，改，设置多值$arr为(索引|关联)数组,$arr[key]=field, [ true ]
$redis->hmget('key',$arr2);//查，获取指定下标的field，[$arr | false]
$redis->hgetall('key');//查，返回哈希表key中的所有域和值。[当key不存在时，返回一个空表]
$redis->hkeys('key');//查，返回哈希表key中的所有域。[当key不存在时，返回一个空表]
$redis->hvals('key');//查，返回哈希表key中的所有值。[当key不存在时，返回一个空表]
$redis->hdel('key',$arr2);//删，删除指定下标的field,不存在的域将被忽略,[num | false]
$redis->hexists('key','field');//查看hash中是否存在field,[1 | 0]
$redis->hincrby('key','field',$int_num);//为哈希表key中的域field的值加上量(+|-)num,[new_num | false]
$redis->hlen('key');//返回哈希表key中域的数量。[ num | 0]
/*1.连接*/
$redis->connect('127.0.0.1',6379,1);//短链接，本地host，端口为6379，超过1秒放弃链接
$redis->open('127.0.0.1',6379,1);//短链接(同上)
$redis->pconnect('127.0.0.1',6379,1);//长链接，本地host，端口为6379，超过1秒放弃链接
$redis->popen('127.0.0.1',6379,1);//长链接(同上)
$redis->auth('password');//登录验证密码，返回【true | false】
$redis->select(0);//选择redis库,0~15 共16个库
$redis->close();//释放资源
$redis->ping();//检查是否还再链接,[+pong]
$redis->ttl('key');//查看失效时间[-1 | timestamps]
$redis->persist('key');//移除失效时间[ 1 | 0]
$redis->sort('key',[$array]);//返回或保存给定列表、集合、有序集合key中经过排序的元素，$array为参数limit等！【配合$array很强大】 [array|false]
/*2.其他*/
$redis->dbSize();//返回当前库中的key的个数
$redis->flushAll();//清空整个redis[总true]
$redis->flushDB();//清空当前redis库[总true]
$redis->save();//同?把数据存储到磁盘-dump.rdb[true]
$redis->bgsave();//异步把数据存储到磁盘-dump.rdb[true]
$redis->info();//查询当前redis的状态 [verson:2.4.5....]
$redis->lastSave();//上次存储时间key的时间[timestamp]
$redis->watch('key','keyn');//监视一个(或多个) key ，如果在事务执行之前这个(或这些) key 被其他命令所改动，那么事务将被打断 [true]
$redis->unwatch('key','keyn');//取消监视一个(或多个) key [true]
$redis->multi(Redis::MULTI);//开启事务，事务块内的多条命令会按照先后顺序被放进一个队列当中，最后由 EXEC 命令在一个原子时间内执行。
$redis->multi(Redis::PIPELINE);//开启管道，事务块内的多条命令会按照先后顺序被放进一个队列当中，最后由 EXEC 命令在一个原子时间内执行。
$redis->exec();//执行所有事务块内的命令，；【事务块内所有命令的返回值，按命令执行的先后顺序排列，当操作被打断时，返回空值 false】
$redis->expire('key',10);//设置失效时间[true | false]
$redis->expireAt($key, $timestamp); //命令用于以 UNIX 时间戳(unix timestamp)格式设置 key 的过期时间。key 过期后将不再可用
$redis->move('key',15);//把当前库中的key移动到15库中[0|1]
```

## redis 事务包裹

> 事务块内的多条命令会按照先后顺序被放进一个队列当中，最后由exec命令原子性(atomic)地执行。

```php
<?php
namespace app\index\controller;
use app\index\model\user;
class test{
	public function multi($req)
    {
        $rds = RD();
        $tx = $rds->multi();
		$tx->set('foo', 'bar');
		$tx->set('foo1', 'bar1');
		$ret = $tx->exec();
		return $rds->get('foo1');
    }
}
?>
```

## redis 管道命令

> 将执行的命令写入到缓冲中，最后由exec命令一次性发送给redis执行返回。

```php
<?php
namespace app\index\controller;
use app\index\model\user;
class test{
	public function pipeline($req)
    {
        $rds = RD();
        $tx = $rds->pipeline();
		$tx->set('foo', 'bar');
		$tx->set('foo1', 'bar1');
		$ret = $tx->exec();
		return $rds->get('foo1');
    }
}
?>
```


## redis 监听

> 监听值的变化，如果执行时有变化则事务失败，无变化则事务成功。

```php
<?php
namespace app\index\controller;
use app\index\model\user;
class test{
	public function watch($req)
    {
    	$rds = RD();
        $tx = $rds->watch('foo');
		$tx->incr('foo');
		$ret = $tx->exec();
		return $rds->get('foo1');
    }
}
?>
```




## redis 切换或指定链接

> 链接由配置文件决定

> 打开 `./.env` 文件

```
[redis]
default_frame = raw
host = 127.0.0.1
password = 
port = 6379
database = 0
timeout = 5
readTimeout = -1
prefix = 

[redis-1]
host = 127.0.0.1
password = 
port = 6379
database = 2
timeout = 5
readTimeout = -1
prefix = 

[redis-cluster]
type = cluster
host[] = 127.0.0.1:9000
host[] = 127.0.0.1:9001
host[] = 127.0.0.1:9002
host[] = 127.0.0.1:9003
host[] = 127.0.0.1:9004
host[] = 127.0.0.1:9005
timeout = 2
readTimeout = 2
password =
prefix =
```

> 打开 `./config/redis.php` 文件 可以看到配置文件的对应关系
```php
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

```

这样就有三个可以使用的 Redis 预设连接配置了。如果需要增删，直接修改 `./config/redis.php` 即可。

```php
<?php
namespace app\index\controller;
use app\index\model\user;
class test{
	public function watch($req)
    {
        $rds = RD('raw', 'database'); // 连接 redis-1
        $rds->set('foo', 'bar');
        $value = $rds->get('foo');
        return $value;
    }
}
?>
```

集群连接
```php
<?php
namespace app\index\controller;
use app\index\model\user;
class test{
	public function watch($req)
    {
        $rds = RD('raw', 'cluster'); // 连接 redis-cluster
        $rds->set('foo', 'bar');
        $value = $rds->get('foo');
        return $value;
    }
}
?>
```

> rcmaker 目录 ./tools/redis 有redis集群配置和一键启动[终止]配置示例

> 如果你把 `default_frame` 改成 `mix`，上面的调用方式保持不变，只需要把 bootstrap 里的载入类切换成 `RC\Helper\Redis\Mix`。
