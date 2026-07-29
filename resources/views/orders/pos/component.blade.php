@can('view order')
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
                                // Get department IDs as comma-separated string
                                $departmentIds = $product->departments->pluck('id')->implode(',');
                            @endphp
                            <li class="nav-item mb-3 me-0 product-item" 
                                style="min-width: 180px;"
                                data-department="{{ $departmentIds }}"> <!-- Added data attribute -->
                                <a class="nav-link nav-link-border-solid btn btn-outline btn-active-color-primary product-card page-bg {{ $first ? 'active' : '' }}" 
                                    data-bs-toggle="pill" 
                                    href="#kt_pos_{{ $product->id }}">
                                        <div class="product-icon">
                                            <img src="{{ productImage($product->image_url) }}" class="w-50px" alt="" />
                                        </div>
                                        <div class="product-name-wrap">
                                            <span class="product-name" data-bs-toggle="tooltip" title="{{ $product->name }}">
                                                {{ $product->name }}
                                            </span>
                                        </div>
                                        <span class="product-options">
                                            {{ $product->variants->count() }} {{ __('pagination._options') }}
                                        </span>
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
                
                <style>
                    .product-item .product-card {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        width: 100%;
                        height: 180px;
                        padding: 14px 10px 10px;
                        overflow: hidden;
                        box-sizing: border-box;
                    }

                    /* Zone 1: icon — fixed size, never shrinks */
                    .product-item .product-icon {
                        flex: 0 0 auto;
                        margin-bottom: 8px;
                    }

                    /* Zone 2: name — the ONLY flexible zone, allowed to clamp/shrink */
                    .product-item .product-name-wrap {
                        flex: 1 1 auto;
                        min-height: 0;          /* critical: lets flex child shrink below content size */
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 100%;
                        overflow: hidden;
                    }

                    .product-item .product-name {
                        display: -webkit-box;
                        -webkit-line-clamp: 2;
                        -webkit-box-orient: vertical;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: normal;
                        word-break: break-word;
                        text-align: center;
                        line-height: 1.2;
                        font-weight: 700;
                        color: #3f4254;
                        font-size: clamp(0.75rem, 0.9vw + 0.4rem, 0.95rem); /* smaller, fits 2 lines safely */
                    }

                    /* Zone 3: options — fixed, always rendered, never clipped */
                    .product-item .product-options {
                        flex: 0 0 auto;
                        font-size: 0.75rem;
                        font-weight: 600;
                        color: #99a1b7;
                        margin-top: 4px;
                        white-space: nowrap;
                    }
                </style>

                <!--begin::Tab Content-->
                <div class="tab-content" id="variantTabContent">
                    @php $firstTab = true; @endphp
                    @foreach ($products as $product)
                        @if ($product->variants->count() > 0)
                            <div class="tab-pane fade {{ $firstTab ? 'show active' : '' }}" id="kt_pos_{{ $product->id }}">
                                <div class="d-flex flex-wrap d-grid gap-5 gap-xxl-9 variant-container">
                                    @foreach ($product->variants as $variant)
                                        @php
                                        $inventoryJson = !$isSingleShop ? json_encode($variant->inventory_by_dept ?? []) : '{}';
                                    @endphp
                                    <div class="card card-flush flex-row-fluid p-6 pb-5 mw-100 variant-item position-relative" 
                                        data-name="{{ strtolower($variant->name ?? $product->name) }}"
                                        data-product="{{ $product->id }}"
                                        data-variant-id="{{ $variant->id }}"
                                        data-price="{{ $variant->selling_price }}"
                                        data-image="{{ productVariantImage($variant->image_url ?? $product->image_url) }}"
                                        data-taxes='@json($variant->applicable_taxes ?? [])'
                                        data-promotions='@json($variant->applicable_promotions ?? [])'
                                        @if(!$isSingleShop) data-inventory='{{ $inventoryJson }}' @endif
                                        onclick="handleVariantClick(this)"
                                        style="cursor: pointer;">

                                            {{-- ✅ Top-left Badge for Taxes --}}
                                            @if(!empty($variant->applicable_taxes) && count($variant->applicable_taxes) > 0)
                                                <span class="badge bg-danger text-white position-absolute top-0 start-0 m-2 px-3 py-2 shadow-sm">
                                                    {{ __('passwords._tax') }}
                                                </span>
                                            @endif

                                            {{-- ✅ Top-right Badge for Promotions --}}
                                            @if(!empty($variant->applicable_promotions) && count($variant->applicable_promotions) > 0)
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
                                                        <span class="text-gray-500 fw-semibold d-block fs-6 mt-n1 variant-qty">
                                                            {{ $variant->quantity_available ?? 0 }} {{__('pagination._available')}}
                                                        </span>
                                                    </div>
                                                </div>

                                                <span class="text-success text-end fw-bold fs-1">
                                                    {{ $variant->selling_price }} {{ currency_symbol() }}
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
                    <a href="javascript:void(0);" class="btn btn-light-primary fs-4 fw-bold py-4" onclick="clearCart()">{{__('pagination.clear_all')}}</a>
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
                        <tbody id="pos-cart-tbody">
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
                        <h3 class="fw-bold text-gray-800 mb-5">{{ __('pagination.select_customer') }}</h3>

                        <!-- Toggle Buttons -->
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="btn btn-outline btn-outline-dashed btn-outline-default d-flex flex-column align-items-center justify-content-center gap-2 py-4 w-100 cursor-pointer"
                                    id="btn-pick-existing">
                                    <i class="ki-duotone ki-profile-circle fs-2x text-primary">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                    <span class="fw-bold fs-6 text-gray-800">{{ __('pagination.existing_customer') }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="btn btn-outline btn-outline-dashed btn-outline-default d-flex flex-column align-items-center justify-content-center gap-2 py-4 w-100 cursor-pointer"
                                    id="btn-pick-new">
                                    <i class="ki-duotone ki-user-edit fs-2x text-primary">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                    <span class="fw-bold fs-6 text-gray-800">{{ __('pagination.new_customer') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Hidden radio inputs -->
                        <input type="radio" name="cust_mode" id="cust-mode-existing" value="existing" class="d-none">
                        <input type="radio" name="cust_mode" id="cust-mode-new" value="new" class="d-none">

                        <!-- Panel: Existing Customer -->
                        <div id="panel-existing-cust" class="d-none">
                            <label class="form-label fw-semibold text-gray-700">{{ __('pagination.choose_customer') }}</label>
                            <select class="form-select form-select-lg" id="cust-existing-select" style="width: 100%">
                                <option value="">— {{ __('pagination.choose_customer') }} —</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->first_name }} {{ $customer->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Panel: New Customer -->
                        <div id="panel-new-cust" class="d-none">
                            <label class="form-label fw-semibold text-gray-700">{{ __('pagination.customer_name_placeholder') }}</label>
                            <input type="text"
                                class="form-control form-control-lg"
                                id="cust-new-input"
                                placeholder="{{ __('pagination.customer_name_placeholder') }}">
                        </div>

                    </div>
                </div>
                                
                <script>
                    // ============================================================
                    // SEARCH FUNCTIONALITY
                    // ============================================================
                    let searchTimeout = null;
                    let isSearching = false;
                    let searchActive = false;
                    let originalTabContentHTML = null;

                    document.addEventListener('DOMContentLoaded', function () {
                        const tabContent = document.getElementById('variantTabContent');
                        if (tabContent) originalTabContentHTML = tabContent.innerHTML;
                    });

                    function filterProductsAndVariants(searchTerm) {
                        if (searchTimeout) {
                            clearTimeout(searchTimeout);
                        }

                        if (!searchTerm || searchTerm.trim() === '') {
                            restoreOriginalView();
                            return;
                        }

                        searchTimeout = setTimeout(function() {
                            performSearch(searchTerm.trim());
                        }, 400);
                    }

                    function restoreOriginalView() {
                        searchActive = false;

                        // Remove search result message
                        const existingMsg = document.getElementById('productList')?.querySelector('.search-result-msg');
                        if (existingMsg) {
                            existingMsg.remove();
                        }

                        // ✅ Show all product pills
                        document.querySelectorAll('.product-item').forEach(item => {
                            item.style.display = '';
                        });

                        // ✅ Restore the original tab content
                        const tabContent = document.getElementById('variantTabContent');
                        if (tabContent && originalTabContentHTML !== null) {
                            tabContent.innerHTML = originalTabContentHTML;
                        }

                        // ✅ Activate the first product pill
                        const firstPill = document.querySelector('.product-item:not([style*="none"]) a[data-bs-toggle="pill"]');
                        if (firstPill) {
                            if (window.bootstrap?.Tab) {
                                bootstrap.Tab.getOrCreateInstance(firstPill).show();
                            } else {
                                firstPill.click();
                            }
                        }

                        // Remove no results message
                        const noResults = document.querySelector('.no-results-message');
                        if (noResults) noResults.remove();

                        // Clear search input
                        const searchInput = document.getElementById('variantSearchInput');
                        if (searchInput) {
                            searchInput.value = '';
                        }
                    }


                    function performSearch(searchTerm) {
                        if (isSearching) return;
                        isSearching = true;
                        searchActive = true;

                        const departmentId = document.getElementById('departmentFilter')?.value || '';

                        @if(!$isSingleShop)
                        if (!departmentId) {
                            toastr.warning('Please select a department first');
                            isSearching = false;
                            return;
                        }
                        @endif

                        const tabContent = document.getElementById('variantTabContent');

                        // ✅ Show loading state inside tab content
                        if (tabContent) {
                            tabContent.innerHTML = `
                                <div class="text-center py-10 w-100">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-3 text-gray-500">Searching for "${searchTerm}"...</p>
                                </div>
                            `;
                        }

                        fetch(`/pos/search?search=${encodeURIComponent(searchTerm)}&department=${departmentId}`, {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(data => {
                            isSearching = false;
                            if (data.success && data.products && data.products.length > 0) {
                                renderSearchResults(data.products, data.is_single_shop);
                            } else {
                                showNoResults(searchTerm);
                            }
                        })
                        .catch(error => {
                            isSearching = false;
                            console.error('Search error:', error);
                            toastr.error('Error performing search. Please try again.');
                            restoreOriginalView();
                        });
                    }

                    function renderSearchResults(products, isSingleShop) {
                        const tabContent = document.getElementById('variantTabContent');
                        if (!tabContent) return;

                        // ✅ Clear ALL existing content first
                        tabContent.innerHTML = '';

                        let hasProducts = false;
                        let firstProduct = true;
                        const foundProductIds = new Set();

                        products.forEach(product => {
                            if (product.variants && product.variants.length > 0) {
                                hasProducts = true;
                                foundProductIds.add(product.id);

                                // Create the tab pane
                                const tabPane = document.createElement('div');
                                tabPane.className = `tab-pane fade ${firstProduct ? 'show active' : ''}`;
                                tabPane.id = `kt_pos_${product.id}`;

                                // Create the variant container INSIDE the tab pane
                                const grid = document.createElement('div');
                                grid.className = 'd-flex flex-wrap d-grid gap-5 gap-xxl-9 variant-container';

                                product.variants.forEach(variant => {
                                    const card = createVariantCard(variant, product, isSingleShop);
                                    grid.appendChild(card);
                                });

                                tabPane.appendChild(grid);
                                tabContent.appendChild(tabPane);
                                firstProduct = false;
                            }
                        });

                        if (!hasProducts) {
                            showNoResults('');
                        } else {
                            // ✅ Update product pills - hide all and show only found products
                            updateProductPills(foundProductIds, products.length);
                        }
                    }


                    function updateProductPills(foundProductIds, totalCount) {
                        const productList = document.getElementById('productList');
                        if (!productList) return;

                        // Remove existing search result message
                        const existingMsg = productList.querySelector('.search-result-msg');
                        if (existingMsg) {
                            existingMsg.remove();
                        }

                        // Add search result message
                        const msg = document.createElement('li');
                        msg.className = 'search-result-msg nav-item me-3';
                        msg.innerHTML = `
                            <span class="fw-bold text-primary fs-6">
                                <i class="ki-duotone ki-search-list fs-4 me-1"></i>
                                Search Results (${totalCount} products)
                            </span>
                        `;
                        productList.prepend(msg);

                        // ✅ HIDE ALL product pills first
                        document.querySelectorAll('.product-item').forEach(item => {
                            item.style.display = 'none';
                        });

                        // ✅ SHOW ONLY the found products
                        if (foundProductIds && foundProductIds.size > 0) {
                            document.querySelectorAll('.product-item').forEach(item => {
                                const link = item.querySelector('a');
                                if (link) {
                                    const href = link.getAttribute('href');
                                    const match = href ? href.match(/kt_pos_(\d+)/) : null;
                                    if (match && foundProductIds.has(parseInt(match[1]))) {
                                        item.style.display = '';
                                    }
                                }
                            });

                            // ✅ Activate the first found product tab
                            const firstVisiblePill = document.querySelector('.product-item:not([style*="none"]) a[data-bs-toggle="pill"]');
                            if (firstVisiblePill) {
                                if (window.bootstrap?.Tab) {
                                    bootstrap.Tab.getOrCreateInstance(firstVisiblePill).show();
                                } else {
                                    firstVisiblePill.click();
                                }
                            }
                        }
                    }

                    function showNoResults(searchTerm) {
                        const tabContent = document.getElementById('variantTabContent');
                        if (!tabContent) return;

                        // ✅ Clear the tab content and show the no results message
                        tabContent.innerHTML = `
                            <div class="text-center py-10 w-100 no-results-message">
                                <i class="ki-duotone ki-search-list fs-3x text-gray-400 mb-3 d-block"></i>
                                <span class="text-gray-500 fw-semibold fs-5">
                                    No products found for "${searchTerm || ''}"
                                </span>
                                <div class="mt-3">
                                    <button class="btn btn-light-primary" onclick="restoreOriginalView()">
                                        <i class="ki-duotone ki-arrow-left fs-4 me-1"></i>
                                        {{ __('auth._back') }}
                                    </button>
                                </div>
                            </div>
                        `;

                        const productList = document.getElementById('productList');
                        if (productList) {
                            const existingMsg = productList.querySelector('.search-result-msg');
                            if (existingMsg) existingMsg.remove();

                            const msg = document.createElement('li');
                            msg.className = 'search-result-msg nav-item me-3';
                            msg.innerHTML = `<span class="fw-bold text-danger fs-6">No results found</span>`;
                            productList.prepend(msg);

                            // ✅ Hide all product pills
                            document.querySelectorAll('.product-item').forEach(item => {
                                item.style.display = 'none';
                            });
                        }
                    }

                    // Search input handlers
                    document.addEventListener('DOMContentLoaded', function() {
                        const searchInput = document.getElementById('variantSearchInput');
                        if (searchInput) {
                            searchInput.addEventListener('keypress', function(e) {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    const searchTerm = this.value.trim();
                                    if (searchTerm) {
                                        performSearch(searchTerm);
                                    } else {
                                        restoreOriginalView();
                                    }
                                }
                            });
                            
                            searchInput.addEventListener('input', function(e) {
                                if (!this.value.trim()) {
                                    restoreOriginalView();
                                }
                            });
                        }
                    });

                    // ============================================================
                    // CREATE VARIANT CARD - FULLY ENABLED (NO DISABLING)
                    // ============================================================
                    function createVariantCard(variant, product, isSingleShop) {
                        const card = document.createElement('div');
                        card.className = 'card card-flush flex-row-fluid p-6 pb-5 mw-100 variant-item position-relative';
                        
                        let imageUrl = variant.image_url || product.image_url || '';
                        
                        if (imageUrl) {
                            imageUrl = imageUrl.replace(/^\/+/, '');
                            imageUrl = '{{ asset("storage") }}/' + imageUrl;
                        } else {
                            imageUrl = '{{ asset("assets/media/stock/ecommerce/2.png") }}';
                        }
                        
                        card.setAttribute('data-name', (variant.name || product.name).toLowerCase());
                        card.setAttribute('data-product', product.id);
                        card.setAttribute('data-variant-id', variant.id);
                        card.setAttribute('data-price', variant.selling_price);
                        card.setAttribute('data-image', imageUrl);
                        card.setAttribute('data-taxes', JSON.stringify(variant.applicable_taxes || []));
                        card.setAttribute('data-promotions', JSON.stringify(variant.applicable_promotions || []));
                        
                        if (!isSingleShop && variant.inventory_by_dept) {
                            card.setAttribute('data-inventory', JSON.stringify(variant.inventory_by_dept || {}));
                        }
                        
                        // ✅ ALWAYS ENABLED - No disabling logic
                        card.onclick = function() { handleVariantClick(this); };
                        card.style.cursor = 'pointer';

                        const hasTaxes = variant.applicable_taxes && variant.applicable_taxes.length > 0;
                        const hasPromos = variant.applicable_promotions && variant.applicable_promotions.length > 0;

                        card.innerHTML = `
                            ${hasTaxes ? `
                                <span class="badge bg-danger text-white position-absolute top-0 start-0 m-2 px-3 py-2 shadow-sm">
                                    <i class="ki-duotone ki-dollar fs-6 me-1"></i>
                                    {{ __('passwords._tax') }}
                                </span>
                            ` : ''}
                            
                            ${hasPromos ? `
                                <span class="badge bg-success text-white position-absolute top-0 end-0 m-2 px-3 py-2 shadow-sm">
                                    <i class="ki-duotone ki-tag fs-6 me-1"></i>
                                    {{ __('passwords._promo') }}
                                </span>
                            ` : ''}

                            <div class="card-body text-center">
                                <img src="${imageUrl}" 
                                    class="rounded-3 mb-4 w-150px h-150px w-xxl-200px h-xxl-200px" 
                                    alt="${variant.name || product.name}"
                                    onerror="this.src='{{ asset('assets/media/stock/ecommerce/2.png') }}'" />

                                <div class="mb-2">
                                    <div class="text-center">
                                        <span class="fw-bold text-gray-800 cursor-pointer text-hover-primary fs-3 fs-xl-1"
                                            data-bs-toggle="tooltip" 
                                            title="${variant.name || product.name}">
                                            ${(variant.name || product.name).length > 20 ? (variant.name || product.name).substring(0, 20) + '...' : (variant.name || product.name)}
                                        </span>
                                        <span class="text-gray-500 fw-semibold d-block fs-6 mt-n1 variant-qty">
                                            ${variant.quantity_available || 0} {{__('pagination._available')}}
                                        </span>
                                    </div>
                                </div>

                                <span class="text-success text-end fw-bold fs-1">
                                    ${variant.selling_price} {{ currency_symbol() }}
                                </span>
                            </div>
                        `;

                        return card;
                    }

                    // ============================================================
                    // CUSTOMER SELECTION
                    // ============================================================
                    document.addEventListener('click', function (e) {
                        const btnExisting = document.getElementById('btn-pick-existing');
                        const btnNew      = document.getElementById('btn-pick-new');

                        const clickedExisting = btnExisting && (e.target === btnExisting || btnExisting.contains(e.target));
                        const clickedNew      = btnNew      && (e.target === btnNew      || btnNew.contains(e.target));

                        if (!clickedExisting && !clickedNew) return;

                        const mode = clickedExisting ? 'existing' : 'new';

                        const radioExisting  = document.getElementById('cust-mode-existing');
                        const radioNew       = document.getElementById('cust-mode-new');
                        const panelExisting  = document.getElementById('panel-existing-cust');
                        const panelNew       = document.getElementById('panel-new-cust');
                        const selectExisting = document.getElementById('cust-existing-select');
                        const inputNew       = document.getElementById('cust-new-input');

                        // Reset BOTH buttons to unselected state
                        [btnExisting, btnNew].forEach(btn => {
                            btn.classList.remove('btn-light-primary', 'border-primary');
                            btn.classList.add('btn-outline-default');
                            btn.querySelector('i').classList.remove('text-primary');
                            btn.querySelector('i').classList.add('text-muted');
                            btn.querySelector('span').classList.remove('text-primary');
                            btn.querySelector('span').classList.add('text-gray-800');
                        });

                        // Hide both panels
                        panelExisting.classList.add('d-none');
                        panelNew.classList.add('d-none');

                        // Activate selected button
                        const activeBtn = mode === 'existing' ? btnExisting : btnNew;
                        activeBtn.classList.remove('btn-outline-default');
                        activeBtn.classList.add('btn-light-primary', 'border-primary');
                        activeBtn.querySelector('i').classList.remove('text-muted');
                        activeBtn.querySelector('i').classList.add('text-primary');
                        activeBtn.querySelector('span').classList.remove('text-gray-800');
                        activeBtn.querySelector('span').classList.add('text-primary');

                        if (mode === 'existing') {
                            radioExisting.checked = true;
                            panelExisting.classList.remove('d-none');
                            setTimeout(() => selectExisting.focus(), 100);
                        } else {
                            radioNew.checked = true;
                            panelNew.classList.remove('d-none');
                            setTimeout(() => { inputNew.focus(); inputNew.select(); }, 100);
                        }
                    });

                    document.addEventListener('DOMContentLoaded', function () {
                        $('#cust-existing-select').select2({
                            placeholder: '— {{ __("pagination.choose_customer") }} —',
                            allowClear: true,
                            width: '100%',
                        });
                    });
                </script>
                
                <div class="m-0">
                    @can('create order')
                    <div class="mt-8">
                        <button 
                            id="processBill" 
                            type="button" 
                            class="btn btn-primary w-100 py-4 d-flex align-items-center justify-content-center gap-3"
                            style="font-size: 1.15rem; font-weight: 600;"
                            onclick="processPayment()">
                            <i class="ki-duotone ki-printer fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                            <span class="indicator-label">{{__('pagination.print_bill')}}</span>
                            <span class="indicator-progress">{{__('pagination.processing_payments')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                    <div class="mt-3">
                        <button 
                            id="generateInvoiceBtn" 
                            type="button" 
                            class="btn btn-light-primary w-100 py-4 d-flex align-items-center justify-content-center gap-3"
                            style="font-size: 1.15rem; font-weight: 600;"
                            onclick="generateInvoice()">
                            <i class="ki-duotone ki-bill fs-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
                            </i>
                            <span class="indicator-label">{{__('pagination.generate_invoice')}}</span>
                            <span class="indicator-progress">{{__('pagination.generating_invoice')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                    @endcan
                </div>

            </div>
        </div>
    </div>
    <!--end::Sidebar-->
</div>

<style>
    /* ✅ Ensure variants are always clickable */
    .variant-item {
        cursor: pointer !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }

    /* ✅ Only show disabled state for truly out of stock items */
    .variant-item.out-of-stock {
        cursor: not-allowed !important;
        opacity: 0.5 !important;
    }
</style>


@include('orders.pos.pause-buy')
@include('orders.pos.payment-mode')
@endcan

