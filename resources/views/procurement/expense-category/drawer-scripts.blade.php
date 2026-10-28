<script>
window.ExpCatDrawer = (function () {
    'use strict';

    const URL_SHOW   = window.EXP_CAT_ROUTES.drawer_data;
    const URL_STATUS = window.EXP_CAT_ROUTES.status;
    const URL_UPDATE = window.EXP_CAT_ROUTES.update;
    const URL_DELETE = window.EXP_CAT_ROUTES.destroy;
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const CURRENCY   = @json(currency_symbol());

    let currentId      = null;
    let currentPayload = null;
    let bs             = null;

    function getBs() {
        const el = document.getElementById('expCatDrawer');
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
            currentPayload = json.category;
            render(json.category);
        } catch (err) {
            console.error('[exp-cat-drawer] load failed:', err);
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
            currentPayload = json.category;
            render(json.category);
        } catch (err) { console.error('[exp-cat-drawer] reload failed:', err); }
    }

    function showSkeleton() {
        document.getElementById('expCatDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('expCatDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('expCatDrawerFooter')?.classList.add('d-none');
        const name = document.getElementById('expCatDrawerName');
        if (name) name.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('expCatDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('expCatDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(c) {
        currentId = c.id;

        const nameEl = document.getElementById('expCatDrawerName');
        if (nameEl) nameEl.textContent = c.name || '';

        document.getElementById('expCatDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('expCatDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = `
            ${renderHeaderBlock(c)}
            ${renderMetaBlock(c)}
            ${renderFlagsBlock(c)}
            ${renderBudgetBlock(c)}
            ${renderDescriptionBlock(c)}
            ${renderAuditBlock(c)}
        `;

        document.getElementById('expCatDrawerActions').innerHTML = renderActionCluster(c);
        document.getElementById('expCatDrawerFooter').classList.remove('d-none');
        bindActions(c);
    }

    function renderHeaderBlock(c) {
        const statusBadge = c.is_active
            ? `<span class="badge badge-light-success fs-7 py-2 px-3">Active</span>`
            : `<span class="badge badge-light-warning fs-7 py-2 px-3">Inactive</span>`;

        return `
            <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(c.name)}</h5>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge badge-light-dark fs-8">${esc(c.code || '—')}</span>
                        <span class="badge badge-light fs-8">#${c.id}</span>
                        ${statusBadge}
                    </div>
                </div>
            </div>
        `;
    }

    function renderMetaBlock(c) {
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-info-circle me-2 text-primary"></i>Details
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-3">
                    ${kv('GL Account ID', c.gl_account_id)}
                    ${kv('Expenses Using This Category', c.expense_count)}
                    ${kv('Created at', c.created_at)}
                    ${kv('Updated at', c.updated_at)}
                </div>
            </div>
        `;
    }

    function renderFlagsBlock(c) {
        const flags = [];
        if (c.requires_receipt)  flags.push(`<span class="badge badge-light-warning fs-8"><i class="bi bi-receipt me-1"></i>Receipt required</span>`);
        if (c.requires_approval) flags.push(`<span class="badge badge-light-info fs-8"><i class="bi bi-shield-check me-1"></i>Approval required</span>`);

        if (flags.length === 0) return '';

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-toggle-on me-2 text-success"></i>Flags
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="d-flex flex-wrap gap-2">${flags.join('')}</div>
            </div>
        `;
    }

    function renderBudgetBlock(c) {
        if (!c.budget_monthly && !c.budget_annual) return '';

        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-cash-coin me-2 text-warning"></i>Budget
            </h6>
            <div class="row g-3 mb-6">
                ${c.budget_monthly ? `
                    <div class="col-md-6">
                        <div class="card bg-light-primary">
                            <div class="card-body py-3 text-center">
                                <span class="text-muted fs-8 d-block text-uppercase">Monthly</span>
                                <span class="fs-5 fw-bold text-primary">${money(c.budget_monthly)}</span>
                            </div>
                        </div>
                    </div>
                ` : ''}
                ${c.budget_annual ? `
                    <div class="col-md-6">
                        <div class="card bg-light-success">
                            <div class="card-body py-3 text-center">
                                <span class="text-muted fs-8 d-block text-uppercase">Annual</span>
                                <span class="fs-5 fw-bold text-success">${money(c.budget_annual)}</span>
                            </div>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    function renderDescriptionBlock(c) {
        if (!c.description) return '';
        return `
            <h6 class="fw-bold text-gray-800 mb-3">
                <i class="bi bi-card-text me-2 text-info"></i>Description
            </h6>
            <div class="bg-light rounded-3 p-4 mb-6">
                <p class="mb-0 text-gray-700 fs-7" style="white-space:pre-wrap;">${esc(c.description)}</p>
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
                    ${kv('Created at', c.created_at)}
                    ${kv('Last updated', c.updated_at)}
                </div>
            </div>
        `;
    }

    function kv(label, value) {
        if (value === null || value === undefined || value === '') return '';
        return `
            <div class="col-6">
                <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">${esc(label)}</div>
                <div class="fw-semibold text-gray-800 fs-7">${esc(value)}</div>
            </div>
        `;
    }

    function renderActionCluster(c) {
        const btns = [];

        if (c.can_toggle) {
            btns.push(`
                <select class="form-select form-select-sm fw-bold"
                        data-drawer-status-select data-current-status="${c.is_active ? 1 : 0}"
                        style="width:160px; cursor:pointer;">
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

        if (c.can_delete && !c.is_active && c.expense_count === 0) {
            btns.push(`<button type="button" class="btn btn-sm btn-danger d-flex align-items-center"
                              data-drawer-action="delete">
                        <i class="bi bi-trash me-2"></i>Delete
                      </button>`);
        }

        return `<div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">${btns.join('')}</div>`;
    }

    function bindActions(c) {
        const actions = document.getElementById('expCatDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', async () => {
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
                    window.ExpCatList?.reload?.();
                    await reload(c.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally { sel.disabled = false; }
            });
        }
    }

    async function doDelete(c) {
        if (!confirm(`Delete category "${c.name}"? This cannot be undone.`)) return;
        try {
            const json = await req('DELETE', URL_DELETE.replace('__ID__', c.id), null);
            window.toastr?.success?.(json.message || 'Category deleted.');
            window.ExpCatList?.reload?.();
            close();
        } catch (err) {
            window.toastr?.error?.(err.message);
        }
    }

    // ─── Shared edit modal ────────────────────────────────
    function openEditModal(c) {
        const modalEl = document.getElementById('expCatEditModal');
        const form    = document.getElementById('expCatEditForm');
        if (!modalEl || !form) {
            window.toastr?.error?.('Edit modal not available.');
            return;
        }

        // Clear prior errors
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
            el.textContent = '';
            el.classList.remove('d-block');
        });

        // Populate
        const setField = (name, value) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (!el) return;
            if (el.type === 'checkbox') el.checked = !!value;
            else el.value = value ?? '';
        };

        document.getElementById('expCatEditId').value = c.id;
        const header = document.getElementById('expCatEditHeaderName');
        if (header) header.textContent = c.name || '';

        ['name','code','description','gl_account_id','budget_monthly','budget_annual'].forEach(f => setField(f, c[f]));
        setField('is_active', c.is_active);
        setField('requires_receipt', c.requires_receipt);
        setField('requires_approval', c.requires_approval);

        // Re-bind submit
        const btn = document.getElementById('expCatEditSubmit');
        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.replaceWith(newBtn);
            newBtn.addEventListener('click', () => submitExpCatEdit(c.id, newBtn));
        }

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    async function submitExpCatEdit(id, btn) {
        const form = document.getElementById('expCatEditForm');
        if (!form) return;

        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback.d-block').forEach(el => {
            el.textContent = '';
            el.classList.remove('d-block');
        });

        const data = Object.fromEntries(new FormData(form));
        data._method = 'PUT';
        delete data.expCatEditId;
        delete data._token;

        data.is_active         = form.querySelector('[name="is_active"]')?.checked ? 1 : 0;
        data.requires_receipt  = form.querySelector('[name="requires_receipt"]')?.checked ? 1 : 0;
        data.requires_approval = form.querySelector('[name="requires_approval"]')?.checked ? 1 : 0;

        Object.keys(data).forEach(k => {
            if (data[k] === '' || data[k] === 'undefined') delete data[k];
        });

        setButtonLoading(btn, true);

        let keepSpinningDuringClose = false;

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
            window.toastr?.success?.(json.message || 'Category updated.');

            // We're about to close the modal — let the spinner keep running while
            // the fade plays out, THEN the `finally` block restores the button
            // after a short delay so it's ready for the next open.
            keepSpinningDuringClose = true;

            bootstrap.Modal.getInstance(document.getElementById('expCatEditModal'))?.hide();

            // Refresh table & drawer — do NOT let their errors affect the button
            try {
                window.ExpCatList?.reload?.();
            } catch (e) {
                console.error('[exp-cat] table reload failed:', e);
            }

            if (isOpenFor(id)) {
                try {
                    await reload(id);
                } catch (e) {
                    console.error('[exp-cat] drawer reload failed:', e);
                }
            }

            return;

        } catch (err) {
            console.error('[exp-cat-drawer] edit failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
        } finally {
            // Always restore the button — the guard that was previously here
            // caused the button to stay stuck whenever the modal was still fading
            // out OR had already finished closing by the time we reached finally.
            if (keepSpinningDuringClose) {
                // Modal is fading out — let the spinner run for the animation,
                // then restore. Next open will re-enable via openEditModal.
                setTimeout(() => {
                    if (document.getElementById('expCatEditModal')?.classList.contains('show')) {
                        setButtonLoading(btn, false);
                    } else {
                        setButtonLoading(btn, false); // unconditional
                    }
                }, 350); // matches Bootstrap's fade-out
            } else {
                // Not closing (422 or error) — restore immediately
                setButtonLoading(btn, false);
            }
        }
    }

    // ─── Helpers ──────────────────────────────────────────
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

    function esc(t) { if (t == null) return ''; const d = document.createElement('div'); d.textContent = String(t); return d.innerHTML; }
    function money(n) { return CURRENCY + Number(n ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    window.ExpCatDrawer = {
        open, reload, close, isOpenFor,
        getCurrent: () => ({ id: currentId, payload: currentPayload }),
    };
    return window.ExpCatDrawer;
})();
</script>