<?php
// .env.example 默认只启用内置组件；ThinkORM、Laravel 等可选引擎需另行 composer 安装后启用。
return rcEnv('bootstrap.load',[RC\Session::class]);
?>
