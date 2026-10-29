<script>
window.PMDrawer = (function () {
    'use strict';

    const URL_SHOW     = window.PM_ROUTES.drawer_data;
    const URL_STATUS   = window.PM_ROUTES.status;
    const URL_UPDATE   = window.PM_ROUTES.update;
    const URL_DELETE   = window.PM_ROUTES.destroy;
    const URL_TRANSFER = window.PM_ROUTES.transfer;
    const URL_PREVIEW  = window.PM_ROUTES.preview;
    const CSRF         = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CURRENCY     = @json(currency_symbol());

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('pmDrawer');
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
            currentPayload = json.payment_method;
            render(json.payment_method);
        } catch (err) {
            console.error('[pm-drawer] load failed:', err);
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
            currentPayload = json.payment_method;
            render(json.payment_method);
        } catch (err) { console.error('[pm-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('pmDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('pmDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('pmDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('pmDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('pmDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('pmDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(pm) {
        currentId = pm.id;

        const nameEl = document.getElementById('pmDrawerName');
        if (nameEl) nameEl.textContent = pm.name || '';

        document.getElementById('pmDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('pmDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(pm)}
            ${renderBalanceBlock(pm)}
            ${renderMetaBlock(pm)}
            ${renderLocationsBlock(pm)}
            ${renderFeesBlock(pm)}
            ${renderAuditBlock(pm)}
        `;

        document.getElementById('pmDrawerActions').innerHTML = renderActionCluster(pm);
        document.getElementById('pmDrawerFooter').classList.remove('d-none');
        bindActions(pm);
    }

    function renderHeaderBlock(pm) {
        const activeBadge = pm.is_active
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;
        const defaultBadge = pm.is_default
            ? `<span class="badge badge-light-info fs-7 py-2 px-3"><i class="bi bi-star-fill me-1"></i>Default</span>`
            : '';

        return `
            <div class="d-flex align-items-start justify-content-between gap-3 mb-6">
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(pm.name)}</h5>
                    <div class="text-muted fs-7 mb-2"><code>${esc(pm.code || '—')}</code></div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light-primary fs-8">${esc(pm.type_label)}</span>
                        ${activeBadge}
                        ${defaultBadge}
                    </div>
                </div>
            </div>
        `;
    }

    function renderBalanceBlock(pm) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-cash-stack me-2 text-success"></i>Current Balance
            </h6>
            <div class="card bg-light-primary mb-6">
                <div class="card-body py-4 text-center">
                    <div class="fs-1 fw-bold text-primary">${money(pm.current_balance)}</div>
                    ${pm.currency_code ? `<div class="text-muted fs-7">${esc(pm.currency_code)}</div>` : ''}
                </div>
            </div>
        `;
    }

    function renderMetaBlock(pm) {
        const rows = [
            ['Type',            pm.type_label],
            ['Provider',        pm.provider],
            ['Account Name',    pm.account_name],
            ['Account Number',  pm.account_number],
            ['Description',     pm.description],
            ['Online',          pm.is_online  ? 'Yes' : 'No'],
            ['Verified',        pm.is_verified ? 'Yes' : 'No'],
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

    function renderLocationsBlock(pm) {
        const locs = pm.locations || [];

        if (locs.length === 0) {
            return `
                <h6 class="fw-bold text-gray-800 mb-3">
                    <i class="bi bi-geo-alt me-2 text-info"></i>Available Locations
                </h6>
                <div class="bg-light rounded-3 p-4 mb-6">
                    <div class="d-flex align-items-center gap-2 fs-7 text-muted">
                        <i class="bi bi-globe"></i>
                        <span>Available at all locations</span>
                    </div>
                </div>
            `;
        }

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-geo-alt me-2 text-info"></i>Available Locations
                <span class="badge badge-light-secondary ms-2">${locs.length}</span>
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="d-flex flex-wrap gap-2">
                    ${locs.map(l => `<span class="badge badge-light-info fs-8 py-2 px-3">${esc(l.name)}</span>`).join('')}
                </div>
            </div>
        `;
    }

    function renderFeesBlock(pm) {
        const fees = [];
        if (pm.transaction_fee_percentage > 0) fees.push(`<div class="col-6"><div class="text-muted fs-8">Fee %</div><div class="fw-bold">${pm.transaction_fee_percentage}%</div></div>`);
        if (pm.transaction_fee_fixed > 0)      fees.push(`<div class="col-6"><div class="text-muted fs-8">Fixed Fee</div><div class="fw-bold">${money(pm.transaction_fee_fixed)}</div></div>`);
        if (pm.min_transaction_amount > 0)     fees.push(`<div class="col-6"><div class="text-muted fs-8">Min Amount</div><div class="fw-bold">${money(pm.min_transaction_amount)}</div></div>`);
        if (pm.max_transaction_amount > 0)     fees.push(`<div class="col-6"><div class="text-muted fs-8">Max Amount</div><div class="fw-bold">${money(pm.max_transaction_amount)}</div></div>`);

        if (fees.length === 0) return '';

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-percent me-2 text-warning"></i>Transaction Rules
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-3">${fees.join('')}</div>
            </div>
        `;
    }

    function renderAuditBlock(pm) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-clock-history me-2 text-muted"></i>Audit
            </h6>
            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Creator</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(pm.creator_name || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(pm.created_at || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Updated at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(pm.updated_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderActionCluster(pm) {
        const btns = [];

        if (pm.can_toggle) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-status-select data-current-status="${pm.is_active ? 1 : 0}"
                        style="width:150px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="1" ${pm.is_active ? 'disabled' : ''}>Active</option>
                    <option value="0" ${!pm.is_active ? 'disabled' : ''}>Inactive</option>
                </select>
            `);
        }

        if (pm.can_transfer) {
            btns.push(`<button type="button" class="btn btn-sm btn-light-primary d-flex align-items-center"
                              data-drawer-action="transfer">
                        <i class="ki-duotone ki-arrows-circle fs-4 me-2">
                            <span class="path1"></span><span class="path2"></span>
                        </i>Transfer
                      </button>`);
        }

        if (pm.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (pm.can_delete && !pm.is_active && !pm.is_default) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(pm) {
        const actions = document.getElementById('pmDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')     return openEditModal(pm);
                if (a === 'delete')   return doDelete(pm);
                if (a === 'transfer') return openTransferModal(pm);
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
                    const json = await req('POST', URL_STATUS.replace('__ID__', pm.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.PMList?.reload?.();
                    await reload(pm.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    async function doDelete(pm) {
        if (!confirm(`Delete payment method "${pm.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', pm.id), null);
            window.toastr?.success?.(json.message || 'Payment method deleted.');
            window.PMList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    // ─── Shared edit modal ────────────────────────────────
    function openEditModal(pm) {
        const modalEl = document.getElementById('pmEditModal');
        const form    = document.getElementById('pmEditForm');
        if (!modalEl || !form) { window.toastr?.error?.('Edit modal not available.'); return; }

        const btn = document.getElementById('pmEditSubmit');
        if (btn) setButtonLoading(btn, false);
        clearFormErrors(form);

        document.getElementById('pmEditId').value = pm.id;
        const header = document.getElementById('pmEditHeaderName');
        if (header) header.textContent = pm.name || '';

        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.type === 'checkbox') el.checked = !!value;
            else el.value = value ?? '';
        };

        setField('name', pm.name);
        setField('code', pm.code);
        setField('type', pm.type);
        setField('provider', pm.provider);
        setField('account_name', pm.account_name);
        setField('account_number', pm.account_number);
        setField('current_balance', pm.current_balance);
        setField('description', pm.description);
        setField('transaction_fee_percentage', pm.transaction_fee_percentage);
        setField('transaction_fee_fixed', pm.transaction_fee_fixed);
        setField('min_transaction_amount', pm.min_transaction_amount);
        setField('max_transaction_amount', pm.max_transaction_amount);
        setField('is_active', pm.is_active);
        setField('is_default', pm.is_default);
        setField('is_online', pm.is_online);

        // Pre-select locations in the multi-select
        const locSelect = form.querySelector('[name="location_id[]"]');
        if (locSelect) {
            const current = pm.location_ids || [];
            Array.from(locSelect.options).forEach(opt => {
                opt.selected = current.map(String).includes(String(opt.value));
            });
            if (window.jQuery && jQuery(locSelect).data('select2')) {
                jQuery(locSelect).trigger('change.select2');
            }
        }

        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitPmEdit(pm.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitPmEdit(id, btn) {
        const form = document.getElementById('pmEditForm');
        if (!form) return;

        clearFormErrors(form);

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        data._method = 'PUT';
        delete data.pmEditId;
        delete data._token;

        // Checkboxes: always 0/1
        data.is_active   = form.querySelector('[name="is_active"]')?.checked   ? 1 : 0;
        data.is_default  = form.querySelector('[name="is_default"]')?.checked  ? 1 : 0;
        data.is_online   = form.querySelector('[name="is_online"]')?.checked   ? 1 : 0;

        // location_id[] comes as an array in FormData; Laravel handles it.
        // If empty, delete so backend treats as "all locations".
        const locs = formData.getAll('location_id[]');
        if (locs.length === 0) {
            delete data['location_id[]'];
        }

        setButtonLoading(btn, true);

        try {
            const res = await fetch(URL_UPDATE.replace('__ID__', id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams(
                    // Serialize arrays properly: location_id[]=1&location_id[]=2
                    Object.entries(data).flatMap(([k, v]) =>
                        Array.isArray(v) ? v.map(x => [k, x]) : [[k, v]]
                    )
                ),
            });

            const json = await res.json().catch(() => ({}));

            if (res.status === 422) {
                Object.entries(json.errors || {}).forEach(([field, msgs]) => {
                    const f = field.replace(/\..*/, '');   // location_id.0 → location_id
                    const input  = form.querySelector(`[name="${f}"], [name="${f}[]"]`);
                    const holder = form.querySelector(`#${CSS.escape(f)}`);
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

            window.toastr?.success?.(json.message || 'Payment method updated.');
            setButtonLoading(btn, false);

            bootstrap.Modal.getInstance(document.getElementById('pmEditModal'))?.hide();

            try { window.PMList?.reload?.(); } catch (e) { console.error('[pm table reload]', e); }
            if (isOpenFor(id)) {
                try { await reload(id); } catch (e) { console.error('[pm drawer reload]', e); }
            }

        } catch (err) {
            console.error('[pm-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setButtonLoading(btn, false);
        }
    }

    // ─── Transfer modal ───────────────────────────────────
    function openTransferModal(pm) {
        if (typeof window.PMTransfer?.open === 'function') {
            window.PMTransfer.open(pm);
        } else {
            window.toastr?.error?.('Transfer module not available.');
        }
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) {
        return CURRENCY + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    window.PMDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.PMDrawer;
})();
</script>

<script>
    window.PMTransfer = (function () {
    'use strict';

    const URL_PREVIEW = window.PM_ROUTES.preview;
    const URL_EXECUTE = window.PM_ROUTES.transfer;
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CURRENCY    = @json(currency_symbol());

    function setButtonLoading(button, isLoading) {
        if (!button) return;
        const label    = button.querySelector('.indicator-label');
        const progress = button.querySelector('.indicator-progress');
        button.disabled = isLoading;
        if (label)    label.style.display    = isLoading ? 'none' : '';
        if (progress) progress.classList.toggle('d-none', !isLoading);
    }

    function getModal() { return document.getElementById('pmTransferModal'); }

    function open(pm) {
        const modalEl = getModal();
        if (!modalEl) { window.toastr?.error?.('Transfer modal not available.'); return; }

        const form       = modalEl.querySelector('#pmTransferForm');
        const fromId     = modalEl.querySelector('#pmTransferFromId');
        const fromDisp   = modalEl.querySelector('#pmTransferFromDisplay');
        const fromBal    = modalEl.querySelector('#pmTransferFromBalance');
        const toSelect   = modalEl.querySelector('#pmTransferToSelect');
        const amountEl   = modalEl.querySelector('#pmTransferAmount');
        const hintEl     = modalEl.querySelector('#pmTransferHint');
        const previewBox = modalEl.querySelector('#pmTransferPreview');
        const previewBtn = modalEl.querySelector('#pmTransferPreviewBtn');
        const executeBtn = modalEl.querySelector('#pmTransferExecuteBtn');

        // Reset
        form.reset();
        previewBox.classList.add('d-none');
        executeBtn.disabled = true;

        fromId.value = pm.id;
        fromDisp.textContent = pm.name;
        fromBal.textContent = 'Available: ' + CURRENCY + Number(pm.current_balance).toLocaleString('en-US', { minimumFractionDigits: 2 });

        // Populate transfer options
        toSelect.innerHTML = '<option value="">— Select —</option>';
        (pm.transfer_options || []).forEach(o => {
            const opt = document.createElement('option');
            opt.value = o.id;
            opt.textContent = `${o.name} — ${CURRENCY}${Number(o.balance).toFixed(2)}`;
            toSelect.appendChild(opt);
        });

        hintEl.textContent = 'Available: ' + CURRENCY + Number(pm.current_balance).toLocaleString('en-US', { minimumFractionDigits: 2 });

        // Wire preview
        previewBtn.onclick = async () => {
            const fd = new FormData(form);
            const payload = {
                from_payment_method_id: fromId.value,
                to_payment_method_id:   toSelect.value,
                amount:                 amountEl.value,
                description:            fd.get('description') || null,
            };

            if (!payload.from_payment_method_id || !payload.to_payment_method_id || !payload.amount) {
                window.toastr?.warning?.('Please fill all required fields.');
                return;
            }

            setButtonLoading(previewBtn, true);

            try {
                const res = await fetch(URL_PREVIEW, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN':     CSRF,
                        'Accept':           'application/json',
                        'Content-Type':     'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();

                if (!res.ok || !json.success) throw new Error(json.message || 'Preview failed');

                const p = json.preview;

                modalEl.querySelector('#pmPreviewFromName').textContent   = p.from.name;
                modalEl.querySelector('#pmPreviewFromBefore').textContent = CURRENCY + p.from.balance_before.toFixed(2);
                modalEl.querySelector('#pmPreviewFromAmount').textContent = '− ' + CURRENCY + p.amount.toFixed(2);
                modalEl.querySelector('#pmPreviewFromAfter').textContent  = CURRENCY + p.from.balance_after.toFixed(2);
                modalEl.querySelector('#pmPreviewToName').textContent     = p.to.name;
                modalEl.querySelector('#pmPreviewToBefore').textContent   = CURRENCY + p.to.balance_before.toFixed(2);
                modalEl.querySelector('#pmPreviewToAmount').textContent   = '+ ' + CURRENCY + p.amount.toFixed(2);
                modalEl.querySelector('#pmPreviewToAfter').textContent    = CURRENCY + p.to.balance_after.toFixed(2);
                modalEl.querySelector('#pmPreviewReference').textContent  = p.reference;

                previewBox.classList.remove('d-none');
                executeBtn.disabled = false;

            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setButtonLoading(previewBtn, false);
            }
        };

        // Wire execute
        executeBtn.onclick = async () => {
            if (!confirm('Execute this transfer?')) return;

            const fd = new FormData(form);
            const payload = {
                from_payment_method_id: fromId.value,
                to_payment_method_id:   toSelect.value,
                amount:                 amountEl.value,
                description:            fd.get('description') || null,
            };

            setButtonLoading(executeBtn, true);

            try {
                const res = await fetch(URL_EXECUTE, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN':     CSRF,
                        'Accept':           'application/json',
                        'Content-Type':     'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload),
                });
                const json = await res.json();

                if (!res.ok || !json.success) throw new Error(json.message || 'Transfer failed');

                window.toastr?.success?.(json.message || 'Transfer completed.');
                bootstrap.Modal.getInstance(modalEl)?.hide();

                // Refresh drawer + table
                window.PMList?.reload?.();
                if (window.PMDrawer?.isOpenFor?.(pm.id)) {
                    await window.PMDrawer.reload(pm.id);
                }

            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setButtonLoading(executeBtn, false);
            }
        };

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    window.PMTransfer = { open };
    return window.PMTransfer;
})();
</script>