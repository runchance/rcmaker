<?php
//以下三个为内置,不需要额外安装
use RC\Helper\View\Raw;
use RC\Helper\View\ThinkPHP;
use RC\Helper\View\Smarty;
//需要用户手动安装  composer require twig/twig
use RC\Helper\View\Twig;
//需要用户手动安装  composer require jenssegers/blade
use RC\Helper\View\Blade;


return [
    'handler' => Raw::class,
    //模板扩展名
    'suffix' => 'html',
    //模板配置 不同引擎配置不同请参考模板引擎官方文档
    'options' => [
    	
    ]
];
?>