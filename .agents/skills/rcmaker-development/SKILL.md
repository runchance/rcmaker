---
name: rcmaker-development
description: Develop, extend, debug, optimize, test, and package applications built with the rcmaker PHP framework. Use when working on rcmaker controllers, routes, request/response handling, middleware, databases, built-in components, multi-application configuration, type=app process groups, Workerman or Swoole CLI runtimes, custom processes, static applications and preload, concurrency or state isolation, source protection, buildBin, or x86_64/AArch64 delivery.
---

# rcmaker Development

Use rcmaker's conventions and built-in capabilities to deliver PHP services quickly without weakening persistent-memory safety, process isolation, or deployment portability.

## Establish Context

1. Treat the current repository as the project root unless the user names another path.
2. Inspect `composer.json`, `config/`, the target application under `apps/`, related `support/` code, and focused tests before editing.
3. Treat installed framework source and current project configuration as authoritative for runtime behavior. Use documentation to understand intended usage, then reconcile it with code.
4. Keep business code in project-owned paths. Edit `vendor/runchance/rcmaker-framework` only when the task explicitly changes the framework itself.
5. Preserve local naming, response shape, database layer, and error-handling conventions. Do not introduce a parallel architecture for a small feature.

## Route The Task

Read only the references needed for the request:

| Task | Reference |
| --- | --- |
| Controllers, routes, middleware, request/response, project layout | [project-workflow.md](references/project-workflow.md) |
| Multiple applications, APP process groups, Workerman/Swoole, static preload | [runtime-and-processes.md](references/runtime-and-processes.md) |
| Database, validation, AutoForm, cache, queue, session and other built-ins | [components.md](references/components.md) |
| Persistent-memory safety, security, testing and RPS work | [quality-and-performance.md](references/quality-and-performance.md) |
| Binary packaging, encryption, x86_64 and AArch64 delivery | [delivery.md](references/delivery.md) |

## Execute The Workflow

1. Translate the request into an application, route, input contract, output contract, state boundary, and deployment boundary.
2. Search the repository for an existing implementation and reuse rcmaker helpers or installed Composer packages when they fit.
3. Choose the execution boundary deliberately:
   - Use the main APP group for ordinary HTTP applications.
   - Use a `type=app` process group when an application needs an independent port, process count, restart lifecycle, bootstrap, or resource quota.
   - Use an ordinary custom process for consumers, timers, daemons, or protocol services that do not need the full HTTP APP pipeline.
4. Implement the smallest complete vertical slice: configuration, controller or service, persistence, response, and focused tests.
5. Audit every static property, singleton, global, cache, and reusable object for cross-request or cross-coroutine state leakage.
6. Run syntax checks and the narrowest relevant tests. Expand testing when shared routing, request lifecycle, process ownership, or packaging changes.
7. State whether restart, rebuild, reverse-proxy, environment, or architecture-specific deployment work is required.

## Preserve Runtime Invariants

- Keep the request object as the first controller argument. Use its request and response adapters instead of reaching into engine-specific objects unnecessarily.
- Never retain request, response, connection, session, user, or uploaded-file data in static or global state after a request completes.
- In Swoole mode, create fresh rcmaker request and response wrappers for every request. Never key reusable request wrappers by `$fd`; keep-alive and multiplexed work can reuse a descriptor.
- In Workerman mode, wrapper reuse is acceptable only because `set()` and `unset()` clear the native request, response, and attributes for each request.
- Do not assume Workerman coroutine behavior. In Swoole coroutine mode, verify that every database, Redis, HTTP, filesystem, and third-party dependency is coroutine-safe.
- Read deployable values through existing configuration and `rcEnv()` patterns. Do not bake ports, credentials, paths, or process counts into business code.
- An app with `bind_process` belongs only to that named, enabled `type=app` group. If the group is absent or stopped, the app is intentionally unavailable.
- Do not use `reusePort` to split Host-based applications across process groups sharing one IP and port. Kernel socket dispatch occurs before rcmaker can inspect the Host header. Give groups distinct internal ports and route with a reverse proxy.
- Respect static preload ownership: Linux can preload globally before fork and inherit pages with copy-on-write; Windows preloads separately for each bound process group.
- Protect the hot path. Avoid per-request filesystem scans, repeated configuration parsing, unconditional logging, reflection, and allocations that can be moved safely to worker startup.

## Definition Of Done

- The change follows existing rcmaker layout and APIs.
- Changed PHP files pass `php -l`; focused tests pass or missing coverage is stated.
- Process, route, and environment changes include the required restart behavior.
- Concurrent requests cannot observe another request's mutable state.
- Inputs, paths, uploads, authorization, and external data are validated at their trust boundary.
- Delivery instructions distinguish runtime executables, micro SFX files, encryption tools, and CPU architecture.
