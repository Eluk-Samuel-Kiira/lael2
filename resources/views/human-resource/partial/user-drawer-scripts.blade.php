<script>
window.UserDrawer = (function () {
    'use strict';

    if (!window.USER_ROUTES) {
        console.error('[user-drawer] USER_ROUTES undefined.');
        return { open: () => {}, reload: () => {}, close: () => {}, isOpenFor: () => false, getCurrent: () => ({ id: null, payload: null }) };
    }

    const URL_SHOW   = window.USER_ROUTES.drawer_data;
    const URL_STATUS = window.USER_ROUTES.status;
    const URL_UPDATE = window.USER_ROUTES.update;
    const URL_DELETE = window.USER_ROUTES.destroy;
    const URL_DEPTS  = window.USER_ROUTES.update_depts;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId = null, currentPayload = null, bs = null;

    function getBs() {
        const el = document.getElementById('userDrawer');
        if (!el) { console.error('[user-drawer] #userDrawer not found'); return null; }
        if (!window.bootstrap?.Offcanvas) { console.error('[user-drawer] bootstrap.Offcanvas missing'); return null; }
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
            currentPayload = json.employee;
            render(json.employee);
        } catch (err) {
            console.error('[user-drawer] load failed:', err);
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
            currentPayload = json.employee;
            render(json.employee);
        } catch (err) { console.error('[user-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('userDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('userDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('userDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('userDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('userDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('userDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(e) {
        currentId = e.id;
        const nameEl = document.getElementById('userDrawerName');
        if (nameEl) nameEl.textContent = e.name || '';

        document.getElementById('userDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('userDrawerContent');
        if (!body) { console.error('[user-drawer] #userDrawerContent missing'); return; }
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(e)}
            ${renderMetaBlock(e)}
            ${renderAssignmentsBlock(e)}
            ${renderAuditBlock(e)}
        `;

        document.getElementById('userDrawerActions').innerHTML = renderActionCluster(e);
        document.getElementById('userDrawerFooter').classList.remove('d-none');
        bindActions(e);
    }

    function renderHeaderBlock(e) {
        const activeBadge = e.status === 'active'
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;
        return `
            <div class="d-flex align-items-center gap-4 mb-6">
                <div class="symbol symbol-circle symbol-70px">
                    <img src="${esc(e.profile_image)}" alt="${esc(e.name)}" class="w-100" />
                </div>
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(e.name)}</h5>
                    <div class="text-muted fs-7 mb-2">${esc(e.email)}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light-primary fs-8">${esc(e.role_name)}</span>
                        ${activeBadge}
                    </div>
                </div>
            </div>
        `;
    }

    function renderMetaBlock(e) {
        const rows = [
            ['Job Title',  e.job_title || '—'],
            ['Phone',      e.telephone_number || '—'],
            ['Department', e.department_name || '—'],
            ['Location',   e.location_name || '—'],
        ];
        return `
            <h6 class="fw-bold text-gray-800 mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Details</h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-3">
                    ${rows.map(([label, value]) => `
                        <div class="col-6 col-md-3">
                            <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(label)}</div>
                            <div class="fw-semibold text-gray-800 fs-7">${esc(value)}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderAssignmentsBlock(e) {
        const locs  = e.all_locations || [];
        const depts = e.all_departments_by_location || {};
        const assignedDepts = (e.assigned_department_ids || []).map(String);
        const assignedLocs  = (e.assigned_location_ids  || []).map(String);

        const deptHtml = locs.map(loc => {
            const list = depts[loc.id] || [];
            if (!list.length) return '';
            return `
                <div class="mb-3">
                    <span class="badge badge-light-info mb-2"><i class="bi bi-geo-alt me-1"></i>${esc(loc.name)}</span>
                    <div class="row g-2 ps-3 border-start border-2 border-gray-200">
                        ${list.map(d => `
                            <div class="col-md-6">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input type="checkbox" class="form-check-input"
                                           data-assign-dept value="${d.id}"
                                           ${assignedDepts.includes(String(d.id)) ? 'checked' : ''}>
                                    <span class="form-check-label fs-7">${esc(d.name)}</span>
                                </label>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }).join('') || '<div class="alert alert-light text-muted fs-7">No departments available.</div>';

        const locHtml = locs.map(l => `
            <div class="col-md-6">
                <label class="form-check form-check-custom form-check-solid">
                    <input type="checkbox" class="form-check-input"
                           data-assign-loc value="${l.id}"
                           ${assignedLocs.includes(String(l.id)) ? 'checked' : ''}>
                    <span class="form-check-label fs-7">${esc(l.name)}</span>
                </label>
            </div>
        `).join('');

        return `
            <h6 class="fw-bold text-gray-800 mb-3"><i class="bi bi-diagram-3 me-2 text-info"></i>Departments &amp; Locations</h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                ${deptHtml}
                <div class="separator separator-dashed my-4"></div>
                <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-1 text-info"></i>Locations</h6>
                <div class="row g-2">${locHtml}</div>
            </div>
        `;
    }

    function renderAuditBlock(e) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3"><i class="bi bi-clock-history me-2 text-muted"></i>Audit</h6>
            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(e.created_at || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Updated at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(e.updated_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderActionCluster(e) {
        const btns = [];

        if (e.can_toggle_status) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold" data-drawer-status-select style="width:150px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="active"   ${e.status === 'active'   ? 'disabled' : ''}>Active</option>
                    <option value="inactive" ${e.status === 'inactive' ? 'disabled' : ''}>Inactive</option>
                </select>
            `);
        }

        if (e.can_assign) {
            btns.push(`<button type="button" class="btn btn-sm btn-light d-flex align-items-center"
                              data-drawer-action="save-assignments">
                        <i class="bi bi-building me-2"></i>Save Assignments
                      </button>`);
        }

        if (e.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (e.can_delete) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(e) {
        const actions = document.getElementById('userDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', async () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')             return openEditModal(e);
                if (a === 'delete')           return doDelete(e);
                if (a === 'save-assignments') return saveAssignments(e, el);
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
                    const json = await req('POST', URL_STATUS.replace('__ID__', e.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.UserList?.reload?.();
                    await reload(e.id);
                } catch (err) { window.toastr?.error?.(err.message); }
                finally { statusSel.disabled = false; }
            });
        }
    }

    async function saveAssignments(e, btn) {
        const deptIds = Array.from(document.querySelectorAll('[data-assign-dept]:checked')).map(el => el.value);
        const locIds  = Array.from(document.querySelectorAll('[data-assign-loc]:checked')).map(el => el.value);

        if (btn) setButtonLoading(btn, true);
        try {
            const res = await fetch(URL_DEPTS.replace('__ID__', e.id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({
                    _method: 'PUT',
                    ...Object.fromEntries(deptIds.map((id, i) => [`departments[${i}]`, id])),
                    ...Object.fromEntries(locIds.map((id, i)  => [`locations[${i}]`, id])),
                }),
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || json.success === false) throw new Error(json.message || 'Save failed');
            window.toastr?.success?.(json.message || 'Assignments saved.');
            window.UserList?.reload?.();
            await reload(e.id);
        } catch (err) {
            window.toastr?.error?.(err.message);
        } finally { if (btn) setButtonLoading(btn, false); }
    }

    async function doDelete(e) {
        if (!confirm(`Delete user "${e.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', e.id), null);
            window.toastr?.success?.(json.message || 'User deleted.');
            window.UserList?.reload?.();
            close();
        } catch (err) { window.toastr?.error?.(err.message); }
    }

    // ─── Edit modal (location → department dependent) ─────
    function openEditModal(e) {
        const modalEl = document.getElementById('userEditModal');
        const form    = document.getElementById('userEditForm');
        if (!modalEl || !form) {
            console.error('[user-drawer] edit modal nodes missing', {
                modalEl: !!modalEl, form: !!form,
            });
            window.toastr?.error?.('Edit modal not available.');
            return;
        }

        const btn = document.getElementById('userEditSubmit');
        if (btn) setButtonLoading(btn, false);
        clearErrors(form);

        const idInput = document.getElementById('userEditId');
        if (idInput) idInput.value = e.id;
        const header = document.getElementById('userEditHeaderName');
        if (header) header.textContent = e.name || '';

        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.tagName === 'SELECT') {
                const target = value == null ? '' : String(value);
                const matched = Array.from(el.options).some(o => o.value === target);
                el.value = matched ? target : '';
            } else {
                el.value = value ?? '';
            }
        };

        setField('first_name', e.first_name);
        setField('last_name',  e.last_name);
        setField('email',      e.email);
        setField('telephone_number', e.telephone_number);
        setField('job_title',  e.job_title);

        // Role select
        const roleSel = form.querySelector('[name="role_id"]');
        if (roleSel) {
            roleSel.innerHTML = '<option value="">— Select —</option>' +
                (e.assignable_roles || []).map(r =>
                    `<option value="${r.id}" ${String(r.id) === String(e.role_id) ? 'selected' : ''}>${esc(r.name)}</option>`
                ).join('');
        }

        // Location select
        const locSel = document.getElementById('userEditLocation');
        if (locSel) {
            locSel.innerHTML = '<option value="">— Select —</option>' +
                (e.all_locations || []).map(l =>
                    `<option value="${l.id}" ${String(l.id) === String(e.location_id) ? 'selected' : ''}>${esc(l.name)}</option>`
                ).join('');
        }

        // Department select — scoped to the selected location
        const deptSel = document.getElementById('userEditDepartment');
        const rebuildDepartments = (locationId, selectedDeptId = null) => {
            if (!deptSel) return;
            const grouped = e.all_departments_by_location || {};
            const list = grouped[locationId] || [];
            deptSel.innerHTML = '<option value="">— Select —</option>' +
                list.map(d =>
                    `<option value="${d.id}" ${String(d.id) === String(selectedDeptId) ? 'selected' : ''}>${esc(d.name)}</option>`
                ).join('');
        };

        rebuildDepartments(e.location_id, e.department_id);

        if (locSel) {
            locSel.addEventListener('change', () => {
                rebuildDepartments(locSel.value, null);
            });
        }

        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitEdit(e.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitEdit(id, btn) {
        const form = document.getElementById('userEditForm');
        if (!form) return;
        clearErrors(form);

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data._token;
        delete data.userEditId;
        Object.keys(data).forEach(k => { if (data[k] === '' || data[k] === 'undefined') delete data[k]; });

        setButtonLoading(btn, true);
        try {
            const res = await fetch(URL_UPDATE.replace('__ID__', id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json',
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
                    if (holder) { holder.classList.add('invalid-feedback', 'd-block'); holder.textContent = msgs[0]; }
                });
                window.toastr?.error?.(json.message || 'Please fix the highlighted fields.');
                setButtonLoading(btn, false);
                return;
            }
            if (!res.ok || json.success === false) throw new Error(json.message || 'Update failed');

            window.toastr?.success?.(json.message || 'User updated.');
            setButtonLoading(btn, false);
            bootstrap.Modal.getInstance(document.getElementById('userEditModal'))?.hide();
            window.UserList?.reload?.();
            if (isOpenFor(id)) await reload(id);
        } catch (err) {
            console.error('[user-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setButtonLoading(btn, false);
        }
    }

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.d-block').forEach(el => { el.textContent = ''; el.classList.remove('d-block'); });
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

    window.UserDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.UserDrawer;
})();
</script>