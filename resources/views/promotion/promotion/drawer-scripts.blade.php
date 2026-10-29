<script>
window.PromoDrawer = (function () {
    'use strict';

    const URL_SHOW   = window.PROMO_ROUTES.drawer_data;
    const URL_STATUS = window.PROMO_ROUTES.status;
    const URL_UPDATE = window.PROMO_ROUTES.update;
    const URL_DELETE = window.PROMO_ROUTES.destroy;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CURRENCY   = @json(currency_symbol());

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('promoDrawer');
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
            currentPayload = json.promotion;
            render(json.promotion);
        } catch (err) {
            console.error('[promo-drawer] load failed:', err);
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
            currentPayload = json.promotion;
            render(json.promotion);
        } catch (err) { console.error('[promo-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('promoDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('promoDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('promoDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('promoDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('promoDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('promoDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(p) {
        currentId = p.id;

        const nameEl = document.getElementById('promoDrawerName');
        if (nameEl) nameEl.textContent = p.name || '';

        document.getElementById('promoDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('promoDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(p)}
            ${renderDiscountBlock(p)}
            ${renderPeriodBlock(p)}
            ${renderMetaBlock(p)}
            ${renderUsageBlock(p)}
            ${renderAuditBlock(p)}
        `;

        document.getElementById('promoDrawerActions').innerHTML = renderActionCluster(p);
        document.getElementById('promoDrawerFooter').classList.remove('d-none');
        bindActions(p);
    }

    function renderHeaderBlock(p) {
        const statusBadge = p.is_active
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;

        const expiredBadge = p.is_expired
            ? `<span class="badge badge-light-danger fs-8 py-2 px-3"><i class="bi bi-clock-history me-1"></i>Expired</span>`
            : '';

        return `
            <div class="d-flex align-items-start justify-content-between gap-3 mb-6">
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(p.name)}</h5>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        ${statusBadge}
                        ${expiredBadge}
                    </div>
                </div>
            </div>
        `;
    }

    function renderDiscountBlock(p) {
        const valueDisplay = p.discount_type === 'percentage'
            ? `${Number(p.discount_value).toFixed(2)}%`
            : (p.discount_type === 'fixed_amount'
                ? money(p.discount_value)
                : esc(p.discount_value));

        const label = {
            percentage:     'Percentage Discount',
            fixed_amount:   'Fixed Amount Discount',
            buy_x_get_y:    'Buy X Get Y',
        }[p.discount_type] || p.discount_type;

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-percent me-2 text-success"></i>Discount
            </h6>
            <div class="card bg-light-primary mb-6">
                <div class="card-body py-4 text-center">
                    <div class="text-muted fs-8 text-uppercase mb-1">${esc(label)}</div>
                    <div class="fs-1 fw-bold text-primary">${valueDisplay}</div>
                </div>
            </div>
        `;
    }

    function renderPeriodBlock(p) {
        const now = new Date();
        const start = p.start_date ? new Date(p.start_date) : null;
        const end = p.end_date ? new Date(p.end_date) : null;

        const state = (() => {
            if (!start || !end) return null;
            if (now < start) return { label: 'Not started yet', color: 'info',      icon: 'clock' };
            if (now > end)   return { label: 'Expired',         color: 'danger',    icon: 'x-circle' };
            return               { label: 'Running now',     color: 'success',   icon: 'play-circle' };
        })();

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-calendar-range me-2 text-info"></i>Period
            </h6>
            <div class="row g-3 mb-6">
                <div class="col-md-6">
                    <div class="card bg-light-info">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Starts</span>
                            <span class="fs-5 fw-bold text-info">${esc(p.start_date_human || '—')}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light-warning">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Ends</span>
                            <span class="fs-5 fw-bold text-warning">${esc(p.end_date_human || '—')}</span>
                        </div>
                    </div>
                </div>
            </div>
            ${state ? `
                <div class="alert alert-light-${state.color} border border-${state.color} border-dashed py-2 mb-6 fs-7">
                    <i class="bi bi-${state.icon} me-2"></i>${esc(state.label)}
                </div>
            ` : ''}
        `;
    }

    function renderMetaBlock(p) {
        const rows = [
            ['Discount Type', p.discount_type],
            ['Creator',       p.creator_name],
            ['Creator Email', p.creator_email],
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

    function renderUsageBlock(p) {
        if (p.usage_count === 0) {
            return `
                <h6 class="fw-bold text-gray-800 mb-3">
                    <i class="bi bi-boxes me-2 text-success"></i>Usage
                </h6>
                <div class="card bg-light-success border border-success border-dashed mb-6">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-check-circle fs-2x text-success"></i>
                        <div>
                            <div class="fw-bold text-gray-800 fs-5">Not in use</div>
                            <div class="text-muted fs-7">This promotion is not attached to any product yet.</div>
                        </div>
                    </div>
                </div>
            `;
        }

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-boxes me-2 text-warning"></i>Usage
            </h6>
            <div class="card bg-light-info mb-6">
                <div class="card-body d-flex align-items-center gap-3">
                    <i class="bi bi-bag-check fs-2x text-info"></i>
                    <div>
                        <div class="fw-bold text-gray-800 fs-5">${p.product_count} product(s)</div>
                        <div class="text-muted fs-7">This promotion is applied to ${p.product_count} product(s).</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderAuditBlock(p) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-clock-history me-2 text-muted"></i>Audit
            </h6>
            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Created at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(p.created_at || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">Updated at</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(p.updated_at || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderActionCluster(p) {
        const btns = [];

        if (p.can_toggle) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-status-select data-current-status="${p.is_active ? 1 : 0}"
                        style="width:150px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="1" ${p.is_active ? 'disabled' : ''}>Active</option>
                    <option value="0" ${!p.is_active ? 'disabled' : ''}>Inactive</option>
                </select>
            `);
        }

        if (p.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (p.can_delete && !p.is_active && p.usage_count === 0) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(p) {
        const actions = document.getElementById('promoDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')   return openEditModal(p);
                if (a === 'delete') return doDelete(p);
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
                    const json = await req('POST', URL_STATUS.replace('__ID__', p.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.PromoList?.reload?.();
                    await reload(p.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    async function doDelete(p) {
        if (!confirm(`Delete promotion "${p.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', p.id), null);
            window.toastr?.success?.(json.message || 'Promotion deleted.');
            window.PromoList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    // ─── Shared edit modal ────────────────────────────────
    function openEditModal(p) {
        const modalEl = document.getElementById('promoEditModal');
        const form    = document.getElementById('promoEditForm');
        if (!modalEl || !form) { window.toastr?.error?.('Edit modal not available.'); return; }

        const btn = document.getElementById('promoEditSubmit');
        if (btn) setButtonLoading(btn, false);

        clearFormErrors(form);

        document.getElementById('promoEditId').value = p.id;
        const header = document.getElementById('promoEditHeaderName');
        if (header) header.textContent = p.name || '';

        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            el.value = value ?? '';
        };

        setField('name', p.name);
        setField('discount_type', p.discount_type);
        setField('discount_value', p.discount_value);
        setField('start_date', p.start_date);
        setField('end_date', p.end_date);

        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitPromoEdit(p.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitPromoEdit(id, btn) {
        const form = document.getElementById('promoEditForm');
        if (!form) return;

        clearFormErrors(form);

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data.promoEditId;
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

            window.toastr?.success?.(json.message || 'Promotion updated.');
            setButtonLoading(btn, false);

            bootstrap.Modal.getInstance(document.getElementById('promoEditModal'))?.hide();

            try { window.PromoList?.reload?.(); } catch (e) { console.error('[promo table reload]', e); }
            if (isOpenFor(id)) {
                try { await reload(id); } catch (e) { console.error('[promo drawer reload]', e); }
            }

        } catch (err) {
            console.error('[promo-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setButtonLoading(btn, false);
        }
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) {
        return CURRENCY + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    window.PromoDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.PromoDrawer;
})();
</script>