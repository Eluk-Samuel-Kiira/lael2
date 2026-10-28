<script>
window.VariantDrawer = (function () {
    'use strict';

    const URL_SHOW   = window.VARIANT_ROUTES.show;
    const URL_DELETE = window.VARIANT_ROUTES.destroy;
    const URL_STATUS = window.VARIANT_ROUTES.status;
    const URL_TAX    = window.VARIANT_ROUTES.taxable;
    const URL_UPDATE = window.VARIANT_ROUTES.update;
    const URL_IMG    = window.VARIANT_ROUTES.upload_image;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const IS_SINGLE  = !!window.VARIANT_BOOTSTRAP?.isSingleShop;

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('variantDrawer');
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

            currentPayload = { variant: json.variant, assign_options: json.assign_options };
            render(json.variant);
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
            currentPayload = { variant: json.variant, assign_options: json.assign_options };
            render(json.variant);
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

    function render(v) {
        currentId = v.id;

        const nameEl = document.getElementById('variantDrawerName');
        if (nameEl) nameEl.textContent = v.name;

        document.getElementById('variantDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('variantDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderView(v);

        document.getElementById('variantDrawerActions').innerHTML = renderActionCluster(v);
        document.getElementById('variantDrawerFooter').classList.remove('d-none');
        bindActionCluster(v);
    }

    function renderView(v) {
        return `
            ${renderHeaderBlock(v)}
            ${renderMetaBlock(v)}
            ${renderPricingBlock(v)}
            ${renderProfitBlock(v)}
            ${renderFlagsBlock(v)}
        `;
    }

    function renderHeaderBlock(v) {
        const statusBadge  = v.is_active ? `<span class="badge badge-light-success">Active</span>` : `<span class="badge badge-light-warning">Inactive</span>`;
        const taxableBadge = v.is_taxable ? `<span class="badge badge-light-info">Taxable</span>` : `<span class="badge badge-light-secondary">Not taxable</span>`;
        const img = v.image_url
            ? `<img src="${esc(v.image_url)}" class="w-100 h-100 object-fit-cover" style="border-radius:50%;">`
            : `<span class="text-primary fw-bold fs-3">${esc(v.name?.charAt(0)?.toUpperCase() || '?')}</span>`;

        return `
            <div class="d-flex align-items-start gap-4 mb-6">
                <div class="symbol symbol-60px symbol-circle">
                    <div class="symbol-label bg-light-primary text-primary">${img}</div>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(v.name || '—')}</h5>
                    <div class="d-flex flex-wrap gap-2 fs-7">
                        <span class="badge badge-light fw-bold">${esc(v.sku || '—')}</span>
                        ${statusBadge} ${taxableBadge}
                    </div>
                </div>
                <div class="text-end">
                    <div class="fs-4 fw-bold text-warning">${num(v.overall_quantity)}</div>
                    <small class="text-muted">${IS_SINGLE ? 'On Hand' : 'Overall'}</small>
                </div>
            </div>
        `;
    }

    function renderMetaBlock(v) {
        return `
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-4">
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Product</div>
                        <div class="fw-bold text-gray-800 fs-7">${esc(v.product_name || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Barcode</div>
                        <div class="fw-bold text-gray-800 fs-7"><code>${esc(v.barcode || '—')}</code></div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Weight</div>
                        <div class="fw-bold text-gray-800 fs-7">${num(v.weight)} ${esc(v.weight_unit_name || '')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Strategy</div>
                        <div class="fw-bold text-gray-800 fs-7 text-capitalize">${esc(v.product_strategy || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created by</div>
                        <div class="fw-bold text-gray-800 fs-7">${esc(v.creator?.name || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-bold text-gray-800 fs-7">${esc(v.created_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderPricingBlock(v) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3 mt-4">
                <i class="bi bi-cash-stack me-2 text-success"></i>Pricing
            </h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle fs-7">
                    <tbody>
                        <tr><th>Supplier Cost</th><td class="text-end">${money(v.supplier_cost_price)}</td></tr>
                        <tr><th>Shipping Cost</th><td class="text-end">${money(v.total_shipping_cost)}</td></tr>
                        <tr><th>URA Taxes</th><td class="text-end">${money(v.ura_taxes_applied)}</td></tr>
                        <tr><th>Additional Expenses</th><td class="text-end">${money(v.additional_expenses)}</td></tr>
                        <tr class="fw-bold"><th>Grand Total Cost</th><td class="text-end text-success">${money(v.grand_total_cost_price)}</td></tr>
                        <tr><th>Selling Price</th><td class="text-end text-primary fw-bold">${money(v.selling_price)}</td></tr>
                        <tr><th>Discount Selling Price</th><td class="text-end">${money(v.discount_selling_price)}</td></tr>
                        <tr><th>Discount %</th><td class="text-end">${num(v.discount_percentage)}%</td></tr>
                        <tr><th>Markup %</th><td class="text-end">${num(v.markup_percentage)}%</td></tr>
                    </tbody>
                </table>
            </div>
        `;
    }

    function renderFlagsBlock(v) {
        const serialBadge = v.serial_summary
            ? `<span class="badge badge-light-primary me-2">${v.serials_count} Serials</span>`
            : '';
        const recipeBadge = v.has_recipe ? `<span class="badge badge-light-warning">Recipe</span>` : '';
        if (!serialBadge && !recipeBadge) return '';
        return `
            <div class="mb-4">
                ${serialBadge}${recipeBadge}
            </div>
        `;
    }

    function renderActionCluster(v) {
        // ── Taxable toggle (mirrors the legacy row switch) ──────
        const taxToggle = v.can_toggle_tax ? `
            <label class="form-check form-switch form-check-custom form-check-solid form-switch-sm d-flex align-items-center mb-0 me-2"
                title="Toggle taxable status">
                <input class="form-check-input"
                    type="checkbox"
                    data-drawer-tax-toggle
                    ${v.is_taxable ? 'checked' : ''}>
                <span class="form-check-label fw-semibold fs-7 ms-2 text-gray-700"
                    data-drawer-tax-label>
                    ${v.is_taxable ? 'Taxable' : 'Not Taxable'}
                </span>
            </label>` : '';

        // ── Status select (unchanged) ───────────────────────────
        const statusSelect = v.can_toggle ? `
            <select class="form-select form-select-sm fw-bold"
                    data-drawer-status-select data-current-status="${v.is_active ? 1 : 0}"
                    style="background-color:${v.is_active ? '#d4edda' : '#fff3cd'};
                        color:${v.is_active ? '#155724' : '#856404'};
                        border-color:${v.is_active ? '#c3e6cb' : '#ffeeba'};
                        font-weight:600; padding:.375rem 1.75rem .375rem .75rem;
                        width:160px; cursor:pointer;">
                <option value="">Status…</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>` : '';

        const btns = [];
        if (v.can_assign)         btns.push(btn('assign',  'bi-building',       'Assign',  'success'));
        if (v.can_manage_serials && v.product_strategy === 'serial')
                                btns.push(btn('serials', 'bi-upc-scan',       'Serials', 'info'));
        if (v.can_manage_recipe && v.has_recipe)
                                btns.push(btn('recipe',  'bi-journal-bookmark','Recipe',  'warning'));
        if (v.can_edit)           btns.push(btn('edit',    'bi-pencil-square',  'Edit',    'primary'));
        if (v.can_delete)         btns.push(btn('delete',  'bi-trash',          'Delete',  'danger'));

        const actionBar = btns.length
            ? `<div class="d-flex align-items-center gap-2 flex-shrink-0">${btns.join('')}</div>`
            : '';

        return `
            <div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">
                ${taxToggle}
                ${statusSelect}
                ${actionBar}
            </div>
        `;
    }

    function btn(action, icon, label, tone) {
        return `<button type="button" class="btn btn-sm btn-${tone} d-flex align-items-center"
                        data-drawer-action="${action}">
                    <i class="bi ${icon} me-2"></i>${label}
                </button>`;
    }

    function bindActionCluster(v) {
        const actions = document.getElementById('variantDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', e => {
                e.preventDefault();
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')    return openEditPanel(v);
                if (a === 'delete')  return openDeletePanel(v.id);
                if (a === 'assign')  return openAssignPanel(v.id);
                if (a === 'recipe')  return openRecipePanel(v.id);
                if (a === 'serials') return openSerialsPanel(v.id);
            });
        });

        // ── Taxable toggle ─────────────────────────────────────
        const taxToggle = actions.querySelector('[data-drawer-tax-toggle]');
        if (taxToggle) {
            taxToggle.addEventListener('change', async () => {
                const newStatus = taxToggle.checked ? 1 : 0;
                const label     = actions.querySelector('[data-drawer-tax-label]');

                taxToggle.disabled = true;
                try {
                    const json = await req('POST', URL_TAX.replace('__ID__', v.id), { status: newStatus });
                    window.toastr?.success?.(json.message);
                    if (label) label.textContent = newStatus ? 'Taxable' : 'Not Taxable';
                    window.VariantList?.reload?.();
                    await reload(v.id);
                } catch (err) {
                    // Revert the checkbox on failure
                    taxToggle.checked = !taxToggle.checked;
                    window.toastr?.error?.(err.message);
                } finally {
                    taxToggle.disabled = false;
                }
            });
        }

        const sel = actions.querySelector('[data-drawer-status-select]');
        if (sel) {
            sel.addEventListener('change', async () => {
                const newStatus = sel.value;
                const current   = sel.getAttribute('data-current-status');
                sel.value = '';
                if (!newStatus || newStatus === current) return;
                sel.disabled = true;
                try {
                    const json = await req('POST', URL_STATUS.replace('__ID__', v.id), { status: newStatus });
                    window.toastr?.success?.(json.message);
                    window.VariantList?.reload?.();
                    await reload(v.id);
                } catch (err) { window.toastr?.error?.(err.message); }
                finally { sel.disabled = false; }
            });
        }
    }

    function renderProfitBlock(v) {
        const unitProfit    = Number(v.selling_price || 0) - Number(v.grand_total_cost_price || 0);
        const unitMargin    = Number(v.selling_price) > 0
            ? (unitProfit / Number(v.selling_price)) * 100
            : 0;
        const overallProfit = unitProfit * Number(v.overall_quantity || 0);

        const unitColor    = unitProfit    >= 0 ? 'success' : 'danger';
        const overallColor = overallProfit >= 0 ? 'success' : 'danger';

        const unitRow = `
            <tr>
                <th>
                    Unit Profit
                    <small class="text-muted d-block fs-8 fw-normal">selling − grand total cost</small>
                </th>
                <td class="text-end fw-bold text-${unitColor}">
                    ${money(unitProfit)}
                    <small class="d-block text-muted fs-8">${unitMargin.toFixed(1)}%</small>
                </td>
            </tr>
        `;

        const overallRow = IS_SINGLE ? `
            <tr>
                <th>
                    Overall Profit
                    <small class="text-muted d-block fs-8 fw-normal">unit profit × on hand</small>
                </th>
                <td class="text-end fw-bold text-${overallColor}">
                    ${money(overallProfit)}
                    <small class="d-block text-muted fs-8">${num(v.overall_quantity)} × ${money(unitProfit)}</small>
                </td>
            </tr>
        ` : '';

        return `
            <h6 class="fw-bold text-gray-800 mb-3 mt-4">
                <i class="bi bi-graph-up-arrow me-2 text-primary"></i>Profit
            </h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle fs-7">
                    <tbody>
                        ${unitRow}
                        ${overallRow}
                    </tbody>
                </table>
            </div>
        `;
    }

    function openAssignPanel(id) {
        const v   = currentPayload?.variant;
        const ao  = currentPayload?.assign_options;
        if (!v || !ao) {
            window.toastr?.error?.('Assign data not loaded.');
            return;
        }

        document.getElementById('assignModalDynamic')?.remove();
        document.body.insertAdjacentHTML('beforeend', renderAssignModalShell(v, ao));

        const modalEl = document.getElementById('assignModalDynamic');
        modalEl.querySelector('[data-assign-submit]')?.addEventListener('click', () =>
            submitAssign(id, ao.assign_url));

        modalEl.addEventListener('hidden.bs.modal', function onHidden() {
            modalEl.removeEventListener('hidden.bs.modal', onHidden);
            modalEl.remove();
            if (isOpenFor(id)) reload(id);
        }, { once: true });

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    function renderAssignModalShell(v, ao) {
        const selTaxes      = new Set((ao.selected_taxes      || []).map(String));
        const selPromotions = new Set((ao.selected_promotions || []).map(String));

        const taxSection = v.is_taxable ? `
            <div class="mb-5">
                <h6 class="fw-bold mb-3">Assign Tax</h6>
                <div class="row g-3">
                    ${(ao.taxes || []).map(t => `
                        <div class="col-md-6">
                            <div class="form-check form-check-custom form-check-solid">
                                <input type="checkbox" class="form-check-input"
                                    name="taxes[]" value="${esc(t.id)}"
                                    id="v_tax_${esc(t.id)}"
                                    ${selTaxes.has(String(t.id)) ? 'checked' : ''}>
                                <label class="form-check-label" for="v_tax_${esc(t.id)}">
                                    ${esc(t.name)} — ${esc(t.code || '')}
                                </label>
                            </div>
                        </div>
                    `).join('') || `<div class="col-12 text-muted fs-7">No taxes available.</div>`}
                </div>
            </div>
        ` : `
            <div class="alert alert-warning py-2 mb-5">
                This variant is not taxable.
            </div>
        `;

        const promoSection = `
            <div class="mb-5">
                <h6 class="fw-bold mb-3">Assign Promotions</h6>
                <div class="row g-3">
                    ${(ao.promotions || []).map(p => `
                        <div class="col-md-6">
                            <div class="form-check form-check-custom form-check-solid">
                                <input type="checkbox" class="form-check-input"
                                    name="promotions[]" value="${esc(p.id)}"
                                    id="v_promo_${esc(p.id)}"
                                    ${selPromotions.has(String(p.id)) ? 'checked' : ''}>
                                <label class="form-check-label" for="v_promo_${esc(p.id)}">
                                    ${esc(p.name)}
                                </label>
                            </div>
                        </div>
                    `).join('') || `<div class="col-12 text-muted fs-7">No promotions available.</div>`}
                </div>
            </div>
        `;

        
        return `
            <div class="modal fade" id="assignModalDynamic" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered mw-750px">
                    <div class="modal-content">
                        <div class="modal-header bg-success py-4">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-building fs-2 text-white"></i>
                                <h2 class="modal-title text-white fw-bold mb-0">
                                    Assign — ${esc(v.name || '')}
                                </h2>
                            </div>
                            <button type="button" class="btn-close btn-close-white"
                                    data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-7 py-6">
                            <form id="variantAssignForm" class="form">
                                ${taxSection}
                                ${promoSection}
                            </form>
                        </div>
                        <div class="modal-footer py-4">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Discard</button>
                            <button type="button" class="btn btn-success" data-assign-submit style="min-width:140px;">
                                <span class="indicator-label"><i class="bi bi-check2 me-1"></i>Update</span>
                                <span class="indicator-progress d-none">Please wait…
                                    <span class="spinner-border spinner-border-sm ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    async function submitAssign(id, url) {
        const modalEl = document.getElementById('assignModalDynamic');
        if (!modalEl) return;

        const form = modalEl.querySelector('#variantAssignForm');
        if (!form) return;

        const btn = modalEl.querySelector('[data-assign-submit]');
        setLoading(btn, true);

        const formData = new FormData(form);
        formData.append('_method', 'PUT');   // route is PUT /assignment/{product}

        try {
            const res = await fetch(url, {
                method: 'POST',   // real verb POST + _method=PUT
                headers: {
                    'X-CSRF-TOKEN':     CSRF,
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const json = await res.json().catch(() => ({}));

            if (res.status === 422) {
                const first = json.errors ? Object.values(json.errors)[0][0] : (json.message || 'Validation failed');
                window.toastr?.error?.(first);
                return;
            }

            if (!res.ok || json.success === false) {
                throw new Error(json.message || 'Assign failed');
            }

            window.toastr?.success?.(json.message || 'Assignments updated.');
            bootstrap.Modal.getInstance(modalEl)?.hide();
            window.VariantList?.reload?.();
            // drawer reload happens in hidden.bs.modal

        } catch (err) {
            window.toastr?.error?.(err.message);
        } finally {
            setLoading(btn, false);
        }
    }

    function setLoading(btn, busy) {
        if (!btn) return;
        if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';
        const label = btn.querySelector('.indicator-label');
        const prog  = btn.querySelector('.indicator-progress');

        if (prog && !prog.dataset.overlayInit) {
            Object.assign(prog.style, {
                position: 'absolute', top: '50%', left: '50%',
                transform: 'translate(-50%, -50%)',
                alignItems: 'center', gap: '.5rem',
                whiteSpace: 'nowrap', pointerEvents: 'none',
            });
            prog.dataset.overlayInit = '1';
        }

        btn.disabled = busy;
        if (label) label.style.visibility = busy ? 'hidden' : '';
        if (prog) {
            prog.classList.toggle('d-none', !busy);
            prog.style.display = busy ? 'inline-flex' : 'none';
        }
    }

    function openEditPanel(vOrId) {
        // Accept either the variant object or the id
        const v = typeof vOrId === 'object'
            ? vOrId
            : currentPayload?.variant;

        if (!v) {
            window.toastr?.error?.('Variant data not loaded.');
            return;
        }

        if (typeof vOrId !== 'object' && String(v.id) !== String(vOrId)) {
            window.toastr?.error?.('Variant data mismatch.');
            return;
        }

        const body = document.getElementById('variantDrawerContent');
        if (!body) return;

        document.getElementById('variantDrawerFooter')?.classList.add('d-none');

        const nameEl = document.getElementById('variantDrawerName');
        if (nameEl) nameEl.textContent = 'Editing — ' + (v.name || '');

        body.innerHTML = renderEditPanel(v);
        bindEditPanel(v.id);
    }

    function renderEditPanel(v) {
        const uoms = currentPayload?.assign_options?.uoms || [];

        const uomOpts = uoms.map(u =>
            `<option value="${esc(u.id)}" ${String(u.id) === String(v.weight_unit_id) ? 'selected' : ''}>${esc(u.name)}</option>`
        ).join('');

        return `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold text-gray-800 mb-0">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Variant
                </h5>
                <button type="button" class="btn btn-sm btn-light" data-edit-back>
                    <i class="bi bi-arrow-left me-1"></i>Back
                </button>
            </div>

            <form id="variantEditForm" class="form">
                <div class="row g-5 mb-4">
                    <div class="col-md-6">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" class="form-control form-control-solid"
                            value="${esc(v.name || '')}">
                        <div class="invalid-feedback" data-error-for="name"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">SKU</label>
                        <input type="text" name="sku" class="form-control form-control-solid"
                            value="${esc(v.sku || '')}">
                        <div class="invalid-feedback" data-error-for="sku"></div>
                    </div>
                </div>

                <div class="row g-5 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Barcode</label>
                        <input type="text" name="barcode" class="form-control form-control-solid"
                            value="${esc(v.barcode || '')}">
                        <div class="invalid-feedback" data-error-for="barcode"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Overall Quantity</label>
                        <input type="number" name="overal_quantity_at_hand" class="form-control form-control-solid"
                            value="${raw(v.overall_quantity)}" min="0">
                        <div class="invalid-feedback" data-error-for="overal_quantity_at_hand"></div>
                    </div>
                </div>

                <h6 class="fw-bold text-gray-800 mb-3 mt-4">
                    <i class="bi bi-cash-stack me-2 text-success"></i>Cost Breakdown
                </h6>
                <div class="row g-5 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Supplier Cost</label>
                        <input type="number" name="supplier_cost_price" class="form-control form-control-solid"
                            value="${raw(v.supplier_cost_price)}" step="0.01" min="0" data-cost-field>
                        <div class="invalid-feedback" data-error-for="supplier_cost_price"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Shipping Cost</label>
                        <input type="number" name="total_shipping_cost" class="form-control form-control-solid"
                            value="${raw(v.total_shipping_cost)}" step="0.01" min="0" data-cost-field>
                        <div class="invalid-feedback" data-error-for="total_shipping_cost"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">URA Taxes</label>
                        <input type="number" name="ura_taxes_applied" class="form-control form-control-solid"
                            value="${raw(v.ura_taxes_applied)}" step="0.01" min="0" data-cost-field>
                        <div class="invalid-feedback" data-error-for="ura_taxes_applied"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Additional Expenses</label>
                        <input type="number" name="additional_expenses" class="form-control form-control-solid"
                            value="${raw(v.additional_expenses)}" step="0.01" min="0" data-cost-field>
                        <div class="invalid-feedback" data-error-for="additional_expenses"></div>
                    </div>
                </div>

                <div class="alert alert-info py-2 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold">Grand Total Cost</span>
                        <span class="fw-bold fs-5 text-primary" id="variantGrandTotalDisplay">
                            ${money(v.grand_total_cost_price)}
                        </span>
                    </div>
                </div>
                <input type="hidden" name="grand_total_cost_price" id="variantGrandTotalInput"
                    value="${raw(v.grand_total_cost_price)}">

                <h6 class="fw-bold text-gray-800 mb-3 mt-4">
                    <i class="bi bi-tag me-2 text-primary"></i>Pricing
                </h6>
                <div class="row g-5 mb-4">
                    <div class="col-md-6">
                        <label class="form-label required">Selling Price</label>
                        <input type="number" name="selling_price" class="form-control form-control-solid"
                            value="${raw(v.selling_price)}" step="0.01" min="0">
                        <div class="invalid-feedback" data-error-for="selling_price"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Discount Selling Price</label>
                        <input type="number" name="discount_selling_price" class="form-control form-control-solid"
                            value="${raw(v.discount_selling_price)}" step="0.01" min="0">
                        <div class="form-text text-muted">Leave blank to keep current value</div>
                        <div class="invalid-feedback" data-error-for="discount_selling_price"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Discount %</label>
                        <input type="number" name="discount_percentage" class="form-control form-control-solid"
                            value="${raw(v.discount_percentage)}" step="0.01" min="0" max="100">
                        <div class="invalid-feedback" data-error-for="discount_percentage"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Markup %</label>
                        <input type="number" name="markup_percentage" class="form-control form-control-solid"
                            value="${raw(v.markup_percentage)}" step="0.01" min="0">
                        <div class="invalid-feedback" data-error-for="markup_percentage"></div>
                    </div>
                </div>

                <h6 class="fw-bold text-gray-800 mb-3 mt-4">
                    <i class="bi bi-box-seam me-2 text-warning"></i>Physical
                </h6>
                <div class="row g-5 mb-4">
                    <div class="col-md-6">
                        <label class="form-label required">Weight</label>
                        <input type="number" name="weight" class="form-control form-control-solid"
                            value="${raw(v.weight)}" step="0.01" min="0">
                        <div class="invalid-feedback" data-error-for="weight"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Weight Unit</label>
                        <select name="weight_unit" class="form-select form-select-solid">
                            <option value="">— Select —</option>
                            ${uomOpts}
                        </select>
                        <div class="invalid-feedback" data-error-for="weight_unit"></div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-5">
                    <button type="button" class="btn btn-light" data-edit-back>Discard</button>
                    <button type="button" class="btn btn-primary" data-edit-submit style="min-width: 160px;">
                        <span class="indicator-label"><i class="bi bi-check2 me-1"></i>Update</span>
                        <span class="indicator-progress d-none">Please wait…
                            <span class="spinner-border spinner-border-sm ms-2"></span></span>
                    </button>
                </div>
            </form>
        `;
    }

    function bindEditPanel(id) {
        const body = document.getElementById('variantDrawerContent');
        if (!body) return;

        body.querySelectorAll('[data-edit-back]').forEach(b =>
            b.addEventListener('click', () => {
                if (currentPayload?.variant) {
                    render(currentPayload.variant);
                }
            })
        );

        // Live grand total as the user edits any cost field
        const form = body.querySelector('#variantEditForm');
        if (form) {
            form.querySelectorAll('[data-cost-field]').forEach(el => {
                el.addEventListener('input', () => {
                    const vals = ['supplier_cost_price', 'total_shipping_cost', 'ura_taxes_applied', 'additional_expenses']
                        .map(n => parseFloat(form.querySelector(`[name="${n}"]`)?.value) || 0);
                    const total = vals.reduce((a, b) => a + b, 0);

                    body.querySelector('#variantGrandTotalDisplay').textContent = money(total);
                    body.querySelector('#variantGrandTotalInput').value = total.toFixed(2);
                });
            });
        }

        body.querySelector('[data-edit-submit]')?.addEventListener('click', ev =>
            submitDrawerEdit(id, ev.currentTarget)
        );
    }

    
    async function submitDrawerEdit(id, btn) {
        const form = document.getElementById('variantEditForm');
        if (!form) return;

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';

        // variant update requires product_id — reuse current payload
        data.product_id = currentPayload?.variant?.product_id ?? '';

        setLoading(btn, true);

        try {
            const res = await fetch(URL_UPDATE.replace('__ID__', id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     CSRF,
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams(data),
            });

            const json = await res.json().catch(() => ({}));

            if (res.status === 422) {
                Object.entries(json.errors || {}).forEach(([field, msgs]) => {
                    const input  = form.querySelector(`[name="${field}"]`);
                    const errBox = form.querySelector(`[data-error-for="${field}"]`);
                    if (input)  input.classList.add('is-invalid');
                    if (errBox) errBox.textContent = msgs[0];
                });
                window.toastr?.error?.(json.message || 'Please fix the highlighted fields.');
                return;
            }

            if (!res.ok || json.success === false) {
                throw new Error(json.message || 'Update failed');
            }

            window.toastr?.success?.(json.message || 'Variant updated.');
            window.VariantList?.reload?.();
            await reload(id);

        } catch (err) {
            console.error('[variant-drawer] edit failed:', err);
            window.toastr?.error?.(err.message);
        } finally {
            setLoading(btn, false);
        }
    }

    function openSerialsPanel(id) {
        const v = currentPayload?.variant;
        if (!v || String(v.id) !== String(id)) {
            window.toastr?.error?.('Variant data not loaded.');
            return;
        }
        if (typeof window.openSerialManagementModal !== 'function') {
            window.toastr?.error?.('Serial management is not available.');
            return;
        }
        window.openSerialManagementModal(v.id, v.name);
    }

    function openRecipePanel(id) {
        const v = currentPayload?.variant;
        if (!v || String(v.id) !== String(id)) {
            window.toastr?.error?.('Variant data not loaded.');
            return;
        }

        if (typeof window.openRecipeIngredientsModal !== 'function') {
            window.toastr?.error?.('Recipe management is not available.');
            return;
        }

        window.openRecipeIngredientsModal(v.id, v.name || '');
    }

    // Panels land in Part 2
    function openDeletePanel(id) {
        const v = currentPayload?.variant;
        if (!v || String(v.id) !== String(id)) {
            window.toastr?.error?.('Variant data not loaded.');
            return;
        }

        const body = document.getElementById('variantDrawerContent');
        if (!body) return;

        document.getElementById('variantDrawerFooter')?.classList.add('d-none');

        const nameEl = document.getElementById('variantDrawerName');
        if (nameEl) nameEl.textContent = 'Deleting — ' + (v.name || '');

        body.innerHTML = renderDeletePanel(v);
        bindDeletePanel(v.id);
    }

    function renderDeletePanel(v) {
        return `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold text-danger mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Variant
                </h5>
                <button type="button" class="btn btn-sm btn-light" data-delete-back>
                    <i class="bi bi-arrow-left me-1"></i>Back
                </button>
            </div>

            <div class="alert alert-danger">
                <p class="mb-2 fw-bold">Are you sure you want to delete this variant?</p>
                <p class="mb-0 fs-7">
                    This will permanently remove <strong>${esc(v.name || '')}</strong>
                    (SKU: <code>${esc(v.sku || '—')}</code>) and all associated
                    inventory records, taxes, and promotions.
                    <br>
                    This action <strong>cannot be undone</strong>.
                </p>
            </div>

            ${Number(v.overall_quantity || 0) > 0 ? `
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle me-2"></i>
                    This variant still has <strong>${num(v.overall_quantity)}</strong> unit(s) on hand.
                    You may want to zero out the stock before deleting, or the deletion may be blocked.
                </div>
            ` : ''}

            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="row g-3 fs-7">
                    <div class="col-6 col-md-3">
                        <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">Variant</div>
                        <div class="fw-bold text-gray-800">${esc(v.name || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">SKU</div>
                        <div class="fw-bold text-gray-800">${esc(v.sku || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">Product</div>
                        <div class="fw-bold text-gray-800">${esc(v.product_name || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">Stock</div>
                        <div class="fw-bold text-gray-800">${num(v.overall_quantity)}</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-5">
                <button type="button" class="btn btn-light" data-delete-back>Discard</button>
                <button type="button" class="btn btn-danger" data-delete-submit style="min-width: 160px;">
                    <span class="indicator-label">
                        <i class="bi bi-trash me-1"></i>Delete Permanently
                    </span>
                    <span class="indicator-progress d-none">
                        Deleting… <span class="spinner-border spinner-border-sm ms-2"></span>
                    </span>
                </button>
            </div>
        `;
    }

    function bindDeletePanel(id) {
        const body = document.getElementById('variantDrawerContent');
        if (!body) return;

        body.querySelectorAll('[data-delete-back]').forEach(b =>
            b.addEventListener('click', () => {
                if (currentPayload?.variant) {
                    render(currentPayload.variant);
                }
            })
        );

        body.querySelector('[data-delete-submit]')?.addEventListener('click', ev =>
            submitDelete(id, ev.currentTarget)
        );
    }

    async function submitDelete(id, btn) {
        setLoading(btn, true);

        try {
            const res = await fetch(URL_DELETE.replace('__ID__', id), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN':     CSRF,
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const json = await res.json().catch(() => ({}));

            if (res.status === 422 || json.success === false) {
                // Server rejected the delete (e.g. has orders, is active, referenced by a recipe)
                throw new Error(json.message || 'The variant cannot be deleted.');
            }

            if (!res.ok) {
                throw new Error(json.message || 'Delete failed');
            }

            window.toastr?.success?.(json.message || 'Variant deleted.');
            window.VariantList?.reload?.();
            close();

        } catch (err) {
            console.error('[variant-drawer] delete failed:', err);
            window.toastr?.error?.(err.message);
            setLoading(btn, false);   // re-enable on failure only
        }
        // Note: no `finally` setLoading(false) — on success the drawer
        // is closed, so the button state doesn't matter.
    }


    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function num(n) {
        const x = typeof n === 'string'
            ? Number(n.replace(/[,\s]+/g, '').replace(/[^\d.\-]/g, ''))
            : Number(n ?? 0);
        return isNaN(x) ? '0' : x.toLocaleString('en-US');
    }

    function money(n) {
        const x = typeof n === 'string'
            ? Number(n.replace(/[,\s]+/g, '').replace(/[^\d.\-]/g, ''))
            : Number(n ?? 0);
        return (window.VARIANT_CURRENCY || '') + (isNaN(x) ? 0 : x).toLocaleString('en-US', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        });
    }

    function raw(n) {
        if (n == null || n === '') return '';
        const x = typeof n === 'string'
            ? Number(n.replace(/[,\s]+/g, '').replace(/[^\d.\-]/g, ''))
            : Number(n);
        return isNaN(x) ? '' : String(x);
    }

    window.VariantDrawer = { open, reload, close, isOpenFor, getCurrent: () => ({ id: currentId, payload: currentPayload }), };
    return window.VariantDrawer;
})();
</script>