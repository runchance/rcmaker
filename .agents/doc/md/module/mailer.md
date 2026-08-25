# 邮件发送组件

rcmaker 内置邮件发送组件 `RC\Helper\Mailer`，常用入口是 `mailer()`。

底层基于 PHPMailer 封装，支持：

- `smtp`
- `mail`
- `Sendmail`

## 配置文件

配置文件位于 `config/mailer.php`。当前仓库示例如下：

```php
<?php
return [
    'default' => [
        'Mailer' => 'smtp',
        'Host' => 'smtp.example.com',
        'SMTPAuth' => false,
        'Username' => 'user@example.com',
        'Password' => 'secret',
        'SMTPSecure' => '',
        'Port' => 25,
        'SMTPDebug' => 0,
        'Debugoutput' => 'error_log',
        'Exception' => true,
        'Language' => 'zh_cn',
        'CharSet' => 'utf-8',
    ],
    'custom1' => [
        'Mailer' => 'mail',
        'SMTPDebug' => 0,
        'Exception' => true,
    ],
    'custom2' => [
        'Mailer' => 'Sendmail',
        'SMTPDebug' => 0,
        'Exception' => true,
    ],
];
```

说明：

- `Mailer` 的可选值按当前实现使用 `smtp`、`mail`、`Sendmail`。
- `Exception` 即使为 `true`，通过 `RC\Helper\Mailer` 封装发送失败时也会返回 `false`，错误信息可通过 `e()` / `getError()` 获取。
- `mailer('custom1')` 这类调用会按连接名缓存 helper 实例。

## 基本用法

```php
$mail = mailer();
$send = $mail->from('sendTest@gmail.com', 'tom')
    ->to('receiveTest@gmail.com')
    ->subject('RC Mailer Test')
    ->body('RC Mailer Test')
    ->send();

if (!$send) {
    return 'Mailer Error: ' . $mail->e();
}

return 'Message sent!';
```

切换连接：

```php
$mail = mailer('custom1');
```

发送 HTML 邮件：

```php
$mail = mailer();
$send = $mail->from('sendTest@gmail.com', 'tom')
    ->to('receiveTest@gmail.com')
    ->subject('RC Mailer Test')
    ->isHtml(true)
    ->msgHTML(file_get_contents(public_path() . '/mailertest.html'), public_path())
    ->send();

if (!$send) {
    return 'Mailer Error: ' . $mail->e();
}

return 'Message sent!';
```

如果你想显式拿到一个独立的新对象，也可以这样：

```php
$mail = mailer()->instance();
```

## 状态复用说明

`mailer()` 会按连接缓存 helper 实例，但当前实现中每次 `send()` 结束后都会自动重置本次消息状态，因此不会把上一次发送的收件人、抄送、附件、正文等内容串到下一次发送里。

这意味着大多数场景下直接复用 `mailer()` 即可，不必为了避免状态残留而强制调用 `instance()`。

## 链式方法

常用链式方法如下：

```php
// 添加附件；$path 可以是字符串，也可以是数组
mailer()->addAttachment($path, $attachmentName);
mailer()->a($path, $attachmentName);

// 添加收件人；$email 可以是字符串，也可以是数组
mailer()->to($email, $toName);
mailer()->t($email, $toName);

// 设置发件人
mailer()->from($email, $fromName);
mailer()->f($email, $fromName);

// 设置主题
mailer()->subject($subject);
mailer()->sb($subject);
mailer()->s($subject);

// 设置正文
mailer()->body($body);
mailer()->b($body);

// 设置是否为 HTML
mailer()->isHtml($bool);
mailer()->ih($bool);

// 设置 HTML 内容；调用后 PHPMailer 会按其内置逻辑生成 HTML/AltBody
mailer()->msgHTML($message, $basedir);
mailer()->mh($message, $basedir);

// 设置抄送与密送
mailer()->cc($mail);
mailer()->bcc($mail);

// 发送邮件
mailer()->send();
mailer()->s();

// 获取错误信息
mailer()->getError();
mailer()->e();
```

说明：

- `s($subject)` 在传参时是“设置主题”的简写；`s()` 无参数时才表示“发送邮件”。
- `msgHTML()` 适合直接加载完整 HTML 模板，并让底层自动生成纯文本备用内容。

## 附件与批量地址示例

```php
mailer()->addAttachment([
    public_path() . '/1.jpg',
    'report.docx' => public_path() . '/report.docx',
]);

mailer()->to([
    'tom@example.com',
    'Jerry' => 'jerry@example.com',
]);
```

## 错误处理

推荐用 helper 的返回值和错误信息处理发送失败：

```php
$mail = mailer();

if (!$mail->from('from@example.com', 'system')
    ->to('to@example.com')
    ->subject('test')
    ->body('hello')
    ->send()) {
    return $mail->e();
}

return 'ok';
```

## 备注

`RC\Helper\Mailer` 底层仍然是 PHPMailer 封装；如果需要更细粒度的 SMTP、认证、调试或 MIME 能力，可以参考 PHPMailer 官方文档：<https://github.com/PHPMailer/PHPMailer>
