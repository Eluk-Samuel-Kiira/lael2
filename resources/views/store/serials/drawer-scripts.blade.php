<script>
window.SerialVariantDrawer = (function () {
    'use strict';

    const URL_SHOW = @json(route('serials.variant.drawer-data', ['variant' => '__ID__']));
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CURRENCY = @json(currency_symbol());

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('serialVariantDrawer');
        if (!el) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }

    function close()       { getBs()?.hide(); }
    function isOpenFor(id) { return String(currentId) === String(id); }

    async function req(method, url, body) {
        const res = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json',
                'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.success === false) throw new Error(json.message || 'Request failed');
        return json;
    }

    async function open(id) {
        currentId = id;
        const inst = getBs(); if (!inst) return;

        showSkeleton(); inst.show();

        try {
            const res = await fetch(URL_SHOW.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            currentPayload = json;
            render(json.variant, json.serials, json.summary);
        } catch (err) {
            console.error('[serial-variant-drawer] load failed:', err);
            renderError(err.message);
        }
    }

    async function reload(id) {
        if (!id) return;
        try {
            const res = await fetch(URL_SHOW.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const json = await res.json();
            if (!json.success) return;
            currentPayload = json;
            render(json.variant, json.serials, json.summary);
        } catch (err) { console.error('[serial-variant-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('serialVariantDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('serialVariantDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('serialVariantDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('serialVariantDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('serialVariantDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('serialVariantDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(variant, serials, summary) {
        currentId = variant.id;

        const nameEl = document.getElementById('serialVariantDrawerName');
        if (nameEl) nameEl.textContent = variant.product_name + ' — ' + variant.name;

        document.getElementById('serialVariantDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('serialVariantDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderView(variant, serials, summary);

        document.getElementById('serialVariantDrawerActions').innerHTML = renderActionCluster(variant);
        document.getElementById('serialVariantDrawerFooter').classList.remove('d-none');
        bindActionCluster(variant, serials);
    }

    function renderView(variant, serials, summary) {
        return `
            ${renderHeaderBlock(variant)}
            ${renderSummaryBlock(summary)}
            ${renderSerialsTable(variant, serials)}
        `;
    }

    function renderHeaderBlock(v) {
        return `
            <div class="mb-6">
                <h5 class="fw-bold text-gray-800 mb-1">${esc(v.name)}</h5>
                <div class="text-muted fs-7 mb-3">
                    ${esc(v.product_name || '')} <span class="mx-2">·</span>
                    <code>${esc(v.sku || '—')}</code>
                </div>
                <div class="d-flex flex-wrap gap-4 fs-7">
                    <div>
                        <span class="text-muted">Cost Price:</span>
                        <span class="fw-bold text-gray-800">${money(v.effective_cost)}</span>
                    </div>
                    <div>
                        <span class="text-muted">Selling Price:</span>
                        <span class="fw-bold text-primary">${money(v.effective_sell)}</span>
                    </div>
                    <div>
                        <span class="text-muted">Pricing:</span>
                        <span class="badge badge-light-${v.pricing_source === 'item' ? 'warning' : 'secondary'} fs-8">
                            ${v.pricing_source === 'item' ? 'Item override' : 'Variant default'}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    function renderSummaryBlock(s) {
        const badges = [
            ['total',    'primary',   'Total'],
            ['available','success',   'Available'],
            ['reserved', 'warning',   'Reserved'],
            ['sold',     'danger',    'Sold'],
            ['returned', 'info',      'Returned'],
            ['lost',     'secondary', 'Lost'],
            ['damaged',  'dark',      'Damaged'],
        ].filter(([key]) => Number(s[key] || 0) > 0);

        if (badges.length === 0) return '';

        return `
            <div class="d-flex flex-wrap gap-2 mb-4">
                ${badges.map(([key, color, label]) => `
                    <span class="badge badge-light-${color} fs-7 py-2 px-3">
                        ${s[key]} ${label}
                    </span>
                `).join('')}
            </div>
        `;
    }

    function renderSerialsTable(variant, serials) {
        if (!serials || serials.length === 0) {
            return `<div class="alert alert-light-warning border border-warning border-dashed py-3 fs-7">
                No serial numbers for this variant yet.
            </div>`;
        }

        const rows = serials.map(s => {
            const actions = [];

            if (s.status === 'available' && variant.can_change_status) {
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-primary"
                            data-serial-action="sold" data-serial-id="${s.id}"
                            title="Mark as Sold">
                        <i class="bi bi-cart-check"></i>
                    </button>`);
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-warning"
                            data-serial-action="reserved" data-serial-id="${s.id}"
                            title="Reserve">
                        <i class="bi bi-bookmark"></i>
                    </button>`);
            }

            if (s.status === 'sold' && variant.can_change_status) {
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-info"
                            data-serial-action="returned" data-serial-id="${s.id}"
                            title="Mark as Returned">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>`);
            }

            if (variant.can_edit_pricing && s.status !== 'sold') {
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-success"
                            data-serial-action="pricing" data-serial-id="${s.id}"
                            title="Edit Pricing">
                        <i class="bi bi-currency-exchange"></i>
                    </button>`);
            }

            if (variant.can_delete && s.status !== 'sold') {
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-danger"
                            data-serial-action="delete" data-serial-id="${s.id}"
                            title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>`);
            }

            const soldCell = s.sold_unit_price !== null
                ? `<span class="fw-bold text-success">${money(s.sold_unit_price)}</span>`
                : `<span class="text-muted">—</span>`;

            return `
                <tr>
                    <td class="fw-bold">${esc(s.serial_number)}</td>
                    <td class="text-center">
                        <span class="badge badge-light-${s.status_color} fs-8">${esc(s.status_label)}</span>
                    </td>
                    <td>${esc(s.location_name || '—')}</td>
                    <td>${esc(s.department_name || '—')}</td>
                    <td class="text-end">${soldCell}</td>
                    <td>
                        ${s.order_number
                            ? `<span class="badge badge-light-info fs-8">#${esc(s.order_number)}</span>`
                            : `<span class="text-muted">—</span>`}
                    </td>
                    <td class="text-end">
                        <div class="d-flex gap-1 justify-content-end align-items-center">
                            ${actions.join('') || `<span class="text-muted">—</span>`}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        return `
            <h6 class="fw-bold text-gray-800 mb-3 mt-4">
                <i class="bi bi-upc-scan me-2 text-primary"></i>Serial Numbers
                <span class="badge badge-light-secondary ms-2">${serials.length}</span>
            </h6>
            <div class="table-responsive">
                <table class="table table-row-dashed align-middle fs-7 gy-3 mb-0">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                            <th>Serial Number</th>
                            <th class="text-center">Status</th>
                            <th>Location</th>
                            <th>Department</th>
                            <th class="text-end">Sold Price</th>
                            <th>Order</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    function renderActionCluster(variant) {
        const btns = [];
        if (variant.can_edit_pricing) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="manage-serials">
                        <i class="bi bi-gear me-2"></i>Manage Serials
                      </button>`);
        }
        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">
            ${btns.join('')}
        </div>`;
    }

    function bindActionCluster(variant, serials) {
        const actions = document.getElementById('serialVariantDrawerActions');
        actions?.querySelectorAll('[data-drawer-action="manage-serials"]').forEach(el => {
            el.addEventListener('click', () => {
                if (typeof window.openSerialManagementModal === 'function') {
                    window.openSerialManagementModal(variant.id, variant.name);
                } else {
                    window.toastr?.error?.('Serial management is not available.');
                }
            });
        });

        const body = document.getElementById('serialVariantDrawerContent');
        body?.querySelectorAll('[data-serial-action]').forEach(btn => {
            btn.addEventListener('click', () => {
                const serialId = parseInt(btn.dataset.serialId, 10);
                const action   = btn.dataset.serialAction;
                if (!serialId) return;

                if (action === 'pricing')  return openPricingModal(variant, serialId);
                if (action === 'delete')   return deleteSerial(variant, serialId);
                if (['sold', 'reserved', 'returned'].includes(action)) {
                    return changeSerialStatus(variant, serialId, action);
                }
            });
        });
    }

    // ── Per-serial actions ────────────────────────────────

    async function changeSerialStatus(variant, serialId, status) {
        if (!confirm(`Change status to ${status}?`)) return;
        try {
            const json = await req('PUT', `/serials/${serialId}/status`, { status });
            window.toastr?.success?.(json.message || 'Status updated.');
            await reload(variant.id);
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    async function deleteSerial(variant, serialId) {
        if (!confirm('Delete this serial number?')) return;
        try {
            const json = await req('DELETE', `/serials/${serialId}`, null);
            window.toastr?.success?.(json.message || 'Serial deleted.');
            await reload(variant.id);
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    function openPricingModal(variant, serialId) {
        // Delegate to the existing SerialModal if it exposes an openPricing hook
        if (typeof window.SerialModal?.openPricing === 'function') {
            // The existing SerialModal.openPricing expects a serial map — it may
            // need this instance to be preloaded. Fall back to a prompt-style
            // edit if the map isn't populated.
            const serial = currentPayload?.serials?.find(s => s.id === serialId);
            if (serial) {
                // Ensure the serial map is populated in SerialModal
                window.SerialModal.refresh?.();   // no-op if not applicable
            }
            return window.SerialModal.openPricing(serialId);
        }
        window.toastr?.error?.('Pricing editor is not available.');
    }

    // ── Helpers ───────────────────────────────────────────
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) {
        return CURRENCY + Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        });
    }

    window.SerialVariantDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.SerialVariantDrawer;
})();
</script>