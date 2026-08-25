<?php

declare(strict_types=1);

$rules = [
    ['native-http', '/\b(?:curl_init|curl_exec|curl_setopt|curl_setopt_array|curl_multi_init)\s*\(/i', '数据抓取和外部 HTTP 必须使用框架 curl() / curl(true)。'],
    ['url-stream', '/\b(?:file_get_contents|fopen)\s*\(\s*[\'\"]https?:\/\//i', 'URL 读取必须使用框架 curl()，不要使用 PHP URL 流。'],
    ['third-party-http', '/\bnew\s+(?:\x5c)?(?:GuzzleHttp\x5cClient|Symfony\x5cComponent\x5cHttpClient\x5c[A-Za-z_][A-Za-z0-9_]*)\b/i', '框架已有 HTTP 客户端，禁止创建重复的第三方客户端。'],
    ['shell-http', '/\b(?:exec|shell_exec|system|passthru|popen|proc_open)\s*\([^;]*(?:curl|wget)\b/is', '生产代码不得通过 shell curl/wget 发起网络请求。'],
    ['native-database', '/\bnew\s+(?:\x5c)?(?:PDO|SQLite3)\b|\bmysqli_[a-z_]+\s*\(/i', '数据库操作必须复用 AutoForm、SDB 或复杂 SQL 场景下的 DB()。'],
    ['direct-db', '/(?<![A-Za-z0-9_])(?:DB|database)\s*\(/i', '确认 AutoForm 和 SDB 都无法合理表达；DB() 仅用于复杂 SQL。'],
    ['manual-validation', '/\b(?:filter_var|preg_match)\s*\(/i', '若用于外部输入校验，必须改用 validator() 或 AutoForm data 规则。'],
    ['native-redis', '/\bnew\s+(?:\x5c)?Redis(?:Cluster)?\b/i', 'Redis 必须使用 redis() / RD() 及框架连接配置。'],
    ['native-session', '/\bsession_start\s*\(|\$_SESSION\b/i', 'Session 必须使用 $req->session() / $req->S()。'],
    ['native-cookie', '/\bsetcookie\s*\(|\$_COOKIE\b/i', 'Cookie 必须使用 Request/Response Cookie 能力。'],
    ['superglobal-input', '/\$_(?:GET|POST|REQUEST|FILES)\b/i', 'HTTP 输入必须从 RC\\Request 获取。'],
    ['native-response', '/\b(?:header|http_response_code|readfile)\s*\(/i', 'HTTP 输出必须使用 $req->response()、json()、file() 或 download()。'],
    ['manual-json', '/\bjson_encode\s*\(/i', '确认这不是 HTTP JSON 响应；HTTP JSON 必须使用 $req->json()。'],
    ['native-mail', '/(?<!->)(?<!::)\bmail\s*\(/i', '邮件发送必须使用 mailer() / ML()，耗时发送应进入 Queue。'],
];

$skipPattern = '#(?:^|[/\\\\])(?:tests?|fixtures?|vendor|runtime|build|\.git|\.agents)(?:[/\\\\]|$)#i';
$targets = array_slice($argv, 1);
if ($targets === []) {
    $targets = ['apps', 'support'];
}

function collectPhpFiles(string $target, string $skipPattern): array
{
    if (is_file($target)) {
        return strtolower(pathinfo($target, PATHINFO_EXTENSION)) === 'php' && !preg_match($skipPattern, $target)
            ? [$target]
            : [];
    }
    if (!is_dir($target)) {
        fwrite(STDERR, "[audit] 路径不存在，已跳过：{$target}\n");
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $item) use ($skipPattern): bool {
                return !preg_match($skipPattern, $item->getPathname());
            }
        )
    );
    foreach ($iterator as $item) {
        if ($item->isFile() && strtolower($item->getExtension()) === 'php') {
            $files[] = $item->getPathname();
        }
    }
    return $files;
}

function codeWithoutComments(string $code): string
{
    $result = '';
    foreach (token_get_all($code) as $token) {
        if (is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            $result .= str_repeat("\n", substr_count($token[1], "\n"));
            continue;
        }
        $result .= is_array($token) ? $token[1] : $token;
    }
    return $result;
}

$files = [];
foreach ($targets as $target) {
    $files = array_merge($files, collectPhpFiles($target, $skipPattern));
}
$files = array_values(array_unique($files));
sort($files);

$findings = 0;
foreach ($files as $file) {
    $contents = file_get_contents($file);
    if ($contents === false) {
        fwrite(STDERR, "[audit] 无法读取：{$file}\n");
        ++$findings;
        continue;
    }
    $code = codeWithoutComments($contents);
    foreach ($rules as [$id, $pattern, $message]) {
        if (!preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
            continue;
        }
        foreach ($matches[0] as [$match, $offset]) {
            $line = substr_count(substr($code, 0, $offset), "\n") + 1;
            $display = str_replace('\\', '/', $file);
            fwrite(STDOUT, "{$display}:{$line} [{$id}] " . trim(preg_replace('/\s+/', ' ', $match)) . "\n");
            fwrite(STDOUT, "  {$message}\n");
            ++$findings;
        }
    }
}

if ($findings > 0) {
    fwrite(STDERR, "[audit] 发现 {$findings} 个需要复核的框架能力绕过点。测试目录已自动忽略。\n");
    exit(1);
}

fwrite(STDOUT, '[audit] 未发现框架能力绕过写法。扫描 PHP 文件：' . count($files) . "\n");
