<?php

declare(strict_types=1);

const RCMAKER_ARTIFACT_BASE_URL = 'https://rcmaker.runchance.com/download';

function rcartifact_supported_php_versions(): array
{
    return ['8.1', '8.2', '8.3', '8.4', '8.5'];
}

function rcartifact_assert_php_version(string $version): void
{
    if (!in_array($version, rcartifact_supported_php_versions(), true)) {
        throw new InvalidArgumentException(
            'Unsupported PHP version: ' . $version
            . '. Supported versions: ' . implode(', ', rcartifact_supported_php_versions())
        );
    }
}

function rcartifact_current_platform(): string
{
    return match (PHP_OS_FAMILY) {
        'Linux' => 'linux',
        'Darwin' => 'macos',
        'Windows' => 'windows',
        default => throw new RuntimeException(
            'Unsupported host platform: ' . PHP_OS_FAMILY . '. Supported platforms: linux, macos, windows'
        ),
    };
}

function rcartifact_normalize_platform(string $platform): string
{
    $platform = strtolower(trim($platform));
    if ($platform === '' || $platform === 'auto') {
        return rcartifact_current_platform();
    }

    $platform = match ($platform) {
        'darwin', 'osx', 'mac', 'macosx' => 'macos',
        'win', 'win32', 'win64' => 'windows',
        default => $platform,
    };

    if (!in_array($platform, ['linux', 'macos', 'windows'], true)) {
        throw new InvalidArgumentException(
            'Unsupported platform: ' . $platform . '. Supported platforms: linux, macos, windows'
        );
    }

    return $platform;
}

function rcartifact_current_arch(): string
{
    return rcartifact_normalize_arch((string)php_uname('m'));
}

function rcartifact_normalize_arch(string $arch): string
{
    $arch = strtolower(trim($arch));
    if ($arch === '' || $arch === 'auto') {
        $arch = strtolower((string)php_uname('m'));
    }

    $arch = match ($arch) {
        'amd64', 'x64', 'x86-64' => 'x86_64',
        'arm64', 'armv8', 'armv8l' => 'aarch64',
        default => $arch,
    };

    if (!in_array($arch, ['x86_64', 'aarch64'], true)) {
        throw new InvalidArgumentException(
            'Unsupported architecture: ' . $arch . '. Supported architectures: x86_64, aarch64'
        );
    }

    return $arch;
}

function rcartifact_assert_target(string $platform, string $arch): void
{
    if ($platform === 'windows' && $arch !== 'x86_64') {
        throw new InvalidArgumentException(
            'Unsupported target: windows/aarch64. Windows artifacts are currently available for x86_64 only.'
        );
    }
}

function rcartifact_assert_host_target(string $platform, string $arch, string $operation): void
{
    $hostPlatform = rcartifact_current_platform();
    $hostArch = rcartifact_current_arch();
    if ($platform !== $hostPlatform || $arch !== $hostArch) {
        throw new RuntimeException(
            $operation . ' executes a platform-specific tool and must run on the target platform. '
            . "Host is {$hostPlatform}/{$hostArch}; target is {$platform}/{$arch}."
        );
    }
}

function rcartifact_runtime_archive(string $version, string $platform, string $arch): string
{
    return "php{$version}-{$platform}-{$arch}.zip";
}

function rcartifact_micro_archive(string $version, string $platform, string $arch): string
{
    return "php{$version}-micro-{$platform}-{$arch}.zip";
}

function rcartifact_beast_archive(string $platform, string $arch): string
{
    return "rcmakerbeast-{$platform}-{$arch}.zip";
}

function rcartifact_runtime_entry(string $platform): string
{
    return $platform === 'windows' ? 'php.exe' : 'php';
}

function rcartifact_beast_entry(string $platform): string
{
    return $platform === 'windows' ? 'rcmakerbeast.exe' : 'rcmakerbeast';
}

function rcartifact_mkdir(string $path): void
{
    if ($path === '' || is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        throw new RuntimeException('Failed to create directory: ' . $path);
    }
}

function rcartifact_download(string $url, string $targetPath): void
{
    rcartifact_mkdir(dirname($targetPath));
    $output = fopen($targetPath, 'wb');
    if (!is_resource($output)) {
        throw new RuntimeException('Failed to create temporary download file: ' . $targetPath);
    }

    try {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            if ($curl === false) {
                throw new RuntimeException('Failed to initialize cURL.');
            }

            curl_setopt_array($curl, [
                CURLOPT_FILE => $output,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_FAILONERROR => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_USERAGENT => 'rcmaker/script',
            ]);
            $success = curl_exec($curl);
            $error = curl_error($curl);
            $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

            if ($success !== true || $status < 200 || $status >= 300) {
                throw new RuntimeException(
                    'Download failed: ' . $url . ($error !== '' ? ' (' . $error . ')' : " (HTTP {$status})")
                );
            }
        } else {
            if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL)) {
                throw new RuntimeException(
                    'Downloading artifacts requires ext-curl or allow_url_fopen=1 in the host PHP.'
                );
            }

            $context = stream_context_create([
                'http' => [
                    'follow_location' => 1,
                    'max_redirects' => 5,
                    'timeout' => 60,
                    'user_agent' => 'rcmaker/script',
                ],
            ]);
            $input = @fopen($url, 'rb', false, $context);
            if (!is_resource($input)) {
                throw new RuntimeException('Download failed: ' . $url);
            }

            try {
                if (stream_copy_to_stream($input, $output) === false) {
                    throw new RuntimeException('Download failed while writing: ' . $url);
                }
            } finally {
                fclose($input);
            }
        }
    } finally {
        fclose($output);
    }

    if (!is_file($targetPath) || filesize($targetPath) === 0) {
        throw new RuntimeException('Downloaded artifact is empty: ' . $url);
    }
}

function rcartifact_extract_single_file(string $archivePath, string $expectedEntry, string $targetPath): void
{
    if (!class_exists(ZipArchive::class)) {
        throw new RuntimeException("The host PHP must have the 'zip' extension enabled to extract artifacts.");
    }

    $zip = new ZipArchive();
    $result = $zip->open($archivePath);
    if ($result !== true) {
        throw new RuntimeException('Failed to open artifact archive: ' . $archivePath . " (ZipArchive code {$result})");
    }

    try {
        if ($zip->numFiles !== 1) {
            throw new RuntimeException(
                'Invalid artifact archive: expected exactly one file, found ' . $zip->numFiles . ' in ' . basename($archivePath)
            );
        }

        $entry = $zip->getNameIndex(0);
        if ($entry !== $expectedEntry) {
            throw new RuntimeException(
                "Invalid artifact archive: expected '{$expectedEntry}', found '" . (string)$entry . "' in " . basename($archivePath)
            );
        }

        $input = $zip->getStream($expectedEntry);
        if (!is_resource($input)) {
            throw new RuntimeException('Failed to read ' . $expectedEntry . ' from ' . basename($archivePath));
        }

        rcartifact_mkdir(dirname($targetPath));
        $temporaryTarget = $targetPath . '.tmp-' . bin2hex(random_bytes(6));
        $output = fopen($temporaryTarget, 'wb');
        if (!is_resource($output)) {
            fclose($input);
            throw new RuntimeException('Failed to create extracted artifact: ' . $temporaryTarget);
        }

        try {
            if (stream_copy_to_stream($input, $output) === false) {
                throw new RuntimeException('Failed to extract ' . $expectedEntry . ' from ' . basename($archivePath));
            }
        } finally {
            fclose($input);
            fclose($output);
        }

        if (is_file($targetPath) && !unlink($targetPath)) {
            @unlink($temporaryTarget);
            throw new RuntimeException('Failed to replace existing artifact: ' . $targetPath);
        }
        if (!rename($temporaryTarget, $targetPath)) {
            @unlink($temporaryTarget);
            throw new RuntimeException('Failed to move extracted artifact to: ' . $targetPath);
        }
    } finally {
        $zip->close();
    }

    if (PHP_OS_FAMILY !== 'Windows') {
        @chmod($targetPath, 0755);
    }
}

function rcartifact_ensure(string $archiveName, string $expectedEntry, string $targetPath): string
{
    if (is_file($targetPath)) {
        echo 'Use existing ' . basename($targetPath) . ' ...' . PHP_EOL;
        return $targetPath;
    }

    $archivePath = tempnam(sys_get_temp_dir(), 'rcmaker-artifact-');
    if ($archivePath === false) {
        throw new RuntimeException('Failed to create temporary artifact archive.');
    }
    $url = RCMAKER_ARTIFACT_BASE_URL . '/' . rawurlencode($archiveName);
    echo 'Downloading ' . $url . ' ...' . PHP_EOL;

    try {
        rcartifact_download($url, $archivePath);
        rcartifact_extract_single_file($archivePath, $expectedEntry, $targetPath);
    } finally {
        if (is_file($archivePath)) {
            @unlink($archivePath);
        }
    }

    return $targetPath;
}
