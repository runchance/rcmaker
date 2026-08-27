# 安装与启动

已经有 PHP `8.1` 至 `8.5` 和 Composer，可以直接跳到“创建项目”。全新环境运行一条命令即可同时准备 PHP 8.5 和 Composer。

## 安装 PHP 与 Composer

### Linux / macOS

下面的命令会自动识别系统和 CPU 架构，并将 PHP 8.5 与 Composer 安装到 `/usr/local/bin`：

```shell
curl -fsSL https://rcmaker.runchance.com/download/install-php.sh | sh
```

需要安装其他版本或指定目录时：

```shell
curl -fsSL https://rcmaker.runchance.com/download/install-php.sh | sh -s -- 8.4 "$HOME/.local/bin"
```

脚本支持 Linux、macOS 的 `x86_64` 和 `aarch64`。安装完成后，`php` 和 `composer` 可以直接在终端中运行。指定自定义目录时，请确保该目录已经加入 `PATH`。

### Windows

在任意目录打开 PowerShell，运行：

```powershell
irm https://rcmaker.runchance.com/download/install-php.ps1 | iex
```

脚本会将 PHP 8.5 和 Composer 安装到 `%LOCALAPPDATA%\rcmaker\bin`，并加入当前用户的 `PATH`。安装位置与项目目录相互独立，因此创建项目后不需要移动 `php.exe`；创建项目后直接运行 `php index.php start`。Windows 当前提供 x86_64 版本。

安装后确认两个命令都可用：

```powershell
php -v
composer --version
```

如果旧终端没有识别到新命令，关闭后重新打开 PowerShell 即可。

需要直接安装指定版本时，例如 PHP 8.4：

```powershell
& ([scriptblock]::Create((irm https://rcmaker.runchance.com/download/install-php.ps1))) -Version 8.4
```

> [!TIP]
> 一键脚本从 `https://rcmaker.runchance.com/download/composer` 获取 Composer。构建独立程序所需的 Micro SFX 和源码保护组件仍由 `php index.php interact` 自动处理，不需要手工下载。

## 创建项目

先确认环境：

```shell
php -v
composer --version
```

Linux 和 macOS：

```shell
composer create-project runchance/rcmaker
cd rcmaker
cp .env.example .env
php index.php start
```

Windows：

```bat
composer create-project runchance/rcmaker
cd rcmaker
copy .env.example .env
php index.php start
```

浏览器访问 `http://127.0.0.1:8680/`。看到 `Hello rcmaker!` 后，可以继续完成 [5 分钟开始](md/quick-start.md) 中的第一个 JSON 接口。

## 只安装 Composer

已经有可用的 PHP，只缺少 Composer 时，可以直接下载安装。

Linux 和 macOS：

```shell
sudo mkdir -p /usr/local/bin && sudo curl -fsSL https://rcmaker.runchance.com/download/composer -o /usr/local/bin/composer && sudo chmod 0755 /usr/local/bin/composer
composer --version
```

Windows PowerShell 可以先在当前目录使用 Composer，不需要设置执行权限：

```powershell
irm https://rcmaker.runchance.com/download/composer -OutFile composer
php .\composer --version
```

此时将文档中的 `composer` 换成 `php .\composer` 即可。需要全局使用 `composer` 命令时，直接运行前面的 Windows 一键安装命令。

## 启动已有项目

项目源码已经存在时，只需准备 `.env` 并启动：

```shell
cp .env.example .env
php index.php start
```

Windows 使用：

```bat
copy .env.example .env
php index.php start
```

`windows.bat` 仍可作为双击启动的便捷包装，但它内部同样执行 `php index.php start`。`windows.php` 已废弃，只用于旧命令兼容，不再承载框架启动逻辑。

## 手动安装 PHP 与 Composer

一键脚本无法使用，或者需要审查每一步时，再展开对应平台。

<details>
<summary>Linux 手动安装命令</summary>

```shell
ARCH="$(uname -m)"
case "$ARCH" in
  x86_64|amd64) TARGET_ARCH="x86_64" ;;
  aarch64|arm64) TARGET_ARCH="aarch64" ;;
  *) echo "Unsupported architecture: $ARCH" >&2; exit 1 ;;
esac

ARCHIVE="php8.5-linux-${TARGET_ARCH}.zip"
curl -fLO "https://rcmaker.runchance.com/download/${ARCHIVE}"
sudo mkdir -p /usr/local/bin
unzip -p "${ARCHIVE}" php | sudo tee /usr/local/bin/php >/dev/null
sudo chmod 0755 /usr/local/bin/php
sudo curl -fsSL https://rcmaker.runchance.com/download/composer -o /usr/local/bin/composer
sudo chmod 0755 /usr/local/bin/composer
rm "${ARCHIVE}"
php -v
composer --version
```

</details>

<details>
<summary>macOS 手动安装命令</summary>

```shell
ARCH="$(uname -m)"
case "$ARCH" in
  x86_64|amd64) TARGET_ARCH="x86_64" ;;
  arm64|aarch64) TARGET_ARCH="aarch64" ;;
  *) echo "Unsupported architecture: $ARCH" >&2; exit 1 ;;
esac

ARCHIVE="php8.5-macos-${TARGET_ARCH}.zip"
curl -fLO "https://rcmaker.runchance.com/download/${ARCHIVE}"
unzip -p "${ARCHIVE}" php > ./php
chmod 0755 ./php
sudo mkdir -p /usr/local/bin
sudo mv ./php /usr/local/bin/php
sudo curl -fsSL https://rcmaker.runchance.com/download/composer -o /usr/local/bin/composer
sudo chmod 0755 /usr/local/bin/composer
rm "${ARCHIVE}"
php -v
composer --version
```

如果 macOS 阻止首次运行，请确认文件来自 rcmaker 官方地址，再执行：

```shell
sudo xattr -d com.apple.quarantine /usr/local/bin/php
```

</details>

<details>
<summary>Windows 手动安装命令</summary>

```powershell
$archive = 'php8.5-windows-x86_64.zip'
Invoke-WebRequest "https://rcmaker.runchance.com/download/$archive" -OutFile $archive
New-Item -ItemType Directory -Force -Path php | Out-Null
Expand-Archive -Path $archive -DestinationPath php -Force
Remove-Item $archive
.\php\php.exe -v
```

解压后应存在 `php\php.exe`。继续下载 Composer 时，不需要设置执行权限：

```powershell
irm https://rcmaker.runchance.com/download/composer -OutFile composer
.\php\php.exe .\composer --version
```

手动安装方式下，使用 `.\php\php.exe .\composer create-project runchance/rcmaker` 创建项目。如果希望直接使用全局 `php` 和 `composer` 命令，请使用前面的一键安装方式。

</details>

## 使用系统 PHP

使用自行安装的 PHP 时，需要保证 CLI 环境具备项目依赖的扩展，并且没有禁用 Workerman 启动需要的系统函数。Windows 没有 `pcntl` 和 `posix` 属于正常情况，框架会使用 Windows 启动方式。

<details>
<summary>查看 Linux 常用系统函数</summary>

```text
stream_socket_server
stream_socket_client
pcntl_signal_dispatch
pcntl_signal
pcntl_alarm
pcntl_fork
pcntl_wait
posix_getuid
posix_getpwuid
posix_kill
posix_setsid
posix_getpid
posix_getpwnam
posix_getgrnam
posix_getgid
posix_setgid
posix_initgroups
posix_setuid
posix_isatty
proc_open
proc_get_status
proc_close
shell_exec
exec
putenv
getenv
```

</details>

## 切换 PHP 版本

独立 PHP CLI 支持 `8.1` 至 `8.5`。切换前先停止正在运行的 rcmaker 服务，然后重新执行安装脚本；脚本会覆盖当前安装目录中的 PHP，并保留相同的命令位置。

Linux / macOS，例如切换到 PHP 8.3：

```shell
curl -fsSL https://rcmaker.runchance.com/download/install-php.sh | sh -s -- 8.3
```

Windows PowerShell，例如切换到 PHP 8.3：

```powershell
& ([scriptblock]::Create((irm https://rcmaker.runchance.com/download/install-php.ps1))) -Version 8.3
```

切换完成后确认版本，再重新启动项目：

```shell
php -v
composer --version
```

> [!IMPORTANT]
> 切换的是当前机器的 PHP CLI，不会改变已经生成的 `.bin` 或 `.exe`。需要更换独立程序的 PHP 版本时，请重新运行 `php index.php interact` 并重新构建。切换到较低版本前，也要确认项目依赖支持该 PHP 版本。

全部平台、架构和 ZIP 命名见 [独立 PHP CLI 下载](md/download.md)。旧式 `php85`、`php85_aarch64` 或 `php85.zip` 地址已经停用。
