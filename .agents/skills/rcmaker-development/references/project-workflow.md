# Project Workflow

## Source Map

| Path | Responsibility |
| --- | --- |
| `apps/<app>/controller` | HTTP controllers and endpoint orchestration |
| `apps/<app>/model` | Application models |
| `config/app.php` | Applications, domains, static roots and process binding |
| `config/route.php` | Explicit routes and route options |
| `config/worker.php` | Main APP worker settings |
| `config/process.php` | Custom processes and independent APP process groups |
| `config/swoole.php` | Swoole runtime and coroutine settings |
| `support/middleware` | Cross-cutting request middleware |
| `support/service` | Reusable business services |
| `support/process` | Custom process handlers |
| `support/queue` | Queue consumers and jobs |
| `view` | Server-rendered views |
| `public` | Public and static assets |
| `runtime` | Logs, cache and generated runtime data; do not commit business source here |

Inspect nearby files before adding new namespaces or directory layers.

## Add An Endpoint

An automatically addressed controller can remain small and delegate business rules to a service:

```php
<?php

namespace app\api\controller;

use RC\Request;

class user
{
    public function detail(Request $request, int $id)
    {
        $data = validator()->input($request->get(), [
            'expand' => [
                'rule' => 'string',
                'name' => 'expand',
                'required' => false,
            ],
        ]);

        return $request->json([
            'id' => $id,
            'expand' => $data['expand'] ?? null,
        ]);
    }
}
```

Follow the project's actual controller naming and validation conventions if they differ. Route parameters begin after the request argument and are also available through GET data.

For an explicit route, follow the existing `config/route.php` style:

```php
use Rcmaker\Route;

Route::get('/users/{id:\d+}', [app\api\controller\user::class, 'detail']);
```

Use `Route::post()`, `put()`, `patch()`, `delete()`, or the local equivalent to match the HTTP contract. Keep authorization and validation visible near the boundary.

## Request And Response

Prefer the framework request adapter:

- Input: `$request->get()`, `post()`, `rawBody()`, `header()`, `cookie()`, `file()`.
- Metadata: `$request->host()`, `method()`, `path()`, `ip()`.
- Output: `$request->response()`, `json()`, `xml()`, `jsonp()`, `redirect()`, `V()` and download helpers.

Return the framework-supported response type. Preserve project-wide JSON envelopes and HTTP status semantics; do not silently convert every failure into HTTP 200.

## Middleware

Place reusable request policies under `support/middleware` and preserve the standard chain:

```php
public function handle($request, callable $next)
{
    // Reject or enrich the request before delegation when needed.
    $response = $next($request);

    // Apply response-wide policy here when needed.
    return $response;
}
```

Declare properties explicitly. PHP 8.2 and later deprecate undeclared dynamic properties. Keep per-request values on the request or local stack, not on a long-lived middleware instance.

## Configuration

- Put environment-specific values in `.env` and read them through the repository's `rcEnv()` or configuration pattern.
- Cast booleans and integers deliberately; environment values commonly begin as strings.
- Keep defaults operational but never default production secrets.
- Restart relevant workers after changing configuration loaded at process startup.

## Validate The Change

Start narrow:

```powershell
php -l apps/api/controller/user.php
php tests/WorkerAppProcessTest.php
```

Use the project's configured test runner when present. Exercise the endpoint through the same engine and application/process binding used in production when lifecycle behavior matters.

Current documentation lives under `official/doc/md/`. Useful starting points include `directory.md`, `apps.md`, `controller.md`, `route.md`, `request.md`, `response.md`, and `middleware.md`.
