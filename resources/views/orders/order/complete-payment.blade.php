<!-- Complete Payment Modal -->
<div class="modal fade" id="completePayment{{$order->id}}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-3 text-gray-800 fw-bold">
                    <i class="ki-duotone ki-dollar-circle fs-2 me-2 text-primary">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    {{__('pagination.complete_payment')}} - Order #{{ $order->order_number }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Order Summary -->
                <div class="alert alert-info mb-6">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold fs-5 text-gray-800 mb-1">{{ __('passwords.total_amount') }}</div>
                            <div class="text-muted fs-7">{{ __('passwords._customer') }}: {{ $order->customer_name ?? __('passwords.none') }}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-primary fs-2">{{ currencySymbol() }} {{ number_format($order->total, 2) }}</div>
                        </div>
                    </div>
                </div>
                @php
                    $orderItemsData = $order->orderItems->map(function($item) {
                        return [
                            "variant_id" => $item->variant_id ?? $item->product_variant_id,
                            "product_variant_id" => $item->product_variant_id ?? $item->variant_id,
                            "quantity" => $item->quantity,
                            "price" => $item->unit_price,
                            "unit_price" => $item->unit_price,
                            "product_id" => $item->product_id ?? null,
                            "name" => $item->item_name,
                            "product_name" => $item->item_name,
                            "sku" => $item->sku ?? "",
                            "subtotal" => $item->total_price
                        ];
                    });
                @endphp
                
                <!-- Hidden inputs for JavaScript -->
                <input type="hidden" id="orderItems{{ $order->id }}" value='@json($orderItemsData)'>
                <input type="hidden" id="orderSubtotal{{ $order->id }}" value="{{ $order->subtotal }}">
                <input type="hidden" id="orderTax{{ $order->id }}" value="{{ $order->tax_total ?? 0 }}">

                
                <!-- STEP 1: Payment Type Selection -->
                <div id="step1_{{$order->id}}">
                    <h4 class="fw-bold text-gray-800 mb-5">
                        <i class="ki-duotone ki-credit-card fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{__('pagination.select_payment_method')}}
                    </h4>
                    
                    @php
                        // Get active payment methods grouped by type
                        $activePaymentMethods = \App\Models\PaymentMethod::where('tenant_id', $order->tenant_id)
                            ->where('is_active', 1)
                            ->get()
                            ->groupBy('type');
                        
                        $paymentTypes = [
                            'cash' => [
                                'label' => __('pagination._cash'),
                                'icon' => 'ki-duotone ki-dollar',
                                'color' => 'primary'
                            ],
                            'mobile_money' => [
                                'label' => __('pagination.mobile_money'),
                                'icon' => 'ki-duotone ki-finance-calculator',
                                'color' => 'warning'
                            ],
                            'card' => [
                                'label' => __('pagination._card'),
                                'icon' => 'ki-duotone ki-credit-cart',
                                'color' => 'success'
                            ],
                            'bank_account' => [
                                'label' => __('payments.bank_account'),
                                'icon' => 'ki-duotone ki-bank',
                                'color' => 'info'
                            ],
                            'digital_wallet' => [
                                'label' => __('payments.digital_wallet'),
                                'icon' => 'ki-duotone ki-wallet',
                                'color' => 'danger'
                            ],
                            'check' => [
                                'label' => __('payments.check'),
                                'icon' => 'ki-duotone ki-document',
                                'color' => 'dark'
                            ],
                            'credit' => [
                                'label' => __('payments.credit'),
                                'icon' => 'ki-duotone ki-time',
                                'color' => 'secondary'
                            ],
                            'other' => [
                                'label' => __('payments.other'),
                                'icon' => 'ki-duotone ki-add-files',
                                'color' => 'gray-600'
                            ],
                        ];
                    @endphp
                    
                    <!-- First Row of Payment Types -->
                    <div class="row g-4 mb-4">
                        @foreach(['mobile_money', 'cash', 'card', 'bank_account'] as $type)
                            @if(isset($activePaymentMethods[$type]) && $activePaymentMethods[$type]->count() > 0)
                                <div class="col-lg-4 col-xxl-3">
                                    <label class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4 payment-type-btn"
                                           data-order-id="{{$order->id}}"
                                           data-payment-type="{{ $type }}">
                                        <input class="btn-check payment-type-radio" 
                                               type="radio" 
                                               name="payment_type{{$order->id}}" 
                                               value="{{ $type }}"
                                               data-order-id="{{$order->id}}">
                                        <i class="{{ $paymentTypes[$type]['icon'] }} fs-2hx mb-3 text-{{ $paymentTypes[$type]['color'] }}">
                                            @if($type == 'cash')
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            @else
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            @endif
                                        </i>
                                        <span class="fs-7 fw-bold text-center">{{ $paymentTypes[$type]['label'] }}</span>
                                        <small class="text-muted fs-8 mt-1">
                                            {{ $activePaymentMethods[$type]->count() }} {{ __('pagination.accounts') }}
                                        </small>
                                    </label>
                                </div>
                            @endif
                        @endforeach
                    </div>
                    
                    <!-- Second Row of Payment Types -->
                    <div class="row g-4 mb-4">
                        @foreach(['digital_wallet', 'check', 'credit', 'other'] as $type)
                            @if(isset($activePaymentMethods[$type]) && $activePaymentMethods[$type]->count() > 0)
                                <div class="col-lg-4 col-xxl-3">
                                    <label class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4 payment-type-btn"
                                           data-order-id="{{$order->id}}"
                                           data-payment-type="{{ $type }}">
                                        <input class="btn-check payment-type-radio" 
                                               type="radio" 
                                               name="payment_type{{$order->id}}" 
                                               value="{{ $type }}"
                                               data-order-id="{{$order->id}}">
                                        <i class="{{ $paymentTypes[$type]['icon'] }} fs-2hx mb-3 text-{{ $paymentTypes[$type]['color'] }}">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <span class="fs-7 fw-bold text-center">{{ $paymentTypes[$type]['label'] }}</span>
                                        <small class="text-muted fs-8 mt-1">
                                            {{ $activePaymentMethods[$type]->count() }} {{ __('pagination.accounts') }}
                                        </small>
                                    </label>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                
                <!-- STEP 2: Account Selection (Hidden by default) -->
                <div id="step2_{{$order->id}}" class="d-none">
                    <div class="separator separator-dashed my-6"></div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="fw-bold text-gray-800 mb-0">
                            <i class="ki-duotone ki-profile-circle fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{__('pagination.select_account')}}
                        </h4>
                        <button type="button" class="btn btn-sm btn-light" onclick="goBackToStep1({{$order->id}})">
                            <i class="ki-duotone ki-arrow-left fs-3 me-1"></i>
                            {{ __('passwords.change_type') }}
                        </button>
                    </div>
                    
                    <!-- Accounts Grid -->
                    <div class="row g-4" id="accountsGrid{{$order->id}}">
                        <!-- Accounts will be loaded here -->
                    </div>
                </div>
                
                <!-- STEP 3: Payment Details (Hidden by default) -->
                <div id="step3_{{$order->id}}" class="d-none">
                    <div class="separator separator-dashed my-6"></div>
                    
                    <h4 class="fw-bold text-gray-800 mb-4">
                        <i class="ki-duotone ki-copy fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{__('pagination.payment_details')}}
                    </h4>
                    
                    <!-- Universal Payment Details (for ALL payment types) -->
                    <div class="card card-flush border border-dashed border-gray-300 mb-4">
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-gray-800 mb-3">
                                        <i class="ki-duotone ki-money fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{__('pagination.amount_tendered')}} *
                                    </label>
                                    <div class="input-group input-group-lg input-group-solid">
                                        <span class="input-group-text fw-bold">{{ currencySymbol() }}</span>
                                        <!-- Simple version - keep as number input without formatting -->
                                        <input type="number" 
                                            class="form-control form-control-lg" 
                                            id="amountTendered{{$order->id}}" 
                                            placeholder="0.00"
                                            step="0.01"
                                            min="0"
                                            value="{{ $order->total }}"
                                            data-order-total="{{ $order->total }}"
                                            oninput="calculateChangeSimple({{$order->id}})">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex flex-column justify-content-center h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-muted fs-6">{{__('passwords.total')}}:</span>
                                            <span class="fw-bold fs-5 text-gray-800">{{ currencySymbol() }} {{ number_format($order->total, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted fs-6">{{__('pagination.change_due')}}:</span>
                                            <span id="changeDue{{$order->id}}" class="fw-bold fs-4 text-success">0.00</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Transaction ID (for non-cash payments) -->
                                <div class="col-12 mt-4" id="transactionIdSection{{$order->id}}">
                                    <label class="form-label fw-bold text-gray-800">
                                        <i class="ki-duotone ki-barcode fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        {{__('payments.transaction_id')}} ({{__('pagination.optional')}})
                                    </label>
                                    <input type="text" 
                                        class="form-control form-control-lg form-control-solid" 
                                        id="transactionId{{$order->id}}" 
                                        placeholder="{{__('payments.enter_transaction_id')}}">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Selected Account Info -->
                    <div class="card card-flush bg-light-primary border border-primary border-dashed mt-4" id="selectedAccountInfo{{$order->id}}">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-40px symbol-circle me-4">
                                        <div class="symbol-label bg-primary">
                                            <i class="ki-duotone ki-wallet fs-2 text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-gray-800 fs-5" id="selectedAccountName{{$order->id}}"></div>
                                        <div class="text-muted fs-7" id="selectedAccountDetails{{$order->id}}"></div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted fs-7">{{ __('passwords.balance') }}</div>
                                    <div class="fw-bold text-gray-800 fs-5" id="selectedAccountBalance{{$order->id}}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2 me-2"></i>
                    {{ __('auth._discard') }}
                </button>
                <button type="button" 
                        class="btn btn-primary d-none" 
                        id="completePaymentBtn{{$order->id}}"
                        onclick="completeOrderPayment({{$order->id}})">
                    <span class="indicator-label">
                        <i class="ki-duotone ki-check fs-2 me-2"></i>
                        {{__('pagination.complete_payment')}}
                    </span>
                    <span class="indicator-progress d-none">
                        {{__('pagination.processing')}}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    
    // Store payment methods data
    const paymentMethodsData = @json($active_payment_methods->toArray() ?? []);
    let selectedAccount = {};


    // Initialize modal
    document.addEventListener('DOMContentLoaded', function() {
        // Payment type selection
        document.querySelectorAll('.payment-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                const paymentType = this.getAttribute('data-payment-type');
                
                // Update the radio button
                const radio = this.querySelector('.payment-type-radio');
                radio.checked = true;
                
                // Show step 2
                showStep2(orderId, paymentType);
            });
        });
    });

    // Show step 2: Account selection
    function showStep2(orderId, paymentType) {
        // Hide step 1, show step 2
        document.getElementById(`step1_${orderId}`).classList.add('d-none');
        document.getElementById(`step2_${orderId}`).classList.remove('d-none');
        
        // Load accounts for this payment type
        loadAccountsForType(orderId, paymentType);
        
        // Hide complete button
        document.getElementById(`completePaymentBtn${orderId}`).classList.add('d-none');
    }

    // Go back to step 1
    function goBackToStep1(orderId) {
        document.getElementById(`step1_${orderId}`).classList.remove('d-none');
        document.getElementById(`step2_${orderId}`).classList.add('d-none');
        document.getElementById(`step3_${orderId}`).classList.add('d-none');
        document.getElementById(`completePaymentBtn${orderId}`).classList.add('d-none');
    }

    // Load accounts for selected payment type
    function loadAccountsForType(orderId, paymentType) {
        const accountsGrid = document.getElementById(`accountsGrid${orderId}`);
        accountsGrid.innerHTML = '';
        
        // Filter accounts by payment type
        const accounts = paymentMethodsData.filter(account => account.type === paymentType);
        
        if (accounts.length === 0) {
            accountsGrid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="ki-duotone ki-shield-tick fs-2 me-2"></i>
                        {{ __('pagination.no_accounts_found') }}
                    </div>
                </div>
            `;
            return;
        }
        
        // Display accounts in a grid
        accounts.forEach(account => {
            accountsGrid.innerHTML += createAccountCard(orderId, account);
        });
        
        // Add click event to account cards
        document.querySelectorAll(`#accountsGrid${orderId} .account-card`).forEach(card => {
            card.addEventListener('click', function() {
                const accountId = this.getAttribute('data-account-id');
                const accountData = JSON.parse(this.getAttribute('data-account'));
                
                // Store selected account
                selectedAccount[orderId] = accountData;
                
                // Remove selected class from all cards
                document.querySelectorAll(`#accountsGrid${orderId} .account-card`).forEach(c => {
                    c.classList.remove('border-primary', 'border-2');
                });
                
                // Add selected class to clicked card
                this.classList.add('border-primary', 'border-2');
                
                // Show step 3: Payment details
                showStep3(orderId, paymentType, accountData);
            });
        });
    }

    // Create account card HTML
    function createAccountCard(orderId, account) {
        const balanceColor = account.current_balance >= 0 ? 'text-success' : 'text-danger';
        const balanceIcon = account.current_balance >= 0 ? 'ki-up' : 'ki-down';
        
        return `
            <div class="col-md-6 col-lg-4">
                <div class="card card-flush card-dashed h-100 account-card cursor-pointer" 
                    data-account-id="${account.id}" 
                    data-account='${JSON.stringify(account)}'>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="symbol symbol-50px symbol-circle me-4">
                                <div class="symbol-label bg-light-${getAccountColor(account.type)}">
                                    <i class="${getAccountIcon(account.type)} fs-2 text-${getAccountColor(account.type)}"></i>
                                </div>
                            </div>
                            <div>
                                <div class="fw-bold text-gray-800 fs-5">${account.name}</div>
                                <div class="text-muted fs-7">${account.account_name || account.provider || ''}</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            ${account.account_number ? `
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ki-duotone ki-profile-circle fs-4 me-2 text-muted"></i>
                                    <span class="text-muted fs-7">${account.account_number}</span>
                                </div>
                            ` : ''}
                            
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="badge badge-light-${getAccountColor(account.type)} fw-bold">
                                    ${getAccountTypeLabel(account.type)}
                                </span>
                                <div class="text-end">
                                    <div class="text-muted fs-7">{{ __('passwords.balance') }}</div>
                                    <div class="fw-bold ${balanceColor} fs-5">
                                        <i class="ki-duotone ${balanceIcon} fs-3 me-1"></i>
                                        {{ currencySymbol() }}${parseFloat(account.current_balance).toFixed(2)}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        ${account.is_default ? `
                            <div class="text-center mt-3">
                                <span class="badge badge-light-success fw-bold">
                                    <i class="ki-duotone ki-star fs-4 me-1"></i>
                                    {{ __('passwords.default') }}
                                </span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }

    // Show step 3: Payment details
    function showStep3(orderId, paymentType, accountData) {
        // Hide step 2, show step 3
        document.getElementById(`step2_${orderId}`).classList.add('d-none');
        document.getElementById(`step3_${orderId}`).classList.remove('d-none');
        
        // Show/hide transaction ID based on payment type
        const transactionSection = document.getElementById(`transactionIdSection${orderId}`);
        if (paymentType === 'cash') {
            transactionSection.classList.add('d-none');
        } else {
            transactionSection.classList.remove('d-none');
        }
        
        // Update selected account info
        document.getElementById(`selectedAccountName${orderId}`).textContent = accountData.name;
        
        let accountDetails = '';
        if (accountData.account_name) accountDetails += accountData.account_name;
        if (accountData.account_number) accountDetails += accountDetails ? ` • ${accountData.account_number}` : accountData.account_number;
        if (accountData.provider) accountDetails += accountDetails ? ` • ${accountData.provider}` : accountData.provider;
        
        document.getElementById(`selectedAccountDetails${orderId}`).textContent = accountDetails || '{{__("pagination.no_additional_info")}}';
        document.getElementById(`selectedAccountBalance${orderId}`).textContent = '{{ currencySymbol() }}' + parseFloat(accountData.current_balance).toFixed(2);
        
        // Focus on amount tendered input
        setTimeout(() => {
            document.getElementById(`amountTendered${orderId}`).focus();
            document.getElementById(`amountTendered${orderId}`).select();
        }, 100);
        
        // Initialize change calculation
        calculateChangeSimple(orderId);
        
        // Show complete payment button
        document.getElementById(`completePaymentBtn${orderId}`).classList.remove('d-none');
    }



    function calculateChangeSimple(orderId) {
        const amountTendered = parseFloat(document.getElementById(`amountTendered${orderId}`).value) || 0;
        
        // Get the total from a data attribute instead of inline PHP
        const total = parseFloat(document.getElementById(`amountTendered${orderId}`).getAttribute('data-order-total')) || 0;
        
        const change = amountTendered - total;
        
        const changeEl = document.getElementById(`changeDue${orderId}`);
        
        if (change >= 0) {
            changeEl.textContent = '{{ currencySymbol() }}' + change.toFixed(2);
            changeEl.className = 'fw-bold fs-4 text-success';
        } else {
            changeEl.textContent = '{{ currencySymbol() }}0.00';
            changeEl.className = 'fw-bold fs-4 text-danger';
        }
    }


    // Complete order payment - FIXED VERSION with proper item formatting
    function completeOrderPayment(orderId) {
        const submitButton = document.getElementById(`completePaymentBtn${orderId}`);
        
        // Get selected payment type
        const paymentType = document.querySelector(`input[name="payment_type${orderId}"]:checked`);
        if (!paymentType) {
            toastr.error('{{ __("pagination.select_payment_method") }}');
            return;
        }
        
        // Check if account is selected
        if (!selectedAccount[orderId]) {
            toastr.error('{{ __("pagination.select_account") }}');
            return;
        }
        
        const accountData = selectedAccount[orderId];
        
        // Get amount tendered - remove commas if any
        const amountTenderedInput = document.getElementById(`amountTendered${orderId}`);
        const amountTendered = parseFloat(amountTenderedInput.value.replace(/,/g, '')) || 0;
        const total = parseFloat(amountTenderedInput.getAttribute('data-order-total') || '{{ $order->total }}'.replace(/,/g, ''));
        
        // Validate amount
        if (amountTendered < total) {
            toastr.error('{{ __("pagination.insufficient_amount") }}');
            amountTenderedInput.focus();
            return;
        }
        
        // Get the order items and format them properly
        const orderItemsRaw = JSON.parse(document.getElementById(`orderItems${orderId}`)?.value || '[]');
        
        // Format items to match the structure your PHP expects
        const orderItems = orderItemsRaw.map(item => ({
            variant_id: item.variant_id || item.product_variant_id,
            quantity: item.quantity,
            price: item.price || item.unit_price,
            product_id: item.product_id,
            name: item.name || item.product_name || '', // Add name field
            // Include any other fields that might be needed
            subtotal: item.subtotal || (item.quantity * item.price)
        }));
        
        // Prepare payment details
        const paymentDetails = {
            transaction_id: document.getElementById(`transactionId${orderId}`)?.value || '',
            amount_tendered: amountTendered,
            change_due: Math.max(0, amountTendered - total)
        };
        
        // Prepare request data in the EXACT format your PHP expects
        const requestData = {
            order_id: orderId,
            payment_method_id: accountData.id,
            total: total,
            subtotal: parseFloat(document.getElementById(`orderSubtotal${orderId}`)?.value || total),
            tax: parseFloat(document.getElementById(`orderTax${orderId}`)?.value || 0),
            items: orderItems, // Properly formatted items
            payment_details: paymentDetails
        };
        
        console.log('Sending payment data:', requestData);
        
        // Show loading state
        LiveBlade.toggleButtonLoading(submitButton, true);
        
        // Call completePayment API
        fetch("{{ route('orders.complete-payment') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(requestData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message || '{{ __("pagination.payment_completed") }}');
                
                // Show payment summary
                console.log('Payment successful:', {
                    payment_method: data.payment_method,
                    balance_before: data.balance_before,
                    balance_after: data.balance_after,
                    transaction_id: data.transaction_id
                });
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById(`completePayment${orderId}`));
                if (modal) modal.hide();
                
                // Reload page to show updated status
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                toastr.error(data.message || '{{ __("pagination.payment_failed") }}');
            }
        })
        .catch(error => {
            console.error('Payment error:', error);
            toastr.error('{{ __("pagination.payment_failed") }}');
        })
        .finally(() => {
            LiveBlade.toggleButtonLoading(submitButton, false);
        });
    }




    // Helper functions
    function getAccountColor(type) {
        const colors = {
            'cash': 'primary',
            'mobile_money': 'warning',
            'card': 'success',
            'bank_account': 'info',
            'digital_wallet': 'danger',
            'check': 'dark',
            'credit': 'secondary',
            'other': 'gray-600'
        };
        return colors[type] || 'primary';
    }

    function getAccountIcon(type) {
        const icons = {
            'cash': 'ki-duotone ki-dollar',
            'mobile_money': 'ki-duotone ki-finance-calculator',
            'card': 'ki-duotone ki-credit-cart',
            'bank_account': 'ki-duotone ki-bank',
            'digital_wallet': 'ki-duotone ki-wallet',
            'check': 'ki-duotone ki-document',
            'credit': 'ki-duotone ki-time',
            'other': 'ki-duotone ki-add-files'
        };
        return icons[type] || 'ki-duotone ki-wallet';
    }

    function getAccountTypeLabel(type) {
        const labels = {
            'cash': '{{__("pagination._cash")}}',
            'mobile_money': '{{__("pagination.mobile_money")}}',
            'card': '{{__("pagination._card")}}',
            'bank_account': '{{__("payments.bank_account")}}',
            'digital_wallet': '{{__("payments.digital_wallet")}}',
            'check': '{{__("payments.check")}}',
            'credit': '{{__("payments.credit")}}',
            'other': '{{__("payments.other")}}'
        };
        return labels[type] || type;
    }


</script>
