# Runtime And Processes

## Choose The Execution Boundary

| Need | Use |
| --- | --- |
| Normal HTTP application with shared APP resources | Main APP group in `config/worker.php` |
| HTTP application with its own port, process count, restart or quota | `type=app` group in `config/process.php` |
| Queue, timer, daemon, consumer or non-HTTP protocol | Ordinary custom process with a handler |
| Static-only site isolated from dynamic traffic | Static app bound to a dedicated APP process group |

An APP process group inherits the normal rcmaker HTTP pipeline: application selection, routes, middleware, controllers, views, sessions, static handling, exceptions and configured bootstrap behavior.

## Bind Applications To APP Groups

Application configuration:

```php
// config/app.php
return [
    'api' => [
        'domains' => ['api.example.com'],
        'bind_process' => 'RC_APP_API',
    ],
];
```

Matching process group:

```php
// config/process.php
return [
    'RC_APP_API' => [
        'type' => 'app',
        'listen' => 'http://0.0.0.0:8081',
        'count' => 4,
        'max_request' => (int) rcEnv('API_MAX_REQUEST', 100000),
        'memory_limit' => '512M',
    ],
];
```

Do not add a custom `handler` to a `type=app` group. Use the exact option names already supported by the current framework source.

Binding rules:

- No `bind_process`: the app belongs to the main APP group.
- `bind_process` set: the app belongs only to the named, enabled APP group.
- Missing, disabled, or stopped group: the bound app does not take effect.
- Several applications may bind to one APP group and share its worker pool and limits.
- One application's domain must not be ambiguously owned by multiple groups.
- FPM mode does not launch process groups from `config/process.php`.

## Do Not Route With Shared `reusePort`

Do not configure the main APP group and an independent APP group to listen on the same IP and port with `reusePort`, then expect Host-based routing to separate them. The operating system chooses a socket before HTTP parsing, so a request can land in a group that does not own the requested app and return 404.

Use unique internal ports and route at a reverse proxy:

```nginx
server {
    server_name api.example.com;
    location / {
        proxy_pass http://127.0.0.1:8081;
    }
}
```

## Preserve Request Scope

- Swoole: construct fresh rcmaker `Request` and `Response` wrappers for every request. Never reuse them by `$fd`; the same descriptor may carry multiple requests.
- Workerman: wrappers may be reused by connection ID only when lifecycle methods clear the native request, response and attributes on every request.
- Do not retain request-owned values in controllers, middleware, services, static properties, globals, timers or callbacks beyond request completion.
- Workerman APP groups are supported without assuming coroutine semantics.
- Swoole coroutine APP groups require coroutine-safe dependencies and context isolation.
- Swoole non-coroutine independent APP groups are not a supported substitute when the framework rejects that combination.

## Configure Static Applications

```php
'website' => [
    'domains' => ['www.example.com'],
    'bind_process' => 'RC_STATIC',
    'document_root' => 'website',
    'enable_static_file' => true,
    'enable_static_preload' => true,
    'static_only' => true,
],
```

`static_only=true` means a static miss returns 404 and does not fall through to dynamic routing. Confirm that `public/website` exists and that the requested Host and port reach `RC_STATIC`.

Useful controls include `static_preload_extensions`, `static_preload_time_limit`, and `enable_static_gzip`. Bound apps are preloaded according to process ownership.

| Platform | Preload behavior |
| --- | --- |
| Linux | Preload globally before fork; child workers inherit memory pages through copy-on-write |
| Windows | No fork sharing; preload separately for the APP process group that owns the application |

Preload only bounded, frequently requested assets. Large or mutable files may consume memory without improving useful throughput.

## Restart And Observe

- Configuration and bootstrap changes require the affected process group to restart.
- Independent APP groups can have separate ports, counts, max-request recycling and memory limits.
- Validate ownership using both expected success and expected 404 cases across main and custom ports.
- Review startup output for duplicate listeners, failed process groups, preload time limits and unexpected exits.

See `official/doc/md/app-process.md`, `apps.md`, `process.md`, `static.md`, `cli.md`, and `fpm.md` for current user-facing details.
