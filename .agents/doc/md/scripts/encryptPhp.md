# 传统 PHP 加密脚本（备选）

> [!WARNING]
> 本页记录的是传统兼容脚本，随时可能删除。PHP 文件、目录加密和单文件程序生成请优先运行 `php index.php interact`，选择“加密 PHP 文件或目录”，并以 [交互式项目工具](md/interact.md) 为准。新项目和新自动化流程不应依赖本脚本。

`scripts/encryptPhp.php` 暂时保留，用于兼容已有 CI 或参数化加密流程，也可处理独立 PHP 文件和非 rcmaker 项目；以下内容仅作为备选方案说明。

## 能力边界

- 加密单个 PHP 文件
- 加密整个项目目录，可排除指定路径
- 下载匹配平台、架构和 PHP 版本的独立运行时
- 把加密结果与 Micro SFX 合并为单文件程序
- 支持 Linux x86_64/AArch64、macOS x86_64/AArch64、Windows x86_64

Windows AArch64 暂未提供。PHP 运行时和 Micro SFX 支持 `8.1` 至 `8.5`。

> [!IMPORTANT]
> 该脚本执行加密机平台和架构对应的 `rcmakerbeast`。加密后的 PHP 载荷不区分平台；`--platform`、`--arch` 用于选择可选的独立 PHP、Micro SFX 和最终单文件程序格式，因此可以在一台构建机上为多个受支持平台生成产物。

## 环境要求

宿主 PHP 必须启用：

- `openssl`，用于加密流程
- `zip`，用于解压下载制品
- `curl`，或设置 `allow_url_fopen=1`
- 生成单文件程序时还需要 `phar`，并设置 `phar.readonly=0`

Micro SFX 和源码保护组件由框架自动选择、校验与获取，不提供手工下载入口。

## 命令格式

```shell
php ./scripts/encryptPhp.php --input=源路径 --output=目标路径 [选项]
```

查看脚本内置帮助：

```shell
php ./scripts/encryptPhp.php --help
```

| 参数 | 说明 |
| --- | --- |
| `--input=path` | 必填，源 PHP 文件或项目目录 |
| `--output=path` | 必填，加密输出文件或目录 |
| `--with-php=8.5` | 运行时或 Micro SFX 版本，默认 `8.1` |
| `--platform=auto` | `auto`、`linux`、`macos`、`windows`，默认自动识别 |
| `--arch=auto` | `auto`、`x86_64`、`aarch64`，默认自动识别 |
| `--entry=index.php` | 目录生成单文件程序时的相对入口 |
| `--build-bin=path` | 继续生成单文件可执行程序 |
| `--custom-ini=...` | 为单文件程序注入 ini 文本或 ini 文件 |
| `--download-runtime` | 下载并解压匹配的独立 PHP 运行时 |
| `--runtime-output=path` | 自定义运行时保存位置，并自动启用下载 |
| `--exclude-files=a,b` | 目录模式下排除相对路径 |
| `--force` | 覆盖已有加密输出、运行时或单文件程序 |

独立运行时默认保存在加密结果旁边，Unix 文件名为 `php`，Windows 为 `php.exe`。如果目标已经存在，脚本默认报错；确认需要替换时必须传 `--force`。

## 加密单个文件

```shell
php ./scripts/encryptPhp.php \
  --input=./demo.php \
  --output=./dist/demo.php
```

下载 PHP 8.5 独立运行时到输出目录：

```shell
php ./scripts/encryptPhp.php \
  --input=./demo.php \
  --output=./dist/demo.php \
  --with-php=8.5 \
  --download-runtime
```

结果通常是：

```text
dist/
├─ demo.php
└─ php
```

在 Windows 上最后一个文件是 `php.exe`。

## 加密项目目录

```shell
php ./scripts/encryptPhp.php \
  --input=./project \
  --output=./dist/project \
  --exclude-files=.git,runtime
```

Linux 上同时把运行时安装到统一位置：

```shell
sudo php ./scripts/encryptPhp.php \
  --input=./project \
  --output=./dist/project \
  --with-php=8.5 \
  --download-runtime \
  --runtime-output=/usr/local/bin/php
```

如果 `/usr/local/bin/php` 已存在且确认需要替换，请追加 `--force`。

## 生成单文件程序

单文件脚本：

```shell
php -d phar.readonly=0 ./scripts/encryptPhp.php \
  --input=./demo.php \
  --output=./dist/demo.php \
  --build-bin=./dist/demo.bin \
  --with-php=8.5
```

目录项目需要指定入口：

```shell
php -d phar.readonly=0 ./scripts/encryptPhp.php \
  --input=./project \
  --output=./dist/project \
  --entry=public/index.php \
  --build-bin=./dist/project.bin \
  --with-php=8.5
```

Windows 建议让输出以 `.exe` 结尾：

```powershell
php -d phar.readonly=0 .\scripts\encryptPhp.php `
  --input=.\project `
  --output=.\dist\project `
  --entry=index.php `
  --build-bin=.\dist\project.exe `
  --with-php=8.5
```

## 显式指定目标

正常情况下使用 `auto` 即可。需要在 CI 中固定目标时，可显式写出当前 Runner 的平台和架构：

```shell
php ./scripts/encryptPhp.php \
  --input=./project \
  --output=./dist/project \
  --platform=linux \
  --arch=aarch64
```

如果参数与宿主机器不一致，脚本会在下载和执行加密工具前停止。

## 构建原理

1. 根据平台和架构选择对应的源码保护组件
2. 校验压缩包内只有预期的可执行文件
3. 使用该工具加密文件或临时项目副本
4. 需要 `--build-bin` 时，把加密结果构建为 Phar
5. 获取目标 PHP 版本、平台和架构对应的 Micro SFX
6. 拼接 Micro SFX、自定义 ini 头和加密后的 Phar

脚本会从框架资源服务自动获取所需制品。独立 PHP 运行时、Micro SFX 与源码保护工具用途不同，脚本会按预期文件严格校验，不会互相替代。内部构建资源的地址和文件名不属于公开接口，请勿依赖。

## 注意事项

1. 单文件程序只能在对应平台和架构运行。
2. `--entry` 必须是加密输出目录内的相对路径。
3. `--exclude-files` 使用英文逗号分隔，路径相对于输入目录。
4. 使用 `--force` 前确认输出路径，目录模式会覆盖目标内容。
5. 生成后应在目标平台执行一次完整启动或业务冒烟测试，再交付产物。
