# Built-in Components

Prefer a maintained rcmaker component or an already-installed Composer package when it satisfies the requirement. Do not recreate infrastructure in a controller.

## Map Requirements To Components

| Requirement | Component or helper | Documentation |
| --- | --- | --- |
| Input validation | `validator()`, `VD()` | `official/doc/md/module/validation.md` |
| Conventional CRUD and form processing | `autoForm($request, $vars)`, `$request->AF($vars)` | `official/doc/md/module/autoform.md` |
| Lightweight data storage | SDB | `official/doc/md/db/sdb.md` |
| SQL access | `DB()`, `database()`, Model | `official/doc/md/db/` |
| Redis and application cache | Redis, Cache | `official/doc/md/db/redis.md`, `official/doc/md/module/cache.md` |
| Pagination and rate limiting | Paginator, Throttler | `official/doc/md/module/paginator.md`, `official/doc/md/module/throttler.md` |
| Authentication primitives | Token, Session, Cookie | `official/doc/md/module/token.md`, `official/doc/md/session.md`, `official/doc/md/cookie.md` |
| Background work | Queue | `official/doc/md/queue.md` |
| Human verification and notifications | Captcha, SMS, Mailer | `official/doc/md/module/captcha.md`, `official/doc/md/module/sms.md`, `official/doc/md/module/mailer.md` |
| Files and documents | Excel, PDF, QR code | `official/doc/md/module/excel.md`, `official/doc/md/module/pdf.md`, `official/doc/md/module/qrcode.md` |
| HTTP integration | Curl | `official/doc/md/module/curl.md` |
| Profiling | StopWatch | `official/doc/md/module/stopWatch.md` |

Confirm the exact local filenames before linking or editing because documentation casing may vary.

## Select A Database Layer

Use the layer already established by the application. rcmaker can integrate its own database/model APIs and adapters such as Medoo, ThinkPHP database, or Laravel database. Before using an adapter:

1. Confirm its Composer package and version in `composer.json` and `composer.lock`.
2. Confirm its bootstrap class is loaded for every process group that uses it.
3. Reuse current connection and transaction configuration.
4. Check long-running connection recovery and coroutine compatibility for the selected engine.
5. Keep transactions inside one request or job boundary; always roll back on failure.

Avoid issuing unbounded queries, N+1 loops, or reconnecting on every request. Use parameter binding and allow-list any dynamic identifier that cannot be bound.

## Validation And Business Logic

- Validate external input before database, filesystem, process, template, or network use.
- Use AutoForm for conventional, well-understood CRUD flows. Use explicit services for complex authorization, state transitions, side effects, or multi-resource transactions.
- Keep controllers focused on transport: parse, authorize, invoke, map response.
- Put reusable domain decisions in `support/service` or the application's established service layer.
- Keep validation and response errors consistent with adjacent endpoints.

## State And Caching

- A PHP array or static property is process-local, not shared across workers.
- Use Redis, database, or another shared backend for cross-worker locks, counters, sessions, rate limits, and invalidation.
- Add cache keys that include tenant, application, locale, user, or permission dimensions when those dimensions affect the result.
- Give caches an explicit lifetime and invalidation owner.
- Never put mutable request or user data into a global component instance.

## Background Work

Use Queue or a custom process when work should not extend request latency. Design jobs to be idempotent, retryable, observable, and bounded. Carry identifiers in a job payload; reload authoritative data in the worker instead of serializing live request objects or service containers.

## Runtime Compatibility

For Swoole coroutine mode, verify every selected database driver, Redis client, Curl/HTTP client, filesystem call, and third-party SDK. A package being installable through Composer does not make it coroutine-safe. Keep blocking or unsafe dependencies in a compatible process boundary when replacement is not justified.

Do not leave StopWatch, verbose SQL tracing, request dumps, or debug logging enabled on the production hot path unless the task explicitly requires sampled diagnostics.
