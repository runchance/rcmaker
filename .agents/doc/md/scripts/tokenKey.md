# 传统 Token 密钥脚本（备选）

> [!WARNING]
> 本页记录的是传统兼容脚本，随时可能删除。生成 Token 签名密钥请优先运行 `php index.php interact`，选择“生成证书 / Token 签名密钥”，并以 [交互式项目工具](md/interact.md) 为准。新项目和新自动化流程不应依赖本脚本。

`scripts/tokenKey.php` 暂时保留，用于兼容已有参数化流程。它可以生成 `RS256`、`RS384`、`RS512`、`ES256`、`ES384` 和 `EDDSA` 所需的私钥和公钥；以下内容仅作为备选方案说明。

生成后的密钥会写入项目根目录下的 `ssl` 目录，文件名包含算法名称和随机后缀，方便同时为 `access_token` 和 `refresh_token` 生成不同密钥。

## 运行前提

1. 生成 RSA、EC 密钥需要启用 PHP OpenSSL 扩展。
2. 生成 EDDSA 密钥需要启用 PHP Sodium 扩展。
3. 如果指定 OpenSSL 配置文件，该文件必须存在且可读。

## 支持算法

| 算法 | 密钥类型 | 说明 |
| --- | --- | --- |
| `RS256` | RSA | 用于 RS256 签名算法。 |
| `RS384` | RSA | 用于 RS384 签名算法。 |
| `RS512` | RSA | 用于 RS512 签名算法。 |
| `ES256` | EC | 使用 P-256 曲线，适用于 ES256。 |
| `ES384` | EC | 使用 P-384 曲线，适用于 ES384。 |
| `EDDSA` | Ed25519 | 使用 Sodium 生成 Ed25519 密钥。 |

## 命令格式

如果当前机器没有安装系统 PHP，请先按 [安装与启动](md/install.md) 的说明下载对应平台和架构的独立 PHP，并统一安装为 `php` 命令。ZIP 命名与支持矩阵见 [独立 PHP CLI 下载](md/download.md)。

```bash
php -v
```

后文统一使用 `php` 命令执行。

```bash
php ./scripts/tokenKey.php <algorithm> [opensslConfig]
```

参数说明：

- `<algorithm>`：必填，指定要生成的密钥算法。
- `[opensslConfig]`：可选，OpenSSL 配置文件路径，仅 RSA、EC 算法使用。

查看帮助：

```bash
php ./scripts/tokenKey.php --help
```

## 使用示例

生成 RS256 密钥：

```bash
php ./scripts/tokenKey.php RS256
```

生成 EDDSA 密钥：

```bash
php ./scripts/tokenKey.php EDDSA
```

指定 OpenSSL 配置文件：

```bash
php ./scripts/tokenKey.php RS256 /etc/ssl/openssl.cnf
```

## 输出文件

生成成功后，脚本会输出私钥和公钥的绝对路径。例如：

```text
Generate RS256 private and public keys success.
privateKey      /path/to/rcmaker/ssl/RS256_123456.key
publicKey       /path/to/rcmaker/ssl/RS256_123456.pub
```

文件命名规则如下：

```text
ssl/RS256_<random>.key
ssl/RS256_<random>.pub
ssl/EDDSA_<random>.key
ssl/EDDSA_<random>.pub
```

## 配置使用

将生成的私钥和公钥绝对路径写入 Token 配置：

```php
'signer' => 'RS256',
'access_private_key' => '/path/to/rcmaker/ssl/RS256_123456.key',
'access_public_key' => '/path/to/rcmaker/ssl/RS256_123456.pub',
```

如果启用了 refresh token，建议为 refresh token 单独生成一组密钥，并配置到 `refresh_private_key` 和 `refresh_public_key`。


