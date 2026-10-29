<script>
window.CategoryDrawer = (function () {
    'use strict';

    if (!window.PRODUCTS_ROUTES?.categoryDrawer) {
        console.error('[category-drawer] route not configured');
        return { open: () => {}, reload: () => {}, close: () => {} };
    }

    const URL_SHOW = window.PRODUCTS_ROUTES.categoryDrawer;
    let currentId = null, payload = null, bs = null;

    function getBs() {
        const el = document.getElementById('categoryDrawer');
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
            payload = json.category;
            render(payload, json.currency);
        } catch (err) {
            console.error('[category-drawer] load failed:', err);
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
            payload = json.category;
            render(payload, json.currency);
        } catch (err) { console.error('[category-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('categoryDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('categoryDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('categoryDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('categoryDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('categoryDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('categoryDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(c, currency) {
        const nameEl = document.getElementById('categoryDrawerName');
        if (nameEl) nameEl.textContent = c.name || '';

        document.getElementById('categoryDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('categoryDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeader(c)}
            ${renderRollup(c, currency)}
            ${renderMeta(c)}
            ${renderProducts(c, currency)}
        `;

        document.getElementById('categoryDrawerActions').innerHTML = renderActions(c);
        document.getElementById('categoryDrawerFooter').classList.remove('d-none');
        bindProductRows();
    }

    function renderHeader(c) {
        const img = c.image_url
            ? `<img src="${esc(c.image_url)}" class="w-100" style="object-fit:cover;" />`
            : `<div class="symbol-label bg-light-primary text-primary fw-bold fs-2x">${esc((c.name || '?').charAt(0).toUpperCase())}</div>`;

        const active = c.is_active
            ? `<span class="badge badge-light-success">Active</span>`
            : `<span class="badge badge-light-danger">Inactive</span>`;

        return `
            <div class="d-flex align-items-center gap-4 mb-6">
                <div class="symbol symbol-70px symbol-circle">${img}</div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(c.name || '—')}</h5>
                    <div class="text-muted fs-7 mb-2">${esc(c.parent_name || '—')}</div>
                    <div class="d-flex flex-wrap gap-2">
                        ${active}
                        <span class="badge badge-light-primary">${c.product_count} {{ __('auth.products') }}</span>
                        <span class="badge badge-light-info">${c.variant_count} {{ __('auth.variants') }}</span>
                        <span class="badge badge-light-${c.margin_percentage >= 50 ? 'success' : c.margin_percentage >= 30 ? 'primary' : c.margin_percentage >= 10 ? 'warning' : 'danger'}">
                            ${Number(c.margin_percentage).toFixed(1)}% {{ __('auth.margin') }}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    function renderRollup(c, currency) {
        const money = (n) => `${currency} ${fmt(n)}`;
        return `
            <div class="row g-3 mb-5">
                <div class="col-md-3"><div class="card card-dashed bg-light-info"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('auth.total_stock') }}</div>
                    <div class="fs-4 fw-bold text-info">${fmt(c.total_stock)}</div>
                </div></div></div>
                <div class="col-md-3"><div class="card card-dashed bg-light"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('auth.cost_value') }}</div>
                    <div class="fs-4 fw-bold">${money(c.total_cost_value)}</div>
                </div></div></div>
                <div class="col-md-3"><div class="card card-dashed bg-light-success"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('auth.revenue_value') }}</div>
                    <div class="fs-4 fw-bold text-success">${money(c.total_revenue_value)}</div>
                </div></div></div>
                <div class="col-md-3"><div class="card card-dashed bg-light-${c.total_margin >= 0 ? 'primary' : 'danger'}"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('auth.total_margin') }}</div>
                    <div class="fs-4 fw-bold text-${c.total_margin >= 0 ? 'primary' : 'danger'}">${money(c.total_margin)}</div>
                </div></div></div>
            </div>
        `;
    }

    function renderMeta(c) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Details</h6>
            <div class="bg-light rounded-3 p-4 mb-5">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('accounting.parent_category') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(c.parent_name || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created by</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(c.created_by || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(c.created_at || '—')}</div>
                    </div>
                    ${c.description ? `
                        <div class="col-12">
                            <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Description</div>
                            <div class="fw-semibold text-gray-800 fs-7">${esc(c.description)}</div>
                        </div>` : ''}
                </div>
            </div>
        `;
    }

    function renderProducts(c, currency) {
        const rows = c.products || [];
        const header = `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-box-seam me-2 text-primary"></i>{{ __('auth.products') }}
                <span class="badge badge-light-secondary ms-2">${rows.length}</span>
            </h6>`;

        if (!rows.length) {
            return header + `<div class="alert alert-light-warning border border-warning border-dashed py-3 fs-7 mb-5">{{ __('auth.no_products_found') }}</div>`;
        }

        const body = rows.map(p => {
            const marginColor = p.margin_pct >= 50 ? 'success' : p.margin_pct >= 30 ? 'primary' : p.margin_pct >= 10 ? 'warning' : 'danger';
            return `
                <tr data-product-id="${p.id}" style="cursor:pointer;" tabindex="0">
                    <td class="ps-3">
                        <div class="d-flex align-items-center">
                            ${p.image_url ? `<div class="symbol symbol-35px me-2"><img src="${esc(p.image_url)}" class="rounded" style="object-fit:cover;" /></div>` : ''}
                            <div>
                                <div class="fw-bold text-gray-800">${esc(p.name || '—')}</div>
                                <small class="text-muted">${esc(p.sku || '')}</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center"><span class="badge badge-light-info">${fmt(p.variant_count)}</span></td>
                    <td class="text-end"><span class="badge badge-light-primary">${fmt(p.total_stock)}</span></td>
                    <td class="text-end">${currency} ${fmt(p.total_cost_value)}</td>
                    <td class="text-end">${currency} ${fmt(p.total_revenue_value)}</td>
                    <td class="text-end"><span class="text-${p.total_margin >= 0 ? 'success' : 'danger'} fw-bold">${currency} ${fmt(p.total_margin)}</span></td>
                    <td class="text-end"><span class="badge badge-light-${marginColor}">${Number(p.margin_pct).toFixed(1)}%</span></td>
                    <td class="text-end text-muted"><i class="bi bi-chevron-right"></i></td>
                </tr>`;
        }).join('');

        return header + `
            <div class="table-responsive mb-4">
                <table class="table table-row-dashed align-middle fs-7 mb-0">
                    <thead class="border-bottom border-gray-200 fs-8 fw-bold text-uppercase bg-light">
                        <tr>
                            <th class="ps-3">{{ __('auth._product') }}</th>
                            <th class="text-center">{{ __('auth.variants') }}</th>
                            <th class="text-end">{{ __('auth.stock') }}</th>
                            <th class="text-end">{{ __('auth.cost_value') }}</th>
                            <th class="text-end">{{ __('auth.revenue_value') }}</th>
                            <th class="text-end">{{ __('auth.total_margin') }}</th>
                            <th class="text-end">{{ __('auth.margin') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>${body}</tbody>
                </table>
            </div>
        `;
    }

    function renderActions(c) {
        return `
            <button type="button" class="btn btn-sm btn-light" data-drawer-action="copy-name">
                <i class="bi bi-clipboard me-2"></i>Copy name
            </button>
        `;
    }

    function bindProductRows() {
        document.querySelectorAll('#categoryDrawerContent tr[data-product-id]').forEach(tr => {
            tr.addEventListener('click', (e) => {
                if (e.target.closest('button, a, input, select')) return;
                const id = parseInt(tr.dataset.productId, 10);
                if (id && window.ProductDrawer) window.ProductDrawer.open(id);
            });
            tr.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                e.preventDefault();
                const id = parseInt(tr.dataset.productId, 10);
                if (id && window.ProductDrawer) window.ProductDrawer.open(id);
            });
        });

        document.querySelectorAll('#categoryDrawerActions [data-drawer-action="copy-name"]').forEach(el => {
            el.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(payload?.name || '');
                    window.toastr?.success?.('Name copied.');
                } catch (e) { window.toastr?.error?.('Could not copy.'); }
            });
        });
    }

    function fmt(n) { return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.CategoryDrawer = {
        open, reload, close,
        getCurrent: () => ({ id: currentId, payload }),
    };
    return window.CategoryDrawer;
})();
</script>