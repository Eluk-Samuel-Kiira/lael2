<script>
window.TaxDrawer = (function () {
    'use strict';

    const URL_SHOW   = window.TAX_ROUTES.drawer_data;
    const URL_STATUS = window.TAX_ROUTES.status;
    const URL_UPDATE = window.TAX_ROUTES.update;
    const URL_DELETE = window.TAX_ROUTES.destroy;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CURRENCY   = @json(currency_symbol());

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('taxDrawer');
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
            currentPayload = json.tax;
            render(json.tax);
        } catch (err) {
            console.error('[tax-drawer] load failed:', err);
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
            currentPayload = json.tax;
            render(json.tax);
        } catch (err) { console.error('[tax-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('taxDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('taxDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('taxDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('taxDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('taxDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('taxDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(t) {
        currentId = t.id;

        const nameEl = document.getElementById('taxDrawerName');
        if (nameEl) nameEl.textContent = t.name || '';

        document.getElementById('taxDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('taxDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(t)}
            ${renderAmountBlock(t)}
            ${renderMetaBlock(t)}
            ${renderUsageBlock(t)}
            ${renderAuditBlock(t)}
        `;

        document.getElementById('taxDrawerActions').innerHTML = renderActionCluster(t);
        document.getElementById('taxDrawerFooter').classList.remove('d-none');
        bindActions(t);
    }

    function renderHeaderBlock(t) {
        const statusBadge = t.is_active
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;

        const whtBadge = t.is_withholding_tax
            ? `<span class="badge badge-light-danger fs-8 py-2 px-3"><i class="bi bi-receipt me-1"></i>Withholding</span>`
            : '';

        return `
            <div class="mb-6">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                    <div>
                        <h5 class="fw-bold text-gray-800 mb-1">${esc(t.name)}</h5>
                        <code class="text-muted">${esc(t.code)}</code>
                    </div>
                    ${statusBadge}
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge badge-light-${t.type === 'percentage' ? 'primary' : 'info'} fs-8">
                        ${esc(t.type)}
                    </span>
                    ${whtBadge}
                </div>
            </div>
        `;
    }

    function renderAmountBlock(t) {
        const display = t.type === 'percentage'
            ? `${Number(t.rate).toFixed(2)}%`
            : `${CURRENCY}${Number(t.rate).toFixed(2)}`;

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-calculator me-2 text-success"></i>Amount
            </h6>
            <div class="card bg-light-primary mb-6">
                <div class="card-body py-4 text-center">
                    <div class="fs-1 fw-bold text-primary">${display}</div>
                    <div class="text-muted fs-7">
                        ${t.type === 'percentage' ? 'Applied to the taxable base' : 'Fixed amount per transaction'}
                    </div>
                </div>
            </div>
        `;
    }

    function renderMetaBlock(t) {
        const rows = [
            ['Code',        t.code],
            ['Type',        t.type],
            ['Withholding', t.is_withholding_tax ? 'Yes' : 'No'],
            ['Creator',     t.creator_name],
            ['Creator Email', t.creator_email],
            ['Created at',  t.created_at],
            ['Updated at',  t.updated_at],
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

    function renderUsageBlock(t) {
        if (t.usage_count === 0) {
            return `
                <h6 class="fw-bold text-gray-800 mb-3">
                    <i class="bi bi-boxes me-2 text-success"></i>Usage
                </h6>
                <div class="card bg-light-success border border-success border-dashed mb-6">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle fs-2x text-success"></i>
                        <div>
                            <div class="fw-bold text-gray-800 fs-5">Not in use</div>
                            <div class="text-muted fs-7">This tax is not attached to any products or variants.</div>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-boxes me-2 text-warning"></i>Usage
            </h6>
            <div class="row g-3 mb-6">
                <div class="col-6">
                    <div class="card bg-light-info">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Products</span>
                            <span class="fs-3 fw-bold text-info">${t.product_count}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card bg-light-primary">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Variants</span>
                            <span class="fs-3 fw-bold text-primary">${t.variant_count}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="alert alert-light-warning border border-warning border-dashed py-2 mb-6 fs-7">
                <i class="bi bi-info-circle me-2"></i>
                This tax cannot be deleted while it's attached to products or variants.
            </div>
        `;
    }

    function renderAuditBlock(t) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-clock-history me-2 text-muted"></i>Audit
            </h6>
            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(t.created_at || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Updated at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(t.updated_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderActionCluster(t) {
        const btns = [];

        if (t.can_toggle) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-status-select data-current-status="${t.is_active ? 1 : 0}"
                        style="width:150px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="1" ${t.is_active ? 'disabled' : ''}>Active</option>
                    <option value="0" ${!t.is_active ? 'disabled' : ''}>Inactive</option>
                </select>
            `);
        }

        if (t.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (t.can_delete && !t.is_active && t.usage_count === 0) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(t) {
        const actions = document.getElementById('taxDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')   return openEditModal(t);
                if (a === 'delete') return doDelete(t);
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
                    const json = await req('POST', URL_STATUS.replace('__ID__', t.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.TaxList?.reload?.();
                    await reload(t.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    async function doDelete(t) {
        if (!confirm(`Delete tax "${t.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', t.id), null);
            window.toastr?.success?.(json.message || 'Tax deleted.');
            window.TaxList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    // ─── Shared edit modal ────────────────────────────────
    function openEditModal(t) {
        const modalEl = document.getElementById('taxEditModal');
        const form    = document.getElementById('taxEditForm');
        if (!modalEl || !form) { window.toastr?.error?.('Edit modal not available.'); return; }

        const btn = document.getElementById('taxEditSubmit');
        if (btn) setButtonLoading(btn, false);

        clearFormErrors(form);

        document.getElementById('taxEditId').value = t.id;
        const header = document.getElementById('taxEditHeaderName');
        if (header) header.textContent = t.name || '';

        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.type === 'checkbox') el.checked = !!value;
            else el.value = value ?? '';
        };

        setField('name', t.name);
        setField('code', t.code);
        setField('rate', t.rate);
        setField('type', t.type);
        setField('is_withholding_tax', t.is_withholding_tax);

        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitTaxEdit(t.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitTaxEdit(id, btn) {
        const form = document.getElementById('taxEditForm');
        if (!form) return;

        clearFormErrors(form);

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data.taxEditId;
        delete data._token;

        // Checkbox: always send 0 or 1
        data.is_withholding_tax = form.querySelector('[name="is_withholding_tax"]')?.checked ? 1 : 0;

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

            window.toastr?.success?.(json.message || 'Tax updated.');
            setButtonLoading(btn, false);

            bootstrap.Modal.getInstance(document.getElementById('taxEditModal'))?.hide();

            try { window.TaxList?.reload?.(); } catch (e) { console.error('[tax table reload]', e); }
            if (isOpenFor(id)) {
                try { await reload(id); } catch (e) { console.error('[tax drawer reload]', e); }
            }

        } catch (err) {
            console.error('[tax-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setButtonLoading(btn, false);
        }
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.TaxDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.TaxDrawer;
})();
</script>