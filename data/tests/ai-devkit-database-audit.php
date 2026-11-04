<?php

declare(strict_types=1);

/**
 * Regression checks for the DevKit's database lifecycle audit.
 * Run from the project with: php data/tests/ai-devkit-database-audit.php
 * Fixtures are scanned as text; no application or database is started.
 */

$root = dirname(__DIR__, 2);
$audit = $root . '/agent/.agents/skills/rcmaker-development/scripts/audit-framework-usage.php';
$fixtureDirectory = $root . '/data/generated/ai-devkit-audit-' . bin2hex(random_bytes(8));
$fixturePath = $fixtureDirectory . '/model.php';

/**
 * Execute the PHP audit against one fixture without a shell.
 *
 * @param string $audit Absolute path to the audit program.
 * @param string $target File or directory to scan.
 * @return array{int, string} Exit code and combined diagnostic output.
 * @throws RuntimeException When the child process cannot be created.
 */
function runAudit(string $audit, string $target): array
{
    $process = proc_open(
        [PHP_BINARY, $audit, $target],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot launch the PHP audit.');
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [proc_close($process), $output];
}

$cases = [
    'bare adapter' => ["<?php\nMedoo::start();\n", 1, 2],
    'fully qualified adapter' => ["<?php\n\\RC\\Helper\\Db\\Medoo::start();\n", 1, 2],
    'qualified adapter' => ["<?php\nRC\\Helper\\Db\\Think::start();\n", 1, 2],
    'Laravel adapter' => ["<?php\nLaravel::start();\n", 1, 2],
    'import alias' => ["<?php\nuse RC\\Helper\\Db\\Medoo as DatabaseAdapter;\nDatabaseAdapter::start();\n", 1, 3],
    'absolute import alias' => ["<?php\nuse \\RC\\Helper\\Db\\Laravel as Engine;\nEngine::start();\n", 1, 3],
    'case-insensitive alias' => ["<?php\nuse RC\\Helper\\Db\\Think as Engine;\nengine::START();\n", 1, 3],
    'wrapper fallback' => ["<?php\nfunction ensureDatabase() {\n    Medoo::start();\n}\n", 1, 3],
    'bootstrap configuration' => ["<?php\nreturn [RC\\Helper\\Db\\Medoo::class];\n", 0, null],
    'comments and normal query' => ["<?php\n// Medoo::start();\n/* Laravel::start(); */\nSDB()->table('users')->select();\n", 0, null],
    'unrelated namespaced class' => ["<?php\nOther\\Medoo::start();\n", 0, null],
    'ordinary instance startup' => ["<?php\n\$worker->start();\n", 0, null],
];

if (!is_file($audit)) {
    throw new RuntimeException('DevKit audit script is missing: ' . $audit);
}
if (!mkdir($fixtureDirectory, 0777, true)) {
    throw new RuntimeException('Cannot create the isolated fixture directory.');
}

$passed = 0;
try {
    foreach ($cases as $name => [$source, $expectedFindings, $expectedLine]) {
        if (file_put_contents($fixturePath, $source) === false) {
            throw new RuntimeException('Cannot write fixture: ' . $name);
        }

        [$status, $output] = runAudit($audit, $fixturePath);
        $expectedStatus = $expectedFindings > 0 ? 1 : 0;
        if ($status !== $expectedStatus
            || substr_count($output, '[manual-db-bootstrap]') !== $expectedFindings
            || ($expectedLine !== null && !str_contains($output, ':' . $expectedLine . ' [manual-db-bootstrap]'))
        ) {
            throw new RuntimeException($name . ' failed (exit ' . $status . "):\n" . $output);
        }
        fwrite(STDOUT, '[PASS] ' . $name . "\n");
        ++$passed;
    }

    // A native PHP client in an isolated PHP test must retain its existing exception.
    $testDirectory = $fixtureDirectory . '/tests';
    mkdir($testDirectory);
    file_put_contents($testDirectory . '/native.php', "<?php\nnew PDO('sqlite::memory:');\n");
    [$status, $output] = runAudit($audit, $testDirectory);
    if ($status !== 0 || str_contains($output, '[native-database]')) {
        throw new RuntimeException("PHP test exception failed:\n" . $output);
    }
    fwrite(STDOUT, "[PASS] isolated native PHP test exception\n");
    ++$passed;
} finally {
    // Remove only these fixtures, never other project data or the parent directories.
    foreach ([$fixturePath, $fixtureDirectory . '/tests/native.php'] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
    if (is_dir($fixtureDirectory . '/tests')) {
        rmdir($fixtureDirectory . '/tests');
    }
    rmdir($fixtureDirectory);
}

fwrite(STDOUT, 'Passed ' . $passed . " PHP regression checks.\n");
