# 令牌与鉴权

rcmaker 内置了基于 JWT 的令牌组件 `RC\Helper\Token`，可用于登录签发、鉴权校验、刷新令牌以及多 guard 认证。

常用入口有两个：

- 请求注入：`$req->token()`
- 助手函数：`token($req, $guard = null, $cache = null)`

## 前置条件

Token 组件默认会使用缓存参与单设备登录校验，因此在默认配置下建议先载入缓存引导类：

```ini
[bootstrap]
load[] = RC\Session
load[] = RC\Helper\Cache\Raw
```

说明：

- `RC\Session` 用于 `type = Session` 场景。
- `RC\Helper\Cache\Raw` 用于默认的 `is_single_device = true` 场景；如果你自己向 `token($req, $guard, $cache)` 传入了缓存实例，也可以不依赖默认缓存引导。

## 配置文件

配置文件位于 `config/token.php`。

当前仓库默认配置的关键部分如下：

```php
<?php

return [
    'msg' => [
        'key_not_exist' => '缺少key',
        'signature_verification_failed' => 'token验证失败',
        'signature_verification_before_invalid' => 'token签名尚未生效',
        'access_expired' => 'token已过期！',
        'token_format_error' => 'token格式错误',
        'signed_in_on_another_device' => 'token在其他设备使用',
        'request_without_info' => '请求未携带信息',
        'illegal_info' => '非法的信息',
        'refresh_token_valid' => '用于刷新的token无效',
        'refresh_token_invalid_yet' => '用于刷新的token尚未生效',
        'refresh_token_expired' => '用于刷新的token已经过期',
        'refresh_token_format_error' => '用于刷新的token格式错误',
    ],
    'default' => 'api',
    'api' => [
        'signer' => 'HS256',
        'type' => 'Bearer',
        'keyName' => 'token',
        'access_secret_key' => 'rcmaker2022authaccess8f9g9i',
        'access_expired' => 7200,
        'refresh_secret_key' => 'rcmaker2022authrefresh5v6g8y',
        'refresh_expired' => 604800,
        'refresh_disable' => false,
        'iss' => 'rcmaker.runchance.com',
        'leeway' => 60,
        'is_single_device' => true,
        'cache_token_time' => 604800,
        'cache_token_prefix' => 'RC:AUTH:TOKEN:',
        'access_private_key' => null,
        'access_public_key' => null,
        'refresh_private_key' => null,
        'refresh_public_key' => null,
    ],
    'user' => [],
    'admin' => [],
];
```

几个关键点：

- `default` 为默认 guard，当前是 `api`。
- `user`、`admin` 这类 guard 配置如果留空，会在运行时和默认字段合并。
- 当前仓库默认 `type` 实际是 `Bearer`，不是 `Session`。
- 默认开启 `is_single_device`，同一 `key + guard + ip` 只保留最后一次登录状态。

常用配置项说明：

参数 | 类型 | 说明
---|---|---
default | string | 默认 guard 名称
signer | string | 签名算法，支持 `HS256`、`HS384`、`HS512`、`RS256`、`RS384`、`RS512`、`ES256`、`ES384`、`EdDSA`
type | string | 客户端取 token 的方式，支持 `Bearer`、`Header`、`Get`、`Post`、`Cookie`、`Session`
keyName | string | token 参数名或键名，默认 `token`
access_secret_key | string | HMAC 模式 access token 密钥
refresh_secret_key | string | HMAC 模式 refresh token 密钥
access_expired | int | access token 过期秒数
refresh_expired | int | refresh token 过期秒数
refresh_disable | bool | 是否禁用 refresh token
iss | string | 签发者
leeway | int | 时间偏差冗余秒数
is_single_device | bool | 是否开启单设备登录
cache_token_time | int | 单设备登录缓存秒数
cache_token_prefix | string | 单设备登录缓存前缀
access_private_key / access_public_key | string|null | 非对称签名 access 密钥文件路径
refresh_private_key / refresh_public_key | string|null | 非对称签名 refresh 密钥文件路径

注意：

- 使用 `HS256`、`HS384`、`HS512` 时，请务必替换默认密钥。
- 使用 `RS*`、`ES*`、`EdDSA` 时，需要配置对应私钥和公钥文件路径。

## 算法切换和证书生成

Token 支持以下算法：

- `HS256`
- `HS384`
- `HS512`
- `RS256`
- `RS384`
- `RS512`
- `ES256`
- `ES384`
- `EdDSA`

切换到非对称算法后，需要生成密钥文件。优先启动框架交互工具：

```shell
php index.php interact
```

在主菜单选择 `4. 生成证书 / Token 签名密钥`，再选择 `RS256`、`EDDSA` 或其他算法。框架会直接使用 OpenSSL 或 Sodium，并在 `ssl` 目录下生成密钥。

> [!WARNING]
> `scripts/tokenKey.php` 仅作为传统兼容入口保留，随时可能删除。尚未迁移的自动化流程可查阅 [传统 Token 密钥脚本](md/scripts/tokenKey.md)，新项目不要依赖该脚本。

生成成功后会在 `ssl` 目录下得到 `.key` 和 `.pub` 文件，例如：

```text
ssl/RS256_413310.key
ssl/RS256_413310.pub
```

然后把绝对路径配置到对应字段：

```php
'api' => [
    'signer' => 'RS256',
    'access_private_key' => '/path/to/ssl/RS256_413310.key',
    'access_public_key' => '/path/to/ssl/RS256_413310.pub',
    'refresh_private_key' => '/path/to/ssl/RS256_998877.key',
    'refresh_public_key' => '/path/to/ssl/RS256_998877.pub',
]
```

## 客户端校验类型

`type` 决定了框架从哪里读取客户端 token。

### Bearer

标准 JWT 认证方式，请求头示例：

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOi...
```

### Header

从自定义请求头读取，键名由 `keyName` 决定：

```http
token: eyJ0eXAiOiJKV1QiLCJhbGciOi...
```

### Get

从查询参数读取：

```text
http://127.0.0.1:8680/api/getuser?token=eyJ0eXAiOiJKV1QiLCJhbGciOi...
```

### Post

从表单或请求体参数读取：

```text
token=eyJ0eXAiOiJKV1QiLCJhbGciOi...
```

### Cookie

调用 `$token->set()` 或 `$token->reSet()` 后：

- access token 会自动写入 `keyName`
- refresh token 会自动写入 `keyName_refresh`

后续请求会自动携带这两个 cookie。

### Session

调用 `$token->set()` 或 `$token->reSet()` 后：

- access token 会自动写入 session 的 `keyName`
- refresh token 会自动写入 session 的 `keyName_refresh`

后续请求会自动从 session 中提取。

## 如何使用

### 生成令牌

```php
<?php
namespace app\index\controller;

class test
{
    public function token_set($req)
    {
        $token = $req->token();
        $data = [
            'key' => 'user_id_123',
            'user_id' => 123,
            'user_name' => 'rcmaker',
        ];

        return $req->json($token->set($data));
    }
}
```

说明：

- `data['key']` 是必填项。
- 默认单设备登录开启时，框架会把 `key`、`guard`、客户端 IP 绑定到缓存里，用于“后登录顶掉前登录”。

返回结构示例：

参数 | 类型 | 说明 | 示例值
---|---|---|---
guard | string | 当前 guard | api
token_type | string | 当前取 token 方式 | Bearer
expires_in | int | access token 有效期，单位秒 | 7200
access_token | string | 访问令牌 | eyJ0eXAiOiJKV1QiLCJhbGci...
refresh_token | string | 刷新令牌，`refresh_disable=false` 时返回 | eyJ0eXAiOiJKV1QiLCJhbGci...

### 验证令牌

```php
<?php
namespace app\index\controller;

use RC\Exception\AuthException;

class test
{
    public function token_get($req)
    {
        $token = $req->token();

        try {
            return $req->json($token->get());
        } catch (AuthException $exception) {
            return $req->json(['code' => 0, 'msg' => $exception->getMessage()]);
        }
    }
}
```

说明：

- `get()`、`verify()`、`reSet()` 对外都会把鉴权失败统一转成 `RC\Exception\AuthException`。
- 公开使用时直接捕获 `AuthException` 即可，不需要在业务里分别捕获底层 JWT 异常。

如果只取某个字段：

```php
$userId = $req->token()->get('user_id');
```

### 刷新令牌

```php
<?php
namespace app\index\controller;

use RC\Exception\AuthException;

class test
{
    public function token_reset($req)
    {
        $token = $req->token();

        try {
            return $req->json($token->reSet());
        } catch (AuthException $exception) {
            return $req->json(['code' => 0, 'msg' => $exception->getMessage()]);
        }
    }
}
```

刷新时的注意事项：

- `reSet()` / `refreshToken()` 校验的是 refresh token，不是 access token。
- `Bearer`、`Header`、`Get`、`Post` 模式下，需要把 `refresh_token` 按当前 `type` 对应的方式传给服务端。
- `Cookie`、`Session` 模式下，框架会自动从 `keyName_refresh` 对应的 cookie 或 session 键读取 refresh token。

当前实现中，刷新后的返回结构与生成令牌保持一致，也会返回：

- `guard`
- `token_type`
- `expires_in`
- `access_token`
- `refresh_token`（未禁用时）

### 切换多点令牌

```php
<?php
namespace app\index\controller;

class test
{
    public function token_reset($req)
    {
        $token = $req->token('admin');
        return $req->json($token->get());
    }
}
```

这里的 `admin` 对应 `config/token.php` 里的 guard 名称。

## 备注

- 如果 `user`、`admin` 只配置少量字段，未配置部分会继续使用默认字段。
- 如果开启 `refresh_disable`，刷新接口不会返回 `refresh_token`。
- 如果关闭 `is_single_device`，则不会再执行“同账号同 guard 同 IP 的互斥登录”校验。



### 单设备登录

> 配置文件设置 `is_single_device=>1` 开启单设备登录,开启单设备登录需要缓存组件支持,调用默认缓存组件，也可以自动用助手函数自定义缓存组件 token($request,$guard = null,$cache = null)

单设登录开启后依据用户IP地址判断用户是否是单设备登录，其他设备再使用相同的$data[$key]验证令牌将会抛出异常

当其他设备满足 `同一个点的令牌`、`同一个key`、`不同的IP地址`则视为多设备登录将被禁止
