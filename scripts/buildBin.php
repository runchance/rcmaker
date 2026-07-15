<?php
define('IS_SCRIPT',1);
define('ROOT_PATH', dirname(__FILE__,2));
require_once __DIR__ . '/../vendor/autoload.php';

function buildbin_normalize_relative_path(string $path): string
{
    return '/' . ltrim(str_replace('\\', '/', $path), '/');
}

function buildbin_mkdir(string $path): void
{
    if (is_dir($path)) {
        return;
    }
    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        throw new RuntimeException("Failed to create directory: {$path}");
    }
}

function buildbin_write_file(string $path, string $contents): void
{
    buildbin_mkdir(dirname($path));
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException("Failed to write file: {$path}");
    }
}

function buildbin_remove_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
            continue;
        }
        unlink($item->getPathname());
    }

    rmdir($dir);
}

function buildbin_remove_path(string $path): void
{
    if (is_dir($path)) {
        buildbin_remove_dir($path);
        return;
    }
    if (is_file($path)) {
        unlink($path);
    }
}

function buildbin_cleanup_build_dir(string $buildDir, string $keepFileName): void
{
    if (!is_dir($buildDir)) {
        return;
    }

    $items = scandir($buildDir);
    if ($items === false) {
        throw new RuntimeException("Failed to scan build directory: {$buildDir}");
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === $keepFileName) {
            continue;
        }

        buildbin_remove_path($buildDir . DIRECTORY_SEPARATOR . $item);
    }
}

function buildbin_copy_tree(string $sourceRoot, string $targetRoot, string $excludePattern): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $sourcePath = $item->getPathname();
        $relativePath = substr($sourcePath, strlen($sourceRoot) + 1);
        $normalizedPath = buildbin_normalize_relative_path($relativePath);

        if (!preg_match($excludePattern, $normalizedPath)) {
            continue;
        }

        $targetPath = $targetRoot . DIRECTORY_SEPARATOR . $relativePath;
        if ($item->isDir()) {
            buildbin_mkdir($targetPath);
            continue;
        }

        buildbin_mkdir(dirname($targetPath));
        if (!copy($sourcePath, $targetPath)) {
            throw new RuntimeException("Failed to copy file: {$sourcePath} -> {$targetPath}");
        }
    }
}

function buildbin_parse_options(array $args): array
{
    $options = [
        'with-php' => '8.1',
        'arch' => 'auto',
        'custom-ini' => '',
        'exclude-files' => '',
        'encrypt' => false,
    ];

    foreach ($args as $arg) {
        if (!str_starts_with($arg, '--')) {
            throw new InvalidArgumentException("Unknown positional argument: {$arg}");
        }

        if ($arg === '--encrypt') {
            $options['encrypt'] = true;
            continue;
        }

        $parts = explode('=', substr($arg, 2), 2);
        if (count($parts) !== 2 || $parts[0] === '') {
            throw new InvalidArgumentException("Invalid option format: {$arg}");
        }

        [$name, $value] = $parts;
        if (!array_key_exists($name, $options)) {
            throw new InvalidArgumentException("Unknown option: --{$name}");
        }

        $options[$name] = $value;
    }

    return $options;
}

function buildbin_assert_supported_php_version(string $version): void
{
    $supportedVersions = ['8.1', '8.2', '8.3', '8.4', '8.5'];
    if (!in_array($version, $supportedVersions, true)) {
        throw new InvalidArgumentException(
            'Unsupported PHP version: ' . $version . '. Supported versions: ' . implode(', ', $supportedVersions)
        );
    }
}

function buildbin_normalize_arch(string $arch): string
{
    $arch = strtolower(trim($arch));
    if ($arch === '' || $arch === 'auto') {
        $arch = strtolower((string)php_uname('m'));
    }

    $map = [
        'amd64' => 'x86_64',
        'x64' => 'x86_64',
        'x86-64' => 'x86_64',
        'arm64' => 'aarch64',
        'armv8' => 'aarch64',
    ];
    $arch = $map[$arch] ?? $arch;

    if (!in_array($arch, ['x86_64', 'aarch64'], true)) {
        throw new InvalidArgumentException(
            'Unsupported architecture: ' . $arch . '. Supported architectures: x86_64, aarch64'
        );
    }

    return $arch;
}

function buildbin_arch_suffix(string $arch): string
{
    return $arch === 'aarch64' ? '_aarch64' : '';
}

function buildbin_sfx_name(string $version, string $arch): string
{
    if ($arch === 'aarch64') {
        return "php$version.micro.aarch64.sfx";
    }
    return "php$version.micro.sfx";
}

function buildbin_download_file(string $host, string $remotePath, string $targetPath, string $label): void
{
    $client = @stream_socket_client("tcp://{$host}:80", $errno, $errstr);
    if (!is_resource($client)) {
        throw new RuntimeException("Connect {$label} download server failed: {$errstr} ({$errno})");
    }

    fwrite($client, "GET {$remotePath} HTTP/1.1\r\nAccept: */*\r\nHost: {$host}\r\nUser-Agent: rcmaker/script\r\nConnection: close\r\n\r\n");

    $bodyLength = 0;
    $bodyBuffer = '';
    $headerBuffer = '';
    $lastPercent = -1;
    while (true) {
        $buffer = fread($client, 65535);
        if ($buffer !== false && $buffer !== '') {
            if ($bodyLength === 0) {
                $headerBuffer .= $buffer;
                $headerEndPos = strpos($headerBuffer, "\r\n\r\n");
                if ($headerEndPos === false) {
                    if (!is_resource($client) || feof($client)) {
                        break;
                    }
                    continue;
                }

                $header = substr($headerBuffer, 0, $headerEndPos + 4);
                if (!preg_match('/HTTP\/1\.[01] 200 /', $header)) {
                    throw new RuntimeException("Download {$label} failed.");
                }
                if (!preg_match('/Content-Length: (\d+)\r\n/i', $header, $match)) {
                    throw new RuntimeException("Download {$label} failed.");
                }

                $bodyLength = (int)$match[1];
                $bodyBuffer = substr($headerBuffer, $headerEndPos + 4);
            } else {
                $bodyBuffer .= $buffer;
            }
        }

        if ($bodyLength > 0) {
            $receiveLength = strlen($bodyBuffer);
            $percent = min(100, (int)ceil($receiveLength * 100 / $bodyLength));
            if ($percent !== $lastPercent) {
                echo '[' . str_pad('', $percent, '=') . '>' . str_pad('', 100 - $percent) . "{$percent}%]";
                echo $percent < 100 ? "\r" : "\n";
                $lastPercent = $percent;
            }

            if ($receiveLength >= $bodyLength) {
                buildbin_write_file($targetPath, $bodyBuffer);
                break;
            }
        }

        if ($buffer === false || !is_resource($client) || feof($client)) {
            break;
        }
    }

    fclose($client);

    if (!is_file($targetPath)) {
        throw new RuntimeException("Download {$label} failed.");
    }
}

function buildbin_ensure_encrypt_binary(string $host, string $arch): string
{
    $encryptBinaryName = 'rcmakerbeast' . buildbin_arch_suffix($arch);
    $encryptBinary = ROOT_PATH . '/build/' . $encryptBinaryName;
    echo "Downloading {$encryptBinaryName} ...\r\n";
    buildbin_download_file($host, '/' . $encryptBinaryName, $encryptBinary, $encryptBinaryName);

    chmod($encryptBinary, 0755);
    return $encryptBinary;
}

function buildbin_encrypt_tree(string $sourceRoot, string $encryptBinary): void
{
    $command = escapeshellarg($encryptBinary)
        . ' dir '
        . escapeshellarg($sourceRoot)
        . ' --in-place --force';

    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('Encrypting staged distribution files failed.');
    }
}

$pharFileName = "rcmaker.phar";
$phar_file = ROOT_PATH.'/build/'.$pharFileName;
$binFileName  = "rcmaker.bin";
$binFile = ROOT_PATH.'/build/'.$binFileName;
$options = buildbin_parse_options(array_slice($argv, 1));
buildbin_assert_supported_php_version($options['with-php']);

$version = $options['with-php'];
$arch = buildbin_normalize_arch($options['arch']);
$sfxFileName = buildbin_sfx_name($version, $arch);
$sfxFile= ROOT_PATH."/build/".$sfxFileName;
$sfxDownUrl = "rcmaker.runchance.com";
$customIni = $options['custom-ini'];
if($customIni){
    if(strpos($customIni,".ini") !== false){
        if(!file_exists($customIni)){
            echo "Custom ini file not exists.\r\n";
            exit;
        }
        $customIni = file_get_contents($customIni);
        if($customIni === false){
            echo "Read custom ini file failed.\r\n";
            exit;
        }
    }else{
        $customIni = str_replace(";","\n",$customIni);
    }
}
$customIniHeaderFile = ROOT_PATH."/scripts/custominiheader.bin";
$exclude_pattern = "#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/|/scripts/))(.*)$#";
$signature_algorithm = Phar::SHA256;
$exclude_files = [];
if ($options['exclude-files'] !== '') {
    $exclude_files = explode(",", str_replace("，", ",", $options['exclude-files']));
}
$stagingDir = ROOT_PATH . '/build/rcmaker-phar-src';
buildbin_mkdir(ROOT_PATH.'/build/');

if (file_exists($binFile)) {
    unlink($binFile);
}


##生成Phar
###########################################################################################################
if (file_exists($phar_file)) {
    unlink($phar_file);
}

buildbin_remove_dir($stagingDir);
try {
    buildbin_mkdir($stagingDir);
    buildbin_copy_tree(ROOT_PATH, $stagingDir, $exclude_pattern);
    if ($options['encrypt']) {
        echo "Encrypt staged distribution files...\r\n";
        $encryptBinary = buildbin_ensure_encrypt_binary($sfxDownUrl, $arch);
        buildbin_encrypt_tree($stagingDir, $encryptBinary);
    }





if (!class_exists(Phar::class, false)) {
    throw new RuntimeException("The 'phar' extension is required for build phar package");
}

if (ini_get('phar.readonly')) {
    throw new RuntimeException(
        "The 'phar.readonly' is 'On', build phar must setting it 'Off' or exec with 'php -d phar.readonly=0'"
    );
}

$phar = new Phar($phar_file,0,'rcmaker');
$phar->startBuffering();
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
$phar->buildFromDirectory($stagingDir);


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
} finally {
    buildbin_remove_dir($stagingDir);
}
echo "Generate Phar file successfully.\r\n";

##生成Bin
###########################################################################################################

if (!is_file($sfxFile)) {
	echo "Downloading PHP$version ...\r\n";
	buildbin_download_file($sfxDownUrl, "/$sfxFileName", $sfxFile, "PHP$version");
}else{
	echo "Use PHP$version ...\r\n";
}
// 生成二进制文件

$sfxContents = file_get_contents($sfxFile);
if ($sfxContents === false) {
    throw new RuntimeException("Failed to read SFX file: {$sfxFile}");
}
buildbin_write_file($binFile, $sfxContents);
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
buildbin_cleanup_build_dir(ROOT_PATH.'/build/', $binFileName);
echo "\r\nSaved $binFileName to $binFile\r\nBuild Success!\r\n";
?>
