# 拼音生成组件

rcmaker 内置了拼音生成组件，用于把中文文本转换成拼音或拼音首字母。常用入口有：

- `pinyin()`
- `PY()`

这两个 helper 都会返回 `RC\Helper\PinYin` 的新实例。

## 基本用法

```php
<?php
namespace app\index\controller;

class test
{
    public function pinyin($req)
    {
        $py = PY();
        $word = '我爱中国';
        return $py->TransformWithoutTone($word, '');
    }
}
```

访问 `http://localhost:8680/test/pinyin` 会输出：`woaizhongguo`

## 初始化参数

```php
PY($charset = 'utf-8')
```

```php
$py = pinyin('utf-8');
```

当前实现支持在构造时指定字符编码，默认是 `utf-8`。

## 常用方法

### 1. 转换为不带声调的拼音

```php
TransformWithoutTone($input_char, $delimiter = '', $outside_ignore = true)
```

参数说明：

参数 | 说明
---|---
$input_char | 需要转换的文本
$delimiter | 拼音之间的分隔符
$outside_ignore | 是否忽略非汉字内容；`true` 表示忽略，`false` 表示保留原字符

示例：

```php
$py->TransformWithoutTone('我爱中国', '');
// woaizhongguo

$py->TransformWithoutTone('我爱RCmaker', '-');
// wo-ai

$py->TransformWithoutTone('我爱RCmaker', '-', false);
// wo-ai-R-Cmaker
```

### 2. 转换为带声调的拼音

```php
TransformWithTone($input_char, $delimiter = ' ', $outside_ignore = false)
```

注意：当前实现中，这个方法的默认行为和 `TransformWithoutTone()` 不同：

- 默认分隔符是空格
- 默认 `outside_ignore=false`，也就是会保留非汉字内容

示例：

```php
$py->TransformWithTone('我爱中国');
// wǒ ài zhōng guó 

$py->TransformWithTone('我爱RCmaker', ' ', false);
// wǒ ài RCmaker
```

### 3. 转换为拼音首字母

```php
TransformUcwords($input_char, $delimiter = '')
```

示例：

```php
$py->TransformUcwords('我爱中国');
// WAZG

$py->TransformUcwords('我爱中国', '-');
// W-A-Z-G
```

## 行为说明

- 当前组件主要针对常见中文字符做拼音映射。
- 非汉字内容是否保留，取决于 `outside_ignore` 参数。
- `TransformUcwords()` 内部基于不带声调拼音再提取首字母，因此返回值是大写字母组合。

## 备注

- 这个组件更适合用户名、标题、分类名等轻量级拼音转换场景。
- 如果你需要非常严格的多音字上下文判断，这个组件并没有做语义级消歧。
