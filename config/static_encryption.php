<?php

//静态资源保护默认关闭。推荐 php index.php interact 的“加密静态应用资源”。
//交互工具写入 static_assets_generated.php；本文件同名配置优先，不会被工具改写。
//密钥必须是随机 32 字节的 base64: 或 hex: 字符串。不要把真实密钥公开到代码仓库。
return [
    'profiles' => [],
    'applications' => [],
];
