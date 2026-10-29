<script>
window.StrategyDrawer = (function () {
    'use strict';

    if (!window.PRODUCTS_ROUTES?.strategyDetail) {
        console.error('[strategy-drawer] route not configured');
        return { open: () => {}, close: () => {} };
    }

    const URL_SHOW = window.PRODUCTS_ROUTES.strategyDetail;
    let currentId = null, payload = null, bs = null;

    function getBs() {
        const el = document.getElementById('strategyDrawer');
        if (!el) return null;
        if (!window.bootstrap?.Offcanvas) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }
    function close() { getBs()?.hide(); }

    async function open(id) {
        currentId = id;
        const inst = getBs();
        if (!inst) {
            console.error('[strategy-drawer] #strategyDrawer not found');
            return;
        }
        showSkeleton(); inst.show();
        try {
            const res = await fetch(URL_SHOW.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!res.ok && !json.success) throw new Error(json.message || 'Failed');
            payload = json;
            render(json);
        } catch (err) {
            console.error('[strategy-drawer] load failed:', err);
            renderError(err.message);
        }
    }

    function showSkeleton() {
        document.getElementById('strategyDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('strategyDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('strategyDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('strategyDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('strategyDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('strategyDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(data) {
        const nameEl = document.getElementById('strategyDrawerName');
        if (nameEl) nameEl.textContent = `${data.product_sku || ''} — ${data.product_name || ''}`;

        document.getElementById('strategyDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('strategyDrawerContent');
        body.classList.remove('d-none');

        const currency = payload.currency || '';

        body.innerHTML = `
            ${renderHeader(data)}
            ${renderSummary(data, currency)}
            ${renderStrategyDetails(data, currency)}
        `;

        document.getElementById('strategyDrawerActions').innerHTML = renderActions(data);
        document.getElementById('strategyDrawerFooter').classList.remove('d-none');
        bindActions();
    }

    function renderHeader(d) {
        const img = d.product?.image_url
            ? `<img src="${esc(d.product.image_url)}" class="w-100" style="object-fit:cover;" />`
            : `<div class="symbol-label bg-light-primary text-primary fw-bold fs-2x">${esc((d.product_name || '?').charAt(0).toUpperCase())}</div>`;

        return `
            <div class="d-flex align-items-center gap-4 mb-6">
                <div class="symbol symbol-70px symbol-circle">${img}</div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(d.product_name || '—')}</h5>
                    <div class="text-muted fs-7 mb-2">SKU: ${esc(d.product_sku || '—')}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light-${d.status_color || 'secondary'}">${esc(d.status_label || '—')}</span>
                        <span class="badge badge-light-${d.strategy_color || 'secondary'}">
                            <i class="ki-duotone ${d.strategy_icon || 'ki-box'} fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                            ${esc(d.strategy_label || '')}
                        </span>
                        <span class="badge badge-light-${d.is_single_shop ? 'primary' : 'info'}">
                            ${d.is_single_shop ? 'Single Shop' : 'Multi-Shop'}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    function renderSummary(d, currency) {
        const variants = d.variants || [];
        const money = (n) => `${currency} ${fmt(n)}`;
        return `
            <div class="row g-3 mb-5">
                <div class="col-md-4"><div class="card card-dashed bg-light-primary"><div class="card-body text-center">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('pagination.variants') }}</div>
                    <div class="fs-4 fw-bold text-primary">${variants.length}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-dashed bg-light-success"><div class="card-body text-center">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('pagination.total_stock') }}</div>
                    <div class="fs-4 fw-bold text-success">${fmt(d.total_stock)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-dashed bg-light-info"><div class="card-body text-center">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('pagination.total_value') }}</div>
                    <div class="fs-4 fw-bold text-info">${money(d.total_value)}</div>
                </div></div></div>
            </div>
        `;
    }

    function renderStrategyDetails(d, currency) {
        const strategy = d.strategy || 'quantity';
        const variants = d.variants || [];

        if (strategy === 'quantity') return renderQuantity(variants, currency);
        if (strategy === 'batch')    return renderBatch(variants, currency);
        if (strategy === 'serial')   return renderSerial(variants, currency);
        if (strategy === 'recipe')   return renderRecipe(variants, currency);
        return '';
    }

    function renderQuantity(variants, currency) {
        if (!variants.length) return emptyAlert();
        return `
            <h6 class="fw-bold text-gray-800 mb-3"><i class="bi bi-boxes me-2 text-primary"></i>{{ __('pagination.variant_stock_details') }}</h6>
            <div class="table-responsive mb-4">
                <table class="table table-row-dashed align-middle fs-7 mb-0">
                    <thead class="bg-light fs-8 fw-bold text-uppercase">
                        <tr>
                            <th class="ps-3">{{ __('pagination.variant') }}</th>
                            <th>SKU</th>
                            <th class="text-center">{{ __('pagination.stock') }}</th>
                            <th class="text-end">{{ __('pagination.cost_price') }}</th>
                            <th class="text-end">{{ __('pagination.selling_price') }}</th>
                            <th class="text-end">{{ __('pagination.value') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${variants.map(v => `
                            <tr>
                                <td class="ps-3 fw-bold">${esc(v.variant_name)}</td>
                                <td class="text-muted">${esc(v.variant_sku)}</td>
                                <td class="text-center ${v.stock == 0 ? 'text-danger' : 'text-success'} fw-bold">${fmt(v.stock)}</td>
                                <td class="text-end">${currency} ${fmt(v.cost_price)}</td>
                                <td class="text-end">${currency} ${fmt(v.selling_price)}</td>
                                <td class="text-end fw-bold">${currency} ${fmt(v.value)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    function renderBatch(variants, currency) {
        if (!variants.length) return emptyAlert();
        return variants.map(v => {
            const batches = v.details || [];
            return `
                <div class="card card-dashed bg-light-info mb-3">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <strong>${esc(v.variant_name)}</strong>
                            <span class="badge badge-light-dark">${esc(v.variant_sku)}</span>
                            <span class="badge badge-light-success">{{ __('pagination.total_stock') }}: ${fmt(v.stock)}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        ${!batches.length
                            ? `<div class="text-center text-muted py-4 fs-7">{{ __('pagination.no_batches') }}</div>`
                            : `<div class="table-responsive">
                                <table class="table table-row-dashed fs-7 mb-0">
                                    <thead class="bg-light fs-8 fw-bold text-uppercase">
                                        <tr>
                                            <th class="ps-3">{{ __('pagination.batch_number') }}</th>
                                            <th class="text-center">{{ __('pagination.quantity') }}</th>
                                            <th>{{ __('pagination.expiry_date') }}</th>
                                            <th class="text-end">{{ __('pagination.value') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${batches.map(b => {
                                            const expired = b.expiry_date && new Date(b.expiry_date) < new Date();
                                            return `
                                                <tr>
                                                    <td class="ps-3"><span class="badge badge-light-dark">${esc(b.batch_number)}</span></td>
                                                    <td class="text-center">${fmt(b.quantity)}</td>
                                                    <td class="${expired ? 'text-danger' : ''}">${esc(b.expiry_date || '—')}</td>
                                                    <td class="text-end fw-bold">${currency} ${fmt(b.value)}</td>
                                                </tr>`;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>`}
                    </div>
                </div>`;
        }).join('');
    }

    function renderSerial(variants, currency) {
        if (!variants.length) return emptyAlert();
        return variants.map(v => {
            const serials = v.details || [];
            return `
                <div class="card card-dashed bg-light-warning mb-3">
                    <div class="card-header">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <strong>${esc(v.variant_name)}</strong>
                            <span class="badge badge-light-dark">${esc(v.variant_sku)}</span>
                            <span class="badge badge-light-success">{{ __('pagination.total_stock') }}: ${fmt(v.stock)}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        ${!serials.length
                            ? `<div class="text-center text-muted py-4 fs-7">{{ __('pagination.no_serial_numbers') }}</div>`
                            : `<div class="table-responsive">
                                <table class="table table-row-dashed fs-7 mb-0">
                                    <thead class="bg-light fs-8 fw-bold text-uppercase">
                                        <tr>
                                            <th class="ps-3">{{ __('pagination.serial_number') }}</th>
                                            <th>{{ __('pagination.location') }}</th>
                                            <th>{{ __('pagination.expiry_date') }}</th>
                                            <th class="text-end">{{ __('pagination.value') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${serials.map(s => `
                                            <tr>
                                                <td class="ps-3"><span class="badge badge-light-dark">${esc(s.serial_number)}</span></td>
                                                <td>${esc(s.location || '—')}</td>
                                                <td>${esc(s.expiry_date || '—')}</td>
                                                <td class="text-end fw-bold">${currency} ${fmt(s.value)}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>`}
                    </div>
                </div>`;
        }).join('');
    }

    function renderRecipe(variants, currency) {
        const first = variants[0];
        const d = first?.details;
        if (!d || !d.ingredients || !d.ingredients.length) return emptyAlert();

        return `
            <div class="card card-dashed bg-light-success mb-3">
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="text-muted fs-8 text-uppercase mb-1">{{ __('pagination.can_produce') }}</div>
                            <div class="fs-4 fw-bold ${d.can_produce ? 'text-success' : 'text-warning'}">${d.can_produce ? 'Yes' : 'No'}</div>
                        </div>
                        <div class="col-md-6">
                            <div class="text-muted fs-8 text-uppercase mb-1">{{ __('pagination.max_producible') }}</div>
                            <div class="fs-4 fw-bold text-primary">${fmt(d.max_producible)} units</div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-row-dashed fs-7 mb-0">
                            <thead class="bg-light fs-8 fw-bold text-uppercase">
                                <tr>
                                    <th class="ps-3">{{ __('pagination.ingredient') }}</th>
                                    <th>SKU</th>
                                    <th class="text-center">{{ __('pagination.required_per_unit') }}</th>
                                    <th class="text-center">{{ __('pagination.available_stock') }}</th>
                                    <th class="text-center">{{ __('pagination.can_produce') }}</th>
                                    <th class="text-end">{{ __('pagination.cost') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${d.ingredients.map(ing => `
                                    <tr>
                                        <td class="ps-3 fw-bold">${esc(ing.ingredient_name)}</td>
                                        <td class="text-muted">${esc(ing.ingredient_sku)}</td>
                                        <td class="text-center">${fmt(ing.quantity_required)}</td>
                                        <td class="text-center ${ing.available_stock == 0 ? 'text-danger' : 'text-success'}">${fmt(ing.available_stock)}</td>
                                        <td class="text-center ${ing.producible > 0 ? 'text-success' : 'text-danger'}">${fmt(ing.producible)}</td>
                                        <td class="text-end">${currency} ${fmt(ing.cost_price)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    }

    function emptyAlert() {
        return `<div class="alert alert-light-warning border border-warning border-dashed py-3 fs-7 mb-5">{{ __('pagination.no_data_available') }}</div>`;
    }

    function renderActions(d) {
        return `
            <button type="button" class="btn btn-sm btn-light" data-drawer-action="copy-sku">
                <i class="bi bi-clipboard me-2"></i>Copy SKU
            </button>
        `;
    }

    function bindActions() {
        document.querySelectorAll('#strategyDrawerActions [data-drawer-action="copy-sku"]').forEach(el => {
            el.addEventListener('click', async () => {
                try {
                    await navigator.clipboard.writeText(payload?.product_sku || '');
                    window.toastr?.success?.('SKU copied.');
                } catch (e) { window.toastr?.error?.('Could not copy.'); }
            });
        });
    }

    function fmt(n) { return (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.StrategyDrawer = { open, close, getCurrent: () => ({ id: currentId, payload }) };
    return window.StrategyDrawer;
})();
</script>