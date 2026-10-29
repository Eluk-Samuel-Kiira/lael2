<script>
window.LocationDrawer = (function () {
    'use strict';

    const URL_SHOW      = window.LOCATION_ROUTES.drawer_data;
    const URL_STATUS    = window.LOCATION_ROUTES.status;
    const URL_PRIMARY   = window.LOCATION_ROUTES.primary_status;
    const URL_UPDATE    = window.LOCATION_ROUTES.update;
    const URL_DELETE    = window.LOCATION_ROUTES.destroy;
    const CSRF          = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('locationDrawer');
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
            currentPayload = json.location;
            render(json.location);
        } catch (err) {
            console.error('[location-drawer] load failed:', err);
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
            currentPayload = json.location;
            render(json.location);
        } catch (err) { console.error('[location-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('locationDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('locationDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('locationDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('locationDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('locationDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('locationDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(l) {
        currentId = l.id;

        const nameEl = document.getElementById('locationDrawerName');
        if (nameEl) nameEl.textContent = l.name || '';

        document.getElementById('locationDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('locationDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(l)}
            ${renderMetaBlock(l)}
            ${renderDepartmentsBlock(l)}
            ${renderAuditBlock(l)}
        `;

        document.getElementById('locationDrawerActions').innerHTML = renderActionCluster(l);
        document.getElementById('locationDrawerFooter').classList.remove('d-none');
        bindActions(l);
    }

    function renderHeaderBlock(l) {
        const activeBadge = l.is_active
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;

        const primaryBadge = l.is_primary
            ? `<span class="badge badge-light-info fs-7 py-2 px-3"><i class="bi bi-star-fill me-1"></i>Primary</span>`
            : '';

        return `
            <div class="d-flex align-items-start justify-content-between gap-3 mb-6">
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(l.name)}</h5>
                    <div class="text-muted fs-7 mb-2">${esc(l.address || '—')}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light-primary fs-8">${esc(l.currency_code || '—')}</span>
                        ${activeBadge}
                        ${primaryBadge}
                    </div>
                </div>
            </div>
        `;
    }

    function renderMetaBlock(l) {
        const rows = [
            ['Currency',  `${l.currency_code || '—'}${l.currency_name ? ' — ' + l.currency_name : ''}`],
            ['Manager',   l.manager_name || '—'],
            ['Manager Email', l.manager_email || '—'],
            ['Creator',   l.creator_name || '—'],
            ['Creator Email', l.creator_email || '—'],
            ['Created at', l.created_at || '—'],
            ['Updated at', l.updated_at || '—'],
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
                            <div class="fw-semibold text-gray-800 fs-7">${esc(value)}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    // ─── Departments list inside the drawer ────────────────
    function renderDepartmentsBlock(l) {
        const depts = l.departments || [];

        const header = `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-diagram-3 me-2 text-info"></i>Departments
                <span class="badge badge-light-secondary ms-2">${depts.length}</span>
            </h6>
        `;

        if (depts.length === 0) {
            return header + `
                <div class="alert alert-light-warning border border-warning border-dashed py-3 fs-7 mb-6">
                    No departments under this location yet.
                    ${l.can_create_department
                        ? `<a href="/departments" class="ms-2 fw-bold">Add a department →</a>`
                        : ''}
                </div>
            `;
        }

        const rows = depts.map(d => `
            <tr>
                <td>
                    <div class="fw-bold text-gray-800">${esc(d.name)}</div>
                </td>
                <td>${esc(d.manager || '—')}</td>
                <td class="text-center">
                    <span class="badge badge-light-${d.is_active ? 'success' : 'secondary'} fs-8">
                        ${d.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td class="text-muted fs-8">${esc(d.created_at || '—')}</td>
            </tr>
        `).join('');

        return `
            ${header}
            <div class="table-responsive mb-6">
                <table class="table table-row-dashed align-middle fs-7 gy-3 mb-0">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                            <th>Department</th>
                            <th>Manager</th>
                            <th class="text-center">Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    function renderAuditBlock(l) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-clock-history me-2 text-muted"></i>Audit
            </h6>
            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(l.created_at || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Updated at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(l.updated_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderActionCluster(l) {
        const btns = [];

        if (l.can_toggle_primary) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-primary-select data-current-primary="${l.is_primary ? 1 : 0}"
                        style="width:170px; cursor:pointer;">
                    <option value="">Primary…</option>
                    <option value="1" ${l.is_primary ? 'disabled' : ''}>Mark Primary</option>
                    <option value="0" ${!l.is_primary ? 'disabled' : ''}>Not Primary</option>
                </select>
            `);
        }

        if (l.can_toggle_active) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-status-select data-current-status="${l.is_active ? 1 : 0}"
                        style="width:150px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="1" ${l.is_active ? 'disabled' : ''}>Active</option>
                    <option value="0" ${!l.is_active ? 'disabled' : ''}>Inactive</option>
                </select>
            `);
        }

        if (l.can_view_department) {
            btns.push(`<a href="/departments" class="btn btn-sm btn-light d-flex align-items-center">
                        <i class="bi bi-diagram-3 me-2"></i>Manage Departments
                      </a>`);
        }

        if (l.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (l.can_delete && !l.is_active && l.departments_count === 0) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(l) {
        const actions = document.getElementById('locationDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', async () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')   return openEditModal(l);
                if (a === 'delete') return doDelete(l);
            });
        });

        const statusSel = actions.querySelector('[data-drawer-status-select]');
        if (statusSel) {
            statusSel.addEventListener('change', async () => {
                const newStatus = statusSel.value;
                statusSel.value = '';
                if (!newStatus) return;
                statusSel.disabled = true;
                try {
                    const json = await req('POST', URL_STATUS.replace('__ID__', l.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.LocationList?.reload?.();
                    await reload(l.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { statusSel.disabled = false; }
            });
        }

        const primarySel = actions.querySelector('[data-drawer-primary-select]');
        if (primarySel) {
            primarySel.addEventListener('change', async () => {
                const newPrimary = primarySel.value;
                primarySel.value = '';
                if (!newPrimary) return;
                primarySel.disabled = true;
                try {
                    const json = await req('POST', URL_PRIMARY.replace('__ID__', l.id), { status: newPrimary });
                    window.toastr?.success?.(json.message || 'Primary status updated.');
                    window.LocationList?.reload?.();
                    await reload(l.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { primarySel.disabled = false; }
            });
        }
    }

    async function doDelete(l) {
        if (!confirm(`Delete location "${l.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', l.id), null);
            window.toastr?.success?.(json.message || 'Location deleted.');
            window.LocationList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    // ─── Shared edit modal ────────────────────────────────
    function openEditModal(l) {
        const modalEl = document.getElementById('locationEditModal');
        const form    = document.getElementById('locationEditForm');
        if (!modalEl || !form) {
            window.toastr?.error?.('Edit modal not available.');
            return;
        }

        // Reset button
        const btn = document.getElementById('locationEditSubmit');
        if (btn) setButtonLoading(btn, false);

        // Clear errors
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
            el.textContent = '';
            el.classList.remove('d-block');
        });

        // Populate
        document.getElementById('locationEditId').value = l.id;
        const header = document.getElementById('locationEditHeaderName');
        if (header) header.textContent = l.name || '';

        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;

            if (el.tagName === 'SELECT') {
                // Compare as strings — avoids "42" vs 42 mismatches
                const target = value == null ? '' : String(value);
                const matched = Array.from(el.options).some(o => o.value === target);
                el.value = matched ? target : '';
            } else if (el.type === 'checkbox') {
                el.checked = !!value;
            } else {
                el.value = value ?? '';
            }
        };

        setField('name', l.name);
        setField('address', l.address);
        setField('manager_id', l.manager_id);
        setField('currency_id', l.currency_id);

        // Re-bind submit
        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitLocationEdit(l.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitLocationEdit(id, btn) {
        const form = document.getElementById('locationEditForm');
        if (!form) return;

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
            el.textContent = '';
            el.classList.remove('d-block');
        });

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data.locationEditId;
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

            window.toastr?.success?.(json.message || 'Location updated.');
            setButtonLoading(btn, false);

            bootstrap.Modal.getInstance(document.getElementById('locationEditModal'))?.hide();

            try { window.LocationList?.reload?.(); } catch (e) { console.error('[loc table reload]', e); }

            if (isOpenFor(id)) {
                try { await reload(id); } catch (e) { console.error('[loc drawer reload]', e); }
            }

        } catch (err) {
            console.error('[location-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setButtonLoading(btn, false);
        }
    }

    function setButtonLoading(button, isLoading) {
        if (!button) return;
        const label    = button.querySelector('.indicator-label');
        const progress = button.querySelector('.indicator-progress');

        if (isLoading) {
            button.disabled = true;
            if (label)    label.style.display    = 'none';
            if (progress) progress.style.display = 'inline';
        } else {
            button.disabled = false;
            if (label)    label.style.display    = 'inline';
            if (progress) progress.style.display = 'none';
        }
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.LocationDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.LocationDrawer;
})();
</script>