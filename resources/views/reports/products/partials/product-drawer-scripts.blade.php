<script>
window.ProductDrawer = (function () {
    'use strict';

    if (!window.PRODUCTS_ROUTES?.productDrawer) {
        console.error('[product-drawer] route not configured');
        return { open: () => {}, reload: () => {}, close: () => {} };
    }

    const URL_SHOW = window.PRODUCTS_ROUTES.productDrawer;
    let currentId = null, payload = null, bs = null;

    function getBs() {
        const el = document.getElementById('productDrawer');
        if (!el) return null;
        if (!window.bootstrap?.Offcanvas) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }
    function close() { getBs()?.hide(); }

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
            payload = json.product;
            render(payload, json.currency);
        } catch (err) {
            console.error('[product-drawer] load failed:', err);
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
            payload = json.product;
            render(payload, json.currency);
        } catch (err) { console.error('[product-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('productDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('productDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('productDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('productDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('productDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('productDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(p, currency) {
        const nameEl = document.getElementById('productDrawerName');
        if (nameEl) nameEl.textContent = `${p.sku || ''} — ${p.name || ''}`;

        document.getElementById('productDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('productDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeader(p)}
            ${renderRollup(p, currency)}
            ${renderMeta(p)}
            ${renderVariants(p, currency)}
        `;

        document.getElementById('productDrawerActions').innerHTML = renderActions(p);
        document.getElementById('productDrawerFooter').classList.remove('d-none');
        bindVariantRows();
    }

    function renderHeader(p) {
        const img = p.image_url
            ? `<img src="${esc(p.image_url)}" class="w-100" style="object-fit:cover;" />`
            : `<div class="symbol-label bg-light-primary text-primary fw-bold fs-2x">${esc((p.name || '?').charAt(0).toUpperCase())}</div>`;

        const active = p.is_active
            ? `<span class="badge badge-light-success">Active</span>`
            : `<span class="badge badge-light-danger">Inactive</span>`;
        const tax = p.is_taxable
            ? `<span class="badge badge-light-primary">Taxable</span>`
            : `<span class="badge badge-light-secondary">Non-taxable</span>`;

        return `
            <div class="d-flex align-items-center gap-4 mb-6">
                <div class="symbol symbol-70px symbol-circle">${img}</div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(p.name || '—')}</h5>
                    <div class="text-muted fs-7 mb-2">${esc(p.category_name || '—')}</div>
                    <div class="d-flex flex-wrap gap-2">
                        ${active}
                        ${tax}
                        <span class="badge badge-light-info">${esc(p.inventory_strategy || 'quantity')}</span>
                        <span class="badge badge-light-primary">${p.variant_count} {{ __('auth.variants') }}</span>
                    </div>
                </div>
            </div>
        `;
    }

    function renderRollup(p, currency) {
        const money = (n) => `${currency} ${fmt(n)}`;
        return `
            <div class="row g-3 mb-5">
                <div class="col-md-4"><div class="card card-dashed bg-light-info"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('auth.total_stock') }}</div>
                    <div class="fs-4 fw-bold text-info">${fmt(p.total_stock)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-dashed bg-light"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('auth.cost_value') }}</div>
                    <div class="fs-4 fw-bold">${money(p.total_cost_value)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-dashed bg-light-success"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('auth.revenue_value') }}</div>
                    <div class="fs-4 fw-bold text-success">${money(p.total_revenue_value)}</div>
                </div></div></div>
            </div>
        `;
    }

    function renderMeta(p) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Details</h6>
            <div class="bg-light rounded-3 p-4 mb-5">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">SKU</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(p.sku || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Type</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(p.type || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created by</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(p.created_by || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(p.created_at || '—')}</div>
                    </div>
                    ${p.description ? `
                        <div class="col-12">
                            <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Description</div>
                            <div class="fw-semibold text-gray-800 fs-7">${esc(p.description)}</div>
                        </div>` : ''}
                </div>
            </div>
        `;
    }

    function renderVariants(p, currency) {
        const rows = p.variants || [];
        const header = `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-boxes me-2 text-primary"></i>{{ __('auth.variants') }}
                <span class="badge badge-light-secondary ms-2">${rows.length}</span>
            </h6>`;

        if (!rows.length) {
            return header + `<div class="alert alert-light-warning border border-warning border-dashed py-3 fs-7 mb-5">{{ __('auth.no_variants_yet') }}</div>`;
        }

        const body = rows.map(v => `
            <tr data-variant-id="${v.id}" style="cursor:pointer;">
                <td class="ps-3">
                    <div class="fw-bold text-gray-800">${esc(v.name || '—')}</div>
                    <small class="text-muted">${esc(v.sku || '')}</small>
                </td>
                <td class="text-end"><span class="badge badge-light-primary">${fmt(v.quantity)}</span></td>
                <td class="text-end">${currency} ${fmt(v.cost_price)}</td>
                <td class="text-end">${currency} ${fmt(v.selling_price)}</td>
                <td class="text-end">
                    <span class="badge badge-light-${v.margin_pct >= 50 ? 'success' : v.margin_pct >= 30 ? 'primary' : v.margin_pct >= 10 ? 'warning' : 'danger'}">
                        ${v.margin_pct.toFixed(1)}%
                    </span>
                </td>
                <td class="text-end text-muted"><i class="bi bi-chevron-right"></i></td>
            </tr>
        `).join('');

        return header + `
            <div class="table-responsive mb-4">
                <table class="table table-row-dashed align-middle fs-7 mb-0">
                    <thead class="border-bottom border-gray-200 fs-8 fw-bold text-uppercase bg-light">
                        <tr>
                            <th class="ps-3">{{ __('auth._variant') }}</th>
                            <th class="text-end">{{ __('auth.stock') }}</th>
                            <th class="text-end">{{ __('auth.cost') }}</th>
                            <th class="text-end">{{ __('auth.price') }}</th>
                            <th class="text-end">{{ __('auth.margin') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>${body}</tbody>
                </table>
            </div>
        `;
    }

    function renderActions(p) {
        return `
            <button type="button" class="btn btn-sm btn-light" data-drawer-action="copy-sku">
                <i class="bi bi-clipboard me-2"></i>Copy SKU
            </button>
        `;
    }

    function bindVariantRows() {
        document.querySelectorAll('#productDrawerContent tr[data-variant-id]').forEach(tr => {
            tr.addEventListener('click', (e) => {
                if (e.target.closest('button, a, input, select')) return;
                const id = parseInt(tr.dataset.variantId, 10);
                if (id && window.VariantDrawer) window.VariantDrawer.open(id);
            });
        });

        document.querySelectorAll('#productDrawerActions [data-drawer-action="copy-sku"]').forEach(el => {
            el.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(payload?.sku || '');
                    window.toastr?.success?.('SKU copied.');
                } catch (e) {
                    window.toastr?.error?.('Could not copy.');
                }
            });
        });
    }

    function fmt(n) { return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.ProductDrawer = {
        open, reload, close,
        getCurrent: () => ({ id: currentId, payload }),
    };
    return window.ProductDrawer;
})();
</script>