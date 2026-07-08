<?php

declare(strict_types=1);

define('IS_SCRIPT', 1);
define('ROOT_PATH', dirname(__FILE__, 2));

const ENCRYPTPHP_HOST = 'rcmaker.runchance.com';
const ENCRYPTPHP_ENCRYPT_BINARY = 'rcmakerbeast';

function encryptphp_usage(): never
{
    $usage = <<<'TXT'
Usage:
  php ./scripts/encryptPhp.php --input=source --output=target [options]

Required:
  --input=path               Source PHP file or project directory
  --output=path              Encrypted output file or directory

Options:
  --with-php=8.1             Runtime version for micro.sfx / php81-php85 download
  --entry=index.php          Entry file relative to output directory when building bin
  --build-bin=app.bin        Build a single executable binary after encryption
  --custom-ini=ini-or-file   Inject runtime ini when building bin
  --download-runtime         Download php81-php85 runtime beside the encrypted output
  --runtime-output=path      Custom runtime output path; implies --download-runtime
  --exclude-files=a,b,c      Skip relative files/directories when encrypting a directory
  --force                    Overwrite output/bin when target already exists
  --help                     Show this help

Examples:
  php ./scripts/encryptPhp.php --input=./demo.php --output=./dist/demo.php
  php ./scripts/encryptPhp.php --input=./demo.php --output=./dist/demo.php --build-bin=./dist/demo.bin --with-php=8.5
  php ./scripts/encryptPhp.php --input=./project --output=./build/project --entry=public/index.php --download-runtime
  php -d phar.readonly=0 ./scripts/encryptPhp.php --input=./project --output=./build/project --entry=index.php --build-bin=./build/project.bin --with-php=8.1
TXT;

    fwrite(STDOUT, $usage . PHP_EOL);
    exit(0);
}

function encryptphp_fail(string $message, int $exitCode = 1): never
{
    fwrite(STDERR, '[encryptPhp] ' . $message . PHP_EOL);
    exit($exitCode);
}

function encryptphp_normalize_relative_path(string $path): string
{
    return '/' . ltrim(str_replace('\\', '/', $path), '/');
}

function encryptphp_is_absolute_path(string $path): bool
{
    return (bool)preg_match('/^(?:[A-Za-z]:[\\\\\/]|\\\\\\\\|\/)/', $path);
}

function encryptphp_absolute_path(string $path): string
{
    if (encryptphp_is_absolute_path($path)) {
        return $path;
    }

    $cwd = getcwd();
    if ($cwd === false) {
        encryptphp_fail('Cannot determine current working directory.');
    }

    return $cwd . DIRECTORY_SEPARATOR . $path;
}

function encryptphp_normalize_compare_path(string $path): string
{
    return rtrim(str_replace('\\', '/', $path), '/');
}

function encryptphp_assert_supported_php_version(string $version): void
{
    $supportedVersions = ['8.1', '8.2', '8.3', '8.4', '8.5'];
    if (!in_array($version, $supportedVersions, true)) {
        throw new InvalidArgumentException(
            'Unsupported PHP version: ' . $version . '. Supported versions: ' . implode(', ', $supportedVersions)
        );
    }
}

function encryptphp_parse_options(array $args): array
{
    $options = [
        'input' => '',
        'output' => '',
        'with-php' => '8.1',
        'entry' => '',
        'build-bin' => '',
        'custom-ini' => '',
        'download-runtime' => false,
        'runtime-output' => '',
        'exclude-files' => '',
        'force' => false,
    ];

    foreach ($args as $arg) {
        if (!str_starts_with($arg, '--')) {
            throw new InvalidArgumentException('Unknown positional argument: ' . $arg);
        }

        if ($arg === '--help') {
            encryptphp_usage();
        }

        if ($arg === '--force') {
            $options['force'] = true;
            continue;
        }

        if ($arg === '--download-runtime') {
            $options['download-runtime'] = true;
            continue;
        }

        $parts = explode('=', substr($arg, 2), 2);
        if (count($parts) !== 2 || $parts[0] === '') {
            throw new InvalidArgumentException('Invalid option format: ' . $arg);
        }

        [$name, $value] = $parts;
        if (!array_key_exists($name, $options)) {
            throw new InvalidArgumentException('Unknown option: --' . $name);
        }

        $options[$name] = $value;
    }

    if ($options['input'] === '' || $options['output'] === '') {
        throw new InvalidArgumentException('--input and --output are required.');
    }

    if ($options['runtime-output'] !== '') {
        $options['download-runtime'] = true;
    }

    return $options;
}

function encryptphp_mkdir(string $path): void
{
    if ($path === '' || is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        throw new RuntimeException('Failed to create directory: ' . $path);
    }
}

function encryptphp_write_file(string $path, string $contents): void
{
    encryptphp_mkdir(dirname($path));
    if (file_put_contents($path, $contents) === false) {
        throw new RuntimeException('Failed to write file: ' . $path);
    }
}

function encryptphp_remove_dir(string $dir): void
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

function encryptphp_remove_path(string $path): void
{
    if (is_dir($path)) {
        encryptphp_remove_dir($path);
        return;
    }

    if (is_file($path)) {
        unlink($path);
    }
}

function encryptphp_runtime_name(string $version): string
{
    return 'php' . str_replace('.', '', $version);
}

function encryptphp_sfx_name(string $version): string
{
    return 'php' . $version . '.micro.sfx';
}

function encryptphp_parse_exclude_paths(string $value): array
{
    if ($value === '') {
        return [];
    }

    $paths = [];
    foreach (explode(',', str_replace('，', ',', $value)) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $paths[] = encryptphp_normalize_relative_path($part);
    }

    return array_values(array_unique($paths));
}

function encryptphp_should_exclude(string $relativePath, array $excludePaths): bool
{
    foreach ($excludePaths as $excludePath) {
        if ($relativePath === $excludePath || str_starts_with($relativePath, $excludePath . '/')) {
            return true;
        }
    }

    return false;
}

function encryptphp_copy_tree(string $sourceRoot, string $targetRoot, array $excludePaths): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $sourcePath = $item->getPathname();
        $relativePath = substr($sourcePath, strlen($sourceRoot) + 1);
        $normalizedRelativePath = encryptphp_normalize_relative_path($relativePath);
        if (encryptphp_should_exclude($normalizedRelativePath, $excludePaths)) {
            continue;
        }

        $targetPath = $targetRoot . DIRECTORY_SEPARATOR . $relativePath;
        if ($item->isDir()) {
            encryptphp_mkdir($targetPath);
            continue;
        }

        encryptphp_mkdir(dirname($targetPath));
        if (!copy($sourcePath, $targetPath)) {
            throw new RuntimeException('Cannot copy file: ' . $sourcePath . ' -> ' . $targetPath);
        }
    }
}

function encryptphp_download_file(string $host, string $remotePath, string $targetPath, string $label): void
{
    $client = @stream_socket_client('tcp://' . $host . ':80', $errno, $errstr);
    if (!is_resource($client)) {
        throw new RuntimeException('Connect ' . $label . ' download server failed: ' . $errstr . ' (' . $errno . ')');
    }

    fwrite(
        $client,
        'GET ' . $remotePath . " HTTP/1.1\r\nAccept: */*\r\nHost: " . $host . "\r\nUser-Agent: rcmaker/script\r\nConnection: close\r\n\r\n"
    );

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
                    throw new RuntimeException('Download ' . $label . ' failed.');
                }
                if (!preg_match('/Content-Length: (\d+)\r\n/i', $header, $match)) {
                    throw new RuntimeException('Download ' . $label . ' failed.');
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
                echo '[' . str_pad('', $percent, '=') . '>' . str_pad('', 100 - $percent) . $percent . "%]";
                echo $percent < 100 ? "\r" : "\n";
                $lastPercent = $percent;
            }

            if ($receiveLength >= $bodyLength) {
                encryptphp_write_file($targetPath, $bodyBuffer);
                break;
            }
        }

        if ($buffer === false || !is_resource($client) || feof($client)) {
            break;
        }
    }

    fclose($client);

    if (!is_file($targetPath)) {
        throw new RuntimeException('Download ' . $label . ' failed.');
    }
}

function encryptphp_ensure_download(string $host, string $remotePath, string $targetPath, string $label): string
{
    if (is_file($targetPath)) {
        echo 'Use existing ' . $label . " ...\r\n";
        return $targetPath;
    }

    echo 'Downloading ' . $label . " ...\r\n";
    encryptphp_download_file($host, $remotePath, $targetPath, $label);
    @chmod($targetPath, 0755);
    return $targetPath;
}

function encryptphp_ensure_encrypt_binary(string $workDir): string
{
    return encryptphp_ensure_download(
        ENCRYPTPHP_HOST,
        '/' . ENCRYPTPHP_ENCRYPT_BINARY,
        $workDir . DIRECTORY_SEPARATOR . ENCRYPTPHP_ENCRYPT_BINARY,
        ENCRYPTPHP_ENCRYPT_BINARY
    );
}

function encryptphp_run_encrypt_binary(
    string $encryptBinary,
    string $mode,
    string $inputPath,
    string $outputPath,
    bool $force
): void {
    $command = escapeshellarg($encryptBinary)
        . ' '
        . $mode
        . ' '
        . escapeshellarg($inputPath)
        . ' '
        . escapeshellarg($outputPath);

    if ($force) {
        $command .= ' --force';
    }

    passthru($command, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('Encrypting files failed.');
    }
}

function encryptphp_resolve_custom_ini(string $customIni): string
{
    if ($customIni === '') {
        return '';
    }

    if (str_contains($customIni, '.ini')) {
        if (!is_file($customIni)) {
            throw new RuntimeException('Custom ini file not exists: ' . $customIni);
        }

        $contents = file_get_contents($customIni);
        if ($contents === false) {
            throw new RuntimeException('Read custom ini file failed: ' . $customIni);
        }

        return $contents;
    }

    return str_replace(';', "\n", $customIni);
}

function encryptphp_append_ini_header(string $binaryPath, string $customIni): void
{
    if ($customIni === '') {
        return;
    }

    $header = "\xfd\xf6\x69\xe6" . pack('N', strlen($customIni)) . $customIni;
    if (file_put_contents($binaryPath, $header, FILE_APPEND) === false) {
        throw new RuntimeException('Failed to append custom ini header: ' . $binaryPath);
    }
}

function encryptphp_resolve_entry(string $sourceRoot, string $entry): string
{
    $entry = trim(str_replace('\\', '/', $entry));
    $entry = ltrim($entry, '/');
    if ($entry === '') {
        if (is_file($sourceRoot . DIRECTORY_SEPARATOR . 'index.php')) {
            return 'index.php';
        }

        throw new RuntimeException('Directory build-bin mode requires --entry when index.php is not present.');
    }

    $entryPath = $sourceRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entry);
    if (!is_file($entryPath)) {
        throw new RuntimeException('Entry file does not exist in encrypted output: ' . $entry);
    }

    return $entry;
}

function encryptphp_phar_alias(string $path): string
{
    $name = pathinfo($path, PATHINFO_FILENAME);
    $name = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string)$name);
    return $name !== '' ? $name : 'app';
}

function encryptphp_build_phar(string $sourcePath, string $pharPath, string $entry, string $alias): void
{
    if (!class_exists(Phar::class, false)) {
        throw new RuntimeException("The 'phar' extension is required for build phar package.");
    }

    if (ini_get('phar.readonly')) {
        throw new RuntimeException(
            "The 'phar.readonly' is 'On', build phar must setting it 'Off' or exec with 'php -d phar.readonly=0'."
        );
    }

    encryptphp_remove_path($pharPath);

    $phar = new Phar($pharPath, 0, $alias);
    $phar->startBuffering();
    $phar->setSignatureAlgorithm(Phar::SHA256);

    if (is_file($sourcePath)) {
        $entryFile = basename($sourcePath);
        $phar->addFile($sourcePath, $entryFile);
    } else {
        $entryFile = encryptphp_resolve_entry($sourcePath, $entry);
        $phar->buildFromDirectory($sourcePath);
    }

    $phar->setStub("#!/usr/bin/env php\n<?php\nPhar::mapPhar('{$alias}');\nrequire 'phar://{$alias}/{$entryFile}';\n__HALT_COMPILER();");
    $phar->stopBuffering();
    unset($phar);
}

function encryptphp_build_binary(string $sfxFile, string $payloadFile, string $outputFile, string $customIni, bool $force): void
{
    if (file_exists($outputFile) && !$force) {
        throw new RuntimeException('Binary output already exists: ' . $outputFile . ' (use --force to overwrite)');
    }

    $sfxContents = file_get_contents($sfxFile);
    if ($sfxContents === false) {
        throw new RuntimeException('Failed to read SFX file: ' . $sfxFile);
    }

    $payloadContents = file_get_contents($payloadFile);
    if ($payloadContents === false) {
        throw new RuntimeException('Failed to read payload file: ' . $payloadFile);
    }

    encryptphp_write_file($outputFile, $sfxContents);
    encryptphp_append_ini_header($outputFile, $customIni);
    if (file_put_contents($outputFile, $payloadContents, FILE_APPEND) === false) {
        throw new RuntimeException('Failed to append payload file: ' . $outputFile);
    }

    @chmod($outputFile, 0755);
}

function encryptphp_runtime_output(string $outputPath, string $version, string $runtimeOutput): string
{
    if ($runtimeOutput !== '') {
        return $runtimeOutput;
    }

    $baseDir = is_dir($outputPath) ? $outputPath : dirname($outputPath);
    return rtrim($baseDir, DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . encryptphp_runtime_name($version);
}

try {
    if (!extension_loaded('openssl')) {
        throw new RuntimeException('The host PHP must have ext-openssl enabled to run this script.');
    }

    $options = encryptphp_parse_options(array_slice($argv, 1));
    encryptphp_assert_supported_php_version($options['with-php']);

    $inputPath = $options['input'];
    $outputPath = $options['output'];
    $buildBinPath = $options['build-bin'];
    $force = $options['force'];
    $excludePaths = encryptphp_parse_exclude_paths($options['exclude-files']);
    $customIni = encryptphp_resolve_custom_ini($options['custom-ini']);
    $workDir = ROOT_PATH . '/build/encrypt-php';
    encryptphp_mkdir($workDir);
    $encryptBinary = encryptphp_ensure_encrypt_binary($workDir);

    if (!file_exists($inputPath)) {
        throw new RuntimeException('Input path does not exist: ' . $inputPath);
    }

    if (is_dir($outputPath) && $force && encryptphp_normalize_compare_path(encryptphp_absolute_path($inputPath)) !== encryptphp_normalize_compare_path(encryptphp_absolute_path($outputPath))) {
        encryptphp_remove_dir($outputPath);
    } elseif (is_file($outputPath) && $force && encryptphp_normalize_compare_path(encryptphp_absolute_path($inputPath)) !== encryptphp_normalize_compare_path(encryptphp_absolute_path($outputPath))) {
        unlink($outputPath);
    }

    if (is_file($inputPath)) {
        encryptphp_run_encrypt_binary($encryptBinary, 'file', $inputPath, $outputPath, $force);
        echo 'Encrypted file: ' . $inputPath . ' -> ' . $outputPath . PHP_EOL;
    } else {
        if (file_exists($outputPath) && !is_dir($outputPath) && encryptphp_normalize_compare_path(encryptphp_absolute_path($inputPath)) !== encryptphp_normalize_compare_path(encryptphp_absolute_path($outputPath))) {
            throw new RuntimeException('Directory output path already exists as a file: ' . $outputPath);
        }
        if (is_dir($outputPath) && !$force && encryptphp_normalize_compare_path(encryptphp_absolute_path($inputPath)) !== encryptphp_normalize_compare_path(encryptphp_absolute_path($outputPath))) {
            throw new RuntimeException('Output directory already exists: ' . $outputPath . ' (use --force to overwrite)');
        }

        $stagingDir = $workDir . DIRECTORY_SEPARATOR . 'staging';
        encryptphp_remove_dir($stagingDir);
        encryptphp_mkdir($stagingDir);
        try {
            encryptphp_copy_tree($inputPath, $stagingDir, $excludePaths);
            encryptphp_run_encrypt_binary($encryptBinary, 'dir', $stagingDir, $outputPath, $force);
        } finally {
            encryptphp_remove_dir($stagingDir);
        }

        $stats = ['encrypted' => 0, 'copied' => 0, 'skipped' => 0];
        echo sprintf(
            'Encrypted directory: %s -> %s%s',
            $inputPath,
            $outputPath,
            PHP_EOL
        );
    }

    if ($options['download-runtime']) {
        $runtimeOutput = encryptphp_runtime_output($outputPath, $options['with-php'], $options['runtime-output']);
        encryptphp_ensure_download(
            ENCRYPTPHP_HOST,
            '/' . encryptphp_runtime_name($options['with-php']),
            $runtimeOutput,
            encryptphp_runtime_name($options['with-php'])
        );
        echo 'Runtime saved to: ' . $runtimeOutput . PHP_EOL;
    }

    if ($buildBinPath !== '') {
        $version = $options['with-php'];
        $sfxFile = encryptphp_ensure_download(
            ENCRYPTPHP_HOST,
            '/' . encryptphp_sfx_name($version),
            $workDir . DIRECTORY_SEPARATOR . encryptphp_sfx_name($version),
            encryptphp_sfx_name($version)
        );

        $pharPath = $workDir . DIRECTORY_SEPARATOR . pathinfo($buildBinPath, PATHINFO_FILENAME) . '.phar';
        $alias = encryptphp_phar_alias($buildBinPath);
        $entry = $options['entry'];
        encryptphp_build_phar($outputPath, $pharPath, $entry, $alias);
        encryptphp_build_binary($sfxFile, $pharPath, $buildBinPath, $customIni, $force);
        encryptphp_remove_path($pharPath);

        echo 'Binary saved to: ' . $buildBinPath . PHP_EOL;
    }
} catch (Throwable $throwable) {
    encryptphp_fail($throwable->getMessage());
}
