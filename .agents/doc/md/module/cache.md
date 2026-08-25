# 缓存器

rcmaker 内置集成了 ThinkPHP 的缓存组件。使用缓存组件可以快速建立文件缓存或 Redis 缓存。

> 当前 `RC\Helper\Cache\Raw` 底层支持的驱动类型包括 `File`、`Memcache`、`Memcached`、`Redis`、`Wincache`。其中“Redis 集群”不是单独的驱动类，而是 `Redis` 驱动在 `host` 配置为数组时自动走集群连接。

## 配置文件

使用缓存前，需要先在 bootstrap 中载入 `RC\Helper\Cache\Raw`：

```
[bootstrap]
load[] = RC\Helper\Cache\Raw
```

配置文件在 `config/cache.php`，当前仓库配置示例如下：

```php
<?php
return [
    'default_frame' => 'raw',
    'default' => 'file',
    'driver' => [
        'file' => [
            'type' => 'File',
            'path' => runtime_path() . '/cache/',
            'prefix' => 'RCCache_',
            'expire' => 0,
        ],
        'redis' => [
            'type' => 'Redis',
            'host' => '127.0.0.1',
            'port' => 6379,
            'prefix' => 'RCCache_',
            'expire' => 0,
        ],
        'rediscluster' => [
            'type' => 'Redis',
            'host' => ['127.0.0.1:9000', '127.0.0.1:9001', '127.0.0.1:9002', '127.0.0.1:9003', '127.0.0.1:9004', '127.0.0.1:9005'],
            'prefix' => 'RCCache_',
            'expire' => 0,
        ],
    ],
];
```

其中 `default_frame` 为缓存引擎框架，目前实际只有 `raw`。

`default` 为默认缓存配置名，对应 `driver` 下的键。当前仓库默认值是 `file`；如果需要切换到 Redis 集群，可以设置 `default => 'rediscluster'`。

> [!IMPORTANT]
> file 缓存适合 Windows 本地开发，Linux 多进程生产环境通常使用 Redis。业务代码始终通过 `cache()` 访问默认配置，不要依赖 file 缓存目录、文件格式或写死 Redis 地址。切换驱动后应保持 key、TTL、失效规则和缓存值格式一致；计数、锁、限流及其他依赖原子性或跨 Worker 一致性的功能，必须使用生产驱动验证。

如果使用助手函数 `cache()`，可以随时切换缓存配置，例如：

```php
$cache = cache('raw', 'rediscluster');
```

组件更多配置方法请参考 [think-cache](https://www.kancloud.cn/manual/thinkphp6_0/1037634)。注意：这里的 `driver` 对应 think-cache 配置里的 `stores`。

## 如何使用

推荐使用助手函数 `cache()`。

## 设置缓存

```php
<?php
namespace app\index\controller;
class test{
    public function cache($req){
        $cache = cache();
        $cache->set('foo', 'bar');
        $value = $cache->get('foo');
        return $value;
    }
}
```

访问控制器 `http://127.0.0.1:8680/test/cache` 输出 `bar`。

## 设置缓存有效期

```php
$cache = cache();
$cache->set('name', $value, 3600);
```

也可以使用 `DateTime` 对象设置过期时间：

```php
$cache = cache();
$cache->set('name', $value, new DateTime('2019-10-01 12:00:00'));
```

## 缓存自增

仅针对数值类型。

```php
$cache = cache();
$cache->set('name', 1);
$cache->inc('name');
$cache->inc('name', 3);
```

## 缓存自减

仅针对数值类型。

```php
$cache = cache();
$cache->set('name', 5);
$cache->dec('name');
$cache->dec('name', 3);
```

## 获取缓存

```php
$cache = cache();
$cache->get('name');
```

如果缓存 key 不存在，可以返回指定默认值：

```php
$cache = cache();
$cache->get('name', 'tom');
```

## 缓存数组

缓存器支持直接缓存数组，无需手动序列化：

```php
$cache = cache();
$data = [1, 2, 3, 4];
$cache->set('data', $data);
$get = $cache->get('data');
return $get;
```

## 数组追加数据

如果缓存值是数组，可以通过 `push()` 追加：

```php
$cache = cache();
$cache->set('data', [1, 2, 3, 4]);
$cache->push('data', 5);
$get = $cache->get('data');
return $get;
```

## 删除缓存

```php
$cache = cache();
$cache->delete('name');
```

## 获取并删除缓存

```php
$cache = cache();
$name = $cache->pull('name');
```

`pull()` 会返回原缓存值后再删除对应 key。当前实现对 `0`、`false`、空字符串这类“假值”也会正常删除，不会因为值本身为假而漏删。

## 清空缓存

```php
$cache = cache();
$cache->clear();
```

## 不存在则写入缓存

```php
$cache = cache();
$name = $cache->remember('name', 'tom');
```

如果 `name` 不存在，则会写入 `'tom'`。

也可以传闭包，只有缓存未命中时才会执行：

```php
$cache = cache();
$user = $cache->remember('user:1', function () {
    return ['id' => 1, 'name' => 'tom'];
});
```

## 缓存标签

某些时候我们需要为缓存归集数据，这时可以使用缓存标签功能：

```php
$cache = cache();
$cache->tag('tag')->set('name1', 'value1');
$cache->tag('tag')->set('name2', 'value2');

$cache->tag('tag')->clear();
```

缓存标签不会改变正常读取操作，所以获取方式依然是：

```php
$cache = cache();
$cache->get('name1');
```

支持同时指定多个缓存标签：

```php
$cache = cache();
$cache->tag(['tag1', 'tag2'])->set('name1', 'value1');
$cache->tag(['tag1', 'tag2'])->set('name2', 'value2');

$cache->tag(['tag1', 'tag2'])->clear();
```

可以追加某个缓存标识到标签：

```php
$cache = cache();
$cache->tag('tag')->append('name3');
```

## 获取标签的缓存标识列表

```php
$cache = cache();
$cache->getTagItems('tag');
```

返回 tag 标签下所有缓存 key。

## 获取实际标签名

```php
$cache = cache();
$cache->getTagKey('tag');
```

## 切换缓存配置

注意：以下示例以当前 `config/cache.php` 为准。

```php
$cache_default = cache();
$cache_file = cache('raw', 'file');
$cache_file->set('name', 'tom');

$cache_rediscluster = cache('raw', 'rediscluster');
$cache_rediscluster->set('name', 'tom');
```




