<script>
window.PaymentDrawer = (function () {
    'use strict';

    if (!window.PAYMENT_ROUTES) {
        console.error('[payment-drawer] PAYMENT_ROUTES undefined.');
        return { open: () => {}, reload: () => {}, close: () => {}, isOpenFor: () => false, getCurrent: () => ({ id: null, payload: null }) };
    }

    const URL_SHOW   = window.PAYMENT_ROUTES.drawer_data;
    const URL_UPDATE = window.PAYMENT_ROUTES.update;
    const URL_DELETE = window.PAYMENT_ROUTES.destroy;
    const URL_STATUS = window.PAYMENT_ROUTES.status;
    const URL_META   = window.PAYMENT_ROUTES.meta;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId = null, currentPayload = null, bs = null, metaCache = null;

    async function getMeta() {
        if (metaCache) return metaCache;
        const res = await fetch(URL_META, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Meta load failed');
        metaCache = json;
        return metaCache;
    }

    function getBs() {
        const el = document.getElementById('paymentDrawer');
        if (!el) { console.error('[payment-drawer] #paymentDrawer missing'); return null; }
        if (!window.bootstrap?.Offcanvas) { console.error('[payment-drawer] bootstrap.Offcanvas missing'); return null; }
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
            currentPayload = json.payment;
            render(json.payment);
        } catch (err) {
            console.error('[payment-drawer] load failed:', err);
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
            currentPayload = json.payment;
            render(json.payment);
        } catch (err) { console.error('[payment-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('paymentDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('paymentDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('paymentDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('paymentDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('paymentDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('paymentDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(p) {
        currentId = p.id;
        const nameEl = document.getElementById('paymentDrawerName');
        if (nameEl) nameEl.textContent = `#${p.id} — ${p.employee_name}`;

        document.getElementById('paymentDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('paymentDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeader(p)}
            ${renderMoney(p)}
            ${renderMeta(p)}
            ${renderTaxes(p)}
            ${renderAdvances(p)}
            ${renderAudit(p)}
        `;

        document.getElementById('paymentDrawerActions').innerHTML = renderActionCluster(p);
        document.getElementById('paymentDrawerFooter').classList.remove('d-none');
        bindActions(p);
    }

    const statusColors = { pending: 'warning', completed: 'success', failed: 'danger', cancelled: 'secondary' };

    function renderHeader(p) {
        return `
            <div class="d-flex align-items-center justify-content-between gap-4 mb-6">
                <div>
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(p.employee_name)}</h5>
                    <div class="text-muted fs-7">${esc(p.employee_email)}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge badge-light-primary">${esc(p.payment_type_label)}</span>
                        <span class="badge badge-light-${statusColors[p.status] || 'secondary'}">${esc(p.status)}</span>
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-muted fs-8 text-uppercase">${esc(p.payment_date_label)}</div>
                </div>
            </div>
        `;
    }

    function renderMoney(p) {
        return `
            <div class="row g-3 mb-5">
                <div class="col-md-4"><div class="card card-dashed bg-light"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('payments.gross_amount') }}</div>
                    <div class="fs-3 fw-bold">${esc(formatNumber(p.gross_amount))} ${esc(p.currency_symbol)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-dashed bg-light-danger"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('payments.total_tax') }}</div>
                    <div class="fs-3 fw-bold text-danger">${esc(formatNumber(p.total_tax_amount))}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-dashed bg-light-success"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('payments.net_amount') }}</div>
                    <div class="fs-3 fw-bold text-success">${esc(formatNumber(p.net_amount))}</div>
                </div></div></div>
            </div>
        `;
    }

    function kv(rows) {
        return `<div class="row g-3">${rows.map(([l, v]) => `
            <div class="col-6 col-md-3">
                <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(l)}</div>
                <div class="fw-semibold text-gray-800 fs-7">${esc(v || '—')}</div>
            </div>`).join('')}</div>`;
    }

    function section(title, icon, body) {
        return `<h6 class="fw-bold text-gray-800 mb-3"><i class="bi ${icon} me-2 text-primary"></i>${esc(title)}</h6>
                <div class="bg-light rounded-3 p-4 mb-5">${body}</div>`;
    }

    function renderMeta(p) {
        return section('Details', 'bi-info-circle', kv([
            ['Payment Method', p.payment_method_name],
            ['Reference',      p.reference_number],
            ['Pay Period',     [p.pay_period_start, p.pay_period_end].filter(Boolean).join(' → ')],
            ['Description',    p.description],
            ['Notes',          p.notes],
        ]));
    }

    function renderTaxes(p) {
        const taxes = p.applied_taxes || [];
        if (!taxes.length) {
            return section('Taxes', 'bi-receipt', '<span class="text-muted fs-7">No taxes applied.</span>');
        }
        const rows = taxes.map(t => `
            <tr>
                <td>${esc(t.tax_name || t.name || 'Tax')}</td>
                <td><span class="badge badge-light-info">${esc(t.rate || 0)}%</span></td>
                <td class="text-end text-danger fw-bold">${esc(formatNumber(t.amount || 0))}</td>
            </tr>`).join('');
        return section('Taxes', 'bi-receipt', `
            <div class="table-responsive"><table class="table table-row-bordered align-middle fs-7 mb-0">
                <thead class="border-bottom border-gray-200 fs-8 fw-bold text-uppercase">
                    <tr><th>Tax</th><th>Rate</th><th class="text-end">Amount</th></tr>
                </thead><tbody>${rows}</tbody>
            </table></div>
        `);
    }

    function renderAdvances(p) {
        const ded = p.advance_deductions || [];
        if (!ded.length) {
            return section('Advance Deductions', 'bi-hand-holding-usd', '<span class="text-muted fs-7">No advance deductions.</span>');
        }
        const rows = ded.map(d => `
            <tr>
                <td>#${d.advance_id}</td>
                <td class="text-end text-danger fw-bold">${esc(formatNumber(d.deduction_amount))}</td>
            </tr>`).join('');
        return section('Advance Deductions', 'bi-hand-holding-usd', `
            <div class="table-responsive"><table class="table table-row-bordered align-middle fs-7 mb-0">
                <thead class="border-bottom border-gray-200 fs-8 fw-bold text-uppercase">
                    <tr><th>Advance</th><th class="text-end">Deducted</th></tr>
                </thead><tbody>${rows}</tbody>
            </table></div>
        `);
    }

    function renderAudit(p) {
        return section('Audit', 'bi-clock-history', kv([
            ['Created at', p.created_at],
            ['Updated at', p.updated_at],
            ['Completed at', p.completed_at],
        ]));
    }

    function renderActionCluster(p) {
        const btns = [];

        if (p.can_toggle_status) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold" data-drawer-status-select style="width:160px; cursor:pointer;">
                    <option value="">Status…</option>
                    <option value="pending"   ${p.status === 'pending'   ? 'disabled' : ''}>Pending</option>
                    <option value="completed" ${p.status === 'completed' ? 'disabled' : ''}>Completed</option>
                    <option value="failed"    ${p.status === 'failed'    ? 'disabled' : ''}>Failed</option>
                    <option value="cancelled" ${p.status === 'cancelled' ? 'disabled' : ''}>Cancelled</option>
                </select>
            `);
        }
        if (p.can_delete) {
            btns.push(`<button type="button" class="btn btn-sm btn-light-danger d-flex align-items-center" data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete</button>`);
        }
        if (p.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center" data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit</button>`);
        }
        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(p) {
        const actions = document.getElementById('paymentDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', async () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'edit')   return openEditModal(p);
                if (a === 'delete') return doDelete(p);
            });
        });

        const statusSel = actions.querySelector('[data-drawer-status-select]');
        if (statusSel) {
            statusSel.addEventListener('change', async () => {
                const newStatus = statusSel.value;
                statusSel.value = '';
                if (!newStatus) return;
                if (newStatus === 'completed' && !confirm('Completing this payment will record the withdrawal and advance repayment. Continue?')) return;
                statusSel.disabled = true;
                try {
                    const json = await req('POST', URL_STATUS.replace('__ID__', p.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.PaymentList?.reload?.();
                    await reload(p.id);
                } catch (err) { window.toastr?.error?.(err.message); }
                finally { statusSel.disabled = false; }
            });
        }
    }

    async function doDelete(p) {
        if (!confirm(`Delete payment #${p.id}? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', p.id), null);
            window.toastr?.success?.(json.message || 'Payment deleted.');
            window.PaymentList?.reload?.();
            close();
        } catch (err) { window.toastr?.error?.(err.message); }
    }

    // ─── Edit modal ────────────────────────────────────────
    async function openEditModal(p) {
        const modalEl = document.getElementById('paymentEditModal');
        const form    = document.getElementById('paymentEditForm');
        if (!modalEl || !form) {
            window.toastr?.error?.('Edit modal not available.');
            return;
        }

        const btn = document.getElementById('paymentEditSubmit');
        if (btn) setButtonLoading(btn, false);
        clearErrors(form);

        // Identifiers
        document.getElementById('paymentEditId').value = p.id;
        document.getElementById('paymentEditEmployeeId').value = p.employee_id ?? '';
        document.getElementById('paymentEditHeaderName').textContent = `#${p.id} — ${p.employee_name}`;

        // Load meta (payment methods + taxes) once, cached
        let meta;
        try {
            meta = await getMeta();
        } catch (err) {
            console.error('[payment-drawer] meta load failed:', err);
            window.toastr?.error?.('Failed to load payment metadata.');
            return;
        }

        // Payment method select
        const methodSel = form.querySelector('[name="payment_method_id"]');
        if (methodSel) {
            methodSel.innerHTML = '<option value="">— {{ __('payments.select_payment_method') }} —</option>'
                + (meta.payment_methods || []).map(m =>
                    `<option value="${m.id}" ${String(m.id) === String(p.payment_method_id) ? 'selected' : ''}>${esc(m.name)}${m.is_default ? ' (default)' : ''}</option>`
                ).join('');
        }

        // Field values
        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.tagName === 'SELECT') {
                const target = value == null ? '' : String(value);
                const hasBlank = Array.from(el.options).some(o => o.value === '');
                const matched  = Array.from(el.options).some(o => o.value === target && o.value !== '');
                el.value = matched ? target : (hasBlank ? '' : (el.options[0]?.value ?? ''));
            } else {
                el.value = value ?? '';
            }
        };

        setField('payment_date', p.payment_date);
        setField('payment_type', p.payment_type);
        setField('payment_method_id', p.payment_method_id);
        setField('gross_amount', p.gross_amount);
        setField('hours_worked', p.hours_worked);
        setField('hourly_rate', p.hourly_rate);
        setField('status', p.status);
        setField('reference_number', p.reference_number);
        setField('pay_period_start', p.pay_period_start);
        setField('pay_period_end', p.pay_period_end);
        setField('description', p.description);
        setField('notes', p.notes);

        // Set initial tax total so the form has a value even before recompute
        document.getElementById('editTotalTaxAmount').value = p.total_tax_amount ?? 0;

        // ── Render tax checkboxes ──────────────────────
        const appliedTaxIds = new Set(
            (p.applied_taxes || [])
                .map(t => t && t.tax_id != null ? String(t.tax_id) : null)
                .filter(Boolean)
        );

        const taxList = document.getElementById('editTaxesList');
        if (taxList) {
            const taxes = meta.taxes || [];
            if (!taxes.length) {
                taxList.innerHTML = `<div class="alert alert-warning w-100">${esc('{{ __('payments.no_active_taxes') }}')}</div>`;
            } else {
                taxList.innerHTML = taxes.map(t => `
                    <div class="col-md-4 mb-3">
                        <div class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input edit-tax-checkbox"
                                type="checkbox"
                                name="selected_taxes[]"
                                value="${t.id}"
                                id="edit_tax_${t.id}"
                                data-rate="${t.rate ?? 0}"
                                ${appliedTaxIds.has(String(t.id)) ? 'checked' : ''}>
                            <label class="form-check-label" for="edit_tax_${t.id}">
                                <span class="fw-bold">${esc(t.name)}</span><br>
                                <span class="badge badge-light-info">${esc(t.display_rate ?? t.rate ?? '')}</span>
                                ${t.code ? `<small class="text-muted d-block">${esc(t.code)}</small>` : ''}
                            </label>
                        </div>
                    </div>
                `).join('');
            }
        }

        // ── Pre-fill advance_deductions hidden field ──
        const advHidden = form.querySelector('[name="advance_deductions"]');
        if (advHidden) {
            advHidden.value = JSON.stringify(p.advance_deductions || []);
        }
        const advTotal = form.querySelector('[name="total_advance_deduction"]');
        if (advTotal) advTotal.value = p.total_advance_deduction ?? 0;

        // ── Refresh previews if they exist ──
        if (typeof window.PaymentForms?.updateAdvancePreview === 'function') {
            // wait for advance table to render first
            setTimeout(() => {
                window.PaymentForms.updateAdvancePreview(form, {
                    containerId: 'editAdvanceDeductionsContainer',
                    previewId:   'editAdvancePreview',
                });
            }, 200);
        }

        // Trigger tax preview if taxes exist and gross is set
        if (appliedTaxIds.size > 0 && parseFloat(p.gross_amount) > 0) {
            recalcEditTaxes(p);
        }

        // Re-bind submit
        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitEdit(p.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    // Tax recalc for the edit modal
    function recalcEditTaxes(p) {
        const form = document.getElementById('paymentEditForm');
        if (!form) return;

        const grossInput = form.querySelector('[name="gross_amount"]');
        const gross = parseFloat(grossInput?.value || 0) || 0;
        const taxes = Array.from(form.querySelectorAll('.edit-tax-checkbox:checked')).map(cb => cb.value);
        const empId = document.getElementById('paymentEditEmployeeId')?.value || null;

        if (!window.PaymentForms?.renderTaxPreview) return;

        window.PaymentForms.renderTaxPreview(form, {
            grossAmount: gross,
            taxes,
            employeeId: empId,
            ids: {
                preview: 'editTaxPreview',
                breakdownBody: 'editTaxBreakdownBody',
                gross: 'editPreviewGross',
                tax: 'editPreviewTax',
                net: 'editPreviewNet',
            },
            onNet: (calc) => {
                const totalEl = document.getElementById('editTotalTaxAmount');
                if (!calc) {
                    if (totalEl) totalEl.value = 0;
                    return;
                }
                if (totalEl) totalEl.value = calc.total_tax_amount ?? 0;
            },
        });
    }

    async function submitEdit(id, btn) {
        const form = document.getElementById('paymentEditForm');
        if (!form) return;
        clearErrors(form);

        // Build body — FormData handles arrays + files; convert to URLSearchParams
        const fd = new FormData(form);

        // Handle tax checkboxes explicitly (FormData only picks them up if they're checked)
        fd.delete('selected_taxes[]');
        form.querySelectorAll('.edit-tax-checkbox:checked').forEach(cb => {
            fd.append('selected_taxes[]', cb.value);
        });

        // Guard overtime fields when not overtime
        if (fd.get('payment_type') !== 'overtime') {
            fd.delete('hours_worked');
            fd.delete('hourly_rate');
        }

        fd.set('_method', 'PUT');

        // Drop empty strings so validation rules like nullable work
        const body = new URLSearchParams();
        for (const [k, v] of fd.entries()) {
            if (v === 'undefined' || v === '') continue;
            body.append(k, v);
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
                body,
            });
            const json = await res.json().catch(() => ({}));

            if (res.status === 422) {
                Object.entries(json.errors || {}).forEach(([field, msgs]) => {
                    const input  = form.querySelector(`[name="${field}"]`) || form.querySelector(`[name="${field}[]"]`);
                    const holder = form.querySelector(`#${CSS.escape(field)}`);
                    if (input)  input.classList.add('is-invalid');
                    if (holder) { holder.classList.add('invalid-feedback', 'd-block'); holder.textContent = msgs[0]; }
                });
                window.toastr?.error?.(Object.values(json.errors || {})[0]?.[0] || json.message || 'Validation failed');
                setButtonLoading(btn, false);
                return;
            }
            if (!res.ok || json.success === false) throw new Error(json.message || 'Update failed');

            window.toastr?.success?.(json.message || 'Payment updated.');
            setButtonLoading(btn, false);
            bootstrap.Modal.getInstance(document.getElementById('paymentEditModal'))?.hide();
            window.PaymentList?.reload?.();
            if (isOpenFor(id)) await reload(id);
        } catch (err) {
            console.error('[payment-drawer] edit failed:', err);
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
        const label = button.querySelector('.indicator-label');
        const progress = button.querySelector('.indicator-progress');
        if (isLoading) {
            button.disabled = true;
            if (label) label.style.display = 'none';
            if (progress) progress.style.display = 'inline';
        } else {
            button.disabled = false;
            if (label) label.style.display = 'inline';
            if (progress) progress.style.display = 'none';
        }
    }
    function formatNumber(n) { const num = Number(n) || 0; return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.PaymentDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
        _recalcEditTaxes: (payload) => recalcEditTaxes(payload),
    };

    return window.PaymentDrawer;
})();
</script>