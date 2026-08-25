# 独立 PHP CLI 下载

rcmaker 为 PHP `8.1` 至 `8.5` 提供可直接运行的 PHP CLI。每个 ZIP 压缩包内只有一个 `php` 或 `php.exe`，适合在未安装系统 PHP 的机器上运行脚本、创建项目或启动 rcmaker。

> [!IMPORTANT]
> 本页公开列出独立 PHP CLI。安装脚本还会从 `/download/composer` 自动安装 Composer。构建单文件程序所需的 Micro SFX 和源码保护组件不提供手工下载入口；请使用 `php index.php interact`，由框架自动选择、校验并获取所需资源。

## 一键安装

Linux 和 macOS：

```shell
curl -fsSL https://rcmaker.runchance.com/download/install-php.sh | sh
```

Windows PowerShell：

```powershell
irm https://rcmaker.runchance.com/download/install-php.ps1 | iex
```

脚本默认安装 PHP 8.5 和 Composer，并自动处理平台、架构、执行权限和命令路径。参数用法与手动安装命令见 [安装与启动](md/install.md)。

## 安装指定版本

支持的版本为 `8.1`、`8.2`、`8.3`、`8.4`、`8.5`。下面以 PHP 8.4 为例。

Linux / macOS：

```shell
curl -fsSL https://rcmaker.runchance.com/download/install-php.sh | sh -s -- 8.4
```

Windows PowerShell：

```powershell
& ([scriptblock]::Create((irm https://rcmaker.runchance.com/download/install-php.ps1))) -Version 8.4
```

需要升降版本时重复执行对应命令即可。请先停止正在运行的服务，并在完成后使用 `php -v` 确认版本。完整注意事项见 [切换 PHP 版本](md/install.md#切换-php-版本)。

## 支持矩阵

| 操作系统 | 架构 | 状态 | ZIP 内文件 |
| --- | --- | --- | --- |
| Linux | `x86_64` | 支持 | `php` |
| Linux | `aarch64` | 支持 | `php` |
| macOS | `x86_64` | 支持 | `php` |
| macOS | `aarch64` | 支持 | `php` |
| Windows | `x86_64` | 支持 | `php.exe` |
| Windows | `aarch64` | 暂未提供 | - |

架构别名会统一处理：`amd64`、`x64` 对应 `x86_64`，`arm64` 对应 `aarch64`。

## 文件命名

将 `{version}` 替换为 `8.1`、`8.2`、`8.3`、`8.4` 或 `8.5`：

```text
php{version}-{platform}-{arch}.zip
```

例如，Linux AArch64 的 PHP 8.5 独立运行时是：

```text
https://rcmaker.runchance.com/download/php8.5-linux-aarch64.zip
```

## PHP 8.5 快速下载

| 目标平台 | PHP CLI |
| --- | --- |
| Linux x86_64 | [下载 ZIP](https://rcmaker.runchance.com/download/php8.5-linux-x86_64.zip) |
| Linux AArch64 | [下载 ZIP](https://rcmaker.runchance.com/download/php8.5-linux-aarch64.zip) |
| macOS Intel | [下载 ZIP](https://rcmaker.runchance.com/download/php8.5-macos-x86_64.zip) |
| macOS Apple Silicon | [下载 ZIP](https://rcmaker.runchance.com/download/php8.5-macos-aarch64.zip) |
| Windows x86_64 | [下载 ZIP](https://rcmaker.runchance.com/download/php8.5-windows-x86_64.zip) |

下载其他 PHP 版本时，只需把链接文件名中的 `8.5` 替换为所需版本。

## 解压与使用

Linux 和 macOS 的压缩包内文件名为 `php`，解压后需要增加执行权限：

```shell
unzip php8.5-linux-x86_64.zip
chmod 0755 php
./php -v
```

Windows 的压缩包内文件名为 `php.exe`：

```powershell
Expand-Archive .\php8.5-windows-x86_64.zip -DestinationPath .\php-runtime
.\php-runtime\php.exe -v
```

完整的环境安装命令和项目启动步骤见 [安装与启动](md/install.md)。一键脚本会同时安装 Composer；已经有 PHP 时，也可以按照该页说明单独下载 Composer。

## 构建与源码保护

在项目根目录启动交互工具：

```shell
php index.php interact
```

选择“构建二进制可执行文件”或“加密脚本”后，操作系统和架构默认自动识别。框架会按目标环境获取内部构建资源，并校验下载、解压和文件类型；用户不需要也不应手工下载 Micro SFX 或源码保护组件。

> [!NOTE]
> 运行交互工具的宿主 PHP 必须启用 `zip` 扩展，并且需要启用 `curl` 或设置 `allow_url_fopen=1`。源码保护流程使用构建机平台对应的工具，加密载荷不区分平台；独立 PHP 和 Micro SFX 仍按目标平台、架构下载。

## 传统脚本（兼容备选）

`scripts/buildBin.php` 和 `scripts/encryptPhp.php` 目前仍可用于旧 CI 和旧自动化流程，但随时可能删除。新项目应使用 [交互式项目工具](md/interact.md)，不要建立新的传统脚本依赖。
