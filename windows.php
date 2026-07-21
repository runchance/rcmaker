<?php
declare(strict_types=1);

define('IS_SCRIPT', 1);
define('ROOT_PATH', dirname(__FILE__));

chdir(__DIR__);
require_once __DIR__ . '/vendor/autoload.php';

use RC\Config;
use RC\Container;
use RC\Controller;
use RC\Stopwatch;
use RC\Worker as RcmakerWorker;
use Workerman\Connection\TcpConnection;
use Workerman\Timer;
use Workerman\Worker;

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This script must run in CLI mode.\n");
	exit(1);
}

$command = strtolower((string)($argv[1] ?? 'start'));

if ($command === 'app' && strtolower((string)($argv[2] ?? '')) === 'start') {
	windows_start_app();
	return;
}

if ($command === 'process' && strtolower((string)($argv[2] ?? '')) === 'start') {
	windows_start_process((string)($argv[3] ?? ''));
	return;
}

windows_start_master();

function windows_should_print_banner(): bool
{
	return Config::get('app', 'cli_banner') !== false;
}

function windows_start_master(): never
{
	$resources = [];
	$commands = [];
	$monitorProcessNames = [];

	if (Config::get('app', 'start_app')) {
		$commands[] = ['name' => 'app', 'mode' => 'app'];
	}

	foreach (windows_process_specs() as $name => $spec) {
		$commands[] = ['name' => $name, 'mode' => 'process'];
		if (windows_is_reload_monitor($spec)) {
			$monitorProcessNames[$name] = true;
		}
	}

	if (!$commands) {
		fwrite(STDOUT, "No Windows start targets found.\n");
		exit(0);
	}

	if (windows_should_print_banner()) {
		windows_print_banner();
	}

	foreach ($commands as $command) {
		$commandLine = windows_child_command($command['mode'], $command['name']);
		$resource = windows_open_process($commandLine);
		$resources[$command['name']] = $resource;
	}

	fwrite(STDOUT, "\n");
	while (true) {
		sleep(1);
		foreach ($resources as $name => $resource) {
			$status = proc_get_status($resource);
			if (!$status['running']) {
				$exitCode = $status['exitcode'] ?? -1;
				if (isset($monitorProcessNames[$name]) && $exitCode === 0) {
					fwrite(STDOUT, $name . " detected file changes, restarting windows workers...\n");
					windows_restart_resources($resources, $commands);
					continue 2;
				}
				fwrite(STDOUT, $name . " stopped unexpectedly.\n");
				exit(1);
			}
		}
	}
}

function windows_start_app(): void
{
	windows_prepare_runtime();
	Config::get('app', null, true);
	windows_apply_error_types();
	$config = Config::get('worker', null, true);

	if (!$config) {
		fwrite(STDERR, "no workerman config found!\n");
		exit(1);
	}

	windows_setup_worker_logging($config);
	TcpConnection::$defaultMaxPackageSize = $config['max_package_size'] ?? 10 * 1024 * 1024;

	$worker = static_worker_create($config);
	windows_sync_rcmaker_worker_state('workerman', $worker, $config['max_request'] ?? 1000000);
	$worker->onWorkerReload = function ($worker): void {
	};
	RcmakerWorker::configureWorkermanAppWorker($worker);
	windows_attach_static_preload($worker);

	Stopwatch::$_framework = stopwatch('__frame__');
	Worker::runAll();
}

function windows_start_process(string $processName): void
{
	if ($processName === '') {
		fwrite(STDERR, "Missing process name.\n");
		exit(1);
	}

	windows_prepare_runtime();
	Config::get('app', null, true);
	windows_apply_error_types();
	$processConfig = Config::get('process', $processName, true);

	if (!$processConfig) {
		$queueConfig = Config::get('queue', null, true) ?: [];
		if (($queueConfig['enable'] ?? false) && isset($queueConfig['consumer_process'][$processName])) {
			$processConfig = $queueConfig['consumer_process'][$processName];
		}
	}

	if (!$processConfig) {
		$cliLogEnabled = Config::get('app', 'cli_log');
		if ($cliLogEnabled && $processName === 'RCmaker_logger') {
			$workerConfig = Config::get('worker', null, true) ?: [];
			$processConfig = [
				'handler' => RC\Helper\Process\Logger::class,
				'name' => 'RCmaker_logger',
				'listen' => $workerConfig['logger_listen'] ?? 'Text://127.0.0.1:8689',
				'count' => 1,
				'reusePort' => true,
			];
		}
	}

	$isAppProcess = RcmakerWorker::isAppProcessConfig($processConfig);
	if (!$processConfig || (!$isAppProcess && !isset($processConfig['handler']))) {
		fwrite(STDERR, "process error: process {$processName} not found or handler missing!\n");
		exit(1);
	}

	windows_setup_process_logging($processName);
	if ($isAppProcess) {
		$processConfig['name'] = $processConfig['name'] ?? $processName;
		$runtimeConfig = RcmakerWorker::mergeAppProcessConfig('workerman', $processConfig, true);
		if (empty($runtimeConfig['listen'])) {
			fwrite(STDERR, "process error: app process {$processName} listen missing!\n");
			exit(1);
		}
		$processWorker = static_worker_create($runtimeConfig);
		foreach (['reloadable', 'protocol'] as $property) {
			if (isset($runtimeConfig[$property])) {
				$processWorker->$property = windows_normalize_worker_property($property, $runtimeConfig[$property]);
			}
		}
		if (($runtimeConfig['ssl'] ?? false) === true) {
			$processWorker->transport = 'ssl';
		}
		windows_sync_rcmaker_worker_state('workerman', $processWorker, (int)($runtimeConfig['max_request'] ?? 1000000));
		RcmakerWorker::configureWorkermanAppWorker($processWorker, $processConfig, $processName);
		windows_attach_static_preload($processWorker, $processName);
		Stopwatch::$_framework = stopwatch('__frame__');
		Worker::runAll();
		return;
	}

	$processWorker = new Worker($processConfig['listen'] ?? null, $processConfig['context'] ?? []);
	$processWorker->name = $processName;
	windows_sync_rcmaker_worker_state('workerman', $processWorker);

	foreach (['count', 'user', 'group', 'reloadable', 'reusePort', 'transport', 'protocol'] as $property) {
		if (isset($processConfig[$property])) {
			$processWorker->$property = windows_normalize_worker_property($property, $processConfig[$property]);
		}
	}

	if (($processConfig['ssl'] ?? false) === true) {
		$processWorker->transport = 'ssl';
	}

	if (class_exists($processConfig['handler'])) {
		$class = $processConfig['handler'];
	} else {
		$classFile = BASE_PATH . '/support/process/' . $processConfig['handler'] . '.php';
		$class = 'support\\process\\' . $processConfig['handler'];
		if (!Container::loadClass($classFile, $class)) {
			fwrite(STDERR, "process error: class {$processConfig['handler']} not exists!\n");
			exit(1);
		}
	}

	$processWorker->onWorkerStart = function ($processWorker) use ($processConfig, $class): void {
		foreach (($processConfig['bootstrap'] ?? []) as $bootstrap) {
			$bootstrap::start();
		}
		foreach (($processConfig['autoload'] ?? []) as $file) {
			include_once $file;
		}

		if ($timezone = ($processConfig['default_timezone'] ?? Config::get('app', 'default_timezone'))) {
			date_default_timezone_set($timezone);
		}

		$instance = Container::make($class, array_merge([
			'type' => 'workerman',
			'worker' => $processWorker,
			'timer' => Timer::class,
		], $processConfig['constructor'] ?? []));
		worker_bind($processWorker, $instance);
	};

	Stopwatch::$_framework = stopwatch('__frame__');
	Worker::runAll();
}

function windows_process_specs(): array
{
	$processes = Config::get('process', null, true, []) ?: [];

	if (Config::get('app', 'cli_log')) {
		$workerConfig = Config::get('worker', null, true) ?: [];
		$processes['RCmaker_logger'] = [
			'handler' => RC\Helper\Process\Logger::class,
			'name' => 'RCmaker_logger',
			'listen' => $workerConfig['logger_listen'] ?? 'Text://127.0.0.1:8689',
			'count' => 1,
			'reusePort' => true,
		];
	}

	$queueConfig = Config::get('queue', null, true) ?: [];
	if (($queueConfig['enable'] ?? false) && isset($queueConfig['consumer_process']) && is_array($queueConfig['consumer_process'])) {
		$processes = array_merge($processes, $queueConfig['consumer_process']);
	}

	$processes = array_filter($processes, static function ($config): bool {
		return is_array($config) && (isset($config['handler']) || RcmakerWorker::isAppProcessConfig($config));
	});

	return $processes;
}

function windows_is_reload_monitor(array $spec): bool
{
	return ($spec['handler'] ?? null) === RC\Helper\Process\FileMonitor::class;
}

function static_worker_create(array $config): Worker
{
	$worker = new Worker($config['listen'], $config['context'] ?? []);

	foreach (['name', 'count', 'user', 'group', 'reusePort', 'transport'] as $property) {
		if (isset($config[$property])) {
			if ($property === 'count') {
				$config[$property] = $config[$property] ?? cpu_count();
			}
			$worker->$property = windows_normalize_worker_property($property, $config[$property]);
		}
	}

	$worker->reusePort = true;

	return $worker;
}

function windows_normalize_worker_property(string $property, $value)
{
	switch ($property) {
		case 'count':
			return max(1, (int)$value);
		case 'reloadable':
		case 'reusePort':
			if (is_bool($value)) {
				return $value;
			}
			if (is_string($value)) {
				$value = strtolower(trim($value));
				return !in_array($value, ['', '0', 'false', 'off', 'no'], true);
			}
			return (bool)$value;
		case 'name':
		case 'user':
		case 'group':
		case 'transport':
		case 'protocol':
			return (string)$value;
		default:
			return $value;
	}
}

function windows_open_process(string $command)
{
	$descriptorspec = [STDIN, STDOUT, STDOUT];
	$resource = proc_open($command, $descriptorspec, $pipes, null, null, ['bypass_shell' => true]);
	if (!$resource) {
		fwrite(STDERR, "Can not execute {$command}\n");
		exit(1);
	}
	return $resource;
}

function windows_restart_resources(array &$resources, array $commands): void
{
	foreach ($resources as $resource) {
		$status = proc_get_status($resource);
		if (!empty($status['running']) && isset($status['pid']) && $status['pid'] > 0) {
			shell_exec('taskkill /F /T /PID ' . (int)$status['pid']);
		}
		proc_close($resource);
	}
	$resources = [];
	if (windows_should_print_banner()) {
		windows_print_banner();
	}
	foreach ($commands as $command) {
		$commandLine = windows_child_command($command['mode'], $command['name']);
		$resources[$command['name']] = windows_open_process($commandLine);
	}
	fwrite(STDOUT, "\n");
}

function windows_child_command(string $mode, string $name): string
{
	$command = '"' . PHP_BINARY . '" "' . __FILE__ . '" ' . $mode . ' start';
	if ($mode === 'process') {
		$command .= ' ' . escapeshellarg($name);
	}
	if (windows_should_print_banner()) {
		$command .= ' -q';
	}
	return $command;
}

function windows_print_banner(): void
{
	fwrite(STDOUT, "\n");
	fwrite(STDOUT, "----------------------------------------------- RCMAKER ------------------------------------------------\r\n");
	fwrite(STDOUT, 'Rcmaker version:' . windows_rcmaker_version() . '          PHP version:' . PHP_VERSION . "\r\n");
	fwrite(STDOUT, 'Workerman version:' . Worker::VERSION . '         Event-Loop:' . windows_workerman_event_loop_name() . "\r\n");
	fwrite(STDOUT, "----------------------------------------------- WORKERS ------------------------------------------------\r\n");
	fwrite(STDOUT, "worker                                          listen                              processes   status\r\n");
}

function windows_rcmaker_version(): string
{
	if (class_exists('\\Composer\\InstalledVersions') && \Composer\InstalledVersions::isInstalled('runchance/rcmaker-framework')) {
		$version = \Composer\InstalledVersions::getPrettyVersion('runchance/rcmaker-framework') ?: '';
		return str_replace('+no-version-set', '', $version);
	}
	return defined('VER') ? (string)VER : 'unknown';
}

function windows_resolve_workerman_event_loop_name(): string
{
	if (Worker::$eventLoopClass) {
		return Worker::$eventLoopClass;
	}
	if (extension_loaded('event')) {
		return '\\Workerman\\Events\\Event';
	}
	if (extension_loaded('libevent')) {
		return '\\Workerman\\Events\\Libevent';
	}
	return '\\Workerman\\Events\\Select';
}

function windows_workerman_event_loop_name(): string
{
	return windows_resolve_workerman_event_loop_name();
}

function windows_prepare_runtime(): void
{
	$paths = [
		runtime_path(),
		runtime_path() . DIRECTORY_SEPARATOR . 'logs',
		runtime_path() . DIRECTORY_SEPARATOR . 'views',
		runtime_path() . DIRECTORY_SEPARATOR . 'windows',
	];

	foreach ($paths as $path) {
		if (!is_dir($path)) {
			mkdir($path, 0777, true);
		}
	}
}

function windows_setup_worker_logging(array $config): void
{
	Worker::$eventLoopClass = windows_resolve_workerman_event_loop_name();
	Worker::$onMasterReload = function (): void {
	};
	Worker::$pidFile = $config['pid_file'];
	Worker::$stdoutFile = $config['stdout_file'];
	Worker::$logFile = $config['log_file'];
	if (isset($config['status_file']) && property_exists(Worker::class, 'statusFile')) {
		Worker::$statusFile = $config['status_file'];
	}
}

function windows_setup_process_logging(string $name): void
{
	$base = runtime_path() . DIRECTORY_SEPARATOR . 'windows';
	Worker::$pidFile = $base . DIRECTORY_SEPARATOR . $name . '.pid';
	Worker::$stdoutFile = runtime_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $name . '.stdout.log';
	Worker::$logFile = runtime_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $name . '.log';
	if (property_exists(Worker::class, 'statusFile')) {
		Worker::$statusFile = $base . DIRECTORY_SEPARATOR . $name . '.status.log';
	}
}

function windows_should_warmup_static_preload(): bool
{
	global $argv;
	return isset($argv[2]) && strtolower((string)$argv[2]) === 'start';
}

function windows_attach_static_preload(Worker $worker, ?string $processName = null): void
{
	if (!windows_should_warmup_static_preload()) {
		return;
	}
	$onWorkerStart = $worker->onWorkerStart;
	$worker->onWorkerStart = static function (Worker $worker) use ($onWorkerStart, $processName): void {
		Controller::warmupStaticPreloadForProcess($processName);
		if ($onWorkerStart) {
			$onWorkerStart($worker);
		}
	};
}

function windows_apply_error_types(): void
{
	error_reporting(Config::get('app', 'error_types') ?? (E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED));
}

function windows_sync_rcmaker_worker_state(string $frame, ?Worker $worker = null, ?int $maxRequestCount = null): void
{
	$reflection = new ReflectionClass(RcmakerWorker::class);

	$frameProperty = $reflection->getProperty('_frame');
	$frameProperty->setValue(null, $frame);

	if ($worker !== null) {
		$workerProperty = $reflection->getProperty('_worker');
		$workerProperty->setValue(null, $worker);
	}

	if ($maxRequestCount !== null) {
		$maxRequestProperty = $reflection->getProperty('_maxRequestCount');
		$maxRequestProperty->setValue(null, $maxRequestCount);
	}
}
