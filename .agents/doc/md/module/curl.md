# CURL

rcmaker 内置了基于 `php-curl-class` 的网络请求组件，适合：

- HTTP 接口调用
- 第三方服务对接
- 数据抓取
- 并发请求

入口有两个：

- `curl()` 返回单请求客户端 `RC\Helper\Curl\Curl`
- `curl(true)` 返回并发客户端 `RC\Helper\Curl\MultiCurl`

说明：

- 这两个是全局 helper，不是 request 映射方法。
- 当前实现里，每次调用 `curl()` / `curl(true)` 都会返回新的实例，不会复用上一次请求的 header、cookie、回调或响应状态。

## 单请求用法

```php
<?php
namespace app\index\controller;

class test
{
    public function curl($req)
    {
        $curl = curl();
        $curl->setTimeout(10);
        $curl->get('https://www.baidu.com/');

        if ($curl->error) {
            return $req->json([
                'success' => false,
                'error_code' => $curl->errorCode,
                'error_message' => $curl->errorMessage,
            ]);
        }

        return $curl->response;
    }
}
```

常用结果属性：

参数 | 说明
---|---
`$curl->response` | 已解码后的响应内容
`$curl->rawResponse` | 原始响应体
`$curl->responseHeaders` | 响应头
`$curl->responseCookies` | 响应 Cookie
`$curl->httpStatusCode` | HTTP 状态码
`$curl->error` | 是否发生错误
`$curl->errorCode` | 错误码
`$curl->errorMessage` | 错误信息

## 常用请求方法

单请求客户端常用方法包括：

- `get($url, $data = [])`
- `post($url, $data = '')`
- `put($url, $data = [])`
- `patch($url, $data = [])`
- `delete($url, $query = [], $data = [])`
- `head($url, $data = [])`
- `options($url, $data = [])`

## JSON 请求示例

当请求头设置为 JSON 时，数组数据会自动编码为 JSON：

```php
$curl = curl();
$curl->setHeader('Content-Type', 'application/json');
$curl->post('https://example.com/api/user', [
    'name' => 'tom',
    'age' => 18,
]);

if ($curl->error) {
    return $curl->errorMessage;
}

return $curl->response;
```

## 第三方 JSON API 示例

更贴近业务的写法通常会把请求头、鉴权和错误分支一起处理：

```php
<?php
namespace app\index\controller;

class test
{
    public function syncOrder($req)
    {
        $curl = curl();
        $curl->setTimeout(15);
        $curl->setHeader('Accept', 'application/json');
        $curl->setHeader('Content-Type', 'application/json');
        $curl->setHeader('Authorization', 'Bearer ' . config('services.order.token'));
        $curl->post('https://api.example.com/orders/sync', [
            'order_no' => 'SO20260706001',
            'status' => 'paid',
        ]);

        if ($curl->error) {
            return $req->json([
                'success' => false,
                'status' => $curl->httpStatusCode,
                'error' => $curl->errorMessage,
                'response' => $curl->rawResponse,
            ]);
        }

        return $req->json([
            'success' => true,
            'status' => $curl->httpStatusCode,
            'data' => $curl->response,
        ]);
    }
}
```

如果你想拿到原始字符串而不是自动解码结果，可以关闭 JSON 解码：

```php
$curl = curl();
$curl->setJsonDecoder(false);
$curl->get('https://example.com/raw-json');

return $curl->rawResponse;
```

## 下载文件示例

单请求下载：

```php
$curl = curl();
$curl->setTimeout(0);
$ok = $curl->download('https://example.com/files/report.xlsx', runtime_path() . '/download/report.xlsx');

if (!$ok || $curl->error) {
    return $curl->errorMessage;
}

return 'download success';
```

说明：

- 下载时会先写入目标文件旁边的 `.pccdownload` 临时文件。
- 如果临时文件已存在且非空，会按已有大小继续下载。
- 完成后才会重命名成最终文件名。

## 常用配置

常见配置方法：

- `setHeader($key, $value)` 设置请求头
- `setTimeout($seconds)` 设置超时
- `setOpt($option, $value)` 直接设置底层 cURL 选项
- `setBasicAuthentication($username, $password)` 设置 Basic Auth
- `setUserAgent($userAgent)` 设置 User-Agent
- `setCookie($key, $value)` 或 `setCookies($array)` 设置 Cookie
- `setCookieString($string)` 直接设置原始 Cookie 字符串
- `setProxy($proxy, $port, $username, $password)` 设置单个代理

注意：

- 不建议在正式环境里把 `CURLOPT_SSL_VERIFYPEER` 和 `CURLOPT_SSL_VERIFYHOST` 关闭。
- 如果确实是本地调试证书问题，再临时关闭，并且只在调试代码里使用。

## curl_multi 并发访问

使用 `curl(true)`：

```php
<?php
namespace app\index\controller;

class test
{
    public function multiCurl($req)
    {
        $results = [];
        $multiCurl = curl(true);
        $multiCurl->setTimeout(10);

        $multiCurl->success(function ($instance) use (&$results) {
            $results[] = [
                'url' => $instance->url,
                'status' => $instance->httpStatusCode,
                'ok' => true,
            ];
        });

        $multiCurl->error(function ($instance) use (&$results) {
            $results[] = [
                'url' => $instance->url,
                'ok' => false,
                'error_code' => $instance->errorCode,
                'error_message' => $instance->errorMessage,
            ];
        });

        $multiCurl->addGet('https://www.baidu.com', [
            'wd' => 'hello world',
        ]);
        $multiCurl->addGet('https://www.so.com/', [
            'q' => 'hello world',
        ]);
        $multiCurl->addGet('https://www.bing.com/search/', [
            'q' => 'hello world',
        ]);

        $multiCurl->start();

        return $req->json($results);
    }
}
```

并发客户端常用方法：

- `addGet($url, $data = [])`
- `addPost($url, $data = '')`
- `addPut($url, $data = [])`
- `addPatch($url, $data = [])`
- `addDelete($url, $query = [], $data = [])`
- `addDownload($url, $filename)` 并发下载文件
- `start()` 启动并发请求
- `success($callback)` / `error($callback)` / `complete($callback)` 设置回调

## 回调说明

`MultiCurl` 的回调会收到单个 `Curl` 实例，因此你可以直接读取：

- `$instance->url`
- `$instance->response`
- `$instance->httpStatusCode`
- `$instance->error`
- `$instance->errorCode`
- `$instance->errorMessage`

## 高阶能力

`MultiCurl` 还支持一些在批量接口调用里很实用的能力：

- `setConcurrency(5)` 控制并发数，必须大于 0。
- `setRateLimit('60/1m')` 控制速率，格式固定为 `请求数/时间`，单位只支持 `s`、`m`、`h`。
- `setRetry(2)` 表示失败后最多再重试 2 次，也就是最多尝试 3 次。
- `setRetry(function ($instance) { ... })` 可以自定义是否继续重试。
- `setProxy(...)` 为全部请求设置同一个代理。
- `setProxies([...])` 为每个子请求从代理池里随机挑选一个代理。

示例：

```php
$multiCurl = curl(true);
$multiCurl->setConcurrency(5);
$multiCurl->setRateLimit('60/1m');
$multiCurl->setRetry(2);
$multiCurl->setProxies([
    '127.0.0.1:7890',
    '127.0.0.1:7891',
]);
```

如果你需要按业务规则决定是否继续重试，可以这样写：

```php
$multiCurl->setRetry(function ($instance) {
    if ($instance->httpStatusCode === 429) {
        return $instance->retries < 3;
    }

    return false;
});
```

## 备注

- 单请求客户端适合串行调用接口。
- 并发客户端适合批量抓取、批量查询多个 HTTP 接口。
- 如果你需要更深的能力，当前封装底层对应的是 [php-curl-class](https://github.com/php-curl-class/php-curl-class)。



