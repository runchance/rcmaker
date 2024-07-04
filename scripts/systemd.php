<?php
	define('IS_SCRIPT',1);
	define('ROOT_PATH', dirname(__FILE__,2));
	require_once __DIR__ . '/../vendor/autoload.php';

	use RC\FileOperator;
	$systemdPath = '/etc/systemd/system/';
	$serviceTpFile = ROOT_PATH.'/scripts/rcmaker.service';
	if(!isset($argv[1]) || strpos($argv[1],'help')){
		$usage = "Usage: \nphp ./scripts/systemd.php <serviceName>(Required) <op>(Optional) <seriveUser>(Optional) <PHP_BINARY>(Op Add Optional)\n<serviceName> Lowercase English letters within 20 digits\n<op> add, remove\n<seriveUser> root or other\n<PHP_BINARY> php binaries\n";
		exit($usage);
	}
	$name = $argv[1];
	$op = $argv[2] ?? 'add';
    $user = $argv[3] ?? 'root';
	$php = $argv[4] ?? PHP_BINARY;
	
	$isRoot = function(){
	    if (function_exists('posix_getuid')) {
            // 获取当前用户的用户 ID
            $uid = posix_getuid();
            // 判断是否为 root 用户
            if ($uid === 0) {
                return true;
            } else {
                return false;
            }
        } else {
            exit("posix_getuid Function is not available\n");
        }
	};
	if(!$isRoot()){
	    exit("Please run with root privileges or sudo\n");
	}

	if(!preg_match('/^[a-z]{1,20}$/', $name)){
	    exit("serivceName illegal\n");
	}
	
	$systemdPath = '/etc/systemd/system/'.$name.'.service';
   
    switch($op){
        case "add":
            $fileBuff = file_get_contents($serviceTpFile);
            $fileBuff = str_replace(['{name}','{phpPath}','{rcmakerPatch}','{user}'],[$name,$php,ROOT_PATH."/index.php",$user],$fileBuff);
            file_put_contents($systemdPath,$fileBuff);
            exec("systemctl daemon-reload");
            exec("systemctl enable ".$name."");
            exit("serivce[".$name."] add\nnext run:\nsudo service ".$name." start\nsudo service ".$name." restart\nsudo service ".$name." stop\n");
        break;
        case "remove":
            exec("systemctl stop ".$name."");
            exec("systemctl disable ".$name."");
            unlink($systemdPath);
            exec("systemctl daemon-reload");
            exit("serivce[".$name."] removed\n");
        break;
    }
?>