<?php
define('IS_SCRIPT',1);
define('ROOT_PATH', dirname(__FILE__,2));
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/artifacts.php';

const BUILDBIN_DEFAULT_EXCLUDE_PREFIXES = [
    '/.agents', '/.claude', '/.codex', '/.continue', '/.cursor', '/.gemini', '/.github',
    '/.idea', '/.roo', '/.setting', '/.tmp', '/.vscode', '/build', '/coverage',
    '/node_modules', '/official', '/runtime', '/scripts', '/test', '/tests', '/tools', '/vendor-bin',
];
const BUILDBIN_DEFAULT_EXCLUDE_DIRECTORY_NAMES = [
    '.agents', '.claude', '.codex', '.continue', '.cursor', '.gemini', '.git', '.github',
    '.hg', '.idea', '.roo', '.setting', '.svn', '.vscode', 'node_modules', 'vendor-bin',
];
const BUILDBIN_DEFAULT_EXCLUDE_VENDOR_DIRECTORY_NAMES = [
    'benchmark', 'benchmarks', 'doc', 'docs', 'example', 'examples', 'test', 'tests',
];
const BUILDBIN_DEFAULT_EXCLUDE_FILE_NAMES = [
    '.editorconfig', '.env.example', '.gitattributes', '.gitignore', '.gitmodules',
    'bun.lock', 'bun.lockb', 'composer.json', 'composer.lock', 'compose.yaml', 'compose.yml',
    'docker-compose.yaml', 'docker-compose.yml', 'dockerfile', 'jsconfig.json', 'makefile',
    'package-lock.json', 'package.json', 'phpstan.neon', 'phpstan.neon.dist', 'phpunit.xml',
    'phpunit.xml.dist', 'pnpm-lock.yaml', 'psalm.xml', 'rector.php', 'tsconfig.json',
    'windows.bat', 'windows.php', 'yarn.lock',
];
const BUILDBIN_DEFAULT_EXCLUDE_EXTENSIONS = ['map', 'markdown', 'md', 'rst'];

function buildbin_normalize_relative_path(string $path): string
{
    return '/' . ltrim(str_replace('\\', '/', $path), '/');
}

function buildbin_parse_exclude_paths(string $value): array
{
    if (trim($value) === '') {
        return [];
    }

    $paths = [];
    foreach (explode(',', str_replace('，', ',', $value)) as $path) {
        $path = trim(str_replace('\\', '/', $path), " \t\n\r\0\x0B/");
        if ($path === '') {
            continue;
        }

        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                throw new InvalidArgumentException('Exclude paths cannot contain ..: ' . $path);
            }
            $segments[] = $segment;
        }
        if ($segments === []) {
            throw new InvalidArgumentException('Exclude path must point to a file or directory inside the project.');
        }

        $paths[] = buildbin_normalize_relative_path(implode('/', $segments));
    }

    return array_values(array_unique($paths));
}

function buildbin_should_exclude(string $normalizedPath, array $excludePaths): bool
{
    foreach ($excludePaths as $excludePath) {
        if ($normalizedPath === $excludePath || str_starts_with($normalizedPath, $excludePath . '/')) {
            return true;
        }
    }

    return false;
}

function buildbin_contains_directory_named(string $normalizedPath, array $directoryNames): bool
{
    foreach (explode('/', trim(str_replace('\\', '/', $normalizedPath), '/')) as $segment) {
        if (in_array(strtolower($segment), $directoryNames, true)) {
            return true;
        }
    }
    return false;
}

function buildbin_should_exclude_default(string $normalizedPath): bool
{
    $normalizedPath = buildbin_normalize_relative_path($normalizedPath);
    if (buildbin_contains_directory_named($normalizedPath, BUILDBIN_DEFAULT_EXCLUDE_DIRECTORY_NAMES)) {
        return true;
    }
    foreach (BUILDBIN_DEFAULT_EXCLUDE_PREFIXES as $prefix) {
        if ($normalizedPath === $prefix || str_starts_with($normalizedPath, $prefix . '/')) {
            return true;
        }
    }
    if (str_starts_with($normalizedPath, '/vendor/')
        && buildbin_contains_directory_named($normalizedPath, BUILDBIN_DEFAULT_EXCLUDE_VENDOR_DIRECTORY_NAMES)
    ) {
        return true;
    }

    $fileName = strtolower(basename($normalizedPath));
    if (in_array($fileName, BUILDBIN_DEFAULT_EXCLUDE_FILE_NAMES, true)) {
        return true;
    }
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    return $extension !== '' && in_array($extension, BUILDBIN_DEFAULT_EXCLUDE_EXTENSIONS, true);
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
            buildbin_remove_empty_dir($item->getPathname());
            continue;
        }
        buildbin_remove_file($item->getPathname());
    }

    buildbin_remove_empty_dir($dir);
}

function buildbin_remove_file(string $path): void
{
    for ($attempt = 0; $attempt < 20; $attempt++) {
        if (!file_exists($path) || @unlink($path)) {
            return;
        }
        usleep(100000);
    }

    throw new RuntimeException('Failed to remove file after retries: ' . $path);
}

function buildbin_remove_empty_dir(string $path): void
{
    for ($attempt = 0; $attempt < 20; $attempt++) {
        if (!is_dir($path) || @rmdir($path)) {
            return;
        }
        usleep(100000);
    }

    throw new RuntimeException('Failed to remove directory after retries: ' . $path);
}

function buildbin_remove_path(string $path): void
{
    if (is_dir($path)) {
        buildbin_remove_dir($path);
        return;
    }
    if (is_file($path)) {
        buildbin_remove_file($path);
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

function buildbin_copy_tree(
    string $sourceRoot,
    string $targetRoot,
    array $excludePaths
): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $sourcePath = $item->getPathname();
        $relativePath = substr($sourcePath, strlen($sourceRoot) + 1);
        $normalizedPath = buildbin_normalize_relative_path($relativePath);

        if (buildbin_should_exclude_default($normalizedPath)
            || buildbin_should_exclude($normalizedPath, $excludePaths)
        ) {
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

function buildbin_copy_file_to_stream(string $sourcePath, $targetStream): void
{
    $source = fopen($sourcePath, 'rb');
    if (!is_resource($source)) {
        throw new RuntimeException('Failed to open build input: ' . $sourcePath);
    }

    try {
        if (stream_copy_to_stream($source, $targetStream) === false) {
            throw new RuntimeException('Failed to append build input: ' . $sourcePath);
        }
    } finally {
        fclose($source);
    }
}

function buildbin_write_to_stream($stream, string $contents): void
{
    $length = strlen($contents);
    $offset = 0;
    while ($offset < $length) {
        $written = fwrite($stream, substr($contents, $offset));
        if ($written === false || $written === 0) {
            throw new RuntimeException('Failed to write binary output.');
        }
        $offset += $written;
    }
}

function buildbin_parse_options(array $args): array
{
    $options = [
        'with-php' => '8.4',
        'platform' => 'auto',
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

function buildbin_ensure_encrypt_binary(string $platform, string $arch): string
{
    $encryptBinaryName = 'rcmakerbeast-' . $platform . '-' . $arch
        . ($platform === 'windows' ? '.exe' : '');
    $encryptBinary = ROOT_PATH . '/build/' . $encryptBinaryName;
    return rcartifact_ensure(
        rcartifact_beast_archive($platform, $arch),
        rcartifact_beast_entry($platform),
        $encryptBinary
    );
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
$options = buildbin_parse_options(array_slice($argv, 1));
rcartifact_assert_php_version($options['with-php']);

$version = $options['with-php'];
$platform = rcartifact_normalize_platform($options['platform']);
$arch = rcartifact_normalize_arch($options['arch']);
rcartifact_assert_target($platform, $arch);
$binFileName = $platform === 'windows' ? 'rcmaker.exe' : 'rcmaker.bin';
$binFile = ROOT_PATH . '/build/' . $binFileName;
$sfxArchive = rcartifact_micro_archive($version, $platform, $arch);
$sfxFile = ROOT_PATH . '/build/' . substr($sfxArchive, 0, -4) . '.sfx';
$entryFile = 'index.php';
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
$signature_algorithm = Phar::SHA256;
$excludePaths = buildbin_parse_exclude_paths($options['exclude-files']);
$stagingDir = ROOT_PATH . '/build/rcmaker-phar-src';
buildbin_mkdir(ROOT_PATH.'/build/');

buildbin_remove_path($binFile);


##生成Phar
###########################################################################################################
buildbin_remove_path($phar_file);

buildbin_remove_dir($stagingDir);
try {
    buildbin_mkdir($stagingDir);
    buildbin_copy_tree(ROOT_PATH, $stagingDir, $excludePaths);
    if (!is_file($stagingDir . DIRECTORY_SEPARATOR . $entryFile)) {
        throw new RuntimeException(
            "Build entry {$entryFile} is missing or excluded for target {$platform}/{$arch}."
        );
    }
    if ($options['encrypt']) {
        echo "Encrypt staged distribution files...\r\n";
        $encryptBinary = buildbin_ensure_encrypt_binary(
            rcartifact_current_platform(),
            rcartifact_current_arch()
        );
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

echo "Files collect complete, begin add file to Phar.\r\n";

$stub = "#!/usr/bin/env php\n"
    . "<?php\n"
    . "define('IN_PHAR', true);\n"
    . "Phar::mapPhar('rcmaker');\n"
    . "require 'phar://rcmaker/{$entryFile}';\n"
    . "__HALT_COMPILER();\n";
$phar->setStub($stub);
$phar->stopBuffering();
unset($phar);
} finally {
    buildbin_remove_dir($stagingDir);
}
echo "Generate Phar file successfully.\r\n";

##生成Bin
###########################################################################################################

rcartifact_ensure($sfxArchive, 'micro.sfx', $sfxFile);
// 生成二进制文件
$temporaryBin = $binFile . '.tmp';
$output = fopen($temporaryBin, 'wb');
if (!is_resource($output)) {
    throw new RuntimeException('Failed to create binary output: ' . $temporaryBin);
}

try {
    buildbin_copy_file_to_stream($sfxFile, $output);
    if ($customIni !== '') {
        buildbin_write_to_stream($output, "\xfd\xf6\x69\xe6" . pack('N', strlen($customIni)) . $customIni);
    }
    buildbin_copy_file_to_stream($phar_file, $output);
} catch (Throwable $throwable) {
    fclose($output);
    @unlink($temporaryBin);
    throw $throwable;
}
fclose($output);

if (!rename($temporaryBin, $binFile)) {
    @unlink($temporaryBin);
    throw new RuntimeException('Failed to finalize binary output: ' . $binFile);
}
 // 添加执行权限
if ($platform !== 'windows') {
    @chmod($binFile, 0755);
}
buildbin_cleanup_build_dir(ROOT_PATH.'/build/', $binFileName);
echo "\r\nSaved $binFileName to $binFile\r\nBuild Success!\r\n";
?>
