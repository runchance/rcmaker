# 交互式项目工具

rcmaker 内置跨平台交互式控制台，可以通过逐级菜单完成二进制构建、PHP 加密、Linux 服务注册和 Token 签名密钥生成。日常使用不需要记忆脚本参数。

```shell
php index.php interact
```

> [!IMPORTANT]
> `interact` 是构建、加密、服务管理和签名密钥操作的推荐入口，由框架内的 `RC\Cli` 类独立实现，不会调用或包含 `scripts/*.php`。传统脚本只作为兼容备选，随时可能删除；新项目和新自动化流程不应依赖 `scripts/*.php`。

## 主菜单

启动后显示以下功能：

```text
1. 构建二进制可执行文件
2. 加密 PHP 文件或目录
3. 注册或移除 Linux systemd 服务
4. 生成证书 / Token 签名密钥
5. 退出
```

输入编号并按 Enter 进入对应功能。提示中方括号内是默认值，直接按 Enter 即可采用。

rcmaker 会在终端支持 ANSI 时自动启用颜色；输出重定向到文件时会自动关闭颜色。也可以显式控制：

```shell
RCMAKER_COLOR=always php index.php interact
RCMAKER_COLOR=never php index.php interact
NO_COLOR=1 php index.php interact
```

## 构建二进制程序

选择 `1` 后依次设置：

| 配置 | 默认值 | 说明 |
| --- | --- | --- |
| 目标操作系统 | `auto` | 自动识别，也可选择 Linux、macOS、Windows |
| 目标架构 | `auto` | 自动识别，也可选择 `x86_64`、`aarch64` |
| PHP 版本 | `8.4` | 支持 PHP `8.1` 至 `8.5` |
| 额外排除路径 | 空 | 在默认排除规则之外继续排除文件或目录，多个路径用英文逗号分隔 |
| 源码加密 | 否 | 启用后使用构建机平台对应的 `rcmakerbeast` |
| 自定义 INI | 空 | 可以输入 ini 内容，也可以填写 `.ini` 文件路径 |

确认后由框架内的 `RC\Cli\BuildBinary` 执行构建：

| 目标平台 | 输出 |
| --- | --- |
| Linux / macOS | `build/rcmaker.bin` |
| Windows | `build/rcmaker.exe` |

框架会自动处理 `phar.readonly=0`，不需要修改系统 `php.ini`。构建过程会校验平台、架构、Phar 入口和下载 ZIP 的内部文件名。

独立程序不带参数时默认按前台 `start` 启动：

```shell
# Linux / macOS
./build/rcmaker.bin

# 需要后台运行时仍可显式传参
./build/rcmaker.bin start -d
```

Windows 可以直接双击 `build/rcmaker.exe`，也可以在终端运行 `build\rcmaker.exe`，两种方式都等同于 `build\rcmaker.exe start`。框架会在 Windows 独立程序输出 Banner 前把进程代码页切换为 UTF-8，中文配置无需转成 GBK。

> [!IMPORTANT]
> 所有目标平台的 Phar Stub 都使用 `index.php`。Windows 启动与子进程管理由框架内的 `RC\Cli\WindowsRuntime` 接管，构建器默认排除已废弃的 `windows.php`，因此更新框架包即可同步 Windows 启动逻辑。

> [!NOTE]
> Windows AArch64 暂未提供。源码加密使用构建机平台和架构对应的 `rcmakerbeast`，加密载荷本身不区分平台；Micro SFX 按目标平台和架构选择，因此启用加密后也可以跨平台、跨架构构建。

构建器默认只收集运行所需内容，以下文件不会进入单文件程序：

- Markdown、reStructuredText 和源码映射文件：`*.md`、`*.markdown`、`*.rst`、`*.map`
- AI、IDE、版本控制和临时目录：`.agents/`、`.cursor/`、`.claude/`、`.codex/`、`.gemini/`、`.github/`、`.git/`、`.idea/`、`.vscode/`、`.tmp/`
- 构建、测试和开发工具目录：`build/`、`runtime/`、`scripts/`、`tests/`、`tools/`、`node_modules/`、`coverage/`、`official/`
- 包管理和开发清单：`composer.json`、`composer.lock`、`package.json`、各类前端 lock 文件、PHPUnit/PHPStan/Psalm 配置、Docker Compose 文件等
- 旧 Windows 入口与便捷脚本：`windows.php`、`windows.bat`
- `vendor/` 中依赖包附带的测试、示例、基准和文档目录

默认规则不会笼统排除 JSON、YAML、证书、模板或 `public/` 资源，避免误删业务运行时文件。`.env` 也会保留；不希望把私密配置打进程序时，应在“额外排除路径”中填写 `.env`，并在部署时把它放到可执行程序旁。

二进制默认直接读取包内 `public/` 和 `view/`，静态应用可以从 PHAR 预加载及返回资源，不需要附带外部静态目录。包内文件只读；上传目录、运行时生成文件或需要单独更新的前端资源，应通过 `.env` 的 `app.public_path` 指向外部绝对路径。日志、PID、缓存和模板编译结果始终写入外部 `runtime/`。

## 加密 PHP

选择 `2` 后可以加密单个 PHP 文件或整个目录，并可继续下载运行时或生成单文件程序。

只加密源码时，无需选择 PHP 版本、目标系统或目标架构。交互工具会直接使用当前主机对应的 `rcmakerbeast`；只有同时下载独立 PHP 或生成单文件程序时，才会继续询问这些目标参数。

主要配置包括：

| 配置 | 说明 |
| --- | --- |
| 输入路径 | 必填，相对于项目根目录，也支持绝对路径 |
| 输出路径 | 必填，菜单会根据输入自动给出建议路径 |
| 目标平台与架构 | 仅下载运行时或生成单文件程序时显示；默认自动识别 |
| PHP 版本 | 仅下载运行时或生成单文件程序时显示，默认 `8.4` |
| 排除路径 | 仅目录模式显示，可排除文件或目录 |
| 覆盖目标 | 默认关闭，防止误覆盖已有文件 |
| 下载独立 PHP | 可将匹配的 `php` 或 `php.exe` 放在输出旁 |
| 生成单文件程序 | 可继续设置输出文件、Phar 入口和自定义 INI |

框架内的 `RC\Cli\EncryptPhp` 会拒绝危险路径组合。例如，输出目录不能是输入目录的祖先，避免覆盖操作误删源代码。原地加密必须明确启用覆盖。

> [!IMPORTANT]
> 源码加密会执行当前构建机平台和架构对应的 `rcmakerbeast`，加密结果可以交付到其他受支持平台。请先保留源码备份，并在目标平台验证加密结果后再交付。

## Linux systemd 服务

选择 `3` 可以注册或移除 systemd 服务。该功能由 `RC\Cli\SystemdService` 实现，只在 Linux 显示实际配置流程。

```shell
sudo php index.php interact
```

注册服务时可以选择：

- 使用 PHP 执行项目 `index.php`
- 使用已经构建的 `build/rcmaker.bin`
- 服务名称
- 服务运行用户
- PHP 可执行文件绝对路径

框架会生成 `/etc/systemd/system/{name}.service`，执行 `systemctl daemon-reload` 并启用服务。修改系统前会再次要求确认。macOS 和 Windows 选择该功能时只会提示平台不支持，不会执行命令。

## Token 签名密钥

选择 `4` 可以生成以下算法的私钥和公钥：

- `RS256`、`RS384`、`RS512`
- `ES256`、`ES384`
- `EDDSA` / Ed25519

RSA 和 EC 使用 OpenSSL，EdDSA 使用 Sodium。生成文件写入项目 `ssl/` 目录：

```text
ssl/RS256_123456.key
ssl/RS256_123456.pub
```

私钥在 Unix 上设置为 `0600`，公钥设置为 `0644`。文件名使用随机后缀，已有文件不会被覆盖。

## 平台差异

| 能力 | Linux | macOS | Windows |
| --- | --- | --- | --- |
| 二进制构建 | 支持 x86_64 / AArch64 | 支持 Intel / Apple Silicon | 支持 x86_64 |
| PHP 加密 | 支持 | 支持 | 支持 x86_64 |
| systemd 服务 | 支持，需要 root | 不支持 | 不支持 |
| Token 密钥 | 支持 | 支持 | 支持 |

## 中断与错误

- 在最终确认前选择 `n`，不会执行实际操作。
- 按 `Ctrl+C` 可以立即终止控制台。
- 输入流关闭时，控制台会正常退出。
- 单项执行失败后会显示错误并返回主菜单，不会启动 rcmaker 服务。
- 下载需要启用 `curl`，或设置 `allow_url_fopen=1`；解压制品需要启用 `zip`。

传统参数脚本目前仍可用于尚未迁移的 CI 或无人值守流程，具体见 [传统二进制脚本](md/scripts/buildBin.md)、[传统加密脚本](md/scripts/encryptPhp.md)、[传统服务脚本](md/scripts/systemd.md) 和 [传统密钥脚本](md/scripts/tokenKey.md)。这些入口属于兼容备选且随时可能删除；日常操作、新项目和可交互的发布流程统一使用本页的 `interact`。
