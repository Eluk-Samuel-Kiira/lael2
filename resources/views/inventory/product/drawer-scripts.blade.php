<script>
window.ProductDrawer = (function () {
    'use strict';

    const URL_SHOW   = window.PRODUCT_ROUTES.show;
    const URL_DELETE = window.PRODUCT_ROUTES.destroy;
    const URL_STATUS = window.PRODUCT_ROUTES.status;
    const URL_UPDATE = window.PRODUCT_ROUTES.update;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('productDrawer');
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
                'X-CSRF-TOKEN':     CSRF,
                'Accept':           'application/json',
                'Content-Type':     'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.success === false) throw new Error(json.message || 'Request failed');
        return json;
    }

    function renderAllocateModalShell(p, ao) {
        // Assign arrays to Sets for O(1) `.has()`
        const selTaxes       = new Set((ao.selected_taxes       || []).map(String));
        const selPromotions  = new Set((ao.selected_promotions  || []).map(String));
        const selLocations   = new Set((ao.selected_locations   || []).map(String));
        const selDepartments = new Set((ao.selected_departments || []).map(String));

        // ── Taxes section ──────────────────────────────────────
        const taxSection = ao.is_taxable ? `
            <div class="mb-5">
                <h6 class="fw-bold mb-3">Assign Tax</h6>
                <div class="row g-3">
                    ${(ao.taxes || []).map(t => `
                        <div class="col-md-6">
                            <div class="form-check form-check-custom form-check-solid">
                                <input type="checkbox" class="form-check-input"
                                    name="taxes[]" value="${esc(t.id)}"
                                    id="tax_${esc(t.id)}"
                                    ${selTaxes.has(String(t.id)) ? 'checked' : ''}>
                                <label class="form-check-label" for="tax_${esc(t.id)}">
                                    ${esc(t.name)} — ${esc(t.code || '')}
                                </label>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        ` : `
            <div class="alert alert-warning py-2 mb-5">
                This product is not taxable.
            </div>
        `;

        // ── Promotions section ─────────────────────────────────
        const promoSection = `
            <div class="mb-5">
                <h6 class="fw-bold mb-3">Assign Promotions</h6>
                <div class="row g-3">
                    ${(ao.promotions || []).map(pr => `
                        <div class="col-md-6">
                            <div class="form-check form-check-custom form-check-solid">
                                <input type="checkbox" class="form-check-input"
                                    name="promotions[]" value="${esc(pr.id)}"
                                    id="promo_${esc(pr.id)}"
                                    ${selPromotions.has(String(pr.id)) ? 'checked' : ''}>
                                <label class="form-check-label" for="promo_${esc(pr.id)}">
                                    ${esc(pr.name)}
                                </label>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        // ── Locations + departments section ────────────────────
        const locSection = ao.is_single_shop
            ? `<div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Single-shop plan:</strong> location and department allocation is disabled.
            </div>`
            : (ao.locations || []).map(loc => {
                const depts = (ao.departments || []).filter(d => String(d.location_id) === String(loc.id));
                const anySelected = depts.some(d => selDepartments.has(String(d.id)));
                const locSelected = selLocations.has(String(loc.id)) || anySelected;

                return `
                    <div class="card border rounded mb-3" data-location-group="${esc(loc.id)}">
                        <div class="card-header bg-light p-3">
                            <div class="d-flex align-items-center">
                                <div class="form-check form-check-custom form-check-solid me-3">
                                    <input type="checkbox" class="form-check-input location-checkbox"
                                        name="locations[]" value="${esc(loc.id)}"
                                        data-location="${esc(loc.id)}"
                                        id="loc_${esc(loc.id)}"
                                        ${locSelected ? 'checked' : ''}>
                                    <label class="form-check-label fw-bold" for="loc_${esc(loc.id)}">
                                        ${esc(loc.name)}
                                    </label>
                                </div>
                                <button type="button" class="btn btn-sm btn-icon btn-light-primary ms-auto"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#locDepts_${esc(loc.id)}">
                                    <i class="ki-duotone ki-down fs-4"></i>
                                </button>
                            </div>
                        </div>
                        <div id="locDepts_${esc(loc.id)}" class="collapse ${anySelected ? 'show' : ''}">
                            <div class="card-body">
                                <div class="row g-3">
                                    ${depts.map(d => `
                                        <div class="col-md-6">
                                            <div class="form-check form-check-custom form-check-solid">
                                                <input type="checkbox" class="form-check-input department-checkbox"
                                                    data-location="${esc(loc.id)}"
                                                    name="departments[]" value="${esc(d.id)}"
                                                    id="dept_${esc(d.id)}"
                                                    ${selDepartments.has(String(d.id)) ? 'checked' : ''}>
                                                <label class="form-check-label" for="dept_${esc(d.id)}">
                                                    ${esc(d.name)}
                                                </label>
                                            </div>
                                        </div>
                                    `).join('') || `
                                        <div class="col-12">
                                            <div class="text-muted text-center py-3">
                                                No departments in this location.
                                            </div>
                                        </div>
                                    `}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

        return `
            <div class="modal fade" id="allocateModalDynamic" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered mw-900px">
                    <div class="modal-content">
                        <div class="modal-header bg-primary py-4">
                            <h2 class="modal-title text-white fw-bold mb-0">
                                Allocate — ${esc(p.name || '')}
                            </h2>
                            <button type="button" class="btn-close btn-close-white"
                                    data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-7 py-6" style="max-height: 70vh; overflow-y: auto;">
                            <form id="allocateFormDynamic" class="form">
                                <input type="hidden" name="product_id" value="${esc(p.id)}">
                                ${taxSection}
                                ${promoSection}
                                <div class="mb-5">
                                    <h6 class="fw-bold mb-3">Allocate Locations & Departments</h6>
                                    ${locSection}
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer py-4">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                Discard
                            </button>
                            <button type="button" class="btn btn-primary" data-allocate-submit style="min-width: 140px;">
                                <span class="indicator-label"><i class="bi bi-check2 me-1"></i>Update</span>
                                <span class="indicator-progress d-none">
                                    Please wait… <span class="spinner-border spinner-border-sm ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function syncAllocateCheckboxes(modalEl) {
        if (!modalEl || modalEl.dataset.allocateBound === '1') return;
        modalEl.dataset.allocateBound = '1';

        modalEl.addEventListener('change', (e) => {
            const t = e.target;

            if (t.classList.contains('location-checkbox')) {
                const locId = t.dataset.location;
                modalEl.querySelectorAll(`.department-checkbox[data-location="${locId}"]`)
                    .forEach(d => { d.checked = t.checked; });
                return;
            }

            if (t.classList.contains('department-checkbox')) {
                const locId = t.dataset.location;
                const locBox = modalEl.querySelector(`.location-checkbox[data-location="${locId}"]`);
                const sib = modalEl.querySelectorAll(`.department-checkbox[data-location="${locId}"]`);
                const anyChecked = Array.from(sib).some(d => d.checked);
                if (locBox) locBox.checked = anyChecked;
            }
        });
    }

    async function submitAllocate(id, url) {
        // Always resolve fresh — never trust the caller's reference
        const modalEl = document.getElementById('allocateModalDynamic');
        if (!modalEl) {
            window.toastr?.error?.('Allocate modal missing.');
            return;
        }

        const form = modalEl.querySelector('#allocateFormDynamic');
        if (!form) return;

        const btn = modalEl.querySelector('[data-allocate-submit]');
        setLoading(btn, true);

        const formData = new FormData(form);

        try {
            const res = await fetch(url, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN':     CSRF,
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const json = await res.json().catch(() => ({}));

            if (!res.ok || json.success === false) {
                throw new Error(json.message || 'Allocation failed');
            }

            window.toastr?.success?.(json.message || 'Product allocated.');
            bootstrap.Modal.getInstance(modalEl)?.hide();
            window.ProductList?.reload?.();
        } catch (err) {
            window.toastr?.error?.(err.message);
        } finally {
            setLoading(btn, false);
        }
    }

    async function open(id) {
        currentId = id;
        const inst = getBs();
        if (!inst) return;

        showSkeleton();
        inst.show();

        try {
            const res = await fetch(URL_SHOW.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');

            currentPayload = {
                product:          json.product,
                variants:         json.variants,
                allocate_options: json.allocate_options,   // ← keep it
            };
            render(json.product, json.variants);
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

            currentPayload = {
                product:          json.product,
                variants:         json.variants,
                allocate_options: json.allocate_options,
            };
            render(json.product, json.variants);
        } catch (err) {
            console.error('[product-drawer] reload failed:', err);
        }
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

    function render(product, variants) {
        currentId = product.id;
        const nameEl = document.getElementById('productDrawerName');
        if (nameEl) nameEl.textContent = product.name;

        document.getElementById('productDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('productDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderView(product, variants);

        document.getElementById('productDrawerActions').innerHTML = renderActionCluster(product);
        document.getElementById('productDrawerFooter').classList.remove('d-none');
        bindActionCluster(product);
    }

    function renderView(product, variants) {
        return `
            ${renderHeaderBlock(product)}
            ${renderMetaBlock(product)}
            ${renderVariantsBlock(variants)}
            ${renderDescription(product)}
        `;
    }

    function renderHeaderBlock(p) {
        const statusBadge = p.is_active
            ? `<span class="badge badge-light-success">Active</span>`
            : `<span class="badge badge-light-warning">Inactive</span>`;
        const taxableBadge = p.is_taxable
            ? `<span class="badge badge-light-info">Taxable</span>`
            : `<span class="badge badge-light-secondary">Not taxable</span>`;
        const imageHtml = p.image_url
            ? `<img src="${esc(p.image_url)}" class="w-100 h-100 object-fit-cover" style="border-radius:50%;">`
            : `<span class="text-primary fw-bold fs-3">${esc(p.name?.charAt(0)?.toUpperCase() || '?')}</span>`;

        return `
            <div class="d-flex align-items-start gap-4 mb-6">
                <div class="symbol symbol-60px symbol-circle">
                    <div class="symbol-label bg-light-primary text-primary">${imageHtml}</div>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(p.name || '—')}</h5>
                    <div class="d-flex flex-wrap gap-2 fs-7">
                        <span class="badge badge-light fw-bold">${esc(p.sku || '—')}</span>
                        ${statusBadge} ${taxableBadge}
                    </div>
                </div>
                <div class="text-end">
                    <div class="fs-4 fw-bold text-primary">${p.variants_count || 0}</div>
                    <small class="text-muted">Variants</small>
                </div>
            </div>
        `;
    }

    function renderMetaBlock(p) {
        return `
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-4">
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">ID</div>
                        <div class="fw-bold text-gray-800 fs-7">#${esc(p.id)}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Category</div>
                        <div class="fw-bold text-gray-800 fs-7">${esc(p.category_name || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Type</div>
                        <div class="fw-bold text-gray-800 fs-7 text-capitalize">${esc(p.type || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Strategy</div>
                        <div class="fw-bold text-gray-800 fs-7 text-capitalize">${esc(p.inventory_strategy || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created by</div>
                        <div class="fw-bold text-gray-800 fs-7">${esc(p.creator?.name || '—')}</div>
                        ${p.creator?.email ? `<small class="text-muted">${esc(p.creator.email)}</small>` : ''}
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-bold text-gray-800 fs-7">${esc(p.created_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderVariantsBlock(variants) {
        if (!variants || variants.length === 0) {
            return `<div class="alert alert-light-info">No variants created for this product yet.</div>`;
        }

        const rows = variants.map(v => `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="symbol symbol-35px">
                            <img src="${esc(v.image_url || '')}" class="symbol-label" style="object-fit:cover;">
                        </div>
                        <div>
                            <div class="fw-bold text-gray-800">${esc(v.name)}</div>
                            <small class="text-muted">${esc(v.sku || '—')}</small>
                        </div>
                    </div>
                </td>
                <td><code class="text-muted">${esc(v.barcode || '—')}</code></td>
                <td class="text-end">${money(v.supplier_cost_price)}</td>
                <td class="text-end">${money(v.grand_total_cost_price)}</td>
                <td class="text-end">${money(v.selling_price)}</td>
                <td class="text-end text-success">${money(v.discount_selling_price)}</td>
                <td class="text-end fw-bold ${v.profit_per_unit >= 0 ? 'text-success' : 'text-danger'}">
                    ${money(v.profit_per_unit)}
                    <small class="d-block text-muted fs-8">${fmtNum(v.profit_margin, 1)}%</small>
                </td>
                <td class="text-center">
                    <span class="badge badge-light-${v.overal_quantity_at_hand > 0 ? 'primary' : 'secondary'}">
                        ${fmtNum(v.overal_quantity_at_hand, 0)}
                    </span>
                </td>
                <td class="text-center">
                    <span class="badge badge-light-${v.is_active ? 'success' : 'warning'}">
                        ${v.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
            </tr>
        `).join('');

        return `
            <h6 class="fw-bold text-gray-800 mb-3 mt-4">
                <i class="bi bi-boxes me-2 text-primary"></i>Variants <span class="badge badge-light-primary ms-1">${variants.length}</span>
            </h6>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-striped align-middle fs-7">
                    <thead class="bg-light">
                        <tr>
                            <th>Variant</th>
                            <th>Barcode</th>
                            <th class="text-end">Supplier Cost</th>
                            <th class="text-end">Total Cost</th>
                            <th class="text-end">Selling</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Profit</th>
                            <th class="text-center">Stock</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    function renderDescription(p) {
        if (!p.description) return '';
        return `
            <h6 class="fw-bold text-gray-800 mb-3 mt-4"><i class="bi bi-card-text me-2 text-info"></i>Description</h6>
            <div class="bg-light rounded-3 p-4 mb-4">
                <p class="mb-0 text-gray-700 fs-7" style="line-height:1.7;">${esc(p.description)}</p>
            </div>
        `;
    }

    function renderActionCluster(p) {
        const statusSelect = p.can_toggle ? `
            <select class="form-select form-select-sm fw-bold"
                    data-drawer-status-select data-current-status="${p.is_active ? 1 : 0}"
                    style="background-color:${p.is_active ? '#d4edda' : '#fff3cd'};
                           color:${p.is_active ? '#155724' : '#856404'};
                           border-color:${p.is_active ? '#c3e6cb' : '#ffeeba'};
                           font-weight:600; padding:.375rem 1.75rem .375rem .75rem;
                           width:160px; cursor:pointer;">
                <option value="">Status…</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>` : '';

        const viewVariantsBtn = p.can_view_variants
            ? `<a href="${esc(p.variants_url)}" target="_blank" rel="noopener"
                  class="btn btn-sm btn-light btn-active-color-success d-flex align-items-center flex-shrink-0">
                    <i class="bi bi-eye me-2"></i>Variants
                    <span class="badge badge-light-primary ms-2">${p.variants_count || 0}</span>
               </a>`
            : '';

        const btns = [];
        if (p.can_allocate) btns.push(btn('allocate', 'bi-building',      'Allocate', 'success'));
        if (p.can_edit)     btns.push(btn('edit',     'bi-pencil-square', 'Edit',     'primary'));
        if (p.can_delete)   btns.push(btn('delete',   'bi-trash',         'Delete',   'danger'));

        const actionBar = btns.length
            ? `<div class="d-flex align-items-center gap-2 flex-shrink-0">${btns.join('')}</div>`
            : '';

        return `
            <div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">
                ${statusSelect}
                ${viewVariantsBtn}
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

    function bindActionCluster(p) {
        const actions = document.getElementById('productDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', e => {
                e.preventDefault();
                const action = el.getAttribute('data-drawer-action');
                if (action === 'edit')     return openEditPanel(p.id);
                if (action === 'delete')   return openDeletePanel(p.id);
                if (action === 'allocate') return openAllocateModal(p.id);
            });
        });

        const sel = actions.querySelector('[data-drawer-status-select]');
        if (sel) {
            sel.addEventListener('change', async () => {
                const newStatus = sel.value;
                const current   = sel.getAttribute('data-current-status');
                sel.value = '';
                if (!newStatus || newStatus === current) return;

                sel.disabled = true;
                try {
                    const json = await req('POST', URL_STATUS.replace('__ID__', p.id), { status: newStatus });
                    window.toastr?.success?.(json.message);
                    window.ProductList?.reload?.();
                    await reload(p.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    // ─────────────────────────────────────────────────────────
    //  Edit panel
    // ─────────────────────────────────────────────────────────
    function openEditPanel(id) {
        const p = currentPayload?.product;
        if (!p || String(p.id) !== String(id)) {
            window.toastr?.error?.('Product data not loaded.');
            return;
        }

        const body = document.getElementById('productDrawerContent');
        if (!body) return;

        document.getElementById('productDrawerFooter')?.classList.add('d-none');

        const nameEl = document.getElementById('productDrawerName');
        if (nameEl) nameEl.textContent = 'Editing — ' + (p.name || '');

        body.innerHTML = renderEditPanel(p);
        bindEditPanel(id);
    }

    function renderEditPanel(p) {
        const typeOpts = [
            ['physical',  'Physical'],
            ['digital',   'Digital'],
            ['service',   'Service'],
            ['composite', 'Composite'],
        ].map(([v, l]) => `<option value="${v}" ${p.type === v ? 'selected' : ''}>${l}</option>`).join('');

        const stratOpts = [
            ['quantity', 'Quantity'],
            ['batch',    'Batch'],
            ['serial',   'Serial'],
            ['recipe',   'Recipe'],
        ].map(([v, l]) => `<option value="${v}" ${p.inventory_strategy === v ? 'selected' : ''}>${l}</option>`).join('');

        const categoryOpts = buildCategoryOptions(p.category_id);

        return `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold text-gray-800 mb-0">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Product
                </h5>
                <button type="button" class="btn btn-sm btn-light" data-edit-back>
                    <i class="bi bi-arrow-left me-1"></i>Back
                </button>
            </div>

            <form id="productDrawerEditForm" class="form">
                <div class="row g-5 mb-4">
                    <div class="col-md-6">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name"
                               class="form-control form-control-solid"
                               value="${esc(p.name || '')}">
                        <div class="invalid-feedback" data-error-for="name"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">SKU</label>
                        <input type="text" name="sku"
                               class="form-control form-control-solid"
                               value="${esc(p.sku || '')}">
                        <div class="invalid-feedback" data-error-for="sku"></div>
                    </div>
                </div>

                <div class="row g-5 mb-4">
                    <div class="col-md-4">
                        <label class="form-label required">Category</label>
                        ${categoryOpts}
                        <div class="invalid-feedback" data-error-for="category_id"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Type</label>
                        <select name="type" class="form-select form-select-solid">
                            <option value="">— Select —</option>
                            ${typeOpts}
                        </select>
                        <div class="invalid-feedback" data-error-for="type"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Strategy</label>
                        <select name="inventory_strategy" class="form-select form-select-solid">
                            <option value="">— Select —</option>
                            ${stratOpts}
                        </select>
                        <div class="invalid-feedback" data-error-for="inventory_strategy"></div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Description</label>
                    <textarea name="description"
                              class="form-control form-control-solid"
                              rows="4">${esc(p.description || '')}</textarea>
                    <div class="invalid-feedback" data-error-for="description"></div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-5">
                    <button type="button" class="btn btn-light" data-edit-back>Discard</button>
                    <button type="button" class="btn btn-primary" data-edit-submit style="min-width: 160px;">
                        <span class="indicator-label">
                            <i class="bi bi-check2 me-1"></i>Update
                        </span>
                        <span class="indicator-progress d-none">
                            Please wait… <span class="spinner-border spinner-border-sm ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        `;
    }

    function buildCategoryOptions(selectedId) {
        const cats = Array.isArray(window.PRODUCT_CATEGORIES) ? window.PRODUCT_CATEGORIES : [];

        if (!cats.length) {
            return `<div class="text-muted fs-7">— No categories —</div>`;
        }

        const sid = selectedId != null ? String(selectedId) : '';
        const current = cats.find(c => String(c.id) === sid);

        return `
            <div class="category-combo position-relative" data-category-combo>
                <input type="text"
                    class="form-control form-control-solid"
                    placeholder="Type to search category…"
                    autocomplete="off"
                    value="${esc(current?.name || '')}"
                    data-combo-input>
                <input type="hidden" name="category_id"
                    value="${esc(sid)}"
                    data-combo-value>
                <div class="category-combo-list position-absolute w-100 mt-1 bg-white border rounded shadow-sm d-none"
                    style="max-height: 260px; overflow-y: auto; z-index: 1080;"
                    data-combo-list>
                    ${cats.map(c => `
                        <div class="px-3 py-2 fs-7 cursor-pointer category-combo-item"
                            data-value="${esc(c.id)}"
                            data-label="${esc(c.name)}">${esc(c.name)}</div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function bindEditPanel(id) {
        const body = document.getElementById('productDrawerContent');
        if (!body) return;

        initCategoryCombo(body);

        body.querySelectorAll('[data-edit-back]').forEach(btn =>
            btn.addEventListener('click', () => {
                if (currentPayload?.product) {
                    render(currentPayload.product, currentPayload.variants);
                }
            })
        );

        body.querySelector('[data-edit-submit]')?.addEventListener('click', ev =>
            submitDrawerEdit(id, ev.currentTarget)
        );
    }

    async function submitDrawerEdit(id, btn) {
        const form = document.getElementById('productDrawerEditForm');
        if (!form) return;

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';

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

            window.toastr?.success?.(json.message || 'Product updated.');
            window.ProductList?.reload?.();
            // close(); dont close the drawer
            await reload(id);   // re-fetch /products/{id}/drawer-data and re-render

        } catch (err) {
            console.error('[drawer-edit] failed:', err);
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
        if (label) label.style.visibility = busy ? 'hidden' : '';    // ← label stays in layout
        if (prog) {
            prog.classList.toggle('d-none', !busy);
            prog.style.display = busy ? 'inline-flex' : 'none';
        }
    }

    function openAllocateModal(id) {
        const p  = currentPayload?.product;
        const ao = currentPayload?.allocate_options;

        if (!p || !ao) {
            window.toastr?.error?.('Allocate data not loaded.');
            return;
        }

        // Remove any prior instance
        document.getElementById('allocateModalDynamic')?.remove();

        // Render fresh into <body>
        document.body.insertAdjacentHTML('beforeend', renderAllocateModalShell(p, ao));

        const modalEl = document.getElementById('allocateModalDynamic');
        if (!modalEl) {
            window.toastr?.error?.('Allocate modal failed to render.');
            return;
        }

        // Location ⇄ department checkbox sync
        syncAllocateCheckboxes(modalEl);

        // Submit
        modalEl.querySelector('[data-allocate-submit]')
            ?.addEventListener('click', () => submitAllocate(id, ao.assign_url));

        // Clean up on close
        modalEl.addEventListener('hidden.bs.modal', function onHidden() {
            modalEl.removeEventListener('hidden.bs.modal', onHidden);
            modalEl.remove();
            if (isOpenFor(id)) reload(id);
        }, { once: true });

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitAllocate(id, url) {
        const modalEl = document.getElementById('allocateModalDynamic');
        if (!modalEl) {
            window.toastr?.error?.('Allocate modal not found.');
            return;
        }

        const form = modalEl.querySelector('#allocateFormDynamic');
        if (!form) {
            window.toastr?.error?.('Allocate form not found.');
            return;
        }

        const btn = modalEl.querySelector('[data-allocate-submit]');
        setLoading(btn, true);

        const formData = new FormData(form);
        formData.append('_method', 'PUT');

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     CSRF,
                    'Accept':           'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const json = await res.json().catch(() => ({}));

            if (!res.ok || json.success === false) {
                throw new Error(json.message || 'Allocation failed');
            }

            window.toastr?.success?.(json.message || 'Product allocated.');
            bootstrap.Modal.getInstance(modalEl)?.hide();
            window.ProductList?.reload?.();

        } catch (err) {
            window.toastr?.error?.(err.message);
        } finally {
            setLoading(btn, false);
        }
    }

    function openDeletePanel(id) {
        const body = document.getElementById('productDrawerContent');
        if (!body) return;
        document.getElementById('productDrawerFooter')?.classList.add('d-none');

        body.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold text-gray-800 mb-0">Confirm deletion</h5>
                <button type="button" class="btn btn-sm btn-light" data-panel-back>
                    <i class="bi bi-arrow-left me-1"></i>Back
                </button>
            </div>
            <div class="alert alert-danger">
                <p>Are you sure you want to delete this product?</p>
                <p class="mb-0">This will delete all variants and inventory records. This action cannot be undone.</p>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-5">
                <button type="button" class="btn btn-sm btn-light" data-panel-back>Discard</button>
                <button type="button" class="btn btn-danger" data-delete-submit>
                    <span class="indicator-label"><i class="bi bi-trash me-1"></i>Delete</span>
                    <span class="indicator-progress d-none">Processing…</span>
                </button>
            </div>
        `;

        body.querySelectorAll('[data-panel-back]').forEach(btn =>
            btn.addEventListener('click', () => render(currentPayload.product, currentPayload.variants))
        );

        body.querySelector('[data-delete-submit]')?.addEventListener('click', async ev => {
            const btn = ev.currentTarget;
            setLoading(btn, true);
            try {
                const json = await req('DELETE', URL_DELETE.replace('__ID__', id), null);
                window.toastr?.success?.(json.message);
                window.ProductList?.reload?.();
                close();
            } catch (err) {
                window.toastr?.error?.(err.message);
                setLoading(btn, false);
            }
        });
    }


    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    function fmtNum(n, d = 0) {
        return Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: d, maximumFractionDigits: d,
        });
    }

    function money(n) {
        return (window.PRODUCT_CURRENCY || '') + fmtNum(n, 2);
    }

    function initCategoryCombo(body) {
        const root = body.querySelector('[data-category-combo]');
        if (!root || root.dataset.initialized === '1') return;
        root.dataset.initialized = '1';

        const input  = root.querySelector('[data-combo-input]');
        const hidden = root.querySelector('[data-combo-value]');
        const list   = root.querySelector('[data-combo-list]');
        const items  = Array.from(list.querySelectorAll('.category-combo-item'));

        let activeIndex = -1;

        function showList()   { list.classList.remove('d-none'); }
        function hideList()   { list.classList.add('d-none'); activeIndex = -1; }

        function filter(term) {
            const q = term.trim().toLowerCase();
            items.forEach(el => {
                const match = !q || el.dataset.label.toLowerCase().includes(q);
                el.classList.toggle('d-none', !match);
            });
            activeIndex = -1;
        }

        function highlight(idx) {
            items.forEach(el => el.classList.remove('bg-light'));
            const visible = items.filter(el => !el.classList.contains('d-none'));
            if (idx >= 0 && idx < visible.length) {
                visible[idx].classList.add('bg-light');
                visible[idx].scrollIntoView({ block: 'nearest' });
                activeIndex = idx;
            }
        }

        function pick(item) {
            input.value  = item.dataset.label;
            hidden.value = item.dataset.value;
            hideList();
        }

        input.addEventListener('focus', () => {
            filter('');
            showList();
        });

        input.addEventListener('input', () => {
            filter(input.value);
            showList();
        });

        input.addEventListener('blur', () => {
            setTimeout(hideList, 150);
        });

        input.addEventListener('keydown', (e) => {
            const visible = items.filter(el => !el.classList.contains('d-none'));

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                highlight(Math.min(activeIndex + 1, visible.length - 1));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlight(Math.max(activeIndex - 1, 0));
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIndex >= 0 && visible[activeIndex]) {
                    pick(visible[activeIndex]);
                }
            } else if (e.key === 'Escape') {
                hideList();
                input.blur();
            }
        });

        list.addEventListener('mousedown', (e) => {
            const item = e.target.closest('.category-combo-item');
            if (item) {
                e.preventDefault();
                pick(item);
            }
        });
    }

    window.ProductDrawer = { open, reload, close, isOpenFor };
    return window.ProductDrawer;
})();
</script>