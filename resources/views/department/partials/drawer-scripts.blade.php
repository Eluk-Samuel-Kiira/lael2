<script>
window.DeptDrawer = (function () {
    'use strict';

    const URL_SHOW   = window.DEPT_ROUTES.drawer_data;
    const URL_STATUS = window.DEPT_ROUTES.status;
    const URL_UPDATE = window.DEPT_ROUTES.update;
    const URL_DELETE = window.DEPT_ROUTES.destroy;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('deptDrawer');
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
            currentPayload = json.department;
            render(json.department);
        } catch (err) {
            console.error('[dept-drawer] load failed:', err);
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
            currentPayload = json.department;
            render(json.department);
        } catch (err) { console.error('[dept-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('deptDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('deptDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('deptDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('deptDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('deptDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('deptDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(d) {
        currentId = d.id;

        const nameEl = document.getElementById('deptDrawerName');
        if (nameEl) nameEl.textContent = d.name || '';

        document.getElementById('deptDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('deptDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(d)}
            ${renderMetaBlock(d)}
            ${renderUsersBlock(d)}
            ${renderAuditBlock(d)}
        `;

        document.getElementById('deptDrawerActions').innerHTML = renderActionCluster(d);
        document.getElementById('deptDrawerFooter').classList.remove('d-none');
        bindActions(d);
    }

    function renderHeaderBlock(d) {
        const statusBadge = d.is_active
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;

        return `
            <div class="mb-6">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                    <h5 class="fw-bold text-gray-800 mb-0">${esc(d.name)}</h5>
                    ${statusBadge}
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge badge-light-info fs-8"><i class="bi bi-geo-alt me-1"></i>${esc(d.location_name || '—')}</span>
                    ${d.department_type ? `<span class="badge badge-light fs-8">${esc(d.department_type)}</span>` : ''}
                    ${d.default_inventory_strategy ? `<span class="badge badge-light-primary fs-8">${esc(d.default_inventory_strategy)}</span>` : ''}
                </div>
            </div>
        `;
    }

    function renderMetaBlock(d) {
        const rows = [
            ['Location', d.location_name],
            ['Manager', d.manager_name],
            ['Manager Email', d.manager_email],
            ['Creator', d.creator_name],
            ['Department Type', d.department_type],
            ['Default Strategy', d.default_inventory_strategy],
        ];

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-info-circle me-2 text-primary"></i>Details
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-3">
                    ${rows.map(([label, value]) => `
                        <div class="col-6 col-md-4">
                            <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(label)}</div>
                            <div class="fw-semibold text-gray-800 fs-7">${esc(value || '—')}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderUsersBlock(d) {
        const users = d.users || [];

        if (users.length === 0) return '';

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-people me-2 text-info"></i>Assigned Users
                <span class="badge badge-light-secondary ms-2">${users.length}</span>
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="d-flex flex-wrap gap-2">
                    ${users.map(u => `<span class="badge badge-light-primary fs-8 py-2 px-3">${esc(u.name)}</span>`).join('')}
                </div>
            </div>
        `;
    }

    function renderAuditBlock(d) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-clock-history me-2 text-muted"></i>Audit
            </h6>
            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(d.created_at || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Updated at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(d.updated_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderActionCluster(d) {
        const btns = [];

        if (d.can_toggle) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-status-select data-current-status="${d.is_active ? 1 : 0}"
                        style="width:150px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="1" ${d.is_active ? 'disabled' : ''}>Active</option>
                    <option value="0" ${!d.is_active ? 'disabled' : ''}>Inactive</option>
                </select>
            `);
        }

        if (d.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (d.can_delete && !d.is_active && d.users_count === 0) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(d) {
        const actions = document.getElementById('deptDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')   return openEditModal(d);
                if (a === 'delete') return doDelete(d);
            });
        });

        const sel = actions.querySelector('[data-drawer-status-select]');
        if (sel) {
            sel.addEventListener('change', async () => {
                const newStatus = sel.value;
                sel.value = '';
                if (!newStatus) return;
                sel.disabled = true;
                try {
                    const json = await req('POST', URL_STATUS.replace('__ID__', d.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.DeptList?.reload?.();
                    await reload(d.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    async function doDelete(d) {
        if (!confirm(`Delete department "${d.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', d.id), null);
            window.toastr?.success?.(json.message || 'Department deleted.');
            window.DeptList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    // ─── Shared edit modal ────────────────────────────────
    function openEditModal(d) {
        const modalEl = document.getElementById('deptEditModal');
        const form    = document.getElementById('deptEditForm');
        if (!modalEl || !form) { window.toastr?.error?.('Edit modal not available.'); return; }

        const btn = document.getElementById('deptEditSubmit');
        if (btn) setButtonLoading(btn, false);

        clearFormErrors(form);

        document.getElementById('deptEditId').value = d.id;
        const header = document.getElementById('deptEditHeaderName');
        if (header) header.textContent = d.name || '';

        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.tagName === 'SELECT') {
                const target = value == null ? '' : String(value);
                el.value = Array.from(el.options).some(o => o.value === target) ? target : '';
            } else if (el.type === 'checkbox') {
                el.checked = !!value;
            } else {
                el.value = value ?? '';
            }
        };

        setField('name', d.name);
        setField('manager_id', d.manager_id);
        setField('location_id', d.location_id);
        setField('department_type', d.department_type);
        setField('default_inventory_strategy', d.default_inventory_strategy);

        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitDeptEdit(d.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitDeptEdit(id, btn) {
        const form = document.getElementById('deptEditForm');
        if (!form) return;

        clearFormErrors(form);

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data.deptEditId;
        delete data._token;

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

            window.toastr?.success?.(json.message || 'Department updated.');
            setButtonLoading(btn, false);

            bootstrap.Modal.getInstance(document.getElementById('deptEditModal'))?.hide();

            try { window.DeptList?.reload?.(); } catch (e) { console.error('[dept table reload]', e); }
            if (isOpenFor(id)) {
                try { await reload(id); } catch (e) { console.error('[dept drawer reload]', e); }
            }

        } catch (err) {
            console.error('[dept-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setButtonLoading(btn, false);
        }
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.DeptDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.DeptDrawer;
})();
</script>