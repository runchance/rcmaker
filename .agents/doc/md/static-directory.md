# 静态目录

> 如果某个应用主要提供静态页面或静态资源，可以为它单独配置静态目录。

静态应用需要独立端口、进程数量或内存限制时，可以通过 `bind_process` 绑定 APP 进程组，完整示例参考 [应用进程组](md/app-process.md#独立静态应用)。

## 应用级静态目录

框架支持在 `config/app.php` 里为单个应用单独指定静态根目录。请求命中该应用绑定域名时，静态文件会优先从该应用自己的 `document_root` 中查找，而不是使用 worker/swoole 的全局 `document_root`。

示例：

```php
'app' => [
	'upload' => [
		'bind_process' => 'RC_STATIC',
		'domains' => ['api.test.com'],
		'document_root' => 'upload',
		'index_default' => 'index.html',
		'enable_static_file' => true,
		'enable_static_gzip' => true,
		'enable_static_preload' => true,
		'static_only' => true,
	],
]
```

说明：

- `document_root` 写相对路径时，会自动按 `public/{document_root}` 解析。
- 例如 `document_root => 'adminstatic'` 会映射到 `public/adminstatic`。
- `index_default` 用于目录首页文件名，默认值是 `index.html`。
- `enable_static_file` 也可以按应用单独覆盖。
- 静态应用建议显式开启 `enable_static_preload`；框架缺省值为关闭，不写该项不会自动预热。
- `bind_process` 可以将静态应用放到独立 APP 进程组；未设置时由主 APP 处理。
- `static_only=true` 时，静态文件未命中会直接返回 404，PHP 文件和动态控制器均不会执行。
- 如果需要放到其他目录，也可以直接写绝对路径。

例如访问：

```text
http://api.test.com:8680/upload.html
```

会优先映射到：

```text
public/upload/upload.html
```

如果访问的是根路径 `/`，并且目录下存在 `index_default` 对应的文件，例如：

```text
public/upload/index.html
```

则会自动把它作为静态首页返回。

## 静态 gzip

框架内置静态文件分发默认会尝试对以下文本类资源启用 gzip：

- `css`
- `js`
- `html`
- `htm`
- `json`
- `svg`
- `txt`
- `xml`

启用条件：

- 当前应用或全局配置没有关闭 `enable_static_gzip`
- 客户端请求头包含 `Accept-Encoding: gzip`
- 请求不是 Range 请求
- 文件是可压缩文本类型

如果某个应用不希望由框架压缩，可以在 `config/app.php` 中关闭：

```php
'app' => [
	'adminstatic' => [
		'domains' => ['static.test.com'],
		'document_root' => 'adminstatic',
		'enable_static_file' => true,
		'enable_static_gzip' => false,
	],
]
```

## 静态预加载

静态应用推荐开启预加载，让常用文本资源从进程启动后即可直接从内存返回：

```php
'enable_static_preload' => true, // 启用静态文件预热
'static_preload_extensions' => ['css', 'js', 'html'], // 预热静态文件类型
'static_preload_time_limit' => 0.5, // 单位秒，单个静态目录的预加载总耗时上限
```

当前行为：

- 只预加载文本类静态资源：`css`、`js`、`html`、`htm`、`json`、`svg`、`txt`、`xml`
- 预加载发生在应用进程启动时，不等用户第一次访问才触发
- 后续请求直接从内存返回 body，不再每次读取文件
- 如果同时开启 `enable_static_gzip`，会一并缓存 gzip 后内容
- 预加载会在单个静态目录达到 `static_preload_time_limit` 后停止，避免启动耗时过长
- 预加载哪些文件由 `static_preload_extensions` 决定

适用场景：

- 纯静态后台首页
- 独立 H5 站点
- 静态资源体积不大、但访问频率高的目录

注意：

- 这是长驻进程启动阶段构建的进程内缓存。
- 在 Linux 多进程 CLI 模式下，预加载会在 fork 前全局执行一次，worker 进程继承这份已预热的内存页；如果后续进程内修改这份缓存，才会触发各自分离。
- Windows 不支持 fork：主 APP 子进程只预热未设置 `bind_process` 的应用，自定义 APP 子进程只预热绑定到当前进程组的应用。同一个静态应用由多个独立进程组提供时，各进程仍需保存自己的缓存。
- `reload` 不会重新执行静态预加载，也不会主动刷新这份缓存。
- 静态文件更新后，如需刷新预加载内容，请使用 `restart` 重新启动相关进程。
- 不建议对大量大文件目录开启，以免占用过多内存。
- 资源规模较大、变化频繁或内存预算严格时，可以关闭预加载；优先通过扩展名和时间上限缩小预热范围。
