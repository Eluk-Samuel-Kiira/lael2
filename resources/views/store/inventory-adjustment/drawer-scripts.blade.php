{{-- ═══════════════════════════════════════════════════════════════
     STOCK ADJUSTMENT DRAWER — IIFE.
     window.StockDrawer.open(id) → fetch /stocks/{id}/details → render.
     Adjust / Transfer buttons call the (future) StockAdjustModal /
     StockTransferModal; if not yet loaded, they log a stub message.
     ═══════════════════════════════════════════════════════════════ --}}
<script>
window.StockDrawer = (function () {
    'use strict';

    const URL_DETAILS = '{{ route("stocks.details", ["id" => "__ID__"]) }}';

    const CURRENCY = '{{ currency_symbol() }}';

    let currentPayload = null;
    let currentId = null;
    let bs = null;

    function getBs() {
        const el = document.getElementById('stockDrawer');
        if (!el) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }

    async function open(id) {
        currentId = id;
        const inst = getBs();
        if (!inst) return;

        showSkeleton();
        inst.show();

        try {
            const res = await fetch(URL_DETAILS.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed to load');
            currentPayload = json;
            render(json);
        } catch (err) {
            console.error('[stock-drawer] load failed:', err);
            renderError(err.message);
        }
    }

    async function reload(id) {
        if (!id) return;
        try {
            const res = await fetch(URL_DETAILS.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const json = await res.json();
            if (!json.success) return;
            currentPayload = json;
            render(json);
        } catch (err) {
            console.error('[stock-drawer] reload failed:', err);
        }
    }

    function close() { getBs()?.hide(); }

    function showSkeleton() {
        document.getElementById('stockDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('stockDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('stockDrawerFooter')?.classList.add('d-none');
        const v = document.getElementById('stockDrawerVariant');
        if (v) v.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('stockDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('stockDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}
        </div>`;
    }

    function render(payload) {
        const item = payload.item;
        currentId = item.id;

        document.getElementById('stockDrawerVariant').textContent =
            `${item.variant_name || '—'}  ·  ${item.department_name || '—'}`;

        document.getElementById('stockDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('stockDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderView(item, payload.adjustments || [], payload.transactions || [], payload.movement_summary || null);

        const footer  = document.getElementById('stockDrawerFooter');
        const actions = document.getElementById('stockDrawerActions');
        actions.innerHTML = renderActionBar(item, payload.permissions || {});
        footer.classList.remove('d-none');

        bindActions();
    }

    /* ────────────────────────────────────────────────────────────
     VIEW
       ──────────────────────────────────────────────────────────── */
    function renderView(item, adjustments, transactions, movement) {
        const imageUrl = productImageUrl(item.variant_image);

        const expiryBadge = (() => {
            if (!item.expiry_date) return '';
            const days = item.expiry_days_remaining;
            if (days === null || days === undefined) return '';
            if (days < 0)  return `<span class="badge badge-light-danger ms-2">{{ __("pagination.expired") ?? "Expired" }}</span>`;
            if (days <= 7) return `<span class="badge badge-light-warning ms-2">${days}d</span>`;
            return '';
        })();

        return `
            <div class="d-flex align-items-start gap-4 mb-6">
                <img src="${imageUrl}"
                     class="rounded-3 border"
                     style="width:64px;height:64px;object-fit:cover;"
                     onerror="this.src='{{ asset('assets/media/stock/ecommerce/2.png') }}'">
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(item.variant_name || '—')}</h5>
                    <div class="text-muted fs-7">
                        SKU <code>${esc(item.variant_sku || '—')}</code>
                        ${item.batch_number ? `&nbsp;·&nbsp; {{ __("pagination.batch_number") }} <code>${esc(item.batch_number)}</code>` : ''}
                    </div>
                </div>
            </div>

            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('pagination._location') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(item.location_name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('auth._department') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(item.department_name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('auth._creater') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(item.created_by_name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('auth.created_at') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(item.created_at || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('pagination.expiry_date') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(item.expiry_date_human || '—')}${expiryBadge}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('pagination.preferred_stock_level') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${item.preferred_stock_level ?? 0}</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-6">
                <div class="col-4">
                    <div class="bg-light-success rounded-3 p-3 text-center h-100">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('pagination.overall_quantity') }}</div>
                        <div class="fs-4 fw-bold text-success">${fmt(item.overall_quantity, 0)}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="bg-light-warning rounded-3 p-3 text-center h-100">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('pagination.quantity_allocated') }}</div>
                        <div class="fs-4 fw-bold text-warning">${fmt(item.quantity_allocated, 0)}</div>
                        ${item.below_preferred ? `<div class="badge badge-light-danger mt-1">{{ __("pagination.below_preferred") ?? "Below preferred" }}</div>` : ''}
                    </div>
                </div>
                <div class="col-4">
                    <div class="bg-light-primary rounded-3 p-3 text-center h-100">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('pagination.preferred_stock_level') }}</div>
                        <div class="fs-4 fw-bold text-primary">${item.preferred_stock_level ?? 0}</div>
                    </div>
                </div>
            </div>

            ${renderMovementSummary(movement)}

            ${renderHistoryBlock('adjustments', '{{ __("pagination.audit_trail") ?? "Audit trail" }}', adjustments, 'adjustment')}
            ${renderHistoryBlock('transactions', '{{ __("pagination.stock_movements") ?? "Stock movements" }}', transactions, 'transaction')}
        `;
    }

    function renderMovementSummary(m) {
        if (!m) return '';
        const net = Number(m.net || 0);
        const netCls = net > 0 ? 'text-success' : (net < 0 ? 'text-danger' : 'text-muted');
        const netTxt = net > 0 ? '+' + fmt(net, 0) : fmt(net, 0);

        return `
            <div class="bg-light rounded-3 p-4 mb-6">
                <h6 class="fw-bold text-gray-800 mb-3">
                    <i class="bi bi-graph-up-arrow me-2 text-primary"></i>
                    {{ __("passwords.movement_summary") ?? "Movement summary" }}
                    <span class="badge badge-light ms-1">last ${m.window_days} days</span>
                </h6>
                <div class="row g-3">
                    <div class="col-4">
                        <div class="text-muted fs-8">{{ __("pagination.stock_in") ?? "In" }}</div>
                        <div class="fw-bold fs-6 text-success">+${fmt(m.recent_in, 0)}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted fs-8">{{ __("pagination.stock_out") ?? "Out" }}</div>
                        <div class="fw-bold fs-6 text-danger">-${fmt(m.recent_out, 0)}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted fs-8">{{ __("pagination.net") ?? "Net" }}</div>
                        <div class="fw-bold fs-6 ${netCls}">${netTxt}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderHistoryBlock(key, title, rows, kind) {
        if (!rows || rows.length === 0) {
            return `
                <div class="mb-6">
                    <h6 class="fw-bold text-gray-800 mb-3">
                        <i class="bi bi-clock-history me-2 text-muted"></i>${title}
                        <span class="badge badge-light ms-1">0</span>
                    </h6>
                    <div class="text-muted fs-7 fst-italic">{{ __('pagination.no_records_found') ?? 'No records' }}</div>
                </div>
            `;
        }

        const body = rows.map(r => {
            if (kind === 'adjustment') {
                const delta = Number(r.delta ?? (r.quantity_after - r.quantity_before));
                const deltaCls = delta > 0 ? 'text-success' : (delta < 0 ? 'text-danger' : 'text-muted');
                const deltaTxt = delta === 0 ? '0' : (delta > 0 ? '+' + delta : String(delta));
                return `
                    <tr>
                        <td class="ps-3 text-gray-800 fs-7">
                            ${esc(r.created_at)}
                            ${r.created_by_name ? `<div class="text-muted fs-8">${esc(r.created_by_name)}</div>` : ''}
                        </td>
                        <td class="text-center">
                            <span class="badge badge-light fs-8">${esc(r.reason_label || r.reason || '—')}</span>
                        </td>
                        <td class="text-end fs-7">
                            ${fmt(r.quantity_before, 0)} → <strong>${fmt(r.quantity_after, 0)}</strong>
                            <span class="d-block fs-8 ${deltaCls}">${deltaTxt}</span>
                        </td>
                    </tr>
                    ${r.notes ? `<tr><td colspan="3" class="ps-3 pe-3 fs-8 text-muted">${esc(r.notes)}</td></tr>` : ''}
                `;
            }
            const qty = Number(r.quantity || 0);
            return `
                <tr>
                    <td class="ps-3 text-gray-800 fs-7">
                        ${esc(r.created_at)}
                        ${r.created_by_name ? `<div class="text-muted fs-8">${esc(r.created_by_name)}</div>` : ''}
                    </td>
                    <td class="text-center">
                        <span class="badge badge-light-${qty >= 0 ? 'success' : 'danger'} fs-8">
                            ${esc(r.type_label || r.type || '—')}
                        </span>
                    </td>
                    <td class="text-end fw-bold fs-7 ${qty >= 0 ? 'text-success' : 'text-danger'}">
                        ${qty >= 0 ? '+' : ''}${fmt(qty, 0)}
                    </td>
                </tr>
                ${r.notes ? `<tr><td colspan="3" class="ps-3 pe-3 fs-8 text-muted">${esc(r.notes)}</td></tr>` : ''}
            `;
        }).join('');

        return `
            <div class="mb-6">
                <h6 class="fw-bold text-gray-800 mb-3">
                    <i class="bi bi-clock-history me-2 text-primary"></i>${title}
                    <span class="badge badge-light-primary ms-1">${rows.length}</span>
                </h6>
                <div class="table-responsive border rounded-3">
                    <table class="table table-row-dashed align-middle mb-0">
                        <thead>
                            <tr class="fw-bold text-muted bg-light fs-8">
                                <th class="ps-3">{{ __('pagination.date') ?? 'Date' }}</th>
                                <th class="text-center">${kind === 'adjustment' ? '{{ __("pagination.reason") ?? "Reason" }}' : '{{ __("pagination.type") ?? "Type" }}'}</th>
                                <th class="text-end pe-3">${kind === 'adjustment' ? '{{ __("pagination.change") ?? "Change" }}' : '{{ __("pagination.quantity") ?? "Qty" }}'}</th>
                            </tr>
                        </thead>
                        <tbody>${body}</tbody>
                    </table>
                </div>
            </div>
        `;
    }

    /* ────────────────────────────────────────────────────────────
     ACTION BAR
       ──────────────────────────────────────────────────────────── */
    function renderActionBar(item, perms) {
        const btns = [];

        const mk = (action, icon, label, tone) => `
            <button type="button"
                    class="btn btn-sm btn-light-${tone} d-inline-flex align-items-center gap-1"
                    data-stock-action="${action}">
                <i class="bi ${icon} fs-6"></i>
                <span class="fs-7 fw-semibold">${label}</span>
            </button>
        `;

        if (perms.can_adjust) {
            btns.push(mk('adjust', 'bi-dash-square', '{{ __("passwords._adjust") }}', 'primary'));
        }
        if (perms.can_transfer) {
            btns.push(mk('transfer', 'bi-arrow-left-right', '{{ __("passwords._transfer") }}', 'success'));
        }

        if (!btns.length) return '';
        return `<div class="d-flex align-items-center gap-2">${btns.join('')}</div>`;
    }

    function bindActions() {
        const actions = document.getElementById('stockDrawerActions');
        if (!actions) return;
        actions.querySelectorAll('[data-stock-action]').forEach(el => {
            el.addEventListener('click', () => {
                const action = el.getAttribute('data-stock-action');
                switch (action) {
                    case 'adjust':
                        if (window.StockAdjustPanel?.open) {
                            window.StockAdjustPanel.open(currentPayload.item.id);
                        } else {
                            console.log('[stock-drawer] adjust panel not loaded');
                        }
                        return;
                    case 'transfer':
                        if (window.StockTransferPanel?.open) {
                            window.StockTransferPanel.open(currentPayload.item.id);
                        } else {
                            console.log('[stock-drawer] transfer panel not loaded');
                        }
                        return;
                }
            });
        });
    }

    
    /* ────────────────────────────────────────────────────────────
     HELPERS
       ──────────────────────────────────────────────────────────── */
    function productImageUrl(path) {
        if (!path) return '{{ asset("assets/media/stock/ecommerce/2.png") }}';
        const clean = String(path).replace(/^\/+/, '');
        return '{{ asset("storage") }}/' + clean;
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    function fmt(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d,
            maximumFractionDigits: d,
        });
    }

    function money(n) {
        return CURRENCY + Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    window.StockDrawer = { open, close, reload };
    return window.StockDrawer;
})();
</script>