# 传统二进制脚本（备选）

> [!WARNING]
> 本页记录的是传统兼容脚本，随时可能删除。构建单文件可执行程序请优先运行 `php index.php interact`，选择“构建二进制可执行文件”，并以 [交互式项目工具](md/interact.md) 为准。新项目和新自动化流程不应依赖本脚本。

`scripts/buildBin.php` 暂时保留，用于兼容已有 CI 或参数化构建流程。它可以把当前 rcmaker 项目的代码、依赖和 PHP Micro 运行时合并为单个可执行程序；以下内容仅作为备选方案说明。

## 支持范围

| 目标 | 输出文件 |
| --- | --- |
| Linux x86_64 / AArch64 | `build/rcmaker.bin` |
| macOS x86_64 / Apple Silicon | `build/rcmaker.bin` |
| Windows x86_64 | `build/rcmaker.exe` |

PHP Micro 支持 `8.1` 至 `8.5`。Windows AArch64 暂未提供，脚本会明确拒绝该组合。Micro SFX 和源码保护组件由框架自动选择、校验与获取，不提供手工下载入口。

## 构建环境

执行脚本的宿主 PHP 需要：

- 启用 `phar` 和 `zip` 扩展
- 设置 `phar.readonly=0`
- 启用 `curl`，或设置 `allow_url_fopen=1`
- 使用 `--encrypt` 时，需要有当前宿主平台和架构对应的 `rcmakerbeast`

> [!IMPORTANT]
> `--platform`、`--arch` 始终表示最终程序的目标环境。启用加密时，脚本执行宿主平台和架构对应的 `rcmakerbeast`，再与目标平台的 Micro SFX 组合；加密载荷不区分平台，因此支持跨平台、跨架构加密构建。

## 命令格式

在 rcmaker 项目根目录执行：

```shell
php -d phar.readonly=0 ./scripts/buildBin.php [选项]
```

| 参数 | 默认值 | 说明 |
| --- | --- | --- |
| `--with-php=8.5` | `8.4` | 单文件程序内置的 PHP Micro 版本，支持 `8.1` 至 `8.5` |
| `--platform=auto` | `auto` | `auto`、`linux`、`macos`、`windows` |
| `--arch=auto` | `auto` | `auto`、`x86_64`、`aarch64` |
| `--custom-ini=...` | 空 | 注入 ini 文本或读取 `.ini` 文件 |
| `--exclude-files=a,b` | 空 | 在默认规则之外额外排除文件或目录，使用英文逗号分隔 |
| `--encrypt` | 关闭 | 打包前使用宿主平台对应的 `rcmakerbeast` 加密临时项目副本 |

`auto` 根据 `PHP_OS_FAMILY` 和 `php_uname('m')` 识别当前机器。`amd64`、`x64` 会映射为 `x86_64`，`arm64` 会映射为 `aarch64`。

## 常用示例

按当前机器自动选择平台和架构，内置 PHP 8.5：

```shell
php -d phar.readonly=0 ./scripts/buildBin.php --with-php=8.5
```

在 Linux AArch64 上构建并保护源码：

```shell
php -d phar.readonly=0 ./scripts/buildBin.php \
  --with-php=8.5 \
  --platform=linux \
  --arch=aarch64 \
  --encrypt
```

在 macOS Apple Silicon 上构建：

```shell
php -d phar.readonly=0 ./scripts/buildBin.php \
  --with-php=8.5 \
  --platform=macos \
  --arch=aarch64
```

Windows PowerShell：

```powershell
php -d phar.readonly=0 .\scripts\buildBin.php --with-php=8.5 --platform=windows --arch=x86_64
```

## 自定义 PHP 配置

直接传入一条或多条配置，分号会转换为换行：

```shell
php -d phar.readonly=0 ./scripts/buildBin.php \
  --custom-ini="memory_limit=256M;post_max_size=50M"
```

也可以读取 ini 文件：

```shell
php -d phar.readonly=0 ./scripts/buildBin.php --custom-ini=./php.ini
```

## 排除文件和目录

```shell
php -d phar.readonly=0 ./scripts/buildBin.php \
  --exclude-files=".env,LICENSE,storage/private"
```

参数值是相对于项目根目录的路径：匹配文件时只排除该文件，匹配目录时会排除目录及其全部子目录和文件。路径可使用 `/` 或 `\\`，可以带末尾斜杠；脚本会统一规范化。出于安全考虑，路径不能包含 `..`。

例如，额外排除本地配置和一个业务目录：

```shell
php -d phar.readonly=0 ./scripts/buildBin.php \
  --exclude-files=".env,storage/private"
```

默认排除范围与交互式构建一致，包括 `*.md`、`*.markdown`、`*.rst`、`*.map`，AI/IDE/版本控制目录，`build/`、`runtime/`、`scripts/`、`tests/`、`tools/`、`node_modules/`、`coverage/`、`official/`，包管理与测试配置，以及 `vendor/` 依赖附带的测试、示例、基准和文档目录，不需要重复填写。

默认规则不会笼统排除业务 JSON、YAML、证书、模板、`public/` 资源或 `.env`。如需把 `.env` 外置，应使用 `--exclude-files=.env`，并在部署时将它放到可执行程序旁。完整默认范围见 [交互式项目工具](md/interact.md#构建二进制程序)。

## 下载与构建流程

脚本执行顺序如下：

1. 校验 PHP 版本、目标平台和架构
2. 复制项目到 `build/rcmaker-phar-src` 临时目录
3. 启用 `--encrypt` 时，获取并执行当前平台对应的源码保护组件
4. 将临时目录构建为 `rcmaker.phar`
5. 获取目标 PHP 版本、平台和架构对应的 Micro SFX
6. 依次拼接 Micro SFX、自定义 ini 头和 Phar
7. 清理中间文件，只保留最终可执行程序

所有平台的 Phar Stub 都使用 `index.php`。Windows Micro EXE 以 `micro` SAPI 运行，并把可执行文件所在目录作为工作目录；框架内的 `RC\Cli\WindowsRuntime` 会从 Phar 运行路径解析当前 `.exe`，再通过内部子进程参数分别派生主 APP、自定义 APP 和普通进程，避免 Workerman 在一个 Windows PHP 进程中初始化多个 Worker。已废弃的 `windows.php` 默认不进入产物；`index.php` 不能通过 `--exclude-files` 排除，否则构建会在生成 Phar 前明确失败。

脚本会从框架资源服务自动获取所需 ZIP。每个 ZIP 都会通过 `ZipArchive` 校验只能包含一个预期文件；内部地址和文件名不属于公开接口，请勿依赖。

## 运行产物

Linux 或 macOS：

```shell
cp .env.example ./build/.env
./build/rcmaker.bin start
```

Windows：

```bat
copy .env.example build\.env
build\rcmaker.exe start
```

> [!WARNING]
> 可执行程序只能在对应平台和架构运行。更新代码后需要重新打包并重启，不支持通过 `reload` 把外部源码热更新进已生成的单文件程序。

运行时日志和临时文件会写到可执行程序所在目录的 `runtime/`。通常还应把 `.env` 放在可执行程序同目录；业务需要写入 `public/` 时，也应把可写目录外置，并通过配置指向该目录。

```ini
[app]
public_path = /home/www/rcmaker/build/public
```

需要注册为 Linux 服务时，继续阅读 [Linux 服务注册](md/scripts/systemd.md)。
