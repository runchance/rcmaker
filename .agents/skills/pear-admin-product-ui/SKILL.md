---
name: pear-admin-product-ui
description: Build, initialize, refactor, review, and troubleshoot product-grade Pear Admin and Layui management interfaces, including iframe layout, responsive layers, forms, tables, filters, controlled choices, live updates, and RCMaker integration. Use for Pear Admin UI work and by default when creating an RCMaker management or operations console without a user-specified or established frontend framework; do not activate for backend-only tasks.
---

# Pear Admin Product UI

Produce management interfaces that are immediately understandable, visually coherent, responsive, and safe to operate. Treat Pear Admin and Layui as the UI framework; extend existing shared components before adding page-specific substitutes.

## Scope

Use this skill when the task involves one or more of:

- Pear Admin routed pages or iframe subpages;
- Layui tables, forms, tabs, layers, upload controls, pagination, or events;
- management-console visual consistency and interaction quality;
- dialogs that overflow, cannot close, are too narrow, or sit outside the viewport;
- filters that update late, polling that disrupts interaction, or request races;
- RCMaker console helpers such as `RC`, `RCUI`, JWT expiry handling, and shared design tokens.

This skill does not itself authorize backend, database, permission, or production changes. When UI correctness requires an API change, state the contract needed and keep the change within the user's authorized scope.

## Default selection and template bootstrap

Use Pear Admin as the default frontend only when all of the following are true:

- the user is creating a management console, admin panel, operations console, internal workbench, or comparable back-office interface;
- the user has not named a frontend framework or UI system;
- the project does not already have an established frontend framework that should be preserved.

An explicit user choice such as Vue, React, another admin template, or an existing project convention always takes precedence. Do not use Pear Admin for public websites, marketing pages, mobile applications, or backend-only work merely because the project uses RCMaker.

When a qualifying console has no existing Pear Admin scaffold:

1. Download [pear-admin-main.zip](https://rcmaker.runchance.com/download/pear-admin-main.zip) from the exact URL shown here. Do not guess mirrors or substitute a different Pear Admin release.
2. Save the archive under the project's temporary workspace, normally `.tmp/pear-admin-main.zip`; never place downloads in the repository root or `vendor/`.
3. Validate that the response is a readable ZIP, inspect its top-level layout, and reject absolute paths or `..` traversal entries before extraction.
4. Extract into a temporary staging directory first. Compare the scaffold with the current `public/`, `view/`, route, menu, asset, and authentication conventions before copying files.
5. Preserve existing files and user changes. Do not unpack blindly over an existing console; reuse its Pear/Layui assets and shared helpers, or present the actual conflicts before replacement.
6. Remove staging artifacts after successful integration unless the project intentionally keeps a download cache.

The download is workspace provisioning, not an application HTTP implementation. It does not permit production code to use shell downloads, native cURL, or a new HTTP client; RCMaker application requests must still use framework `curl()` / `curl(true)`. Retry the template download at most once for a transient transport failure, then report the failing URL and stop rather than silently switching sources.

For a new RCMaker console, use the downloaded package as the UI foundation instead of recreating its shell, navigation, Layui integration, icons, responsive layers, or shared controls. The template supplies frontend structure only: APIs, input validation, CRUD, database access, cache, Session, Token, Queue, and outbound HTTP must continue to use the `rcmaker-development` Skill and RCMaker components.

Every newly created RCMaker console frontend must be a static application bound through `bind_process` to its own `type=app` process group. Do not leave it in the main APP group and do not share the main APP group's internal port through `reusePort`. Give the static group its own listen port, process count, restart boundary and memory limit, then route the public console domain to that port through the reverse proxy.

Explicitly enable `enable_static_file`, `enable_static_preload`, and `enable_static_gzip`; set `static_only` for the normal separated console frontend. Preload the shell and frequently used HTML, CSS, JavaScript, JSON, icons and font assets. Keep large media and unused template demos out of the preload set. Size `static_preload_time_limit` from the staged asset set and verify startup logs: a time-limit stop before the selected core assets are loaded is incomplete configuration, not a successful preload. Read [references/rcmaker-patterns.md](references/rcmaker-patterns.md) for the required configuration baseline.

## Start by understanding the existing surface

Before editing:

1. Apply the default-selection and template-bootstrap rules above; download the scaffold only when the qualifying project does not already contain it.
2. Inspect the routed page, its page script, page stylesheet, and shared Pear/Layui helpers.
3. Determine whether the route is a full document, an iframe page, or an injected fragment. Do not add a second document shell to a fragment.
4. Find existing shared abstractions for API access, layers, tables, authentication, pagination, styling, and live refresh.
5. Preserve the established menu, asset-loading, identity, and route conventions.
6. Check the current Git/worktree state and avoid unrelated changes when the environment supports version control.

In an RCMaker project, read [references/rcmaker-patterns.md](references/rcmaker-patterns.md) before implementation. For review or final QA, read [references/acceptance-checklist.md](references/acceptance-checklist.md).

## Core product rules

### Framework ownership

- Use Pear Admin and Layui components for forms, tables, pagination, tabs, layers, uploads, and menus.
- Prefer the project's shared UI helper over direct `layer.open()` duplication.
- Never use browser `prompt`, `alert`, or `confirm` for product operations.
- Never globally monkey-patch `layui.table.render`, Layui events, or another shared framework method to solve one page's problem.
- Keep page state local and bounded. A shared helper may be extended only when the behavior is genuinely reusable.

### One vertical scroll owner

- The route shell should not vertically scroll an iframe page.
- The iframe document should own normal page scrolling.
- Tables and list cards should grow naturally with the current page of rows; avoid fixed table-body heights.
- A dialog may scroll its content region, but should not create nested scrollbars inside multiple descendants.
- Preserve horizontal table scrolling only when essential columns genuinely cannot fit. First combine related fields and remove wasteful widths.

### Responsive layers

- Let Layui own layer centering and positioning. Do not calculate `top` offsets or continually reposition a layer.
- Constrain maximum height to the current viewport and keep title, content, and footer reachable.
- Choose width from content complexity, not one global size: normal form, detail, and wide content need different presets.
- Prefer a wider two-column form for many short fields; return to one column on narrow screens.
- Every actionable layer needs the standard close icon and an explicit cancel path where appropriate.
- Do not hide an overflowing form below the iframe viewport. The content region must scroll while the action footer remains reachable.

### Forms and controlled choices

- If a value comes from a platform catalog or enum, use select, radio, checkbox, searchable selection, or a choice grid. Do not ask operators to type codes or delimiter lists.
- Keep free text for names, descriptions, reasons, and genuinely open-ended requirements.
- Use clear labels, help text, units, valid ranges, and safe defaults. Keep labels on one line when space permits.
- Use browser validity only as immediate feedback; server validation remains authoritative.
- After dynamically inserting Layui controls, call the appropriate `form.render()`.
- A Layui-rendered `<select>` must use `lay-filter` plus `form.on('select(filter)')` when an immediate action is required. Do not rely on native `change` for a control Layui has taken over.

### Lists and tables

- A management list normally needs keyword search, relevant filters, reset, count, pagination, loading, empty state, and a stable operation entry.
- Search/filter changes reset to page 1 and query immediately.
- Keep raw status codes, hashes, schema names, and IDs secondary; lead with human business language.
- Combine tightly related data into readable two-line cells before adding columns.
- Put multiple row operations behind a Pear/Layui action menu when buttons would wrap, clip, or dominate the row.
- Never hide operations behind `overflow: hidden` without another reachable entry.
- Escape all untrusted values before inserting HTML.

### Live updates and request races

- Manual refresh, filter changes, archive switches, and tab switches must run immediately and independently of polling.
- Automatic refresh may defer only its own view commit while the user is selecting text, typing, scrolling, using a dropdown, or reading a layer.
- Never let an automatic-refresh guard suppress a manual query.
- Give overlapping list requests a monotonic request sequence or cancellation policy; only the newest relevant request may commit.
- When a filter changes during an in-flight poll, the late poll must not overwrite the new result.
- Do not replace the whole view while the user is copying text or interacting with a form.

### Visual language

- Reuse the product's design tokens for typography, spacing, color, radius, and shadow.
- Use a small semantic type scale. Avoid arbitrary local font sizes that compete with page titles or body text.
- Give primary, secondary, warning, and destructive actions distinct but restrained appearances.
- Use framework icons consistently; do not mix unrelated icon systems without a product-level reason.
- Status colors must carry stable meaning: success/active, warning/pending, danger/failed, and neutral/inactive.
- Prefer structured fields, timelines, and key-value groups over raw JSON as the primary interface. Raw payloads belong in an optional technical/audit view.

### Authentication and API behavior

- A new RCMaker management backend uses the framework JWT Token component by default, normally the `admin` guard with Bearer authentication. Do not invent a custom JWT implementation, token format, signature routine, refresh mechanism or login-state cache.
- Issue tokens with `$req->token('admin')->set(...)`; validate protected requests through `$req->token('admin')->get()` or framework middleware; refresh with `reSet()` and handle `RC\Exception\AuthException` through the established API error path.
- Configure secrets, algorithms, expiry, refresh and single-device behavior in `config/token.php`. Replace development secrets before production; use `php index.php interact` to generate signing keys when an asymmetric algorithm is selected.
- Reuse the console's shared API client for the Authorization header, refresh locking and top-level login redirect. Do not implement token handling independently in each iframe page.
- Reuse the project's API client, authentication refresh, error normalization, and top-level login redirect.
- An expired iframe must redirect the top-level console once; it should not leave multiple pages showing raw `UNAUTHORIZED` responses.
- Do not hard-code development hosts, ports, protocols, credentials, or API keys into page scripts.
- Keep API and static-app responsibilities separate when the application architecture separates them.

## Implementation workflow

1. Classify the page as list, detail, workbench, long form, log/monitor, or mixed dashboard.
2. Reuse the nearest existing page pattern and shared helpers.
3. Define state, query inputs, request ownership, and render boundaries before wiring events.
4. Implement framework-native controls and immediate user-triggered behavior.
5. Add responsive behavior using shared tokens and the smallest necessary page stylesheet.
6. Add a regression test for the failure mode, not merely the desired wording.
7. Verify served assets and the live routed page after reloading the iframe.
8. Test normal, empty, long-content, slow-response, error, and narrow/short viewport states.

## Stop conditions

Do not declare completion if any of these remain:

- a filter only updates on the next poll;
- a dialog action or close control is outside the viewport;
- the page and an inner list both show vertical scrollbars without necessity;
- an operation menu is clipped or unreachable;
- automatic refresh interrupts copy, input, dropdown, layer, or scroll interaction;
- a catalog-backed field still requires typing an internal code;
- raw JSON or internal state codes are the main user-facing representation;
- the console static application still runs in the main APP process group or shares its internal listen port;
- static preload is disabled, omits required shell assets, or stops at the time limit before the selected core set is loaded;
- protected management APIs bypass the RCMaker JWT Token component;
- the implementation bypasses an existing shared Pear/Layui/RCMaker capability.
