{{--
  partials/payment-scripts.blade.php
  ──────────────────────────────────
  Contains the split-payment modal scripts, extracted from pos-scripts.blade.php:
    - window.openPaymentModal(cartData)
    - window.processSplitPayments()
    - window.generateMultiPaymentReceipt(order)
    - addPayment / removePayment / tab handlers / bargain discount

  Requires on the page (in this order, before this file):
    - partials/payment-modals.blade.php   → #paymentModal, #receiptModal, #posSendReceiptModal
    - window.userData                     → set below (idempotent)

  Safe to include on any page. The cart-specific code path inside
  openPaymentModal is guarded for non-POS pages.
--}}

<script>
// Ensure userData exists — no-op if pos-scripts already set it
window.userData = window.userData || {
    tenantId:        {{ auth()->user()?->tenant_id ?? 0 }},
    isSingleShop:    {{ auth()->user()?->tenant_id && tenant_is_single_shop(auth()->user()->tenant_id) ? 'true' : 'false' }},
    locationId:      {{ auth()->user()?->location_id ?? 1 }},
    currencySymbol:  '{{ currency_symbol() }}',
    currencyCode:    '{{ currency_code() }}',
};
var POS_CURRENCY_SYM = window.POS_CURRENCY_SYM || window.userData.currencySymbol;
window.POS_CURRENCY_SYM = POS_CURRENCY_SYM;

function posFmt(n) {
    return POS_CURRENCY_SYM + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/* ── Receipt generator — from pos-scripts, verbatim ────────── */
window.generateMultiPaymentReceipt = function (order) {
    window.currentReceiptOrder = order;
    var g = id => document.getElementById(id);
    var SYM = POS_CURRENCY_SYM;
    var fmt = n => SYM + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    var pad = n => String(n).padStart(2, '0');
    var now = new Date(), date = pad(now.getDate())+'/'+pad(now.getMonth()+1)+'/'+now.getFullYear();
    var time = pad(now.getHours())+':'+pad(now.getMinutes())+':'+pad(now.getSeconds());

    g('rcpt-order-no').textContent = order.order_number || order.ref || 'N/A';
    g('rcpt-date').textContent     = order.date || date;
    g('rcpt-time').textContent     = order.time || time;
    g('rcpt-cashier').textContent  = order.cashier || '{{ auth()->user()->name ?? "STAFF" }}';
    var customerName = order.customer_name || (order.customer && order.customer.name) || 'GUEST';
    g('rcpt-customer-name').textContent = '{{ __("pagination.customer") }}: ' + String(customerName).toUpperCase();
    g('rcpt-order-type').textContent = String(order.order_type || order.type || 'SALE').toUpperCase();

    var items = order.items || [], itemCount = 0;
    var itemsBody = g('rcpt-items-body');
    if (items.length) {
        itemsBody.innerHTML = items.map(function (item) {
            var qty = parseInt(item.quantity || item.qty || 1);
            var total = parseFloat(item.total || ((item.price || 0) * qty));
            itemCount += qty;
            return '<tr><td class="rcpt-item-qty">'+qty+'</td><td class="rcpt-item-name">'+
                   (item.name || item.item_name || 'Item')+'</td><td class="rcpt-item-price">'+fmt(total)+'</td></tr>'+
                   (item.note ? '<tr><td></td><td colspan="2" class="rcpt-item-sub">↳ '+item.note+'</td></tr>' : '');
        }).join('');
    } else {
        itemsBody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#999;">No items</td></tr>';
    }
    g('rcpt-item-count').textContent = itemCount;

    var subtotal = parseFloat(order.subtotal || order.total || 0);
    var discount = parseFloat(order.discount || 0);
    var tax      = parseFloat(order.tax || 0);
    var total    = parseFloat(order.total || 0);
    g('rcpt-subtotal').textContent = fmt(subtotal);
    if (discount > 0) { g('rcpt-discount-row').classList.remove('d-none'); g('rcpt-discount').textContent = '-'+fmt(discount); }
    else              { g('rcpt-discount-row').classList.add('d-none'); }
    if (tax > 0)      { g('rcpt-tax-row').classList.remove('d-none');      g('rcpt-tax').textContent = fmt(tax); }
    else              { g('rcpt-tax-row').classList.add('d-none'); }
    g('rcpt-grand-total').textContent = fmt(total);

    var payments = order.payments || [];
    var payBody = g('rcpt-payments-body');
    var totalChange = parseFloat(order.total_change || 0);
    if (payments.length) {
        payBody.innerHTML = payments.map(function (p) {
            var change = parseFloat(p.change || 0);
            totalChange += change;
            var label = String(p.method_name || p.type || 'Payment').toUpperCase();
            var acct  = p.account_number ? ' (****'+String(p.account_number).slice(-4)+')' : '';
            return '<tr><td class="rcpt-pay-label">'+label+acct+'</td><td class="rcpt-pay-value">'+fmt(p.tendered||p.amount||0)+'</td></tr>'+
                   (p.transaction_reference ? '<tr><td colspan="2" style="font-size:10px;color:#888;padding-left:12px;">Ref: '+p.transaction_reference+'</td></tr>' : '');
        }).join('');
    } else {
        payBody.innerHTML = '<tr><td class="rcpt-pay-label">{{ __("pagination.paid") }}</td><td class="rcpt-pay-value">'+fmt(total)+'</td></tr>';
    }

    if (totalChange > 0.005) {
        g('rcpt-change-box').classList.remove('d-none');
        g('rcpt-change-value').textContent = fmt(totalChange);
    } else {
        g('rcpt-change-box').classList.add('d-none');
    }

    var barcodeVal = String(order.order_number || order.ref || '0').replace(/\D/g, '').padEnd(12, '0').slice(0, 12);
    g('rcpt-barcode').textContent = barcodeVal;
    g('rcpt-barcode-num').textContent = barcodeVal;

    bootstrap.Modal.getOrCreateInstance(g('receiptModal')).show();
};

/* ── Payment modal IIFE — from pos-scripts, verbatim ────────── */
(function () {
    @if(isset($active_payment_methods))
        window.activePaymentMethods = @json($globalPaymentMethods ?? []);
    @endif

    var TYPES_WITH_REF = ['card','bank_account','mobile_money','digital_wallet','check','credit'];
    var CASH_TYPES     = ['cash'];
    var splitPayments  = [];
    var currentOrder   = null;
    var bargainDiscount = 0;

    var g  = id  => document.getElementById(id);
    var qs = sel => document.querySelector(sel);

    function getRemainingRaw() {
        var el = g('pm-remaining');
        return el ? parseFloat(el.textContent.replace(/[^0-9.-]+/g,'')) || 0 : 0;
    }
    function updateRemainingHint(type) {
        var hint = g('pm-remaining-hint-'+type);
        if (hint) hint.textContent = '{{ __("pagination.remaining") }}: ' + posFmt(getRemainingRaw());
    }
    function buildQuickAmounts(type) {
        var container = g('pm-quick-'+type); if (!container) return;
        var remaining = getRemainingRaw();
        if (remaining <= 0) { container.innerHTML = '<span class="text-success fw-semibold fs-7">{{ __("pagination.fully_paid") }}</span>'; return; }
        var presets = [parseFloat(remaining.toFixed(2))];
        var rounds  = [1,2,5,10,20,50,100,200,500,1000,2000,5000,10000];
        for (var i = 0; i < rounds.length && presets.length < 7; i++) {
            var r = Math.ceil(remaining/rounds[i])*rounds[i];
            if (r > remaining && r <= remaining*5 && presets.indexOf(r)===-1) presets.push(r);
        }
        presets.sort((a,b) => a-b);
        container.innerHTML = presets.map(function (v) {
            var isExact = Math.abs(v-remaining) < 0.005;
            return '<button type="button" class="pm-quick-btn'+(isExact?' pm-exact':'')+'" data-payment-type="'+type+'" data-quick-amount="'+v+'">'+(isExact?'<span style="font-size:.7rem">✓ EXACT</span><br>':'')+posFmt(v)+'</button>';
        }).join('');
    }
    function updateCashCalc(type) {
        var amountEl = g('pm-amount-'+type), calcWrap = g('pm-cash-calc-'+type);
        if (!amountEl || !calcWrap) return;
        var isCash = CASH_TYPES.indexOf(type) !== -1, tendered = parseFloat(amountEl.value)||0, remaining = getRemainingRaw();
        if (!isCash || tendered <= 0) { calcWrap.classList.add('d-none'); return; }
        calcWrap.classList.remove('d-none');
        var change = Math.max(0,tendered-remaining), isUnderpaid = tendered < remaining-0.005;
        var banner = g('pm-change-banner-'+type), tenderedEl = g('pm-tendered-'+type), changeEl = g('pm-change-'+type);
        if (tenderedEl) tenderedEl.textContent = posFmt(tendered);
        if (banner)     banner.classList.toggle('pm-underpaid', isUnderpaid);
        if (changeEl)   changeEl.textContent   = isUnderpaid ? 'Short '+posFmt(remaining-tendered) : posFmt(change);
    }
    function validateBtn(type) {
        var account = g('pm-account-'+type), amount = g('pm-amount-'+type), btn = g('pm-add-btn-'+type);
        if (!account||!amount||!btn) return;
        var tendered = parseFloat(amount.value)||0, remaining = getRemainingRaw(), isCash = CASH_TYPES.indexOf(type)!==-1;
        var ok = account.value!=='' && tendered>0 && (isCash ? remaining>0 : tendered<=remaining+0.005);
        btn.disabled = !ok; btn.classList.toggle('btn-primary',ok); btn.classList.toggle('btn-secondary',!ok);
    }
    function toggleRef(type) {
        var row = g('pm-ref-row-'+type); if (!row) return;
        var show = TYPES_WITH_REF.indexOf(type)!==-1;
        row.classList.toggle('d-none',!show);
        if (!show) { var inp = g('pm-ref-'+type); if (inp) inp.value=''; }
    }
    function resetTab(type) {
        [g('pm-account-'+type),g('pm-amount-'+type),g('pm-ref-'+type)].forEach(function (el) { if (el) el.value=''; });
        var calc = g('pm-cash-calc-'+type); if (calc) calc.classList.add('d-none');
        validateBtn(type); buildQuickAmounts(type); updateRemainingHint(type);
    }
    function updateBargainUI() {
        var input      = g('pm-bargain-input');
        var applyBtn   = g('pm-bargain-apply-btn');
        var removeBtn  = g('pm-bargain-remove-btn');
        var appliedRow = g('pm-bargain-applied-row');
        var appliedTxt = g('pm-bargain-applied-text');
        var help       = g('pm-bargain-help');
        var locked     = splitPayments.length > 0;
        if (input)    input.disabled    = locked || bargainDiscount > 0;
        if (applyBtn) applyBtn.disabled = locked || bargainDiscount > 0;
        if (bargainDiscount > 0) {
            appliedRow.classList.remove('d-none');
            appliedTxt.textContent = '{{ __("pagination.discount_applied") }}: ' + posFmt(bargainDiscount);
            if (removeBtn) removeBtn.classList.toggle('d-none', locked);
        } else {
            appliedRow.classList.add('d-none');
        }
        if (help) {
            help.textContent = locked
                ? '{{ __("pagination.remove_payments_to_change_discount") }}'
                : '{{ __("pagination.negotiated_discount_help") }}';
        }
    }
    function applyBargainDiscount() {
        var input = g('pm-bargain-input');
        if (!input || !currentOrder) return;
        var value = parseFloat(input.value);
        if (!value || value <= 0) { toastr.warning('{{ __("pagination.please_enter_valid_amount") }}'); return; }
        var payableBeforeDiscount = currentOrder.original_total ?? currentOrder.total;
        if (value >= payableBeforeDiscount) { toastr.warning('{{ __("pagination.discount_exceeds_total") }}'); return; }
        if (!currentOrder.original_total) currentOrder.original_total = currentOrder.total;
        bargainDiscount    = parseFloat(value.toFixed(2));
        currentOrder.total = parseFloat((currentOrder.original_total - bargainDiscount).toFixed(2));
        g('pm-order-total').textContent = posFmt(currentOrder.total);
        updateSummary();
        updateBargainUI();
        toastr.success('{{ __("pagination.discount_applied_successfully") }}');
    }
    function removeBargainDiscount() {
        if (!currentOrder) return;
        currentOrder.total = currentOrder.original_total ?? currentOrder.total;
        bargainDiscount = 0;
        var input = g('pm-bargain-input'); if (input) input.value = '';
        g('pm-order-total').textContent = posFmt(currentOrder.total);
        updateSummary();
        updateBargainUI();
    }
    function renderTable() {
        var tbody = g('pm-splits-body'); if (!tbody) return;
        if (!splitPayments.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-muted"><div class="fs-6 fw-semibold">{{ __("pagination.no_payments_added") }}</div></td></tr>';
            return;
        }
        tbody.innerHTML = splitPayments.map(function (p,i) {
            return '<tr><td class="ps-6"><div class="d-flex align-items-center gap-3"><div><div class="fw-bold text-gray-800">'+formatPaymentType(p.type)+'</div><small class="text-muted">'+p.method_name+'</small></div></div></td>'+
                '<td><span class="badge badge-light-primary fs-8">'+(p.account_number||'N/A')+'</span>'+(p.transaction_ref?'<small class="d-block text-muted mt-1">Ref: '+p.transaction_ref+'</small>':'')+'</td>'+
                '<td class="text-end fw-bold text-gray-600">'+posFmt(p.tendered)+'</td>'+
                '<td class="text-end fw-bolder fs-5 text-gray-900">'+posFmt(p.amount)+'</td>'+
                '<td class="text-end">'+(p.change>0.005?'<span class="badge badge-light-success fw-bold fs-7">'+posFmt(p.change)+'</span>':'<span class="text-muted fs-7">—</span>')+'</td>'+
                '<td class="text-end pe-6"><button type="button" class="btn btn-sm btn-icon btn-light-danger pm-remove-btn" data-index="'+i+'">×</button></td></tr>';
        }).join('');
        var count = g('pm-splits-count');
        if (count) count.textContent = splitPayments.length + ' {{ __("pagination.payments") }}';
    }
    function updateSummary() {
        if (!currentOrder) return;
        var totalApplied  = splitPayments.reduce(function (s,p) { return s+p.amount; },   0);
        var totalTendered = splitPayments.reduce(function (s,p) { return s+p.tendered; }, 0);
        var totalChange   = splitPayments.reduce(function (s,p) { return s+p.change; },   0);
        var remaining     = Math.max(0, currentOrder.total - totalApplied);
        g('pm-paid-amount').textContent = posFmt(totalApplied);
        g('pm-remaining').textContent   = posFmt(remaining);
        var wrap = g('pm-remaining-wrap'), remEl = g('pm-remaining');
        if (wrap)  { wrap.classList.toggle('bg-light-danger', remaining>0.005);  wrap.classList.toggle('bg-light-success', remaining<=0.005); }
        if (remEl) { remEl.classList.toggle('text-danger',    remaining>0.005);  remEl.classList.toggle('text-success',    remaining<=0.005); }
        var totTen=g('pm-total-tendered'),totApp=g('pm-splits-total'),totChg=g('pm-total-change');
        if (totTen) totTen.textContent = totalTendered>0 ? posFmt(totalTendered) : '—';
        if (totApp) totApp.textContent = posFmt(totalApplied);
        if (totChg) totChg.textContent = totalChange>0.005 ? posFmt(totalChange) : '—';
        var pb = g('pm-process-btn');
        if (pb) pb.disabled = !(splitPayments.length>0 && remaining<=0.005);
        var activePane = qs('.tab-pane.active[data-payment-type]');
        if (activePane) { var t=activePane.dataset.paymentType; validateBtn(t); buildQuickAmounts(t); updateRemainingHint(t); }
        updateBargainUI();
    }
    function addPayment(type) {
        var accountEl=g('pm-account-'+type),amountEl=g('pm-amount-'+type),refEl=g('pm-ref-'+type);
        if (!accountEl||!amountEl) return;
        var accountId=accountEl.value, tendered=parseFloat(amountEl.value), ref=refEl?refEl.value.trim():'', remaining=getRemainingRaw(), isCash=CASH_TYPES.indexOf(type)!==-1;
        if (!accountId)                         { toastr.warning('{{ __("pagination.please_select_account") }}');      return; }
        if (!tendered||tendered<=0)             { toastr.warning('{{ __("pagination.please_enter_valid_amount") }}'); return; }
        if (!isCash&&tendered>remaining+0.005)  { toastr.warning('{{ __("pagination.amount_exceeds_remaining") }}');  return; }
        if (remaining<=0)                       { toastr.warning('{{ __("pagination.order_already_paid") }}');         return; }
        var applied=isCash?Math.min(tendered,remaining):tendered, change=isCash?Math.max(0,tendered-remaining):0;
        var opt=accountEl.options[accountEl.selectedIndex], accountData={};
        try { accountData=JSON.parse(opt.dataset.account||'{}'); } catch(e) {}
        splitPayments.push({ id:Date.now()+Math.random(), type:type, method_id:accountId, method_name:accountData.name||'Unknown',
            account_number:accountData.account_number||'', tendered:tendered, amount:applied, change:change, transaction_ref:ref, account_data:accountData });
        renderTable(); updateSummary(); resetTab(type);
        if (change>0.005) toastr.info('<strong>{{ __("pagination.change_due") }}: '+posFmt(change)+'</strong>','{{ __("pagination.give_change_to_customer") }}',{timeOut:6000});
    }
    function removePayment(index) { splitPayments.splice(index,1); renderTable(); updateSummary(); }

    window.openPaymentModal = function (cartData) {
        // Recaclulate from live cart IF resumed — guarded for non-POS pages
        if (window.resumedOrderId
            && typeof cart !== 'undefined'
            && Array.isArray(cart)
            && cart.length > 0
            && typeof computeItemDiscount === 'function'
            && typeof computeItemTax === 'function') {
            var liveSubtotal = cart.reduce(function (s,i) { return s + i.price*i.quantity; }, 0);
            var liveDiscount = cart.reduce(function (s,i) { return s + computeItemDiscount(i); }, 0);
            var liveTax      = cart.reduce(function (s,i) { return s + computeItemTax(i); }, 0);
            cartData.subtotal = liveSubtotal;
            cartData.discount = liveDiscount;
            cartData.tax      = liveTax;
            cartData.total    = parseFloat((liveSubtotal - liveDiscount + liveTax).toFixed(2));
        }

        currentOrder = cartData;
        window.currentOrder = cartData;
        splitPayments = [];
        bargainDiscount = 0;
        currentOrder.original_total = cartData.total;

        var bargainInput = g('pm-bargain-input'); if (bargainInput) bargainInput.value = '';
        g('pm-order-total').textContent = posFmt(cartData.total);
        g('pm-paid-amount').textContent = posFmt(0);
        g('pm-remaining').textContent   = posFmt(cartData.total);
        var refEl = g('pm-order-ref');
        if (refEl) refEl.textContent = cartData.order_number ? '#'+cartData.order_number : (cartData.ref ? '#'+cartData.ref : '—');

        document.querySelectorAll('.pm-account-select').forEach(function (s) { s.value=''; });
        document.querySelectorAll('.pm-amount-input').forEach(function (i) { i.value=''; });
        document.querySelectorAll('.pm-ref-input').forEach(function (i) { i.value=''; });
        document.querySelectorAll('.pm-add-btn').forEach(function (b) { b.disabled=true; b.classList.remove('btn-primary'); b.classList.add('btn-secondary'); });
        document.querySelectorAll('[id^="pm-cash-calc-"]').forEach(function (el) { el.classList.add('d-none'); });

        var pb=g('pm-process-btn'); if (pb) pb.disabled=true;
        var wrap=g('pm-remaining-wrap'),remEl=g('pm-remaining');
        if (wrap)  { wrap.classList.add('bg-light-danger');  wrap.classList.remove('bg-light-success'); }
        if (remEl) { remEl.classList.add('text-danger');     remEl.classList.remove('text-success'); }
        renderTable();
        updateBargainUI();

        setTimeout(function () {
            var cashTab = document.querySelector('#pm-tab-cash');
            if (cashTab) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tab) { new bootstrap.Tab(cashTab).show(); }
                else { cashTab.classList.add('active'); var cp = g('pm-pane-cash'); if (cp) cp.classList.add('show','active'); }
                setTimeout(function () { var ai=g('pm-amount-cash'); if (ai) { ai.dispatchEvent(new Event('input',{bubbles:true})); ai.focus(); ai.select(); } }, 300);
            }
            var ap = qs('.tab-pane.active[data-payment-type]');
            if (ap) { var t=ap.dataset.paymentType; toggleRef(t); buildQuickAmounts(t); updateRemainingHint(t); }
        }, 150);

        bootstrap.Modal.getOrCreateInstance(g('paymentModal')).show();
    };

    window.processSplitPayments = function () {
        if (!currentOrder) { toastr.error('{{ __("pagination.no_order_found") }}'); return; }
        if (!splitPayments.length) { toastr.warning('{{ __("pagination.no_payments_added") }}'); return; }
        var totalApplied = splitPayments.reduce(function (s,p) { return s+p.amount; }, 0);
        if (Math.abs(currentOrder.total - totalApplied) > 0.01) {
            toastr.warning('{{ __("pagination.payment_total_mismatch") }}');
            return;
        }
        var btn = g('pm-process-btn');
        btn.disabled = true;

        var isResumed = !!window.resumedOrderId;
        var cartSnapshot = null;
        if (isResumed && typeof cart !== 'undefined' && Array.isArray(cart) && cart.length > 0) {
            cartSnapshot = {
                items: cart.map(function (item) {
                    var itemSubtotal = item.price * item.quantity;
                    var itemTaxes = (item.taxes||[]).map(function (tax) {
                        var rate = parseFloat(tax.rate||0);
                        var amt  = tax.type==='percentage' ? itemSubtotal*(rate/100) : rate*item.quantity;
                        return { id:tax.id, name:tax.name, type:tax.type, rate:tax.rate, amount:amt };
                    });
                    var taxTotal  = itemTaxes.reduce(function (s,t) { return s+t.amount; }, 0);
                    var discTotal = 0;
                    var promos = [];
                    (item.promotions||[]).forEach(function (promo) {
                        var d = 0;
                        if (promo.type==='percentage')   d = itemSubtotal*(promo.value/100);
                        if (promo.type==='fixed_amount') d = promo.value*item.quantity;
                        if (d>0) { discTotal += d; promos.push({ id:promo.id, name:promo.name, type:promo.type, value:promo.value, discount:d }); }
                    });
                    return { variant_id:item.id, name:item.name, price:item.price, quantity:item.quantity,
                        subtotal:itemSubtotal, taxes:itemTaxes, tax_total:taxTotal, discount:discTotal,
                        promotions:promos, total:parseFloat((itemSubtotal-discTotal+taxTotal).toFixed(2)),
                        inventory_id:item.inventory_id||null, department_id:item.department_id||null,
                        batch_id:item.batch_id||null, batch_number:item.batch_number||null,
                        serial_id:item.serial_id||null, serial_number:item.serial_number||null };
                })
            };
        }

        var payload = {
            order_id:       currentOrder.order_id || currentOrder.id,
            total_tendered: splitPayments.reduce(function (s,p) { return s+p.tendered; }, 0),
            total_change:   splitPayments.reduce(function (s,p) { return s+p.change; },   0),
            cart_updated:   isResumed,
            updated_cart:   cartSnapshot,
            bargain_discount: bargainDiscount || 0,
            payments: splitPayments.map(function (p) {
                return {
                    payment_method_id:     p.method_id,
                    amount:                p.amount,
                    tendered:              p.tendered,
                    change:                p.change,
                    transaction_reference: p.transaction_ref || null,
                    type:                  p.type,
                    method_name:           p.method_name,
                    account_number:        p.account_number,
                };
            }),
        };

        fetch('/orders/process-split-payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(payload),
        })
        .then(function (response) {
            if (!response.ok) {
                return response.json().then(function (d) { throw { success:false, message:d.message||'Payment failed', debug:d.debug||null }; })
                    .catch(function () { throw { success:false, message:'Payment failed' }; });
            }
            return response.json();
        })
        .then(function (data) {
            btn.disabled = false;
            if (!data.success) {
                toastr.error(data.message || '{{ __("pagination.payment_failed") }}');
                if (data.debug) console.error('[Debug]', data.debug);
                return;
            }
            toastr.success(data.message || '{{ __("pagination.payment_completed") }}');
            window.resumedOrderId = null;
            window.resumedOrderNumber = null;
            bargainDiscount = 0;

            var payModalEl = g('paymentModal');
            if (payModalEl) {
                payModalEl.addEventListener('hidden.bs.modal', function onHidden() {
                    payModalEl.removeEventListener('hidden.bs.modal', onHidden);
                    if (typeof window.generateMultiPaymentReceipt === 'function' && data.order) {
                        window.generateMultiPaymentReceipt(data.order);
                    }
                    if (window.OrderList?.reload) window.OrderList.reload();
                    if (window.OrderDrawer?.reload && currentOrder?.order_id) {
                        window.OrderDrawer.reload(currentOrder.order_id);
                    }
                });
                bootstrap.Modal.getInstance(payModalEl)?.hide();
            }
            if (typeof clearCart === 'function') clearCart();
        })
        .catch(function (error) {
            btn.disabled = false;
            toastr.error(error.message || '{{ __("pagination.payment_error") }}');
            console.error('[processSplitPayments] error:', error);
        });
    };

    document.addEventListener('click', function (e) {
        if (e.target.closest('#pm-bargain-apply-btn'))  { applyBargainDiscount();  return; }
        if (e.target.closest('#pm-bargain-remove-btn')) { removeBargainDiscount(); return; }
        var qb = e.target.closest('.pm-quick-btn');
        if (qb) { var inp=g('pm-amount-'+qb.dataset.paymentType); if (inp) { inp.value=qb.dataset.quickAmount; inp.dispatchEvent(new Event('input',{bubbles:true})); inp.focus(); } return; }
        var addBtn = e.target.closest('.pm-add-btn'); if (addBtn && !addBtn.disabled) { addPayment(addBtn.dataset.paymentType); return; }
        var remBtn = e.target.closest('.pm-remove-btn'); if (remBtn) { removePayment(parseInt(remBtn.dataset.index,10)); return; }
        if (e.target.closest('#pm-process-btn')) window.processSplitPayments();
    });
    document.addEventListener('change', function (e) { var sel=e.target.closest('.pm-account-select'); if (sel) validateBtn(sel.dataset.paymentType); });
    document.addEventListener('input',  function (e) { var inp=e.target.closest('.pm-amount-input'); if (!inp) return; validateBtn(inp.dataset.paymentType); updateCashCalc(inp.dataset.paymentType); });
    document.addEventListener('shown.bs.tab', function (e) { var type=e.target.dataset.paymentType; if (!type) return; toggleRef(type); buildQuickAmounts(type); validateBtn(type); updateRemainingHint(type); });

})();
</script>