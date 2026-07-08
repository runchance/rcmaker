<?php
	define('IS_SCRIPT',1);
	define('ROOT_PATH', dirname(__FILE__,2));
	require_once __DIR__ . '/../vendor/autoload.php';

    $systemdDir = '/etc/systemd/system';
    $serviceTemplateFile = ROOT_PATH . '/scripts/rcmaker.service';
    $binFile = ROOT_PATH . '/build/rcmaker.bin';

    $usage = function(){
        return "Usage:\n"
            . "  sudo php ./scripts/systemd.php <serviceName> [add|remove] [serviceUser] [PHP_BINARY]\n"
            . "  sudo php ./scripts/systemd.php <serviceName>@bin [add|remove] [serviceUser]\n\n"
            . "Arguments:\n"
            . "  <serviceName>  Start with a lowercase letter, allow lowercase letters, numbers, underscores and hyphens, max 20 chars.\n"
            . "  <op>           add or remove, default: add.\n"
            . "  <serviceUser>  Linux user used to run the service, default: root.\n"
            . "  <PHP_BINARY>   Absolute PHP binary path, default: current PHP binary.\n";
    };

    $fail = function($message, $code = 1){
        fwrite(STDERR, $message . "\n");
        exit($code);
    };

    $systemdQuote = function($value){
        return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
    };

    $runCommand = function($command, $allowFailure = false) use ($fail){
        $output = [];
        $code = 0;
        exec($command . ' 2>&1', $output, $code);
        if($code !== 0 && !$allowFailure){
            $fail("Command failed: " . $command . "\n" . implode("\n", $output), $code);
        }
        return [$code, $output];
    };

    if(!isset($argv[1]) || in_array($argv[1], ['help', '--help', '-h'], true)){
        exit($usage());
    }

    if(!function_exists('posix_getuid')){
        $fail('posix_getuid function is not available');
    }
    if(posix_getuid() !== 0){
        $fail('Please run with root privileges or sudo');
    }

    $name = trim($argv[1]);
    $isBin = false;
    if(substr($name, -4) === '@bin'){
        $isBin = true;
        $name = substr($name, 0, -4);
    }

    $op = $argv[2] ?? 'add';
    $user = $argv[3] ?? 'root';
    $php = $argv[4] ?? PHP_BINARY;

    if(!preg_match('/^[a-z][a-z0-9_-]{0,19}$/', $name)){
        $fail('serviceName is illegal');
    }
    if(!in_array($op, ['add', 'remove'], true)){
        $fail('op must be add or remove');
    }
    if(!preg_match('/^[a-z_][a-z0-9_-]{0,31}$/', $user)){
        $fail('serviceUser is illegal');
    }
    if(function_exists('posix_getpwnam') && posix_getpwnam($user) === false){
        $fail('serviceUser does not exist: ' . $user);
    }
    $userInfo = function_exists('posix_getpwnam') ? posix_getpwnam($user) : null;
    $groupInfo = $userInfo && function_exists('posix_getgrgid') ? posix_getgrgid($userInfo['gid']) : null;
    $group = $groupInfo['name'] ?? $user;

    $serviceFile = $systemdDir . '/' . $name . '.service';
    $serviceUnit = $name . '.service';

    switch($op){
        case 'add':
            if(!is_file($serviceTemplateFile)){
                $fail('service template does not exist: ' . $serviceTemplateFile);
            }
            if($isBin){
                if(!is_file($binFile) || !is_executable($binFile)){
                    $fail('./build/rcmaker.bin does not exist or is not executable');
                }
                $command = $systemdQuote($binFile);
            }else{
                if(!is_file($php) || !is_executable($php)){
                    $fail('PHP_BINARY does not exist or is not executable: ' . $php);
                }
                $command = $systemdQuote($php) . ' ' . $systemdQuote(ROOT_PATH . '/index.php');
            }

            $fileBuff = file_get_contents($serviceTemplateFile);
            if($fileBuff === false){
                $fail('read service template failed: ' . $serviceTemplateFile);
            }
            $fileBuff = str_replace(
                ['{name}', '{command}', '{workingDirectory}', '{user}', '{group}'],
                [$name, $command, ROOT_PATH, $user, $group],
                $fileBuff
            );
            if(preg_match('/\{[a-zA-Z]+\}/', $fileBuff)){
                $fail('service template has unresolved placeholders');
            }
            if(file_put_contents($serviceFile, $fileBuff, LOCK_EX) === false){
                $fail('write service file failed: ' . $serviceFile);
            }

            $runCommand('systemctl daemon-reload');
            $runCommand('systemctl enable ' . escapeshellarg($serviceUnit));
            exit("service[" . $name . "] added\nnext run:\nsudo service " . $name . " start\nsudo service " . $name . " restart\nsudo service " . $name . " stop\n");
        case 'remove':
            if(!is_file($serviceFile)){
                $fail('service file does not exist: ' . $serviceFile);
            }
            $runCommand('systemctl stop ' . escapeshellarg($serviceUnit), true);
            $runCommand('systemctl disable ' . escapeshellarg($serviceUnit), true);
            if(!unlink($serviceFile)){
                $fail('remove service file failed: ' . $serviceFile);
            }
            $runCommand('systemctl daemon-reload');
            exit("service[" . $name . "] removed\n");
    }