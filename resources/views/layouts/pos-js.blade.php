
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
        const existingCustomer = document.getElementById('existingCustomer'); // dropdown
        const newCustomer = document.getElementById('newCustomer'); // input
        const existingOption = document.getElementById('existingOption'); // radio
        const newOption = document.getElementById('newOption'); // radio

        let customerData = null;

        if (existingOption.checked) {
            // Existing customer chosen
            if (existingCustomer.value === "") {
                toastr['warning']('{{ __("pagination.please_select_existing_customer") }}');
                return;
            }
            customerData = {
                type: "existing",
                id: existingCustomer.value
            };
        } else if (newOption.checked) {
            // New customer chosen
            if (newCustomer.value.trim() === "") {
                toastr['warning']('{{ __("pagination.please_enter_customer") }}');
                return;
            }
            customerData = {
                type: "new",
                name: newCustomer.value.trim()
            };
        }

        // Get selected payment method
        const selectedMethod = document.querySelector('input[name="method"]:checked');
        const paymentMethod = selectedMethod ? selectedMethod.value : 'cash';

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
            payment_method: paymentMethod,

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
                openPaymentModal(paymentMethod, cartData);
                
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
    @if(isset($active_payment_methods))
        window.activePaymentMethods = @json($active_payment_methods ?? []);
    @endif

    // Function to open appropriate modal
    function openPaymentModal(paymentMethod, cartData) {
        // Set order data globally for access in modal functions
        window.currentOrder = cartData;
        window.currentPaymentMethodType = paymentMethod; // This is already 'cash', 'bank_account', 'card', etc.
        
        openCashModal(paymentMethod, cartData)
    }

    // Cash Modal Function with account dropdown
    function openCashModal(paymentMethod, cartData) {
        currentCartData = cartData;
        currentPaymentMethodType = paymentMethod;
        selectedPaymentAccountId = null;
        
        // Update modal title based on payment method
        updateModalTitle(paymentMethod);
        
        // Update total amount
        document.getElementById('cashTotalAmount').textContent = `${formatCurrency(cartData.total)}`;
        document.getElementById('cashAmountInput').value = '';
        document.getElementById('cashAmountTendered').textContent = '0.00';
        document.getElementById('cashChangeDue').textContent = '0.00';
        
        // Reset account info card
        document.getElementById('selectedAccountInfoCard').classList.add('d-none');
        
        // Populate accounts dropdown based on payment method
        populateAccountsByType(currentPaymentMethodType);

        
        // Show modal
        const cashModal = new bootstrap.Modal(document.getElementById('cashModal'));
        cashModal.show();
    }

    // Update modal title based on payment method
    function updateModalTitle(paymentMethod) {
        const modalTitle = document.querySelector('#cashModal .modal-title');
        const titles = {
            'cash': '{{__("pagination.cash_payment")}}',
            'mobile_money': '{{__("pagination.mobile_money_payment")}}',
            'card': '{{__("pagination.card_payment")}}',
            'bank_account': '{{__("pagination.bank_account_payment")}}',
            'digital_wallet': '{{__("pagination.digital_wallet_payment")}}',
            'check': '{{__("pagination.check_payment")}}',
            'credit': '{{__("pagination.credit_payment")}}',
            'other': '{{__("pagination.other_payment")}}'
        };
        
        modalTitle.textContent = titles[paymentMethod] || '{{__("pagination.payment")}}';
    }

    // Populate accounts in dropdown by payment method type
    function populateAccountsByType(paymentMethodType) {
        const dropdown = document.getElementById('cashAccountSelect');
        dropdown.innerHTML = '<option value="">{{ __("pagination.select_account") }}</option>';
        
        if (!window.activePaymentMethods) {
            console.error('activePaymentMethods is not defined');
            return;
        }
        
        // Filter accounts by the current payment method type
        const filteredAccounts = window.activePaymentMethods.filter(account => 
            account.type === paymentMethodType
        );
        
        if (filteredAccounts.length === 0) {
            const noAccountsText = {
                'cash': '{{ __("pagination.no_cash_accounts") }}',
                'mobile_money': '{{ __("pagination.no_mobile_money_accounts") }}',
                'card': '{{ __("pagination.no_card_accounts") }}',
                'bank_account': '{{ __("pagination.no_bank_accounts") }}',
                'digital_wallet': '{{ __("pagination.no_digital_wallet_accounts") }}',
                'check': '{{ __("pagination.no_check_accounts") }}',
                'credit': '{{ __("pagination.no_credit_accounts") }}',
                'other': '{{ __("pagination.no_other_accounts") }}'
            };
            
            dropdown.innerHTML = `<option value="" disabled>${noAccountsText[paymentMethodType] || '{{ __("pagination.no_accounts_found") }}'}</option>`;
            return;
        }
        
        // Add filtered accounts to dropdown
        filteredAccounts.forEach(account => {
            const option = document.createElement('option');
            option.value = account.id;
            option.setAttribute('data-account', JSON.stringify(account)); // Store full account data
            
            // Create display text with name, account number, and account name
            let displayText = account.name;
            if (account.account_number) {
                displayText += ` - ${account.account_number}`;
            }
            if (account.account_name) {
                displayText += ` (${account.account_name})`;
            }
            
            option.textContent = displayText;
            dropdown.appendChild(option);
        });
        
        // Remove existing event listeners and add new one
        const newDropdown = dropdown.cloneNode(true);
        dropdown.parentNode.replaceChild(newDropdown, dropdown);
        
        // Add event listener for account selection
        newDropdown.addEventListener('change', function() {
            selectedPaymentAccountId = this.value;
            
            if (this.value) {
                // Get selected account data
                const selectedOption = this.options[this.selectedIndex];
                const accountData = JSON.parse(selectedOption.getAttribute('data-account') || '{}');
                
                // Display account information
                displayAccountInfo(accountData);
                
                // Show/hide cash amount section based on payment type
                if (currentPaymentMethodType === 'cash') {
                    document.getElementById('cashAmountSection').classList.remove('d-none');
                    document.getElementById('cashAmountInput').focus();
                } else {
                    document.getElementById('cashAmountSection').classList.add('d-none');
                }
                
            } else {
                // Hide account info card
                document.getElementById('selectedAccountInfoCard').classList.add('d-none');
                
                // Hide cash amount section
                // document.getElementById('cashAmountSection').classList.add('d-none');
            }
        });
    }

    // Display selected account information
    function displayAccountInfo(account) {
        const infoCard = document.getElementById('selectedAccountInfoCard');
        if (!infoCard) {
            console.error('selectedAccountInfoCard element not found');
            return;
        }
        
        // Safely update account information
        const accountNameEl = document.getElementById('selectedAccountName');
        const accountNumberEl = document.getElementById('selectedAccountNumber');
        const accountHolderEl = document.getElementById('selectedAccountHolder');
        const accountProviderEl = document.getElementById('selectedAccountProvider');
        
        if (accountNameEl) accountNameEl.textContent = account.name || '-';
        if (accountNumberEl) accountNumberEl.textContent = account.account_number || '-';
        if (accountHolderEl) accountHolderEl.textContent = account.account_name || '-';
        if (accountProviderEl) accountProviderEl.textContent = account.provider || '-';
        
        // Show the info card
        infoCard.classList.remove('d-none');
    }

    // Also update the event listener to be more robust:
    dropdown.addEventListener('change', function() {
        selectedPaymentAccountId = this.value;
        
        // Get account info card element
        const infoCard = document.getElementById('selectedAccountInfoCard');
        
        if (this.value) {
            // Get selected account data
            try {
                const selectedOption = this.options[this.selectedIndex];
                const accountData = JSON.parse(selectedOption.getAttribute('data-account') || '{}');
                
                // Display account information
                displayAccountInfo(accountData);
                
            } catch (error) {
                console.error('Error processing account selection:', error);
                if (infoCard) infoCard.classList.add('d-none');
            }
        } else {
            // Hide account info card
            if (infoCard) {
                infoCard.classList.add('d-none');
            }
        }
    });



    // Calculate change (for cash payments)
    document.getElementById('cashAmountInput').addEventListener('input', function() {
        const amountTendered = parseFloat(this.value) || 0;
        const total = parseFloat(currentCartData.total) || 0;
        const change = amountTendered - total;
        document.getElementById('changeDue').value = change >= 0 ? formatCurrency(change) : '0.00';
    });



    function calculateChange() {
        const total = window.currentOrder.total;
        const tendered = parseFloat(document.getElementById('cashAmountInput').value) || 0;
        const change = tendered - total;
        
        document.getElementById('cashAmountTendered').textContent = `${formatCurrency(tendered)}`;
        document.getElementById('cashChangeDue').textContent = `${formatCurrency(Math.max(0, change))}`;
    }

    function completeCashPayment() {
        const submitButton = document.getElementById('cashCheckout');
        const tendered = parseFloat(document.getElementById('cashAmountInput').value) || 0;
        const transactionId = document.getElementById('transactionIdInput').value || '';

        const accountSelect = document.getElementById('cashAccountSelect');
        const selectedPaymentMethodId = accountSelect ? accountSelect.value : null;
        
        if (!selectedPaymentMethodId) {
            toastr['error']('{{ __("pagination.select_payment_method") }}');
            return;
        }

        if (tendered < window.currentOrder.total) {
            toastr['error']('{{ __("pagination.insufficient_amount") }}');
            return;
        }
        
        // Spinning
        LiveBlade.toggleButtonLoading(submitButton, true);
        // Process cash payment
        processFinalPayment(selectedPaymentMethodId, {
            amount_tendered: tendered,
            transaction_id: transactionId,
            change_due: tendered - window.currentOrder.total
        });

    }


    // Final Payment Processing
    function processFinalPayment(payment_method_id, paymentDetails) {
        const submitButton = document.getElementById('cashCheckout');

        const order = window.currentOrder;
        order.payment_method_id = payment_method_id;

        order.payment_details = {
            amount_tendered: paymentDetails.amount_tendered,
            change_due: paymentDetails.change_due,
            transaction_id: paymentDetails.transaction_id
        };

        fetch("/orders/checkout", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(order)
        })
        .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, body: data })))
        .then(response => {
            if (!response.ok) {
                throw new Error(response.body.message || '{{ __("pagination.order_saved_error") }}');
            }
            toastr['success']('{{ __("pagination.order_saved") }}');
            // console.log("Order saved:", response.body);

            // Close current modal
            bootstrap.Modal.getInstance(document.getElementById('cashModal'))?.hide();

            setTimeout(() => {
                generateReceipt(order);
                openReceiptModal(window.currentOrder);

                toastr['success']('{{ __("pagination.payment_completed") }}');
            }, 2000);
            
            // Clear cart after successful payment
            clearCart();
            LiveBlade.toggleButtonLoading(submitButton, false);
        })
        .catch(err => {
            console.error("❌ Error saving order:", err);
            toastr['error'](err.message || '{{ __("pagination.order_saved_error") }}');
            LiveBlade.toggleButtonLoading(submitButton, false);
        });

        
    }

    // --- Generate Receipt ---
    function generateReceipt(paymentDetails) {
        const receiptContent = document.getElementById('receiptContent');
        const order = window.currentOrder;
        const paymentMethod = window.currentPaymentMethodType
        // console.log(paymentMethod)
        

        // Determine customer display: use customerName if available
        let customerInfo = '{{ __("pagination.customer") }}: N/A';
        if (order.customerName) {
            customerInfo = `{{ __("pagination.customer") }}: ${order.customerName}`;
        } else if (order.customer) {
            customerInfo = `{{ __("pagination.customer") }}: ${order.customer.name}`;
        }

        // Generate items table
        const itemsHtml = `
            <table style="width:100%; border-collapse: collapse; margin-bottom: 10px;">
                <thead>
                    <tr>
                        <th style="border-bottom: 1px solid #ddd; text-align:left;">{{ __("pagination._item") }}</th>
                        <th style="border-bottom: 1px solid #ddd; text-align:center;">{{ __("pagination._qty") }}</th>
                        <th style="border-bottom: 1px solid #ddd; text-align:right;">{{ __("pagination._price") }}</th>
                        <th style="border-bottom: 1px solid #ddd; text-align:right;">{{ __("pagination._discount") }}</th>
                        <th style="border-bottom: 1px solid #ddd; text-align:right;">{{ __("pagination._total") }}</th>
                    </tr>
                </thead>
                <tbody>
                    ${order.items.map(item => `
                        <tr>
                            <td>${item.name}</td>
                            <td style="text-align:center;">${item.quantity}</td>
                            <td style="text-align:right;">${formatCurrency(item.price)}</td>
                            <td style="text-align:right;">${formatCurrency(item.discount)}</td>
                            <td style="text-align:right;">${formatCurrency(item.price * item.quantity)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;

        receiptContent.innerHTML = `
            <div class="text-center mb-6">
                <h3 class="fw-bold text-gray-800">{{ getMailOptions('app_name') }}</h3>
                <p class="text-gray-600">Order #: ${order.order_number || 'N/A'}</p>
                <p class="text-gray-600">Date: ${new Date().toLocaleString()}</p>
                <p class="text-gray-600">${customerInfo}</p>
            </div>

            <div class="border-bottom mb-4 pb-4">
                <h4 class="fw-bold text-gray-800 mb-3">{{__('pagination.order_summary')}}</h4>
                ${itemsHtml}
                <div class="d-flex justify-content-between mb-2">
                    <span>{{__('pagination.subtotal')}}:</span>
                    <span>${formatCurrency(order.subtotal)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>{{__('pagination.tax')}}:</span>
                    <span>${formatCurrency(order.tax)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>{{__('pagination._discount')}}:</span>
                    <span>${formatCurrency(order.discount)}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>{{__('pagination.total')}}:</span>
                    <span class="fw-bold">${formatCurrency(order.total)}</span>
                </div>
            </div>

            <div class="border-bottom mb-4 pb-4">
                <h4 class="fw-bold text-gray-800 mb-3">{{__('pagination.payment_details')}}</h4>
                <div class="d-flex justify-content-between mb-2">
                    <span>{{__('pagination.method')}}:</span>
                    <span>${paymentMethod.replace('_', ' ').toUpperCase()}</span>
                </div>
                ${paymentMethod === 'cash' ? `
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{__('pagination.amount_tendered')}}:</span>
                        <span>${formatCurrency(paymentDetails.payment_details.amount_tendered)}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{__('pagination.change_due')}}:</span>
                        <span>${formatCurrency(paymentDetails.payment_details.change_due)}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{__('pagination._currency')}}:</span>
                        <span>{{ getMailOptions('currency') }} - {{ currencySymbol() }}</span>
                    </div>
                ` : ''}
            </div>

            <div class="text-center mt-6">
                <p class="text-gray-600">{{__('pagination.thank_you_message')}}</p>
            </div>
        `;
    }


    // --- Open Receipt Modal ---
    function openReceiptModal() {
        const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
        receiptModal.show();
    }

    // --- Print Receipt ---
    function printReceipt() {
        window.location.href = "{{ url()->current() }}";
        const receiptContent = document.getElementById('receiptContent').innerHTML;
        const printWindow = window.open('', '_blank');

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receipt - ${window.currentOrder.order_number || 'N/A'}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .text-center { text-align: center; }
                    .fw-bold { font-weight: bold; }
                    .border-bottom { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 10px; }
                    .d-flex { display: flex; justify-content: space-between; }
                    .mb-2 { margin-bottom: 8px; }
                    .mb-4 { margin-bottom: 16px; }
                    .pb-4 { padding-bottom: 16px; }
                </style>
            </head>
            <body>
                ${receiptContent}
            </body>
            </html>
        `);

        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();

        // Close the modal after printing
        bootstrap.Modal.getInstance(document.getElementById('receiptModal'))?.hide();

    }


    // Prevent all closing methods
    document.addEventListener('DOMContentLoaded', function() {
        const receiptModal = document.getElementById('receiptModal');
        
        // Prevent backdrop click
        receiptModal.addEventListener('click', function(event) {
            if (event.target === receiptModal) {
                event.stopPropagation();
                event.preventDefault();
                return false;
            }
        });
        
        // Prevent ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && receiptModal.classList.contains('show')) {
                event.preventDefault();
                event.stopPropagation();
                return false;
            }
        });
        
        // Prevent Bootstrap hide events
        receiptModal.addEventListener('hide.bs.modal', function(event) {
            event.preventDefault();
            return false;
        });
        
        // Prevent any other hide attempts
        receiptModal.addEventListener('hidden.bs.modal', function(event) {
            event.preventDefault();
            return false;
        });
    });

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


