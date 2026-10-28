<script>
window.UOMDrawer = (function () {
    'use strict';

    const URL_SHOW   = window.UOM_ROUTES.drawer_data;
    const URL_STATUS = window.UOM_ROUTES.status;
    const URL_UPDATE = window.UOM_ROUTES.update;
    const URL_DELETE = window.UOM_ROUTES.destroy;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('uomDrawer');
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
            currentPayload = json.uom;
            render(json.uom);
        } catch (err) {
            console.error('[uom-drawer] load failed:', err);
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
            currentPayload = json.uom;
            render(json.uom);
        } catch (err) { console.error('[uom-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('uomDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('uomDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('uomDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('uomDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('uomDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('uomDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(u) {
        currentId = u.id;

        const nameEl = document.getElementById('uomDrawerName');
        if (nameEl) nameEl.textContent = u.name || '';

        document.getElementById('uomDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('uomDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(u)}
            ${renderMetaBlock(u)}
            ${renderVariantBlock(u)}
        `;

        document.getElementById('uomDrawerActions').innerHTML = renderActionCluster(u);
        document.getElementById('uomDrawerFooter').classList.remove('d-none');
        bindActions(u);
    }

    function renderHeaderBlock(u) {
        const statusBadge = u.is_active
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;

        return `
            <div class="d-flex align-items-start justify-content-between gap-3 mb-6">
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(u.name)}</h5>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge badge-light-primary fs-8 py-2 px-3">${esc(u.symbol || '—')}</span>
                        <span class="badge badge-light fs-8">#${u.id}</span>
                        ${statusBadge}
                    </div>
                </div>
            </div>
        `;
    }

    function renderMetaBlock(u) {
        const rows = [
            ['Created by', u.creator_name || '—'],
            ['Email',      u.creator_email || '—'],
            ['Created at', u.created_at || '—'],
            ['Updated at', u.updated_at || '—'],
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
                            <div class="fw-semibold text-gray-800 fs-7">${esc(value)}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderVariantBlock(u) {
        const color = u.variant_count > 0 ? 'warning' : 'success';
        const icon  = u.variant_count > 0 ? 'exclamation-triangle' : 'check-circle';

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-boxes me-2 text-${color}"></i>Usage
            </h6>
            <div class="card bg-light-${color} border border-${color} border-dashed mb-6">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-${icon} fs-2x text-${color}"></i>
                    <div>
                        <div class="fw-bold text-gray-800 fs-5">${u.variant_count} variant(s)</div>
                        <div class="text-muted fs-7">
                            ${u.variant_count > 0
                                ? 'This UOM is used by ' + u.variant_count + ' variant(s) and cannot be deleted while in use.'
                                : 'This UOM is not currently used by any variant.'}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderActionCluster(u) {
        const btns = [];

        if (u.can_toggle) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-status-select data-current-status="${u.is_active ? 1 : 0}"
                        style="width:160px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="1" ${u.is_active ? 'disabled' : ''}>Active</option>
                    <option value="0" ${!u.is_active ? 'disabled' : ''}>Inactive</option>
                </select>
            `);
        }

        if (u.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (u.can_delete && !u.is_active && u.variant_count === 0) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(u) {
        const actions = document.getElementById('uomDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', async () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')   return openEditModal(u);
                if (a === 'delete') return doDelete(u);
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
                    const json = await req('POST', URL_STATUS.replace('__ID__', u.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.UOMList?.reload?.();
                    await reload(u.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    async function doDelete(u) {
        if (!confirm(`Delete UOM "${u.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', u.id), null);
            window.toastr?.success?.(json.message || 'UOM deleted.');
            window.UOMList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    // ─── Shared edit modal ────────────────────────────────
    function openEditModal(u) {
        const modalEl = document.getElementById('uomEditModal');
        const form    = document.getElementById('uomEditForm');
        if (!modalEl || !form) {
            window.toastr?.error?.('Edit modal not available.');
            return;
        }

        // Reset the button first (in case previous session left it stuck)
        const btn = document.getElementById('uomEditSubmit');
        if (btn) setButtonLoading(btn, false);

        // Clear prior errors
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
            el.textContent = '';
            el.classList.remove('d-block');
        });

        // Populate
        document.getElementById('uomEditId').value = u.id;
        const header = document.getElementById('uomEditHeaderName');
        if (header) header.textContent = u.name || '';

        form.querySelector('[name="name"]').value   = u.name || '';
        form.querySelector('[name="symbol"]').value = u.symbol || '';

        // Re-bind submit
        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitUomEdit(u.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitUomEdit(id, btn) {
        const form = document.getElementById('uomEditForm');
        if (!form) return;

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
            el.textContent = '';
            el.classList.remove('d-block');
        });

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data.uomEditId;
        delete data._token;

        Object.keys(data).forEach(k => {
            if (data[k] === '' || data[k] === 'undefined') delete data[k];
        });

        setButtonLoading(btn, true);

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

            if (!res.ok || json.success === false) {
                throw new Error(json.message || 'Update failed');
            }

            window.toastr?.success?.(json.message || 'UOM updated.');
            setButtonLoading(btn, false);

            bootstrap.Modal.getInstance(document.getElementById('uomEditModal'))?.hide();

            try { window.UOMList?.reload?.(); } catch (e) { console.error('[uom table reload]', e); }

            if (isOpenFor(id)) {
                try { await reload(id); } catch (e) { console.error('[uom drawer reload]', e); }
            }

        } catch (err) {
            console.error('[uom-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setButtonLoading(btn, false);
        }
    }


    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.UOMDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.UOMDrawer;
})();
</script>