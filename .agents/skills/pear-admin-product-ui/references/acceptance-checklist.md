# Pear Admin product acceptance checklist

## RCMaker architecture

- A new console static application is bound to a dedicated `type=app` process group, not the main APP group.
- The static process group has a distinct internal port, process count, restart boundary and memory limit; `reusePort` is not used to share the main APP port.
- Static files, gzip and preload are explicitly enabled; the selected preload set covers core shell assets and startup logs do not show those assets being skipped by the time limit.
- Protected management APIs use the RCMaker JWT Token component and the intended guard, normally `admin`; signing, validation, refresh and single-device state are not reimplemented.
- The shared frontend API client owns Bearer headers, refresh locking, normalized errors and one top-level expiry redirect.

Use this checklist after implementation or when reviewing an existing page. Test observable behavior, not only source-code patterns.

## 1. Navigation and page shell

- The menu opens the intended iframe route without a 404, blank page, or unknown application.
- The page header, title, subtitle, icon, and primary actions follow the same hierarchy as sibling pages.
- The iframe fills the routed content area and does not create a baseline gap.
- Normal content uses the iframe document's vertical scrollbar; the outer shell does not add a second scrollbar.
- Returning to the page does not leave stale overlays, duplicate event handlers, or duplicate polling timers.

## 2. Lists

Test with 0, 1, one full page, and multiple pages of records.

- Empty state explains that no matching data exists.
- Keyword search is debounced and resets to page 1.
- Every filter resets to page 1 and updates immediately.
- Reset restores defaults immediately and re-renders Layui controls.
- Count and pagination match the current server result.
- Necessary columns remain readable at common widths; related columns are combined before horizontal scrolling is introduced.
- Operations remain reachable and do not wrap outside the row.
- Long names, identifiers, and descriptions wrap or truncate with a reachable detail view.

## 3. Layers and forms

Test at desktop widths around 1920, 1366, 1024, and 768 pixels, and viewport heights around 900, 768, and 600 pixels.

- The layer is centered by Layui and remains within the viewport.
- Content-heavy forms use enough width to avoid an unnecessarily long vertical form.
- At short heights, the content area scrolls and the footer actions remain reachable.
- The close icon, cancel button, shade-close behavior, and successful submit all settle the interaction correctly.
- Labels and inputs align; labels do not split into single-character vertical stacks.
- Dynamic selects, radios, and checkboxes render correctly after content injection.
- Required fields, ranges, lengths, and file constraints provide useful immediate feedback.
- The server still rejects invalid or unauthorized submissions.
- Destructive actions show the exact target, consequences, and required reason in a Pear/Layui layer.

## 4. Immediate interaction versus polling

- Change each Layui select and confirm the network request starts immediately, not at the next polling interval.
- Start a slow poll, then change a filter; the late poll must not overwrite the new result.
- Create or update a task and confirm its state can advance without manual refresh.
- Switch between current and archived records; each view should update immediately.
- While automatic following is enabled, select and copy text; the selection must remain stable.
- Type in search or a form, open a dropdown, scroll, and read a layer; automatic commits must pause without disabling manual actions.
- Leave the page idle and confirm automatic following resumes.

## 5. Visual consistency

- Page title, section title, body, metadata, caption, and metric values use the shared semantic type scale.
- Primary, secondary, warning, and destructive buttons have distinct, readable states.
- Button icons and text have sufficient contrast; avoid colored backgrounds with muted gray labels.
- Cards use consistent border, radius, shadow, and spacing.
- Success, warning, failure, and neutral statuses use stable semantic colors.
- The page does not invent a new icon family, font scale, or button style without a product-level reason.
- Narrow-screen layout becomes one column where necessary and does not hide controls.

## 6. Human-readable data

- The default view explains business meaning without requiring knowledge of internal state codes.
- Technical identifiers remain copyable and available as secondary evidence.
- Logs are presented as columns, timelines, or structured fields rather than a wall of JSON.
- Deep structured input uses stacked labels and values; text never overlaps.
- Dates, durations, units, money, percentages, and counts are formatted consistently.
- Sensitive values are masked or omitted and are never written into UI logs.

## 7. Authentication, errors, and safety

- Expired credentials trigger one top-level redirect to login after refresh fails.
- Multiple iframe requests do not create a redirect loop or repeated expiry messages.
- API errors use a clear product message and preserve a trace identifier when available.
- Loading indicators close on success and failure.
- Double-clicking or retrying an operation does not create duplicate records when the backend provides idempotency.
- Untrusted values are escaped before HTML insertion.
- Development ports, API hosts, secrets, and credentials are not embedded in page scripts.

## 8. Automated checks

Add or update tests that assert behavioral invariants such as:

- the select has the required `lay-filter` and the script binds the matching `form.on` event;
- no browser-native blocking dialogs exist;
- no global Layui renderer monkey-patch exists;
- live-follow configuration has a real refresh button and a local commit guard;
- responsive layer helpers and wide-form presets are used for long content;
- iframe/list CSS does not reintroduce fixed inner vertical scrolling;
- syntax checks and relevant UI regression suites pass.

Also verify the development server is actually serving the changed HTML, JavaScript, and CSS. A correct source file does not prove an already-open iframe has loaded it; reload the iframe or reopen its tab before live acceptance.
