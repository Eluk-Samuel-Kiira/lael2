<script>
window.CustomerDrawer = (function () {
    'use strict';

    const URL_SHOW   = window.CUSTOMER_ROUTES.drawer_data;
    const URL_STATUS = window.CUSTOMER_ROUTES.status;
    const URL_UPDATE = window.CUSTOMER_ROUTES.update;
    const URL_DELETE = window.CUSTOMER_ROUTES.destroy;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CURRENCY   = @json(currency_symbol());

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('customerDrawer');
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
            currentPayload = json.customer;
            render(json.customer);
        } catch (err) {
            console.error('[customer-drawer] load failed:', err);
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
            currentPayload = json.customer;
            render(json.customer);
        } catch (err) { console.error('[customer-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('customerDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('customerDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('customerDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('customerDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('customerDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('customerDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(c) {
        currentId = c.id;

        const nameEl = document.getElementById('customerDrawerName');
        if (nameEl) nameEl.textContent = c.full_name || '';

        document.getElementById('customerDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('customerDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(c)}
            ${renderContactBlock(c)}
            ${renderGroupBlock(c)}
            ${renderAddressBlock(c)}
            ${renderStatsBlock(c)}
            ${renderNotesBlock(c)}
            ${renderAuditBlock(c)}
        `;

        document.getElementById('customerDrawerActions').innerHTML = renderActionCluster(c);
        document.getElementById('customerDrawerFooter').classList.remove('d-none');
        bindActions(c);
    }

    function renderHeaderBlock(c) {
        const activeBadge = c.is_active
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;

        const marketBadge = c.accepts_marketing
            ? `<span class="badge badge-light-info fs-8"><i class="bi bi-envelope-paper me-1"></i>Opted-in to marketing</span>`
            : '';

        const initials = ((c.first_name || 'C')[0] + (c.last_name || '')[0] || '').toUpperCase();

        return `
            <div class="d-flex align-items-start gap-3 mb-6">
                <div class="symbol symbol-60px symbol-circle">
                    <div class="symbol-label bg-light-primary">
                        <span class="fw-bold text-primary fs-3">${esc(initials)}</span>
                    </div>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(c.full_name)}</h5>
                    <div class="text-muted fs-7 mb-2">#${c.id}</div>
                    <div class="d-flex flex-wrap gap-2">
                        ${activeBadge}
                        ${marketBadge}
                    </div>
                </div>
            </div>
        `;
    }

    function renderContactBlock(c) {
        const rows = [
            ['Email', c.email],
            ['Phone', c.phone],
            ['Tax Number', c.tax_number],
            ['Birth Date', c.birth_date_human],
        ];

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-person-lines-fill me-2 text-primary"></i>Contact
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

    function renderGroupBlock(c) {
        if (!c.group_name) return '';

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-people me-2 text-info"></i>Customer Group
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold text-gray-800">${esc(c.group_name)}</div>
                        ${c.group_discount > 0
                            ? `<span class="badge badge-light-info fs-8 mt-1">${Number(c.group_discount).toFixed(2)}% discount</span>`
                            : ''}
                    </div>
                    <i class="bi bi-star-fill text-warning fs-2"></i>
                </div>
            </div>
        `;
    }

    function renderAddressBlock(c) {
        const hasAddress = c.address || c.city || c.state || c.postal_code || c.country_code;
        if (!hasAddress) return '';

        const parts = [
            c.address,
            [c.city, c.state, c.postal_code].filter(Boolean).join(', '),
            c.country_code,
        ].filter(Boolean);

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-geo-alt me-2 text-info"></i>Address
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                ${parts.map(p => `<div class="fw-semibold text-gray-800 fs-7">${esc(p)}</div>`).join('')}
            </div>
        `;
    }

    function renderStatsBlock(c) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-graph-up me-2 text-success"></i>Activity
            </h6>
            <div class="row g-3 mb-6">
                <div class="col-6">
                    <div class="card bg-light-primary">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Orders</span>
                            <span class="fs-3 fw-bold text-primary">${c.order_count || 0}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card bg-light-success">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Total Spent</span>
                            <span class="fs-5 fw-bold text-success">${money(c.total_spent || 0)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderNotesBlock(c) {
        if (!c.notes) return '';
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-journal-text me-2 text-muted"></i>Notes
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <p class="mb-0 text-gray-700 fs-7" style="white-space:pre-wrap;">${esc(c.notes)}</p>
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
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Creator</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(c.creator_name || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(c.created_at || '—')}</div>
                    </div>
                    <div class="col-6 col-md-3">
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

        if (c.can_delete && (c.order_count || 0) === 0) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(c) {
        const actions = document.getElementById('customerDrawerActions');
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
                    window.CustomerList?.reload?.();
                    await reload(c.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    async function doDelete(c) {
        if (!confirm(`Delete customer "${c.full_name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', c.id), null);
            window.toastr?.success?.(json.message || 'Customer deleted.');
            window.CustomerList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    // ─── Shared edit modal ────────────────────────────────
    function openEditModal(c) {
        const modalEl = document.getElementById('customerEditModal');
        const form    = document.getElementById('customerEditForm');
        if (!modalEl || !form) { window.toastr?.error?.('Edit modal not available.'); return; }

        const btn = document.getElementById('customerEditSubmit');
        if (btn) setButtonLoading(btn, false);
        clearFormErrors(form);

        document.getElementById('customerEditId').value = c.id;
        const header = document.getElementById('customerEditHeaderName');
        if (header) header.textContent = c.full_name || '';

        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.type === 'checkbox') el.checked = !!value;
            else el.value = value ?? '';
        };

        setField('first_name', c.first_name);
        setField('last_name', c.last_name);
        setField('email', c.email);
        setField('phone', c.phone);
        setField('group_id', c.group_id);
        setField('birth_date', c.birth_date);
        setField('tax_number', c.tax_number);
        setField('country_code', c.country_code);
        setField('address', c.address);
        setField('city', c.city);
        setField('state', c.state);
        setField('postal_code', c.postal_code);
        setField('notes', c.notes);
        setField('is_active', c.is_active);
        setField('accepts_marketing', c.accepts_marketing);

        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitCustomerEdit(c.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitCustomerEdit(id, btn) {
        const form = document.getElementById('customerEditForm');
        if (!form) return;

        clearFormErrors(form);

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data.customerEditId;
        delete data._token;

        data.accepts_marketing = form.querySelector('[name="accepts_marketing"]')?.checked ? 1 : 0;

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

            window.toastr?.success?.(json.message || 'Customer updated.');
            setButtonLoading(btn, false);

            bootstrap.Modal.getInstance(document.getElementById('customerEditModal'))?.hide();

            try { window.CustomerList?.reload?.(); } catch (e) { console.error('[customer table reload]', e); }
            if (isOpenFor(id)) {
                try { await reload(id); } catch (e) { console.error('[customer drawer reload]', e); }
            }

        } catch (err) {
            console.error('[customer-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setButtonLoading(btn, false);
        }
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) {
        return CURRENCY + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    window.CustomerDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.CustomerDrawer;
})();
</script>