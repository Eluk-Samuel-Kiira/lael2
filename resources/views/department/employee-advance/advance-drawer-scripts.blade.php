<script>
window.AdvanceDrawer = (function () {
    'use strict';

    if (!window.ADVANCE_ROUTES) {
        console.error('[advance-drawer] ADVANCE_ROUTES undefined.');
        return { open: () => {}, reload: () => {}, close: () => {}, isOpenFor: () => false, getCurrent: () => ({ id: null, payload: null }) };
    }

    const URL_SHOW    = window.ADVANCE_ROUTES.drawer_data;
    const URL_UPDATE  = window.ADVANCE_ROUTES.update;
    const URL_DELETE  = window.ADVANCE_ROUTES.destroy;
    const URL_APPROVE = window.ADVANCE_ROUTES.approve;
    const URL_REJECT  = window.ADVANCE_ROUTES.reject;
    const URL_CANCEL  = window.ADVANCE_ROUTES.cancel;
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId = null, currentPayload = null, bs = null;

    function getBs() {
        const el = document.getElementById('advanceDrawer');
        if (!el) { console.error('[advance-drawer] #advanceDrawer missing'); return null; }
        if (!window.bootstrap?.Offcanvas) { console.error('[advance-drawer] bootstrap.Offcanvas missing'); return null; }
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
            currentPayload = json.advance;
            render(json.advance);
        } catch (err) {
            console.error('[advance-drawer] load failed:', err);
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
            currentPayload = json.advance;
            render(json.advance);
        } catch (err) { console.error('[advance-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('advanceDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('advanceDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('advanceDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('advanceDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('advanceDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('advanceDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(a) {
        currentId = a.id;
        const nameEl = document.getElementById('advanceDrawerName');
        if (nameEl) nameEl.textContent = `#${a.id} — ${a.employee_name}`;

        document.getElementById('advanceDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('advanceDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeader(a)}
            ${renderMoney(a)}
            ${renderDetails(a)}
            ${renderHistory(a)}
            ${renderSchedule(a)}
            ${renderAudit(a)}
        `;

        document.getElementById('advanceDrawerActions').innerHTML = renderActionCluster(a);
        document.getElementById('advanceDrawerFooter').classList.remove('d-none');
        bindActions(a);
    }

    const statusColors = {
        pending: 'warning', approved: 'info', partially_paid: 'primary',
        fully_paid: 'success', rejected: 'danger', cancelled: 'secondary',
    };

    function renderHeader(a) {
        return `
            <div class="d-flex align-items-center gap-4 mb-6">
                <div class="symbol symbol-circle symbol-70px">
                    <div class="symbol-label bg-light-primary text-primary fw-bold fs-2x">${esc(a.employee_initials)}</div>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(a.employee_name)}</h5>
                    <div class="text-muted fs-7 mb-2">${esc(a.employee_email)} · ${esc(a.department_name)}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light-${statusColors[a.status] || 'secondary'} fs-8 py-2 px-3">${esc(a.status)}</span>
                        ${a.approval_date ? `<span class="badge badge-light-info fs-8">Approved ${esc(a.approval_date)}</span>` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    function renderMoney(a) {
        return `
            <div class="row g-3 mb-5">
                <div class="col-md-4"><div class="card card-dashed bg-light-warning"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('payments.advance_amount') }}</div>
                    <div class="fs-3 fw-bold text-warning">${esc(formatNumber(a.advance_amount))} ${esc(a.currency_symbol)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-dashed bg-light"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('payments.remaining') }}</div>
                    <div class="fs-3 fw-bold">${esc(formatNumber(a.remaining_amount))} ${esc(a.currency_symbol)}</div>
                </div></div></div>
                <div class="col-md-4"><div class="card card-dashed bg-light-success"><div class="card-body">
                    <div class="text-muted fs-8 text-uppercase mb-1">{{ __('payments.recovered_amount') }}</div>
                    <div class="fs-3 fw-bold text-success">${esc(formatNumber(a.recovered_amount))} ${esc(a.currency_symbol)}</div>
                </div></div></div>
            </div>
        `;
    }

    function kv(rows) {
        return `<div class="row g-3">${rows.map(([label, value]) => `
            <div class="col-6 col-md-3">
                <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(label)}</div>
                <div class="fw-semibold text-gray-800 fs-7">${esc(value || '—')}</div>
            </div>`).join('')}</div>`;
    }

    function section(title, icon, body) {
        return `<h6 class="fw-bold text-gray-800 mb-3"><i class="bi ${icon} me-2 text-primary"></i>${esc(title)}</h6>
                <div class="bg-light rounded-3 p-4 mb-5">${body}</div>`;
    }

    function renderDetails(a) {
        return section('Details', 'bi-info-circle', kv([
            ['Advance Date', a.advance_date_label],
            ['Request Date', a.request_date],
            ['Frequency',    a.deduction_frequency_label],
            ['Installments', a.installments > 1 ? `${a.installments_paid}/${a.installments}` : 'N/A'],
            ['Purpose',      a.purpose],
            ['Reason',       a.reason],
            ['Notes',        a.notes],
        ]));
    }

    function renderHistory(a) {
        const hist = a.deduction_history || [];
        if (!hist.length) return '';
        const rows = hist.map(h => `
            <tr>
                <td>${esc(h.date)}</td>
                <td class="text-end text-danger fw-bold">${esc(formatNumber(h.amount))}</td>
                <td><span class="badge badge-light-primary">#${h.payment_id}</span></td>
                <td class="text-end text-success fw-bold">${esc(formatNumber(h.remaining_after))}</td>
            </tr>`).join('');
        return section('Deduction History', 'bi-clock-history', `
            <div class="table-responsive"><table class="table table-row-bordered align-middle fs-7 mb-0">
                <thead class="border-bottom border-gray-200 fs-8 fw-bold text-uppercase">
                    <tr><th>Date</th><th class="text-end">Amount</th><th>Payment</th><th class="text-end">Remaining</th></tr>
                </thead><tbody>${rows}</tbody>
            </table></div>
        `);
    }

    function renderSchedule(a) {
        const sched = a.deduction_schedule || [];
        if (!sched.length) return '';
        const rows = sched.map(s => `
            <tr>
                <td>#${s.installment}</td>
                <td>${esc(s.scheduled_date)}</td>
                <td class="text-end fw-bold">${esc(formatNumber(s.amount))}</td>
                <td><span class="badge badge-light-${s.status === 'completed' ? 'success' : 'warning'}">${esc(s.status)}</span></td>
            </tr>`).join('');
        return section('Deduction Schedule', 'bi-calendar-event', `
            <div class="table-responsive"><table class="table table-row-bordered align-middle fs-7 mb-0">
                <thead class="border-bottom border-gray-200 fs-8 fw-bold text-uppercase">
                    <tr><th>#</th><th>Scheduled</th><th class="text-end">Amount</th><th>Status</th></tr>
                </thead><tbody>${rows}</tbody>
            </table></div>
        `);
    }

    function renderAudit(a) {
        return section('Audit', 'bi-clock-history', kv([
            ['Created at', a.created_at],
            ['Updated at', a.updated_at],
            ['Approved by', a.approved_by_name],
            ['Rejection reason', a.rejection_reason],
        ]));
    }

    function renderActionCluster(a) {
        const btns = [];

        if (a.can_approve) {
            btns.push(`<button type="button" class="btn btn-sm btn-success d-flex align-items-center" data-drawer-action="approve">
                        <i class="bi bi-check-circle me-2"></i>Approve</button>`);
        }
        if (a.can_reject) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center" data-drawer-action="reject">
                        <i class="bi bi-x-circle me-2"></i>Reject</button>`);
        }
        if (a.can_cancel) {
            btns.push(`<button type="button" class="btn btn-sm btn-warning d-flex align-items-center" data-drawer-action="cancel">
                        <i class="bi bi-x-octagon me-2"></i>Cancel</button>`);
        }
        if (a.can_delete) {
            btns.push(`<button type="button" class="btn btn-sm btn-light-danger d-flex align-items-center" data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete</button>`);
        }
        if (a.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center" data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit</button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(a) {
        const actions = document.getElementById('advanceDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', async () => {
                const act = el.getAttribute('data-drawer-action');
                if (act === 'edit')    return openEditModal(a);
                if (act === 'delete')  return doDelete(a);
                if (act === 'approve') return doApprove(a);
                if (act === 'reject')  return doReject(a);
                if (act === 'cancel')  return doCancel(a);
            });
        });
    }

    async function doApprove(a) {
        const paymentMethods = a.payment_methods || [];
        if (!paymentMethods.length) {
            window.toastr?.error?.('No active payment method available.');
            return;
        }

        // Build modal once per call (idempotent)
        let modalEl = document.getElementById('advanceApproveModal');
        if (modalEl) modalEl.remove();

        modalEl = document.createElement('div');
        modalEl.className = 'modal fade';
        modalEl.id = 'advanceApproveModal';
        modalEl.tabIndex = -1;
        modalEl.innerHTML = `
            <div class="modal-dialog modal-dialog-centered mw-500px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="fw-bold">${esc(a.status === 'pending' ? '{{ __("payments.approve_advance") }}' : '')}</h2>
                        <button type="button" class="btn btn-icon btn-sm" data-bs-dismiss="modal">
                            <i class="bi bi-x fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-4">{{ __("payments.confirm_approve_message") }}</p>
                        <div class="bg-light-warning rounded p-3 mb-4">
                            <div class="fw-bold fs-5">${esc(a.employee_name)}</div>
                            <div class="text-muted">${esc(a.currency_symbol)} ${esc(formatNumber(a.advance_amount))}</div>
                        </div>
                        <label class="form-label required">{{ __("payments.disbursement_method") }}</label>
                        <select id="advanceApprovePaymentMethod" class="form-select form-select-solid">
                            ${paymentMethods.map(m => `
                                <option value="${m.id}" ${m.is_default ? 'selected' : ''}>
                                    ${esc(m.name)}${m.is_default ? ' ({{ __("payments._default") }})' : ''}
                                </option>
                            `).join('')}
                        </select>
                        <div id="advanceApproveError" class="text-danger mt-2 d-none"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __("auth._cancel") }}</button>
                        <button type="button" id="advanceApproveConfirm" class="btn btn-success">
                            <span class="indicator-label">{{ __("payments.approve_and_disburse") }}</span>
                            <span class="indicator-progress" style="display:none;">
                                {{ __("auth.please_wait") }}
                                <span class="spinner-border spinner-border-sm ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalEl);

        const bsModal = new bootstrap.Modal(modalEl, { backdrop: 'static' });

        // Confirm handler
        const confirmBtn = modalEl.querySelector('#advanceApproveConfirm');
        confirmBtn.addEventListener('click', async () => {
            const pmId = modalEl.querySelector('#advanceApprovePaymentMethod')?.value;
            const errEl = modalEl.querySelector('#advanceApproveError');

            if (!pmId) {
                if (errEl) { errEl.textContent = '{{ __("payments.payment_method_required") }}'; errEl.classList.remove('d-none'); }
                return;
            }
            if (errEl) errEl.classList.add('d-none');

            setButtonLoading(confirmBtn, true);
            try {
                const json = await req('POST', URL_APPROVE.replace('__ID__', a.id), { payment_method_id: pmId });
                bsModal.hide();
                window.toastr?.success?.(json.message || 'Advance approved.');
                window.AdvanceList?.reload?.();
                await reload(a.id);
            } catch (err) {
                window.toastr?.error?.(err.message);
                setButtonLoading(confirmBtn, false);
            }
        });

        // Cleanup on close
        modalEl.addEventListener('hidden.bs.modal', () => {
            bsModal.dispose();
            modalEl.remove();
        });

        bsModal.show();
    }

    async function doReject(a) {
        const modalEl = document.getElementById('advanceRejectModal');
        const summary = document.getElementById('advanceRejectSummary');
        const reasonEl = document.getElementById('advanceRejectReason');
        const errorEl = document.getElementById('advanceRejectError');
        const confirmBtn = document.getElementById('advanceRejectConfirm');

        if (!modalEl) { window.toastr?.error?.('Reject modal not available.'); return; }

        // Reset
        if (reasonEl) reasonEl.value = '';
        if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('d-none'); }
        if (summary) summary.innerHTML = `
            <div class="fw-bold">${esc(a.employee_name)}</div>
            <div class="text-muted">${esc(a.currency_symbol)} ${esc(formatNumber(a.advance_amount))}</div>
        `;
        setButtonLoading(confirmBtn, false);

        // One-shot confirm handler
        const onClick = async () => {
            const reason = (reasonEl?.value || '').trim();
            if (!reason) {
                if (errorEl) { errorEl.textContent = '{{ __("payments.rejection_reason_required") }}'; errorEl.classList.remove('d-none'); }
                reasonEl?.focus();
                return;
            }
            if (errorEl) errorEl.classList.add('d-none');

            setButtonLoading(confirmBtn, true);
            try {
                const json = await req('POST', URL_REJECT.replace('__ID__', a.id), { rejection_reason: reason });
                modalEl.querySelector('[data-bs-dismiss="modal"]')?.click();
                window.toastr?.success?.(json.message || 'Advance rejected.');
                window.AdvanceList?.reload?.();
                await reload(a.id);
            } catch (err) {
                window.toastr?.error?.(err.message);
                setButtonLoading(confirmBtn, false);
            }
        };

        // Bind fresh each open, remove on close
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static' });
        confirmBtn?.addEventListener('click', onClick);
        modalEl.addEventListener('hidden.bs.modal', () => {
            confirmBtn?.removeEventListener('click', onClick);
        }, { once: true });

        modal.show();
        setTimeout(() => reasonEl?.focus(), 300);
    }

    async function doCancel(a) {
        const result = await Swal.fire({
            title: '{{ __("payments.confirm_advance") }}',
            text: '{{ __("payments.confirm_advance_message") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __("payments.yes_cancel") }}',
            cancelButtonText: '{{ __("auth._cancel") }}',
        });
        if (!result.isConfirmed) return;

        try {
            const json = await req('POST', URL_CANCEL.replace('__ID__', a.id), null);
            window.toastr?.success?.(json.message || 'Advance cancelled.');
            window.AdvanceList?.reload?.();
            await reload(a.id);
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    async function doDelete(a) {
        if (!confirm(`Delete advance #${a.id}? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', a.id), null);
            window.toastr?.success?.(json.message || 'Advance deleted.');
            window.AdvanceList?.reload?.();
            close();
        } catch (err) { window.toastr?.error?.(err.message); }
    }

    // ─── Edit modal ────────────────────────────────────────
    function openEditModal(a) {
        const modalEl = document.getElementById('advanceEditModal');
        const form    = document.getElementById('advanceEditForm');
        if (!modalEl || !form) {
            console.error('[advance-drawer] edit modal nodes missing', { modalEl: !!modalEl, form: !!form });
            window.toastr?.error?.('Edit modal not available.');
            return;
        }

        const btn = document.getElementById('advanceEditSubmit');
        if (btn) setButtonLoading(btn, false);
        clearErrors(form);

        document.getElementById('advanceEditId').value = a.id;
        const header = document.getElementById('advanceEditHeaderName');
        if (header) header.textContent = `#${a.id} — ${a.employee_name}`;

        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.tagName === 'SELECT') {
                const target = value == null ? '' : String(value);
                const hasBlank = Array.from(el.options).some(o => o.value === '');
                const matched  = Array.from(el.options).some(o => o.value === target && o.value !== '');
                if (matched) el.value = target;
                else if (hasBlank) el.value = '';
                else { const first = Array.from(el.options).find(o => o.value !== ''); el.value = first ? first.value : ''; }
            } else if (el.type === 'checkbox') {
                el.checked = !!value;
            } else {
                el.value = value ?? '';
            }
        };

        setField('advance_amount', a.advance_amount);
        setField('advance_date',   a.advance_date);
        setField('deduction_frequency', a.deduction_frequency);
        setField('installments',   a.installments > 1 ? a.installments : '');
        setField('deduction_day',  a.deduction_day);
        setField('deduction_start_date', a.deduction_start_date);
        setField('purpose', a.purpose);
        setField('reason',  a.reason);
        setField('notes',   a.notes);

        // applicable_salary_types checkboxes
        form.querySelectorAll('input[name="applicable_salary_types[]"]').forEach(cb => {
            cb.checked = (a.applicable_salary_types || []).includes(cb.value);
        });

        // currency symbol
        form.querySelectorAll('.advance-currency').forEach(el => { el.textContent = a.currency_symbol || ''; });

        // toggle installment/deduction_day visibility
        const toggleFields = () => {
            const freq = form.querySelector('[name="deduction_frequency"]')?.value;
            const recurring = ['weekly', 'monthly', 'yearly'].includes(freq);
            const inst = document.getElementById('advanceInstallmentsField');
            const day  = document.getElementById('advanceDeductionDayField');
            if (inst) inst.style.display = recurring ? '' : 'none';
            if (day)  day.style.display  = recurring ? '' : 'none';
        };
        toggleFields();
        form.querySelector('[name="deduction_frequency"]')?.addEventListener('change', toggleFields);

        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitEdit(a.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitEdit(id, btn) {
        const form = document.getElementById('advanceEditForm');
        if (!form) return;
        clearErrors(form);

        const fd = new FormData(form);
        fd.set('_method', 'PUT');
        fd.delete('_token');
        fd.delete('advanceEditId');

        // Build URLSearchParams from FormData
        const body = new URLSearchParams();
        for (const [k, v] of fd.entries()) {
            if (v === '' || v === 'undefined') continue;
            body.append(k, v);
        }
        // ensure empty applicable_salary_types still arrives as empty array marker
        if (!body.has('applicable_salary_types[]')) body.append('applicable_salary_types_present', '1');

        setButtonLoading(btn, true);
        try {
            const res = await fetch(URL_UPDATE.replace('__ID__', id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json',
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
                const firstError = Object.values(json.errors || {})[0]?.[0];
                window.toastr?.error?.(firstError || json.message || 'Please fix the highlighted fields.');
                setButtonLoading(btn, false);
                return;
            }
            if (!res.ok || json.success === false) throw new Error(json.message || 'Update failed');

            window.toastr?.success?.(json.message || 'Advance updated.');
            setButtonLoading(btn, false);
            bootstrap.Modal.getInstance(document.getElementById('advanceEditModal'))?.hide();
            window.AdvanceList?.reload?.();
            if (isOpenFor(id)) await reload(id);
        } catch (err) {
            console.error('[advance-drawer] edit failed:', err);
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
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.AdvanceDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.AdvanceDrawer;
})();
</script>