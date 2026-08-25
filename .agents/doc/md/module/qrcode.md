# 二维码生成组件

rcmaker 内置了二维码组件，常用入口有：

- `$req->qrcode()`
- `$req->Q()`
- `qrcode($request, ...)`

## 基本用法

```php
<?php
namespace app\index\controller;

class test
{
    public function qrcode($req)
    {
        return $req->Q('Hello RCmaker', 'png');
    }
}
```

访问 `http://localhost:8680/test/qrcode` 会直接显示二维码图片。

当前实现里，`png` 分支会真实使用你传入的 `$text` 生成二维码内容。

## 函数签名

```php
$req->Q($text, $format = 'png', $outfile = false, $level = 0, $size = 3, $margin = 4, $saveandprint = false)
```

参数说明：

参数 | 说明
---|---
$text | 二维码文本内容
$format | 输出格式，支持 `png`、`text`、`raw`
$outfile | 保存文件路径；为 `false` 时不落地文件
$level | 容错级别
$size | 点尺寸
$margin | 外边距
$saveandprint | 仅在配合 `$outfile` 时有意义，控制保存后是否同时输出

## 输出格式

### png

```php
return $req->Q('https://example.com', 'png');
```

行为：

- 返回一个 `image/png` 响应
- 如果传了 `$outfile`，可以同时把图片保存到文件

### text

```php
return $req->Q('Hello RCmaker', 'text');
```

行为：

- 返回 JSON 响应
- 内容是二维码的文本编码结果

### raw

```php
$raw = $req->Q('Hello RCmaker', 'raw');
```

行为：

- 直接返回原始二维码数据
- 不会自动包装成响应对象

## 保存文件

保存到指定路径：

```php
return $req->Q('Hello RCmaker', 'png', public_path() . '/qrcode.png');
```

只保存，不输出：

```php
$req->Q('Hello RCmaker', 'png', public_path() . '/qrcode.png', 0, 3, 4, false);
```

保存并输出：

```php
return $req->Q('Hello RCmaker', 'png', public_path() . '/qrcode.png', 0, 3, 4, true);
```

## 备注

- `text` 和 `raw` 更适合调试、二次处理或自己接管输出逻辑。
- 常规 Web 场景下通常直接使用 `png`。

组件更多使用方法请参考 [phpqrcode](http://phpqrcode.sourceforge.net/)
 

