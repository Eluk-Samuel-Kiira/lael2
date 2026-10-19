<script>
window.OrderDrawer = (function () {
    'use strict';

    const URL_DETAILS = '{{ route("orders.details", ["id" => "__ID__"]) }}';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    let currentPayload = null;
    let currentId = null;
    let bs = null;

    function getBs() {
        const el = document.getElementById('orderDrawer');
        if (!el) return null;
        bs = bs || bootstrap.Offcanvas.getOrCreateInstance(el);
        return bs;
    }

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
        if (!res.ok || json.success === false) throw new Error(json.message || 'Request failed');
        return json;
    }

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
            console.error('[order-drawer] load failed:', err);
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
            console.error('[order-drawer] reload failed:', err);
        }
    }

    function close() { getBs()?.hide(); }

    function showSkeleton() {
        document.getElementById('orderDrawerSkeleton')?.classList.remove('d-none');
        const c = document.getElementById('orderDrawerContent');
        if (c) { c.classList.add('d-none'); c.innerHTML = ''; }
        document.getElementById('orderDrawerFooter')?.classList.add('d-none');
        const num = document.getElementById('orderDrawerNumber');
        if (num) num.textContent = '';
    }

    function renderError(msg) {
        document.getElementById('orderDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('orderDrawerContent');
        if (!body) return;
        body.classList.remove('d-none');
        body.innerHTML = `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>${esc(msg)}</div>`;
    }

    function render(payload) {
        const ord = payload.order;
        currentId = ord.id;
        const num = document.getElementById('orderDrawerNumber');
        if (num) num.textContent = ord.order_number;

        document.getElementById('orderDrawerSkeleton')?.classList.add('d-none');
        const body = document.getElementById('orderDrawerContent');
        body.classList.remove('d-none');
        body.innerHTML = renderView(ord, payload.items || [], payload.payments || []);

        const footer = document.getElementById('orderDrawerFooter');
        const actions = document.getElementById('orderDrawerActions');
        actions.innerHTML = renderActionBar(ord);
        footer.classList.remove('d-none');
        bindActions(ord);
    }

    function renderView(ord, items, payments) {
        return `
            <div class="d-flex align-items-start gap-4 mb-6">
                <div class="symbol symbol-50px">
                    <div class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                        ${esc(ord.customer_initial || '?')}
                    </div>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <h5 class="fw-bold text-gray-800 mb-1">${esc(ord.customer_name || '—')}</h5>
                    <div class="d-flex flex-wrap gap-3 fs-7 text-muted">
                        ${ord.customer_email ? `<span><i class="bi bi-envelope me-1"></i>${esc(ord.customer_email)}</span>` : ''}
                        ${ord.customer_phone ? `<span><i class="bi bi-phone me-1"></i>${esc(ord.customer_phone)}</span>` : ''}
                    </div>
                </div>
            </div>

            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="row g-4">
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('passwords.order_number') }}</div>
                        <div class="fw-bold text-gray-800 fs-7">${esc(ord.order_number)}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('auth._status') }}</div>
                        <span class="badge badge-light-${esc(ord.status_color)}">${esc(ord.status_label)}</span>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('passwords.location') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(ord.location_name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('passwords.department') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(ord.department_name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('auth._creater') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(ord.creator_name || '—')}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted fw-semibold fs-8 text-uppercase mb-1">{{ __('passwords._source') }}</div>
                        <div class="fw-semibold text-gray-800 fs-7">${esc(ord.source_label || '—')}</div>
                    </div>
                </div>
            </div>

            ${items.length > 0 ? `
            <div class="mb-6">
                <h6 class="fw-bold text-gray-800 mb-3">
                    <i class="bi bi-list-ul me-2 text-primary"></i>{{ __('passwords.order_items') }}
                    <span class="badge badge-light-primary ms-1">${items.length}</span>
                </h6>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle fs-7">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-3">{{ __('passwords.item_name') }}</th>
                                <th class="text-center">{{ __('passwords.quantity') }}</th>
                                <th class="text-end">{{ __('passwords.unit_price') }}</th>
                                <th class="text-center">{{ __('payments.refund_status') ?? 'Refund' }}</th>
                                <th class="pe-3 text-end">{{ __('passwords.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(it => `
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-gray-800">${esc(it.item_name)}</div>
                                        ${it.sku ? `<div class="text-muted fs-8"><code>${esc(it.sku)}</code></div>` : ''}
                                    </td>
                                    <td class="text-center">${esc(it.quantity)}</td>
                                    <td class="text-end">${esc(it.unit_price_formatted)}</td>
                                    <td class="text-center">${renderItemRefundBadge(it)}</td>
                                    <td class="pe-3 text-end fw-bold">${esc(it.total_formatted)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            ` : ''}

            ${payments.length > 0 ? `
            <div class="mb-6">
                <h6 class="fw-bold text-gray-800 mb-3">
                    <i class="bi bi-cash-coin me-2 text-primary"></i>{{ __('payments.payment_details') }}
                    <span class="badge badge-light-primary ms-1">${payments.length}</span>
                </h6>
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle fs-7">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-3">{{ __('payments.date') }}</th>
                                <th class="text-end">{{ __('payments.amount') }}</th>
                                <th>{{ __('payments.method') }}</th>
                                <th class="text-center">{{ __('auth._status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${payments.map(p => `
                                <tr>
                                    <td class="ps-3 text-gray-800">${esc(p.payment_date)}</td>
                                    <td class="text-end fw-bold text-success">${esc(p.amount_formatted)}</td>
                                    <td>${esc(p.method_name || '—')}</td>
                                    <td class="text-center"><span class="badge badge-light-${esc(p.status_color)}">${esc(p.status_label)}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            ` : ''}

            <div class="bg-light-primary rounded-3 p-4 mb-6">
                <div class="d-flex justify-content-between fs-7 mb-2">
                    <span class="text-muted">{{ __('passwords.subtotal') }}</span>
                    <span class="fw-semibold text-gray-800">${esc(ord.subtotal_formatted)}</span>
                </div>
                ${ord.discount_total > 0 ? `
                    <div class="d-flex justify-content-between fs-7 mb-2">
                        <span class="text-muted">{{ __('passwords.discount') }}</span>
                        <span class="fw-semibold text-danger">-${esc(ord.discount_formatted)}</span>
                    </div>
                ` : ''}
                ${ord.tax_total > 0 ? `
                    <div class="d-flex justify-content-between fs-7 mb-2">
                        <span class="text-muted">{{ __('passwords.tax') }}</span>
                        <span class="fw-semibold text-warning">${esc(ord.tax_formatted)}</span>
                    </div>
                ` : ''}
                <div class="separator separator-dashed my-2"></div>
                <div class="d-flex justify-content-between">
                    <span class="fw-bold text-primary fs-6">{{ __('passwords.total') }}</span>
                    <span class="fw-bold text-primary fs-5">${esc(ord.total_formatted)}</span>
                </div>
                ${ord.paid_amount > 0 ? `
                    <div class="separator separator-dashed my-2"></div>
                    <div class="d-flex justify-content-between fs-7 mb-2">
                        <span class="text-muted">{{ __('passwords.paid') }}</span>
                        <span class="fw-semibold text-success">${esc(ord.paid_formatted)}</span>
                    </div>
                    <div class="d-flex justify-content-between fs-7">
                        <span class="text-muted">{{ __('passwords.balance_due') }}</span>
                        <span class="fw-semibold ${ord.balance_due > 0 ? 'text-danger' : 'text-success'}">${esc(ord.balance_formatted)}</span>
                    </div>
                ` : ''}
            </div>

            ${ord.notes ? `
                <div class="bg-light rounded-3 p-4 mb-6">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-gray-300 border-dashed">
                        <i class="bi bi-sticky fs-5 text-warning"></i>
                        <h6 class="fw-bold text-gray-800 mb-0">{{ __('payments.notes') }}</h6>
                    </div>
                    <div class="text-gray-700 fs-7" style="white-space: pre-line; line-height: 1.7;">${esc(ord.notes)}</div>
                </div>
            ` : ''}

            ${renderRefundSummary(ord, items)}
            ${renderNotesBlock(ord)}
        `;
    }

    function renderRefundSummary(ord, items) {
        const refundedTotal  = Number(ord.refunded_total || 0);
        const refundableCap  = Number(ord.refundable_cap || 0);
        const hasAnyRefund   = refundedTotal > 0;
        const anyItemRefunded = items.some(i => Number(i.refunded_quantity || 0) > 0);

        if (!hasAnyRefund && !anyItemRefunded) return '';

        const fullyRefundedCount = items.filter(i => Number(i.refundable_quantity ?? i.quantity) <= 0.0001).length;
        const partiallyRefundedCount = items.filter(i => {
            const r = Number(i.refunded_quantity || 0);
            const rem = Number(i.refundable_quantity ?? (Number(i.quantity) - r));
            return r > 0 && rem > 0.0001;
        }).length;

        const SYM = '{{ currency_symbol() }}';
        const fmt = (n) => SYM + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        return `
            <div class="bg-light-warning rounded-3 p-4 mb-6">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-warning border-dashed">
                    <i class="bi bi-arrow-return-left fs-5 text-warning"></i>
                    <h6 class="fw-bold text-gray-800 mb-0">{{ __('payments.refund_summary') ?? 'Refund summary' }}</h6>
                </div>
                <div class="d-flex justify-content-between fs-7 mb-2">
                    <span class="text-muted">{{ __('payments.already_refunded') ?? 'Already refunded' }}</span>
                    <span class="fw-bold text-warning">${fmt(refundedTotal)}</span>
                </div>
                <div class="d-flex justify-content-between fs-7 mb-2">
                    <span class="text-muted">{{ __('payments.refundable_cap') ?? 'Still refundable' }}</span>
                    <span class="fw-bold text-gray-800">${fmt(refundableCap)}</span>
                </div>
                <div class="separator separator-dashed my-2"></div>
                <div class="d-flex justify-content-between fs-7">
                    <span class="text-muted">{{ __('payments.items_fully_refunded') ?? 'Items fully refunded' }}</span>
                    <span class="fw-semibold text-gray-700">${fullyRefundedCount} / ${items.length}</span>
                </div>
                ${partiallyRefundedCount > 0 ? `
                    <div class="d-flex justify-content-between fs-7 mt-1">
                        <span class="text-muted">{{ __('payments.items_partially_refunded') ?? 'Items partially refunded' }}</span>
                        <span class="fw-semibold text-gray-700">${partiallyRefundedCount}</span>
                    </div>
                ` : ''}
            </div>
        `;
    }

    function renderItemRefundBadge(it) {
        const qty       = Number(it.quantity || 0);
        const refunded  = Number(it.refunded_quantity || 0);
        const refundable= Number(it.refundable_quantity ?? (qty - refunded));

        if (refunded <= 0) {
            return `<span class="text-muted fs-8">—</span>`;
        }

        // Fully refunded
        if (refundable <= 0.0001) {
            return `<span class="badge badge-light-danger fs-8 fw-bold">
                        <i class="bi bi-x-circle me-1"></i>{{ __('payments.fully_refunded') ?? 'Fully refunded' }}
                        <span class="ms-1">(${esc(it.refunded_amount_formatted || '')})</span>
                    </span>`;
        }

        // Partially refunded — show what's left
        return `<span class="badge badge-light-warning fs-8 fw-bold"
                    title="${refunded} of ${qty} refunded">
                    <i class="bi bi-arrow-return-left me-1"></i>${refunded} / ${qty}
                </span>`;
    }

    function renderNotesBlock(ord) {
        if (!ord.notes) return '';

        // Split on newlines so appendNote()-style entries render as separate paragraphs
        const lines = String(ord.notes).split(/\r?\n/).map(l => l.trim()).filter(Boolean);
        if (!lines.length) return '';

        return `
            <div class="bg-light rounded-3 p-4 mb-6">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-gray-300 border-dashed">
                    <i class="bi bi-sticky fs-5 text-warning"></i>
                    <h6 class="fw-bold text-gray-800 mb-0">{{ __('payments.notes') }}</h6>
                </div>
                <div>
                    ${lines.map(l => `<p class="mb-3 text-gray-700 fs-7" style="line-height: 1.7;">${esc(l)}</p>`).join('')}
                </div>
            </div>
        `;
    }

    function renderActionBar(ord) {
        const p = ord.permissions || {};
        const btns = [];

        const iconBtn = (action, icon, label, tone = 'secondary') => `
            <button type="button"
                    class="btn btn-sm btn-icon btn-light-${tone}"
                    data-order-action="${action}"
                    data-bs-toggle="tooltip"
                    data-bs-placement="top"
                    title="${label}">
                <i class="ki-duotone ${icon} fs-4">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
            </button>
        `;

        if (p.can_complete) btns.push(iconBtn('complete', 'ki-dollar',       '{{ __('pagination._complete') }}', 'primary'));
        if (p.can_send)     btns.push(iconBtn('send',     'ki-sms',          '{{ __('passwords.send_receipt') }}', 'success'));
        if (p.can_refund)   btns.push(iconBtn('refund',   'ki-arrow-circle-left', '{{ __('payments.refund') }}', 'success'));
        if (p.can_cancel)   btns.push(iconBtn('cancel',   'ki-cross',        '{{ __('passwords.cancel') }}', 'danger'));
        if (p.can_print)    btns.push(iconBtn('print',    'ki-printer',      '{{ __('passwords._print') }}', 'secondary'));

        if (!btns.length) return '';
        return `<div class="d-flex align-items-center gap-1">${btns.join('')}</div>`;
    }

    function bindActions(ord) {
        const actions = document.getElementById('orderDrawerActions');
        if (!actions) return;

        actions.querySelectorAll('[data-order-action]').forEach(el => {
            el.addEventListener('click', () => {
                const action = el.getAttribute('data-order-action');
                switch (action) {
                    case 'complete': return completePayment(ord);
                    case 'send':     return openSendPanel(ord);
                    case 'refund':   return openRefundPanel(ord);
                    case 'cancel':   return openCancelPanel(ord);
                    case 'print':    return printOrder(ord);
                }
            });
        });

        actions.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            bootstrap.Tooltip.getOrCreateInstance(el, { container: 'body' });
        });
    }

    function completePayment(ord) {
        if (!currentPayload) return;

        // Build cartData in the SAME shape openCompletePayment_{id}() produces
        const cartData = {
            // ── identifiers ─────────────────────────────────────────
            order_id:      ord.id,
            id:            ord.id,
            ref:           ord.order_number,
            order_number:  ord.order_number,
            source:        ord.source,

            // ── customer ────────────────────────────────────────────
            customer_name: ord.customer_name || '{{ __("pagination.walk_in_customer") }}',
            customer_id:   ord.customer_id ?? null,   // ← was null; make sure ord.customer_id is in details()

            // ── financials ──────────────────────────────────────────
            subtotal: ord.subtotal,
            discount: ord.discount_total,
            tax:      ord.tax_total,
            total:    ord.total,

            // ── items (for receipt) ─────────────────────────────────
            items: (currentPayload.items || []).map(it => ({
                variant_id:         it.variant_id ?? it.id,
                product_variant_id: it.variant_id ?? it.id,
                product_id:         it.product_id ?? null,
                name:               it.item_name,
                product_name:       it.item_name,
                sku:                it.sku || '',
                quantity:           it.quantity,
                price:              it.unit_price,
                unit_price:         it.unit_price,
                total:              it.total_price,
                subtotal:           it.total_price,
                note:               it.note || null,
            })),

            // ── flag so processSplitPayments knows this is a
            //    "complete existing order" call, not a fresh POS sale ─
            completing_existing_order: true,
        };

        if (typeof window.openPaymentModal === 'function') {
            window.openPaymentModal(cartData);
        } else {
            console.error('[OrderDrawer] openPaymentModal is not defined. ' +
                'Make sure partials.payment-scripts is included on /orders.');
            window.toastr?.error?.('Payment system not loaded. Please refresh.');
        }
    }

    // ── SEND RECEIPT ───────────────────────────────────────────
    function openSendPanel(ord) {
        const body = panelShell(
            '{{ __('passwords.send_receipt') }}',
            `
            <div class="mb-4">
                <label class="form-label fw-semibold">{{ __('payments.send_via') }}</label>
                <div class="d-flex gap-4">
                    <div class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input" type="radio" name="send_channel" value="email" id="ord-ch-email" checked>
                        <label class="form-check-label fw-semibold" for="ord-ch-email"><i class="bi bi-envelope-at me-1"></i>{{ __('payments.email') }}</label>
                    </div>
                    <div class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input" type="radio" name="send_channel" value="whatsapp" id="ord-ch-wa">
                        <label class="form-check-label fw-semibold" for="ord-ch-wa"><i class="bi bi-whatsapp me-1"></i>{{ __('payments.whatsapp') }}</label>
                    </div>
                </div>
            </div>

            <div class="mb-3" data-channel-email>
                <label class="form-label fw-semibold required">{{ __('payments.customer_email') }}</label>
                <input type="email" name="email" class="form-control" value="${esc(ord.customer_email || '')}">
                <div class="text-danger fs-8 d-none" data-email-error></div>
            </div>

            <div class="mb-3 d-none" data-channel-phone>
                <label class="form-label fw-semibold required">{{ __('payments.customer_phone') }}</label>
                <input type="tel" name="phone" class="form-control" value="${esc(ord.customer_phone || '')}">
                <div class="text-danger fs-8 d-none" data-phone-error></div>
            </div>
            `,
            `<button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('payments.cancel') }}</button>
             <button type="button" class="btn btn-success" data-send-submit>
                 <span class="indicator-label"><i class="bi bi-send me-1"></i>{{ __('payments.confirm_send') }}</span>
                 <span class="indicator-progress d-none">{{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
             </button>`
        );

        const updateVis = () => {
            const ch = body.querySelector('input[name="send_channel"]:checked')?.value || 'email';
            body.querySelector('[data-channel-email]')?.classList.toggle('d-none', ch !== 'email');
            body.querySelector('[data-channel-phone]')?.classList.toggle('d-none', ch !== 'whatsapp');
        };
        body.querySelectorAll('input[name="send_channel"]').forEach(r => r.addEventListener('change', updateVis));
        updateVis();

        body.querySelector('[data-send-submit]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            const channel = body.querySelector('input[name="send_channel"]:checked').value;
            const emailInput = body.querySelector('[name="email"]');
            const phoneInput = body.querySelector('[name="phone"]');
            const emailErr = body.querySelector('[data-email-error]');
            const phoneErr = body.querySelector('[data-phone-error]');
            emailErr?.classList.add('d-none');
            phoneErr?.classList.add('d-none');

            const payload = { channel };

            if (channel === 'email') {
                const email = (emailInput?.value || '').trim();
                if (!email) {
                    if (emailErr) { emailErr.textContent = '{{ __("payments.customer_email_required") }}'; emailErr.classList.remove('d-none'); }
                    return;
                }
                payload.email = email;
            }

            if (channel === 'whatsapp') {
                const raw = (phoneInput?.value || '').trim();
                const v = validateE164(raw);
                if (!v.valid) {
                    if (phoneErr) { phoneErr.textContent = v.error; phoneErr.classList.remove('d-none'); }
                    return;
                }
                payload.phone = v.formatted;
            }

            setLoading(btn, true);
            try {
                const json = await req('POST', `/orders/${ord.id}/send`, payload);
                window.toastr?.success?.(json.message);
                render(currentPayload);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });
    }

    // ── REFUND ─────────────────────────────────────────────────
    function openRefundPanel(ord) {
        if (!currentPayload) return;
        const items    = currentPayload.items    || [];
        const payments = currentPayload.payments || [];

        // ── Currency helpers — hoisted above all template strings ──
        const SYM = '{{ currency_symbol() }}';
        const fmtMoney = (n) => SYM + Number(n || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        // ── Per-item refund state ────────────────────────────────
        const refundQty = {};
        items.forEach(it => { refundQty[it.id] = 0; });

        const maxQtyFor = (itemId) => {
            const it = items.find(x => x.id === itemId);
            if (!it) return 0;
            // Prefer server-computed refundable_quantity; fall back to quantity
            return Math.max(0, Number(it.refundable_quantity ?? it.quantity));
        };

        const itemRows = items.length ? items.map(it => {
            const qty        = Number(it.quantity || 0);
            const refunded   = Number(it.refunded_quantity || 0);
            const refundable = Number(it.refundable_quantity ?? (qty - refunded));
            const fullyRefunded = refundable <= 0.0001;

            const refundedCell = refunded > 0
                ? `<span class="badge ${fullyRefunded ? 'badge-light-danger' : 'badge-light-warning'} fs-8 fw-bold">
                    ${refunded} / ${qty}
                </span>`
                : `<span class="text-muted fs-8">—</span>`;

            return `
                <tr data-refund-item="${it.id}"
                    data-unit-price="${it.unit_price}"
                    data-max-qty="${refundable}"
                    class="${fullyRefunded ? 'opacity-75' : ''}">
                    <td class="ps-3">
                        <div class="fw-bold text-gray-800">${esc(it.item_name)}</div>
                        ${it.sku ? `<div class="text-muted fs-8"><code>${esc(it.sku)}</code></div>` : ''}
                        ${fullyRefunded ? `<span class="badge badge-light-danger fs-9 mt-1">{{ __('payments.fully_refunded') ?? 'Fully refunded' }}</span>` : ''}
                    </td>
                    <td class="text-end text-nowrap">${esc(it.unit_price_formatted)}</td>
                    <td class="text-center">
                        <span class="badge badge-light fw-bold">${esc(it.quantity)}</span>
                    </td>
                    <td class="text-center">${refundedCell}</td>
                    <td class="text-center" style="width: 160px;">
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <button type="button"
                                    class="btn btn-sm btn-icon btn-light flex-shrink-0"
                                    data-refund-dec="${it.id}"
                                    style="width: 30px; height: 30px;"
                                    ${fullyRefunded ? 'disabled' : ''}>
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number"
                                class="form-control form-control-sm text-center fw-bold refund-qty-input flex-shrink-0"
                                data-refund-qty="${it.id}"
                                min="0"
                                max="${refundable}"
                                step="1"
                                value="0"
                                style="width: 56px; min-width: 56px;"
                                ${fullyRefunded ? 'disabled' : ''}>
                            <button type="button"
                                    class="btn btn-sm btn-icon btn-light flex-shrink-0"
                                    data-refund-inc="${it.id}"
                                    style="width: 30px; height: 30px;"
                                    ${fullyRefunded ? 'disabled' : ''}>
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </td>
                    <td class="pe-3 text-end fw-bold text-gray-800 refund-line-total" data-refund-line-total="${it.id}">
                        ${fmtMoney(0)}
                    </td>
                </tr>
            `;
        }).join('') : `<tr><td colspan="6" class="text-center text-muted py-5">{{ __('passwords.no_items') ?? 'No items' }}</td></tr>`;


        const paymentRows = payments.length ? payments.map(p => `
            <tr>
                <td class="ps-3 text-gray-700">${esc(p.method_name || '—')}</td>
                <td class="pe-3 text-end fw-bold text-success">${esc(p.amount_formatted)}</td>
            </tr>
        `).join('') : '';

        // ── Panel shell ─────────────────────────────────────────
        const body = panelShell(
            '{{ __('payments.refund') }} — ' + esc(ord.order_number),
            `
            <div class="alert alert-warning d-flex align-items-start mb-4">
                <i class="bi bi-exclamation-triangle fs-3 me-3 mt-1"></i>
                <div class="fs-7">
                    <strong>{{ __('payments.refund_warning_title') ?? 'Refund selected items.' }}</strong>
                    <div class="mt-2">
                        {{ __('payments.refund_warning_body_partial') ?? 'Choose the quantity of each item to return.' }}
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold text-gray-800 mb-0">
                        <i class="bi bi-arrow-return-left me-2 text-primary"></i>
                        {{ __('passwords.items_to_return') ?? 'Items to return' }}
                        <span class="badge badge-light-primary ms-1" data-refund-selected-count>0 / ${items.length}</span>
                    </h6>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light" data-refund-select-all>
                            {{ __('payments.select_all') ?? 'Select all' }}
                        </button>
                        <button type="button" class="btn btn-sm btn-light" data-refund-clear-all>
                            {{ __('payments.clear') ?? 'Clear' }}
                        </button>
                    </div>
                </div>
                <div class="table-responsive border rounded-3">
                    <table class="table table-row-dashed align-middle fs-7 mb-0">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th class="ps-3">{{ __('passwords.item_name') }}</th>
                                <th class="text-end">{{ __('passwords.unit_price') }}</th>
                                <th class="text-center">{{ __('passwords.ordered_qty') ?? 'Ordered' }}</th>
                                <th class="text-center">{{ __('payments.already_refunded') ?? 'Refunded' }}</th>
                                <th class="text-center">{{ __('payments.refund_qty') ?? 'Refund qty' }}</th>
                                <th class="pe-3 text-end">{{ __('payments.refund_amount') ?? 'Refund amount' }}</th>
                            </tr>
                        </thead>
                        <tbody>${itemRows}</tbody>
                    </table>
                </div>
            </div>

            <div class="bg-light rounded-3 p-4 mb-4">
                <div class="d-flex justify-content-between fs-7 mb-2">
                    <span class="text-muted">{{ __('passwords.order_total') }}</span>
                    <span class="fw-bold text-gray-800">${esc(ord.total_formatted)}</span>
                </div>
                <div class="d-flex justify-content-between fs-7 mb-2">
                    <span class="text-muted">{{ __('passwords.paid') }}</span>
                    <span class="fw-bold text-success">${esc(ord.paid_formatted)}</span>
                </div>
                ${Number(ord.refunded_total || 0) > 0 ? `
                    <div class="d-flex justify-content-between fs-7 mb-2">
                        <span class="text-muted">{{ __('payments.already_refunded') ?? 'Already refunded' }}</span>
                        <span class="fw-bold text-warning">${fmtMoney(ord.refunded_total)}</span>
                    </div>
                ` : ''}
                <div class="separator separator-dashed my-2"></div>

                <div class="d-flex justify-content-between">
                    <span class="fw-bold text-danger fs-6">{{ __('payments.refund_total') ?? 'This refund' }}</span>
                    <span class="fw-bold text-danger fs-4" data-refund-total>${fmtMoney(0)}</span>
                </div>

                <div class="d-flex justify-content-between fs-8 mt-2" data-refund-after-wrap>
                    <span class="text-muted">{{ __('payments.paid_after_refund') ?? 'Paid after this refund' }}</span>
                    <span class="fw-semibold text-gray-700" data-refund-after-paid>${esc(ord.paid_formatted)}</span>
                </div>

                <div class="d-flex justify-content-between fs-8 mt-1">
                    <span class="text-muted">{{ __('payments.refundable_cap') ?? 'Maximum refundable' }}</span>
                    <span class="fw-semibold text-gray-600" data-refund-cap>${fmtMoney(ord.refundable_cap || 0)}</span>
                </div>
            </div>

            ${paymentRows ? `
                <div class="mb-4">
                    <h6 class="fw-bold text-gray-800 mb-3">
                        <i class="bi bi-cash-coin me-2 text-warning"></i>
                        {{ __('payments.payments_on_order') ?? 'Payments on this order' }}
                        <span class="badge badge-light-warning ms-1">${payments.length}</span>
                    </h6>
                    <div class="table-responsive border rounded-3">
                        <table class="table table-row-dashed align-middle fs-7 mb-0">
                            <thead>
                                <tr class="fw-bold text-muted bg-light">
                                    <th class="ps-3">{{ __('payments.payment_method') }}</th>
                                    <th class="pe-3 text-end">{{ __('payments.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>${paymentRows}</tbody>
                        </table>
                    </div>
                </div>
            ` : ''}

            <div class="mb-2">
                <label class="form-label fw-semibold">{{ __('payments.refund_reason') ?? 'Reason for refund' }}</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="{{ __('payments.enter_refund_reason') ?? 'Explain why these items are being refunded…' }}"></textarea>
            </div>
            `,
            `<button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('payments.cancel') }}</button>
            <button type="button" class="btn btn-danger" data-refund-submit>
                <span class="indicator-label"><i class="bi bi-arrow-return-left me-1"></i>{{ __('payments.confirm_refund') ?? 'Confirm refund' }}</span>
                <span class="indicator-progress d-none">{{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
            </button>`
        );

        // ── Recalculate summary ─────────────────────────────────
        const totalRefundEl = body.querySelector('[data-refund-total]');
        const afterPaidEl   = body.querySelector('[data-refund-after-paid]');
        const afterWrapEl   = body.querySelector('[data-refund-after-wrap]');
        const selectedCount = body.querySelector('[data-refund-selected-count]');

        const recalc = () => {
            let total = 0;
            let rowsSelected = 0;

            items.forEach(it => {
                const max = maxQtyFor(it.id);
                const q = Math.max(0, Math.min(Number(refundQty[it.id] || 0), max));
                refundQty[it.id] = q;

                const lineEl = body.querySelector(`[data-refund-line-total="${it.id}"]`);
                if (lineEl) {
                    lineEl.textContent = q > 0 ? fmtMoney(q * Number(it.unit_price)) : fmtMoney(0);
                    lineEl.classList.toggle('text-danger', q > 0);
                }

                if (q > 0) {
                    rowsSelected++;
                    total += q * Number(it.unit_price);
                }
            });

            if (totalRefundEl) totalRefundEl.textContent = fmtMoney(total);

            const paidAfter = Math.max(0, Number(ord.paid_amount || 0) - total);
            if (afterPaidEl) afterPaidEl.textContent = fmtMoney(paidAfter);
            if (afterWrapEl) afterWrapEl.classList.toggle('d-none', total === 0);

            if (selectedCount) {
                const selectable = items.filter(i => maxQtyFor(i.id) > 0).length;
                selectedCount.textContent = `${rowsSelected} / ${selectable}`;
            }

            const submitBtn = body.querySelector('[data-refund-submit]');
            if (submitBtn) submitBtn.disabled = total <= 0;
        };

        // ── Input wiring ────────────────────────────────────────
        body.querySelectorAll('[data-refund-qty]').forEach(input => {
            const id = Number(input.getAttribute('data-refund-qty'));
            const max = maxQtyFor(id);

            const clamp = () => {
                let v = parseInt(input.value, 10);
                if (isNaN(v)) v = 0;
                if (v < 0) v = 0;
                if (v > max) v = max;
                input.value = v;
                refundQty[id] = v;
                recalc();
            };

            input.addEventListener('input', clamp);
            input.addEventListener('blur', clamp);
        });

        body.querySelectorAll('[data-refund-inc]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = Number(btn.getAttribute('data-refund-inc'));
                const max = maxQtyFor(id);
                refundQty[id] = Math.min(max, Number(refundQty[id] || 0) + 1);
                const input = body.querySelector(`[data-refund-qty="${id}"]`);
                if (input) input.value = refundQty[id];
                recalc();
            });
        });

        body.querySelectorAll('[data-refund-dec]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = Number(btn.getAttribute('data-refund-dec'));
                refundQty[id] = Math.max(0, Number(refundQty[id] || 0) - 1);
                const input = body.querySelector(`[data-refund-qty="${id}"]`);
                if (input) input.value = refundQty[id];
                recalc();
            });
        });

        body.querySelector('[data-refund-select-all]')?.addEventListener('click', () => {
            items.forEach(it => {
                const max = maxQtyFor(it.id);
                refundQty[it.id] = max;
                const input = body.querySelector(`[data-refund-qty="${it.id}"]`);
                if (input) input.value = refundQty[it.id];
            });
            recalc();
        });

        body.querySelector('[data-refund-clear-all]')?.addEventListener('click', () => {
            items.forEach(it => {
                refundQty[it.id] = 0;
                const input = body.querySelector(`[data-refund-qty="${it.id}"]`);
                if (input) input.value = 0;
            });
            recalc();
        });

        recalc();

        // ── Submit ──────────────────────────────────────────────
        body.querySelector('[data-refund-submit]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            const reason = body.querySelector('[name="reason"]').value.trim();

            const selections = items
                .map(it => ({ order_item_id: it.id, quantity: Number(refundQty[it.id] || 0) }))
                .filter(s => s.quantity > 0);

            if (!selections.length) {
                window.toastr?.warning?.('{{ __("payments.select_at_least_one_item") ?? "Select at least one item to refund." }}');
                return;
            }
            if (!reason) {
                window.toastr?.warning?.('{{ __("payments.refund_reason_required") ?? "Please provide a reason for the refund." }}');
                return;
            }

            setLoading(btn, true);
            try {
                const json = await req('POST', `/orders/${ord.id}/refund`, { reason, items: selections });
                window.toastr?.success?.(json.message);
                if (json.invoice_voided) {
                    window.toastr?.info?.('{{ __("payments.linked_invoice_voided") ?? "The linked invoice was also voided." }}');
                }
                window.OrderList?.reload?.();
                await reload(ord.id);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });
    }

    // ── CANCEL ─────────────────────────────────────────────────
    function openCancelPanel(ord) {
        const body = panelShell(
            '{{ __('passwords.cancel_order') }}',
            `
            <div class="alert alert-danger d-flex mb-4">
                <i class="bi bi-exclamation-triangle fs-3 me-3"></i>
                <div class="fs-7">{{ __('passwords.cancel_confirmation') }}</div>
            </div>
            `,
            `<button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('passwords.keep_order') }}</button>
             <button type="button" class="btn btn-danger" data-cancel-submit>
                 <span class="indicator-label"><i class="bi bi-x-circle me-1"></i>{{ __('passwords.cancel_order') }}</span>
                 <span class="indicator-progress d-none">{{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
             </button>`
        );

        body.querySelector('[data-cancel-submit]')?.addEventListener('click', async (ev) => {
            const btn = ev.currentTarget;
            setLoading(btn, true);
            try {
                const json = await req('POST', `/pos-cancel/${ord.id}`, { status: 'cancelled' });
                window.toastr?.success?.(json.message || '{{ __("passwords.success") }}');
                window.OrderList?.reload?.();
                await reload(ord.id);
            } catch (err) {
                window.toastr?.error?.(err.message);
            } finally {
                setLoading(btn, false);
            }
        });
    }

    function printOrder(ord) {
        if (!currentPayload) return;
        const items    = currentPayload.items    || [];
        const payments = currentPayload.payments || [];

        // ── Build the same payload shape as generateMultiPaymentReceipt ──
        const receiptOrder = {
            id:           ord.id,
            order_number: ord.order_number,
            ref:          ord.order_number,
            type:         ord.type,
            order_type:   ord.type_label || ord.type || 'sale',
            source:       ord.source,
            customer_name: ord.customer_name,
            customer: { name: ord.customer_name, email: ord.customer_email, phone: ord.customer_phone },
            cashier:      ord.creator_name,
            subtotal:     ord.subtotal,
            discount:     ord.discount_total,
            tax:          ord.tax_total,
            total:        ord.total,
            items: items.map(it => ({
                name:  it.item_name,
                quantity: it.quantity,
                price: it.unit_price,
                total: it.total_price,
                note:  null,
            })),
            payments: payments.map(p => ({
                method_name: p.method_name,
                type:        p.method_type,
                amount:      p.amount,
                tendered:    p.amount,
                change:      0,
                transaction_reference: p.transaction_id,
                account_number: p.account_number,
            })),
            total_change: 0,
        };

        // ── Render the receipt HTML in a new window with POS styles ──
        const html = buildReceiptHtml(receiptOrder);
        const w = window.open('', '_blank', 'width=450,height=800,scrollbars=yes');
        if (!w) {
            window.toastr?.warning?.('Please allow popups to print the receipt.');
            return;
        }
        w.document.write(`<!DOCTYPE html><html><head>
            <title>Receipt ${esc(ord.order_number)}</title>
            <meta charset="UTF-8">
            <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">
            <style>${receiptCss()}</style>
        </head><body>
            <div class="rcpt-page">${html}</div>
            <script>window.onload=function(){setTimeout(function(){window.print();}, 250);};<\/script>
        </body></html>`);
        w.document.close();
    }

    // Same styles as the POS receipt modal
    function receiptCss() {
        return `
            @page { margin: 8mm; }
            body {
                font-family: 'Inter', 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
                background: #fff; margin: 0; padding: 20px;
                color: #1e2129; font-size: 13px;
            }
            .rcpt-page { max-width: 420px; margin: 0 auto; }
            .rcpt-store-name { font-size: 16px; font-weight: 700; color: #181c32; line-height: 1.2; margin: 0; }
            .rcpt-store-tagline { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px; color: #7e8299; }
            .rcpt-store-address { font-size: 10px; color: #7e8299; }
            .rcpt-divider-solid { border-top: 1px solid #e4e6ef; margin: 15px 0; }
            .rcpt-divider-dash  { border-top: 1px dashed #b5b5c3; margin: 15px 0; }
            .rcpt-divider-star  { text-align: center; font-size: 12px; color: #7e8299; margin: 12px 0; letter-spacing: 2px; }
            .rcpt-meta { font-size: 12px; color: #5e6278; line-height: 1.8; }
            .rcpt-meta-row { display: flex; justify-content: space-between; padding: 2px 0; }
            .rcpt-meta-row span:last-child { font-weight: 600; color: #181c32; }
            .rcpt-customer {
                background: #f1faff; color: #009ef7; text-align: center;
                padding: 8px 12px; margin: 12px 0; font-size: 13px; font-weight: 600;
                border-radius: 0.475rem; border-left: 4px solid #009ef7;
            }
            .rcpt-order-type {
                font-size: 11px; font-weight: 600; letter-spacing: 0.5px;
                text-transform: uppercase; background: #f5f8fa; padding: 4px 12px;
                display: inline-block; color: #3f4254; border-radius: 30px;
            }
            .rcpt-items { width: 100%; border-collapse: collapse; margin: 10px 0; }
            .rcpt-items th {
                font-size: 11px; font-weight: 600; color: #7e8299;
                text-transform: uppercase; letter-spacing: 0.5px;
                padding-bottom: 6px; border-bottom: 1px solid #e4e6ef;
            }
            .rcpt-items td { padding: 6px 0; vertical-align: top; font-size: 13px; border-bottom: 1px dashed #f1f1f2; }
            .rcpt-item-qty { width: 35px; color: #5e6278; font-weight: 500; }
            .rcpt-item-name { font-weight: 500; color: #181c32; }
            .rcpt-item-price { text-align: right; font-weight: 600; white-space: nowrap; padding-left: 10px; color: #181c32; }
            .rcpt-item-sub { font-size: 11px; color: #7e8299; padding-left: 35px; font-style: italic; border-bottom: none !important; }
            .rcpt-totals { width: 100%; border-collapse: collapse; margin: 10px 0; }
            .rcpt-totals td { padding: 4px 0; font-size: 13px; }
            .rcpt-total-label { color: #5e6278; font-weight: 500; }
            .rcpt-total-value { text-align: right; font-weight: 600; color: #181c32; }
            .rcpt-grand-row td { font-size: 18px; font-weight: 700; padding-top: 8px; padding-bottom: 4px; color: #009ef7; border-top: 1px solid #e4e6ef; }
            .rcpt-grand-value { text-align: right; color: #009ef7; font-weight: 700; }
            .rcpt-payments { width: 100%; border-collapse: collapse; margin: 8px 0; }
            .rcpt-payments td { padding: 4px 0; font-size: 12px; border-bottom: 1px dashed #f1f1f2; }
            .rcpt-pay-label { color: #5e6278; font-weight: 500; }
            .rcpt-pay-value { text-align: right; font-weight: 600; color: #181c32; }
            .rcpt-change-box {
                background: #e8fff3; text-align: center; padding: 12px 15px;
                margin: 15px 0; border-radius: 0.625rem; border-left: 4px solid #50cd89;
            }
            .rcpt-change-label { font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase; color: #50cd89; font-weight: 600; margin-bottom: 4px; }
            .rcpt-change-value { font-size: 28px; font-weight: 700; color: #50cd89; line-height: 1.2; }
            .rcpt-thankyou { text-align: center; font-size: 15px; font-weight: 600; letter-spacing: 0.3px; margin: 15px 0 5px; color: #181c32; }
            .rcpt-survey { text-align: center; font-size: 10px; color: #7e8299; line-height: 1.5; }
            .rcpt-barcode { text-align: center; font-family: 'Libre Barcode 128', monospace; font-size: 48px; line-height: 1; margin: 8px 0 2px; color: #181c32; letter-spacing: 1px; }
            .rcpt-barcode-num { text-align: center; font-size: 10px; letter-spacing: 2px; color: #7e8299; margin-top: -5px; font-weight: 500; }
        `;
    }

    // Build the receipt body HTML — mirrors the POS receipt modal's structure
    function buildReceiptHtml(order) {
        const sym = '{{ currency_symbol() }}';
        const fmt = n => sym + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        const now = new Date();
        const pad = n => String(n).padStart(2, '0');
        const date = `${pad(now.getDate())}/${pad(now.getMonth()+1)}/${now.getFullYear()}`;
        const time = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;

        const items    = order.items    || [];
        const payments = order.payments || [];
        let itemCount = 0;
        let totalChange = parseFloat(order.total_change || 0);

        const itemRows = items.length ? items.map(it => {
            const qty = parseInt(it.quantity || 1, 10);
            const total = parseFloat(it.total || 0);
            itemCount += qty;
            return `
                <tr>
                    <td class="rcpt-item-qty">${qty}</td>
                    <td class="rcpt-item-name">${esc(it.name || 'Item')}</td>
                    <td class="rcpt-item-price">${fmt(total)}</td>
                </tr>
                ${it.note ? `<tr><td></td><td colspan="2" class="rcpt-item-sub">↳ ${esc(it.note)}</td></tr>` : ''}
            `;
        }).join('') : '<tr><td colspan="3" style="color:#999;text-align:center;">No items</td></tr>';

        const payRows = payments.length ? payments.map(p => {
            const change = parseFloat(p.change || 0);
            totalChange += change;
            const label = String(p.method_name || p.type || 'Payment').toUpperCase();
            const acct = p.account_number ? ' (****' + String(p.account_number).slice(-4) + ')' : '';
            return `
                <tr><td class="rcpt-pay-label">${label}${acct}</td><td class="rcpt-pay-value">${fmt(p.tendered || p.amount || 0)}</td></tr>
                ${parseFloat(p.change || 0) > 0.005 ? `<tr><td class="rcpt-pay-label" style="padding-left:12px;color:#888;">↳ Applied</td><td class="rcpt-pay-value" style="color:#888;">${fmt(p.amount)}</td></tr>` : ''}
                ${p.transaction_reference ? `<tr><td colspan="2" style="font-size:10px;color:#888;padding-left:12px;">Ref: ${esc(p.transaction_reference)}</td></tr>` : ''}
            `;
        }).join('') : `<tr><td class="rcpt-pay-label">PAID</td><td class="rcpt-pay-value">${fmt(order.total)}</td></tr>`;

        const barcodeVal = String(order.order_number || '0').replace(/\D/g, '').padEnd(12, '0').slice(0, 12);

        const discountRow = order.discount > 0 ? `<tr><td class="rcpt-total-label">Discount</td><td class="rcpt-total-value" style="color:#f1416c;">-${fmt(order.discount)}</td></tr>` : '';
        const taxRow      = order.tax > 0      ? `<tr><td class="rcpt-total-label">Tax</td><td class="rcpt-total-value">${fmt(order.tax)}</td></tr>` : '';
        const changeBox   = totalChange > 0.005 ? `<div class="rcpt-change-box"><div class="rcpt-change-label">Change Due</div><div class="rcpt-change-value">${fmt(totalChange)}</div></div>` : '';

        return `
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;padding-bottom:8px;border-bottom:2px solid #e4e6ef;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <img alt="Logo" src="{{ getLogoImage() }}" style="max-height:38px;width:auto;object-fit:contain;" />
                    <div class="rcpt-store-name">{{ getUIOptions('app_name') }}</div>
                </div>
            </div>

            <div style="text-align:center;font-size:10px;color:#7e8299;margin-bottom:6px;line-height:1.3;">
                <div style="text-align:center;">
                    <div style="font-size:14px;font-weight:700;color:#009ef7;text-transform:uppercase;letter-spacing:1px;">RECEIPT</div>
                    <div style="font-size:9px;color:#7e8299;">#<span>${esc(order.order_number)}</span></div>
                </div>
            </div>

            <div style="text-align:center;font-size:10px;color:#7e8299;margin-bottom:6px;line-height:1.3;">
                <span class="rcpt-store-tagline">{{ getUIOptions('app_name') }}</span>
                <span class="rcpt-store-address">{{ getUIOptions('app_email') }} | {{ getUIOptions('app_contact') }}</span>
            </div>

            <div class="rcpt-divider-solid"></div>

            <div class="rcpt-meta">
                <div class="rcpt-meta-row"><span>Order #:</span><span>${esc(order.order_number)}</span></div>
                <div class="rcpt-meta-row"><span>Date:</span><span>${date}</span></div>
                <div class="rcpt-meta-row"><span>Time:</span><span>${time}</span></div>
                <div class="rcpt-meta-row"><span>Cashier:</span><span>${esc(order.cashier || 'STAFF')}</span></div>
            </div>

            <div class="rcpt-customer"><span>Customer: ${esc((order.customer_name || 'GUEST').toUpperCase())}</span></div>

            <div style="text-align:center;margin:8px 0;">
                <span class="rcpt-order-type">${esc((order.order_type || 'SALE').toUpperCase())}</span>
            </div>

            <div class="rcpt-divider-dash"></div>

            <table class="rcpt-items">
                <thead>
                    <tr>
                        <th style="text-align:left;">Qty</th>
                        <th style="text-align:left;">Item</th>
                        <th style="text-align:right;">Price</th>
                    </tr>
                </thead>
                <tbody>${itemRows}</tbody>
            </table>

            <div class="rcpt-divider-dash"></div>

            <table class="rcpt-totals">
                <tbody>
                    <tr><td class="rcpt-total-label">Subtotal</td><td class="rcpt-total-value">${fmt(order.subtotal)}</td></tr>
                    ${discountRow}
                    ${taxRow}
                    <tr class="rcpt-grand-row"><td class="rcpt-total-label">TOTAL</td><td class="rcpt-grand-value">${fmt(order.total)}</td></tr>
                </tbody>
            </table>

            <div class="rcpt-divider-solid"></div>

            <div style="font-size:12px;font-weight:600;color:#3f4254;margin-bottom:6px;">PAYMENT METHODS</div>
            <table class="rcpt-payments"><tbody>${payRows}</tbody></table>

            ${changeBox}

            <div class="rcpt-divider-star">• • • • • • • • • • • • • • • •</div>

            <div class="rcpt-meta">
                <div class="rcpt-meta-row"><span>Total Items:</span><span>${itemCount}</span></div>
            </div>

            <div class="rcpt-thankyou">Thank you for your business!</div>
            <div class="rcpt-survey">Please come again<br><span style="color:#009ef7;">By Stardena</span></div>

            <div class="rcpt-barcode">${barcodeVal}</div>
            <div class="rcpt-barcode-num">${barcodeVal}</div>
            <div style="height:10px;"></div>
        `;
    }

    // ── PANEL SHELL ────────────────────────────────────────────
    function panelShell(title, bodyHtml, footerHtml) {
        const body = document.getElementById('orderDrawerContent');
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
        document.getElementById('orderDrawerFooter')?.classList.add('d-none');
        body.querySelectorAll('[data-panel-back]').forEach(btn => {
            btn.addEventListener('click', () => render(currentPayload));
        });
        return body;
    }

    // ── helpers ────────────────────────────────────────────────
    function setLoading(btn, busy) {
        if (!btn) return;
        if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';
        const label = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');
        if (progress && !progress.dataset.overlayInit) {
            Object.assign(progress.style, {
                position: 'absolute', top: '50%', left: '50%',
                transform: 'translate(-50%, -50%)',
                alignItems: 'center', gap: '0.5rem',
                whiteSpace: 'nowrap', pointerEvents: 'none',
            });
            progress.dataset.overlayInit = '1';
        }
        btn.disabled = busy;
        if (label) label.style.visibility = busy ? 'hidden' : '';
        if (progress) {
            progress.classList.toggle('d-none', !busy);
            progress.style.display = busy ? 'inline-flex' : 'none';
        }
    }

    function validateE164(raw) {
        if (!raw || !raw.trim()) return { valid: false, error: '{{ __("payments.phone_required") }}' };
        let c = raw.trim().replace(/[\s\-\.\(\)]/g, '');
        if (c.startsWith('00')) c = '+' + c.slice(2);
        if (!c.startsWith('+')) c = '+' + c;
        if (!/^\+[1-9]\d{7,14}$/.test(c)) return { valid: false, error: '{{ __("payments.invalid_phone_format") }}' };
        return { valid: true, formatted: c };
    }

    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    window.OrderDrawer = { open, reload, close };
    return window.OrderDrawer;
})();
</script>