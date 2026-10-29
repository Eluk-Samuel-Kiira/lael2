<script>
window.EmployeeDrawer = (function () {
    'use strict';

    if (!window.EMPLOYEE_ROUTES) {
        console.error('[tenant-employee-drawer] EMPLOYEE_ROUTES undefined.');
        return {
            open: () => {}, reload: () => {}, close: () => {},
            isOpenFor: () => false,
            getCurrent: () => ({ id: null, payload: null }),
        };
    }

    const URL_SHOW    = window.EMPLOYEE_ROUTES.drawer_data;
    const URL_STATUS  = window.EMPLOYEE_ROUTES.status;
    const URL_UPDATE  = window.EMPLOYEE_ROUTES.update;
    const URL_DOC_UP  = window.EMPLOYEE_ROUTES.doc_upload;
    const URL_DOC_DEL = window.EMPLOYEE_ROUTES.doc_delete;
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId = null, currentPayload = null, bs = null;

    function getBs() {
        const el = document.getElementById('employeeDrawer');
        if (!el) { console.error('[tenant-employee-drawer] #employeeDrawer missing'); return null; }
        if (!window.bootstrap?.Offcanvas) { console.error('[tenant-employee-drawer] bootstrap.Offcanvas missing'); return null; }
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
            console.error('[tenant-employee-drawer] load failed:', err);
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
        } catch (err) { console.error('[tenant-employee-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('employeeDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('employeeDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('employeeDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('employeeDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('employeeDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('employeeDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(e) {
        currentId = e.id;
        const nameEl = document.getElementById('employeeDrawerName');
        if (nameEl) nameEl.textContent = e.name || '';

        document.getElementById('employeeDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('employeeDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeader(e)}
            ${renderPersonal(e)}
            ${renderEmployment(e)}
            ${renderSalary(e)}
            ${renderTaxBank(e)}
            ${renderIdentification(e)}
            ${renderNextOfKin(e)}
            ${renderDocuments(e)}
            ${renderAudit(e)}
        `;

        document.getElementById('employeeDrawerActions').innerHTML = renderActionCluster(e);
        document.getElementById('employeeDrawerFooter').classList.remove('d-none');
        bindActions(e);
    }

    function renderHeader(e) {
        return `
            <div class="d-flex align-items-center gap-4 mb-6">
                <div class="symbol symbol-circle symbol-70px">
                    <img src="${esc(e.profile_image)}" alt="${esc(e.name)}" class="w-100" />
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(e.name)}</h5>
                    <div class="text-muted fs-7 mb-2">${esc(e.email)} · ${esc(e.phone || '—')}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light-primary fs-8">${esc(e.department_name)}</span>
                        <span class="badge badge-light-${e.is_active ? 'success' : 'warning'} fs-8">${e.is_active ? 'Active' : 'Inactive'}</span>
                    </div>
                </div>
            </div>
        `;
    }

    function section(title, icon, body) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3"><i class="bi ${icon} me-2 text-primary"></i>${esc(title)}</h6>
            <div class="bg-light rounded-3 p-4 mb-5">${body}</div>
        `;
    }

    function kv(rows) {
        return `<div class="row g-3">${rows.map(([label, value]) => `
            <div class="col-6 col-md-3">
                <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(label)}</div>
                <div class="fw-semibold text-gray-800 fs-7">${esc(value || '—')}</div>
            </div>`).join('')}</div>`;
    }

    function renderPersonal(e) {
        return section('Personal Information', 'bi-person', kv([
            ['Gender', e.gender],
            ['Date of Birth', e.date_of_birth],
            ['Residence', e.residence],
        ]));
    }

    function renderEmployment(e) {
        return section('Employment Details', 'bi-briefcase', kv([
            ['Department', e.department_name],
            ['Job Title', e.job_title],
            ['Employee Type', e.employee_type],
            ['Hire Date', e.hire_date],
            ['Termination Date', e.termination_date],
        ]));
    }

    function renderSalary(e) {
        const recur = e.is_salary_recurring ? `Yes${e.recurring_day ? ' (day ' + e.recurring_day + ')' : ''}` : 'No';
        return section('Salary', 'bi-cash-coin', kv([
            ['Salary', `${formatNumber(e.salary)} ${e.currency_symbol || ''}`],
            ['Type', e.salary_type],
            ['Recurring', recur],
        ]));
    }

    function renderTaxBank(e) {
        return section('Tax & Bank', 'bi-bank', kv([
            ['NSSF', e.nssf_number],
            ['TIN', e.tin_number],
            ['Bank', e.bank_name],
            ['Account', e.bank_account_number],
            ['Branch', e.bank_branch],
        ]));
    }

    function renderIdentification(e) {
        return section('Identification', 'bi-card-text', kv([
            ['ID Type', e.id_type],
            ['ID Number', e.id_number],
            ['Qualification', e.qualification],
        ]));
    }

    function renderNextOfKin(e) {
        return section('Next of Kin', 'bi-people', kv([
            ['Name', e.next_of_kin_name],
            ['Contact', e.next_of_kin_contact],
            ['Relationship', e.next_of_kin_relationship],
        ]));
    }

    function renderDocuments(e) {
        const docs = e.documents || [];
        const header = `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-file-earmark-text me-2 text-info"></i>Documents
                <span class="badge badge-light-secondary ms-2">${docs.length}</span>
            </h6>`;

        if (!docs.length) {
            return header + `<div class="alert alert-light-warning border border-warning border-dashed py-3 fs-7 mb-5">No documents uploaded yet.</div>`;
        }

        const rows = docs.map(d => `
            <tr>
                <td>${esc(d.name)}</td>
                <td><span class="badge badge-light-primary fs-8">${esc(d.category)}</span></td>
                <td class="text-muted fs-8">${esc(d.uploaded_at)}</td>
                <td class="text-end">
                    <div class="d-flex gap-1 justify-content-end">
                        <a href="${esc(d.view_url)}" target="_blank"
                           class="btn btn-sm btn-icon btn-light btn-active-light-primary">
                            <i class="bi bi-eye fs-4"></i>
                        </a>
                        <a href="${esc(d.download_url)}"
                           class="btn btn-sm btn-icon btn-light btn-active-light-success">
                            <i class="bi bi-download fs-4"></i>
                        </a>
                        <button type="button"
                                data-doc-delete="${d.index}"
                                class="btn btn-sm btn-icon btn-light btn-active-light-danger">
                            <i class="bi bi-trash fs-4"></i>
                        </button>
                    </div>
                </td>
            </tr>`).join('');

        return header + `
            <div class="table-responsive mb-6">
                <table class="table table-row-bordered align-middle gy-3 fs-7 mb-0">
                    <thead class="border-bottom border-gray-200 fs-8 fw-bold text-uppercase">
                        <tr><th>Name</th><th>Category</th><th>Uploaded</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    function renderAudit(e) {
        return section('Audit', 'bi-clock-history', kv([
            ['Created at', e.created_at],
            ['Updated at', e.updated_at],
        ]));
    }

    function renderActionCluster(e) {
        const btns = [];

        if (e.can_toggle_status) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold" data-drawer-status-select style="width:150px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="1" ${e.is_active ? 'disabled' : ''}>Active</option>
                    <option value="0" ${!e.is_active ? 'disabled' : ''}>Inactive</option>
                </select>`);
        }

        if (e.can_manage_documents) {
            btns.push(`<button type="button" class="btn btn-sm btn-light d-flex align-items-center"
                              data-drawer-action="upload-doc">
                        <i class="bi bi-cloud-upload me-2"></i>Upload Document
                      </button>`);
        }

        if (e.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(e) {
        const actions = document.getElementById('employeeDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', async () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')       return openEditModal(e);
                if (a === 'upload-doc') return openDocUploadModal(e);
            });
        });

        const statusSel = actions.querySelector('[data-drawer-status-select]');
        if (statusSel) {
            statusSel.addEventListener('change', async () => {
                const newStatus = parseInt(statusSel.value, 10);
                statusSel.value = '';
                if (!Number.isInteger(newStatus) || (newStatus !== 0 && newStatus !== 1)) return;
                statusSel.disabled = true;
                try {
                    const json = await req('POST', URL_STATUS.replace('__ID__', e.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.EmployeeList?.reload?.();
                    await reload(e.id);
                } catch (err) { window.toastr?.error?.(err.message); }
                finally { statusSel.disabled = false; }
            });
        }

        document.querySelectorAll('[data-doc-delete]').forEach(el => {
            el.addEventListener('click', async () => {
                const index = parseInt(el.dataset.docDelete, 10);
                if (!confirm('Delete this document?')) return;
                try {
                    const json = await req('POST', URL_DOC_DEL, { employee_id: e.id, document_index: index });
                    window.toastr?.success?.(json.message || 'Document deleted.');
                    await reload(e.id);
                } catch (err) { window.toastr?.error?.(err.message); }
            });
        });
    }

    async function openDocUploadModal(e) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.pdf,.jpg,.jpeg,.png,.doc,.docx';
        input.addEventListener('change', async () => {
            if (!input.files?.length) return;
            const form = new FormData();
            form.append('employee_id', e.id);
            form.append('name', input.files[0].name.replace(/\.[^.]+$/, ''));
            form.append('category', 'other');
            form.append('document', input.files[0]);

            try {
                const res = await fetch(URL_DOC_UP, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: form,
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || json.success === false) throw new Error(json.message || 'Upload failed');
                window.toastr?.success?.(json.message || 'Document uploaded.');
                window.EmployeeList?.reload?.();
                await reload(e.id);
            } catch (err) { window.toastr?.error?.(err.message); }
        });
        input.click();
    }

    // ─── Edit modal ─────────────────────────────────────────
    function openEditModal(e) {
        const modalEl = document.getElementById('employeeEditModal');
        const form    = document.getElementById('employeeEditForm');
        if (!modalEl || !form) {
            console.error('[tenant-employee-drawer] edit modal nodes missing', {
                modalEl: !!modalEl, form: !!form,
            });
            window.toastr?.error?.('Edit modal not available.');
            return;
        }

        const btn = document.getElementById('employeeEditSubmit');
        if (btn) setButtonLoading(btn, false);
        clearErrors(form);

        document.getElementById('employeeEditId').value = e.id;
        document.getElementById('employeeEditHeaderName').textContent = e.name || '';

        // ── FIX: never leave a select with no valid option ──
        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.tagName === 'SELECT') {
                const target   = value == null ? '' : String(value);
                const hasBlank = Array.from(el.options).some(o => o.value === '');
                const matched  = Array.from(el.options).some(o => o.value === target && o.value !== '');

                if (matched) {
                    el.value = target;
                } else if (hasBlank) {
                    el.value = '';
                } else {
                    const first = Array.from(el.options).find(o => o.value !== '');
                    el.value = first ? first.value : '';
                }
            } else if (el.type === 'checkbox') {
                el.checked = !!value;
            } else {
                el.value = value ?? '';
            }
        };

        setField('first_name', e.first_name);
        setField('last_name', e.last_name);
        setField('email', e.email);
        setField('phone', e.phone);
        setField('gender', e.gender);
        setField('date_of_birth', e.date_of_birth);
        setField('residence', e.residence);
        setField('job_title', e.job_title);
        setField('employee_type', e.employee_type);
        setField('hire_date', e.hire_date);
        setField('termination_date', e.termination_date);
        setField('is_active', e.is_active);
        setField('salary', e.salary);
        setField('salary_type', e.salary_type || 'monthly');   // ← default so required passes
        setField('is_salary_recurring', e.is_salary_recurring);
        setField('recurring_day', e.recurring_day);
        setField('nssf_number', e.nssf_number);
        setField('tin_number', e.tin_number);
        setField('bank_name', e.bank_name);
        setField('bank_account_number', e.bank_account_number);
        setField('bank_branch', e.bank_branch);
        setField('id_type', e.id_type);
        setField('id_number', e.id_number);
        setField('qualification', e.qualification);
        setField('next_of_kin_name', e.next_of_kin_name);
        setField('next_of_kin_contact', e.next_of_kin_contact);
        setField('next_of_kin_relationship', e.next_of_kin_relationship);
        setField('notes', e.notes);

        const deptSel = form.querySelector('[name="department_id"]');
        if (deptSel) {
            deptSel.innerHTML = '<option value="">— Select —</option>' +
                (e.all_departments || []).map(d =>
                    `<option value="${d.id}" ${String(d.id) === String(e.department_id) ? 'selected' : ''}>${esc(d.name)}</option>`
                ).join('');
            deptSel.value = String(e.department_id || '');
        }

        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitEdit(e.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitEdit(id, btn) {
        const form = document.getElementById('employeeEditForm');
        if (!form) return;
        clearErrors(form);

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data._token;
        delete data.employeeEditId;
        if (!('is_active' in data)) data.is_active = '0';
        if (!('is_salary_recurring' in data)) data.is_salary_recurring = '0';
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
                // FIX: also surface the first error in a toast — never silent
                const firstError = Object.values(json.errors || {})[0]?.[0];
                window.toastr?.error?.(firstError || json.message || 'Please fix the highlighted fields.');
                setButtonLoading(btn, false);
                return;
            }
            if (!res.ok || json.success === false) throw new Error(json.message || 'Update failed');

            window.toastr?.success?.(json.message || 'Employee updated.');
            setButtonLoading(btn, false);
            bootstrap.Modal.getInstance(document.getElementById('employeeEditModal'))?.hide();
            window.EmployeeList?.reload?.();
            if (isOpenFor(id)) await reload(id);
        } catch (err) {
            console.error('[tenant-employee-drawer] edit failed:', err);
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

    function formatNumber(n) {
        const num = Number(n) || 0;
        return num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.EmployeeDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.EmployeeDrawer;
})();
</script>