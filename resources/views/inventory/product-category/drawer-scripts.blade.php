<script>
window.ProductCategoryDrawer = (function () {
    'use strict';

    const URL_SHOW    = window.PCATEGORY_ROUTES.show;
    const URL_DELETE  = window.PCATEGORY_ROUTES.destroy;
    const URL_STATUS  = window.PCATEGORY_ROUTES.status;
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('pcategoryDrawer');
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
        if (!res.ok || json.success === false) throw new Error(json.message || 'Request failed');
        return json;
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
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed to load');
            currentPayload = json.category;
            render(json.category);
        } catch (err) {
            console.error('[pcat-drawer] load failed:', err);
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
            currentPayload = json.category;
            render(json.category);
        } catch (err) {
            console.error('[pcat-drawer] reload failed:', err);
        }
    }

    function close() { getBs()?.hide(); }
    function isOpenFor(id) { return String(currentId) === String(id); }

    function showSkeleton() {
        document.getElementById('pcategoryDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('pcategoryDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('pcategoryDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('pcategoryDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('pcategoryDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('pcategoryDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}
        </div>`;
    }

    function render(cat) {
        currentId = cat.id;
        const name = document.getElementById('pcategoryDrawerName');
        if (name) name.textContent = cat.name;

        document.getElementById('pcategoryDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('pcategoryDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderView(cat);

        const footer = document.getElementById('pcategoryDrawerFooter');
        const actions = document.getElementById('pcategoryDrawerActions');
        actions.innerHTML = renderActionCluster(cat);
        footer.classList.remove('d-none');
        bindActionCluster(cat);
    }

    function renderView(cat) {
        return `
            ${renderHeaderBlock(cat)}
            ${renderMetaBlock(cat)}
            ${renderUsageBlock(cat)}
            ${renderNotesBlock(cat)}
        `;
    }

    function renderHeaderBlock(cat) {
        const statusBadge = cat.is_active
            ? `<span class="badge badge-light-success">Active</span>`
            : `<span class="badge badge-light-warning">Inactive</span>`;
        
        const imageHtml = cat.image_url 
            ? `<img src="${esc(cat.image_url)}" class="w-100 h-100 object-fit-cover" style="border-radius: 50%;" />`
            : `<span class="text-primary fw-bold fs-3">${esc(cat.name?.charAt(0)?.toUpperCase() || '?')}</span>`;

        return `
            <div class="d-flex align-items-start gap-4 mb-6">
                <div class="symbol symbol-50px symbol-circle">
                    <div class="symbol-label bg-light-primary text-primary">
                        ${imageHtml}
                    </div>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(cat.name || '—')}</h5>
                    <div class="d-flex flex-wrap gap-3 fs-7 text-muted">
                        <span><i class="bi bi-upc-scan me-1"></i>${esc(cat.slug || '—')}</span>
                        ${statusBadge}
                    </div>
                </div>
            </div>
        `;
    }

    function renderMetaBlock(cat) {
        return `
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">ID</div>
                        <div class="fw-bold text-gray-800 fs-7">ID-${esc(cat.id)}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Parent Category</div>
                        <div class="fw-bold text-gray-800 fs-7">${esc(cat.parent_name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created by</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(cat.creator?.name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(cat.created_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderUsageBlock(cat) {
        return `
            <div class="row g-3 mb-6">
                <div class="col-6">
                    <div class="bg-light-info rounded-3 p-3 text-center h-100">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">Products</div>
                        <div class="fs-4 fw-bold text-info">${esc(cat.product_count ?? 0)}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-light-warning rounded-3 p-3 text-center h-100">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">Subcategories</div>
                        <div class="fs-4 fw-bold text-warning">${esc(cat.child_count ?? 0)}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderNotesBlock(cat) {
        if (!cat.description) return '';
        return `
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-gray-300 border-dashed">
                    <i class="bi bi-card-text fs-5 text-info"></i>
                    <h6 class="fw-bold text-gray-800 mb-0">Description</h6>
                </div>
                <div><p class="mb-0 text-gray-700 fs-7" style="line-height:1.7;">${esc(cat.description)}</p></div>
            </div>
        `;
    }

    // ─── Action Cluster ───
    function renderActionCluster(cat) {
        const statusSelect = renderStatusSelect(cat);
        const actionBar    = renderActionBar(cat);
        if (!statusSelect && !actionBar) return '';

        return `
            <div class="d-flex align-items-center justify-content-end gap-2 w-100">
                ${statusSelect ? `<div style="width: 160px;">${statusSelect}</div>` : ''}
                ${actionBar ? `<div class="d-flex align-items-center gap-2">${actionBar}</div>` : ''}
            </div>
        `;
    }

    function renderStatusSelect(cat) {
        if (!cat.can_toggle) return '';
        const isActive = !!cat.is_active;
        return `
            <select class="form-select form-select-sm fw-bold" data-drawer-status-select data-current-status="${isActive ? 1 : 0}"
                    style="background-color:${isActive ? '#d4edda' : '#fff3cd'}; color:${isActive ? '#155724' : '#856404'}; border-color:${isActive ? '#c3e6cb' : '#ffeeba'}; width:100%; cursor:pointer;">
                <option value="" selected>Status…</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        `;
    }

    function renderActionBar(cat) {
        const btns = [];
        const btn = (action, icon, label, tone = 'secondary') => `
            <button type="button" class="btn btn-sm btn-light-${tone} d-flex align-items-center" data-drawer-action="${action}">
                <i class="bi ${icon} me-2 fs-5"></i>${label}
            </button>
        `;
        if (cat.can_edit)   btns.push(btn('edit',   'bi-pencil-square', 'Edit', 'primary'));
        if (cat.can_delete) btns.push(btn('delete', 'bi-trash',         'Delete', 'danger'));
        return btns.join('');
    }

    function bindActionCluster(cat) {
        const actions = document.getElementById('pcategoryDrawerActions');
        
        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const action = el.getAttribute('data-drawer-action');
                if (action === 'edit')   return openEditModal(cat);
                if (action === 'delete') return openDeletePanel(cat.id);
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
                    const json = await req('POST', URL_STATUS.replace('__ID__', cat.id), { status: newStatus });
                    window.toastr?.success?.(json.message);
                    window.ProductCategoryList?.reload?.();
                    await reload(cat.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally {
                    sel.disabled = false;
                }
            });
        }
    }

    function openEditModal(cat) {
        document.getElementById('edit_pcat_id').value          = cat.id;
        document.getElementById('edit_pcat_name').value        = cat.name || '';
        document.getElementById('edit_pcat_description').value = cat.description || '';
        document.getElementById('edit_pcat_status').value      = cat.is_active ? '1' : '0';

        const parentSel = document.getElementById('edit_pcat_parent');
        parentSel.innerHTML = '<option value="0">None (Root)</option>';

        const currentParentId = String(cat.parent_id ?? 0);

        (cat.parent_options || []).forEach(opt => {
            const option = document.createElement('option');
            option.value = opt.id;
            option.textContent = opt.name;
            if (String(opt.id) === currentParentId) option.selected = true;
            parentSel.appendChild(option);
        });

        parentSel.value = currentParentId;

        close();
        const drawerEl = document.getElementById('pcategoryDrawer');
        const show = () =>
            bootstrap.Modal.getOrCreateInstance(
                document.getElementById('kt_modal_edit_pcategory')
            ).show();
        if (drawerEl) drawerEl.addEventListener('hidden.bs.offcanvas', show, { once: true });
        else show();
    }

    function openDeletePanel(id) {
        const body = document.getElementById('pcategoryDrawerContent');
        if (!body) return;

        document.getElementById('pcategoryDrawerFooter')?.classList.add('d-none');

        body.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold text-gray-800 mb-0">Confirm deletion</h5>
                <button type="button" class="btn btn-sm btn-light" data-panel-back>
                    <i class="bi bi-arrow-left me-1"></i>Back
                </button>
            </div>
            <div class="alert alert-danger">
                <p>${esc('Are you sure you want to delete this category?')}</p>
                <p class="mb-0">${esc('This action cannot be undone. Categories with active products or subcategories cannot be deleted.')}</p>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-5">
                <button type="button" class="btn btn-sm btn-light" data-panel-back>Discard</button>
                <button type="button" class="btn btn-danger" data-delete-submit>
                    <span class="indicator-label"><i class="bi bi-trash me-1"></i>Delete</span>
                    <span class="indicator-progress d-none">Processing <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        `;

        body.querySelectorAll('[data-panel-back]').forEach(btn => {
            btn.addEventListener('click', () => render(currentPayload));
        });

        body.querySelector('[data-delete-submit]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            setLoading(btn, true);
            try {
                const json = await req('DELETE', URL_DELETE.replace('__ID__', id), null);
                window.toastr?.success?.(json.message);
                window.ProductCategoryList?.reload?.();
                close();
            } catch (err) {
                window.toastr?.error?.(err.message);
                setLoading(btn, false);
            }
        });
    }

    function setLoading(btn, busy) {
        if (!btn) return;
        if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';

        const label    = btn.querySelector('.indicator-label');
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

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    window.ProductCategoryDrawer = { open, reload, close, isOpenFor, openEditModal, openDeletePanel };
    return window.ProductCategoryDrawer;
})();
</script>