<script>
    function formatCurrency(value) {
        return Number(value).toLocaleString('en-US', { 
            minimumFractionDigits: 2, 
            maximumFractionDigits: 2 
        });
    }

    let cart = [];

    // ─── Single, guaranteed reference to the cart tbody ───────────────────────
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
            renderCartItem(cartItem);  // renders + calls updateItemExtraLines internally
            toastr['success']('{{ __("pagination.item_added") }}');
        }

    }

    function renderCartItem(item) {
        const cartTbody = getCartTbody();
        if (!cartTbody) {
            console.error('[POS] #pos-cart-tbody not found – check your HTML.');
            return;
        }

        const lineSubtotal = item.price * item.quantity;
        const newRow = document.createElement('tr');
        newRow.setAttribute('data-item-id', item.id);

        newRow.innerHTML = `
            <td class="pe-0">
                <div class="d-flex align-items-center gap-3">
                    <img src="${item.image}"
                         class="w-50px h-50px rounded-3 object-fit-cover border"
                         alt="${item.name}" />
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-gray-800 text-hover-primary fs-6">${item.name}</span>
                    </div>
                </div>
            </td>
            <td class="pe-0">
                <div class="d-flex align-items-center gap-1">
                    <button type="button"
                            class="btn btn-icon btn-sm btn-light btn-icon-gray-500 w-30px h-30px"
                            onclick="decreaseQuantity(${item.id})">
                        <i class="ki-duotone ki-minus fs-4"></i>
                    </button>
                    <input type="text"
                           class="form-control border-0 text-center px-0 fs-5 fw-bold text-gray-800 w-35px quantity-input"
                           name="quantity_${item.id}"
                           value="${item.quantity}"
                           onchange="updateQuantity(${item.id}, this.value)" />
                    <button type="button"
                            class="btn btn-icon btn-sm btn-light btn-icon-gray-500 w-30px h-30px"
                            onclick="increaseQuantity(${item.id})">
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
                    <button type="button"
                            class="btn btn-icon btn-sm btn-light-danger"
                            onclick="removeFromCart(${item.id})">
                        <i class="bi bi-trash fs-5"></i>
                    </button>
                </div>
            </td>
        `;

        cartTbody.appendChild(newRow);

        // Update tax/discount lines AFTER the row is in the DOM
        updateItemExtraLines(item.id);
    }

    function updateItemExtraLines(itemId) {
        const item = cart.find(i => i.id === itemId);
        const row  = document.querySelector(`tr[data-item-id="${itemId}"]`);
        if (!item || !row) return;

        const tax      = computeItemTax(item);
        const discount = computeItemDiscount(item);
        const taxEl    = row.querySelector('.item-tax-line');
        const discEl   = row.querySelector('.item-discount-line');
        const totalEl  = row.querySelector('.item-total');

        // Base subtotal (always shown)
        totalEl.textContent = formatCurrency(item.price * item.quantity);

        if (tax > 0) {
            taxEl.style.display = '';
            taxEl.textContent = `+ {{ __("pagination._tax") }} ${formatCurrency(tax)}`;
        } else {
            taxEl.style.display = 'none';
            taxEl.textContent   = '';
        }

        if (discount > 0) {
            discEl.style.display = '';
            discEl.textContent = `- {{ __("pagination._disc") }} ${formatCurrency(discount)}`;
        } else {
            discEl.style.display = 'none';
            discEl.textContent   = '';
        }

        calculateCartSummary();
    }

    function computeItemTax(item) {
        const base = item.price * item.quantity;
        let taxTotal = 0;
        if (!item.taxes || !item.taxes.length) return 0;
        item.taxes.forEach(t => {
            const rate = parseFloat(t.rate || 0);
            taxTotal += (t.type === 'percentage') ? base * (rate / 100) : rate * item.quantity;
        });
        return taxTotal;
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
            if (cart[idx].quantity < cart[idx].quantity_available) {
                cart[idx].quantity += 1;
                updateCartItem(idx);
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
        }
    }

    function updateQuantity(itemId, newQuantity) {
        const qty = parseInt(newQuantity);
        const idx = cart.findIndex(i => i.id === itemId);
        if (idx === -1 || isNaN(qty)) return;

        if (qty > 0 && qty <= cart[idx].quantity_available) {
            cart[idx].quantity = qty;
        } else if (qty > cart[idx].quantity_available) {
            toastr['warning']('{{ __("pagination.max_quantity_reached") }}');
            cart[idx].quantity = cart[idx].quantity_available;
        }
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

    document.addEventListener('DOMContentLoaded', function () {
        calculateCartSummary();
    });
</script>

<script>
    /**
     * POS Unified Search
     * Searches both product pills (nav) AND variant cards simultaneously.
     * - Typing filters product pills by name
     * - If only one product matches, auto-switches to its tab
     * - Variant cards within each tab are also filtered by name
     */
    function filterProductsAndVariants(searchTerm) {
        const searchValue = searchTerm.toLowerCase().trim();
        const productItems = document.querySelectorAll('.product-item');
        const allVariantItems = document.querySelectorAll('.variant-item');

        // ── Empty search: show everything ──────────────────────────────────────
        if (searchValue === '') {
            productItems.forEach(item => item.style.display = '');
            allVariantItems.forEach(item => item.style.display = '');
            clearSearchMessage();
            return;
        }

        // ── Step 1: Filter product pills by name ───────────────────────────────
        let visibleProductCount = 0;
        let lastVisiblePill = null;

        productItems.forEach(item => {
            // Product name lives inside the pill's span.fw-bold
            const nameEl = item.querySelector('span.fw-bold, span.text-gray-800');
            const productName = (nameEl ? nameEl.getAttribute('title') || nameEl.textContent : '').toLowerCase();

            // Also check if any variant inside this product's tab matches
            const tabHref = item.querySelector('a[href]')?.getAttribute('href'); // e.g. #kt_pos_5
            const tabId   = tabHref ? tabHref.replace('#', '') : null;
            const tabPane = tabId ? document.getElementById(tabId) : null;
            const variantsInTab = tabPane ? tabPane.querySelectorAll('.variant-item') : [];
            const anyVariantMatches = Array.from(variantsInTab).some(v =>
                (v.getAttribute('data-name') || '').includes(searchValue)
            );

            if (productName.includes(searchValue) || anyVariantMatches) {
                item.style.display = '';
                visibleProductCount++;
                lastVisiblePill = item;
            } else {
                item.style.display = 'none';
            }
        });

        // ── Step 2: Filter variant cards ───────────────────────────────────────
        allVariantItems.forEach(item => {
            const variantName = (item.getAttribute('data-name') || '').toLowerCase();
            item.style.display = variantName.includes(searchValue) ? '' : 'none';
        });

        // ── Step 3: Auto-switch tab if only one product pill is visible ────────
        if (visibleProductCount === 1 && lastVisiblePill) {
            const pillLink = lastVisiblePill.querySelector('a[data-bs-toggle="pill"]');
            if (pillLink) {
                // Use Bootstrap's Tab API if available, otherwise simulate click
                if (window.bootstrap && window.bootstrap.Tab) {
                    bootstrap.Tab.getOrCreateInstance(pillLink).show();
                } else {
                    pillLink.click();
                }
            }
        }

        // ── Step 4: Show "no results" if nothing matched ───────────────────────
        showSearchMessage(visibleProductCount === 0);
    }

    function clearSearchMessage() {
        const msg = document.getElementById('pos-search-no-results');
        if (msg) msg.remove();
    }

    function showSearchMessage(show) {
        clearSearchMessage();
        if (!show) return;
        const container = document.getElementById('variantTabContent');
        if (!container) return;
        const div = document.createElement('div');
        div.id = 'pos-search-no-results';
        div.className = 'text-center py-10';
        div.innerHTML = `
            <i class="ki-duotone ki-search-list fs-3x text-gray-400 mb-3 d-block"></i>
            <span class="text-gray-500 fw-semibold fs-5">{{ __('pagination.no_products_match_search') }}</span>
        `;
        container.appendChild(div);
    }

    // Re-apply search when switching tabs (so variant filter stays consistent)
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.tab-pane').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function () {
                const searchInput = document.getElementById('variantSearchInput');
                if (searchInput && searchInput.value.trim()) {
                    filterVariants(searchInput.value);
                }
            });
        });

        // Department filter
        const departmentFilter = document.getElementById('departmentFilter');
        if (departmentFilter) {
            departmentFilter.addEventListener('change', function () {
                const selectedDepartment = this.value;
                let hasVisibleProducts = false;
                const productList = document.getElementById('productList');
                const tabContent  = document.getElementById('variantTabContent');
                const searchInput = document.getElementById('variantSearchInput');

                document.querySelectorAll('.product-item').forEach(function (item) {
                    const itemDepartments = item.getAttribute('data-department').split(',');
                    const matches = selectedDepartment === '' || itemDepartments.includes(selectedDepartment);

                    item.style.display = matches ? '' : 'none';
                    if (matches) hasVisibleProducts = true;

                    // Also hide/show the corresponding tab pane and its variants
                    const pillLink = item.querySelector('a[href]');
                    const tabId   = pillLink ? pillLink.getAttribute('href').replace('#', '') : null;
                    const tabPane = tabId ? document.getElementById(tabId) : null;
                    if (tabPane) {
                        tabPane.querySelectorAll('.variant-item').forEach(v => {
                            v.style.display = matches ? '' : 'none';
                        });
                    }
                });

                // Remove old messages
                productList.querySelector('.no-products-message')?.remove();
                document.getElementById('pos-dept-no-variants')?.remove();

                if (!hasVisibleProducts && selectedDepartment !== '') {
                    // Lock and clear the search input
                    if (searchInput) {
                        searchInput.value = '';
                        searchInput.disabled = true;
                        searchInput.placeholder = '{{ __("pagination.no_products_in_department") }}';
                    }

                    // Message in pill list
                    const msgPill = document.createElement('div');
                    msgPill.className = 'card-header pt-5 no-products-message';
                    msgPill.innerHTML = '<h3 class="card-title fw-bold text-gray-800 fs-2qx">{{ __("pagination.no_products_in_department") }}</h3>';
                    productList.appendChild(msgPill);

                    // Message in tab content area
                    if (tabContent) {
                        const msgTab = document.createElement('div');
                        msgTab.id = 'pos-dept-no-variants';
                        msgTab.className = 'text-center py-10';
                        msgTab.innerHTML = `
                            <i class="ki-duotone ki-category fs-3x text-gray-400 mb-3 d-block">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <span class="text-gray-500 fw-semibold fs-5">{{ __("pagination.no_products_in_department") }}</span>
                        `;
                        tabContent.appendChild(msgTab);
                    }

                    // Hide all tab panes so nothing bleeds through
                    document.querySelectorAll('#variantTabContent .tab-pane').forEach(pane => {
                        pane.style.display = 'none';
                    });

                } else {
                    // Unlock search input and restore its placeholder
                    if (searchInput) {
                        searchInput.disabled = false;
                        searchInput.placeholder = '{{ __("auth._search") }} {{ __("pagination._variants") }}';
                        // If there was a live search term, re-apply it for the new department
                        if (searchInput.value.trim()) {
                            filterVariants(searchInput.value);
                        }
                    }

                    // Restore tab pane visibility
                    document.querySelectorAll('#variantTabContent .tab-pane').forEach(pane => {
                        pane.style.display = '';
                    });

                    // If the currently active pill is now hidden, activate the first visible one
                    const activePill = document.querySelector('.product-item a.active');
                    const activeParent = activePill?.closest('.product-item');
                    if (activeParent && activeParent.style.display === 'none') {
                        const firstVisible = document.querySelector('.product-item:not([style*="none"]) a[data-bs-toggle="pill"]');
                        if (firstVisible) {
                            if (window.bootstrap && window.bootstrap.Tab) {
                                bootstrap.Tab.getOrCreateInstance(firstVisible).show();
                            } else {
                                firstVisible.click();
                            }
                        }
                    }
                }
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
                        // Fix: Convert percentage to decimal
                        taxAmount = itemSubtotal * (rate / 100);
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
                        // Fix: Convert percentage to decimal
                        promoDiscount = itemSubtotal * (promo.value / 100);
                    } else if (promo.type === 'fixed_amount') {
                        promoDiscount = (item.price - promo.value) * item.quantity;
                    } else if (promo.type === 'buy_x_get_y') {
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
                if (typeof window.openPaymentModal === 'function') {
                    window.openPaymentModal(cartData);
                } else {
                    console.error('openPaymentModal not found — check payment-scripts.blade.php is included');
                }
                
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


{{--
════════════════════════════════════════════════════════════════
  payment-scripts.blade.php  —  JavaScript only, zero HTML
  Must be included AFTER payment-modals.blade.php:

    @include('partials.payment-modals')   ← HTML structures first
    @include('partials.payment-scripts')  ← JS last
════════════════════════════════════════════════════════════════
--}}
<script>
    // ═══════════════════════════════════════════════════════════
    // SHARED HELPERS
    // currency_symbol() is a PHP helper — output once here as a
    // JS string. Never call it as a JS function.
    // ═══════════════════════════════════════════════════════════
    var POS_CURRENCY_SYM = '{{ currency_symbol() }}';

    function posFmt(n) {
        return POS_CURRENCY_SYM + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }


    // ═══════════════════════════════════════════════════════════
    // RECEIPT GENERATOR
    // Defined on window at the top level — NOT inside any IIFE —
    // so processSplitPayments() can always call it after the
    // hidden.bs.modal event fires.
    // ═══════════════════════════════════════════════════════════
    window.generateMultiPaymentReceipt = function (order) {
        console.log('[Receipt] triggered ✓', order);

        var g   = function (id) { return document.getElementById(id); };
        var now = new Date();
        var p2  = function (n) { return String(n).padStart(2, '0'); };

        var date = p2(now.getDate()) + '/' + p2(now.getMonth() + 1) + '/' + now.getFullYear();
        var time = p2(now.getHours()) + ':' + p2(now.getMinutes()) + ':' + p2(now.getSeconds());

        var orderNo = order.ref || order.id || Math.floor(Math.random() * 900000 + 100000);

        // ── Meta ─────────────────────────────────────────────
        g('rcpt-order-no').textContent = '#' + orderNo;
        g('rcpt-date').textContent     = date;
        g('rcpt-time').textContent     = time;

        // ── Customer ──────────────────────────────────────────
        var custName = order.customer_name || (order.customer && order.customer.name) || 'GUEST';
        g('rcpt-customer-banner').textContent = '👤 ' + String(custName).toUpperCase();

        // ── Order type ────────────────────────────────────────
        g('rcpt-order-type').textContent = String(order.order_type || order.type || 'SALE').toUpperCase();

        // ── Table (optional) ──────────────────────────────────
        if (order.table) {
            g('rcpt-table-row').classList.remove('d-none');
            g('rcpt-table').textContent = order.table;
        } else {
            g('rcpt-table-row').classList.add('d-none');
        }

        // ── Items ─────────────────────────────────────────────
        var items     = order.items || order.cart_items || [];
        var itemBody  = g('rcpt-items-body');
        var itemCount = 0;

        if (items.length) {
            itemBody.innerHTML = items.map(function (item) {
                var qty   = parseInt(item.quantity || item.qty || 1);
                var price = parseFloat(item.price || item.unit_price || 0);
                var total = parseFloat(item.total || item.subtotal || (qty * price));
                itemCount += qty;
                return '<tr>' +
                    '<td class="rcpt-item-qty">' + qty + 'x</td>' +
                    '<td class="rcpt-item-name">' + (item.name || item.product_name || 'Item') + '</td>' +
                    '<td class="rcpt-item-price">' + posFmt(total) + '</td>' +
                    '</tr>' +
                    (item.note ? '<tr><td></td><td colspan="2" class="rcpt-item-sub">↳ ' + item.note + '</td></tr>' : '');
            }).join('');
        } else {
            itemBody.innerHTML = '<tr><td colspan="3" style="color:#999;font-size:11px;text-align:center;">—</td></tr>';
        }
        g('rcpt-item-count').textContent = itemCount;

        // ── Totals ────────────────────────────────────────────
        var subtotal   = parseFloat(order.subtotal || order.total || 0);
        var discount   = parseFloat(order.discount || 0);
        var tax        = parseFloat(order.tax || 0);
        var grandTotal = parseFloat(order.total || 0);

        g('rcpt-subtotal').textContent    = posFmt(subtotal);
        g('rcpt-grand-total').textContent = posFmt(grandTotal);

        if (discount > 0) {
            g('rcpt-discount-row').classList.remove('d-none');
            g('rcpt-discount').textContent = '-' + posFmt(discount);
        } else {
            g('rcpt-discount-row').classList.add('d-none');
        }

        if (tax > 0) {
            g('rcpt-tax-row').classList.remove('d-none');
            g('rcpt-tax').textContent = posFmt(tax);
        } else {
            g('rcpt-tax-row').classList.add('d-none');
        }

        // ── Payment splits ────────────────────────────────────
        var payments    = order.payments || [];
        var payBody     = g('rcpt-payments-body');
        var totalChange = parseFloat(order.total_change || 0);

        if (payments.length) {
            payBody.innerHTML = payments.map(function (p) {
                var label = (p.method_name || p.type || 'Payment').toUpperCase();
                var acct  = p.account_number ? ' (****' + String(p.account_number).slice(-4) + ')' : '';
                var rows  = '<tr>' +
                    '<td class="rcpt-pay-label">' + label + acct + '</td>' +
                    '<td class="rcpt-pay-value">' + posFmt(p.tendered || p.amount) + '</td>' +
                    '</tr>';
                if (parseFloat(p.change || 0) > 0.005) {
                    rows += '<tr>' +
                        '<td class="rcpt-pay-label" style="padding-left:12px;color:#888;">↳ Applied</td>' +
                        '<td class="rcpt-pay-value" style="color:#888;">' + posFmt(p.amount) + '</td>' +
                        '</tr>';
                }
                if (p.transaction_reference) {
                    rows += '<tr><td colspan="2" style="font-size:10px;color:#888;padding-left:12px;">Ref: ' + p.transaction_reference + '</td></tr>';
                }
                return rows;
            }).join('');
        } else {
            payBody.innerHTML = '<tr>' +
                '<td class="rcpt-pay-label">{{ strtoupper(__("pagination.paid")) }}</td>' +
                '<td class="rcpt-pay-value">' + posFmt(grandTotal) + '</td>' +
                '</tr>';
        }

        // ── Change due ────────────────────────────────────────
        var changeBox = g('rcpt-change-box');
        if (totalChange > 0.005) {
            changeBox.classList.remove('d-none');
            g('rcpt-change-value').textContent = posFmt(totalChange);
        } else {
            changeBox.classList.add('d-none');
        }

        // ── Barcode ───────────────────────────────────────────
        var barcodeVal = String(orderNo).replace(/\D/g, '').padEnd(8, '0').slice(0, 12);
        g('rcpt-barcode').textContent     = barcodeVal;
        g('rcpt-barcode-num').textContent = barcodeVal;

        // ── Open modal ────────────────────────────────────────
        var receiptEl = document.getElementById('receiptModal');
        if (!receiptEl) { console.error('[Receipt] #receiptModal not found in DOM'); return; }
        bootstrap.Modal.getOrCreateInstance(receiptEl).show();
    };

    // Print button (delegated — safe if modal rerenders)
    document.addEventListener('click', function (e) {
        if (e.target.closest('#rcpt-print-btn')) window.print();
    });


    // ═══════════════════════════════════════════════════════════
    // PAYMENT MODAL LOGIC
    // State (splitPayments, currentOrder) kept private via IIFE.
    // Public API exposed on window: openPaymentModal, processSplitPayments
    // ═══════════════════════════════════════════════════════════
    (function () {

        @if(isset($active_payment_methods))
            window.activePaymentMethods = @json($globalPaymentMethods ?? []);
        @endif

        var SYM            = POS_CURRENCY_SYM;
        var TYPES_WITH_REF = ['card', 'bank_account', 'mobile_money', 'digital_wallet', 'check', 'credit'];
        var CASH_TYPES     = ['cash'];

        var splitPayments = [];
        var currentOrder  = null;

        var g  = function (id)  { return document.getElementById(id); };
        var qs = function (sel) { return document.querySelector(sel); };

        function fmt(n) { return posFmt(n); }

        // ── Helpers ───────────────────────────────────────────

        function getRemainingRaw() {
            var el = g('pm-remaining');
            return el ? parseFloat(el.textContent.replace(/[^0-9.-]+/g, '')) || 0 : 0;
        }

        function updateRemainingHint(type) {
            var hint = g('pm-remaining-hint-' + type);
            if (hint) hint.textContent = '{{ __("pagination.remaining") }}: ' + fmt(getRemainingRaw());
        }

        function buildQuickAmounts(type) {
            var container = g('pm-quick-' + type);
            if (!container) return;
            var remaining = getRemainingRaw();
            if (remaining <= 0) {
                container.innerHTML = '<span class="text-success fw-semibold fs-7">' +
                    '<i class="ki-duotone ki-check-circle fs-4 me-1 text-success"><span class="path1"></span><span class="path2"></span></i>' +
                    '{{ __("pagination.fully_paid") }}</span>';
                return;
            }
            var presets = [parseFloat(remaining.toFixed(2))];
            var rounds  = [1, 2, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000];
            for (var i = 0; i < rounds.length && presets.length < 7; i++) {
                var rounded = Math.ceil(remaining / rounds[i]) * rounds[i];
                if (rounded > remaining && rounded <= remaining * 5 && presets.indexOf(rounded) === -1) {
                    presets.push(rounded);
                }
            }
            presets.sort(function (a, b) { return a - b; });
            container.innerHTML = presets.map(function (v) {
                var isExact = Math.abs(v - remaining) < 0.005;
                return '<button type="button" class="pm-quick-btn' + (isExact ? ' pm-exact' : '') + '"' +
                    ' data-payment-type="' + type + '" data-quick-amount="' + v + '">' +
                    (isExact ? '<span style="font-size:.7rem">✓ EXACT</span><br>' : '') +
                    fmt(v) + '</button>';
            }).join('');
        }

        function updateCashCalc(type) {
            var amountEl  = g('pm-amount-' + type);
            var calcWrap  = g('pm-cash-calc-' + type);
            if (!amountEl || !calcWrap) return;
            var isCash    = CASH_TYPES.indexOf(type) !== -1;
            var tendered  = parseFloat(amountEl.value) || 0;
            var remaining = getRemainingRaw();
            if (!isCash || tendered <= 0) { calcWrap.classList.add('d-none'); return; }
            calcWrap.classList.remove('d-none');
            var change      = Math.max(0, tendered - remaining);
            var isUnderpaid = tendered < remaining - 0.005;
            var banner      = g('pm-change-banner-' + type);
            var tenderedEl  = g('pm-tendered-' + type);
            var changeEl    = g('pm-change-' + type);
            if (tenderedEl) tenderedEl.textContent = fmt(tendered);
            if (banner)     banner.classList.toggle('pm-underpaid', isUnderpaid);
            if (changeEl)   changeEl.textContent   = isUnderpaid ? 'Short ' + fmt(remaining - tendered) : fmt(change);
        }

        function validateBtn(type) {
            var account   = g('pm-account-' + type);
            var amount    = g('pm-amount-' + type);
            var btn       = g('pm-add-btn-' + type);
            if (!account || !amount || !btn) return;
            var tendered  = parseFloat(amount.value) || 0;
            var remaining = getRemainingRaw();
            var isCash    = CASH_TYPES.indexOf(type) !== -1;
            var ok = account.value !== '' && tendered > 0 &&
                    (isCash ? remaining > 0 : tendered <= remaining + 0.005);
            btn.disabled = !ok;
            btn.classList.toggle('btn-primary',   ok);
            btn.classList.toggle('btn-secondary', !ok);
        }

        function toggleRef(type) {
            var row = g('pm-ref-row-' + type);
            if (!row) return;
            var show = TYPES_WITH_REF.indexOf(type) !== -1;
            row.classList.toggle('d-none', !show);
            if (!show) { var inp = g('pm-ref-' + type); if (inp) inp.value = ''; }
        }

        function resetTab(type) {
            [g('pm-account-' + type), g('pm-amount-' + type), g('pm-ref-' + type)]
                .forEach(function (el) { if (el) el.value = ''; });
            var calc = g('pm-cash-calc-' + type);
            if (calc) calc.classList.add('d-none');
            validateBtn(type);
            buildQuickAmounts(type);
            updateRemainingHint(type);
        }

        // ── Render splits table ───────────────────────────────

        function renderTable() {
            var tbody = g('pm-splits-body');
            if (!tbody) return;
            if (!splitPayments.length) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center py-12 text-muted">' +
                    '<i class="ki-duotone ki-wallet fs-3x mb-3 d-block opacity-20"><span class="path1"></span><span class="path2"></span></i>' +
                    '<div class="fs-6 fw-semibold">{{ __("pagination.no_payments_added") }}</div>' +
                    '</td></tr>';
                return;
            }
            tbody.innerHTML = splitPayments.map(function (p, i) {
                return '<tr>' +
                    '<td class="ps-6"><div class="d-flex align-items-center gap-3">' +
                        '<span class="symbol symbol-35px symbol-circle"><span class="symbol-label bg-light-primary">' +
                            '<i class="ki-duotone ' + getPaymentTypeIcon(p.type) + ' fs-3 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>' +
                        '</span></span>' +
                        '<div><div class="fw-bold text-gray-800">' + formatPaymentType(p.type) + '</div>' +
                        '<small class="text-muted">' + p.method_name + '</small></div>' +
                    '</div></td>' +
                    '<td><span class="badge badge-light-primary fs-8">' + (p.account_number || 'N/A') + '</span>' +
                        (p.transaction_ref ? '<small class="d-block text-muted mt-1">Ref: ' + p.transaction_ref + '</small>' : '') +
                    '</td>' +
                    '<td class="text-end fw-bold text-gray-600">' + fmt(p.tendered) + '</td>' +
                    '<td class="text-end fw-bolder fs-5 text-gray-900">' + fmt(p.amount) + '</td>' +
                    '<td class="text-end">' +
                        (p.change > 0.005
                            ? '<span class="badge badge-light-success fw-bold fs-7">' + fmt(p.change) + '</span>'
                            : '<span class="text-muted fs-7">—</span>') +
                    '</td>' +
                    '<td class="text-end pe-6">' +
                        '<button type="button" class="btn btn-sm btn-icon btn-light-danger pm-remove-btn" data-index="' + i + '">' +
                            '<i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>' +
                        '</button>' +
                    '</td>' +
                    '</tr>';
            }).join('');

            var count = g('pm-splits-count');
            if (count) count.textContent = splitPayments.length + ' {{ __("pagination.payments") }}';
        }

        // ── Update summary strip + footer totals ──────────────

        function updateSummary() {
            if (!currentOrder) return;
            var totalApplied  = splitPayments.reduce(function (s, p) { return s + p.amount;   }, 0);
            var totalTendered = splitPayments.reduce(function (s, p) { return s + p.tendered; }, 0);
            var totalChange   = splitPayments.reduce(function (s, p) { return s + p.change;   }, 0);
            var remaining     = Math.max(0, currentOrder.total - totalApplied);

            g('pm-paid-amount').textContent = fmt(totalApplied);
            g('pm-remaining').textContent   = fmt(remaining);

            var wrap = g('pm-remaining-wrap'), remEl = g('pm-remaining');
            if (wrap)  { wrap.classList.toggle('bg-light-danger',  remaining > 0.005); wrap.classList.toggle('bg-light-success', remaining <= 0.005); }
            if (remEl) { remEl.classList.toggle('text-danger',     remaining > 0.005); remEl.classList.toggle('text-success',    remaining <= 0.005); }

            var totTen = g('pm-total-tendered'), totApp = g('pm-splits-total'), totChg = g('pm-total-change');
            if (totTen) totTen.textContent = totalTendered > 0 ? fmt(totalTendered) : '—';
            if (totApp) totApp.textContent = fmt(totalApplied);
            if (totChg) totChg.textContent = totalChange > 0.005 ? fmt(totalChange) : '—';

            var pb = g('pm-process-btn');
            if (pb) pb.disabled = !(splitPayments.length > 0 && remaining <= 0.005);

            var activePane = qs('.tab-pane.active[data-payment-type]');
            if (activePane) {
                var t = activePane.dataset.paymentType;
                validateBtn(t); buildQuickAmounts(t); updateRemainingHint(t);
            }
        }

        // ── Add / remove payment ──────────────────────────────

        function addPayment(type) {
            var accountEl = g('pm-account-' + type);
            var amountEl  = g('pm-amount-'  + type);
            var refEl     = g('pm-ref-'     + type);
            if (!accountEl || !amountEl) return;

            var accountId = accountEl.value;
            var tendered  = parseFloat(amountEl.value);
            var ref       = refEl ? refEl.value.trim() : '';
            var remaining = getRemainingRaw();
            var isCash    = CASH_TYPES.indexOf(type) !== -1;

            if (!accountId)                            { toastr.warning('{{ __("pagination.please_select_account") }}');      return; }
            if (!tendered || tendered <= 0)            { toastr.warning('{{ __("pagination.please_enter_valid_amount") }}'); return; }
            if (!isCash && tendered > remaining+0.005) { toastr.warning('{{ __("pagination.amount_exceeds_remaining") }}');  return; }
            if (remaining <= 0)                        { toastr.warning('{{ __("pagination.order_already_paid") }}');         return; }

            var applied = isCash ? Math.min(tendered, remaining) : tendered;
            var change  = isCash ? Math.max(0, tendered - remaining) : 0;

            var opt = accountEl.options[accountEl.selectedIndex];
            var accountData = {};
            try { accountData = JSON.parse(opt.dataset.account || '{}'); } catch (e) {}

            splitPayments.push({
                id:             Date.now() + Math.random(),
                type:           type,
                method_id:      accountId,
                method_name:    accountData.name           || 'Unknown',
                account_number: accountData.account_number || '',
                tendered:       tendered,
                amount:         applied,
                change:         change,
                transaction_ref: ref,
                account_data:   accountData
            });

            renderTable();
            updateSummary();
            resetTab(type);

            if (change > 0.005) {
                toastr.info(
                    '<strong>{{ __("pagination.change_due") }}: ' + fmt(change) + '</strong>',
                    '{{ __("pagination.give_change_to_customer") }}',
                    { timeOut: 6000 }
                );
            }
            if (getRemainingRaw() <= 0.005) toastr.success('{{ __("pagination.payment_complete") }}');
        }

        function removePayment(index) {
            splitPayments.splice(index, 1);
            renderTable();
            updateSummary();
        }

        // ── Public: open payment modal ────────────────────────

        window.openPaymentModal = function (cartData) {
            currentOrder        = cartData;
            window.currentOrder = cartData;
            splitPayments       = [];

            g('pm-order-total').textContent = fmt(cartData.total);
            g('pm-paid-amount').textContent = fmt(0);
            g('pm-remaining').textContent   = fmt(cartData.total);

            var refEl = g('pm-order-ref');
            if (refEl) refEl.textContent = cartData.ref ? '#' + cartData.ref : '—';

            // Reset all inputs
            document.querySelectorAll('.pm-account-select').forEach(function (s) { s.value = ''; });
            document.querySelectorAll('.pm-amount-input').forEach(function (i)   { i.value = ''; });
            document.querySelectorAll('.pm-ref-input').forEach(function (i)      { i.value = ''; });
            document.querySelectorAll('.pm-add-btn').forEach(function (b) {
                b.disabled = true;
                b.classList.remove('btn-primary');
                b.classList.add('btn-secondary');
            });
            document.querySelectorAll('[id^="pm-cash-calc-"]').forEach(function (el) { el.classList.add('d-none'); });

            var pb = g('pm-process-btn'); if (pb) pb.disabled = true;

            var wrap = g('pm-remaining-wrap'), remEl = g('pm-remaining');
            if (wrap)  { wrap.classList.add('bg-light-danger');  wrap.classList.remove('bg-light-success'); }
            if (remEl) { remEl.classList.add('text-danger');     remEl.classList.remove('text-success'); }

            renderTable();

            // AUTO-SELECT CASH TAB - Add this code
            setTimeout(function() {
                // Find the cash tab button
                const cashTabButton = document.querySelector('#pm-tab-cash');
                
                if (cashTabButton) {
                    // Method 1: Use Bootstrap's Tab API
                    if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
                        const tab = new bootstrap.Tab(cashTabButton);
                        tab.show();
                    } 
                    // Method 2: Manual activation if Bootstrap Tab API is not available
                    else {
                        // Remove active class from all tab buttons
                        document.querySelectorAll('#pm-type-tabs .nav-link').forEach(tab => {
                            tab.classList.remove('active');
                        });
                        
                        // Remove active class from all panes
                        document.querySelectorAll('.tab-pane').forEach(pane => {
                            pane.classList.remove('show', 'active');
                        });
                        
                        // Activate cash tab button
                        cashTabButton.classList.add('active');
                        
                        // Activate cash pane
                        const cashPane = document.getElementById('pm-pane-cash');
                        if (cashPane) {
                            cashPane.classList.add('show', 'active');
                        }
                        
                        // Trigger custom event
                        var event = new CustomEvent('shown.bs.tab', {
                            detail: { target: cashTabButton }
                        });
                        document.dispatchEvent(event);
                    }
                    
                    // Auto-focus the amount input and pre-fill with total
                    setTimeout(function() {
                        var amountInput = g('pm-amount-cash');
                        if (amountInput && currentOrder && currentOrder.total) {
                            // Pre-fill with the total amount
                            // amountInput.value = currentOrder.total;
                            // Trigger input event to update calculations
                            amountInput.dispatchEvent(new Event('input', { bubbles: true }));
                            // Focus the input
                            amountInput.focus();
                            // Select all text for easy replacement
                            amountInput.select();
                        }
                        
                        // Also pre-select the first cash account if available
                        // var cashSelect = g('pm-account-cash');
                        // if (cashSelect && cashSelect.options.length > 1) {
                        //     // Select the first non-empty option
                        //     for (let i = 0; i < cashSelect.options.length; i++) {
                        //         if (cashSelect.options[i].value) {
                        //             cashSelect.selectedIndex = i;
                        //             break;
                        //         }
                        //     }
                        //     // Trigger change event to validate button
                        //     cashSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        // }
                    }, 300);
                }
                
                // Set up the active tab for our custom functions
                var ap = qs('.tab-pane.active[data-payment-type]');
                if (ap) { 
                    var t = ap.dataset.paymentType; 
                    toggleRef(t); 
                    buildQuickAmounts(t); 
                    updateRemainingHint(t); 
                    
                    // Enable the add button if amount is filled
                    if (t === 'cash') {
                        setTimeout(function() {
                            validateBtn('cash');
                        }, 350);
                    }
                }
            }, 150);

            bootstrap.Modal.getOrCreateInstance(g('paymentModal')).show();
        };

        // ── Public: process payment ───────────────────────────

        window.processSplitPayments = function () {
            if (!currentOrder)         { toastr.error('{{ __("pagination.no_order_found") }}');      return; }
            if (!splitPayments.length) { toastr.warning('{{ __("pagination.no_payments_added") }}'); return; }

            var totalApplied = splitPayments.reduce(function (s, p) { return s + p.amount; }, 0);
            if (Math.abs(currentOrder.total - totalApplied) > 0.01) {
                toastr.warning('{{ __("pagination.payment_total_mismatch") }}');
                return;
            }

            var btn = g('pm-process-btn');
            btn.setAttribute('data-kt-indicator', 'on');
            btn.disabled = true;

            var payload = Object.assign({}, currentOrder, {
                payments: splitPayments.map(function (p) {
                    return {
                        payment_method_id:     p.method_id,
                        amount:                p.amount,
                        tendered:              p.tendered,
                        change:                p.change,
                        transaction_reference: p.transaction_ref,
                        type:                  p.type,
                        method_name:           p.method_name,
                        account_number:        p.account_number
                    };
                }),
                total_paid:      totalApplied,
                total_tendered:  splitPayments.reduce(function (s, p) { return s + p.tendered; }, 0),
                total_change:    splitPayments.reduce(function (s, p) { return s + p.change;   }, 0),
                payment_methods: splitPayments.map(function (p) { return p.type; }).join(', ')
            });

            fetch('/orders/process-split-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                btn.removeAttribute('data-kt-indicator');
                btn.disabled = false;

                if (data.success) {
                    toastr.success(data.message || '{{ __("pagination.payment_completed") }}');

                    // Store receipt data
                    const receiptData = data.order;

                    // 1. Grab the payment modal instance
                    const payModal = bootstrap.Modal.getInstance(g('paymentModal'));

                    // 2. Listen for when it FULLY finishes hiding, THEN open receipt
                    g('paymentModal').addEventListener('hidden.bs.modal', function onHidden() {
                        // Remove listener so it doesn't fire again next time
                        g('paymentModal').removeEventListener('hidden.bs.modal', onHidden);

                        // 3. Now safe to open receipt with the full data
                        if (typeof window.generateMultiPaymentReceipt === 'function') {
                            window.generateMultiPaymentReceipt(receiptData);
                        } else {
                            console.error('generateMultiPaymentReceipt function not found');
                            // Fallback
                            generateMultiPaymentReceipt(receiptData);
                        }
                    });

                    // 4. Hide payment modal (triggers the listener above)
                    payModal.hide();

                    // 5. Clear cart immediately
                    if (typeof clearCart === 'function') clearCart();

                } else {
                    toastr.error(data.message || '{{ __("pagination.payment_failed") }}');
                }
            })
            .catch(function (err) {
                btn.removeAttribute('data-kt-indicator');
                btn.disabled = false;
                toastr.error('{{ __("pagination.payment_error") }}');
                console.error('Payment error:', err);
            });
        };

        // ── Delegated event listeners ─────────────────────────

        document.addEventListener('click', function (e) {
            // Quick preset button
            var qb = e.target.closest('.pm-quick-btn');
            if (qb) {
                var inp = g('pm-amount-' + qb.dataset.paymentType);
                if (inp) { inp.value = qb.dataset.quickAmount; inp.dispatchEvent(new Event('input', { bubbles: true })); inp.focus(); }
                return;
            }
            // Add payment
            var addBtn = e.target.closest('.pm-add-btn');
            if (addBtn && !addBtn.disabled) { addPayment(addBtn.dataset.paymentType); return; }
            // Remove payment
            var remBtn = e.target.closest('.pm-remove-btn');
            if (remBtn) { removePayment(parseInt(remBtn.dataset.index, 10)); return; }
            // Process button
            if (e.target.closest('#pm-process-btn')) window.processSplitPayments();
        });

        document.addEventListener('change', function (e) {
            var sel = e.target.closest('.pm-account-select');
            if (sel) validateBtn(sel.dataset.paymentType);
        });

        document.addEventListener('input', function (e) {
            var inp = e.target.closest('.pm-amount-input');
            if (!inp) return;
            validateBtn(inp.dataset.paymentType);
            updateCashCalc(inp.dataset.paymentType);
        });

        document.addEventListener('shown.bs.tab', function (e) {
            var type = e.target.dataset.paymentType;
            if (!type) return;
            toggleRef(type); buildQuickAmounts(type); validateBtn(type); updateRemainingHint(type);
        });

    })();
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


