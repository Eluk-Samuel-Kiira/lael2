
{{-- resources/views/department/employee-payment/payment-page-scripts.blade.php --}}
<script>
(function () {
    'use strict';

    if (!window.PAYMENT_ROUTES) {
        console.error('[payment-page] PAYMENT_ROUTES missing.');
        return;
    }

    const CSRF   = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const ROUTES = window.PAYMENT_ROUTES;
    const CURRENCY = (() => {
        const el = document.querySelector('[data-currency-symbol]');
        return el?.dataset.currencySymbol || window.PAYMENT_BOOTSTRAP?.currency || '';
    })();

    // ── Utilities ────────────────────────────────────────────
    const esc = (t) => { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; };
    const fmt = (n) => (Number(n) || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const setBtn = (btn, loading) => {
        if (!btn) return;
        const label    = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        btn.disabled = !!loading;
        if (label)    label.style.display    = loading ? 'none' : 'inline';
        if (progress) progress.style.display = loading ? 'inline' : 'none';
    };
    const fetchJSON = async (url, options = {}) => {
        const isForm = options.body instanceof FormData;
        const res = await fetch(url, {
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.body && !isForm ? { 'Content-Type': 'application/json' } : {}),
                ...(options.headers || {}),
            },
            ...options,
        });
        const json = await res.json().catch(() => ({}));
        return { res, json };
    };

    // ── Field helpers ────────────────────────────────────────
    const getVal = (scope, name) => {
        const el = scope.querySelector(`[name="${name}"]`);
        return el ? el.value : '';
    };
    const setVal = (scope, name, value) => {
        const el = scope.querySelector(`[name="${name}"]`);
        if (!el) return;
        el.value = value == null ? '' : String(value);
        el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    // ── Tax preview ──────────────────────────────────────────
    async function renderTaxPreview(form, { grossAmount, taxes, employeeId, ids, onNet }) {
        const preview = document.getElementById(ids.preview);
        const tbody   = document.getElementById(ids.breakdownBody);

        if (!grossAmount || !taxes.length) {
            preview?.classList.add('d-none');
            onNet && onNet(null);
            return;
        }

        try {
            const { res, json } = await fetchJSON(ROUTES.tax_preview, {
                method: 'POST',
                body: JSON.stringify({
                    gross_amount: grossAmount,
                    selected_taxes: taxes,
                    employee_id: employeeId || null,
                }),
            });
            if (!res.ok || !json.success) throw new Error(json.message || 'Tax preview failed');

            const calc = json.calculation || {};
            preview?.classList.remove('d-none');

            const gEl = document.getElementById(ids.gross);
            const tEl = document.getElementById(ids.tax);
            const nEl = document.getElementById(ids.net);
            if (gEl) gEl.textContent = fmt(calc.gross_amount);
            if (tEl) tEl.textContent = fmt(calc.total_tax_amount);
            if (nEl) nEl.textContent = fmt(calc.net_amount);

            if (tbody) {
                tbody.innerHTML = '';
                (calc.tax_breakdown || []).forEach(t => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td><span class="fw-bold">${esc(t.label || t.tax_name || 'Tax')}</span></td>
                        <td><span class="badge badge-light-info">${esc(t.rate || '0%')}</span></td>
                        <td class="text-end fw-bold">${fmt(t.amount || 0)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            onNet && onNet(calc);
        } catch (err) {
            console.error('[tax-preview] failed:', err);
            window.toastr?.error?.(err.message || 'Tax preview failed');
            preview?.classList.add('d-none');
            onNet && onNet(null);
        }
    }

    // ── Advance loader ───────────────────────────────────────
    async function loadAdvances(form, { employeeId, paymentType, paymentId = null, containerId, previewId }) {
        console.log(employeeId);
        
        const container = document.getElementById(containerId);
        const preview   = document.getElementById(previewId);
        if (!container) return;

        if (!employeeId) {
            container.innerHTML = `<div class="text-center py-5 text-muted">
                <i class="fas fa-arrow-up fs-2 mb-3 d-block"></i>
                <span>{{ __('payments.select_employee_to_view_advances') }}</span></div>`;
            preview?.classList.add('d-none');
            return;
        }

        container.innerHTML = `<div class="text-center py-4">
            <div class="spinner-border text-warning" role="status"></div>
            <p class="mt-2 text-muted">{{ __('payments.loading_advances') }}</p></div>`;

        const params = new URLSearchParams({ payment_type: paymentType || '' });
        if (paymentId) params.set('payment_id', paymentId);

        try {
            const { res, json } = await fetchJSON(`/employee-advances/${employeeId}/active?${params}`, { method: 'GET' });
            if (!res.ok || !json.success) throw new Error(json.message || 'Load failed');

            if (!(json.advances || []).length) {
                container.innerHTML = `<div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle fs-2 mb-3 text-success d-block"></i>
                    <span>${esc(json.message || '{{ __('payments.no_active_advances') }}')}</span></div>`;
                preview?.classList.add('d-none');
                return;
            }

            renderAdvanceTable(form, json.advances, { paymentId, containerId, previewId });
        } catch (err) {
            console.error('[advance-loader] failed:', err);
            container.innerHTML = `<div class="text-center py-5 text-danger">
                <i class="fas fa-exclamation-triangle fs-2 mb-3 d-block"></i>
                <span>{{ __('payments.error_loading_advances') }}</span></div>`;
            preview?.classList.add('d-none');
        }
    }

    function renderAdvanceTable(form, advances, { paymentId = null, containerId, previewId }) {
        const container = document.getElementById(containerId);
        if (!container) return;

        let selectedMap = {};
        const hiddenInit = form.querySelector('[name="advance_deductions"]');
        if (hiddenInit && hiddenInit.value) {
            try {
                (JSON.parse(hiddenInit.value) || []).forEach(d => {
                    selectedMap[d.advance_id] = d.deduction_amount;
                });
            } catch (_) {}
        }

        const rows = advances.map(a => {
            const isSelected = selectedMap[a.id] !== undefined;
            const suggested  = isSelected ? selectedMap[a.id] : (a.installment_amount || a.remaining_amount);
            const lockedPaid = a.is_fully_paid && !isSelected;

            return `<tr class="${lockedPaid ? 'bg-light-secondary text-muted' : ''}">
                <td><div class="form-check form-check-sm form-check-custom form-check-solid">
                    <input class="form-check-input advance-checkbox" type="checkbox" value="${a.id}"
                           ${isSelected ? 'checked' : ''} ${lockedPaid ? 'disabled' : ''}>
                </div></td>
                <td>${esc(a.advance_date)}</td>
                <td class="fw-bold">${esc(CURRENCY)} ${fmt(a.advance_amount)}</td>
                <td class="${a.remaining_amount > 0 ? 'text-warning' : 'text-success'} fw-bold">${esc(CURRENCY)} ${fmt(a.remaining_amount)}</td>
                <td><span class="badge badge-light-info">${esc(a.deduction_frequency_label)}</span></td>
                <td><span class="badge badge-light-primary">${esc(a.status)}</span></td>
                <td class="text-end">
                    <div class="input-group input-group-sm w-200px ms-auto">
                        <input type="number" class="form-control form-control-sm advance-deduction-amount"
                               data-advance-id="${a.id}" min="0.01" max="${a.remaining_amount}" step="0.01"
                               value="${suggested}" ${isSelected ? '' : 'disabled'}>
                        <span class="input-group-text">${esc(CURRENCY)}</span>
                    </div>
                </td>
            </tr>`;
        }).join('');

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="w-50px"><input class="form-check-input advance-check-all" type="checkbox"></th>
                            <th>{{ __('payments.advance_date') }}</th>
                            <th>{{ __('payments.advance_amount') }}</th>
                            <th>{{ __('payments.remaining') }}</th>
                            <th>{{ __('payments.deduction_frequency') }}</th>
                            <th>{{ __('payments.status') }}</th>
                            <th class="text-end">{{ __('payments.to_deduct') }}</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;

        container.querySelectorAll('.advance-checkbox').forEach(cb => {
            cb.addEventListener('change', () => {
                const inp = container.querySelector(`.advance-deduction-amount[data-advance-id="${cb.value}"]`);
                if (inp) inp.disabled = !cb.checked;
                updateAdvancePreview(form, { containerId, previewId });
            });
        });
        container.querySelectorAll('.advance-deduction-amount').forEach(inp => {
            inp.addEventListener('input', () => updateAdvancePreview(form, { containerId, previewId }));
        });
        container.querySelector('.advance-check-all')?.addEventListener('change', function () {
            container.querySelectorAll('.advance-checkbox:not(:disabled)').forEach(cb => {
                cb.checked = this.checked;
                const inp = container.querySelector(`.advance-deduction-amount[data-advance-id="${cb.value}"]`);
                if (inp) inp.disabled = !this.checked;
            });
            updateAdvancePreview(form, { containerId, previewId });
        });

        updateAdvancePreview(form, { containerId, previewId });
    }

    function updateAdvancePreview(form, { containerId, previewId }) {
        const container = document.getElementById(containerId);
        const preview   = document.getElementById(previewId);
        if (!container) return;

        const deductions = [];
        let total = 0;

        container.querySelectorAll('.advance-checkbox:checked').forEach(cb => {
            const inp = container.querySelector(`.advance-deduction-amount[data-advance-id="${cb.value}"]`);
            const amount = parseFloat(inp?.value || 0) || 0;
            if (amount > 0) {
                deductions.push({ advance_id: parseInt(cb.value, 10), deduction_amount: amount });
                total += amount;
            }
        });

        const hidden     = form.querySelector('[name="advance_deductions"]');
        const totalField = form.querySelector('[name="total_advance_deduction"]');
        if (hidden)     hidden.value = JSON.stringify(deductions);
        if (totalField) totalField.value = total.toFixed(2);

        const countEl = form.querySelector('[data-preview-advance-count]');
        const totalEl = form.querySelector('[data-preview-advance-total]');
        const afterEl = form.querySelector('[data-preview-advance-after]');
        const gross   = parseFloat(form.querySelector('[name="gross_amount"]')?.value || 0) || 0;

        if (countEl) countEl.textContent = deductions.length;
        if (totalEl) totalEl.textContent = fmt(total);
        if (afterEl) afterEl.textContent = fmt(Math.max(0, gross - total));

        preview?.classList.toggle('d-none', deductions.length === 0);
    }

    // ── CREATE modal ─────────────────────────────────────────
    function wireCreate() {
        const modal = document.getElementById('kt_modal_add_payment');
        const form  = document.getElementById('kt_modal_add_payment_form');
        if (!modal || !form) return;

        const submitBtn = document.getElementById('submitEmplPaymentButton');
        const btnLoad   = document.getElementById('btnLoadAdvances');
        const btnTax    = document.getElementById('btnPreviewTaxes');

        const schedule = (() => {
            const timers = new Map();
            return (key, fn, ms = 200) => {
                clearTimeout(timers.get(key));
                timers.set(key, setTimeout(fn, ms));
            };
        })();

        function refreshOvertimeFields() {
            const isOt = getVal(form, 'payment_type') === 'overtime';
            form.querySelectorAll('.overtime-fields').forEach(el => el.style.display = isOt ? '' : 'none');
        }

        function refreshAdvances() {
            loadAdvances(form, {
                employeeId:  getVal(form, 'employee_id'),
                paymentType: getVal(form, 'payment_type'),
                containerId: 'advance_deductions_container',
                previewId:   'advance_deduction_preview',
            });
        }

        function refreshTaxes() {
            const gross = parseFloat(getVal(form, 'gross_amount') || 0) || 0;
            const taxes = Array.from(form.querySelectorAll('.tax-checkbox:checked')).map(cb => cb.value);
            const empId = getVal(form, 'employee_id');
            renderTaxPreview(form, {
                grossAmount: gross,
                taxes,
                employeeId: empId,
                ids: {
                    preview: 'tax_preview',
                    breakdownBody: 'tax_breakdown_body',
                    gross: 'preview_gross',
                    tax: 'preview_tax',
                    net: 'preview_net',
                },
                onNet: (calc) => {
                    if (!calc) return;
                    form.querySelector('[name="net_amount"]').value        = calc.net_amount ?? gross;
                    form.querySelector('[name="total_tax_amount"]').value = calc.total_tax_amount ?? 0;
                },
            });
        }

        // Button handlers
        btnLoad?.addEventListener('click', (e) => { e.preventDefault(); refreshAdvances(); });
        btnTax?.addEventListener('click', (e) => { e.preventDefault(); refreshTaxes(); });

        // Any change inside the modal — bubbles up from Tom Select's hidden select
        modal.addEventListener('change', (e) => {
            const name = e.target?.name;
            if (!name) return;

            if (name === 'payment_type') {
                refreshOvertimeFields();
                schedule('adv', refreshAdvances);
                schedule('tax', refreshTaxes);
            }
            if (name === 'employee_id') schedule('adv', refreshAdvances);
            if (name === 'gross_amount') {
                schedule('tax', refreshTaxes);
                // also refresh advance preview after-total
                updateAdvancePreview(form, {
                    containerId: 'advance_deductions_container',
                    previewId:   'advance_deduction_preview',
                });
            }
            if (['hours_worked', 'hourly_rate'].includes(name) && getVal(form, 'payment_type') === 'overtime') {
                const h = parseFloat(getVal(form, 'hours_worked') || 0);
                const r = parseFloat(getVal(form, 'hourly_rate') || 0);
                if (h > 0 && r > 0) form.querySelector('[name="gross_amount"]').value = (h * r).toFixed(2);
                schedule('tax', refreshTaxes);
            }
        });

        // Re-run on modal open (in case preselected values exist)
        modal.addEventListener('shown.bs.modal', () => {
            setTimeout(() => {
                refreshOvertimeFields();
                refreshAdvances();
                refreshTaxes();
            }, 100);
        });

        // Submit
        submitBtn?.addEventListener('click', async (e) => {
            e.preventDefault();

            const isOt = getVal(form, 'payment_type') === 'overtime';

            const fd = new FormData(form);
            fd.delete('selected_taxes[]');
            form.querySelectorAll('.tax-checkbox:checked').forEach(cb => {
                fd.append('selected_taxes[]', cb.value);
            });
            fd.set('_method', 'POST');

            // ✅ Strip overtime fields when not overtime
            if (!isOt) {
                fd.delete('hours_worked');
                fd.delete('hourly_rate');
            }

            setBtn(submitBtn, true);
            form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            form.querySelectorAll('.invalid-feedback.d-block').forEach(el => { el.textContent = ''; el.classList.remove('d-block'); });

            try {
                const { res, json } = await fetchJSON(ROUTES.store, { method: 'POST', body: fd });

                if (res.status === 422) {
                    Object.entries(json.errors || {}).forEach(([field, msgs]) => {
                        const input  = form.querySelector(`[name="${field}"]`) || form.querySelector(`[name="${field}[]"]`);
                        const holder = form.querySelector(`#${CSS.escape(field)}`);
                        if (input)  input.classList.add('is-invalid');
                        if (holder) { holder.classList.add('invalid-feedback', 'd-block'); holder.textContent = msgs[0]; }
                    });
                    window.toastr?.error?.(Object.values(json.errors || {})[0]?.[0] || json.message || 'Validation failed');
                    setBtn(submitBtn, false);
                    return;
                }
                if (!res.ok || json.success === false) throw new Error(json.message || 'Create failed');

                window.toastr?.success?.(json.message || 'Payment created.');
                setBtn(submitBtn, false);
                bootstrap.Modal.getInstance(modal)?.hide();
                form.reset();
                window.PaymentList?.reload?.();
            } catch (err) {
                console.error('[create-payment] failed:', err);
                window.toastr?.error?.(err.message || 'Something went wrong.');
                setBtn(submitBtn, false);
            }
        });
    }

    // ── EDIT modal ───────────────────────────────────────────
    function wireEdit() {
        const modal = document.getElementById('paymentEditModal');
        const form  = document.getElementById('paymentEditForm');
        if (!modal || !form) return;
        if (modal.dataset.wired === '1') return;
        modal.dataset.wired = '1';

        const schedule = (() => {
            const timers = new Map();
            return (key, fn, ms = 250) => {
                clearTimeout(timers.get(key));
                timers.set(key, setTimeout(fn, ms));
            };
        })();

        function refreshOvertimeFields() {
            const isOt = getVal(form, 'payment_type') === 'overtime';
            const hEl = document.getElementById('paymentHoursField');
            const rEl = document.getElementById('paymentRateField');
            if (hEl) hEl.style.display = isOt ? '' : 'none';
            if (rEl) rEl.style.display = isOt ? '' : 'none';
        }

        function refreshTaxes() {
            const payload = window.PaymentDrawer?.getCurrent?.();
            // Use the drawer's own recalc helper if exposed; else call directly
            if (typeof window.PaymentDrawer?._recalcEditTaxes === 'function') {
                window.PaymentDrawer._recalcEditTaxes(payload?.payload);
            }
        }

        function refreshAdvances() {
            const currentId = document.getElementById('paymentEditId')?.value;
            loadAdvances(form, {
                employeeId:  getVal(form, 'employee_id'),
                paymentType: getVal(form, 'payment_type'),
                paymentId:   currentId ? parseInt(currentId, 10) : null,
                containerId: 'editAdvanceDeductionsContainer',
                previewId:   'editAdvancePreview',
            });
        }

        // Button hooks
        document.getElementById('btnEditPreviewTaxes')?.addEventListener('click', (e) => {
            e.preventDefault();
            refreshTaxes();
        });

        document.getElementById('btnEditLoadAdvances')?.addEventListener('click', (e) => {
            e.preventDefault();
            refreshAdvances();
        });

        // Change hooks — bubble from the modal
        modal.addEventListener('change', (e) => {
            const name = e.target?.name;
            if (!name) return;

            if (name === 'payment_type') {
                refreshOvertimeFields();
                schedule('adv', refreshAdvances);
                schedule('tax', refreshTaxes);
            }
            if (name === 'employee_id') schedule('adv', refreshAdvances);
            if (name === 'gross_amount') schedule('tax', refreshTaxes);
            if (name === 'selected_taxes[]') schedule('tax', refreshTaxes);

            if (['hours_worked', 'hourly_rate'].includes(name) && getVal(form, 'payment_type') === 'overtime') {
                const h = parseFloat(getVal(form, 'hours_worked') || 0);
                const r = parseFloat(getVal(form, 'hourly_rate') || 0);
                if (h > 0 && r > 0) form.querySelector('[name="gross_amount"]').value = (h * r).toFixed(2);
                schedule('tax', refreshTaxes);
            }
        });

        modal.addEventListener('shown.bs.modal', () => {
            setTimeout(() => {
                refreshOvertimeFields();
                refreshAdvances();
                refreshTaxes();
            }, 150);
        });
    }

    // ── BOOT ─────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        wireCreate();
        wireEdit();
    });

    // Expose for drawer use
    window.PaymentForms = { loadAdvances, renderTaxPreview, updateAdvancePreview, getVal, setVal };
})();
</script>