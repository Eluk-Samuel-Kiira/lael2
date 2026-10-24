<script>
window.CategoryDrawer = (function () {
    'use strict';

    const URL_SHOW    = window.CATEGORY_ROUTES.show;
    const URL_DELETE  = window.CATEGORY_ROUTES.destroy;
    const URL_UPDATE  = window.CATEGORY_ROUTES.update;
    const URL_STATUS  = window.CATEGORY_ROUTES.status;
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('categoryDrawer');
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

    // ── open / reload ─────────────────────────────────────────
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
            currentPayload = json.category;
            render(json.category);
        } catch (err) {
            console.error('[category-drawer] reload failed:', err);
        }
    }

    function close() { getBs()?.hide(); }
    function isOpenFor(id) { return String(currentId) === String(id); }

    // ── state helpers ─────────────────────────────────────────
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
        body.innerHTML = `<div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}
        </div>`;
    }

    // ── main render ───────────────────────────────────────────
    function render(cat) {
        currentId = cat.id;
        const name = document.getElementById('categoryDrawerName');
        if (name) name.textContent = cat.name;

        document.getElementById('categoryDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('categoryDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderView(cat);

        const footer = document.getElementById('categoryDrawerFooter');
        const actions = document.getElementById('categoryDrawerActions');
        
        // Render the updated bottom cluster
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
            ? `<span class="badge badge-light-success">${esc('Active')}</span>`
            : `<span class="badge badge-light-warning">${esc('Inactive')}</span>`;
        return `
            <div class="d-flex align-items-start gap-4 mb-6">
                <div class="symbol symbol-50px">
                    <div class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                        ${esc(cat.name?.charAt(0)?.toUpperCase() || '?')}
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
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc('ID')}</div>
                        <div class="fw-bold text-gray-800 fs-7">ID-${esc(cat.id)}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc('Created by')}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(cat.creator?.name || '—')}</div>
                        ${cat.creator?.email ? `<small class="text-muted">${esc(cat.creator.email)}</small>` : ''}
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc('Created at')}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(cat.created_at || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc('Updated at')}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(cat.updated_at || '—')}</div>
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
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">Used as parent</div>
                        <div class="fs-4 fw-bold text-warning">${esc(cat.used_as_parent ?? 0)}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderNotesBlock(cat) {
        if (!cat.description) return '';
        const lines = String(cat.description).split(/\r?\n/).map(l => l.trim()).filter(Boolean);
        if (!lines.length) return '';
        return `
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-gray-300 border-dashed">
                    <i class="bi bi-card-text fs-5 text-info"></i>
                    <h6 class="fw-bold text-gray-800 mb-0">Description</h6>
                </div>
                <div>${lines.map(l => `<p class="mb-3 text-gray-700 fs-7" style="line-height:1.7;">${esc(l)}</p>`).join('')}</div>
            </div>
        `;
    }

    // ─── Action Cluster (Now stacked for the bottom footer) ───
    function renderActionCluster(cat) {
        const statusSelect = renderStatusSelect(cat);
        const actionBar    = renderActionBar(cat);

        if (!statusSelect && !actionBar) return '';

        // Horizontal layout: status select + buttons side-by-side
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
        const bg = isActive ? '#d4edda' : '#fff3cd';
        const fg = isActive ? '#155724' : '#856404';
        const border = isActive ? '#c3e6cb' : '#ffeeba';

        return `
            <select class="form-select form-select-sm fw-bold"
                    data-drawer-status-select
                    data-current-status="${isActive ? 1 : 0}"
                    style="background-color:${bg};color:${fg};border-color:${border};
                           font-weight:600;padding:0.375rem 1.75rem 0.375rem 0.75rem;
                           width:100%;cursor:pointer;">
                <option value="" selected>Status…</option>
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
        `;
    }

    function renderActionBar(cat) {
        const btns = [];
        // Note: Using text labels + icons now since it's at the bottom,
        // which looks better than floating icons.
        const btn = (action, icon, label, tone = 'secondary') => `
            <button type="button"
                    class="btn btn-sm btn-light-${tone} d-flex align-items-center"
                    data-drawer-action="${action}">
                <i class="bi ${icon} me-2 fs-5"></i>
                ${label}
            </button>
        `;

        if (cat.can_edit)   btns.push(btn('edit',   'bi-pencil-square', 'Edit', 'primary'));
        if (cat.can_delete) btns.push(btn('delete', 'bi-trash',         'Delete', 'danger'));

        if (!btns.length) return '';
        return btns.join('');
    }

    function bindActionCluster(cat) {
        const actions = document.getElementById('categoryDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const action = el.getAttribute('data-drawer-action');
                if (action === 'edit')   return openEditModal(cat.id);
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
                    window.CategoryList?.reload?.();
                    await reload(cat.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally {
                    sel.disabled = false;
                }
            });
        }
    }

    // ── EDIT MODAL trigger ────────────────────────────────────
    function openEditModal(id) {
        // Ensure we have the data
        if (!currentPayload || String(currentPayload.id) !== String(id)) {
            window.toastr?.error?.('Category data not loaded.');
            return;
        }

        // 1. Populate the modal fields
        document.getElementById('edit_category_id').value = currentPayload.id;
        document.getElementById('edit_category_name').value = currentPayload.name || '';
        document.getElementById('edit_category_description').value = currentPayload.description || '';

        // 2. Close the drawer first
        close();

        // 3. Show the modal after the drawer finishes hiding
        const drawerEl = document.getElementById('categoryDrawer');
        const show = () => {
            const modalEl = document.getElementById('kt_modal_edit_category');
            if (!modalEl) {
                window.toastr?.error?.('Edit modal not found.');
                return;
            }
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        };

        if (!drawerEl) return show();
        drawerEl.addEventListener('hidden.bs.offcanvas', show, { once: true });
    }

    // ── DELETE panel — inside the drawer ──────────────────────
    function openDeletePanel(id) {
        const body = document.getElementById('categoryDrawerContent');
        if (!body) return;

        // Hide the footer actions while confirming deletion
        document.getElementById('categoryDrawerFooter')?.classList.add('d-none');

        body.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold text-gray-800 mb-0">Confirm deletion</h5>
                <button type="button" class="btn btn-sm btn-light" data-panel-back>
                    <i class="bi bi-arrow-left me-1"></i>Back
                </button>
            </div>
            <div class="alert alert-danger">
                <p>${esc('Are you sure?')}</p>
                <p class="mb-0">${esc('This action cannot be undone.')}</p>
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
                window.CategoryList?.reload?.();
                close();
            } catch (err) {
                window.toastr?.error?.(err.message);
                setLoading(btn, false);
            }
        });
    }

    // ── helpers ──────────────────────────────────────────────
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

    window.CategoryDrawer = { open, reload, close, isOpenFor, openEditModal, openDeletePanel };
    return window.CategoryDrawer;
})();
</script>