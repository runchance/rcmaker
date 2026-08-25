# 短信验证码组件

rcmaker 内置了短信验证码组件 `RC\Helper\Sms`，用于生成和校验不同场景下的短信验证码。

常用入口：

- `$req->sms()`
- `sms($request, $method = 'get', $config = [], $cache = null)`

说明：这个组件只负责验证码的生成、缓存和校验，不负责真正调用短信服务商发送短信。通常你会在拿到 `create()` 返回的验证码后，再自行对接阿里云、腾讯云或其他短信服务。

## 配置文件

配置文件位于 `config/sms.php`，当前仓库示例如下：

```php
<?php
return [
    'type' => 1,
    'length' => 4,
    'expire' => 180,
    'ipCheck' => true,
    'mobileKey' => 'mobile',
    'codeKey' => 'code',
    'autoDelete' => true,
];
```

字段说明：

参数 | 类型 | 说明
---|---|---
type | int | 验证码类型
length | int | 验证码长度
expire | int | 验证码过期时间，单位秒
ipCheck | bool | 是否绑定客户端 IP 校验
mobileKey | string | 获取手机号时使用的请求参数名
codeKey | string | 获取验证码时使用的请求参数名
autoDelete | bool | 校验成功后是否自动删除缓存中的验证码

注意：

- 当前实现兼容旧拼写 `autoDelte`，但建议统一使用 `autoDelete`。
- 当 `ipCheck=true` 时，同一手机号在不同 IP 下生成的验证码不能互通。

## 验证码类型

`type` 支持以下值：

参数 | 说明
---|---
1 | 纯数字
2 | 纯小写字母
3 | 纯大写字母
4 | 数字 + 小写字母
5 | 数字 + 大写字母
6 | 小写字母 + 大写字母
7 | 数字 + 小写字母 + 大写字母

## 基本用法

### 获取验证码

`sms()` 的第二个参数用于指定从 `get` 还是 `post` 取手机号和验证码。当前实现默认值是 `get`。

```php
<?php
namespace app\index\controller;

class test
{
    public function getSmsCode($req)
    {
        $sms = sms($req, 'get');
        $code = $sms->create();
        return $code;
    }
}
```

访问：`http://localhost:8680/test/getSmsCode?mobile=13888888888`

返回类似：`8096`

### 校验验证码

```php
<?php
namespace app\index\controller;

class test
{
    public function checkSmsCode($req)
    {
        $sms = $req->sms();
        $check = $sms->check();
        return $check ? 'yes' : 'no';
    }
}
```

访问：`http://localhost:8680/test/checkSmsCode?mobile=13888888888&code=8096`

返回：`yes`

行为说明：

- `check()` 成功时返回 `true`
- `check()` 失败时返回 `false`
- 未传手机号或验证码时会直接抛异常
- `autoDelete=true` 时，只有校验成功后才会删除缓存中的验证码

## 多场景使用

可以通过 `scene($scene)` 区分不同业务场景，例如登录、注册、API 调用。

```php
<?php
namespace app\index\controller;

class test
{
    public function getSmsCodeApi($req)
    {
        return $req->sms()->scene('api')->create();
    }

    public function checkSmsCodeApi($req)
    {
        return $req->sms()->scene('api')->check() ? 'yes' : 'no';
    }

    public function getSmsCode($req)
    {
        return $req->sms()->scene('login')->create();
    }

    public function checkSmsCode($req)
    {
        return $req->sms()->scene('login')->check() ? 'yes' : 'no';
    }
}
```

实际缓存 key 由以下内容拼接而成：

```php
rcsms + scene + mobile
```

因此同一手机号在不同 `scene` 下的验证码互不影响。

## 手动指定手机号

可以通过 `mobile($mobile)` 手动指定手机号，指定后无需再从请求里读取：

```php
<?php
namespace app\index\controller;

class test
{
    public function getSmsCode($req)
    {
        return sms($req, 'get')->mobile('13888888888')->create();
    }

    public function checkSmsCode($req)
    {
        return $req->sms()->mobile('13888888888')->check() ? 'yes' : 'no';
    }
}
```

## 手动指定验证码

可以通过 `code($code)` 手动指定验证码，指定后无需再从请求里读取：

```php
<?php
namespace app\index\controller;

class test
{
    public function checkSmsCode($req)
    {
        return $req->sms()->mobile('13888888888')->code('8096')->check() ? 'yes' : 'no';
    }
}
```

## 调整有效期

除了在配置文件设置外，也可以通过链式方法 `exp($second)` 单次调整有效期：

```php
<?php
namespace app\index\controller;

class test
{
    public function getSmsCode($req)
    {
        return sms($req, 'get')->mobile('13888888888')->exp(240)->create();
    }
}
```

## 对接短信服务商

实际项目里，通常是先生成验证码，再调用第三方短信平台：

```php
$sms = $req->sms();
$code = $sms->mobile('13888888888')->scene('login')->create();

// 把 $code 发送到你的短信服务商
```

如果第三方短信发送失败，建议你自行决定是否删除刚生成的验证码，避免“短信没发出，但验证码已经缓存”的状态残留。



