# RCMaker Pear Admin implementation patterns

Read this reference when working in the RCMaker project or a Pear Admin project with comparable shared helpers. Adapt names to the local project; do not copy endpoints or business fields blindly.

## Required console architecture

For a new RCMaker management console, keep the static frontend outside the main APP process group:

```php
// config/app.php
'admin_console' => [
    'domains' => ['admin.example.com'],
    'bind_process' => 'RC_ADMIN_STATIC',
    'document_root' => 'admin',
    'index_default' => 'index.html',
    'enable_static_file' => true,
    'enable_static_gzip' => true,
    'enable_static_preload' => true,
    'static_preload_extensions' => [
        'html', 'css', 'js', 'json', 'svg', 'ico', 'woff', 'woff2', 'ttf',
    ],
    'static_preload_time_limit' => (float) rcEnv('admin.static_preload_time_limit', 2.0),
    'static_only' => true,
],
```

```php
// config/process.php
'RC_ADMIN_STATIC' => [
    'type' => 'app',
    'listen' => rcEnv('admin.static_listen', 'http://0.0.0.0:8682'),
    'count' => max(1, (int) rcEnv('admin.static_count', 2)),
    'default_app' => 'admin_console',
    'max_request' => max(1, (int) rcEnv('admin.static_max_request', 500000)),
    'memory_limit' => rcEnv('admin.static_memory_limit', '256M'),
],
```

The names, domain, directory and port are examples; preserve local naming and choose an unused internal port. The following boundaries are mandatory:

- `bind_process` exactly matches the `type=app` process-group key;
- the static group does not share the main APP port through `reusePort`;
- `public/admin/index.html` exists for the example `document_root`;
- the reverse proxy preserves `Host` and sends the console domain to the static group's port;
- a stopped or missing `RC_ADMIN_STATIC` makes the bound console unavailable and does not fall back to the main APP group;
- selected preload extensions cover the actual shell assets, while large media and unused demo files remain disk-served;
- startup logs are checked after a full restart. If preload reports `reason=time_limit` before the core set is complete, increase `admin.static_preload_time_limit` or narrow the extension set deliberately.

Linux performs global preload before fork and APP workers inherit it through copy-on-write. Windows cannot fork and preloads according to application/process ownership. Do not compensate for either platform by binding the console back to the main APP group.

The backend API may use the main API application or its own API APP group, but it remains separate from the static console process. New management APIs default to RCMaker JWT:

```php
// Login: issue access and refresh tokens with the admin guard.
return $request->json($request->token('admin')->set([
    'key' => 'admin_' . $adminId,
    'admin_id' => $adminId,
    'admin_name' => $adminName,
]));
```

```php
// Protected endpoint or shared authentication middleware.
$adminId = $request->token('admin')->get('admin_id');
```

Use Bearer mode unless the project has an explicit, established alternative. Configure the `admin` guard in `config/token.php`, retain framework refresh and `AuthException` behavior, and load the framework cache bootstrap required by single-device login. Generate asymmetric signing keys through `php index.php interact`; never put real secrets, tokens or private keys in frontend assets.

## 1. Locate shared capabilities first

In RCMaker, inspect these files before creating page-specific infrastructure:

- `public/pear/admin/js/rc/api.js`: API discovery, JWT refresh, request handling, and top-level expiry redirect;
- `public/pear/admin/js/rc/ui.js`: responsive layers, forms, reasons, action menus, choice grids, list filters, and safe live following;
- `public/pear/admin/css/rcmaker.css`: page shell, cards, tables, dialogs, design tokens, and responsive rules;
- the closest existing page under `public/pear/view/admin` or `public/pear/view/user`;
- the matching page script under `public/pear/admin/js/pages`.

Do not duplicate these capabilities in a page script unless the use case is materially different.

## 2. Routed page structure

Use one page header, one content flow, and no fixed list viewport:

```html
<div class="rc-page">
    <header class="rc-page-head">
        <div>
            <h2>业务页面名称</h2>
            <p>一句话说明这个页面能完成什么。</p>
        </div>
        <div class="rc-head-actions">
            <button id="refreshRows" class="layui-btn layui-btn-primary layui-btn-sm" type="button">
                <i class="layui-icon layui-icon-refresh"></i>刷新
            </button>
        </div>
    </header>

    <section class="layui-card rc-table-card">
        <div class="layui-form rc-list-tools">
            <div class="rc-list-search">
                <i class="layui-icon layui-icon-search"></i>
                <input id="rowKeyword" type="search" placeholder="搜索名称或编号" autocomplete="off">
            </div>
            <select id="rowStatus" lay-filter="rowFilter">
                <option value="">全部状态</option>
            </select>
            <button id="resetRows" class="layui-btn layui-btn-primary layui-btn-sm" type="button">
                重置
            </button>
            <span id="rowCount" class="rc-list-count"></span>
        </div>
        <table id="rowTable" lay-filter="rowTable"></table>
        <div id="rowPager" class="rc-data-pager"></div>
    </section>
</div>
```

The iframe document owns vertical scrolling. Do not add a fixed `height` to the card or Layui table body merely to fill the screen.

## 3. Layui select events

Layui replaces the visible interaction for a rendered select. Use a shared `lay-filter` when several filters perform the same action:

```js
/**
 * Layui接管原生选择框，必须监听框架事件才能即时查询。
 * Layui owns the native select, so framework events trigger immediate queries.
 */
form.on('select(rowFilter)', function () {
    state.page = 1;
    loadRows().catch(RCUI.error);
});
```

After changing options or values programmatically:

```js
$('#rowStatus').html(optionsHtml).val(selectedValue);
form.render('select');
```

Use native `change` only when the control remains native and is not rendered by Layui.

## 4. Server-paginated list with request ownership

Keep query construction separate from rendering. Manual actions should use a visible loading state; automatic refresh may be silent.

```js
const state = {
    page: 1,
    limit: 20,
    total: 0,
    requestSequence: 0
};

/**
 * 构造当前列表查询，不在渲染器中读取隐式状态。
 * Build the current query without hiding state reads inside the renderer.
 */
function rowQuery() {
    return new URLSearchParams({
        page: String(state.page),
        limit: String(state.limit),
        keyword: ($('#rowKeyword').val() || '').trim(),
        status: $('#rowStatus').val() || ''
    });
}

/**
 * 只允许最新查询提交视图，防止迟到轮询覆盖人工筛选。
 * Only the newest query may commit, preventing a late poll from replacing a manual filter.
 */
async function loadRows(renderPager = true, silent = false, canCommit = null) {
    const requestSequence = ++state.requestSequence;
    const loading = silent ? null : RCUI.busy();

    try {
        const result = await RC.request('rows?' + rowQuery());
        if (requestSequence !== state.requestSequence
            || (silent && canCommit && !canCommit())) {
            return;
        }

        state.total = Number(result.count || 0);
        renderRows(result.data || []);
        $('#rowCount').text('共 ' + state.total + ' 条');

        if (renderPager) {
            renderPagerControl();
        }
    } finally {
        if (loading !== null) {
            layui.layer.close(loading);
        }
    }
}
```

Do not guard the global table renderer. The guard belongs only to the automatic request that owns it.

## 5. Safe continuous following

Use the shared follower rather than a raw `setInterval` that redraws during interaction:

```js
RCUI.liveFollow({
    key: 'business-row-list',
    refreshButton: '#refreshRows',
    interval: 5000,
    defaultEnabled: true,
    canRefresh: () => state.page === 1,
    refresh: (canCommit) => loadRows(false, true, canCommit)
});
```

The filter handler still calls `loadRows()` directly. It must never wait for `liveFollow`.

## 6. Responsive forms and details

Use shared layer helpers:

```js
RCUI.form(
    '编辑业务配置',
    '<div class="rc-form-grid">'
        + RCUI.field('名称', '<input name="name" class="layui-input" required maxlength="80">')
        + RCUI.field('状态', '<select name="status" required>' + statusOptions + '</select>')
        + '</div>'
        + RCUI.field('说明', '<textarea name="description" class="layui-textarea" maxlength="500"></textarea>'),
    (data) => RC.post('rowSave', data),
    {
        dialogSize: 'wide',
        submitText: '保存',
        done: loadRows
    }
);
```

Use `dialogSize: 'wide'` for content-heavy forms instead of forcing a narrow layer to become extremely tall. Let the shared helper constrain viewport height and keep the footer reachable.

For read-only details:

```js
RCUI.html('业务详情', detailHtml, {
    dialogSize: 'wide'
});
```

Do not calculate layer coordinates or write a second centering system.

## 7. Controlled choices

Render finite catalogs as controls rather than editable code strings:

```js
const productChoices = catalog.map((item) => ({
    value: item.code,
    label: item.code + ' · ' + item.name
}));

const productGrid = RCUI.choiceGrid(
    'product_codes[]',
    productChoices,
    selectedCodes,
    { className: 'rc-choice-grid-wide' }
);
```

If selection count is large, use a searchable selector or grouped list rather than hundreds of always-visible checkboxes.

## 8. Row operation menus

Keep the table operation column narrow and put multiple actions in a framework menu:

```js
table.on('tool(rowTable)', async function (event) {
    if (event.event !== 'actions') {
        return;
    }

    const action = await RCUI.actionMenu('记录操作 · ' + event.data.name, [
        { key: 'detail', label: '查看详情', icon: 'layui-icon-read' },
        { key: 'edit', label: '编辑资料', icon: 'layui-icon-edit' },
        { key: 'disable', label: '停用', icon: 'layui-icon-close', danger: true }
    ]);

    if (!action) {
        return;
    }
    await runAction(action, event.data);
});
```

Avoid a row full of links that wrap, disappear, or create a wide table.

## 9. Human-readable task and log details

- Show summary, status, time, actor, reason, and business identifiers as columns or key-value rows.
- Use technical codes and hashes as secondary audit text.
- Flatten structured input into sections such as “业务输入”, “AI结果”, and “平台契约”.
- Long keys and values should use stacked label/value rows with `overflow-wrap: anywhere`.
- Keep raw JSON in a collapsible advanced view only when operators genuinely need it.

## 10. Common failure diagnoses

| Symptom | Typical cause | Corrective pattern |
| --- | --- | --- |
| Archive/status filter changes only after 5 seconds | Native `change` bound to a Layui-rendered select | Add `lay-filter`, listen with `form.on('select(...)')`, query immediately |
| New options exist in DOM but are not visible | Dynamic select was not re-rendered | Call `form.render('select')` after updating options |
| Dialog is centered incorrectly | Page CSS or JavaScript recalculates layer position | Remove custom positioning; let Layui center it |
| Long form falls below the viewport | Narrow fixed width and uncontrolled content height | Use a wider preset, responsive columns, and one scrollable content region |
| Every iframe page has two scrollbars | Shell, iframe document, and inner list all own vertical scroll | Shell overflow hidden; iframe document owns scroll; list grows naturally |
| Row actions cannot be opened or are clipped | Inline action overflow or ancestor clipping | Use a narrow action trigger plus framework action menu |
| Copying text is interrupted | Poll replaces the full view during interaction | Pause only automatic commits during active interaction |
| A late poll restores the old filter | Requests have no ownership or sequence | Add a monotonic sequence and commit only the latest query |
| Expired login displays raw `UNAUTHORIZED` | Each iframe handles 401 independently | Reuse shared refresh lock and one top-level login redirect |
