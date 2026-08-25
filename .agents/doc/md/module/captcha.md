# 验证码

rcmaker 内置了验证码组件，常用入口有：

- `$req->captcha()`
- `$req->C()`
- `captchaCheck()`
- `$req->CC()`

验证码可以使用 `cache`、`session`、`closure` 三种存储方式。

推荐：

- 前后端分离、跨域接口、移动端接口，优先使用 `store=cache`
- 同源页面、传统服务端渲染表单，才优先考虑 `store=session`

如果使用 `session`，通常只要保持 `RC\Session` 已加载即可；如果改成 `cache`，还需要先载入 `RC\Helper\Cache\Raw`。

## 配置文件

配置文件位于 `config/captcha.php`，当前仓库示例如下：

```php
<?php
return [
  'default' => [
    'expire' => 300,
    'namePrefix' => 'RC_CAPTCHA_',
    'length' => 5,
    'store' => 'session',
    'phrase' => [
      'width' => 150,
      'height' => 40,
      'font' => null,
      'fingerprint' => null,
    ],
    'charset' => 'abcdefghijklmnpqrstuvwxyz123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    'autoDelete' => true,
    'return' => 'image',
  ],
  'session' => [
    'expire' => 300,
    'length' => 4,
    'store' => 'session',
    'return' => 'image',
  ],
  'closure' => [
    'expire' => 300,
    'length' => 5,
    'store' => 'closure',
    'return' => 'image',
  ],
];
```

说明：

- 可以通过 `$req->captcha($name, 'session')` 这类方式切换配置连接。
- 当前实现兼容旧拼写 `autoDelte`，但建议统一使用 `autoDelete`。
- `return` 支持 `image` 和 `text`。

## store 选择建议

`store=cache`：

- 最适合前后端分离接口
- 不依赖浏览器 session cookie
- 只要验证码 key 规则一致，跨域请求也能正常校验

`store=session`：

- 更适合同源页面、SSR 页面、传统表单提交
- 依赖同一个 session cookie 在“取验证码”和“提交验证码”两次请求之间保持一致
- 前后端分离场景下，如果浏览器没有正确带回 session cookie，会一直出现“验证码有误”

当前项目如果是跨域登录页，哪怕已经开启了跨域响应头，也不建议默认使用 `store=session`。

原因：

- 带凭证跨域时，`Access-Control-Allow-Origin` 不能为 `*`
- 前端请求本身还需要显式开启 `withCredentials` 或 `credentials: 'include'`
- 浏览器对跨站 cookie 还有 `SameSite`、`Secure`、HTTPS 等限制

因此在 API 登录、跨域后台、移动端 H5 等场景里，更稳妥的做法是直接使用 `store=cache`。

常用字段说明：

参数 | 类型 | 说明
---|---|---
expire | int | 验证码有效期；`store=cache` 时作为缓存 TTL，`store=closure` 时会作为第三个参数传给闭包
namePrefix | string | 验证码存储 key 的前缀参与项，默认 `RC_CAPTCHA_`
length | int | 验证码长度
store | string | 存储方式，支持 `cache`、`session`、`closure`
phrase | array | 图片参数，例如宽高、字体、指纹
charset | string | 验证码字符集
autoDelete | bool | 使用 `captchaCheck()` / `CC()` 校验成功后是否自动删除
return | string | 返回类型，支持 `image`、`text`

`phrase` 的常见写法：

```php
'phrase' => [
  'width' => 130,
  'height' => 40,
  'font' => FRAME_PATH . '/Helper/Captcha/Font/captcha0.ttf',
  'fingerprint' => null,
]
```

## 基本用法

控制器示例：

```php
<?php
namespace app\index\controller;

class test
{
  public function captcha($req)
  {
    return $req->C('user_id_10000');
  }

  public function check($req)
  {
    if ($req->CC('user_id_10000', $req->post('captcha'))) {
      return $req->json(['code' => 0, 'msg' => 'ok']);
    }

    return $req->json(['code' => 400, 'msg' => 'captcha fail']);
  }

  public function login($req)
  {
    return $req->V('login');
  }
}
```

模板示例：

```html
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>验证码测试</title>
</head>
<body>
  <form method="post" action="/test/check">
    <img id="captcha" title="看不清？点击图片更换验证码！" src="/test/captcha" onclick="resetCaptcha()" /><br>
    <input type="text" name="captcha" />
    <input type="submit" value="提交" />
  </form>
<script>
var captcha = '/test/captcha';
function resetCaptcha() {
  document.getElementById('captcha').src = captcha + '/?' + Math.random();
}
</script>
</body>
</html>
```

访问 `http://localhost:8680/test/login` 后，验证码图片会随刷新重新生成。

## 参数签名

生成验证码函数签名：

```php
captcha($request, $name = '', $connect = 'default', $closure = null, $cache = null)
```

请求映射调用时参数顺序不变：

```php
$req->captcha($name = '', $connect = 'default', $closure = null, $cache = null)
```

参数说明：

- `$name` 用于区分业务对象，例如用户名、手机号、用户 ID。
- `$connect` 为 `config/captcha.php` 中的连接名。
- `$closure` 仅在 `store=closure` 时使用。
- `$cache` 仅在 `store=cache` 时使用，可以显式传入缓存实例。

注意：当前实现的实际存储 key 不是简单的 `namePrefix + name`，而是：

```php
md5($namePrefix . $name . $request->ip() . strtolower($captchaCode))
```

因此同一个 `$name` 在不同 IP、不同验证码文本下对应的存储 key 都不同。

## 闭包接管存储

如果不想使用默认的 `cache` 或 `session`，可以把 `store` 设为 `closure`，然后自己接管存储。

闭包会收到 3 个参数：

- `$name`：实际存储 key，已经是计算后的哈希值
- `$captchaString`：验证码文本，已转为小写
- `$expire`：过期秒数

示例：

```php
<?php
namespace app\index\controller;

class test
{
  public function captcha($req)
  {
    $closure = function ($name, $captchaString, $expire) {
      // 这里可以写入你自己的存储系统
    };

    return $req->C('user_id_10000', 'closure', $closure);
  }
}
```

## 校验助手

rcmaker 提供了验证码校验助手：

```php
captchaCheck($request, $name = '', $value = '', $connect = 'default', $cache = null)
```

也可以通过请求映射调用：

```php
$req->captchaCheck(...)
$req->CC(...)
```

行为说明：

- 校验成功返回 `true`
- 校验失败返回 `false`
- 当 `$value` 为空时，会直接抛出异常，不是返回 `false`

示例：

```php
<?php
namespace app\index\controller;

class test
{
  public function captcha_check($req)
  {
    $key = 'user_id_10000';
    $code = $req->get('code');
    return $req->captchaCheck($key, $code) ? 'ok' : 'fail';
  }
}
```

## 手动校验

优先推荐直接使用 `captchaCheck()`。如果你确实要自己读 `cache` 或 `session`，需要先按真实规则计算存储 key。

`store=cache` 示例：

```php
$namePrefix = 'RC_CAPTCHA_';
$code = strtolower($req->post('captcha'));
$captchaKey = md5($namePrefix . 'user_id_10000' . $req->ip() . $code);
$captcha = cache()->get($captchaKey);

return $captcha === $code
  ? $req->json(['code' => 0, 'msg' => 'ok'])
  : $req->json(['code' => 400, 'msg' => 'captcha fail']);
```

`store=session` 示例：

```php
$namePrefix = 'RC_CAPTCHA_';
$code = strtolower($req->post('captcha'));
$captchaKey = md5($namePrefix . 'user_id_10000' . $req->ip() . $code);
$captcha = $req->S($captchaKey);

return $captcha === $code
  ? $req->json(['code' => 0, 'msg' => 'ok'])
  : $req->json(['code' => 400, 'msg' => 'captcha fail']);
```


