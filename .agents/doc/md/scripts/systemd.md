# 传统 Linux 服务脚本（备选）

> [!WARNING]
> 本页记录的是传统兼容脚本，随时可能删除。注册或移除 Linux systemd 服务请优先运行 `sudo php index.php interact`，选择“注册或移除 Linux systemd 服务”，并以 [交互式项目工具](md/interact.md) 为准。新项目和新自动化流程不应依赖本脚本。

`scripts/systemd.php` 暂时保留，用于兼容已有自动化部署流程。它支持使用 PHP 解释器运行 `index.php`，或使用已经构建好的 `build/rcmaker.bin`；以下内容仅作为备选方案说明。

> 适用范围：当前服务注册脚本支持 Linux `x86_64` 和 Linux `aarch64`。如果使用独立 PHP，请先从 [独立 PHP CLI 下载](md/download.md) 下载对应架构的 ZIP，将其中的 `php` 安装到 `/usr/local/bin/php`。

## 文件说明

- `scripts/systemd.php`：服务注册和移除脚本。
- `scripts/rcmaker.service`：systemd 服务模板。
- `/etc/systemd/system/`：生成后的服务文件目录。
- `build/rcmaker.bin`：二进制启动模式使用的文件。

## 运行前提

1. 必须使用 `root` 权限运行脚本，或通过 `sudo` 执行。
2. PHP 模式需要传入可执行的 PHP 绝对路径，默认使用当前 PHP 二进制路径。
3. 二进制模式需要存在可执行文件 `build/rcmaker.bin`。
4. `<serviceUser>` 必须是系统中已经存在的 Linux 用户。

如果当前机器没有系统 PHP，请先按 [安装 rcmaker](md/install.md) 的说明把对应架构的独立 PHP 下载到 `/usr/local/bin/php`：

```bash
php -v
```

后文统一使用 `php ./scripts/systemd.php`。如果要注册 PHP 模式服务，建议把服务启动用的 PHP 路径写成 `/usr/local/bin/php`。

## 参数说明

| 参数 | 是否必需 | 说明 |
| --- | --- | --- |
| `<serviceName>` | 是 | 服务名称，要求以小写英文字母开头，可包含小写字母、数字、下划线和短横线，最长 20 个字符。 |
| `<op>` | 否 | 操作类型，支持 `add` 和 `remove`，默认值为 `add`。 |
| `<serviceUser>` | 否 | 服务运行用户，默认值为 `root`。 |
| `<PHP_BINARY>` | 否 | PHP 可执行文件的绝对路径，仅 PHP 模式使用。 |

查看帮助：

```bash
php ./scripts/systemd.php --help
```

## PHP 模式

PHP 模式会生成类似下面的启动命令：

```ini
ExecStart="/usr/bin/php" "/path/to/rcmaker/index.php" start -d
```

注册服务：

```bash
sudo php ./scripts/systemd.php rcmaker add root /usr/bin/php
```

如果使用下载到 `/usr/local/bin/php` 的独立 PHP：

```bash
sudo php ./scripts/systemd.php rcmaker add root /usr/local/bin/php
```

如果使用当前 PHP 二进制，可以省略最后一个参数：

```bash
sudo php ./scripts/systemd.php rcmaker
```

## 二进制模式

二进制模式需要在服务名后追加 `@bin`。`@bin` 只用于告诉脚本使用 `build/rcmaker.bin` 生成服务文件，最终服务名仍然是去掉 `@bin` 后的名称。

```bash
sudo php ./scripts/systemd.php rcmaker@bin add root
```

移除服务时不需要带 `@bin`：

```bash
sudo php ./scripts/systemd.php rcmaker remove
```

## 服务管理

服务注册完成后，可以使用 systemd 或 service 命令管理服务：

```bash
sudo service rcmaker start
sudo service rcmaker restart
sudo service rcmaker stop
sudo systemctl status rcmaker
```

PHP 模式下，也可以在 rcmaker 根目录查看运行状态：

```bash
sudo php index.php status
```

二进制模式下，对应命令为：

```bash
sudo ./build/rcmaker.bin status
```

## 排查命令

如果服务无法启动，优先查看 systemd 状态和日志：

```bash
sudo systemctl status rcmaker
sudo journalctl -u rcmaker -f
```

修改服务文件后，需要重新加载 systemd：

```bash
sudo systemctl daemon-reload
```

