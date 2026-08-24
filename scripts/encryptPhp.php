<?php

declare(strict_types=1);

define('IS_SCRIPT', 1);
define('ROOT_PATH', dirname(__FILE__, 2));
require_once __DIR__ . '/artifacts.php';

function encryptphp_usage(): never
{
    $usage = <<<'TXT'
Usage:
  php ./scripts/encryptPhp.php --input=source --output=target [options]

Required:
  --input=path               Source PHP file or project directory
  --output=path              Encrypted output file or directory

Options:
  --with-php=8.1             Runtime version for binary/runtime download
  --platform=auto            Target platform: auto, linux, macos, windows
  --arch=auto                Target architecture: auto, x86_64, aarch64
  --entry=index.php          Entry file relative to output directory when building bin
  --build-bin=app.bin        Build a single executable binary after encryption
  --custom-ini=ini-or-file   Inject runtime ini when building bin
  --download-runtime         Download and extract the matching standalone PHP runtime
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
    $path = str_replace('\\', '/', $path);
    $prefix = '';

    if (preg_match('/^([A-Za-z]:|\/\/[^\/]+\/[^\/]+|\/)/', $path, $match)) {
        $prefix = $match[1];
        $path = substr($path, strlen($prefix));
    }

    $segments = explode('/', $path);
    $normalized = [];
    foreach ($segments as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }

        if ($segment === '..') {
            array_pop($normalized);
            continue;
        }

        $normalized[] = $segment;
    }

    $path = $prefix . ($prefix !== '' && !str_ends_with($prefix, '/') ? '/' : '') . implode('/', $normalized);
    return rtrim($path, '/');
}

function encryptphp_parse_options(array $args): array
{
    $options = [
        'input' => '',
        'output' => '',
        'with-php' => '8.1',
        'platform' => 'auto',
        'arch' => 'auto',
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
    $sourceRoot = encryptphp_normalize_compare_path(encryptphp_absolute_path($sourceRoot));
    $targetRoot = encryptphp_normalize_compare_path(encryptphp_absolute_path($targetRoot));
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

function encryptphp_ensure_encrypt_binary(string $workDir, string $platform, string $arch): string
{
    $encryptBinary = rcartifact_beast_entry($platform);
    return rcartifact_ensure(
        rcartifact_beast_archive($platform, $arch),
        $encryptBinary,
        $workDir . DIRECTORY_SEPARATOR . $encryptBinary
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

function encryptphp_write_to_stream($stream, string $contents): void
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

function encryptphp_copy_file_to_stream(string $sourcePath, $targetStream): void
{
    $source = fopen($sourcePath, 'rb');
    if (!is_resource($source)) {
        throw new RuntimeException('Failed to open binary input: ' . $sourcePath);
    }

    try {
        if (stream_copy_to_stream($source, $targetStream) === false) {
            throw new RuntimeException('Failed to append binary input: ' . $sourcePath);
        }
    } finally {
        fclose($source);
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

    encryptphp_mkdir(dirname($outputFile));
    $temporaryOutput = $outputFile . '.tmp-' . bin2hex(random_bytes(6));
    $output = fopen($temporaryOutput, 'wb');
    if (!is_resource($output)) {
        throw new RuntimeException('Failed to create binary output: ' . $temporaryOutput);
    }

    try {
        encryptphp_copy_file_to_stream($sfxFile, $output);
        if ($customIni !== '') {
            encryptphp_write_to_stream(
                $output,
                "\xfd\xf6\x69\xe6" . pack('N', strlen($customIni)) . $customIni
            );
        }
        encryptphp_copy_file_to_stream($payloadFile, $output);
    } catch (Throwable $throwable) {
        fclose($output);
        @unlink($temporaryOutput);
        throw $throwable;
    }
    fclose($output);

    if (is_file($outputFile) && !unlink($outputFile)) {
        @unlink($temporaryOutput);
        throw new RuntimeException('Failed to replace binary output: ' . $outputFile);
    }
    if (!rename($temporaryOutput, $outputFile)) {
        @unlink($temporaryOutput);
        throw new RuntimeException('Failed to finalize binary output: ' . $outputFile);
    }

    if (PHP_OS_FAMILY !== 'Windows') {
        @chmod($outputFile, 0755);
    }
}

function encryptphp_runtime_output(string $outputPath, string $runtimeOutput, string $platform): string
{
    if ($runtimeOutput !== '') {
        return $runtimeOutput;
    }

    $baseDir = is_dir($outputPath) ? $outputPath : dirname($outputPath);
    return rtrim($baseDir, DIRECTORY_SEPARATOR . '/\\')
        . DIRECTORY_SEPARATOR
        . rcartifact_runtime_entry($platform);
}

 $encryptPhpError = null;
 $workDir = '';

try {
    if (!extension_loaded('openssl')) {
        throw new RuntimeException('The host PHP must have ext-openssl enabled to run this script.');
    }

    $options = encryptphp_parse_options(array_slice($argv, 1));
    rcartifact_assert_php_version($options['with-php']);
    $platform = rcartifact_normalize_platform($options['platform']);
    $arch = rcartifact_normalize_arch($options['arch']);
    rcartifact_assert_target($platform, $arch);
    rcartifact_assert_host_target($platform, $arch, 'PHP encryption');

    $inputPath = $options['input'];
    $outputPath = $options['output'];
    $buildBinPath = $options['build-bin'];
    $force = $options['force'];
    $excludePaths = encryptphp_parse_exclude_paths($options['exclude-files']);
    $customIni = encryptphp_resolve_custom_ini($options['custom-ini']);
    $workDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR . '/\\') . DIRECTORY_SEPARATOR . 'rcmaker-encrypt-php';
    encryptphp_mkdir($workDir);
    $encryptBinary = encryptphp_ensure_encrypt_binary($workDir, $platform, $arch);

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
    }

    if ($options['download-runtime']) {
        $runtimeOutput = encryptphp_runtime_output($outputPath, $options['runtime-output'], $platform);
        $runtimeEntry = rcartifact_runtime_entry($platform);
        if (is_file($runtimeOutput)) {
            if (!$force) {
                throw new RuntimeException(
                    'Runtime output already exists: ' . $runtimeOutput . ' (use --force to replace it)'
                );
            }
            if (!unlink($runtimeOutput)) {
                throw new RuntimeException('Failed to replace runtime output: ' . $runtimeOutput);
            }
        }
        rcartifact_ensure(
            rcartifact_runtime_archive($options['with-php'], $platform, $arch),
            $runtimeEntry,
            $runtimeOutput
        );
        echo 'Runtime saved to: ' . $runtimeOutput . PHP_EOL;
    }

    if ($buildBinPath !== '') {
        $version = $options['with-php'];
        $sfxFile = rcartifact_ensure(
            rcartifact_micro_archive($version, $platform, $arch),
            'micro.sfx',
            $workDir . DIRECTORY_SEPARATOR . 'micro.sfx'
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
    $encryptPhpError = $throwable;
} finally {
    if ($workDir !== '' && is_dir($workDir)) {
        encryptphp_remove_dir($workDir);
    }
}

if (isset($encryptPhpError)) {
    encryptphp_fail($encryptPhpError->getMessage());
}
