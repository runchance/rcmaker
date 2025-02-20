<?php
define('IS_SCRIPT',1);
define('ROOT_PATH', dirname(__FILE__,2));
require_once __DIR__ . '/../vendor/autoload.php';

use RC\FileOperator;

$pharFileName = "rcmaker.phar";
$phar_file = ROOT_PATH.'/build/'.$pharFileName;
$binFileName  = "rcmaker.bin";
$binFile = ROOT_PATH.'/build/'.$binFileName;
$version = $argv[1] ?? "8.1";
$sfxFileName = "php$version.micro.sfx";
$sfxFile= ROOT_PATH."/build/".$sfxFileName;
$sfxDownUrl = "rcmaker.runchance.com";
$customIni = $argv[2] ?? "";
$customIniHeaderFile = ROOT_PATH."/scripts/custominiheader.bin";
$exclude_pattern = "#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/|/scripts/))(.*)$#";
$signature_algorithm = Phar::SHA256;
$exclude_files = [];
if(isset($argv[3])){
     $exclude_files = explode(",",str_replace("，",",",$argv[3]));
}
FileOperator::mkdir(ROOT_PATH.'/build/');

if (file_exists($binFile)) {
    unlink($binFile);
}


##生成Phar
###########################################################################################################
if (file_exists($phar_file)) {
    unlink($phar_file);
}





if (!class_exists(Phar::class, false)) {
    throw new RuntimeException("The 'phar' extension is required for build phar package");
}

if (ini_get('phar.readonly')) {
    throw new RuntimeException(
        "The 'phar.readonly' is 'On', build phar must setting it 'Off' or exec with 'php -d phar.readonly=0 ./webman $command'"
    );
}

$phar = new Phar($phar_file,0,'rcmaker');
$phar->startBuffering();
if (!in_array($signature_algorithm,[Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512,Phar::OPENSSL])) {
    throw new RuntimeException('The signature algorithm must be one of Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512, or Phar::OPENSSL.');
}
if (!in_array($signature_algorithm,[Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512,Phar::OPENSSL])) {
    throw new RuntimeException('The signature algorithm must be one of Phar::MD5, Phar::SHA1, Phar::SHA256, Phar::SHA512, or Phar::OPENSSL.');
}
if ($signature_algorithm === Phar::OPENSSL) {
    $private_key_file = ROOT_PATH.'/build/phar.pem';
    if (!file_exists($private_key_file)) {
        throw new RuntimeException("If the value of the signature algorithm is 'Phar::OPENSSL', you must set the private key file.");
    }
    $private = openssl_get_privatekey(file_get_contents($private_key_file));
    $pkey = '';
    openssl_pkey_export($private, $pkey);
    $phar->setSignatureAlgorithm($signature_algorithm, $pkey);
} else {
    $phar->setSignatureAlgorithm($signature_algorithm);
}
$phar->buildFromDirectory(ROOT_PATH,$exclude_pattern);


foreach ($exclude_files as $file) {
    if($phar->offsetExists($file)){
        $phar->delete($file);
    }
}

echo "Files collect complete, begin add file to Phar.\r\n";

$phar->setStub("#!/usr/bin/env php
<?php
define('IN_PHAR', true);
Phar::mapPhar('rcmaker');
require 'phar://rcmaker/index.php';
__HALT_COMPILER();
");
$phar->stopBuffering();
unset($phar);
echo "Generate Phar file successfully.\r\n";

##生成Bin
###########################################################################################################

if (!is_file($sfxFile)) {
	echo "Downloading PHP$version ...\r\n";
	$client = stream_socket_client("tcp://$sfxDownUrl:80");
	fwrite($client, "GET /$sfxFileName HTTP/1.1\r\nAccept: text/html\r\nHost: $sfxDownUrl\r\nUser-Agent: rcmaker/script\r\n\r\n");
	$bodyLength = 0;
    $bodyBuffer = '';
    $lastPercent = 0;
    while (true) {
        $buffer = fread($client, 65535);
        if ($buffer !== false) {
            $bodyBuffer .= $buffer;
            if (!$bodyLength && $pos = strpos($bodyBuffer, "\r\n\r\n")) {
                if (!preg_match('/Content-Length: (\d+)\r\n/', $bodyBuffer, $match)) {
                	echo "Download php$sfxFileName failed\r\n";
                    return false;
                }
                $firstLine = substr($bodyBuffer, 9, strpos($bodyBuffer, "\r\n") - 9);
                if (!preg_match('/200 /', $bodyBuffer)) {
                	echo "Download php$sfxFileName failed\r\n";
                    return false;
                }
                $bodyLength = (int)$match[1];
                $bodyBuffer = substr($bodyBuffer, $pos + 4);
            }
        }
        $receiveLength = strlen($bodyBuffer);
        $percent = ceil($receiveLength * 100 / $bodyLength);
        if ($percent != $lastPercent) {
            echo '[' . str_pad('', $percent, '=') . '>' . str_pad('', 100 - $percent) . "$percent%]";
            echo $percent < 100 ? "\r" : "\n";
        }
        $lastPercent = $percent;
        if ($bodyLength && $receiveLength >= $bodyLength) {
            FileOperator::write($sfxFile,$bodyBuffer,true);
            break;
        }
        if ($buffer === false || !is_resource($client) || feof($client)) {
        	echo "Fail donwload PHP$version ...\r\n";
            return false;
        }
    }
}else{
	echo "Use PHP$version ...\r\n";
}
// 生成二进制文件

FileOperator::write($binFile,file_get_contents($sfxFile),true);
 // 自定义INI
if (!empty($customIni)) {
    if (file_exists($customIniHeaderFile)) {
        unlink($customIniHeaderFile);
    }
    $f = fopen($customIniHeaderFile, 'wb');
    fwrite($f, "\xfd\xf6\x69\xe6");
    fwrite($f, pack('N', strlen($customIni)));
    fwrite($f, $customIni);
    fclose($f);
    file_put_contents($binFile, file_get_contents($customIniHeaderFile),FILE_APPEND);
    unlink($customIniHeaderFile);
}
file_put_contents($binFile, file_get_contents($phar_file), FILE_APPEND);
 // 添加执行权限
chmod($binFile, 0755);
echo "\r\nSaved $binFileName to $binFile\r\nBuild Success!\r\n";
?>