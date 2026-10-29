<script>
window.CGDrawer = (function () {
    'use strict';

    const URL_SHOW   = window.CG_ROUTES.drawer_data;
    const URL_UPDATE = window.CG_ROUTES.update;
    const URL_DELETE = window.CG_ROUTES.destroy;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('cgDrawer');
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
            currentPayload = json.group;
            render(json.group);
        } catch (err) {
            console.error('[cg-drawer] load failed:', err);
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
            currentPayload = json.group;
            render(json.group);
        } catch (err) { console.error('[cg-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('cgDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('cgDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('cgDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('cgDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('cgDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('cgDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(g) {
        currentId = g.id;

        const nameEl = document.getElementById('cgDrawerName');
        if (nameEl) nameEl.textContent = g.name || '';

        document.getElementById('cgDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('cgDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(g)}
            ${renderStatsBlock(g)}
            ${renderMetaBlock(g)}
            ${renderCustomersBlock(g)}
            ${renderAuditBlock(g)}
        `;

        document.getElementById('cgDrawerActions').innerHTML = renderActionCluster(g);
        document.getElementById('cgDrawerFooter').classList.remove('d-none');
        bindActions(g);
    }

    function renderHeaderBlock(g) {
        const defaultBadge = g.is_default
            ? `<span class="badge badge-light-success fs-7 py-2 px-3"><i class="bi bi-star-fill me-1"></i>Default Group</span>`
            : '';

        return `
            <div class="mb-6">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                    <h5 class="fw-bold text-gray-800 mb-0">${esc(g.name)}</h5>
                    ${defaultBadge}
                </div>
            </div>
        `;
    }

    function renderStatsBlock(g) {
        return `
            <div class="row g-3 mb-6">
                <div class="col-6">
                    <div class="card bg-light-info">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Discount</span>
                            <span class="fs-3 fw-bold text-info">${Number(g.discount_percentage).toFixed(2)}%</span>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card bg-light-primary">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Customers</span>
                            <span class="fs-3 fw-bold text-primary">${g.customers_count}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderMetaBlock(g) {
        const rows = [
            ['Creator',       g.creator_name],
            ['Creator Email', g.creator_email],
            ['Created at',    g.created_at],
            ['Updated at',    g.updated_at],
        ];

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-info-circle me-2 text-primary"></i>Details
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-3">
                    ${rows.map(([label, value]) => `
                        <div class="col-6">
                            <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(label)}</div>
                            <div class="fw-semibold text-gray-800 fs-7">${esc(value || '—')}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderCustomersBlock(g) {
        const customers = g.sample_customers || [];

        if (g.customers_count === 0) {
            return `
                <h6 class="fw-bold text-gray-800 mb-3">
                    <i class="bi bi-people me-2 text-success"></i>Customers
                </h6>
                <div class="alert alert-light-info border border-info border-dashed py-3 fs-7">
                    No customers assigned to this group yet.
                </div>
            `;
        }

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-people me-2 text-success"></i>Customers
                <span class="badge badge-light-secondary ms-2">${g.customers_count}</span>
                ${g.customers_count > 10 ? `<small class="text-muted fs-8 ms-2">showing 10 of ${g.customers_count}</small>` : ''}
            </h6>
            <div class="table-responsive mb-6">
                <table class="table table-row-dashed align-middle fs-7 gy-3 mb-0">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${customers.map(c => `
                            <tr>
                                <td class="fw-semibold">${esc(c.name || '—')}</td>
                                <td>${esc(c.email || '—')}</td>
                                <td>${esc(c.phone || '—')}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    function renderAuditBlock(g) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-clock-history me-2 text-muted"></i>Audit
            </h6>
            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(g.created_at || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Updated at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(g.updated_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderActionCluster(g) {
        const btns = [];

        if (g.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (g.can_delete && !g.is_default && g.customers_count === 0) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(g) {
        const actions = document.getElementById('cgDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')   return openEditModal(g);
                if (a === 'delete') return doDelete(g);
            });
        });
    }

    async function doDelete(g) {
        if (!confirm(`Delete customer group "${g.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', g.id), null);
            window.toastr?.success?.(json.message || 'Group deleted.');
            window.CGList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    function openEditModal(g) {
        const modalEl = document.getElementById('cgEditModal');
        const form    = document.getElementById('cgEditForm');
        if (!modalEl || !form) { window.toastr?.error?.('Edit modal not available.'); return; }

        const btn = document.getElementById('cgEditSubmit');
        if (btn) setButtonLoading(btn, false);
        clearFormErrors(form);

        document.getElementById('cgEditId').value = g.id;
        const header = document.getElementById('cgEditHeaderName');
        if (header) header.textContent = g.name || '';

        form.querySelector('[name="name"]').value                = g.name || '';
        form.querySelector('[name="discount_percentage"]').value = g.discount_percentage ?? '';
        form.querySelector('[name="is_default"]').checked        = !!g.is_default;

        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitCgEdit(g.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitCgEdit(id, btn) {
        const form = document.getElementById('cgEditForm');
        if (!form) return;

        clearFormErrors(form);

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data.cgEditId;
        delete data._token;

        data.is_default = form.querySelector('[name="is_default"]')?.checked ? 1 : 0;

        Object.keys(data).forEach(k => {
            if (data[k] === '' || data[k] === 'undefined') delete data[k];
        });

        setButtonLoading(btn, true);

        try {
            const res = await fetch(URL_UPDATE.replace('__ID__', id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams(data),
            });

            const json = await res.json().catch(() => ({}));

            if (res.status === 422) {
                Object.entries(json.errors || {}).forEach(([field, msgs]) => {
                    const input  = form.querySelector(`[name="${field}"]`);
                    const holder = form.querySelector(`#${CSS.escape(field)}`);
                    if (input)  input.classList.add('is-invalid');
                    if (holder) {
                        holder.classList.add('invalid-feedback', 'd-block');
                        holder.textContent = msgs[0];
                    }
                });
                window.toastr?.error?.(json.message || 'Please fix the highlighted fields.');
                setButtonLoading(btn, false);
                return;
            }

            if (!res.ok || json.success === false) throw new Error(json.message || 'Update failed');

            window.toastr?.success?.(json.message || 'Group updated.');
            setButtonLoading(btn, false);

            bootstrap.Modal.getInstance(document.getElementById('cgEditModal'))?.hide();

            try { window.CGList?.reload?.(); } catch (e) { console.error('[cg table reload]', e); }
            if (isOpenFor(id)) {
                try { await reload(id); } catch (e) { console.error('[cg drawer reload]', e); }
            }

        } catch (err) {
            console.error('[cg-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setButtonLoading(btn, false);
        }
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.CGDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.CGDrawer;
})();
</script>