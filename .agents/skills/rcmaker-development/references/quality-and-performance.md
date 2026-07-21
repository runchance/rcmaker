# Quality And Performance

## Audit Persistent-Memory State

Before finishing a change, ask:

1. Can this value outlive the current request or job?
2. Is it mutable, user-specific, tenant-specific, connection-specific, or permission-specific?
3. Can another request, coroutine, timer, callback, or worker observe it?
4. Is cleanup guaranteed on success, exception, early return, disconnect, and cancellation?
5. Is process-local storage being mistaken for shared state?

Keep immutable configuration and precomputed metadata long-lived. Keep request-owned state local or in the framework request context, and clear reusable containers deterministically.

## Request Lifecycle Guarantees

- Swoole requests need fresh framework request/response wrappers even when `$fd` repeats.
- Workerman wrapper reuse is safe only while `set()` and `unset()` reset the native request, native response, attributes, and other request-owned caches.
- Do not close over a request object from a timer, deferred callback, queue payload, or long-lived promise.
- Verify lifecycle cleanup with sequential keep-alive requests carrying different headers, cookies, parameters and authenticated identities.

## Protect The Hot Path

Prefer startup-time work for immutable data:

- Parse stable configuration and routes once per worker lifecycle.
- Cache immutable reflection or metadata only with bounded, deterministic keys.
- Avoid per-request directory scans, class-map generation, environment-file parsing and repeated serializer construction.
- Cache logging feature flags at worker startup when configuration is not expected to hot-change.
- Avoid unnecessary request/response copies and conversions.
- Keep logging asynchronous, sampled, or severity-gated where the existing architecture supports it.

Do not optimize by weakening correctness or state isolation. Measure before and after one change at a time.

## Static Performance

- Use static preload for small, hot, stable assets when memory cost is justified.
- Set preload extensions and time limits deliberately.
- Use precompressed output only when content negotiation and cache headers remain correct.
- Keep `static_only` applications isolated when they should never execute dynamic controllers.
- Recheck memory per worker and startup time after changing preload scope.

## Security Boundaries

- Validate and normalize route, query, body, header, cookie and uploaded-file input.
- Authorize resource access independently of input validity.
- Prevent traversal by resolving paths under an allow-listed root.
- Do not expose stack traces, credentials, tokens, environment values or internal paths in production responses.
- Use constant-time or maintained library primitives for security-sensitive comparisons and cryptography.
- Treat source encryption as code protection, not as a substitute for secret management or authorization.

## Verification Ladder

Run the smallest applicable layers and expand with risk:

1. `php -l` on changed PHP files.
2. Focused unit or direct test scripts.
3. Route/controller integration with realistic request data.
4. Keep-alive isolation tests across multiple requests.
5. Process ownership tests for main and bound APP groups.
6. Workerman and Swoole checks when shared lifecycle code changes.
7. Packaging smoke test when build or filesystem assumptions change.

`tests/WorkerAppProcessTest.php` is a useful focused test for APP process group behavior when present.

## Benchmark Fairly

When evaluating RPS or latency:

- Use the same machine, PHP version, event loop, process count, response body, headers and keep-alive behavior.
- Warm both systems, alternate run order, run several samples, and report median plus tail latency.
- Check socket errors, timeouts, CPU saturation, memory, context switches and response correctness.
- Separate framework no-op throughput from routing, middleware, JSON, database and realistic business workloads.
- A higher single no-op RPS result is evidence for that scenario, not proof of universal application performance.

## Review Checklist

- No request state leaks across keep-alive requests or coroutines.
- No ambiguous application ownership or shared-port Host routing.
- No new unbounded cache, queue, query, file read, or log volume.
- Failure paths clean up transactions, locks, files and request context.
- Tests cover the changed contract and process boundary.
- Performance claims include reproducible commands and error counts.
