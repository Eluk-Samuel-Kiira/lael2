<script>
window.CurrencyDrawer = (function () {
    'use strict';

    const URL_SHOW   = window.CURRENCY_ROUTES.drawer_data;
    const URL_STATUS = window.CURRENCY_ROUTES.status;
    const URL_UPDATE = window.CURRENCY_ROUTES.update;
    const URL_DELETE = window.CURRENCY_ROUTES.destroy;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('currencyDrawer');
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
            currentPayload = json.currency;
            render(json.currency);
        } catch (err) {
            console.error('[currency-drawer] load failed:', err);
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
            currentPayload = json.currency;
            render(json.currency);
        } catch (err) { console.error('[currency-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('currencyDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('currencyDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('currencyDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('currencyDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('currencyDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('currencyDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(c) {
        currentId = c.id;

        const nameEl = document.getElementById('currencyDrawerName');
        if (nameEl) nameEl.textContent = (c.code || '') + ' — ' + (c.name || '');

        document.getElementById('currencyDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('currencyDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(c)}
            ${renderMetaBlock(c)}
            ${renderProtectionBlock(c)}
            ${renderAuditBlock(c)}
        `;

        document.getElementById('currencyDrawerActions').innerHTML = renderActionCluster(c);
        document.getElementById('currencyDrawerFooter').classList.remove('d-none');
        bindActions(c);
    }

    function renderHeaderBlock(c) {
        const activeBadge = c.is_active
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;

        const baseBadge = c.is_base_currency
            ? `<span class="badge badge-light-info fs-7 py-2 px-3"><i class="bi bi-star-fill me-1"></i>Base Currency</span>`
            : '';

        return `
            <div class="d-flex align-items-start justify-content-between gap-3 mb-6">
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(c.name)}</h5>
                    <div class="text-muted fs-7 mb-2"><code>${esc(c.code)}</code></div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light-dark fs-8 py-2 px-3 fs-6">${esc(c.symbol)}</span>
                        ${activeBadge}
                        ${baseBadge}
                    </div>
                </div>
            </div>
        `;
    }

    function renderMetaBlock(c) {
        const rows = [
            ['Code',             c.code],
            ['Symbol',           c.symbol],
            ['Symbol Position',  c.symbol_position === 'after' ? 'After (100$)' : 'Before ($100)'],
            ['Decimal Places',   c.decimal_places],
            ['Exchange Rate',    Number(c.exchange_rate).toFixed(6) + ' ' + (c.tenant_currency || '')],
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
                            <div class="fw-semibold text-gray-800 fs-7">${esc(value ?? '—')}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    function renderProtectionBlock(c) {
        if (!c.is_base_currency) return '';

        return `
            <div class="alert alert-light-warning border border-warning border-dashed py-3 mb-6 fs-7">
                <i class="bi bi-shield-lock me-2"></i>
                <strong>Protected:</strong> The base currency cannot be edited, deactivated, or deleted.
                Create a new currency and set it as base to change this.
            </div>
        `;
    }

    function renderAuditBlock(c) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-clock-history me-2 text-muted"></i>Audit
            </h6>
            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Creator</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(c.creator_name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(c.created_at || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Updated at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(c.updated_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderActionCluster(c) {
        const btns = [];

        if (c.can_toggle) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-status-select data-current-status="${c.is_active ? 1 : 0}"
                        style="width:150px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="1" ${c.is_active ? 'disabled' : ''}>Active</option>
                    <option value="0" ${!c.is_active ? 'disabled' : ''}>Inactive</option>
                </select>
            `);
        }

        if (c.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (c.can_delete && !c.is_active) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(c) {
        const actions = document.getElementById('currencyDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')   return openEditModal(c);
                if (a === 'delete') return doDelete(c);
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
                    const json = await req('POST', URL_STATUS.replace('__ID__', c.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.CurrencyList?.reload?.();
                    await reload(c.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    async function doDelete(c) {
        if (!confirm(`Delete currency "${c.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', c.id), null);
            window.toastr?.success?.(json.message || 'Currency deleted.');
            window.CurrencyList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    function openEditModal(c) {
        const modalEl = document.getElementById('currencyEditModal');
        const form    = document.getElementById('currencyEditForm');
        if (!modalEl || !form) { window.toastr?.error?.('Edit modal not available.'); return; }

        const btn = document.getElementById('currencyEditSubmit');
        if (btn) setButtonLoading(btn, false);
        clearFormErrors(form);

        document.getElementById('currencyEditId').value = c.id;
        const header = document.getElementById('currencyEditHeaderName');
        if (header) header.textContent = c.name || '';

        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            el.value = value ?? '';
        };

        setField('code', c.code);
        setField('name', c.name);
        setField('symbol', c.symbol);
        setField('symbol_position', c.symbol_position || 'before');
        setField('decimal_places', c.decimal_places);
        setField('exchange_rate', c.exchange_rate);

        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitCurrencyEdit(c.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitCurrencyEdit(id, btn) {
        const form = document.getElementById('currencyEditForm');
        if (!form) return;

        clearFormErrors(form);

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data.currencyEditId;
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

            window.toastr?.success?.(json.message || 'Currency updated.');
            setButtonLoading(btn, false);

            bootstrap.Modal.getInstance(document.getElementById('currencyEditModal'))?.hide();

            try { window.CurrencyList?.reload?.(); } catch (e) { console.error('[currency table reload]', e); }
            if (isOpenFor(id)) {
                try { await reload(id); } catch (e) { console.error('[currency drawer reload]', e); }
            }

        } catch (err) {
            console.error('[currency-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setButtonLoading(btn, false);
        }
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.CurrencyDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.CurrencyDrawer;
})();
</script>