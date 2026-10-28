<script>
window.ExpenseDrawer = (function () {
    'use strict';

    const URL_SHOW   = @json(route('expense.drawer-data', ['id' => '__ID__']));
    const URL_APPROVE = (id) => `/expense/${id}/approve`;
    const URL_STATUS  = (id) => `/expense-status/${id}`;
    const URL_DELETE  = (id) => `/expense/${id}`;
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CURRENCY    = @json(currency_symbol());

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('expenseDrawer');
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

            currentPayload = json.expense;
            render(json.expense);
        } catch (err) {
            console.error('[expense-drawer] load failed:', err);
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
            currentPayload = json.expense;
            render(json.expense);
        } catch (err) { console.error('[expense-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('expenseDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('expenseDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('expenseDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('expenseDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('expenseDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('expenseDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(e) {
        currentId = e.id;

        const nameEl = document.getElementById('expenseDrawerName');
        if (nameEl) nameEl.textContent = (e.expense_number || '') + ' — ' + (e.description || '').substring(0, 60);

        document.getElementById('expenseDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('expenseDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(e)}
            ${renderAmountBlock(e)}
            ${renderMetaBlock(e)}
            ${renderTaxBlock(e)}
            ${renderApprovalBlock(e)}
            ${renderNotesBlock(e)}
        `;

        document.getElementById('expenseDrawerActions').innerHTML = renderActionCluster(e);
        document.getElementById('expenseDrawerFooter').classList.remove('d-none');
        bindActions(e);
    }

    // ─── Header ───────────────────────────────────────
    function renderHeaderBlock(e) {
        const statusBadge = {
            pending:    `<span class="badge badge-light-warning fs-7 py-2 px-3">Pending</span>`,
            paid:       `<span class="badge badge-light-success fs-7 py-2 px-3">Paid</span>`,
            reimbursed: `<span class="badge badge-light-info fs-7 py-2 px-3">Reimbursed</span>`,
        }[e.payment_status] || `<span class="badge badge-light-secondary fs-7">${esc(e.payment_status)}</span>`;

        const approvedBadge = e.approved_at
            ? `<span class="badge badge-light-success fs-8"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>`
            : `<span class="badge badge-light-warning fs-8">Not approved</span>`;

        return `
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(e.description || '—')}</h5>
                    <div class="text-muted fs-7 mb-2">
                        <code class="text-gray-700">${esc(e.expense_number || '—')}</code>
                        <span class="mx-2">·</span>
                        <i class="bi bi-calendar3 me-1"></i>${esc(e.date_formatted || '—')}
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        ${statusBadge}
                        ${approvedBadge}
                    </div>
                </div>
            </div>
        `;
    }

    // ─── Amounts ──────────────────────────────────────
    function renderAmountBlock(e) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-cash-stack me-2 text-success"></i>Amounts
            </h6>
            <div class="row g-3 mb-6">
                <div class="col-6 col-md-3">
                    <div class="card bg-light">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Gross</span>
                            <span class="fs-5 fw-bold text-gray-800">${money(e.gross_amount)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-warning">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Tax</span>
                            <span class="fs-5 fw-bold text-warning">${money(e.tax_amount)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-primary">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Net</span>
                            <span class="fs-5 fw-bold text-primary">${money(e.net_amount)}</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-success">
                        <div class="card-body py-3 text-center">
                            <span class="text-muted fs-8 d-block text-uppercase">Total</span>
                            <span class="fs-5 fw-bold text-success">${money(e.total_amount)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // ─── Metadata ─────────────────────────────────────
    function renderMetaBlock(e) {
        const rows = [
            ['Vendor',         e.vendor_name || '—'],
            ['Supplier',       e.supplier?.name || '—'],
            ['Category',       e.category?.name || '—'],
            ['Employee',       e.employee?.name || '—'],
            ['Location',       e.location?.name || '—'],
            ['Department',     e.department?.name || '—'],
            ['Payment Method', e.payment_method
                ? `${e.payment_method.name}${e.payment_method.is_default ? ' (default)' : ''}`
                : '—'],
            ['Paid Date',      e.paid_date_formatted || '—'],
            ['Created by',     e.creator?.name || '—'],
            ['Created at',     e.created_at || '—'],
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
                            <div class="fw-semibold text-gray-800 fs-7">${esc(value)}</div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    // ─── Tax breakdown ────────────────────────────────
    function renderTaxBlock(e) {
        if (!e.tax_breakdown || e.tax_breakdown.length === 0) return '';

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-receipt me-2 text-warning"></i>Tax Breakdown
                <span class="badge badge-light-secondary ms-2">${e.tax_breakdown.length}</span>
            </h6>
            <div class="table-responsive mb-6">
                <table class="table table-row-dashed align-middle fs-7 gy-3 mb-0">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-8 text-uppercase gs-0">
                            <th>Tax</th>
                            <th class="text-center">Rate</th>
                            <th class="text-center">Type</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${e.tax_breakdown.map(t => `
                            <tr>
                                <td>
                                    <div class="fw-bold">${esc(t.tax_name)}</div>
                                    ${t.tax_code ? `<small class="text-muted">${esc(t.tax_code)}</small>` : ''}
                                </td>
                                <td class="text-center">${Number(t.rate).toFixed(2)}%</td>
                                <td class="text-center">
                                    <span class="badge badge-light-${t.is_withholding_tax ? 'danger' : 'primary'} fs-8">
                                        ${t.is_withholding_tax ? 'Withholding' : 'Additive'}
                                    </span>
                                </td>
                                <td class="text-end fw-bold">${money(t.amount)}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    // ─── Approval ─────────────────────────────────────
    function renderApprovalBlock(e) {
        if (!e.approved_at && !e.can_approve) return '';

        const body = e.approved_at
            ? `<div class="d-flex align-items-center gap-3">
                   <i class="bi bi-check-circle-fill text-success fs-2"></i>
                   <div>
                       <div class="fw-bold text-gray-800">Approved</div>
                       <div class="text-muted fs-7">
                           by ${esc(e.approved_by_name || '—')} on ${esc(e.approved_at)}
                       </div>
                   </div>
               </div>`
            : `<div class="d-flex align-items-center gap-3">
                   <i class="bi bi-clock text-warning fs-2"></i>
                   <div>
                       <div class="fw-bold text-gray-800">Awaiting approval</div>
                       <div class="text-muted fs-7">
                           This expense has not been approved yet. Use the Approve button below.
                       </div>
                   </div>
               </div>`;

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-shield-check me-2 text-success"></i>Approval
            </h6>
            <div class="bg-light-success rounded-3 p-4 mb-6">${body}</div>
        `;
    }

    // ─── Notes / audit trail ──────────────────────────
    function renderNotesBlock(e) {
        if (!e.notes) return '';
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-journal-text me-2 text-info"></i>Activity Trail
            </h6>
            <div class="bg-light rounded-3 p-4 mb-4" style="max-height:300px;overflow-y:auto;">
                <pre class="mb-0 text-gray-700 fs-7" style="white-space:pre-wrap;font-family:inherit;">${esc(e.notes)}</pre>
            </div>
        `;
    }

    // ─── Action cluster ───────────────────────────────
    function renderActionCluster(e) {
        const btns = [];

        if (e.can_approve) {
            btns.push(`<button type="button" class="btn btn-sm btn-success d-flex align-items-center"
                              data-drawer-action="approve">
                        <i class="bi bi-check-circle me-2"></i>Approve
                      </button>`);
        }

        if (e.can_update_status && e.approved_at) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-status-select data-current-status="${esc(e.payment_status)}"
                        style="width:160px; cursor:pointer;">
                    <option value="">Change status…</option>
                    <option value="pending"    ${e.payment_status === 'pending'    ? 'disabled' : ''}>Pending</option>
                    <option value="paid"       ${e.payment_status === 'paid'       ? 'disabled' : ''}>Paid</option>
                    <option value="reimbursed" ${e.payment_status === 'reimbursed' ? 'disabled' : ''}>Reimbursed</option>
                </select>
            `);
        }

        if (e.can_upload) {
            btns.push(`<button type="button" class="btn btn-sm btn-light d-flex align-items-center"
                              data-drawer-action="upload">
                        <i class="bi bi-upload me-2"></i>Upload Receipt
                      </button>`);
        }

        if (e.can_edit) {
            btns.push(`<button type="button" class="btn btn-sm btn-primary d-flex align-items-center"
                              data-drawer-action="edit">
                        <i class="bi bi-pencil-square me-2"></i>Edit
                      </button>`);
        }

        if (e.can_delete && e.payment_status !== 'paid') {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">
            ${btns.join('')}
        </div>`;
    }

    // ─── Action bindings ──────────────────────────────
    function bindActions(e) {
        const actions = document.getElementById('expenseDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', async () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'approve') return doApprove(e);
                if (a === 'upload')  return openUploadReceipt(e);
                if (a === 'edit')    return openEdit(e);
                if (a === 'delete')  return doDelete(e);
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
                    const json = await req('POST', URL_STATUS(e.id), { status: newStatus });
                    window.toastr?.success?.(json.message || 'Status updated.');
                    window.ExpenseList?.reload?.();
                    await reload(e.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    async function doApprove(e) {
        if (!confirm('Approve this expense?')) return;
        try {
            const json = await req('POST', URL_APPROVE(e.id), { approved: 1 });
            window.toastr?.success?.(json.message || 'Expense approved.');
            window.ExpenseList?.reload?.();
            await reload(e.id);
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    async function doDelete(e) {
        if (!confirm('Delete this expense permanently?')) return;
        try {
            const json = await req('DELETE', URL_DELETE(e.id), null);
            window.toastr?.success?.(json.message || 'Expense deleted.');
            window.ExpenseList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    function openEdit(e) {
        if (typeof window.ExpenseActions?.edit === 'function') {
            // Reuse the existing edit flow
            window.ExpenseActions.edit(e.id);
        } else {
            window.toastr?.error?.('Edit module not available.');
        }
    }

    function openUploadReceipt(e) {
        if (typeof window.ExpenseActions?.uploadReceipt === 'function') {
            window.ExpenseActions.uploadReceipt(e.id);
        } else {
            window.toastr?.error?.('Upload module not available.');
        }
    }

    // ─── Helpers ──────────────────────────────────────
    function esc(t) {
        if (t == null) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }
    function money(n) {
        return CURRENCY + Number(n ?? 0).toLocaleString('en-US', {
            minimumFractionDigits: 2, maximumFractionDigits: 2,
        });
    }

    window.ExpenseDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.ExpenseDrawer;
})();
</script>