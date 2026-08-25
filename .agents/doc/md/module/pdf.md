# PDF 组件

rcmaker 内置了 PDF 组件，底层基于 TCPDF。常用入口有：

- `$req->pdf()`
- `$req->P()`
- `pdf($request, $config)`

## 初始化参数

默认初始化参数如下：

```php
[
    'orientation' => 'P',
    'unit' => 'mm',
    'format' => 'A4',
    'unicode' => true,
    'encoding' => 'UTF-8',
    'diskcache' => false,
]
```

例如：

```php
$pdf = $req->P([
    'orientation' => 'P',
    'unit' => 'mm',
    'format' => 'A4',
    'unicode' => true,
    'encoding' => 'UTF-8',
    'diskcache' => false,
]);
```

## 基本用法

```php
<?php
namespace app\index\controller;

class test
{
    public function pdf($req)
    {
        $pdf = $req->P();

        $pdf->SetCreator('vsa');
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('TCPDF Example 002');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->SetFont('times', 'BI', 20);
        $pdf->AddPage();

        $txt = <<<EOD
TCPDF Example 002

Default page header and footer are disabled using setPrintHeader() and setPrintFooter() methods.
EOD;

        $pdf->Write(0, $txt, '', 0, 'C', true, 0, false, false, 0);

        return $pdf->Output('example_002.pdf', 'I');
    }
}
```

访问 `http://localhost:8680/test/pdf` 即可直接显示生成后的 PDF。

## Output 输出方式

`$pdf->Output($name, $dest)` 的第二个参数用于指定输出方式。当前实现支持：

参数 | 说明
---|---
I | 直接在响应中内联显示 PDF
D | 以附件形式下载 PDF
F | 保存到服务器本地文件，不直接输出响应内容
FI | 先保存到文件，再以内联方式输出
FD | 先保存到文件，再以下载方式输出
S | 直接返回 PDF 二进制字符串
E | 返回 base64 MIME 邮件附件字符串

示例：

```php
return $pdf->Output('report.pdf', 'D');
```

保存到本地：

```php
$pdf->Output(runtime_path() . '/report.pdf', 'F');
```

先保存再下载：

```php
return $pdf->Output(runtime_path() . '/report.pdf', 'FD');
```

## 说明

- `I`、`D`、`FI`、`FD` 在当前框架里会返回响应对象，可以直接 `return`。
- `F` 只负责保存文件，不会返回可下载响应。
- `S` 适合你自己接管二进制输出或进一步处理。
- `E` 适合把 PDF 作为邮件附件内容再交给邮件组件发送。

## 备注

- 当前实现已经为 TCPDF 注入了 request，上述输出模式在框架响应链里可直接工作。
- 如果 TCPDF 类文件加载失败，框架会抛出异常。

组件更多使用方法请参考 [PHP_tcpdf](http://www.tcpdf.org)
