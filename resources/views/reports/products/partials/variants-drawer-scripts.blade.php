<script>
window.VariantDrawer = (function () {
    'use strict';

    if (!window.PRODUCTS_ROUTES?.variantDrawer) {
        console.error('[variant-drawer] route not configured');
        return { open: () => {}, reload: () => {}, close: () => {} };
    }

    const URL_SHOW = window.PRODUCTS_ROUTES.variantDrawer;
    let currentId = null, payload = null, bs = null;

    function getBs() {
        const el = document.getElementById('variantDrawer');
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
            payload = json.variant;
            render(payload, json.currency);
        } catch (err) {
            console.error('[variant-drawer] load failed:', err);
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
            payload = json.variant;
            render(payload, json.currency);
        } catch (err) { console.error('[variant-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('variantDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('variantDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('variantDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('variantDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('variantDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('variantDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(v, currency) {
        const nameEl = document.getElementById('variantDrawerName');
        if (nameEl) nameEl.textContent = `${v.sku || ''} — ${v.name || ''}`;

        document.getElementById('variantDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('variantDrawerContent');
        body.classList.remove('d-none');

        const img = v.image_url
            ? `<img src="${esc(v.image_url)}" class="symbol-label" style="object-fit:cover;" />`
            : `<div class="symbol-label bg-light-primary text-primary fw-bold fs-2x">${esc((v.name || '?').charAt(0).toUpperCase())}</div>`;

        const stockBadge = v.is_out_of_stock
            ? `<span class="badge badge-light-danger">Out of stock</span>`
            : v.is_low_stock
                ? `<span class="badge badge-light-warning">Low stock</span>`
                : `<span class="badge badge-light-success">In stock</span>`;

        const activeBadge = v.is_active
            ? `<span class="badge badge-light-success">Active</span>`
            : `<span class="badge badge-light-danger">Inactive</span>`;

        const taxBadge = v.is_taxable
            ? `<span class="badge badge-light-primary">Taxable</span>`
            : `<span class="badge badge-light-secondary">Non-taxable</span>`;

        const price = (n) => `${currency} ${fmt(n)}`;
        const pct   = (n) => `${Number(n || 0).toFixed(2)}%`;

        body.innerHTML = `
            <div class="d-flex align-items-center gap-4 mb-6">
                <div class="symbol symbol-70px symbol-circle">
                    ${img}
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(v.name || '—')}</h5>
                    <div class="text-muted fs-7 mb-2">${esc(v.product_name || '—')}${v.category_name ? ' · ' + esc(v.category_name) : ''}</div>
                    <div class="d-flex flex-wrap gap-2">
                        ${activeBadge}
                        ${taxBadge}
                        ${stockBadge}
                        ${v.inventory_strategy ? `<span class="badge badge-light-info">${esc(v.inventory_strategy)}</span>` : ''}
                    </div>
                </div>
            </div>

            ${renderMoneyBlock(v, price)}
            ${renderCostBlock(v, price)}
            ${renderPricingBlock(v, price, pct)}
            ${renderInventoryBlock(v, price)}
            ${renderMetaBlock(v)}
        `;

        document.getElementById('variantDrawerActions').innerHTML = renderActions(v);
        document.getElementById('variantDrawerFooter').classList.remove('d-none');
        bindActions(v);
    }

    function section(title, icon, inner) {
        return `<h6 class="fw-bold text-gray-800 mb-3"><i class="bi ${icon} me-2 text-primary"></i>${esc(title)}</h6>
                <div class="bg-light rounded-3 p-4 mb-5">${inner}</div>`;
    }
    function kv(rows) {
        return `<div class="row g-3">${rows.map(([l, val]) => `
            <div class="col-6 col-md-3">
                <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(l)}</div>
                <div class="fw-semibold text-gray-800 fs-7">${val}</div>
            </div>`).join('')}</div>`;
    }

    function renderMoneyBlock(v, price) {
        return `
            <div class="row g-3 mb-5">
                <div class="col-md-4"><div class="card card-dashed bg-light-success"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">Selling Price</div>
                    <div class="fs-4 fw-bold text-success">${price(v.selling_price)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-dashed bg-light"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">Grand Cost</div>
                    <div class="fs-4 fw-bold">${price(v.grand_total_cost_price)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-dashed bg-light-${v.profit_per_unit >= 0 ? 'info' : 'danger'}"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">Profit / Unit</div>
                    <div class="fs-4 fw-bold text-${v.profit_per_unit >= 0 ? 'info' : 'danger'}">${price(v.profit_per_unit)}</div>
                </div></div></div>
            </div>`;
    }

    function renderCostBlock(v, price) {
        return section('Cost Breakdown', 'bi-calculator', kv([
            ['Supplier cost', price(v.supplier_cost_price)],
            ['Shipping',      price(v.total_shipping_cost)],
            ['URA taxes',     price(v.ura_taxes_applied)],
            ['Additional',    price(v.additional_expenses)],
            ['Grand total',   `<span class="text-primary fw-bold">${price(v.grand_total_cost_price)}</span>`],
        ]));
    }

    function renderPricingBlock(v, price, pct) {
        return section('Pricing', 'bi-tag', kv([
            ['Selling price',    price(v.selling_price)],
            ['Discount %',       pct(v.discount_percentage)],
            ['Discounted price', price(v.discount_selling_price)],
            ['Markup %',         pct(v.markup_percentage)],
            ['Profit margin %',  pct(v.profit_margin_pct)],
        ]));
    }

    function renderInventoryBlock(v, price) {
        return section('Inventory', 'bi-box-seam', kv([
            ['At hand',         fmt(v.overal_quantity_at_hand)],
            ['Low-stock level', fmt(v.low_stock_level)],
            ['Stock value',     price(v.stock_cost_value)],
            ['Revenue value',   price(v.stock_revenue_value)],
            ['Unit',            esc(v.unit_name || '—')],
            ['Weight',          v.weight != null ? `${fmt(v.weight)}` : '—'],
        ]));
    }

    function renderMetaBlock(v) {
        return section('Audit', 'bi-clock-history', kv([
            ['Created by', v.created_by || '—'],
            ['Created at', v.created_at || '—'],
            ['Updated at', v.updated_at || '—'],
            ['Barcode',    v.barcode || '—'],
        ]));
    }

    function renderActions(v) {
        return `
            <span class="text-muted fs-8 flex-grow-1">${esc(v.sku || '')}</span>
            <button type="button" class="btn btn-sm btn-light" data-drawer-action="copy-sku">
                <i class="bi bi-clipboard me-2"></i>Copy SKU
            </button>
        `;
    }

    function bindActions(v) {
        const actions = document.getElementById('variantDrawerActions');
        if (!actions) return;
        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', async () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'copy-sku') {
                    try {
                        await navigator.clipboard.writeText(v.sku || '');
                        window.toastr?.success?.('SKU copied.');
                    } catch (e) { window.toastr?.error?.('Could not copy.'); }
                }
            });
        });
    }

    function fmt(n) {
        return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.VariantDrawer = {
        open, reload, close,
        getCurrent: () => ({ id: currentId, payload }),
    };
    return window.VariantDrawer;
})();
</script>