
<script>
    function formatCurrency(value) {
        return Number(value).toLocaleString('en-US', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    }


    let cart = [];

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
                id: variant.id,
                name: variant.name,
                price: parseFloat(variant.price),
                image: variant.image,
                quantity: 1,
                quantity_available: variant.quantity_available,
                taxes: Array.isArray(variant.taxes) ? variant.taxes : [],
                promotions: Array.isArray(variant.promotions) ? variant.promotions : []

            };
            cart.push(cartItem);
            renderCartItem(cartItem);
            toastr['success']('{{ __("pagination.item_added") }}');
        }

        updateCartTotal();
        calculateCartSummary();
    }

    function renderCartItem(item) {
        const cartTable = document.querySelector('#kt_pos_form tbody');
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-kt-pos-element', '{{ __("pagination._item") }}');
        newRow.setAttribute('data-kt-pos-item-price', formatCurrency(item.price));
        newRow.setAttribute('data-item-id', item.id);

        const lineSubtotal = (item.price * item.quantity);
        newRow.innerHTML = `
            <td class="pe-0">
                <div class="d-flex align-items-center">
                    <img src="${item.image}" class="w-50px h-50px rounded-3 me-3" alt="${item.name}" />
                    <span class="fw-bold text-gray-800 cursor-pointer text-hover-primary fs-6 me-1">${item.name}</span>
                </div>
            </td>
            <td class="pe-0">
                <div class="position-relative d-flex align-items-center" data-kt-dialer="true" data-kt-dialer-min="1" data-kt-dialer-max="${item.quantity_available}" data-kt-dialer-step="1" data-kt-dialer-decimals="0">
                    <button type="button" class="btn btn-icon btn-sm btn-light btn-icon-gray-500" onclick="decreaseQuantity(${item.id})">
                        <i class="ki-duotone ki-minus fs-3x"></i>
                    </button>
                    <input type="text" class="form-control border-0 text-center px-0 fs-3 fw-bold text-gray-800 w-30px quantity-input"
                        placeholder="Amount" name="quantity_${item.id}"
                        value="${item.quantity}" onchange="updateQuantity(${item.id}, this.value)" />
                    <button type="button" class="btn btn-icon btn-sm btn-light btn-icon-gray-500" onclick="increaseQuantity(${item.id})">
                        <i class="ki-duotone ki-plus fs-3x"></i>
                    </button>
                </div>
            </td>
            <td class="text-end">
                <div class="d-flex align-items-center justify-content-end">
                    <div class="d-flex flex-column text-end">
                        <span class="fw-bold text-primary fs-2 item-total">${formatCurrency(lineSubtotal)}</span>
                        <small class="text-muted item-tax-line" style="display:none;"></small>
                        <small class="text-success item-discount-line" style="display:none;"></small>
                    </div>
                    <button type="button" class="btn btn-icon btn-sm btn-danger ms-2" onclick="removeFromCart(${item.id})">
                        <i class="bi bi-trash fs-2"></i>
                    </button>
                </div>
            </td>
        `;
        cartTable.appendChild(newRow);
        updateItemExtraLines(item.id);
    }

    function updateItemExtraLines(itemId) {
        const item = cart.find(i => i.id === itemId);
        const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
        if (!item || !row) return;

        const tax = computeItemTax(item);
        const discount = computeItemDiscount(item);

        const taxEl = row.querySelector('.item-tax-line');
        const discEl = row.querySelector('.item-discount-line');

        if (tax > 0) {
            taxEl.style.display = '';
            taxEl.textContent = `+ {{ __("pagination._tax") }} ${formatCurrency(tax)}`;
        } else {
            taxEl.style.display = 'none';
            taxEl.textContent = '';
        }

        if (discount > 0) {
            discEl.style.display = '';
            discEl.textContent = `- {{ __("pagination._disc") }} ${formatCurrency(discount)}`;
        } else {
            discEl.style.display = 'none';
            discEl.textContent = '';
        }

        // refresh subtotal display (base only)
        const itemTotalEl = row.querySelector('.item-total');
        const lineSubtotal = item.price * item.quantity;
        itemTotalEl.textContent = `${formatCurrency(lineSubtotal)}`;

        calculateCartSummary();
    }

    function computeItemTax(item) {
        const base = item.price * item.quantity;
        let taxTotal = 0;
        if (!item.taxes || !item.taxes.length) return 0;

        item.taxes.forEach(t => {
            const rate = parseFloat(t.rate || 0);
            if (t.type === 'percentage') {
                taxTotal += base * rate;
            } else {
                taxTotal += rate * item.quantity;
            }
        });
        return taxTotal;
    }

    function computeItemDiscount(item) {
        let discountTotal = 0;
        const subtotal = item.price * item.quantity;

        (item.promotions || []).forEach(promo => {
            if (promo.type === 'percentage') {
                discountTotal += subtotal * (promo.value / 100);
            } else if (promo.type === 'fixed_amount') {
                discountTotal += promo.value * item.quantity;
            } else if (promo.type === 'buy_x_get_y') {
                // Implement your custom logic if needed
                discountTotal += 0; 
            }
        });

        // console.log(discountTotal);

        return discountTotal;
    }

    function updateCartItem(itemIndex) {
        const item = cart[itemIndex];
        const row = document.querySelector(`tr[data-item-id="${item.id}"]`);
        if (!row) return;

        const quantityInput = row.querySelector('.quantity-input');
        quantityInput.value = item.quantity;

        updateItemExtraLines(item.id);
    }

    function increaseQuantity(itemId) {
        const idx = cart.findIndex(i => i.id === itemId);
        if (idx > -1) {
            if (cart[idx].quantity < cart[idx].quantity_available) {
                cart[idx].quantity += 1;
                updateCartItem(idx);
                updateCartTotal();
            } else {
                toastr['warning']('{{ __("pagination.max_quantity_reached") }}');
            }
        }
    }

    function decreaseQuantity(itemId) {
        const idx = cart.findIndex(i => i.id === itemId);
        if (idx > -1 && cart[idx].quantity > 1) {
            cart[idx].quantity -= 1;
            updateCartItem(idx);
            updateCartTotal();
        }
    }

    function updateQuantity(itemId, newQuantity) {
        const qty = parseInt(newQuantity);
        const idx = cart.findIndex(i => i.id === itemId);
        if (idx > -1 && !isNaN(qty)) {
            if (qty > 0 && qty <= cart[idx].quantity_available) {
                cart[idx].quantity = qty;
                updateCartItem(idx);
                updateCartTotal();
            } else if (qty > cart[idx].quantity_available) {
                toastr['warning']('{{ __("pagination.max_quantity_reached") }}');
                cart[idx].quantity = cart[idx].quantity_available;
                updateCartItem(idx);
                updateCartTotal();
            }
        }
    }

    function removeFromCart(itemId) {
        const idx = cart.findIndex(i => i.id === itemId);
        if (idx > -1) {
            cart.splice(idx, 1);
            const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
            if (row) row.remove();
            updateCartTotal();
            toastr['success']('{{ __("pagination.item_removed") }}');
        }
    }

    function clearCart() {
        cart = [];
        const cartTable = document.querySelector('#kt_pos_form tbody');
        cartTable.innerHTML = '';
        updateCartTotal();
        toastr['success']('{{ __("pagination.cart_cleared") }}');
    }

    // === Totals ===
    function calculateCartSummary() {
        const subtotal = cart.reduce((sum, i) => sum + (i.price * i.quantity), 0);
        const tax = cart.reduce((sum, i) => sum + computeItemTax(i), 0);
        const discount = cart.reduce((sum, i) => sum + computeItemDiscount(i), 0);
        const grandTotal = subtotal - discount + tax;

        document.querySelector('[data-kt-pos-element="total"]').textContent = `${formatCurrency(subtotal)}`;
        document.querySelector('[data-kt-pos-element="discount"]').textContent = `-${formatCurrency(discount)}`;
        document.querySelector('[data-kt-pos-element="tax"]').textContent = `${formatCurrency(tax)}`;
        document.querySelector('[data-kt-pos-element="grant-total"]').textContent = `${formatCurrency(grandTotal)}`;
    }

    function updateCartTotal() {
        calculateCartSummary();
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateCartTotal();
        const clearBtn = document.querySelector('[onclick="clearCart()"]');
        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                clearCart();
            });
        }
    });
</script>





<script>
    
    function processPayment() {
        const submitButton = document.getElementById('processBill');

        // Check if cart is empty
        if (cart.length === 0) {
            toastr['warning']('{{ __("pagination.cart_empty") }}');
            console.log('Cart is empty - cannot process payment');
            return;
        }

        // --- Check Customer Selection ---
        const radioExisting  = document.getElementById('cust-mode-existing');
        const radioNew       = document.getElementById('cust-mode-new');
        const custExistSelect = document.getElementById('cust-existing-select');
        const custNewInput   = document.getElementById('cust-new-input');

        let customerData = null;

        if (!radioExisting.checked && !radioNew.checked) {
            toastr['warning']('{{ __("pagination.please_select_customer_type") }}');
            return;
        }

        if (radioExisting.checked) {
            if (custExistSelect.value === "") {
                toastr['warning']('{{ __("pagination.please_select_existing_customer") }}');
                return;
            }
            customerData = {
                type: "existing",
                id: custExistSelect.value
            };
        } else if (radioNew.checked) {
            if (custNewInput.value.trim() === "") {
                toastr['warning']('{{ __("pagination.please_enter_customer") }}');
                return;
            }
            customerData = {
                type: "new",
                name: custNewInput.value.trim()
            };
        }

        // console.log(customerData);

        // Get selected payment method
        // const selectedMethod = document.querySelector('input[name="method"]:checked');
        // const paymentMethod = selectedMethod ? selectedMethod.value : 'cash';

        // console.log('Selected payment method:', paymentMethod);


        const cartData = {
            items: cart.map(item => {
                const itemSubtotal = item.price * item.quantity;

                // Taxes per item
                const itemTaxes = (item.taxes || []).map(tax => {
                    let taxAmount = 0;
                    const rate = parseFloat(tax.rate || 0);

                    if (tax.type === 'percentage') {
                        taxAmount = itemSubtotal * rate;
                    } else {
                        taxAmount = rate * item.quantity;
                    }

                    return {
                        id: tax.id,
                        name: tax.name,
                        type: tax.type,
                        rate: tax.rate,
                        amount: taxAmount
                    };
                });

                const itemTaxTotal = itemTaxes.reduce((sum, t) => sum + t.amount, 0);

                // Discounts & promotions
                let discountTotal = 0;
                const appliedPromotions = [];

                (item.promotions || []).forEach(promo => {
                    let promoDiscount = 0;
                    if (promo.type === 'percentage') {
                        promoDiscount = itemSubtotal * (promo.value / 100);
                    } else if (promo.type === 'fixed_amount') {
                        // fixed per item = (price - value) * qty  (if value is a price override)
                        promoDiscount = (item.price - promo.value) * item.quantity;
                    } else if (promo.type === 'buy_x_get_y') {
                        // Example: buy 2 get 1 free
                        promoDiscount = 0;
                    }

                    if (promoDiscount > 0) {
                        discountTotal += promoDiscount;
                        appliedPromotions.push({
                            id: promo.id,
                            name: promo.name,
                            type: promo.type,
                            value: promo.value,
                            discount: promoDiscount
                        });
                    }
                });

                return {
                    variant_id: item.id,
                    quantity: item.quantity,
                    price: item.price,
                    name: item.name,
                    subtotal: itemSubtotal,
                    taxes: itemTaxes,
                    tax_total: itemTaxTotal,
                    discount: discountTotal,
                    promotions: appliedPromotions, // 👈 attach applied promotions here
                    total: itemSubtotal - discountTotal + itemTaxTotal
                };
            }),

            customer: customerData,
            // payment_method: paymentMethod,

            subtotal: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
            discount: cart.reduce((sum, item) => sum + computeItemDiscount(item), 0),
            tax: cart.reduce((sum, item) => sum + computeItemTax(item), 0),
            total: 0
        };

        cartData.total = cartData.subtotal - cartData.discount + cartData.tax;
        // console.log(cartData);


        
        // Start loading
        LiveBlade.toggleButtonLoading(submitButton, true);

        
        const formData = new FormData();
        formData.append('cart_data', JSON.stringify(cartData));
        
        fetch('{{ route("orders.process-payment") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => {
            // Check if response is OK
            if (!response.ok) {
                toastr['error']('{{ __("pagination.network_error") }}');
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Stop loading
            LiveBlade.toggleButtonLoading(submitButton, false);
            
            if (data.success) {
                toastr['success'](data.message);

                const cartDataRaw = formData.get("cart_data");  // JSON string
                const cartData = JSON.parse(cartDataRaw);       // Convert to object
                cartData.order_number = data.order_number;     
                cartData.customerName = data.customerName;    
                cartData.order_id = data.order_id; 
                
                // Open modal based on payment method
                openPaymentModal(cartData);
                
            } else {
                // Show error toast from backend response
                const message = data.message || '{{ __("pagination.order_failed") }}';
                toastr['success'](message);
            }
        })
        .catch(error => {
            // Stop loading
            LiveBlade.toggleButtonLoading(submitButton, false);
            console.error('Payment error:', error);
            toastr['error']('{{ __("pagination.payment_error") }}');
        });

        
    }

</script>
<script>
    function getPaymentTypeColor(type) {
        const colors = {
            'cash': 'success',
            'card': 'primary',
            'bank_account': 'info',
            'mobile_money': 'warning',
            'digital_wallet': 'danger',
            'check': 'dark',
            'credit': 'secondary',
            'other': 'secondary'
        };
        return colors[type] || 'primary';
    }

    function getPaymentTypeIcon(type) {
        const icons = {
            'cash': 'ki-wallet',
            'card': 'ki-credit-cart',
            'bank_account': 'ki-bank',
            'mobile_money': 'ki-phone',
            'digital_wallet': 'ki-wallet',
            'check': 'ki-document',
            'credit': 'ki-time',
            'other': 'ki-add-files'
        };
        return icons[type] || 'ki-wallet';
    }

    function formatPaymentType(type) {
        if (!type) return '';
        return type.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }
</script>





<!-- Complete Orders -->


<script>

    function printOrder(orderId) {
        console.log('Printing order:', orderId);
        
        const printElement = document.getElementById('printableOrder' + orderId);
        
        if (!printElement) {
            console.error('Print element not found for order ID:', orderId);
            toastr.error('Print content not found for this order.');
            return;
        }
        
        const printContent = printElement.innerHTML;
        const printWindow = window.open('', '_blank', 'width=1000,height=800,scrollbars=yes');
        
        if (!printWindow) {
            toastr.warning('Please allow popups to print this order.');
            return;
        }
        
        // Create an enhanced print document with Metronic-inspired styling
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <title>Invoice - Order #${orderId}</title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    /* Metronic Color Palette */
                    :root {
                        --kt-primary: #009ef7;
                        --kt-primary-light: #f1faff;
                        --kt-success: #50cd89;
                        --kt-success-light: #e8fff3;
                        --kt-danger: #f1416c;
                        --kt-danger-light: #fff5f8;
                        --kt-warning: #ffc700;
                        --kt-info: #7239ea;
                        --kt-dark: #181c32;
                        --kt-gray-100: #f9f9f9;
                        --kt-gray-200: #f1f1f2;
                        --kt-gray-300: #e4e6ef;
                        --kt-gray-400: #b5b5c3;
                        --kt-gray-500: #a1a5b7;
                        --kt-gray-600: #7e8299;
                        --kt-gray-700: #5e6278;
                        --kt-gray-800: #3f4254;
                    }
                    
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    
                    body {
                        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        line-height: 1.6;
                        color: var(--kt-dark);
                        background: #ffffff;
                        padding: 30px 20px;
                    }
                    
                    .invoice-container {
                        max-width: 900px;
                        margin: 0 auto;
                        background: white;
                        box-shadow: 0 0 40px rgba(0, 0, 0, 0.08);
                        border-radius: 12px;
                        overflow: hidden;
                    }
                    
                    /* Header Section */
                    .invoice-header {
                        background: linear-gradient(135deg, var(--kt-primary) 0%, #0095e8 100%);
                        color: white;
                        padding: 40px;
                        position: relative;
                        overflow: hidden;
                    }
                    
                    .invoice-header::before {
                        content: '';
                        position: absolute;
                        top: -50%;
                        right: -10%;
                        width: 400px;
                        height: 400px;
                        background: rgba(255, 255, 255, 0.1);
                        border-radius: 50%;
                    }
                    
                    .header-content {
                        position: relative;
                        z-index: 1;
                    }
                    
                    .invoice-title {
                        font-size: 32px;
                        font-weight: 700;
                        margin-bottom: 8px;
                        letter-spacing: -0.5px;
                    }
                    
                    .invoice-subtitle {
                        font-size: 18px;
                        font-weight: 500;
                        opacity: 0.95;
                    }
                    
                    /* Main Content */
                    .invoice-body {
                        padding: 40px;
                    }
                    
                    /* Info Grid */
                    .info-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 30px;
                        margin-bottom: 35px;
                        padding-bottom: 30px;
                        border-bottom: 2px dashed var(--kt-gray-300);
                    }
                    
                    .info-card {
                        background: var(--kt-gray-100);
                        border-radius: 8px;
                        padding: 20px;
                        border-left: 4px solid var(--kt-primary);
                    }
                    
                    .info-label {
                        color: var(--kt-gray-600);
                        font-size: 12px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        margin-bottom: 6px;
                    }
                    
                    .info-value {
                        color: var(--kt-dark);
                        font-size: 15px;
                        font-weight: 600;
                        margin-bottom: 12px;
                    }
                    
                    .info-value:last-child {
                        margin-bottom: 0;
                    }
                    
                    /* Payment Section */
                    .payment-section {
                        background: linear-gradient(135deg, var(--kt-success-light) 0%, #d4f8e8 100%);
                        border: 1px solid rgba(80, 205, 137, 0.3);
                        border-radius: 10px;
                        padding: 25px;
                        margin-bottom: 35px;
                    }
                    
                    .payment-header {
                        display: flex;
                        align-items: center;
                        margin-bottom: 20px;
                        padding-bottom: 15px;
                        border-bottom: 1px dashed rgba(80, 205, 137, 0.3);
                    }
                    
                    .payment-icon {
                        width: 40px;
                        height: 40px;
                        background: var(--kt-success);
                        border-radius: 8px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-right: 12px;
                        color: white;
                        font-weight: 700;
                        font-size: 18px;
                    }
                    
                    .payment-title {
                        color: var(--kt-dark);
                        font-size: 18px;
                        font-weight: 700;
                    }
                    
                    .payment-grid {
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 20px;
                    }
                    
                    .payment-item {
                        background: white;
                        border-radius: 6px;
                        padding: 15px;
                    }
                    
                    .payment-label {
                        color: var(--kt-gray-600);
                        font-size: 11px;
                        font-weight: 600;
                        text-transform: uppercase;
                        margin-bottom: 4px;
                    }
                    
                    .payment-value {
                        color: var(--kt-dark);
                        font-size: 14px;
                        font-weight: 700;
                    }
                    
                    .payment-type-badge {
                        display: inline-block;
                        background: var(--kt-success);
                        color: white;
                        padding: 4px 10px;
                        border-radius: 4px;
                        font-size: 11px;
                        font-weight: 600;
                        text-transform: uppercase;
                    }
                    
                    /* Items Table */
                    .items-section {
                        margin-bottom: 35px;
                    }
                    
                    .section-title {
                        color: var(--kt-dark);
                        font-size: 18px;
                        font-weight: 700;
                        margin-bottom: 20px;
                        display: flex;
                        align-items: center;
                    }
                    
                    .section-title::before {
                        content: '';
                        width: 4px;
                        height: 24px;
                        background: var(--kt-primary);
                        border-radius: 2px;
                        margin-right: 12px;
                    }
                    
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        background: white;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                    }
                    
                    thead {
                        background: var(--kt-gray-100);
                    }
                    
                    th {
                        padding: 14px 12px;
                        text-align: left;
                        color: var(--kt-gray-700);
                        font-weight: 700;
                        font-size: 11px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        border-bottom: 2px solid var(--kt-gray-300);
                    }
                    
                    td {
                        padding: 14px 12px;
                        color: var(--kt-dark);
                        font-size: 13px;
                        border-bottom: 1px solid var(--kt-gray-200);
                    }
                    
                    tbody tr:last-child td {
                        border-bottom: none;
                    }
                    
                    tbody tr:hover {
                        background: var(--kt-gray-100);
                    }
                    
                    .text-center {
                        text-align: center;
                    }
                    
                    .text-right {
                        text-align: right;
                    }
                    
                    .item-name {
                        font-weight: 600;
                        color: var(--kt-dark);
                    }
                    
                    .item-sku {
                        background: var(--kt-primary-light);
                        color: var(--kt-primary);
                        padding: 3px 8px;
                        border-radius: 4px;
                        font-size: 11px;
                        font-weight: 600;
                        display: inline-block;
                    }
                    
                    /* Summary Section */
                    .summary-section {
                        display: flex;
                        justify-content: flex-end;
                    }
                    
                    .summary-box {
                        width: 380px;
                        background: var(--kt-gray-100);
                        border-radius: 10px;
                        padding: 25px;
                        border: 1px solid var(--kt-gray-300);
                    }
                    
                    .summary-row {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 10px 0;
                    }
                    
                    .summary-row.total {
                        border-top: 2px dashed var(--kt-gray-400);
                        margin-top: 10px;
                        padding-top: 15px;
                    }
                    
                    .summary-label {
                        color: var(--kt-gray-700);
                        font-size: 13px;
                        font-weight: 500;
                    }
                    
                    .summary-value {
                        font-weight: 700;
                        font-size: 14px;
                        color: var(--kt-dark);
                    }
                    
                    .total .summary-label {
                        font-size: 16px;
                        font-weight: 700;
                        color: var(--kt-dark);
                    }
                    
                    .total .summary-value {
                        font-size: 24px;
                        color: var(--kt-primary);
                    }
                    
                    .text-danger {
                        color: var(--kt-danger) !important;
                    }
                    
                    .text-success {
                        color: var(--kt-success) !important;
                    }
                    
                    /* Footer */
                    .invoice-footer {
                        background: var(--kt-gray-100);
                        padding: 25px 40px;
                        margin-top: 40px;
                        text-align: center;
                        color: var(--kt-gray-600);
                        font-size: 13px;
                    }
                    
                    .footer-thank-you {
                        font-size: 16px;
                        font-weight: 600;
                        color: var(--kt-dark);
                        margin-bottom: 8px;
                    }
                    
                    /* Print Buttons */
                    .print-actions {
                        text-align: center;
                        margin: 30px 0;
                        padding: 0 40px;
                    }
                    
                    .btn {
                        display: inline-block;
                        padding: 12px 28px;
                        border: none;
                        border-radius: 8px;
                        font-weight: 600;
                        font-size: 14px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        text-decoration: none;
                        margin: 0 6px;
                    }
                    
                    .btn-primary {
                        background: var(--kt-primary);
                        color: white;
                        box-shadow: 0 4px 12px rgba(0, 158, 247, 0.3);
                    }
                    
                    .btn-primary:hover {
                        background: #0095e8;
                        transform: translateY(-2px);
                        box-shadow: 0 6px 16px rgba(0, 158, 247, 0.4);
                    }
                    
                    .btn-secondary {
                        background: var(--kt-gray-200);
                        color: var(--kt-gray-700);
                    }
                    
                    .btn-secondary:hover {
                        background: var(--kt-gray-300);
                        color: var(--kt-dark);
                    }
                    
                    /* Print Styles */
                    @media print {
                        body {
                            padding: 0;
                            background: white;
                        }
                        
                        .invoice-container {
                            box-shadow: none;
                            max-width: 100%;
                        }
                        
                        .no-print {
                            display: none !important;
                        }
                        
                        .invoice-header {
                            print-color-adjust: exact;
                            -webkit-print-color-adjust: exact;
                        }
                        
                        .payment-section {
                            print-color-adjust: exact;
                            -webkit-print-color-adjust: exact;
                        }
                        
                        tbody tr:hover {
                            background: transparent;
                        }
                    }
                    
                    @page {
                        size: A4;
                        margin: 15mm;
                    }
                </style>
            </head>
            <body>
                <div class="invoice-container">
                    ${printContent}
                </div>
                
                <div class="print-actions no-print">
                    <button class="btn btn-primary" onclick="window.print()">
                        🖨️ Print Invoice
                    </button>
                    <button class="btn btn-secondary" onclick="window.close()">
                        ✕ Close Window
                    </button>
                </div>
                
                <script>
                    window.onload = function() {
                        window.focus();
                        // Uncomment to auto-print
                        // setTimeout(() => window.print(), 300);
                    };
                <\/script>
            </body>
            </html>
        `);
        
        printWindow.document.close();
    }

    // Initialize tooltips and toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Handle toggle animation
        const toggleButtons = document.querySelectorAll('.toggle-items');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                if (target) {
                    this.classList.toggle('collapsed');
                }
            });
        });
    });
</script>


<script>
    function cancelPOSOrder(orderId) {
        const updateRoute = '/pos-cancel/' + orderId;
        
        Swal.fire({
            title: '{{ __("passwords.cancel_title") }}',
            text: '{{ __("passwords.cancel_confirmation") }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '{{ __("passwords.cancel_order") }}',
            cancelButtonText: '{{ __("passwords.keep_order") }}',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: updateRoute,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: 'cancelled'
                    },
                    dataType: 'json'
                })
                .then(response => {
                    // Check if response is successful
                    if (response.success === false) {
                        throw new Error(response.message || 'Failed to cancel order');
                    }
                    return response;
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `Request failed: ${error.statusText || error.message}`
                    );
                });
            }
        }).then((result) => {
            if (result.isConfirmed && result.value && result.value.success) {
                // Show success message and reload
                Swal.fire({
                    title: '{{ __("passwords.success") }}',
                    text: result.value.message,
                    icon: 'success',
                    confirmButtonColor: '#3085d6',
                }).then(() => {
                    // Reload the page to see updated status
                    location.reload();
                    
                    // OR redirect to orders index if needed
                    // if (result.value.redirect) {
                    //     window.location.href = result.value.redirect;
                    // } else {
                    //     location.reload();
                    // }
                });
            }
        });
    }
</script>


