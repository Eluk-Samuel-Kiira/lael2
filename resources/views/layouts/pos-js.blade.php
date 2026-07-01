{{--
  pos-scripts.blade.php  —  FULL REPLACEMENT
  Replaces ALL script blocks in your POS view.
  
  Contains:
    1. Cart functions (addToCart, clearCart, etc.)
    2. Search & department filter
    3. processPayment()       ← resumedOrderId NOT cleared here
    4. Payment modal logic    ← openPaymentModal recalculates live total
                              ← processSplitPayments sends cart_updated + explicit payload
    5. Receipt generator
    6. Payment type helpers
    7. cancelPOSOrder()
--}}

{{-- ═══════════════════════════════════════════════
     1. CART FUNCTIONS
════════════════════════════════════════════════ --}}
<script>
function formatCurrency(value) {
    return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

let cart = [];

function getCartTbody() {
    return document.getElementById('pos-cart-tbody');
}

function addToCart(variant) {
    if (variant.quantity_available <= 0) {
        toastr['error']('{{ __("pagination.out_of_stock") }}');
        return;
    }
    const idx = cart.findIndex(i => i.id === variant.id);
    if (idx > -1) {
        if (cart[idx].quantity < variant.quantity_available) {
            cart[idx].quantity += 1;
            updateCartItem(idx);
            toastr['success']('{{ __("pagination.item_added") }}');
        } else {
            toastr['warning']('{{ __("pagination.max_quantity_reached") }}');
        }
    } else {
        const cartItem = {
            id: variant.id, name: variant.name,
            price: parseFloat(variant.price), image: variant.image,
            quantity: 1, quantity_available: variant.quantity_available,
            taxes: Array.isArray(variant.taxes) ? variant.taxes : [],
            promotions: Array.isArray(variant.promotions) ? variant.promotions : []
        };
        cart.push(cartItem);
        renderCartItem(cartItem);
        toastr['success']('{{ __("pagination.item_added") }}');
    }
}

function renderCartItem(item) {
    const cartTbody = getCartTbody();
    if (!cartTbody) { console.error('[POS] #pos-cart-tbody not found'); return; }
    const lineSubtotal = item.price * item.quantity;
    const newRow = document.createElement('tr');
    newRow.setAttribute('data-item-id', item.id);
    newRow.innerHTML = `
        <td class="pe-0">
            <div class="d-flex align-items-center gap-3">
                <img src="${item.image}" class="w-50px h-50px rounded-3 object-fit-cover border" alt="${item.name}" />
                <div class="d-flex flex-column">
                    <span class="fw-bold text-gray-800 text-hover-primary fs-6">${item.name}</span>
                </div>
            </div>
        </td>
        <td class="pe-0">
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-icon btn-sm btn-light btn-icon-gray-500 w-30px h-30px" onclick="decreaseQuantity(${item.id})">
                    <i class="ki-duotone ki-minus fs-4"></i>
                </button>
                <input type="text" class="form-control border-0 text-center px-0 fs-5 fw-bold text-gray-800 w-35px quantity-input"
                       name="quantity_${item.id}" value="${item.quantity}" onchange="updateQuantity(${item.id}, this.value)" />
                <button type="button" class="btn btn-icon btn-sm btn-light btn-icon-gray-500 w-30px h-30px" onclick="increaseQuantity(${item.id})">
                    <i class="ki-duotone ki-plus fs-4"></i>
                </button>
            </div>
        </td>
        <td class="text-end">
            <div class="d-flex align-items-center justify-content-end gap-2">
                <div class="d-flex flex-column text-end">
                    <span class="fw-bold text-primary fs-4 item-total">${formatCurrency(lineSubtotal)}</span>
                    <small class="text-muted item-tax-line" style="display:none;"></small>
                    <small class="text-success item-discount-line" style="display:none;"></small>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-light-danger" onclick="removeFromCart(${item.id})">
                    <i class="bi bi-trash fs-5"></i>
                </button>
            </div>
        </td>`;
    cartTbody.appendChild(newRow);
    updateItemExtraLines(item.id);
}

function updateItemExtraLines(itemId) {
    const item  = cart.find(i => i.id === itemId);
    const row   = document.querySelector(`tr[data-item-id="${itemId}"]`);
    if (!item || !row) return;
    const tax      = computeItemTax(item);
    const discount = computeItemDiscount(item);
    const taxEl    = row.querySelector('.item-tax-line');
    const discEl   = row.querySelector('.item-discount-line');
    const totalEl  = row.querySelector('.item-total');
    totalEl.textContent = formatCurrency(item.price * item.quantity);
    if (tax > 0)      { taxEl.style.display  = ''; taxEl.textContent  = `+ {{ __("pagination._tax") }} ${formatCurrency(tax)}`; }
    else              { taxEl.style.display  = 'none'; taxEl.textContent  = ''; }
    if (discount > 0) { discEl.style.display = ''; discEl.textContent = `- {{ __("pagination._disc") }} ${formatCurrency(discount)}`; }
    else              { discEl.style.display = 'none'; discEl.textContent = ''; }
    calculateCartSummary();
}

function computeItemTax(item) {
    const base = item.price * item.quantity;
    let total  = 0;
    if (!item.taxes || !item.taxes.length) return 0;
    item.taxes.forEach(t => {
        const rate = parseFloat(t.rate || 0);
        total += (t.type === 'percentage') ? base * (rate / 100) : rate * item.quantity;
    });
    return total;
}

function computeItemDiscount(item) {
    let total = 0;
    const subtotal = item.price * item.quantity;
    (item.promotions || []).forEach(promo => {
        if (promo.type === 'percentage')        total += subtotal * (promo.value / 100);
        else if (promo.type === 'fixed_amount') total += promo.value * item.quantity;
    });
    return total;
}

function updateCartItem(itemIndex) {
    const item = cart[itemIndex];
    const row  = document.querySelector(`tr[data-item-id="${item.id}"]`);
    if (!row) return;
    row.querySelector('.quantity-input').value = item.quantity;
    updateItemExtraLines(item.id);
}

function increaseQuantity(itemId) {
    const idx = cart.findIndex(i => i.id === itemId);
    if (idx > -1) {
        if (cart[idx].quantity < cart[idx].quantity_available) { cart[idx].quantity += 1; updateCartItem(idx); }
        else toastr['warning']('{{ __("pagination.max_quantity_reached") }}');
    }
}

function decreaseQuantity(itemId) {
    const idx = cart.findIndex(i => i.id === itemId);
    if (idx > -1 && cart[idx].quantity > 1) { cart[idx].quantity -= 1; updateCartItem(idx); }
}

function updateQuantity(itemId, newQuantity) {
    const qty = parseInt(newQuantity);
    const idx = cart.findIndex(i => i.id === itemId);
    if (idx === -1 || isNaN(qty)) return;
    if (qty > 0 && qty <= cart[idx].quantity_available) { cart[idx].quantity = qty; }
    else if (qty > cart[idx].quantity_available) { toastr['warning']('{{ __("pagination.max_quantity_reached") }}'); cart[idx].quantity = cart[idx].quantity_available; }
    updateCartItem(idx);
}

function removeFromCart(itemId) {
    const idx = cart.findIndex(i => i.id === itemId);
    if (idx > -1) {
        cart.splice(idx, 1);
        const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
        if (row) row.remove();
        calculateCartSummary();
        toastr['success']('{{ __("pagination.item_removed") }}');
    }
}

function clearCart() {
    cart = [];
    const tbody = getCartTbody();
    if (tbody) tbody.innerHTML = '';
    calculateCartSummary();
    toastr['success']('{{ __("pagination.cart_cleared") }}');
    // Clear resume state — safety net if cashier abandons a resumed order
    window.resumedOrderId     = null;
    window.resumedOrderNumber = null;
}

function calculateCartSummary() {
    const subtotal   = cart.reduce((s, i) => s + i.price * i.quantity, 0);
    const tax        = cart.reduce((s, i) => s + computeItemTax(i), 0);
    const discount   = cart.reduce((s, i) => s + computeItemDiscount(i), 0);
    const grandTotal = subtotal - discount + tax;
    document.querySelector('[data-kt-pos-element="total"]').textContent       = formatCurrency(subtotal);
    document.querySelector('[data-kt-pos-element="discount"]').textContent    = `-${formatCurrency(discount)}`;
    document.querySelector('[data-kt-pos-element="tax"]').textContent         = formatCurrency(tax);
    document.querySelector('[data-kt-pos-element="grant-total"]').textContent = formatCurrency(grandTotal);
}

document.addEventListener('DOMContentLoaded', () => calculateCartSummary());
</script>


{{-- ═══════════════════════════════════════════════
     2. SEARCH & DEPARTMENT FILTER
════════════════════════════════════════════════ --}}
<script>
function filterProductsAndVariants(searchTerm) {
    const searchValue   = searchTerm.toLowerCase().trim();
    const productItems  = document.querySelectorAll('.product-item');
    const allVariantItems = document.querySelectorAll('.variant-item');
    if (searchValue === '') {
        productItems.forEach(i => i.style.display = '');
        allVariantItems.forEach(i => i.style.display = '');
        clearSearchMessage();
        return;
    }
    let visibleProductCount = 0, lastVisiblePill = null;
    productItems.forEach(item => {
        const nameEl      = item.querySelector('span.fw-bold, span.text-gray-800');
        const productName = (nameEl ? nameEl.getAttribute('title') || nameEl.textContent : '').toLowerCase();
        const tabHref     = item.querySelector('a[href]')?.getAttribute('href');
        const tabPane     = tabHref ? document.getElementById(tabHref.replace('#', '')) : null;
        const anyMatch    = tabPane ? Array.from(tabPane.querySelectorAll('.variant-item')).some(v => (v.getAttribute('data-name') || '').includes(searchValue)) : false;
        if (productName.includes(searchValue) || anyMatch) { item.style.display = ''; visibleProductCount++; lastVisiblePill = item; }
        else item.style.display = 'none';
    });
    allVariantItems.forEach(item => {
        item.style.display = (item.getAttribute('data-name') || '').toLowerCase().includes(searchValue) ? '' : 'none';
    });
    if (visibleProductCount === 1 && lastVisiblePill) {
        const pillLink = lastVisiblePill.querySelector('a[data-bs-toggle="pill"]');
        if (pillLink) { window.bootstrap?.Tab ? bootstrap.Tab.getOrCreateInstance(pillLink).show() : pillLink.click(); }
    }
    showSearchMessage(visibleProductCount === 0);
}

function clearSearchMessage() { document.getElementById('pos-search-no-results')?.remove(); }

function showSearchMessage(show) {
    clearSearchMessage();
    if (!show) return;
    const container = document.getElementById('variantTabContent');
    if (!container) return;
    const div = document.createElement('div');
    div.id = 'pos-search-no-results'; div.className = 'text-center py-10';
    div.innerHTML = `<i class="ki-duotone ki-search-list fs-3x text-gray-400 mb-3 d-block"></i><span class="text-gray-500 fw-semibold fs-5">{{ __('pagination.no_products_match_search') }}</span>`;
    container.appendChild(div);
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tab-pane').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function () {
            const si = document.getElementById('variantSearchInput');
            if (si && si.value.trim()) filterProductsAndVariants(si.value);
        });
    });

    const departmentFilter = document.getElementById('departmentFilter');
    if (departmentFilter) {
        departmentFilter.addEventListener('change', function () {
            const sel  = this.value;
            const pl   = document.getElementById('productList');
            const tc   = document.getElementById('variantTabContent');
            const si   = document.getElementById('variantSearchInput');
            let hasVisible = false;
            document.querySelectorAll('.product-item').forEach(item => {
                const depts   = item.getAttribute('data-department').split(',');
                const matches = sel === '' || depts.includes(sel);
                item.style.display = matches ? '' : 'none';
                if (matches) hasVisible = true;
                const pillLink = item.querySelector('a[href]');
                const tabPane  = pillLink ? document.getElementById(pillLink.getAttribute('href').replace('#', '')) : null;
                if (tabPane) tabPane.querySelectorAll('.variant-item').forEach(v => v.style.display = matches ? '' : 'none');
            });
            pl.querySelector('.no-products-message')?.remove();
            document.getElementById('pos-dept-no-variants')?.remove();
            if (!hasVisible && sel !== '') {
                if (si) { si.value = ''; si.disabled = true; si.placeholder = '{{ __("pagination.no_products_in_department") }}'; }
                const mp = document.createElement('div'); mp.className = 'card-header pt-5 no-products-message';
                mp.innerHTML = '<h3 class="card-title fw-bold text-gray-800 fs-2qx">{{ __("pagination.no_products_in_department") }}</h3>';
                pl.appendChild(mp);
                if (tc) { const mt = document.createElement('div'); mt.id = 'pos-dept-no-variants'; mt.className = 'text-center py-10';
                    mt.innerHTML = `<i class="ki-duotone ki-category fs-3x text-gray-400 mb-3 d-block"><span class="path1"></span><span class="path2"></span></i><span class="text-gray-500 fw-semibold fs-5">{{ __("pagination.no_products_in_department") }}</span>`;
                    tc.appendChild(mt); }
                document.querySelectorAll('#variantTabContent .tab-pane').forEach(p => p.style.display = 'none');
            } else {
                if (si) { si.disabled = false; si.placeholder = '{{ __("auth._search") }} {{ __("pagination._variants") }}'; if (si.value.trim()) filterProductsAndVariants(si.value); }
                document.querySelectorAll('#variantTabContent .tab-pane').forEach(p => p.style.display = '');
                const activePill = document.querySelector('.product-item a.active');
                const activeParent = activePill?.closest('.product-item');
                if (activeParent && activeParent.style.display === 'none') {
                    const first = document.querySelector('.product-item:not([style*="none"]) a[data-bs-toggle="pill"]');
                    if (first) { window.bootstrap?.Tab ? bootstrap.Tab.getOrCreateInstance(first).show() : first.click(); }
                }
            }
        });
    }
});
</script>


{{-- ═══════════════════════════════════════════════
     3. PROCESS PAYMENT (creates/resumes order)
     
     KEY RULE: Do NOT clear window.resumedOrderId here.
     It must survive into openPaymentModal() and
     processSplitPayments() so they can recalculate
     the live cart total and send cart_updated=true.
     It is cleared by processSplitPayments() on success
     and by clearCart() as a safety net.
════════════════════════════════════════════════ --}}
<script>
function processPayment() {
    const submitButton = document.getElementById('processBill');

    if (cart.length === 0) { toastr['warning']('{{ __("pagination.cart_empty") }}'); return; }

    const radioExisting   = document.getElementById('cust-mode-existing');
    const radioNew        = document.getElementById('cust-mode-new');
    const custExistSelect = document.getElementById('cust-existing-select');
    const custNewInput    = document.getElementById('cust-new-input');
    let customerData = null;

    if (!radioExisting.checked && !radioNew.checked) { toastr['warning']('{{ __("pagination.please_select_customer_type") }}'); return; }
    if (radioExisting.checked) {
        if (!custExistSelect.value) { toastr['warning']('{{ __("pagination.please_select_existing_customer") }}'); return; }
        customerData = { type: 'existing', id: custExistSelect.value };
    } else {
        if (!custNewInput.value.trim()) { toastr['warning']('{{ __("pagination.please_enter_customer") }}'); return; }
        customerData = { type: 'new', name: custNewInput.value.trim() };
    }

    const cartData = {
        items: cart.map(item => {
            const itemSubtotal = item.price * item.quantity;
            const itemTaxes = (item.taxes || []).map(tax => {
                const rate = parseFloat(tax.rate || 0);
                const amt  = tax.type === 'percentage' ? itemSubtotal * (rate / 100) : rate * item.quantity;
                return { id: tax.id, name: tax.name, type: tax.type, rate: tax.rate, amount: amt };
            });
            const itemTaxTotal = itemTaxes.reduce((s, t) => s + t.amount, 0);
            let discountTotal  = 0;
            const appliedPromotions = [];
            (item.promotions || []).forEach(promo => {
                let d = 0;
                if (promo.type === 'percentage')   d = itemSubtotal * (promo.value / 100);
                if (promo.type === 'fixed_amount') d = promo.value * item.quantity;
                if (d > 0) { discountTotal += d; appliedPromotions.push({ id: promo.id, name: promo.name, type: promo.type, value: promo.value, discount: d }); }
            });
            return { variant_id: item.id, quantity: item.quantity, price: item.price, name: item.name,
                     subtotal: itemSubtotal, taxes: itemTaxes, tax_total: itemTaxTotal,
                     discount: discountTotal, promotions: appliedPromotions,
                     total: itemSubtotal - discountTotal + itemTaxTotal };
        }),
        customer: customerData,
        subtotal: cart.reduce((s, i) => s + i.price * i.quantity, 0),
        discount: cart.reduce((s, i) => s + computeItemDiscount(i), 0),
        tax:      cart.reduce((s, i) => s + computeItemTax(i), 0),
        total:    0,
    };
    cartData.total = cartData.subtotal - cartData.discount + cartData.tax;

    const formData = new FormData();
    formData.append('cart_data', JSON.stringify(cartData));
    if (window.resumedOrderId) {
        formData.append('resumed_order_id', window.resumedOrderId);
    }

    LiveBlade.toggleButtonLoading(submitButton, true);

    fetch('{{ route("orders.process-payment") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: formData,
    })
    .then(r => { if (!r.ok) { toastr['error']('{{ __("pagination.network_error") }}'); throw new Error('Not ok'); } return r.json(); })
    .then(data => {
        LiveBlade.toggleButtonLoading(submitButton, false);
        if (data.success) {
            toastr['success'](data.message);
            const enrichedCart       = JSON.parse(formData.get('cart_data'));
            enrichedCart.order_number = data.order_number;
            enrichedCart.customerName = data.customerName;
            enrichedCart.order_id     = data.order_id;

            // ── DO NOT clear resumedOrderId here ──────────────────────────
            // openPaymentModal() needs it to recalculate the live cart total.
            // processSplitPayments() needs it to send cart_updated=true.
            // Both will use it and then clear it themselves.

            if (typeof window.openPaymentModal === 'function') {
                window.openPaymentModal(enrichedCart);
            } else {
                console.error('openPaymentModal not found');
            }
        } else {
            toastr['error'](data.message || '{{ __("pagination.order_failed") }}');
        }
    })
    .catch(err => {
        LiveBlade.toggleButtonLoading(submitButton, false);
        console.error('Payment error:', err);
        toastr['error']('{{ __("pagination.payment_error") }}');
    });
}
</script>
<script>
function generateInvoice() {
    const submitButton = document.getElementById('generateInvoiceBtn');

    if (cart.length === 0) { toastr['warning']('{{ __("pagination.cart_empty") }}'); return; }

    const radioExisting   = document.getElementById('cust-mode-existing');
    const radioNew        = document.getElementById('cust-mode-new');
    const custExistSelect = document.getElementById('cust-existing-select');
    const custNewInput    = document.getElementById('cust-new-input');
    let customerData = null;

    if (!radioExisting.checked && !radioNew.checked) { toastr['warning']('{{ __("pagination.please_select_customer_type") }}'); return; }
    if (radioExisting.checked) {
        if (!custExistSelect.value) { toastr['warning']('{{ __("pagination.please_select_existing_customer") }}'); return; }
        customerData = { type: 'existing', id: custExistSelect.value };
    } else {
        if (!custNewInput.value.trim()) { toastr['warning']('{{ __("pagination.please_enter_customer") }}'); return; }
        customerData = { type: 'new', name: custNewInput.value.trim() };
    }

    // Same cart-to-payload shape as processPayment() — kept identical
    // so both endpoints receive data in the exact same format.
    const cartData = {
        items: cart.map(item => {
            const itemSubtotal = item.price * item.quantity;
            const itemTaxes = (item.taxes || []).map(tax => {
                const rate = parseFloat(tax.rate || 0);
                const amt  = tax.type === 'percentage' ? itemSubtotal * (rate / 100) : rate * item.quantity;
                return { id: tax.id, name: tax.name, type: tax.type, rate: tax.rate, amount: amt };
            });
            const itemTaxTotal = itemTaxes.reduce((s, t) => s + t.amount, 0);
            let discountTotal  = 0;
            const appliedPromotions = [];
            (item.promotions || []).forEach(promo => {
                let d = 0;
                if (promo.type === 'percentage')   d = itemSubtotal * (promo.value / 100);
                if (promo.type === 'fixed_amount') d = promo.value * item.quantity;
                if (d > 0) { discountTotal += d; appliedPromotions.push({ id: promo.id, name: promo.name, type: promo.type, value: promo.value, discount: d }); }
            });
            return { variant_id: item.id, quantity: item.quantity, price: item.price, name: item.name,
                     subtotal: itemSubtotal, taxes: itemTaxes, tax_total: itemTaxTotal,
                     discount: discountTotal, promotions: appliedPromotions,
                     total: itemSubtotal - discountTotal + itemTaxTotal };
        }),
        customer: customerData,
        subtotal: cart.reduce((s, i) => s + i.price * i.quantity, 0),
        discount: cart.reduce((s, i) => s + computeItemDiscount(i), 0),
        tax:      cart.reduce((s, i) => s + computeItemTax(i), 0),
        total:    0,
    };
    cartData.total = cartData.subtotal - cartData.discount + cartData.tax;

    const formData = new FormData();
    formData.append('cart_data', JSON.stringify(cartData));

    LiveBlade.toggleButtonLoading(submitButton, true);

    fetch('{{ route("orders.generate-invoice") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: formData,
    })
    .then(r => { if (!r.ok) { toastr['error']('{{ __("pagination.network_error") }}'); throw new Error('Not ok'); } return r.json(); })
    .then(data => {
        LiveBlade.toggleButtonLoading(submitButton, false);
        if (data.success) {
            toastr['success'](data.message);
            Swal.fire({
                icon: 'success',
                title: '{{ __("pagination.invoice_generated") }}',
                html: '{{ __("pagination.invoice_number") }}: <strong>' + data.invoice_number + '</strong>',
                confirmButtonText: 'OK',
            });
            if (typeof clearCart === 'function') clearCart();
        } else {
            toastr['error'](data.message || '{{ __("pagination.invoice_generation_failed") }}');
        }
    })
    .catch(err => {
        LiveBlade.toggleButtonLoading(submitButton, false);
        console.error('Invoice error:', err);
        toastr['error']('{{ __("pagination.invoice_generation_failed") }}');
    });
}
</script>

{{-- ═══════════════════════════════════════════════
     4. CURRENCY + RECEIPT GENERATOR + PAYMENT MODAL
════════════════════════════════════════════════ --}}
<script>
var POS_CURRENCY_SYM = '{{ currency_symbol() }}';

function posFmt(n) {
    return POS_CURRENCY_SYM + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// ── Receipt ───────────────────────────────────────────────
window.generateMultiPaymentReceipt = function (order) {
    var g  = id => document.getElementById(id);
    var now = new Date(), p2 = n => String(n).padStart(2,'0');
    var date = p2(now.getDate())+'/'+p2(now.getMonth()+1)+'/'+now.getFullYear();
    var time = p2(now.getHours())+':'+p2(now.getMinutes())+':'+p2(now.getSeconds());
    var orderNo = order.order_number || order.ref || order.id || Math.floor(Math.random()*900000+100000);

    g('rcpt-order-no').textContent = '#' + orderNo;
    g('rcpt-date').textContent     = date;
    g('rcpt-time').textContent     = time;

    var custName = order.customer_name || (order.customer && order.customer.name) || 'GUEST';
    g('rcpt-customer-banner').textContent = '👤 ' + String(custName).toUpperCase();
    g('rcpt-order-type').textContent = String(order.order_type || order.type || 'SALE').toUpperCase();

    if (order.table) { g('rcpt-table-row').classList.remove('d-none'); g('rcpt-table').textContent = order.table; }
    else             { g('rcpt-table-row').classList.add('d-none'); }

    var items = order.items || order.cart_items || [], itemCount = 0;
    var itemBody = g('rcpt-items-body');
    if (items.length) {
        itemBody.innerHTML = items.map(item => {
            var qty   = parseInt(item.quantity || item.qty || 1);
            var total = parseFloat(item.total || item.subtotal || ((item.price || item.unit_price || 0) * qty));
            itemCount += qty;
            return '<tr><td class="rcpt-item-qty">'+qty+'x</td><td class="rcpt-item-name">'+(item.name||item.item_name||'Item')+'</td><td class="rcpt-item-price">'+posFmt(total)+'</td></tr>'+
                   (item.note ? '<tr><td></td><td colspan="2" class="rcpt-item-sub">↳ '+item.note+'</td></tr>' : '');
        }).join('');
    } else {
        itemBody.innerHTML = '<tr><td colspan="3" style="color:#999;font-size:11px;text-align:center;">—</td></tr>';
    }
    g('rcpt-item-count').textContent = itemCount;

    var subtotal = parseFloat(order.subtotal||0), discount = parseFloat(order.discount||0);
    var tax = parseFloat(order.tax||0), grandTotal = parseFloat(order.total||0);
    g('rcpt-subtotal').textContent    = posFmt(subtotal);
    g('rcpt-grand-total').textContent = posFmt(grandTotal);
    if (discount > 0) { g('rcpt-discount-row').classList.remove('d-none'); g('rcpt-discount').textContent = '-'+posFmt(discount); }
    else              { g('rcpt-discount-row').classList.add('d-none'); }
    if (tax > 0)      { g('rcpt-tax-row').classList.remove('d-none');      g('rcpt-tax').textContent = posFmt(tax); }
    else              { g('rcpt-tax-row').classList.add('d-none'); }

    var payments = order.payments || [], payBody = g('rcpt-payments-body');
    var totalChange = parseFloat(order.total_change || 0);
    if (payments.length) {
        payBody.innerHTML = payments.map(p => {
            var label = (p.method_name||p.type||'Payment').toUpperCase();
            var acct  = p.account_number ? ' (****'+String(p.account_number).slice(-4)+')' : '';
            var rows  = '<tr><td class="rcpt-pay-label">'+label+acct+'</td><td class="rcpt-pay-value">'+posFmt(p.tendered||p.amount)+'</td></tr>';
            if (parseFloat(p.change||0) > 0.005) rows += '<tr><td class="rcpt-pay-label" style="padding-left:12px;color:#888;">↳ Applied</td><td class="rcpt-pay-value" style="color:#888;">'+posFmt(p.amount)+'</td></tr>';
            if (p.transaction_reference) rows += '<tr><td colspan="2" style="font-size:10px;color:#888;padding-left:12px;">Ref: '+p.transaction_reference+'</td></tr>';
            return rows;
        }).join('');
    } else {
        payBody.innerHTML = '<tr><td class="rcpt-pay-label">{{ strtoupper(__("pagination.paid")) }}</td><td class="rcpt-pay-value">'+posFmt(grandTotal)+'</td></tr>';
    }

    var changeBox = g('rcpt-change-box');
    if (totalChange > 0.005) { changeBox.classList.remove('d-none'); g('rcpt-change-value').textContent = posFmt(totalChange); }
    else                     { changeBox.classList.add('d-none'); }

    var barcodeVal = String(orderNo).replace(/\D/g,'').padEnd(8,'0').slice(0,12);
    g('rcpt-barcode').textContent = g('rcpt-barcode-num').textContent = barcodeVal;

    var receiptEl = document.getElementById('receiptModal');
    if (!receiptEl) { console.error('[Receipt] #receiptModal not found'); return; }
    bootstrap.Modal.getOrCreateInstance(receiptEl).show();
};

document.addEventListener('click', e => { if (e.target.closest('#rcpt-print-btn')) window.print(); });

// ── Payment modal IIFE ────────────────────────────────────
(function () {

    @if(isset($active_payment_methods))
        window.activePaymentMethods = @json($globalPaymentMethods ?? []);
    @endif

    var TYPES_WITH_REF = ['card','bank_account','mobile_money','digital_wallet','check','credit'];
    var CASH_TYPES     = ['cash'];
    var splitPayments  = [];
    var currentOrder   = null;

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
        if (remaining <= 0) { container.innerHTML = '<span class="text-success fw-semibold fs-7"><i class="ki-duotone ki-check-circle fs-4 me-1 text-success"><span class="path1"></span><span class="path2"></span></i>{{ __("pagination.fully_paid") }}</span>'; return; }
        var presets = [parseFloat(remaining.toFixed(2))];
        var rounds  = [1,2,5,10,20,50,100,200,500,1000,2000,5000,10000];
        for (var i = 0; i < rounds.length && presets.length < 7; i++) {
            var r = Math.ceil(remaining/rounds[i])*rounds[i];
            if (r > remaining && r <= remaining*5 && presets.indexOf(r)===-1) presets.push(r);
        }
        presets.sort((a,b) => a-b);
        container.innerHTML = presets.map(v => {
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
        [g('pm-account-'+type),g('pm-amount-'+type),g('pm-ref-'+type)].forEach(el => { if (el) el.value=''; });
        var calc = g('pm-cash-calc-'+type); if (calc) calc.classList.add('d-none');
        validateBtn(type); buildQuickAmounts(type); updateRemainingHint(type);
    }
    function renderTable() {
        var tbody = g('pm-splits-body'); if (!tbody) return;
        if (!splitPayments.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-muted"><i class="ki-duotone ki-wallet fs-3x mb-3 d-block opacity-20"><span class="path1"></span><span class="path2"></span></i><div class="fs-6 fw-semibold">{{ __("pagination.no_payments_added") }}</div></td></tr>';
            return;
        }
        tbody.innerHTML = splitPayments.map((p,i) =>
            '<tr><td class="ps-6"><div class="d-flex align-items-center gap-3"><span class="symbol symbol-35px symbol-circle"><span class="symbol-label bg-light-primary"><i class="ki-duotone '+getPaymentTypeIcon(p.type)+' fs-3 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></span></span>'+
            '<div><div class="fw-bold text-gray-800">'+formatPaymentType(p.type)+'</div><small class="text-muted">'+p.method_name+'</small></div></div></td>'+
            '<td><span class="badge badge-light-primary fs-8">'+(p.account_number||'N/A')+'</span>'+(p.transaction_ref?'<small class="d-block text-muted mt-1">Ref: '+p.transaction_ref+'</small>':'')+'</td>'+
            '<td class="text-end fw-bold text-gray-600">'+posFmt(p.tendered)+'</td>'+
            '<td class="text-end fw-bolder fs-5 text-gray-900">'+posFmt(p.amount)+'</td>'+
            '<td class="text-end">'+(p.change>0.005?'<span class="badge badge-light-success fw-bold fs-7">'+posFmt(p.change)+'</span>':'<span class="text-muted fs-7">—</span>')+'</td>'+
            '<td class="text-end pe-6"><button type="button" class="btn btn-sm btn-icon btn-light-danger pm-remove-btn" data-index="'+i+'"><i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i></button></td></tr>'
        ).join('');
        var count = g('pm-splits-count');
        if (count) count.textContent = splitPayments.length + ' {{ __("pagination.payments") }}';
    }
    function updateSummary() {
        if (!currentOrder) return;
        var totalApplied  = splitPayments.reduce((s,p) => s+p.amount,  0);
        var totalTendered = splitPayments.reduce((s,p) => s+p.tendered,0);
        var totalChange   = splitPayments.reduce((s,p) => s+p.change,  0);
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
        splitPayments.push({ id:Date.now()+Math.random(), type, method_id:accountId, method_name:accountData.name||'Unknown',
            account_number:accountData.account_number||'', tendered, amount:applied, change, transaction_ref:ref, account_data:accountData });
        renderTable(); updateSummary(); resetTab(type);
        if (change>0.005) toastr.info('<strong>{{ __("pagination.change_due") }}: '+posFmt(change)+'</strong>','{{ __("pagination.give_change_to_customer") }}',{timeOut:6000});
        if (getRemainingRaw()<=0.005) toastr.success('{{ __("pagination.payment_complete") }}');
    }
    function removePayment(index) { splitPayments.splice(index,1); renderTable(); updateSummary(); }

    // ── openPaymentModal ──────────────────────────────────────
    window.openPaymentModal = function (cartData) {

        // ── RESUME: recalculate total from LIVE cart ──────────
        // cartData came from the server with the OLD order total.
        // If items changed, live cart total is different.
        // Recalculate so modal shows correct amount and
        // processSplitPayments() client-check passes.
        if (window.resumedOrderId && typeof cart !== 'undefined' && cart.length > 0) {
            var liveSubtotal = cart.reduce((s,i) => s + i.price*i.quantity, 0);
            var liveDiscount = cart.reduce((s,i) => s + computeItemDiscount(i), 0);
            var liveTax      = cart.reduce((s,i) => s + computeItemTax(i), 0);
            cartData.subtotal = liveSubtotal;
            cartData.discount = liveDiscount;
            cartData.tax      = liveTax;
            cartData.total    = parseFloat((liveSubtotal - liveDiscount + liveTax).toFixed(2));
            console.log('[Resume] live total:', cartData.total);
        }

        currentOrder = cartData; window.currentOrder = cartData; splitPayments = [];

        g('pm-order-total').textContent = posFmt(cartData.total);
        g('pm-paid-amount').textContent = posFmt(0);
        g('pm-remaining').textContent   = posFmt(cartData.total);

        var refEl = g('pm-order-ref');
        if (refEl) refEl.textContent = cartData.order_number ? '#'+cartData.order_number : (cartData.ref ? '#'+cartData.ref : '—');

        document.querySelectorAll('.pm-account-select').forEach(s => s.value='');
        document.querySelectorAll('.pm-amount-input').forEach(i => i.value='');
        document.querySelectorAll('.pm-ref-input').forEach(i => i.value='');
        document.querySelectorAll('.pm-add-btn').forEach(b => { b.disabled=true; b.classList.remove('btn-primary'); b.classList.add('btn-secondary'); });
        document.querySelectorAll('[id^="pm-cash-calc-"]').forEach(el => el.classList.add('d-none'));

        var pb=g('pm-process-btn'); if (pb) pb.disabled=true;
        var wrap=g('pm-remaining-wrap'),remEl=g('pm-remaining');
        if (wrap)  { wrap.classList.add('bg-light-danger');  wrap.classList.remove('bg-light-success'); }
        if (remEl) { remEl.classList.add('text-danger');     remEl.classList.remove('text-success'); }
        renderTable();

        setTimeout(function () {
            const cashTabButton = document.querySelector('#pm-tab-cash');
            if (cashTabButton) {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tab) { new bootstrap.Tab(cashTabButton).show(); }
                else { cashTabButton.classList.add('active'); const cp = document.getElementById('pm-pane-cash'); if (cp) cp.classList.add('show','active'); }
                setTimeout(function () { var ai=g('pm-amount-cash'); if (ai) { ai.dispatchEvent(new Event('input',{bubbles:true})); ai.focus(); ai.select(); } }, 300);
            }
            var ap = qs('.tab-pane.active[data-payment-type]');
            if (ap) { var t=ap.dataset.paymentType; toggleRef(t); buildQuickAmounts(t); updateRemainingHint(t); }
        }, 150);

        bootstrap.Modal.getOrCreateInstance(g('paymentModal')).show();
    };

    // ── processSplitPayments ──────────────────────────────────
    window.processSplitPayments = function () {
        if (!currentOrder)         { toastr.error('{{ __("pagination.no_order_found") }}');      return; }
        if (!splitPayments.length) { toastr.warning('{{ __("pagination.no_payments_added") }}'); return; }

        var totalApplied = splitPayments.reduce((s,p) => s+p.amount, 0);
        if (Math.abs(currentOrder.total - totalApplied) > 0.01) {
            toastr.warning('{{ __("pagination.payment_total_mismatch") }}');
            return;
        }

        var btn = g('pm-process-btn');
        btn.setAttribute('data-kt-indicator','on'); btn.disabled=true;

        // ── Build cart snapshot for resumed orders ────────────
        var isResumed    = !! window.resumedOrderId;
        var cartSnapshot = null;

        if (isResumed && typeof cart !== 'undefined' && cart.length > 0) {
            cartSnapshot = {
                items: cart.map(item => {
                    var itemSubtotal = item.price * item.quantity;
                    var itemTaxes = (item.taxes||[]).map(tax => {
                        var rate = parseFloat(tax.rate||0);
                        var amt  = tax.type==='percentage' ? itemSubtotal*(rate/100) : rate*item.quantity;
                        return { id:tax.id, name:tax.name, type:tax.type, rate:tax.rate, amount:amt };
                    });
                    var taxTotal  = itemTaxes.reduce((s,t) => s+t.amount, 0);
                    var discTotal = 0; var promos = [];
                    (item.promotions||[]).forEach(promo => {
                        var d = 0;
                        if (promo.type==='percentage')   d = itemSubtotal*(promo.value/100);
                        if (promo.type==='fixed_amount') d = promo.value*item.quantity;
                        if (d>0) { discTotal+=d; promos.push({id:promo.id,name:promo.name,type:promo.type,value:promo.value,discount:d}); }
                    });
                    return { variant_id:item.id, name:item.name, price:item.price, quantity:item.quantity,
                             subtotal:itemSubtotal, taxes:itemTaxes, tax_total:taxTotal,
                             discount:discTotal, promotions:promos,
                             total:parseFloat((itemSubtotal-discTotal+taxTotal).toFixed(2)) };
                })
            };
        }

        // ── Explicit payload — no Object.assign spread ────────
        var payload = {
            order_id:       currentOrder.order_id || currentOrder.id,
            total_tendered: splitPayments.reduce((s,p) => s+p.tendered, 0),
            total_change:   splitPayments.reduce((s,p) => s+p.change,   0),
            cart_updated:   isResumed,
            updated_cart:   cartSnapshot,
            payments: splitPayments.map(p => ({
                payment_method_id:     p.method_id,
                amount:                p.amount,
                tendered:              p.tendered,
                change:                p.change,
                transaction_reference: p.transaction_ref || null,
                type:                  p.type,
                method_name:           p.method_name,
                account_number:        p.account_number,
            })),
        };

        console.log('[POS] processSplitPayments payload:', JSON.stringify({
            order_id: payload.order_id, order_total: currentOrder.total,
            total_applied: totalApplied, cart_updated: payload.cart_updated, payment_count: payload.payments.length,
        }));

        fetch('/orders/process-split-payment', {
            method:'POST',
            headers:{ 'Content-Type':'application/json', 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify(payload),
        })
        .then(r => { if (!r.ok) return r.json().then(body => { throw new Error(body.message||'{{ __("pagination.payment_error") }}'); }); return r.json(); })
        .then(data => {
            btn.removeAttribute('data-kt-indicator'); btn.disabled=false;
            if (data.success) {
                toastr.success(data.message||'{{ __("pagination.payment_completed") }}');

                // ── Clear resume state now that payment is done ───
                window.resumedOrderId     = null;
                window.resumedOrderNumber = null;

                var payModalEl = g('paymentModal');
                payModalEl.addEventListener('hidden.bs.modal', function onHidden() {
                    payModalEl.removeEventListener('hidden.bs.modal', onHidden);
                    window.generateMultiPaymentReceipt(data.order);
                });
                bootstrap.Modal.getInstance(payModalEl).hide();
                if (typeof clearCart === 'function') clearCart();
            } else {
                toastr.error(data.message||'{{ __("pagination.payment_failed") }}');
            }
        })
        .catch(err => {
            btn.removeAttribute('data-kt-indicator'); btn.disabled=false;
            toastr.error(err.message||'{{ __("pagination.payment_error") }}');
            console.error('[processSplitPayments] error:', err);
        });
    };

    document.addEventListener('click', function (e) {
        var qb=e.target.closest('.pm-quick-btn');
        if (qb) { var inp=g('pm-amount-'+qb.dataset.paymentType); if (inp) { inp.value=qb.dataset.quickAmount; inp.dispatchEvent(new Event('input',{bubbles:true})); inp.focus(); } return; }
        var addBtn=e.target.closest('.pm-add-btn'); if (addBtn&&!addBtn.disabled) { addPayment(addBtn.dataset.paymentType); return; }
        var remBtn=e.target.closest('.pm-remove-btn'); if (remBtn) { removePayment(parseInt(remBtn.dataset.index,10)); return; }
        if (e.target.closest('#pm-process-btn')) window.processSplitPayments();
    });
    document.addEventListener('change', e => { var sel=e.target.closest('.pm-account-select'); if (sel) validateBtn(sel.dataset.paymentType); });
    document.addEventListener('input',  e => { var inp=e.target.closest('.pm-amount-input'); if (!inp) return; validateBtn(inp.dataset.paymentType); updateCashCalc(inp.dataset.paymentType); });
    document.addEventListener('shown.bs.tab', e => { var type=e.target.dataset.paymentType; if (!type) return; toggleRef(type); buildQuickAmounts(type); validateBtn(type); updateRemainingHint(type); });

})();
</script>


{{-- ═══════════════════════════════════════════════
     5. PAYMENT TYPE HELPERS
════════════════════════════════════════════════ --}}
<script>
function getPaymentTypeColor(type) {
    return ({cash:'success',card:'primary',bank_account:'info',mobile_money:'warning',digital_wallet:'danger',check:'dark',credit:'secondary'})[type]||'primary';
}
function getPaymentTypeIcon(type) {
    return ({cash:'ki-wallet',card:'ki-credit-cart',bank_account:'ki-bank',mobile_money:'ki-phone',digital_wallet:'ki-wallet',check:'ki-document',credit:'ki-time',other:'ki-add-files'})[type]||'ki-wallet';
}
function formatPaymentType(type) {
    if (!type) return '';
    return type.split('_').map(w => w.charAt(0).toUpperCase()+w.slice(1)).join(' ');
}
</script>


{{-- ═══════════════════════════════════════════════
     6. CANCEL POS ORDER
════════════════════════════════════════════════ --}}
<script>
    function cancelPOSOrder(orderId) {
        Swal.fire({
            title: '{{ __("passwords.cancel_title") }}', text: '{{ __("passwords.cancel_confirmation") }}',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("passwords.cancel_order") }}', cancelButtonText: '{{ __("passwords.keep_order") }}',
            reverseButtons: true, showLoaderOnConfirm: true,
            preConfirm: () => $.ajax({ url: '/pos-cancel/'+orderId, method:'POST',
                data: { _token:'{{ csrf_token() }}', status:'cancelled' }, dataType:'json' })
                .then(r => { if (r.success===false) throw new Error(r.message||'Failed'); return r; })
                .catch(e => Swal.showValidationMessage(`Request failed: ${e.statusText||e.message}`))
        }).then(result => {
            if (result.isConfirmed && result.value?.success) {
                Swal.fire({ title:'{{ __("passwords.success") }}', text:result.value.message, icon:'success', confirmButtonColor:'#3085d6' })
                    .then(() => location.reload());
            }
        });
    }
</script>