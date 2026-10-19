{{-- ═══════════════════════════════════════════════════════════════
     INVENTORY DRAWER — IIFE.
     open(id) → fetch /items/{id}/details → render view + action bar.
     Panels for Edit allocation / Pricing / Delete swap the drawer body.
     ═══════════════════════════════════════════════════════════════ --}}
<script>
window.InventoryDrawer = (function () {
    'use strict';

    const URL_DETAILS = '{{ route("items.details", ["id" => "__ID__"]) }}';
    const URL_UPDATE  = '{{ route("items.update",  ["item" => "__ID__"]) }}';
    const URL_DELETE  = '{{ route("items.destroy", ["item" => "__ID__"]) }}';
    const URL_PRICING_UPDATE = '{{ route("items.pricing.update", ["id" => "__ID__"]) }}';

    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CURRENCY = '{{ currency_symbol() }}';

    let currentPayload = null;
    let currentId = null;
    let bs = null;

    function getBs() {
        const el = document.getElementById('inventoryDrawer');
        if (!el) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }

    async function req(method, url, body) {
        const res = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.success === false) {
            throw new Error(json.message || 'Request failed');
        }
        return json;
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
            console.error('[inventory-drawer] load failed:', err);
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
            console.error('[inventory-drawer] reload failed:', err);
        }
    }

    function close() { getBs()?.hide(); }

    function showSkeleton() {
        document.getElementById('inventoryDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('inventoryDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('inventoryDrawerFooter')?.classList.add('d-none');
        const v = document.getElementById('inventoryDrawerVariant');
        if (v) v.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('inventoryDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('inventoryDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}
        </div>`;
    }

    function render(payload) {
        const item = payload.item;
        currentId = item.id;

        document.getElementById('inventoryDrawerVariant').textContent =
            `${item.variant_name || '—'}  ·  ${item.department_name || '—'}`;

        document.getElementById('inventoryDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('inventoryDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderView(item, payload.adjustments || [], payload.transactions || []);

        const footer  = document.getElementById('inventoryDrawerFooter');
        const actions = document.getElementById('inventoryDrawerActions');
        actions.innerHTML = renderActionBar(item, payload.permissions || {});
        footer.classList.remove('d-none');

        bindActions();
    }

    function renderView(item, adjustments, transactions) {
        const imageUrl = productImageUrl(item.variant_image);

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
                        <div class="fw-semibold text-gray-800 fs-7">${esc(item.expiry_date_human || '—')}</div>
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

            ${renderPricingSummary(item.pricing)}

            ${renderHistoryBlock('adjustments', '{{ __("pagination.audit_trail") ?? "Audit trail" }}', adjustments, 'adjustment')}
            ${renderHistoryBlock('transactions', '{{ __("pagination.stock_movements") ?? "Stock movements" }}', transactions, 'transaction')}
        `;
    }

    function renderPricingSummary(pricing) {
        if (!pricing) return '';
        const eff = pricing.effective || {};
        const src = eff.pricing_source === 'item'
            ? '{{ __("passwords.custom_pricing") }}'
            : '{{ __("passwords.inherited_from_variant") }}';

        return `
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-gray-300 border-dashed">
                    <h6 class="fw-bold text-gray-800 mb-0">
                        <i class="bi bi-currency-exchange me-2 text-primary"></i>
                        {{ __("passwords.custom_pricing") }}
                    </h6>
                    <span class="badge badge-light-${eff.pricing_source === 'item' ? 'warning' : 'secondary'}">${src}</span>
                </div>
                <div class="row g-3">
                    <div class="col-4">
                        <div class="text-muted fs-8">{{ __('passwords.cost_price') }}</div>
                        <div class="fw-bold fs-7">${money(eff.cost_price || 0)}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted fs-8">{{ __('passwords.selling_price') }}</div>
                        <div class="fw-bold text-primary fs-7">${money(eff.selling_price || 0)}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted fs-8">{{ __('passwords.discount_price') }}</div>
                        <div class="fw-bold text-success fs-7">${money(eff.discount_price || 0)}</div>
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
                return `
                    <tr>
                        <td class="ps-3 text-gray-800 fs-7">${esc(r.created_at)}</td>
                        <td class="text-center">
                            <span class="badge badge-light fs-8">${esc(r.reason || '—')}</span>
                        </td>
                        <td class="text-end fs-7 pe-3">
                            ${fmt(r.quantity_before, 0)} → <strong>${fmt(r.quantity_after, 0)}</strong>
                        </td>
                    </tr>
                    ${r.notes ? `<tr><td colspan="3" class="ps-3 pe-3 fs-8 text-muted">${esc(r.notes)}</td></tr>` : ''}
                `;
            }
            return `
                <tr>
                    <td class="ps-3 text-gray-800 fs-7">${esc(r.created_at)}</td>
                    <td class="text-center">
                        <span class="badge badge-light-${r.quantity >= 0 ? 'success' : 'danger'} fs-8">
                            ${esc(r.type || '—')}
                        </span>
                    </td>
                    <td class="text-end fw-bold fs-7 pe-3 ${r.quantity >= 0 ? 'text-success' : 'text-danger'}">
                        ${r.quantity >= 0 ? '+' : ''}${fmt(r.quantity, 0)}
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

    function renderActionBar(item, perms) {
        const btns = [];
        const mk = (action, icon, label, tone) => `
            <button type="button"
                    class="btn btn-sm btn-light-${tone} d-inline-flex align-items-center gap-1"
                    data-inv-action="${action}">
                <i class="bi ${icon} fs-6"></i>
                <span class="fs-7 fw-semibold">${label}</span>
            </button>
        `;

        if (perms.can_edit) {
            btns.push(mk('edit', 'bi-pencil-square', '{{ __("auth._edit") }}', 'primary'));
        }
        if (perms.can_change_pricing) {
            btns.push(mk('pricing', 'bi-currency-exchange', '{{ __("passwords.custom_pricing") }}', 'warning'));
        }
        if (perms.can_delete) {
            btns.push(mk('delete', 'bi-trash', '{{ __("auth._delete") }}', 'danger'));
        }

        if (!btns.length) return '';
        return `<div class="d-flex align-items-center gap-2">${btns.join('')}</div>`;
    }

    function bindActions() {
        const actions = document.getElementById('inventoryDrawerActions');
        if (!actions) return;
        actions.querySelectorAll('[data-inv-action]').forEach(el => {
            el.addEventListener('click', () => {
                const action = el.getAttribute('data-inv-action');
                switch (action) {
                    case 'edit':    return openEditPanel();
                    case 'pricing': return openPricingPanel();
                    case 'delete':  return openDeletePanel();
                }
            });
        });
    }

    // ── Panels ──────────────────────────────────────────────────
    function panelShell(title, bodyHtml, footerHtml) {
        const body = document.getElementById('inventoryDrawerContent');
        body.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold text-gray-800 mb-0">${title}</h5>
                <button type="button" class="btn btn-sm btn-light" data-panel-back>
                    <i class="bi bi-arrow-left me-1"></i>{{ __('auth._back') ?? 'Back' }}
                </button>
            </div>
            <div data-panel-body>${bodyHtml}</div>
            <div class="d-flex justify-content-end gap-2 mt-5" data-panel-footer>${footerHtml}</div>
        `;
        document.getElementById('inventoryDrawerFooter')?.classList.add('d-none');
        body.querySelectorAll('[data-panel-back]').forEach(btn => {
            btn.addEventListener('click', () => render(currentPayload));
        });
        return body;
    }

    // ── Edit allocation ─────────────────────────────────────────
    function openEditPanel() {
        const item = currentPayload.item;
        const overall = Number(item.overall_quantity || 0);
        const currentAlloc = Number(item.quantity_allocated || 0);
        const maxAllowed = overall + currentAlloc;

        const body = panelShell(
            '{{ __("auth._edit") }} — ' + esc(item.variant_name),
            `
            <div class="row g-4 mb-4">
                <div class="col-6">
                    <label class="form-label fw-semibold">{{ __('pagination._location') }}</label>
                    <input type="text" class="form-control" value="${esc(item.location_name || '')}" disabled>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">{{ __('auth._department') }}</label>
                    <input type="text" class="form-control" value="${esc(item.department_name || '')}" disabled>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-4">
                    <label class="form-label fw-semibold">{{ __('pagination.available_stock') }}</label>
                    <input type="text" class="form-control" value="${overall}" disabled>
                </div>
                <div class="col-4">
                    <label class="form-label fw-semibold">{{ __('pagination.current_allocated') }}</label>
                    <input type="text" class="form-control" value="${currentAlloc}" disabled>
                </div>
                <div class="col-4">
                    <label class="form-label fw-semibold required">{{ __('pagination.new_quantity') }}</label>
                    <input type="number" class="form-control" min="0" max="${maxAllowed}" step="1"
                           value="${currentAlloc}" data-new-qty>
                </div>
            </div>

            <div class="alert alert-light-primary">
                <div class="d-flex flex-wrap w-100 justify-content-between gap-3">
                    <div>
                        <span class="fw-bold">{{ __('pagination.new_allocated') }}:</span>
                        <span class="badge badge-light-primary ms-1" data-display-new>${currentAlloc}</span>
                    </div>
                    <div>
                        <span class="fw-bold">{{ __('pagination.remaining_stock') }}:</span>
                        <span class="badge badge-light-info ms-1" data-display-remaining>${overall}</span>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-6">
                    <label class="form-label fw-semibold">{{ __('pagination.expiry_date') }}</label>
                    <input type="date" class="form-control" value="${esc(item.expiry_date || '')}" data-expiry>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">{{ __('pagination.preferred_stock_level') }}</label>
                    <input type="number" class="form-control" min="0" step="1"
                           value="${item.preferred_stock_level || 0}" data-preferred>
                </div>
            </div>
            `,
            `<button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('auth._discard') }}</button>
             <button type="button" class="btn btn-primary" data-save-allocation>
                 <span class="indicator-label">{{ __('auth._update') }} {{__('pagination._inventory_item')}}</span>
                 <span class="indicator-progress d-none">{{ __('auth.please_wait') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
             </button>`
        );

        const input = body.querySelector('[data-new-qty]');
        const displayNew = body.querySelector('[data-display-new]');
        const displayRem = body.querySelector('[data-display-remaining]');

        const recalc = () => {
            let v = parseInt(input.value, 10);
            if (isNaN(v) || v < 0) v = 0;
            if (v > maxAllowed) v = maxAllowed;
            input.value = v;
            displayNew.textContent = v;
            displayRem.textContent = maxAllowed - v;
        };
        input.addEventListener('input', recalc);

        body.querySelector('[data-save-allocation]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            const newQty = parseInt(input.value, 10) || 0;
            const expiry = body.querySelector('[data-expiry]').value || null;
            const preferred = parseInt(body.querySelector('[data-preferred]').value, 10) || 0;

            setLoading(btn, true);
            try {
                await req('PUT', URL_UPDATE.replace('__ID__', item.id), {
                    variant_id:            item.variant_id,
                    location_id:           item.location_id,
                    department_id:         item.department_id,
                    quantity_allocated:    newQty,
                    expiry_date:           expiry,
                    preferred_stock_level: preferred,
                });
                window.toastr?.success?.('{{ __("auth._updated") }}');
                window.InventoryList?.reload?.();
                await reload(item.id);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });
    }

    // ── Pricing ─────────────────────────────────────────────────
    function openPricingPanel() {
        const item = currentPayload.item;
        const pricing = item.pricing || {};
        const raw = pricing.raw || {};
        const hasCustom = !!pricing.has_custom_pricing;

        const body = panelShell(
            '{{ __("passwords.custom_pricing") }} — ' + esc(item.variant_name),
            `
            <div class="alert alert-light-info mb-4">
                <div class="fs-7">{{ __('passwords.pricing_help_text') ?? 'Override pricing for this specific branch item. When off, the pricing is inherited from the parent variant.' }}</div>
            </div>

            <div class="form-check form-switch form-check-custom form-check-solid mb-4">
                <input class="form-check-input" type="checkbox" id="drawerCustomPricingToggle"
                       ${hasCustom ? 'checked' : ''}>
                <label class="form-check-label fw-semibold" for="drawerCustomPricingToggle">
                    {{ __('passwords.enable_custom_pricing') }}
                </label>
            </div>

            <div class="p-3 rounded bg-light mb-4" data-fallback-section ${hasCustom ? 'style="display:none"' : ''}>
                <div class="text-muted fs-8 mb-2">{{ __('passwords.inherited_from_variant') }}</div>
                <div class="row g-3">
                    <div class="col-4"><div class="fs-8 text-muted">{{ __('passwords.cost_price') }}</div><div class="fw-bold">${money(pricing.variant_fallback?.grand_total_cost_price || 0)}</div></div>
                    <div class="col-4"><div class="fs-8 text-muted">{{ __('passwords.selling_price') }}</div><div class="fw-bold text-primary">${money(pricing.variant_fallback?.selling_price || 0)}</div></div>
                    <div class="col-4"><div class="fs-8 text-muted">{{ __('passwords.discount_price') }}</div><div class="fw-bold text-success">${money(pricing.variant_fallback?.discount_selling_price || 0)}</div></div>
                </div>
            </div>

            <div data-override-section ${hasCustom ? '' : 'style="display:none"'}>
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label">{{ __('passwords.supplier_cost') }}</label>
                        <input type="number" step="0.01" min="0" class="form-control" data-inv-cost
                               value="${raw.supplier_cost_price ?? ''}">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('passwords.other_costs') }}</label>
                        <input type="number" step="0.01" min="0" class="form-control" data-inv-other
                               value="${raw.total_shipping_cost ?? ''}">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('passwords.selling_price') }}</label>
                        <input type="number" step="0.01" min="0" class="form-control" data-inv-selling
                               value="${raw.selling_price ?? ''}">
                    </div>
                    <div class="col-6">
                        <label class="form-label">{{ __('passwords.discount') }} (%)</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" data-inv-discount
                               value="${raw.discount_percentage ?? ''}">
                    </div>
                </div>
            </div>
            `,
            `<button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('auth._discard') }}</button>
             <button type="button" class="btn btn-primary" data-save-pricing>
                 <span class="indicator-label">{{ __('auth.save') }} {{ __("passwords.custom_pricing") }}</span>
                 <span class="indicator-progress d-none">{{ __('auth.please_wait') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
             </button>`
        );

        const toggle = body.querySelector('#drawerCustomPricingToggle');
        const fallback = body.querySelector('[data-fallback-section]');
        const override = body.querySelector('[data-override-section]');

        toggle.addEventListener('change', () => {
            const on = toggle.checked;
            fallback.style.display = on ? 'none' : '';
            override.style.display = on ? '' : 'none';
        });

        body.querySelector('[data-save-pricing]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            const customOn = toggle.checked;

            const numOrNull = (sel) => {
                const el = body.querySelector(sel);
                if (!el || el.value === '') return null;
                const n = parseFloat(el.value);
                return isNaN(n) ? null : n;
            };

            setLoading(btn, true);
            try {
                await req('PUT', URL_PRICING_UPDATE.replace('__ID__', item.id), {
                    has_custom_pricing:  customOn ? 1 : 0,
                    supplier_cost_price: customOn ? numOrNull('[data-inv-cost]')    : null,
                    total_shipping_cost: customOn ? numOrNull('[data-inv-other]')   : null,
                    ura_taxes_applied:   0,
                    additional_expenses: 0,
                    selling_price:       customOn ? numOrNull('[data-inv-selling]') : null,
                    discount_percentage: customOn ? (numOrNull('[data-inv-discount]') ?? 0) : null,
                });
                window.toastr?.success?.('{{ __("passwords.custom_pricing_updated") }}');
                window.InventoryList?.reload?.();
                await reload(item.id);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });
    }

    // ── Delete ──────────────────────────────────────────────────
    function openDeletePanel() {
        const item = currentPayload.item;

        const body = panelShell(
            '{{ __("auth._delete") }} — ' + esc(item.variant_name),
            `
            <div class="alert alert-danger">
                <p class="mb-2"><strong>{{ __('auth.are_you_sure') }}</strong></p>
                <p class="mb-0">{{ __('auth.action_cannot') }}</p>
            </div>
            <div class="text-muted fs-7">
                {{ __('pagination._location') }}: <strong>${esc(item.location_name || '—')}</strong> &nbsp;·&nbsp;
                {{ __('auth._department') }}: <strong>${esc(item.department_name || '—')}</strong>
            </div>
            `,
            `<button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('auth._discard') }}</button>
             <button type="button" class="btn btn-danger" data-delete-confirm>
                 <span class="indicator-label"><i class="bi bi-trash me-1"></i>{{ __('auth._confirm') }}</span>
                 <span class="indicator-progress d-none">{{ __('auth.please_wait') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
             </button>`
        );

        body.querySelector('[data-delete-confirm]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            setLoading(btn, true);
            try {
                await req('DELETE', URL_DELETE.replace('__ID__', item.id), null);
                window.toastr?.success?.('{{ __("auth._deleted") }}');
                window.InventoryList?.reload?.();
                close();
            } catch (err) {
                window.toastr?.error?.(err.message);
                setLoading(btn, false);
            }
        });
    }

    // ── Helpers ─────────────────────────────────────────────────
    function setLoading(btn, busy) {
        if (!btn) return;
        if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';
        const label = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        if (progress && !progress.dataset.overlayInit) {
            Object.assign(progress.style, {
                position: 'absolute', top: '50%', left: '50%',
                transform: 'translate(-50%, -50%)',
                alignItems: 'center', gap: '0.5rem',
                whiteSpace: 'nowrap', pointerEvents: 'none',
            });
            progress.dataset.overlayInit = '1';
        }
        btn.disabled = busy;
        if (label) label.style.visibility = busy ? 'hidden' : '';
        if (progress) {
            progress.classList.toggle('d-none', !busy);
            progress.style.display = busy ? 'inline-flex' : 'none';
        }
    }

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

    window.InventoryDrawer = { open, close, reload };
    return window.InventoryDrawer;
})();
</script>