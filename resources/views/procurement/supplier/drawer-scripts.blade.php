<script>
window.SupplierDrawer = (function () {
    'use strict';

    const URL_SHOW    = window.SUPPLIER_ROUTES.drawer_data;
    const URL_STATUS  = window.SUPPLIER_ROUTES.status;
    const URL_UPDATE  = window.SUPPLIER_ROUTES.update;
    const URL_DELETE  = window.SUPPLIER_ROUTES.destroy;
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('supplierDrawer');
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
            currentPayload = json.supplier;
            render(json.supplier);
        } catch (err) {
            console.error('[supplier-drawer] load failed:', err);
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
            currentPayload = json.supplier;
            render(json.supplier);
        } catch (err) { console.error('[supplier-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('supplierDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('supplierDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('supplierDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('supplierDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('supplierDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('supplierDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(s) {
        currentId = s.id;
        const nameEl = document.getElementById('supplierDrawerName');
        if (nameEl) nameEl.textContent = s.name || '';

        document.getElementById('supplierDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('supplierDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(s)}
            ${renderContactBlock(s)}
            ${renderAddressBlock(s)}
            ${renderTaxBlock(s)}
            ${renderBankingBlock(s)}
            ${renderTermsBlock(s)}
            ${renderNotesBlock(s)}
            ${renderAuditBlock(s)}
        `;

        document.getElementById('supplierDrawerActions').innerHTML = renderActionCluster(s);
        document.getElementById('supplierDrawerFooter').classList.remove('d-none');
        bindActions(s);
    }

    // ─── Header ───────────────────────────────────────
    function renderHeaderBlock(s) {
        const statusBadge = s.is_active
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;

        const vatBadge = s.is_vat_registered
            ? `<span class="badge badge-light-info fs-8">VAT Registered</span>` : '';
        const whtBadge = s.withholding_tax_applicable
            ? `<span class="badge badge-light-danger fs-8">WHT ${Number(s.withholding_tax_rate || 0).toFixed(2)}%</span>` : '';

        return `
            <div class="mb-6">
                <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                    <div class="flex-grow-1 min-w-0">
                        <h5 class="fw-bold text-gray-800 mb-1">${esc(s.name || '—')}</h5>
                        ${s.trading_name ? `<div class="text-muted fs-7">Trading as ${esc(s.trading_name)}</div>` : ''}
                    </div>
                    ${statusBadge}
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge badge-light-${typeColor(s.supplier_type)} fs-8">
                        ${esc(s.supplier_type || '—')}
                    </span>
                    ${s.supplier_code ? `<span class="badge badge-light-dark fs-8">${esc(s.supplier_code)}</span>` : ''}
                    ${vatBadge}
                    ${whtBadge}
                </div>
            </div>
        `;
    }

    function typeColor(t) {
        return { company: 'primary', individual: 'info', government: 'warning', ngo: 'success', foreign: 'danger' }[t] || 'secondary';
    }

    // ─── Contact ──────────────────────────────────────
    function renderContactBlock(s) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-person-lines-fill me-2 text-primary"></i>Contact
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-3">
                    ${kv('Contact Person', s.contact_person)}
                    ${kv('Email', s.email)}
                    ${kv('Phone', s.phone)}
                    ${kv('Secondary Phone', s.phone_secondary)}
                    ${kv('Website', s.website)}
                </div>
            </div>
        `;
    }

    function renderAddressBlock(s) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-geo-alt me-2 text-info"></i>Address
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-3">
                    ${kv('Address', s.address)}
                    ${kv('City', s.city)}
                    ${kv('State', s.state)}
                    ${kv('Postal Code', s.postal_code)}
                    ${kv('Country', s.country_code)}
                </div>
            </div>
        `;
    }

    function renderTaxBlock(s) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-receipt me-2 text-warning"></i>Tax &amp; Compliance
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-3">
                    ${kv('Tax Number', s.tax_number)}
                    ${kv('VAT Registered', s.is_vat_registered ? 'Yes' : 'No')}
                    ${s.is_vat_registered ? kv('VAT Number', s.vat_number) : ''}
                    ${kv('WHT Applicable', s.withholding_tax_applicable ? 'Yes' : 'No')}
                    ${s.withholding_tax_applicable ? kv('WHT Rate', Number(s.withholding_tax_rate || 0).toFixed(2) + '%') : ''}
                    ${kv('WHT Exemption Ref', s.withholding_tax_exemption_ref)}
                </div>
            </div>
        `;
    }

    function renderBankingBlock(s) {
        const hasData = s.bank_name || s.bank_account_number || s.mobile_money_number;
        if (!hasData) return '';

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-bank me-2 text-success"></i>Banking
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-3">
                    ${kv('Bank Name', s.bank_name)}
                    ${kv('Branch', s.bank_branch)}
                    ${kv('Account Name', s.bank_account_name)}
                    ${kv('Account Number', s.bank_account_number)}
                    ${kv('SWIFT Code', s.bank_swift_code)}
                    ${kv('Mobile Money', s.mobile_money_number)}
                    ${kv('MoMo Provider', s.mobile_money_provider)}
                </div>
            </div>
        `;
    }

    function renderTermsBlock(s) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-cash-coin me-2 text-success"></i>Payment Terms
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-3">
                    ${kv('Terms Type', s.payment_terms_type ? s.payment_terms_type.toUpperCase() : null)}
                    ${kv('Terms Days', s.payment_terms_days)}
                    ${kv('Preferred Method', s.preferred_payment_method)}
                    ${kv('Credit Limit', s.credit_limit ? money(s.credit_limit) : null)}
                    ${kv('Category', s.category)}
                    ${kv('Risk Level', s.risk_level)}
                    ${kv('Currency', s.currency_code)}
                </div>
            </div>
        `;
    }

    function renderNotesBlock(s) {
        if (!s.notes) return '';
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-journal-text me-2 text-secondary"></i>Notes
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <p class="mb-0 text-gray-700 fs-7" style="white-space:pre-wrap;">${esc(s.notes)}</p>
            </div>
        `;
    }

    function renderAuditBlock(s) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-clock-history me-2 text-muted"></i>Audit
            </h6>
            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="row g-3">
                    ${kv('Created by', s.creator_name)}
                    ${kv('Created at', s.created_at)}
                    ${kv('Updated at', s.updated_at)}
                </div>
            </div>
        `;
    }

    function kv(label, value) {
        if (value === null || value === undefined || value === '') return '';
        return `
            <div class="col-6 col-md-4">
                <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(label)}</div>
                <div class="fw-semibold text-gray-800 fs-7">${esc(value)}</div>
            </div>
        `;
    }

    // ─── Action cluster ───────────────────────────────
    function renderActionCluster(s) {
        const btns = [];

        if (s.can_toggle) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-status-select data-current-status="${s.is_active ? 1 : 0}"
                        style="width:160px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="1" ${s.is_active ? 'disabled' : ''}>Active</option>
                    <option value="0" ${!s.is_active ? 'disabled' : ''}>Inactive</option>
                </select>
            `);
        }

        if (s.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (s.can_delete && !s.is_active) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(s) {
        const actions = document.getElementById('supplierDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', async () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')   return openEditModal(s);
                if (a === 'delete') return doDelete(s);
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
                    const json = await req('POST', URL_STATUS.replace('__ID__', s.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.SupplierList?.reload?.();
                    await reload(s.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    async function doDelete(s) {
        if (!confirm(`Delete supplier "${s.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', s.id), null);
            window.toastr?.success?.(json.message || 'Supplier deleted.');
            window.SupplierList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    function openEditModal(s) {
        const modalEl = document.getElementById('supplierEditModal');
        const form    = document.getElementById('supplierEditForm');
        if (!modalEl || !form) {
            window.toastr?.error?.('Edit modal not available.');
            return;
        }

        // Header
        const headerName = document.getElementById('supplierEditHeaderName');
        if (headerName) headerName.textContent = s.name || '';

        // Hidden id
        document.getElementById('supplierEditId').value = s.id;

        // Clear any prior errors
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
            el.textContent = '';
            el.classList.remove('d-block');
        });

        // Populate text / number / email / url / date / select fields
        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.type === 'checkbox') el.checked = !!value;
            else el.value = value ?? '';
        };

        [
            'name', 'trading_name', 'supplier_type', 'supplier_code',
            'contact_person', 'email', 'phone', 'phone_secondary', 'website',
            'address', 'city', 'state', 'postal_code',
            'country_code', 'tax_number', 'vat_number',
            'withholding_tax_rate', 'withholding_tax_exemption_ref',
            'withholding_tax_exemption_expiry',
            'bank_name', 'bank_branch', 'bank_account_name',
            'bank_account_number', 'bank_swift_code',
            'mobile_money_number', 'mobile_money_provider',
            'payment_terms_days', 'payment_terms_type', 'preferred_payment_method',
            'credit_limit', 'currency_code', 'category', 'risk_level', 'notes',
        ].forEach(field => setField(field, s[field]));

        // Checkboxes
        setField('is_active', s.is_active);
        setField('is_vat_registered', s.is_vat_registered);
        setField('withholding_tax_applicable', s.withholding_tax_applicable);

        // Re-bind the submit button (clone to strip old listeners)
        const btn = document.getElementById('supplierEditSubmit');
        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitSupplierEdit(s.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitSupplierEdit(id, btn) {
        const form = document.getElementById('supplierEditForm');
        if (!form) return;

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
            el.textContent = '';
            el.classList.remove('d-block');
        });

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data.supplier_id;

        Object.keys(data).forEach(k => {
            if (data[k] === '' || data[k] === 'undefined') delete data[k];
        });

        // Loading — same pattern as create/edit forms elsewhere in the app
        setSupplierButtonLoading(btn, true);

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
                return;
            }

            if (!res.ok || json.success === false) {
                throw new Error(json.message || 'Update failed');
            }

            // ── Success ──────────────────────────────────────
            window.toastr?.success?.(json.message || 'Supplier updated.');

            // Hide the modal — spinner stays visible until the modal is gone
            bootstrap.Modal.getInstance(document.getElementById('supplierEditModal'))?.hide();

            window.SupplierList?.reload?.();
            if (window.SupplierDrawer?.isOpenFor?.(id)) {
                await reload(id);
            }

            return;

        } catch (err) {
            console.error('[supplier-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
        } finally {
            // Restore only if the modal is still open — skips the success path
            if (document.getElementById('supplierEditModal')?.classList.contains('show')) {
                setSupplierButtonLoading(btn, false);
            }
        }
    }

    function setSupplierButtonLoading(button, isLoading) {
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
    function money(n) { return (window.SUPPLIER_CURRENCY || '') + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    window.SupplierDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.SupplierDrawer;
})();
</script>