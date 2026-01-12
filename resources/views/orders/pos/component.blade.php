<div class="d-flex flex-column flex-xl-row">
    <div class="d-flex flex-row-fluid me-xl-9 mb-10 mb-xl-0">
        <div class="card card-flush card-p-0 bg-transparent border-0">
            <div class="card-body">
                <ul class="nav nav-pills nav-pills-custom gap-3 mb-6 d-flex flex-nowrap" 
                    id="productList"
                    style="overflow-x: auto; white-space: nowrap; list-style: none; padding: 0;">
                    @php $first = true; @endphp
                    @foreach ($products as $product)
                        @if ($product->variants->count() > 0)
                            @php
                                $words = explode(' ', $product->name);
                                $truncated = count($words) > 2 
                                    ? implode(' ', array_slice($words, 0, 2)) . '...' 
                                    : $product->name;
                                
                                // Get department IDs as comma-separated string
                                $departmentIds = $product->departments->pluck('id')->implode(',');
                            @endphp
                            <li class="nav-item mb-3 me-0 product-item" 
                                style="min-width: 180px;"
                                data-department="{{ $departmentIds }}"> <!-- Added data attribute -->
                                <a class="nav-link nav-link-border-solid btn btn-outline btn-flex btn-active-color-primary flex-column flex-stack pt-9 pb-7 page-bg {{ $first ? 'active' : '' }}" 
                                data-bs-toggle="pill" 
                                href="#kt_pos_{{ $product->id }}" 
                                style="width: 100%; height: 180px">
                                    <div class="nav-icon mb-3">
                                        <img src="{{ productImage($product->image_url) }}" class="w-50px" alt="" />
                                    </div>
                                    <div>
                                        <span class="text-gray-800 fw-bold fs-2 d-block" 
                                            data-bs-toggle="tooltip" 
                                            title="{{ $product->name }}">
                                            {{ $truncated }}
                                        </span>
                                        <span class="text-gray-500 fw-semibold fs-7">
                                            {{ $product->variants->count() }} {{ __('pagination._options') }}
                                        </span>
                                    </div>
                                </a>
                            </li>
                            @php $first = false; @endphp
                        @endif
                    @endforeach

                    @if ($products->filter(fn($p) => $p->variants->count() > 0)->isEmpty())
                        <div class="card-header pt-5">
                            <h3 class="card-title fw-bold text-gray-800 fs-2qx">{{ __('pagination.not_assigned') }}</h3>
                        </div>
                    @endif
                </ul>
                <!--end::Nav-->

                <!--begin::Tab Content-->
                <div class="tab-content" id="variantTabContent">
                    @php $firstTab = true; @endphp
                    @foreach ($products as $product)
                        @if ($product->variants->count() > 0)
                            <div class="tab-pane fade {{ $firstTab ? 'show active' : '' }}" id="kt_pos_{{ $product->id }}">
                                <div class="d-flex flex-wrap d-grid gap-5 gap-xxl-9 variant-container">
                                    @foreach ($product->variants as $variant)
                                        <div class="card card-flush flex-row-fluid p-6 pb-5 mw-100 variant-item position-relative" 
                                            data-name="{{ strtolower($variant->name ?? $product->name) }}"
                                            data-product="{{ $product->id }}"
                                            onclick="addToCart({{ json_encode([
                                                'id' => $variant->id,
                                                'name' => $variant->name ?? $product->name,
                                                'price' => $variant->price,
                                                'image' => productVariantImage($variant->image_url ?? $product->image_url),
                                                'quantity_available' => $variant->quantity_available ?? 0,
                                                'taxes' => $variant->applicable_taxes ?? [],  
                                                'promotions' => $variant->applicable_promotions ?? []  
                                            ]) }})"
                                            style="cursor: pointer;">

                                            {{-- ✅ Top-left Green Tag for Taxes --}}
                                            @if (!empty($variant->applicable_taxes) && count($variant->applicable_taxes) > 0)
                                                <span class="badge bg-danger text-white position-absolute top-0 start-0 m-2 px-3 py-2 shadow-sm">
                                                    {{ __('passwords._tax') }}
                                                </span>
                                            @endif

                                            {{-- ✅ Top-right Red Tag for Promotions --}}
                                            @if (!empty($variant->applicable_promotions) && count($variant->applicable_promotions) > 0)
                                                <span class="badge bg-success text-white position-absolute top-0 end-0 m-2 px-3 py-2 shadow-sm">
                                                    {{ __('passwords._promo') }}
                                                </span>
                                            @endif

                                            <div class="card-body text-center">
                                                <img src="{{ productVariantImage($variant->image_url ?? $product->image_url) }}" 
                                                    class="rounded-3 mb-4 w-150px h-150px w-xxl-200px h-xxl-200px" 
                                                    alt="{{ $variant->name ?? $product->name }}" />

                                                <div class="mb-2">
                                                    <div class="text-center">
                                                        <span class="fw-bold text-gray-800 cursor-pointer text-hover-primary fs-3 fs-xl-1"
                                                            data-bs-toggle="tooltip" 
                                                            title="{{ $variant->name ?? $product->name }}">
                                                            {{ \Illuminate\Support\Str::words($variant->name ?? $product->name, 2, '...') }}
                                                        </span>
                                                        <span class="text-gray-500 fw-semibold d-block fs-6 mt-n1">
                                                            {{ $variant->quantity_available ?? 0 }} {{__('pagination._available')}}
                                                        </span>
                                                    </div>
                                                </div>

                                                <span class="text-success text-end fw-bold fs-1">
                                                    {{ displayFormatedCurrency($variant->price) }} {{ currencySymbol() }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @php $firstTab = false; @endphp
                        @endif
                    @endforeach
                </div>
                <!--end::Tab Content-->

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const departmentFilter = document.getElementById('departmentFilter');
                        const productItems = document.querySelectorAll('.product-item');
                        const productList = document.getElementById('productList');
                        
                        departmentFilter.addEventListener('change', function() {
                            const selectedDepartment = this.value;
                            let hasVisibleProducts = false;
                            
                            productItems.forEach(function(item) {
                                const itemDepartments = item.getAttribute('data-department').split(',');
                                
                                if (selectedDepartment === '' || itemDepartments.includes(selectedDepartment)) {
                                    item.style.display = ''; // Show the item
                                    hasVisibleProducts = true;
                                } else {
                                    item.style.display = 'none'; // Hide the item
                                }
                            });
                            
                            // Remove any existing "no products" message
                            const existingMessage = productList.querySelector('.no-products-message');
                            if (existingMessage) {
                                existingMessage.remove();
                            }
                            
                            // If no products match and a department is selected, show message
                            if (!hasVisibleProducts && selectedDepartment !== '') {
                                const messageDiv = document.createElement('div');
                                messageDiv.className = 'card-header pt-5 no-products-message';
                                messageDiv.innerHTML = '<h3 class="card-title fw-bold text-gray-800 fs-2qx">{{ __("pagination.no_products_in_department") }}</h3>';
                                productList.appendChild(messageDiv);
                            }
                        });
                    });
                </script>

                <script>
                    function filterVariants(searchTerm) {
                        const searchValue = searchTerm.toLowerCase().trim();
                        const allVariantItems = document.querySelectorAll('.variant-item');
                        const activeTab = document.querySelector('.tab-pane.active');
                        
                        if (searchValue === '') {
                            // Show all variants when search is empty
                            allVariantItems.forEach(item => {
                                item.style.display = '';
                            });
                            return;
                        }

                        // Filter variants based on search term
                        allVariantItems.forEach(item => {
                            const variantName = item.getAttribute('data-name');
                            const isInActiveTab = activeTab && activeTab.contains(item);
                            
                            if (variantName.includes(searchValue)) {
                                item.style.display = '';
                                // If not in active tab, ensure it's visible when user switches to that tab
                            } else {
                                item.style.display = 'none';
                            }
                        });

                        // Optional: Show message if no variants found in active tab
                        const visibleVariantsInActiveTab = activeTab ? 
                            activeTab.querySelectorAll('.variant-item[style=""]').length : 0;
                        
                        if (visibleVariantsInActiveTab === 0 && searchValue !== '') {
                            // You could add a "no results" message here if desired
                            console.log('No variants found matching search');
                        }
                    }

                    // Optional: Add event listener for tab changes to reapply search filter
                    document.addEventListener('DOMContentLoaded', function() {
                        const tabPanes = document.querySelectorAll('.tab-pane');
                        
                        tabPanes.forEach(tab => {
                            tab.addEventListener('shown.bs.tab', function () {
                                const searchInput = document.getElementById('variantSearchInput');
                                if (searchInput.value) {
                                    filterVariants(searchInput.value);
                                }
                            });
                        });
                    });
                </script>

            </div>
        </div>
    </div>
    <!--end::Content-->


    <!--begin::Body-->
    <div class="flex-row-auto w-xl-450px">
        <!--begin::Pos order-->
        <div id="kt_pos_form" class="card card-flush bg-body">
            <div class="card-header pt-5">
                <h3 class="card-title fw-bold text-gray-800 fs-2qx">{{__('pagination._current_order')}}</h3>
                <div class="card-toolbar">
                    <a href="#" class="btn btn-light-primary fs-4 fw-bold py-4" onclick="clearCart()">{{__('pagination.clear_all')}}</a>
                </div>
            </div>

            <!--begin::Body-->
            <div class="card-body pt-0">
                <div class="table-responsive mb-8">
                    <table class="table align-middle gs-0 gy-4 my-0">
                        <thead>
                            <tr>
                                <th class="min-w-175px">{{__('pagination._item')}}</th>
                                <th class="w-125px">{{__('pagination._quantity')}}</th>
                                <th class="w-60px">{{__('pagination._total')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Cart items will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>

                <!--begin::Summary-->
                <div class="d-flex flex-stack bg-success rounded-3 p-6 mb-11">
                    <!--begin::Content-->
                    <div class="fs-6 fw-bold text-white">
                        <span class="d-block lh-1 mb-2">{{__('pagination.sub_total')}}</span>
                        <span class="d-block mb-2">{{__('pagination._discount')}}</span>
                        <span class="d-block mb-9">{{__('pagination._tax')}}</span>
                        <span class="d-block fs-2qx lh-1">{{__('pagination.grant_total')}}</span>
                    </div>
                    <!--end::Content-->
                    <!--begin::Content-->
                    <div class="fs-6 fw-bold text-white text-end">
                        <span class="d-block lh-1 mb-2" data-kt-pos-element="total">0.00</span>
                        <span class="d-block mb-2" data-kt-pos-element="discount">-0.00</span>
                        <span class="d-block mb-9" data-kt-pos-element="tax">0.20</span>
                        <span class="d-block fs-2qx lh-1" data-kt-pos-element="grant-total">0.00</span>
                    </div>
                    <!--end::Content-->
                </div>
                
                
                <!--begin::Customer-->
                <div class="m-0">
                    <div class="mb-8">
                        <h3 class="fw-bold text-gray-800 mb-5">{{__('pagination.select_customer')}}</h3>

                        <!-- Radio Options -->
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-center gap-2">
                                <input type="radio" name="customerOption" id="existingOption" value="existing" checked>
                                <label for="existingOption" class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 px-4">
                                    {{__('pagination.existing_customer')}}
                                </label>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="radio" name="customerOption" id="newOption" value="new">
                                <label for="newOption" class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 px-4">
                                    {{__('pagination.new_customer')}}
                                </label>
                            </div>
                        </div>

                        <!-- Existing Customer -->
                        <div id="existingCustomerWrapper" class="mt-4">
                            <select class="form-select form-select-lg" id="existingCustomer">
                                <option value="">{{__('pagination.choose_customer')}}</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->first_name }} {{ $customer->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- New Customer -->
                        <div id="newCustomerWrapper" class="mt-4 d-none">
                            <input type="text" class="form-control form-control-lg" 
                                placeholder="{{__('pagination.customer_name_placeholder')}}" 
                                id="newCustomer">
                        </div>
                    </div>
                </div>
                
                <!--begin::Payment Method-->
                <div class="m-0">
                    <h3 class="fw-bold text-gray-800 mb-5">{{__('pagination.payment_method')}}</h3>
                    
                    <!--begin::Radio group - Row 1 (3 items)-->
                    <div class="row g-4 mb-8">
                        <!-- Mobile Money -->
                        <div class="col-lg-4 col-xxl-3">
                            <label class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                <input class="btn-check" type="radio" name="method" value="mobile_money" />
                                <i class="ki-duotone ki-finance-calculator fs-2hx mb-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span class="fs-7 fw-bold text-center">{{__('pagination.mobile_money')}}</span>
                            </label>
                        </div>
                        
                        <!-- Cash (Default) -->
                        <div class="col-lg-4 col-xxl-3">
                            <label class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4 active">
                                <input class="btn-check" type="radio" name="method" value="cash" checked="checked"/>
                                <i class="ki-duotone ki-dollar fs-2hx mb-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <span class="fs-7 fw-bold text-center">{{__('pagination._cash')}}</span>
                            </label>
                        </div>
                        
                        <!-- Card -->
                        <div class="col-lg-4 col-xxl-3">
                            <label class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                <input class="btn-check" type="radio" name="method" value="card" />
                                <i class="ki-duotone ki-credit-cart fs-2hx mb-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span class="fs-7 fw-bold text-center">{{__('pagination._card')}}</span>
                            </label>
                        </div>
                        
                        <!-- Bank Account -->
                        <div class="col-lg-4 col-xxl-3">
                            <label class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                <input class="btn-check" type="radio" name="method" value="bank_account" />
                                <i class="ki-duotone ki-bank fs-2hx mb-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span class="fs-7 fw-bold text-center">{{__('payments.bank_account')}}</span>
                            </label>
                        </div>
                    </div>
                    
                    <!--begin::Radio group - Row 2 (3 items)-->
                    <div class="row g-4 mb-8">
                        <!-- Digital Wallet -->
                        <div class="col-lg-4 col-xxl-3">
                            <label class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                <input class="btn-check" type="radio" name="method" value="digital_wallet" />
                                <i class="ki-duotone ki-wallet fs-2hx mb-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span class="fs-7 fw-bold text-center">{{__('payments.digital_wallet')}}</span>
                            </label>
                        </div>
                        
                        <!-- Check -->
                        <div class="col-lg-4 col-xxl-3">
                            <label class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                <input class="btn-check" type="radio" name="method" value="check" />
                                <i class="ki-duotone ki-document fs-2hx mb-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span class="fs-7 fw-bold text-center">{{__('payments.check')}}</span>
                            </label>
                        </div>
                        
                        <!-- Credit -->
                        <div class="col-lg-4 col-xxl-3">
                            <label class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                <input class="btn-check" type="radio" name="method" value="credit" />
                                <i class="ki-duotone ki-time fs-2hx mb-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span class="fs-7 fw-bold text-center">{{__('payments.credit')}}</span>
                            </label>
                        </div>
                        
                        <!-- Other -->
                        <div class="col-lg-4 col-xxl-3">
                            <label class="btn bg-light btn-color-gray-600 btn-active-text-gray-800 border border-3 border-gray-100 border-active-primary btn-active-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                                <input class="btn-check" type="radio" name="method" value="other" />
                                <i class="ki-duotone ki-add-files fs-2hx mb-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span class="fs-7 fw-bold text-center">{{__('payments.other')}}</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Process Bill Button -->
                    <div class="mt-8">
                        <button 
                            id="processBill" 
                            type="button" 
                            class="btn btn-primary fs-1 w-100 py-4"
                            onclick="processPayment()">
                            <span class="indicator-label">{{__('pagination.print_bill')}}</span>
                            <span class="indicator-progress">{{__('pagination.processing_payments')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>

                <style>
                    /* Custom styles for payment method buttons */
                    .btn-check:checked + .btn {
                        background-color: var(--kt-primary-light) !important;
                        border-color: var(--kt-primary) !important;
                        color: var(--kt-primary) !important;
                    }

                    .btn-check:focus + .btn {
                        box-shadow: 0 0 0 0.25rem rgba(0, 158, 247, 0.25);
                    }

                    /* Ensure consistent button height */
                    .btn.h-100 {
                        min-height: 120px;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.2s ease;
                    }

                    .btn.h-100:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                    }

                    /* Responsive adjustments */
                    @media (max-width: 768px) {
                        .btn.h-100 {
                            min-height: 100px;
                        }
                        
                        .row.g-4 > div {
                            margin-bottom: 1rem;
                        }
                    }

                    @media (max-width: 576px) {
                        .row.g-4 {
                            --bs-gutter-x: 0.5rem;
                            --bs-gutter-y: 0.5rem;
                        }
                        
                        .btn.h-100 {
                            min-height: 90px;
                            padding: 1rem !important;
                        }
                        
                        .fs-2hx {
                            font-size: 1.75rem !important;
                        }
                    }
                </style>

                <script>
                    // Ensure only one payment method can be selected at a time
                    document.addEventListener('DOMContentLoaded', function() {
                        const paymentRadios = document.querySelectorAll('input[name="method"]');
                        
                        // Set cash as default
                        const cashRadio = document.querySelector('input[value="cash"]');
                        if (cashRadio) {
                            cashRadio.checked = true;
                        }
                        
                        // Handle radio button changes
                        paymentRadios.forEach(radio => {
                            radio.addEventListener('change', function() {
                                // Remove active class from all labels
                                document.querySelectorAll('label.btn').forEach(label => {
                                    label.classList.remove('active');
                                });
                                
                                // Add active class to parent label of selected radio
                                if (this.checked) {
                                    this.closest('label').classList.add('active');
                                }
                                
                                // Log selected method (for debugging)
                                console.log('Selected payment method:', this.value);
                            });
                        });
                    });
                </script>
            </div>
        </div>
    </div>
    <!--end::Sidebar-->
</div>

<script>
    const existingOption = document.getElementById('existingOption');
    const newOption = document.getElementById('newOption');
    const existingWrapper = document.getElementById('existingCustomerWrapper');
    const newWrapper = document.getElementById('newCustomerWrapper');

    function toggleCustomerFields() {
        if (existingOption.checked) {
            existingWrapper.classList.remove('d-none');
            newWrapper.classList.add('d-none');
        } else {
            existingWrapper.classList.add('d-none');
            newWrapper.classList.remove('d-none');
        }
    }

    // Init state
    toggleCustomerFields();

    // Listen for changes
    existingOption.addEventListener('change', toggleCustomerFields);
    newOption.addEventListener('change', toggleCustomerFields);
</script>
@include('orders.pos.payment-mode')


