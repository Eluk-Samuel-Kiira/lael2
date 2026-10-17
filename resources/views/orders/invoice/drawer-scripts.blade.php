<script>
window.InvoiceDrawer = (function () {
    'use strict';

    const URL_DETAILS = '{{ route("invoices.details", ["id" => "__ID__"]) }}';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentId = null;
    let currentPayload = null;
    let bs = null;

    function getBs() {
        const el = document.getElementById('invoiceDrawer');
        if (!el) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }

    // ── fetch helper ──────────────────────────────────────────
    async function req(method, url, body) {
        const res = await fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body ? JSON.stringify(body) : undefined,
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.success === false) {
            throw new Error(json.message || 'Request failed');
        }
        return json;
    }

    // ── open / reload ─────────────────────────────────────────
    async function open(id) {
        currentId = id;
        const inst = getBs();
        if (!inst) return;

        showSkeleton();
        inst.show();

        try {
            const res = await fetch(URL_DETAILS.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Failed to load');
            currentPayload = json;
            render(json);
        } catch (err) {
            console.error('[drawer] load failed:', err);
            renderError(err.message);
        }
    }

    async function reload(id) {
        if (!id) return;
        try {
            const res = await fetch(URL_DETAILS.replace('__ID__', id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) return;
            const json = await res.json();
            if (!json.success) return;
            currentPayload = json;
            render(json);
        } catch (err) {
            console.error('[drawer] reload failed:', err);
        }
    }

    function close() { getBs()?.hide(); }

    // ── state helpers ─────────────────────────────────────────
    function showSkeleton() {
        document.getElementById('invoiceDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('invoiceDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('invoiceDrawerFooter')?.classList.add('d-none');
        const num = document.getElementById('invoiceDrawerNumber');
        if (num) num.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('invoiceDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('invoiceDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}
        </div>`;
    }

    // ── main render ───────────────────────────────────────────
    function render(payload) {
        const inv = payload.invoice;
        currentId = inv.id;
        const num = document.getElementById('invoiceDrawerNumber');
        if (num) num.textContent = inv.invoice_number;

        document.getElementById('invoiceDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('invoiceDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderView(inv, payload.items || [], payload.payments || []);

        const footer = document.getElementById('invoiceDrawerFooter');
        const actions = document.getElementById('invoiceDrawerActions');
        actions.innerHTML = renderActionCluster(inv);
        footer.classList.remove('d-none');

        // Bind dropdown + status
        bindActionCluster(inv);
    }

    // ── the invoice body ──────────────────────────────────────
    function renderView(inv, items, payments) {
        return `
            ${renderCustomerBlock(inv)}
            ${renderMetaBlock(inv)}
            ${renderOrderBlock(inv)}
            ${renderItemsBlock(items)}
            ${renderTotalsBlock(inv)}
            ${renderPaymentSummary(inv)}
            ${renderPaymentHistory(payments)}
            ${renderNotesBlock(inv)}
        `;
    }

    function renderCustomerBlock(inv) {
        return `
            <div class="d-flex align-items-start gap-4 mb-6">
                <div class="symbol symbol-50px">
                    <div class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                        ${esc(inv.billing.initial || '?')}
                    </div>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(inv.billing.name || '—')}</h5>
                    <div class="d-flex flex-wrap gap-3 fs-7 text-muted">
                        ${inv.billing.email ? `<span><i class="bi bi-envelope me-1"></i>${esc(inv.billing.email)}</span>` : ''}
                        ${inv.billing.phone ? `<span><i class="bi bi-phone me-1"></i>${esc(inv.billing.phone)}</span>` : ''}
                    </div>
                    ${inv.billing.address ? `<div class="fs-7 text-muted mt-1"><i class="bi bi-geo-alt me-1"></i>${esc(inv.billing.address)}</div>` : ''}
                    ${inv.billing.tax_id ? `<div class="mt-2"><span class="badge badge-light-dark">TIN: ${esc(inv.billing.tax_id)}</span></div>` : ''}
                </div>
            </div>
        `;
    }

    function renderMetaBlock(inv) {
        return `
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('payments.invoice_number') }}</div>
                        <div class="fw-bold text-gray-800 fs-7">${esc(inv.invoice_number)}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('payments.status') }}</div>
                        <span class="badge badge-light-${esc(inv.status_color)}">
                            <i class="bi ${esc(inv.status_icon)} me-1"></i>${esc(inv.status_label)}
                        </span>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('payments.issue_date') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(inv.issue_date_human || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('payments.due_date') }}</div>
                        <div class="fw-semibold fs-7 ${inv.is_overdue ? 'text-danger' : 'text-gray-800'}">
                            ${esc(inv.due_date_human || '—')}
                            ${inv.is_overdue ? `<span class="badge badge-light-danger ms-1">{{ __('payments.overdue') }}</span>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderOrderBlock(inv) {
        if (!inv.order) return '';
        return `
            <div class="notice d-flex bg-light-info rounded border-info border border-dashed mb-6 p-4">
                <i class="bi bi-receipt fs-2 text-info me-4"></i>
                <div class="row g-4 flex-grow-1">
                    <div class="col-sm-4">
                        <div class="text-muted fs-8 fw-semibold text-uppercase">{{ __('payments.order_reference') }}</div>
                        <a href="${esc(inv.order.show_url)}" class="fw-bold text-primary">#${esc(inv.order.order_number)}</a>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted fs-8 fw-semibold text-uppercase">{{ __('payments.location') }}</div>
                        <div class="fw-bold text-gray-800">${esc(inv.order.location_name || '—')}</div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted fs-8 fw-semibold text-uppercase">{{ __('payments.department') }}</div>
                        <div class="fw-bold text-gray-800">${esc(inv.order.department_name || '—')}</div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderItemsBlock(items) {
        if (!items || items.length === 0) return '';
        return `
            <div class="mb-6">
                <h6 class="fw-bold text-gray-800 mb-3">
                    <i class="bi bi-list-ul me-2 text-primary"></i>{{ __('payments.items') ?? 'Line items' }}
                    <span class="badge badge-light-primary ms-1">${items.length}</span>
                </h6>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle gs-0 gy-3 fs-7">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-3 min-w-40px text-center">#</th>
                                <th class="min-w-160px">{{ __('payments.item') }}</th>
                                <th class="min-w-60px text-center">{{ __('payments.qty') }}</th>
                                <th class="min-w-90px text-end">{{ __('payments.unit_price') }}</th>
                                <th class="pe-3 min-w-100px text-end">{{ __('payments.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map((it, i) => `
                                <tr>
                                    <td class="ps-3 text-center text-muted fw-semibold">${i + 1}</td>
                                    <td>
                                        <div class="fw-bold text-gray-800">${esc(it.item_name)}</div>
                                        ${it.sku ? `<div class="text-muted fs-8"><code>${esc(it.sku)}</code></div>` : ''}
                                    </td>
                                    <td class="text-center"><span class="badge badge-light-primary">${esc(it.quantity)}</span></td>
                                    <td class="text-end fw-semibold text-gray-800">${esc(it.unit_price_formatted)}</td>
                                    <td class="pe-3 text-end fw-bold text-gray-800">${esc(it.total_formatted)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function renderTotalsBlock(inv) {
        return `
            <div class="bg-light-primary rounded-3 p-4 mb-6">
                <div class="d-flex justify-content-between fs-7 mb-2">
                    <span class="text-muted">{{ __('payments.subtotal') }}</span>
                    <span class="fw-semibold text-gray-800">${esc(inv.subtotal_formatted)}</span>
                </div>
                ${inv.discount_total > 0 ? `
                    <div class="d-flex justify-content-between fs-7 mb-2">
                        <span class="text-muted">{{ __('payments.discount') }}</span>
                        <span class="fw-semibold text-danger">-${esc(inv.discount_formatted)}</span>
                    </div>
                ` : ''}
                ${inv.tax_total > 0 ? `
                    <div class="d-flex justify-content-between fs-7 mb-2">
                        <span class="text-muted">{{ __('payments.tax') }}</span>
                        <span class="fw-semibold text-warning">${esc(inv.tax_formatted)}</span>
                    </div>
                ` : ''}
                <div class="separator separator-dashed my-2"></div>
                <div class="d-flex justify-content-between">
                    <span class="fw-bold text-primary fs-6">{{ __('payments.grand_total') }}</span>
                    <span class="fw-bold text-primary fs-5">${esc(inv.total_formatted)}</span>
                </div>
            </div>
        `;
    }

    function renderPaymentSummary(inv) {
        return `
            <div class="row g-3 mb-6">
                <div class="col-4"><div class="bg-light-primary rounded-3 p-3 text-center h-100">
                    <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('payments.total_amount') }}</div>
                    <div class="fs-5 fw-bold text-primary">${esc(inv.total_formatted)}</div>
                </div></div>
                <div class="col-4"><div class="bg-light-success rounded-3 p-3 text-center h-100">
                    <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('payments.amount_paid') }}</div>
                    <div class="fs-5 fw-bold text-success">${esc(inv.amount_paid_formatted)}</div>
                </div></div>
                <div class="col-4"><div class="bg-light-danger rounded-3 p-3 text-center h-100">
                    <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('payments.balance_due') }}</div>
                    <div class="fs-5 fw-bold text-danger">${esc(inv.balance_due_formatted)}</div>
                </div></div>
            </div>
        `;
    }

    function renderPaymentHistory(payments) {
        if (!payments || payments.length === 0) return '';
        return `
            <div class="mb-6">
                <h6 class="fw-bold text-gray-800 mb-3">
                    <i class="bi bi-clock-history me-2 text-primary"></i>{{ __('payments.payment_history') }}
                    <span class="badge badge-light-primary ms-1">${payments.length}</span>
                </h6>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle fs-7">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-3">{{ __('payments.date') }}</th>
                                <th class="text-end">{{ __('payments.amount') }}</th>
                                <th>{{ __('payments.method') }}</th>
                                <th class="text-center">{{ __('payments.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${payments.map(p => `
                                <tr>
                                    <td class="ps-3 text-gray-800">${esc(p.payment_date)}</td>
                                    <td class="text-end fw-bold text-success">${esc(p.amount_formatted)}</td>
                                    <td class="text-gray-700">${esc(p.method_name || '—')}</td>
                                    <td class="text-center"><span class="badge badge-light-${esc(p.status_color)}">${esc(p.status_label)}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    function renderNotesBlock(inv) {
        if (!inv.notes && !inv.terms) return '';

        const renderLines = (text) => {
            if (!text) return '';
            const lines = String(text).split(/\r?\n/).map(l => l.trim()).filter(Boolean);
            if (!lines.length) return '';
            return lines.map(l => `<p class="mb-3 text-gray-700 fs-7" style="line-height: 1.7;">${esc(l)}</p>`).join('');
        };

        return `
            <div class="d-flex flex-column gap-4 mb-6">
                ${inv.notes ? `
                    <div class="bg-light rounded-3 p-4">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-gray-300 border-dashed">
                            <i class="bi bi-sticky fs-5 text-warning"></i>
                            <h6 class="fw-bold text-gray-800 mb-0">{{ __('payments.notes') }}</h6>
                        </div>
                        <div>${renderLines(inv.notes)}</div>
                    </div>
                ` : ''}
                ${inv.terms ? `
                    <div class="bg-light rounded-3 p-4">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-gray-300 border-dashed">
                            <i class="bi bi-file-text fs-5 text-info"></i>
                            <h6 class="fw-bold text-gray-800 mb-0">{{ __('payments.terms_conditions') }}</h6>
                        </div>
                        <div>${renderLines(inv.terms)}</div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    function renderActionCluster(inv) {
        const p = inv.permissions || {};
        const statusSelect = renderStatusSelect(inv);
        const actionBar = renderActionBar(inv, p);

        if (!statusSelect && !actionBar) return '';

        // Layout: status select takes up to 45%, action bar takes the rest.
        // Both sit on one row, right-aligned. No wrap.
        return `
            <div class="d-flex align-items-center justify-content-end gap-2 w-100 flex-nowrap">
                ${statusSelect ? `<div style="flex: 0 1 45%; min-width: 130px;">${statusSelect}</div>` : ''}
                ${actionBar ? `<div class="d-flex align-items-center">${actionBar}</div>` : ''}
            </div>
        `;
    }

    // Status select — matches former Blade behaviour exactly.
    function renderStatusSelect(inv) {
        const colorMap = {
            draft:          { bg: '#e9ecef', text: '#495057', border: '#ced4da' },
            sent:           { bg: '#cce5ff', text: '#004085', border: '#b8daff' },
            viewed:         { bg: '#cce5ff', text: '#004085', border: '#b8daff' },
            partially_paid: { bg: '#fff3cd', text: '#856404', border: '#ffeeba' },
            paid:           { bg: '#d4edda', text: '#155724', border: '#c3e6cb' },
            overdue:        { bg: '#f8d7da', text: '#721c24', border: '#f5c6cb' },
            void:           { bg: '#e2e3e5', text: '#383d41', border: '#d6d8db' },
            cancelled:      { bg: '#f8d7da', text: '#721c24', border: '#f5c6cb' },
        };
        const c = colorMap[inv.status] || colorMap.draft;

        const isPayable = ['sent', 'viewed', 'partially_paid', 'overdue'].includes(inv.status);
        const isEventDriven = ['sent', 'viewed'].includes(inv.status);

        // Which transitions are allowed from the current state
        const transitions = [];
        if (inv.status === 'draft') {
            transitions.push({ value: 'draft', label: `📄 {{ __('payments.draft') }}` });
        }
        if (isPayable || inv.status === 'paid') {
            transitions.push({ value: 'partially_paid', label: `💳 {{ __('payments.partially_paid') }}` });
            transitions.push({ value: 'paid',           label: `✅ {{ __('payments.paid') }}` });
        }
        if (isPayable) {
            transitions.push({ value: 'overdue', label: `⚠️ {{ __('payments.overdue') }}` });
        }

        // Only render the select when there's something meaningful to pick.
        if (transitions.length <= 1) return '';

        // ── KEY CHANGE: placeholder as first option, nothing pre-selected ──
        // The visible value is always the placeholder ("Change status…"), never
        // the current state. That means:
        //   • the browser always has a "different" option to open into
        //   • picking the current state (e.g. partially_paid) fires a real change
        //   • the onChange handler can then decide what to do
        return `
            <select class="form-select form-select-sm fw-bold" data-drawer-status-select
                data-current-status="${inv.status}"
                style="
                    background-color: ${c.bg};
                    color: ${c.text};
                    border-color: ${c.border};
                    font-weight: 600;
                    padding: 0.375rem 1.75rem 0.375rem 0.75rem;
                    width: 100%;
                    cursor: pointer;
                ">
                <option value="" selected>
                    {{ __('payments.change_status') ?? 'Change status…' }}
                </option>
                ${transitions.map(o => `
                    <option value="${o.value}">${o.label}</option>
                `).join('')}
            </select>
        `;
    }

    // Renders inline buttons. No menu, no Popper, no clipping.
    function renderActionBar(inv, p) {
        const btns = [];

        const btn = (action, icon, label, tone = 'secondary') => `
            <button type="button"
                    class="btn btn-sm btn-icon btn-light-${tone}"
                    data-drawer-action="${action}"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="${label}">
                <i class="bi ${icon} fs-5"></i>
            </button>
        `;

        if (p.can_send)      btns.push(btn('send',      'bi-envelope',         '{{ __('payments.send_invoice') }}',    'success'));
        if (p.can_pay)       btns.push(btn('pay',       'bi-cash-coin',        '{{ __('payments.record_payment') }}',  'primary'));
        if (p.can_discount)  btns.push(btn('discount',  'bi-percent',          '{{ __('payments.apply_discount') }}',  'warning'));
        if (p.can_edit_date) btns.push(btn('edit-date', 'bi-calendar-event',   '{{ __('payments.edit_issue_date') }}', 'info'));
        if (p.can_void)      btns.push(btn('void',      'bi-ban',              '{{ __('payments.void_invoice') }}',    'dark'));
        if (p.can_delete)    btns.push(btn('delete',    'bi-trash',            '{{ __('payments.delete_invoice') }}',  'danger'));

        if (p.can_download) {
            btns.push(`
                <a href="/invoices/${inv.id}/pdf"
                target="_blank"
                class="btn btn-sm btn-icon btn-light-secondary"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                title="{{ __('payments.download_pdf') }}">
                    <i class="bi bi-file-pdf fs-5"></i>
                </a>
            `);
        }

        if (!btns.length) return '';

        return `<div class="d-flex align-items-center gap-1">${btns.join('')}</div>`;
    }

    // ── bind handlers in the footer ───────────────────────────
    function bindActionCluster(inv) {
        const actions = document.getElementById('invoiceDrawerActions');
        if (!actions) return;

        // Every inline action button dispatches the same way
        actions.querySelectorAll('[data-drawer-action]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const action = el.getAttribute('data-drawer-action');
                openActionPanel(action);
            });
        });

        // Tooltips (Metronic/Bootstrap 5)
        actions.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            bootstrap.Tooltip.getOrCreateInstance(el, { container: 'body' });
        });

        // Status select — unchanged
        const sel = actions.querySelector('[data-drawer-status-select]');
        if (sel) {
            sel.addEventListener('change', async () => {
                const newStatus  = sel.value;
                const currentStatus = sel.getAttribute('data-current-status') || inv.status;

                // Reset the visual selection back to the placeholder immediately
                // so the select is always re-openable, even if this request fails.
                sel.value = '';

                if (!newStatus) return; // user opened the menu and dismissed it

                // paid / partially_paid — either is "record a payment"
                if (newStatus === 'paid' || newStatus === 'partially_paid') {
                    return openPayPanel(inv.id);
                }

                // Selecting the same status the invoice already has = no-op
                // (a "partially_paid → partially_paid" transition has no meaning
                //  other than "add another payment", which we routed above)
                if (newStatus === currentStatus) {
                    return;
                }

                sel.disabled = true;
                try {
                    const json = await req('POST', `/invoices/${inv.id}/status`, { status: newStatus });
                    window.toastr?.success?.(json.message);
                    window.InvoiceList?.reload?.();
                    await reload(inv.id);
                } catch (err) {
                    window.toastr?.error?.(err.message);
                } finally {
                    sel.disabled = false;
                }
            });
        }
    }

    // ── panels ────────────────────────────────────────────────
    function openActionPanel(action) {
        if (!currentPayload) return;
        const inv = currentPayload.invoice;
        switch (action) {
            case 'send':       return openSendPanel(inv.id);
            case 'pay':        return openPayPanel(inv.id);
            case 'void':       return openVoidPanel(inv.id);
            case 'discount':   return openDiscountPanel(inv.id);
            case 'edit-date':  return openIssueDatePanel(inv.id);
            case 'delete':     return openDeletePanel(inv.id);
        }
    }

    function panelShell(title, bodyHtml, footerHtml) {
        const body = document.getElementById('invoiceDrawerContent');
        body.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold text-gray-800 mb-0">${title}</h5>
                <button type="button" class="btn btn-sm btn-light" data-panel-back>
                    <i class="bi bi-arrow-left me-1"></i>{{ __('auth._back') ?? 'Back' }}
                </button>
            </div>
            <div data-panel-body>${bodyHtml}</div>
            <div class="d-flex justify-content-end gap-2 mt-5" data-panel-footer>${footerHtml}</div>
        `;
        // Hide the action cluster while a form is open
        document.getElementById('invoiceDrawerFooter')?.classList.add('d-none');
                body.querySelectorAll('[data-panel-back]').forEach(btn => {
            btn.addEventListener('click', () => render(currentPayload));
        });
        return body;
    }

    // ── SEND ─────────────────────────────────────────────────
    function openSendPanel(id) {
        const inv = currentPayload.invoice;
        const body = panelShell(
            '{{ __('payments.send_invoice') }}',
            `
            <div class="mb-4">
                <label class="form-label fw-semibold">{{ __('payments.send_via') }}</label>
                <div class="d-flex flex-wrap gap-4">
                    ${[
                        { v: 'email',    i: 'bi-envelope-at',       l: '{{ __('payments.email') }}' },
                        { v: 'whatsapp', i: 'bi-whatsapp',          l: '{{ __('payments.whatsapp') }}' },
                        { v: 'sms',      i: 'bi-chat-dots',         l: '{{ __('payments.sms') }}' },
                        { v: 'print',    i: 'bi-file-earmark-arrow-down', l: '{{ __('payments.download_pdf_send_manually') }}' },
                    ].map(o => `
                        <div class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="radio" name="send_channel" value="${o.v}" id="drawer-ch-${o.v}" ${o.v === 'email' ? 'checked' : ''}>
                            <label class="form-check-label fw-semibold" for="drawer-ch-${o.v}">
                                <i class="bi ${o.i} me-1"></i>${o.l}
                            </label>
                        </div>
                    `).join('')}
                </div>
            </div>

            <div class="mb-3" data-channel-email>
                <label class="form-label fw-semibold required">{{ __('payments.customer_email') }}</label>
                <input type="email" name="email" class="form-control" value="${esc(inv.billing.email || '')}" placeholder="{{ __('payments.enter_customer_email') }}">
                <div class="text-danger fs-8 d-none" data-email-error></div>
            </div>

            <div class="mb-3 d-none" data-channel-phone>
                <label class="form-label fw-semibold required">{{ __('payments.customer_phone') }}</label>
                <input type="tel" name="phone" class="form-control" value="${esc(inv.billing.phone || '')}" placeholder="{{ __('payments.enter_customer_phone') }}">
                <div class="text-danger fs-8 d-none" data-phone-error></div>
            </div>

            <div class="alert alert-info mb-0 d-none" data-channel-print>
                <i class="bi bi-info-circle me-1"></i>{{ __('payments.download_note_will_mark_sent') }}
            </div>
            `,
            `<button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('payments.cancel') }}</button>
             <button type="button" class="btn btn-success" data-send-submit>
                 <span class="indicator-label"><i class="bi bi-send me-1"></i>{{ __('payments.confirm_send') }}</span>
                 <span class="indicator-progress d-none">{{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
             </button>`
        );

        const updateChannelVisibility = () => {
            const ch = body.querySelector('input[name="send_channel"]:checked')?.value || 'email';
            body.querySelector('[data-channel-email]')?.classList.toggle('d-none', ch !== 'email');
            body.querySelector('[data-channel-phone]')?.classList.toggle('d-none', !['whatsapp', 'sms'].includes(ch));
            body.querySelector('[data-channel-print]')?.classList.toggle('d-none', ch !== 'print');
        };
        body.querySelectorAll('input[name="send_channel"]').forEach(r => r.addEventListener('change', updateChannelVisibility));
        updateChannelVisibility();

        body.querySelector('[data-send-submit]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            const channel = body.querySelector('input[name="send_channel"]:checked').value;
            const emailInput = body.querySelector('[name="email"]');
            const phoneInput = body.querySelector('[name="phone"]');
            const emailErr = body.querySelector('[data-email-error]');
            const phoneErr = body.querySelector('[data-phone-error]');
            emailErr?.classList.add('d-none');
            phoneErr?.classList.add('d-none');

            let payload = { channel };

            if (channel === 'email') {
                const email = (emailInput?.value || '').trim();
                if (!email) {
                    if (emailErr) { emailErr.textContent = '{{ __("payments.customer_email_required") }}'; emailErr.classList.remove('d-none'); }
                    emailInput?.focus();
                    return;
                }
                payload.email = email;
            }

            if (channel === 'whatsapp' || channel === 'sms') {
                const raw = (phoneInput?.value || '').trim();
                const v = validateE164(raw);
                if (!v.valid) {
                    if (phoneErr) { phoneErr.textContent = v.error; phoneErr.classList.remove('d-none'); }
                    phoneInput?.focus();
                    return;
                }
                payload.phone = v.formatted;
            }

            setLoading(btn, true);
            try {
                const json = await req('POST', `/invoices/${id}/send`, payload);
                window.toastr?.success?.(json.message);
                if (json.download_url) window.open(json.download_url, '_blank');
                window.InvoiceList?.reload?.();
                await reload(id);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });
    }

    // ── PAY ──────────────────────────────────────────────────
    function openPayPanel(id) {
        const inv = currentPayload.invoice;
        const body = panelShell(
            '{{ __('payments.record_payment') }}',
            `
            <div class="row g-3 mb-4">
                <div class="col-6"><div class="bg-light-primary rounded-3 p-3 text-center">
                    <div class="fs-7 text-muted">{{ __('payments.total_amount') }}</div>
                    <div class="fs-4 fw-bold text-primary">${esc(inv.total_formatted)}</div>
                </div></div>
                <div class="col-6"><div class="bg-light-danger rounded-3 p-3 text-center">
                    <div class="fs-7 text-muted">{{ __('payments.balance_due') }}</div>
                    <div class="fs-4 fw-bold text-danger">${esc(inv.balance_due_formatted)}</div>
                </div></div>
            </div>
            <div class="mb-3">
                <label class="form-label required">{{ __('payments.amount_received') }}</label>
                <input type="number" step="0.01" min="0.01" max="${inv.balance_due}" name="amount" class="form-control"
                       value="${inv.balance_due}" required>
                <div class="form-text">{{ __('payments.leave_full_amount_or_edit_for_partial') }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label required">{{ __('payments.received_into') }}</label>
                <select name="payment_method_id" class="form-select">
                    <option value="">{{ __('payments.select_payment_method') }}</option>
                    @foreach(getUniquePaymentTypes() as $type)
                        <optgroup label="{{ ucfirst(str_replace('_', ' ', $type)) }}">
                            @foreach(getPaymentMethodsByType($type) as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}@if($method->account_number) — {{ $method->account_number }}@endif</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <div class="text-danger fs-8 d-none" data-method-error></div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('payments.transaction_reference') }} <span class="text-muted fw-normal">({{ __('payments.optional') }})</span></label>
                <input type="text" name="transaction_id" class="form-control">
            </div>
            <div class="mb-2">
                <label class="form-label">{{ __('payments.notes') }} <span class="text-muted fw-normal">({{ __('payments.optional') }})</span></label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
            `,
            `<button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('payments.cancel') }}</button>
             <button type="button" class="btn btn-success" data-pay-submit>
                 <span class="indicator-label"><i class="bi bi-check-circle me-1"></i>{{ __('payments.confirm_payment') }}</span>
                 <span class="indicator-progress d-none">{{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
             </button>`
        );

        body.querySelector('[data-pay-submit]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            const methodSel = body.querySelector('[name="payment_method_id"]');
            const methodErr = body.querySelector('[data-method-error]');
            methodErr?.classList.add('d-none');

            if (!methodSel?.value) {
                if (methodErr) { methodErr.textContent = '{{ __("payments.select_payment_method") }}'; methodErr.classList.remove('d-none'); }
                return;
            }

            const payload = {
                amount: body.querySelector('[name="amount"]').value,
                payment_method_id: methodSel.value,
                transaction_id: body.querySelector('[name="transaction_id"]').value || null,
                notes: body.querySelector('[name="notes"]').value || null,
            };

            setLoading(btn, true);
            try {
                const json = await req('POST', `/invoices/${id}/record-payment`, payload);
                window.toastr?.success?.(json.message);
                window.InvoiceList?.reload?.();
                await reload(id);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });
    }

    // ── VOID ─────────────────────────────────────────────────
    function openVoidPanel(id) {
        const body = panelShell(
            '{{ __('payments.void_invoice') }}',
            `
            <div class="alert alert-warning d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-triangle fs-3 me-3"></i>
                <div class="fs-7">{{ __('payments.void_warning') }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('payments.void_reason') }}</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="{{ __('payments.enter_void_reason') }}"></textarea>
            </div>
            `,
            `<button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('payments.cancel') }}</button>
             <button type="button" class="btn btn-dark" data-void-submit>
                 <span class="indicator-label"><i class="bi bi-ban me-1"></i>{{ __('payments.confirm_void') }}</span>
                 <span class="indicator-progress d-none">{{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
             </button>`
        );
        body.querySelector('[data-void-submit]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            setLoading(btn, true);
            try {
                const json = await req('POST', `/invoices/${id}/void`, {
                    reason: body.querySelector('[name="reason"]').value || null,
                });
                window.toastr?.success?.(json.message);
                window.InvoiceList?.reload?.();
                await reload(id);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });
    }

    // ── DISCOUNT ─────────────────────────────────────────────
    function openDiscountPanel(id) {
        const inv = currentPayload.invoice;
        const subtotal = Number(inv.subtotal || 0);
        const tax      = Number(inv.tax_total || 0);

        const body = panelShell(
            '{{ __('payments.apply_discount') }}',
            `
            <div class="row g-3 mb-4">
                <div class="col-4"><div class="bg-light-primary rounded-3 p-3 text-center">
                    <div class="fs-7 text-muted">{{ __('payments.subtotal') }}</div>
                    <div class="fs-5 fw-bold text-primary">${esc(inv.subtotal_formatted)}</div>
                </div></div>
                <div class="col-4"><div class="bg-light-info rounded-3 p-3 text-center">
                    <div class="fs-7 text-muted">{{ __('payments.tax') }}</div>
                    <div class="fs-5 fw-bold text-info">${esc(inv.tax_formatted)}</div>
                </div></div>
                <div class="col-4"><div class="bg-light-success rounded-3 p-3 text-center">
                    <div class="fs-7 text-muted">{{ __('payments.total') }}</div>
                    <div class="fs-5 fw-bold text-success">${esc(inv.total_formatted)}</div>
                </div></div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold required">{{ __('payments.discount_amount') }}</label>
                <input type="number" step="0.01" min="0" name="discount_amount"
                       class="form-control" value="${inv.discount_total || 0}" data-discount-input>
                <div class="form-text">
                    {{ __('payments.discount_amount_hint') }}
                    <span class="text-muted">({{ __('payments.max_discount') }}: ${esc(inv.subtotal_formatted)})</span>
                </div>
            </div>

            <div class="bg-light p-3 rounded-3 mb-3">
                <div class="row g-2">
                    <div class="col-4">
                        <span class="text-muted fs-7">{{ __('payments.subtotal') }}</span>
                        <div class="fw-bold">${esc(inv.subtotal_formatted)}</div>
                    </div>
                    <div class="col-4">
                        <span class="text-muted fs-7">{{ __('payments.discount') }}</span>
                        <div class="fw-bold text-danger">-<span data-preview-discount>0.00</span></div>
                    </div>
                    <div class="col-4">
                        <span class="text-muted fs-7">{{ __('payments.new_total') }}</span>
                        <div class="fw-bold text-success fs-5"><span data-preview-total>${esc(inv.total_formatted)}</span></div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('payments.discount_notes') }} <span class="text-muted fw-normal">({{ __('payments.optional') }})</span></label>
                <textarea name="discount_notes" class="form-control" rows="2" placeholder="{{ __('payments.enter_discount_reason') }}"></textarea>
            </div>

            ${inv.discount_total > 0 ? `
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle fs-3 me-3 text-warning"></i>
                    <div class="fs-7">
                        <strong>{{ __('payments.discount_already_applied') }}</strong>
                        <span class="d-block text-muted">{{ __('payments.applying_new_discount_will_replace') }}</span>
                    </div>
                </div>
            ` : ''}
            `,
            `
            ${inv.discount_total > 0
                ? `<button type="button" class="btn btn-danger me-auto" data-discount-remove>
                       <i class="bi bi-x-circle me-1"></i>{{ __('payments.remove_discount') }}
                   </button>`
                : ''}
            <button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('payments.cancel') }}</button>
            <button type="button" class="btn btn-warning" data-discount-submit>
                <span class="indicator-label"><i class="bi bi-check-circle me-1"></i>{{ __('payments.apply_discount') }}</span>
                <span class="indicator-progress d-none">{{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
            </button>
            `
        );

        // Live preview
        const input = body.querySelector('[data-discount-input]');
        const previewDiscount = body.querySelector('[data-preview-discount]');
        const previewTotal    = body.querySelector('[data-preview-total]');
        const fmtMoney = (n) => '{{ currency_symbol() }}' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const updatePreview = () => {
            let d = parseFloat(input.value) || 0;
            if (d > subtotal) {
                d = subtotal;
                input.value = subtotal.toFixed(2);
            }
            const newTotal = subtotal - d + tax;
            previewDiscount.textContent = d.toFixed(2);
            previewTotal.textContent = fmtMoney(newTotal);
        };
        input?.addEventListener('input', updatePreview);
        updatePreview();

        body.querySelector('[data-discount-submit]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            const d = parseFloat(input.value) || 0;
            if (d <= 0) {
                window.toastr?.warning?.('{{ __("payments.discount_cannot_be_zero") }}');
                return;
            }
            if (d > subtotal) {
                window.toastr?.error?.('{{ __("payments.discount_exceeds_subtotal") }}');
                return;
            }
            setLoading(btn, true);
            try {
                const json = await req('POST', `/invoices/${id}/apply-discount`, {
                    discount_amount: d,
                    discount_notes:  body.querySelector('[name="discount_notes"]').value || null,
                });
                window.toastr?.success?.(json.message);
                window.InvoiceList?.reload?.();
                await reload(id);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });

        body.querySelector('[data-discount-remove]')?.addEventListener('click', async (ev) => {
            if (!confirm('{{ __("payments.confirm_remove_discount") }}')) return;
            const btn = ev.currentTarget;
            setLoading(btn, true);
            try {
                const json = await req('POST', `/invoices/${id}/remove-discount`, {});
                window.toastr?.success?.(json.message);
                window.InvoiceList?.reload?.();
                await reload(id);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });
    }

    // ── EDIT ISSUE DATE ──────────────────────────────────────
    function openIssueDatePanel(id) {
        const inv = currentPayload.invoice;
        const body = panelShell(
            '{{ __('payments.edit_issue_date') }}',
            `
            <div class="mb-3">
                <label class="form-label required">{{ __('payments.issue_date') }}</label>
                <input type="date" name="issue_date" class="form-control" value="${esc(inv.issue_date || '')}">
                <div class="text-danger fs-8 d-none" data-issue-error></div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ __('payments.due_date') }}</label>
                <input type="date" name="due_date" class="form-control" value="${esc(inv.due_date || '')}">
                <div class="text-danger fs-8 d-none" data-due-error></div>
            </div>
            `,
            `<button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('payments.cancel') }}</button>
             <button type="button" class="btn btn-primary" data-date-submit>
                 <span class="indicator-label">{{ __('payments._confirm_update') }}</span>
                 <span class="indicator-progress d-none">{{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
             </button>`
        );

        body.querySelector('[data-date-submit]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            const issue = body.querySelector('[name="issue_date"]').value;
            const due   = body.querySelector('[name="due_date"]').value;
            const issueErr = body.querySelector('[data-issue-error]');
            const dueErr   = body.querySelector('[data-due-error]');
            issueErr?.classList.add('d-none'); dueErr?.classList.add('d-none');

            if (!issue) {
                if (issueErr) { issueErr.textContent = '{{ __("payments.issue_date_required") }}'; issueErr.classList.remove('d-none'); }
                return;
            }
            if (due && due < issue) {
                if (dueErr) { dueErr.textContent = '{{ __("payments.due_date_after_issue") }}'; dueErr.classList.remove('d-none'); }
                return;
            }

            setLoading(btn, true);
            try {
                const json = await req('PUT', `/invoices/${id}/issue-date`, {
                    issue_date: issue,
                    due_date:   due || null,
                });
                window.toastr?.success?.(json.message);
                window.InvoiceList?.reload?.();
                await reload(id);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });
    }

    // ── DELETE ───────────────────────────────────────────────
    function openDeletePanel(id) {
        const body = panelShell(
            '{{ __('auth.confirm_deletion') }}',
            `<div class="alert alert-danger">
                <p>{{ __('auth.are_you_sure') }}</p>
                <p class="mb-0">{{ __('auth.action_cannot') }}</p>
            </div>`,
            `<button type="button" class="btn btn-light" data-panel-back>{{ __('auth._discard') }}</button>
             <button type="button" class="btn btn-danger" data-delete-submit>
                 <span class="indicator-label"><i class="bi bi-trash me-1"></i>{{ __('auth._confirm') }}</span>
                 <span class="indicator-progress d-none">{{ __('auth.please_wait') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
             </button>`
        );
        body.querySelector('[data-delete-submit]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            setLoading(btn, true);
            try {
                const json = await req('DELETE', `/invoices/${id}`, null);
                window.toastr?.success?.(json.message);
                window.InvoiceList?.reload?.();
                close();
            } catch (err) {
                window.toastr?.error?.(err.message);
                setLoading(btn, false);
            }
        });
    }

    // ── helpers ──────────────────────────────────────────────
    function setLoading(btn, busy) {
        if (!btn) return;

        // The button must anchor the absolutely-positioned spinner
        if (getComputedStyle(btn).position === 'static') {
            btn.style.position = 'relative';
        }

        const label    = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');

        // One-time style on the progress span so it floats over the button
        // without changing the button's box.
        if (progress && !progress.dataset.overlayInit) {
            Object.assign(progress.style, {
                position: 'absolute',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                alignItems: 'center',
                gap: '0.5rem',
                whiteSpace: 'nowrap',
                pointerEvents: 'none',
            });
            progress.dataset.overlayInit = '1';
        }

        btn.disabled = busy;

        // Keep the label occupying its space — only hide its pixels.
        // This is what stops the button from shrinking during loading.
        if (label) label.style.visibility = busy ? 'hidden' : '';

        // Show/hide the spinner via the `d-none` class so its !important
        // rule always wins when idle, and the inline display wins when busy.
        if (progress) {
            progress.classList.toggle('d-none', !busy);
            progress.style.display = busy ? 'inline-flex' : 'none';
        }
    }

    function validateE164(raw) {
        if (!raw || !raw.trim()) {
            return { valid: false, error: '{{ __("payments.phone_required") }}' };
        }
        let cleaned = raw.trim().replace(/[\s\-\.\(\)]/g, '');
        if (cleaned.startsWith('00')) cleaned = '+' + cleaned.slice(2);
        if (!cleaned.startsWith('+')) cleaned = '+' + cleaned;
        if (!/^\+[1-9]\d{7,14}$/.test(cleaned)) {
            return { valid: false, error: '{{ __("payments.invalid_phone_format") }}' };
        }
        return { valid: true, formatted: cleaned };
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    // ── public API ───────────────────────────────────────────
    window.InvoiceDrawer = {
        open, reload, close,
        openSendPanel, openPayPanel, openVoidPanel,
        openDiscountPanel, openIssueDatePanel, openDeletePanel,
    };
    return window.InvoiceDrawer;
})();
</script>