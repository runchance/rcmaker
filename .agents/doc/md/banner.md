# 启动 Banner

RCMaker 在 CLI 启动时可以显示框架版本、PHP/JIT、运行引擎、操作系统、事件循环和进程列表。默认样式参考 Workerman 5.x，保持紧凑、清晰，适合开发环境和独立可执行程序。

项目可以通过 `config/banner.php` 替换默认内容，加入产品名称、版本和多行颜色，同时自行决定是否显示进程列表。

## 显示规则

| 配置 | 结果 |
| --- | --- |
| `config/app.php` 中 `cli_banner=false` | RCMaker 不接管启动 Banner；底层运行引擎仍可能输出自身启动信息。 |
| `config/app.php` 中 `cli_banner=true`，Banner 配置为空 | 使用内置的 Workerman 5.x 风格 RCMaker Banner。 |
| `config/app.php` 中 `cli_banner=true`，配置了 `banner.lines` | 完全使用项目自定义 Banner。 |

启停开关由开发者直接写在 `config/app.php`：

```php
'cli_banner' => true,
```

> [!TIP]
> `cli_banner` 不读取 `.env`。打包为独立可执行程序后，外部 `.env` 也不能开启、关闭或替换 Banner。显示内容由开发者在 `config/banner.php` 中预先确定；该文件返回空配置时使用框架默认样式。

## 快速配置

下面的配置会显示产品信息、运行环境和进程表：

```php
<?php

return [
    'app' => [
        'name' => '智慧馆藏平台',
        'version' => '1.2.0',
    ],
    'color' => 'auto',
    'width' => 112,
    'datetime_format' => 'Y-m-d H:i:s',

    'lines' => [
        [
            'type' => 'separator',
            'text' => '{app.name} {app.version}',
            'color' => 'bright_cyan',
        ],
        [
            'text' => 'Powered by {framework.name} {framework.version}',
            'color' => 'green',
            'align' => 'center',
        ],
        [
            'text' => 'PHP {php.version} | {runtime.name} {runtime.version} | {event_loop}',
            'color' => 'white',
        ],
        [
            'text' => '{datetime} {timezone} | {os} {arch}',
            'color' => 'gray',
        ],
        [
            'type' => 'workers',
            'title' => 'WORKERS',
            'title_color' => 'bright_white',
        ],
    ],
];
```

删除下面这个块，Banner 就不会显示进程列表：

```php
[
    'type' => 'workers',
]
```

## 行类型

`lines` 按数组顺序输出，每项负责一行或一个多行块。

### 普通文本

```php
[
    'text' => 'PHP {php.version}',
    'color' => 'green',
    'background' => 'black',
    'bold' => true,
    'dim' => false,
    'align' => 'left',
]
```

`align` 支持 `left`、`center`、`right`。文本可以包含换行，同一配置项中的各行使用相同样式；需要不同颜色时，分别配置多项。

### 分隔标题

```php
[
    'type' => 'separator',
    'text' => '{app.name}',
    'fill' => '-',
    'color' => 'bright_cyan',
]
```

标题会根据 `width` 居中，两侧自动补齐填充字符。

### 空行

```php
[
    'type' => 'blank',
]
```

### 进程列表

```php
[
    'type' => 'workers',
    'title' => 'PROCESSES',
    'columns' => ['event_loop', 'protocol', 'name', 'listen', 'processes', 'status'],
    'header' => true,
    'header_color' => 'bright_white',
]
```

进程列表来自本次启动已经解析的主 APP、自定义 APP、普通自定义进程、日志进程和队列消费进程，不会额外扫描目录或访问网络。

可选列包括：

| 列名 | 内容 |
| --- | --- |
| `event_loop` | 当前事件循环的短名称，例如 `select`、`event`、`swoole`。 |
| `protocol` | HTTP、TCP、Text、process 等协议或类型。 |
| `user` | 当前运行用户或进程配置用户。 |
| `name` | Worker 或进程组名称。 |
| `listen` | 监听地址；没有监听端口时为 `none`。 |
| `processes` | 当前平台实际使用的进程数量；Windows 下 Workerman 单进程组显示为 `1`。 |
| `status` | 启动表中的状态。 |

可以在全局 `workers` 配置中设置列名、宽度和状态颜色：

```php
'workers' => [
    'columns' => ['event_loop', 'protocol', 'user', 'name', 'listen', 'processes', 'status'],
    'labels' => [
        'name' => 'worker',
        'processes' => 'count',
        'status' => 'state',
    ],
    'widths' => [
        'name' => 24,
        'listen' => 36,
    ],
    'header' => true,
    'header_color' => 'bright_white',
    'row_color' => null,
    'status_colors' => [
        'ok' => 'green',
        'ready' => 'green',
        'disabled' => 'yellow',
        'invalid' => 'red',
        'stopped' => 'red',
    ],
],
```

单个 `workers` 块中的同名选项会覆盖全局设置。

## 内置变量

变量使用 `{变量名}` 写在 `text` 或标题中。未识别的变量会原样保留，便于发现拼写错误。

| 变量 | 内容 |
| --- | --- |
| `{framework.name}` | 框架名称，当前为 `RCMAKER`。 |
| `{framework.version}` | 当前安装的 rcmaker-framework 版本。 |
| `{app.name}` | `banner.app.name` 配置。 |
| `{app.version}` | `banner.app.version` 配置。 |
| `{php.version}` | PHP 版本。 |
| `{php.sapi}` | `cli`、`micro` 等 SAPI。 |
| `{php.jit}` | JIT 状态，值为 `on` 或 `off`。 |
| `{runtime.name}` | Workerman 或 Swoole。 |
| `{runtime.version}` | 当前运行引擎版本。 |
| `{workerman.version}` | Workerman 版本。 |
| `{swoole.version}` | Swoole 版本；未安装时为 `unavailable`。 |
| `{event_loop}` | Workerman 事件循环类或 Swoole 运行方式。 |
| `{os}` | 操作系统类型。 |
| `{os.name}` | 操作系统名称，与 `php_uname('s')` 一致。 |
| `{os.release}` | 操作系统或内核版本，与 `php_uname('r')` 一致。 |
| `{arch}` | CPU 架构。 |
| `{user}` | 当前 CLI 用户。 |
| `{datetime}` | 按 `datetime_format` 格式化的当前时间。 |
| `{timezone}` | 当前时区。 |
| `{command}` | `start`、`restart` 等当前命令。 |
| `{process_count}` | Banner 进程表中的进程总数。 |

所有点号变量同时支持下划线别名，例如 `{framework_version}`、`{php_version}`。

## 颜色

`color` 支持三种模式：

| 值 | 行为 |
| --- | --- |
| `auto` | 仅在支持颜色的交互终端中启用，推荐。 |
| `always` | 强制启用。 |
| `never` | 始终输出纯文本。 |

支持的前景色和背景色名称：

```text
black red green yellow blue magenta cyan white gray
bright_black bright_red bright_green bright_yellow bright_blue
bright_magenta bright_cyan bright_white
```

环境变量可以临时覆盖配置：

```shell
RCMAKER_COLOR=always php index.php start
RCMAKER_COLOR=never php index.php start
NO_COLOR=1 php index.php start
```

输出重定向到日志、`TERM=dumb` 或终端不支持 ANSI 时，`auto` 会使用纯文本。配置中的原始 ANSI 控制符会被移除，只允许使用上面的颜色名称。

## 不同运行方式

- Workerman：RCMaker 在 `start`、`restart` 时接管启动 UI，并保持 Workerman 5.x 风格的默认布局。
- Swoole：普通模式和协程模式共用相同 Banner 配置，启用后不再重复输出旧的 Server Started 列表。
- Windows：由 Windows 主控进程只输出一次；框架会抑制 Workerman 子进程即使带 `-q` 仍会打印的启动状态行，进入 `onWorkerStart` 前恢复标准输出，后续日志和异常不受影响。
- Micro SFX：与普通 CLI 使用同一配置，适合独立可执行程序展示产品信息。

Banner 只在启动阶段构建和输出一次，不进入请求处理链，不影响 HTTP RPS。

## 常见问题

| 现象 | 检查方法 |
| --- | --- |
| 修改后仍显示默认 Banner | 确认 `lines` 是非空数组，并执行完整重启。 |
| 没有显示进程列表 | 在 `lines` 中加入 `type=workers` 块。 |
| 颜色没有显示 | 检查是否重定向输出、设置了 `NO_COLOR`，或使用 `RCMAKER_COLOR=always` 测试。 |
| 变量原样显示 | 变量名不存在或拼写错误，对照内置变量表。 |
| 表格过宽 | 减少 `columns`，或调整 `widths` 和 Banner `width`。 |
| `config/app.php` 中 `cli_banner=false` 仍有启动文字 | 这是底层运行引擎的原生输出，RCMaker 已停止接管。 |
