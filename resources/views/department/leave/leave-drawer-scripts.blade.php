<script>
window.LeaveDrawer = (function () {
    'use strict';

    if (!window.LEAVE_ROUTES) {
        console.error('[leave-drawer] LEAVE_ROUTES undefined.');
        return { open: () => {}, reload: () => {}, close: () => {}, isOpenFor: () => false, getCurrent: () => ({ id: null, payload: null }) };
    }

    const URL_SHOW    = window.LEAVE_ROUTES.drawer_data;
    const URL_APPROVE = window.LEAVE_ROUTES.approve;
    const URL_REJECT  = window.LEAVE_ROUTES.reject;
    const URL_CANCEL  = window.LEAVE_ROUTES.cancel;
    const URL_UPDATE  = window.LEAVE_ROUTES.update;
    const URL_DELETE  = window.LEAVE_ROUTES.destroy;
    const CSRF        = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId = null, currentPayload = null, bs = null, pendingRejectId = null;

    function getBs() {
        const el = document.getElementById('leaveDrawer');
        if (!el) { console.error('[leave-drawer] #leaveDrawer missing'); return null; }
        if (!window.bootstrap?.Offcanvas) { console.error('[leave-drawer] bootstrap missing'); return null; }
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }
    function close()       { getBs()?.hide(); }
    function isOpenFor(id) { return String(currentId) === String(id); }

    async function req(method, url, body) {
        const res = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
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
            const res = await fetch(URL_SHOW.replace('__ID__', id), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed');
            currentPayload = json.leave;
            render(json.leave);
        } catch (err) {
            console.error('[leave-drawer] load failed:', err);
            renderError(err.message);
        }
    }

    async function reload(id) {
        if (!id) return;
        try {
            const res = await fetch(URL_SHOW.replace('__ID__', id), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const json = await res.json();
            if (!json.success) return;
            currentPayload = json.leave;
            render(json.leave);
        } catch (err) { console.error('[leave-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('leaveDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('leaveDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('leaveDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('leaveDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('leaveDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('leaveDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(l) {
        currentId = l.id;
        const nameEl = document.getElementById('leaveDrawerName');
        if (nameEl) nameEl.textContent = `#${l.id} — ${l.employee_name}`;

        document.getElementById('leaveDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('leaveDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeader(l)}
            ${renderTimeline(l)}
            ${renderDetails(l)}
            ${renderHandover(l)}
            ${renderAttachments(l)}
            ${renderReasoning(l)}
        `;

        document.getElementById('leaveDrawerActions').innerHTML = renderActionCluster(l);
        document.getElementById('leaveDrawerFooter').classList.remove('d-none');
        bindActions(l);
    }

    const statusColors = {
        pending: 'warning', approved: 'info', ongoing: 'primary',
        completed: 'success', rejected: 'danger', cancelled: 'secondary',
    };

    function renderHeader(l) {
        return `
            <div class="d-flex align-items-center gap-4 mb-6">
                <div class="symbol symbol-circle symbol-70px">
                    <div class="symbol-label bg-light-primary text-primary fw-bold fs-2x">${esc(l.employee_initials)}</div>
                </div>
                <div class="flex-grow-1">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(l.employee_name)}</h5>
                    <div class="text-muted fs-7 mb-2">${esc(l.employee_email)} · ${esc(l.department_name)}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light-${statusColors[l.status] || 'secondary'}">${esc(l.status)}</span>
                        <span class="badge badge-light-primary">${esc(l.leave_type_label)}</span>
                        <span class="badge badge-light-${l.is_paid ? 'success' : 'secondary'}">
                            ${l.is_paid ? 'Paid' : 'Unpaid'}
                        </span>
                    </div>
                </div>
            </div>
        `;
    }

    function section(title, icon, body) {
        return `<h6 class="fw-bold text-gray-800 mb-3"><i class="bi ${icon} me-2 text-primary"></i>${esc(title)}</h6>
                <div class="bg-light rounded-3 p-4 mb-5">${body}</div>`;
    }
    function kv(rows) {
        return `<div class="row g-3">${rows.map(([l, v]) => `
            <div class="col-6 col-md-3">
                <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(l)}</div>
                <div class="fw-semibold text-gray-800 fs-7">${esc(v || '—')}</div>
            </div>`).join('')}</div>`;
    }

    function renderTimeline(l) {
        const items = [];
        if (l.applied_at) items.push(['bi-calendar-plus text-primary', 'Applied', l.applied_at]);
        if (l.approved_at) items.push(['bi-check-circle text-success', `Approved by ${l.approved_by || '—'}`, l.approved_at]);
        if (l.rejected_at) items.push(['bi-x-circle text-danger', `Rejected by ${l.rejected_by || '—'}`, l.rejected_at]);
        if (l.cancelled_at) items.push(['bi-x-octagon text-secondary', 'Cancelled', l.cancelled_at]);

        return section('Timeline', 'bi-clock-history', `
            <div class="d-flex flex-wrap gap-4">
                ${items.map(([icon, label, ts]) => `
                    <div class="d-flex align-items-start">
                        <i class="bi ${icon} me-2 fs-5"></i>
                        <div>
                            <div class="fw-bold">${esc(label)}</div>
                            <div class="text-muted fs-7">${esc(ts)}</div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `);
    }

    function renderDetails(l) {
        return section('Leave Details', 'bi-info-circle', kv([
            ['Start', l.start_label],
            ['End', l.end_label],
            ['Total days', l.total_days],
            ['Custom type', l.custom_type],
        ]));
    }

    function renderHandover(l) {
        const emps = l.handover_employees || [];
        const empList = emps.length
            ? emps.map(e => `<span class="badge badge-light-info me-1">${esc(e.name)}${e.job_title ? ' — ' + esc(e.job_title) : ''}</span>`).join('')
            : '<span class="text-muted fs-7">Not assigned</span>';

        return section('Handover', 'bi-arrow-left-right', `
            ${kv([
                ['Alternate contact', l.alternate_contact],
                ['Emergency contact', l.emergency_contact],
            ])}
            <div class="mt-3">
                <div class="text-muted fs-8 text-uppercase mb-1">Handover notes</div>
                <div class="fw-semibold text-gray-800 fs-7">${esc(l.handover_notes || '—')}</div>
            </div>
            <div class="mt-3">
                <div class="text-muted fs-8 text-uppercase mb-1">Handover to</div>
                <div>${empList}</div>
            </div>
        `);
    }

    function renderAttachments(l) {
        const files = l.attachments || [];
        if (!files.length) {
            return section('Attachments', 'bi-paperclip', '<span class="text-muted fs-7">No attachments.</span>');
        }
        return section('Attachments', 'bi-paperclip', `
            <div class="row g-3">
                ${files.map(a => `
                    <div class="col-md-4">
                        <div class="bg-white rounded border p-3">
                            <div class="fw-bold text-truncate">${esc(a.name)}</div>
                            <div class="text-muted fs-7">${Math.round((a.size || 0) / 1024)} KB</div>
                            ${a.url ? `<a href="${esc(a.url)}" target="_blank" class="btn btn-sm btn-light-primary mt-2">View</a>` : ''}
                        </div>
                    </div>
                `).join('')}
            </div>
        `);
    }

    function renderReasoning(l) {
        return section('Reason', 'bi-chat-dots', `<div class="fw-semibold text-gray-800 fs-7">${esc(l.reason || '—')}</div>`);
    }

    function renderActionCluster(l) {
        const btns = [];
        if (l.can_approve) btns.push(`<button class="btn btn-sm btn-success" data-drawer-action="approve"><i class="bi bi-check-circle me-2"></i>Approve</button>`);
        if (l.can_reject)  btns.push(`<button class="btn btn-sm btn-danger"  data-drawer-action="reject"><i class="bi bi-x-circle me-2"></i>Reject</button>`);
        if (l.can_cancel)  btns.push(`<button class="btn btn-sm btn-warning" data-drawer-action="cancel"><i class="bi bi-x-octagon me-2"></i>Cancel</button>`);
        if (l.can_delete)  btns.push(`<button class="btn btn-sm btn-light-danger" data-drawer-action="delete"><i class="bi bi-trash me-2"></i>Delete</button>`);
        if (l.can_edit)    btns.push(`<button class="btn btn-sm btn-primary" data-drawer-action="edit"><i class="bi bi-pencil-square me-2"></i>Edit</button>`);
        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(l) {
        const actions = document.getElementById('leaveDrawerActions');
        if (!actions) return;
        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', () => {
                const a = el.getAttribute('data-drawer-action');
                if (a === 'approve') return doApprove(l);
                if (a === 'reject')  return openRejectModal(l);
                if (a === 'cancel')  return doCancel(l);
                if (a === 'delete')  return doDelete(l);
                if (a === 'edit')    return openEditModal(l);
            });
        });
    }

    async function doApprove(l) {
        if (!confirm(`Approve leave #${l.id}?`)) return;
        try {
            const json = await req('POST', URL_APPROVE.replace('__ID__', l.id), null);
            window.toastr?.success?.(json.message || 'Leave approved.');
            window.LeaveList?.reload?.();
            await reload(l.id);
        } catch (err) { window.toastr?.error?.(err.message); }
    }

    function openRejectModal(l) {
        pendingRejectId = l.id;
        const reasonEl = document.getElementById('leaveRejectReason');
        const errEl    = document.getElementById('leaveRejectError');
        if (reasonEl) reasonEl.value = '';
        if (errEl) { errEl.textContent = ''; errEl.classList.add('d-none'); }

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('leaveRejectModal'));
        modal.show();
    }

    // Bind once — the reject modal is shared
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('leaveRejectConfirm')?.addEventListener('click', async () => {
            const reason = (document.getElementById('leaveRejectReason')?.value || '').trim();
            const errEl  = document.getElementById('leaveRejectError');
            const btn    = document.getElementById('leaveRejectConfirm');

            if (!reason) {
                if (errEl) { errEl.textContent = 'Please enter a rejection reason.'; errEl.classList.remove('d-none'); }
                return;
            }
            if (errEl) errEl.classList.add('d-none');

            setButtonLoading(btn, true);
            try {
                const json = await req('POST', URL_REJECT.replace('__ID__', pendingRejectId), { rejection_reason: reason });
                bootstrap.Modal.getInstance(document.getElementById('leaveRejectModal'))?.hide();
                window.toastr?.success?.(json.message || 'Leave rejected.');
                window.LeaveList?.reload?.();
                if (isOpenFor(pendingRejectId)) await reload(pendingRejectId);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setButtonLoading(btn, false);
            }
        });
    });

    async function doCancel(l) {
        if (!confirm(`Cancel leave #${l.id}?`)) return;
        try {
            const json = await req('POST', URL_CANCEL.replace('__ID__', l.id), null);
            window.toastr?.success?.(json.message || 'Leave cancelled.');
            window.LeaveList?.reload?.();
            await reload(l.id);
        } catch (err) { window.toastr?.error?.(err.message); }
    }

    async function doDelete(l) {
        if (!confirm(`Delete leave #${l.id}?`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', l.id), null);
            window.toastr?.success?.(json.message || 'Leave deleted.');
            window.LeaveList?.reload?.();
            close();
        } catch (err) { window.toastr?.error?.(err.message); }
    }

    // ── Edit modal ────────────────────────────────────────
    function openEditModal(l) {
        const modalEl = document.getElementById('leaveEditModal');
        const form    = document.getElementById('leaveEditForm');
        if (!modalEl || !form) { window.toastr?.error?.('Edit modal not available.'); return; }

        const btn = document.getElementById('leaveEditSubmit');
        if (btn) setButtonLoading(btn, false);

        document.getElementById('leaveEditId').value = l.id;
        document.getElementById('leaveEditHeaderName').textContent = `#${l.id} — ${l.employee_name}`;

        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.type === 'checkbox') el.checked = !!value;
            else if (el.tagName === 'SELECT') {
                const target = value == null ? '' : String(value);
                el.value = Array.from(el.options).some(o => o.value === target) ? target : '';
            } else el.value = value ?? '';
        };

        setField('leave_type', l.leave_type);
        setField('custom_type', l.custom_type);
        setField('start_date', l.start_date);
        setField('end_date', l.end_date);
        setField('is_paid', l.is_paid);
        setField('alternate_contact', l.alternate_contact);
        setField('emergency_contact', l.emergency_contact);
        setField('reason', l.reason);
        setField('handover_notes', l.handover_notes);
        setField('notes', l.notes);

        const toggleCustom = () => {
            const isOther = form.querySelector('[name="leave_type"]')?.value === 'other';
            const f = document.getElementById('leaveCustomTypeField');
            if (f) f.style.display = isOther ? '' : 'none';
        };
        toggleCustom();
        form.querySelector('[name="leave_type"]')?.addEventListener('change', toggleCustom);

        const newBtn = btn.cloneNode(true);
        btn.replaceWith(newBtn);
        newBtn.addEventListener('click', () => submitEdit(l.id, newBtn));

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitEdit(id, btn) {
        const form = document.getElementById('leaveEditForm');
        if (!form) return;
        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        if (!('is_paid' in data)) data.is_paid = '0';
        Object.keys(data).forEach(k => { if (data[k] === '' || data[k] === 'undefined') delete data[k]; });

        setButtonLoading(btn, true);
        try {
            const res = await fetch(URL_UPDATE.replace('__ID__', id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(data),
            });
            const json = await res.json().catch(() => ({}));

            if (res.status === 422) {
                window.toastr?.error?.(Object.values(json.errors || {})[0]?.[0] || 'Please check the fields.');
                setButtonLoading(btn, false);
                return;
            }
            if (!res.ok || json.success === false) throw new Error(json.message || 'Update failed');

            window.toastr?.success?.(json.message || 'Leave updated.');
            bootstrap.Modal.getInstance(document.getElementById('leaveEditModal'))?.hide();
            window.LeaveList?.reload?.();
            if (isOpenFor(id)) await reload(id);
        } catch (err) {
            window.toastr?.error?.(err.message || 'Something went wrong.');
        } finally {
            setButtonLoading(btn, false);
        }
    }

    function setButtonLoading(button, isLoading) {
        if (!button) return;
        const label = button.querySelector('.indicator-label');
        const progress = button.querySelector('.indicator-progress');
        if (isLoading) { button.disabled = true; if (label) label.style.display = 'none'; if (progress) progress.style.display = 'inline'; }
        else { button.disabled = false; if (label) label.style.display = 'inline'; if (progress) progress.style.display = 'none'; }
    }
    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }

    window.LeaveDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.LeaveDrawer;
})();
</script>