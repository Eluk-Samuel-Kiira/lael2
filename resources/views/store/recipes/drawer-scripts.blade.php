<script>
window.RecipeVariantDrawer = (function () {
    'use strict';

    const URL_SHOW = @json(route('recipes.variant.drawer-data', ['variant' => '__ID__']));
    const CURRENCY = @json(currency_symbol());
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('recipeVariantDrawer');
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
            render();
        } catch (err) {
            console.error('[recipe-drawer] load failed:', err);
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
            render();
        } catch (err) { console.error('[recipe-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('recipeVariantDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('recipeVariantDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('recipeVariantDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('recipeVariantDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('recipeVariantDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('recipeVariantDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render() {
        const { variant, recipe, inherits_from_product, serials, serial_summary } = currentPayload;

        currentId = variant.id;
        const nameEl = document.getElementById('recipeVariantDrawerName');
        if (nameEl) nameEl.textContent = (variant.product_name || '') + ' — ' + variant.name;

        document.getElementById('recipeVariantDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('recipeVariantDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(variant, inherits_from_product, recipe)}
            ${renderRecipeBlock(recipe)}
            ${renderSerialsBlock(serials || [], serial_summary, variant)}
        `;

        document.getElementById('recipeVariantDrawerActions').innerHTML = renderActionCluster(variant);
        document.getElementById('recipeVariantDrawerFooter').classList.remove('d-none');
        bindActions(variant);
    }

    // ── Render: variant header ───────────────────────────
    function renderHeaderBlock(v, inherits, recipe) {
        const badge = inherits
            ? `<span class="badge badge-light-info fs-8">Inherits product recipe</span>`
            : (recipe
                ? `<span class="badge badge-light-success fs-8">Variant recipe</span>`
                : `<span class="badge badge-light-warning fs-8">No recipe</span>`);

        return `
            <div class="mb-6">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                    <div>
                        <h5 class="fw-bold text-gray-800 mb-1">${esc(v.name)}</h5>
                        <div class="text-muted fs-7">
                            ${esc(v.product_name || '')} <span class="mx-2">·</span>
                            <code>${esc(v.sku || '—')}</code>
                        </div>
                    </div>
                    ${badge}
                </div>
                <div class="d-flex flex-wrap gap-4 fs-7 mt-3">
                    <div>
                        <span class="text-muted">Selling Price:</span>
                        <span class="fw-bold text-primary">${money(v.selling_price)}</span>
                    </div>
                    ${v.weight_unit ? `
                        <div>
                            <span class="text-muted">Unit:</span>
                            <span class="fw-bold text-gray-800">${esc(v.weight_unit)}</span>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    }

    // ── Render: recipe ────────────────────────────────────
    function renderRecipeBlock(recipe) {
        if (!recipe) {
            return `
                <h6 class="fw-bold text-gray-800 mb-3 mt-4">
                    <i class="bi bi-list-check me-2 text-warning"></i>Ingredients
                </h6>
                <div class="alert alert-light-warning border border-warning border-dashed py-3 fs-7">
                    No recipe defined for this variant.
                </div>
            `;
        }

        if (!recipe.ingredients || recipe.ingredients.length === 0) {
            return `
                <h6 class="fw-bold text-gray-800 mb-3 mt-4">
                    <i class="bi bi-list-check me-2 text-warning"></i>Ingredients
                </h6>
                <div class="alert alert-light-warning border border-warning border-dashed py-3 fs-7">
                    Recipe exists but has no ingredients.
                </div>
            `;
        }

        const cost   = Number(recipe.computed_total_cost      || 0);
        const sell   = Number(recipe.computed_total_selling   || 0);
        const profit = Number(recipe.computed_profit_per_unit || 0);
        const custom = Number(recipe.computed_custom_count    || 0);
        const count  = recipe.ingredients.length;
        const profitColor = profit >= 0 ? 'success' : 'danger';

        return `
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card bg-light-primary">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 fw-semibold d-block text-uppercase">Ingredients</span>
                            <span class="fs-4 fw-bold text-primary">${count}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-warning">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 fw-semibold d-block text-uppercase">Total Cost</span>
                            <span class="fs-5 fw-bold text-warning">${money(cost)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-info">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 fw-semibold d-block text-uppercase">Selling Value</span>
                            <span class="fs-5 fw-bold text-info">${money(sell)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-${profitColor}">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 fw-semibold d-block text-uppercase">Profit</span>
                            <span class="fs-5 fw-bold text-${profitColor}">${money(profit)}</span>
                        </div>
                    </div>
                </div>
            </div>

            ${custom > 0 ? `
                <div class="alert alert-light-warning border border-warning border-dashed py-2 px-3 mb-4 fs-7">
                    <i class="bi bi-pencil-square me-2"></i>
                    ${custom} ingredient${custom === 1 ? '' : 's'} use custom pricing.
                </div>
            ` : ''}

            <h6 class="fw-bold text-gray-800 mb-3 mt-4">
                <i class="bi bi-list-check me-2 text-warning"></i>Ingredients
                <span class="badge badge-light-secondary ms-2">${count}</span>
            </h6>
            <div class="table-responsive mb-6">
                <table class="table table-row-dashed align-middle fs-7 gy-3 mb-0">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                            <th>Ingredient</th>
                            <th class="text-center min-w-90px">Required</th>
                            <th class="min-w-80px">Unit</th>
                            <th class="text-end min-w-100px">Cost</th>
                            <th class="text-end min-w-100px">Sell</th>
                            <th class="text-end min-w-110px">Line Cost</th>
                            <th class="text-center min-w-80px">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${recipe.ingredients.map(ing => `
                            <tr>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-gray-800">${esc(ing.name || '—')}</span>
                                        <span class="text-muted fs-8">
                                            ${esc(ing.sku || '—')}${ing.product_name ? ' · ' + esc(ing.product_name) : ''}
                                        </span>
                                    </div>
                                </td>
                                <td class="text-center fw-bold">${Number(ing.quantity_required).toFixed(4)}</td>
                                <td>${esc(ing.unit_name || '—')}</td>
                                <td class="text-end">${money(ing.unit_cost)}</td>
                                <td class="text-end text-primary">${money(ing.unit_sell)}</td>
                                <td class="text-end fw-bold">${money(ing.line_cost)}</td>
                                <td class="text-center">
                                    <span class="badge badge-light-${ing.source === 'ingredient' ? 'success' : ing.source === 'item' ? 'warning' : 'secondary'} fs-8">
                                        ${esc(ing.source || 'variant')}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                    <tfoot class="border-top">
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Total ingredient cost:</td>
                            <td class="text-end fw-bolder text-gray-900">${money(cost)}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
    }

    // ── Render: serials ───────────────────────────────────
    function renderSerialsBlock(serials, summary, variant) {
        if (!summary) return '';

        const badges = [
            ['total',     'secondary', 'Total'],
            ['available', 'success',   'Available'],
            ['reserved',  'warning',   'Reserved'],
            ['sold',      'danger',    'Sold'],
            ['returned',  'info',      'Returned'],
            ['lost',      'secondary', 'Lost'],
            ['damaged',   'dark',      'Damaged'],
        ].filter(([k]) => Number(summary[k] || 0) > 0);

        if (serials.length === 0) {
            return `
                <h6 class="fw-bold text-gray-800 mb-3 mt-6">
                    <i class="bi bi-upc-scan me-2 text-primary"></i>Serial Numbers
                </h6>
                <div class="alert alert-light-warning border border-warning border-dashed py-3 fs-7">
                    No serial numbers for this variant yet.
                </div>
            `;
        }

        const rows = serials.map(s => {
            const actions = [];

            if (s.status === 'available' && variant.can_edit) {
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

            if (s.status === 'sold' && variant.can_edit) {
                actions.push(`
                    <button class="btn btn-sm btn-icon btn-light-info"
                            data-serial-action="returned" data-serial-id="${s.id}"
                            title="Mark as Returned">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>`);
            }

            if (s.status !== 'sold' && variant.can_edit) {
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
            <h6 class="fw-bold text-gray-800 mb-3 mt-6">
                <i class="bi bi-upc-scan me-2 text-primary"></i>Serial Numbers
                <span class="badge badge-light-secondary ms-2">${serials.length}</span>
            </h6>
            <div class="d-flex flex-wrap gap-2 mb-3">
                ${badges.map(([key, color, label]) => `
                    <span class="badge badge-light-${color} fs-8 py-1 px-2">${summary[key]} ${label}</span>
                `).join('')}
            </div>
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

    // ── Render: footer ────────────────────────────────────
    function renderActionCluster(variant) {
        const btns = [];
        if (variant.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-warning d-flex align-items-center"
                              data-drawer-action="edit-recipe">
                        <i class="bi bi-pencil-square me-2"></i>Edit Recipe
                      </button>`);
        }
        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">
            ${btns.join('')}
        </div>`;
    }

    // ── Bind events ────────────────────────────────────────
    function bindActions(variant) {
        // Footer — Edit Recipe
        document.getElementById('recipeVariantDrawerActions')
            ?.querySelectorAll('[data-drawer-action="edit-recipe"]')
            .forEach(el => el.addEventListener('click', () => {
                if (typeof window.openRecipeIngredientsModal === 'function') {
                    window.openRecipeIngredientsModal(variant.id, variant.name);
                } else {
                    window.toastr?.error?.('Recipe editor is not available.');
                }
            }));

        // Serial row actions
        const body = document.getElementById('recipeVariantDrawerContent');
        body?.querySelectorAll('[data-serial-action]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const serialId = parseInt(btn.dataset.serialId, 10);
                const action   = btn.dataset.serialAction;
                if (!serialId) return;

                if (action === 'delete') return deleteSerial(variant, serialId);
                if (['sold', 'reserved', 'returned'].includes(action)) {
                    return changeSerialStatus(variant, serialId, action);
                }
            });
        });
    }

    async function changeSerialStatus(variant, serialId, status) {
        if (!confirm(`Change status to "${status}"?`)) return;
        try {
            const json = await req('PUT', `/serials/${serialId}/status`, { status });
            window.toastr?.success?.(json.message || 'Status updated.');
            await reload(variant.id);
        } catch (err) { window.toastr?.error?.(err.message); }
    }

    async function deleteSerial(variant, serialId) {
        if (!confirm('Delete this serial number?')) return;
        try {
            const json = await req('DELETE', `/serials/${serialId}`, null);
            window.toastr?.success?.(json.message || 'Serial deleted.');
            await reload(variant.id);
        } catch (err) { window.toastr?.error?.(err.message); }
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) {
        return CURRENCY + Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        });
    }

    window.RecipeVariantDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.RecipeVariantDrawer;
})();
</script>