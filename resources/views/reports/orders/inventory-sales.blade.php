@extends('layouts.app')

@section('title', __('auth.inventory_sales_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- Toolbar --}}
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0 w-100">
                        <div class="page-title d-flex flex-column align-items-start text-start me-auto">
                            <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                                {{ __('auth.inventory_sales_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.inventory_sales_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="isExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="isPrintBtn">
                                <i class="bi bi-printer fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.print') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="row mb-6"><div class="col-12"><div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="bi bi-funnel fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form id="isFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="isStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="isEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="isLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="isDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center gap-2">
                                <span class="badge badge-light-{{ $isSingleShop ? 'info' : 'primary' }}" id="isShopModeBadge">
                                    {{ $isSingleShop ? '🏪 Single Shop' : '🏢 Multi-Shop' }}
                                </span>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-primary" id="isApplyBtn">
                                        <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                    </button>
                                    <button type="button" class="btn btn-light" id="isClearBtn">
                                        <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="isSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 6; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card h-100"><div class="card-body text-center">
                            <div class="is-sk is-sk-text mx-auto" style="width:60%;height:24px;"></div>
                            <div class="is-sk is-sk-text mx-auto mt-2" style="width:80%;"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="is-sk is-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="is-sk is-sk-text mb-2" style="width:35%;"></div>
                                <div class="is-sk is-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="is-sk is-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="isContent" class="d-none">

                    <div id="isEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_inventory_data_found_for_period') }}</p>
                    </div></div>

                    {{-- Summary cards --}}
                    <div id="isSummaryCards" class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-4 g-lg-5 mb-6"></div>

                    {{-- Movement cards --}}
                    <div id="isMovementCards" class="row g-6 mb-6"></div>

                    {{-- Dead stock --}}
                    <div id="isDeadCard" class="card mb-6 border border-danger d-none">
                        <div class="card-header bg-light-danger border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle fs-2 me-2 text-danger"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.dead_stock_alert') }}</h3>
                                </div>
                                <span class="badge badge-light-danger fs-7" id="isDeadBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light-danger">
                                    <th class="ps-4">{{ __('auth.product') }}</th>
                                    <th>{{ __('auth.sku') }}</th>
                                    <th class="text-center">{{ __('auth.current_stock') }}</th>
                                    <th class="text-end">{{ __('auth.price') }}</th>
                                    <th class="text-end pe-4">{{ __('auth.stock_value') }}</th>
                                </tr></thead>
                                <tbody id="isDeadBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="isDeadPaginationWrap">
                                <span class="text-muted fs-7" id="isDeadPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="isDeadPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Sold products --}}
                    <div id="isSoldCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-cart-check fs-2 me-2 text-success"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.sold_products') }}</h3>
                                </div>
                                <span class="badge badge-light-success fs-7" id="isSoldBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="isSoldTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('accounting.rank') }}</th>
                                    <th>{{ __('auth.product') }}</th>
                                    <th>{{ __('auth.sku') }}</th>
                                    <th class="text-center">{{ __('auth.quantity_sold') }}</th>
                                    <th class="text-end">{{ __('auth.revenue_generated') }}</th>
                                    <th class="text-end">{{ __('auth.cost') }}</th>
                                    <th class="text-end">{{ __('auth.profit') }}</th>
                                    <th class="text-center">{{ __('auth.current_stock') }}</th>
                                    <th class="text-end">{{ __('auth.stock_value') }}</th>
                                    <th class="text-center">{{ __('auth.times_ordered') }}</th>
                                    <th class="text-center">{{ __('auth.daily_sales_rate') }}</th>
                                    <th class="text-center">{{ __('auth.movement_category') }}</th>
                                    <th class="pe-4">{{ __('auth.last_sold_date') }}</th>
                                </tr></thead>
                                <tbody id="isSoldBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="isSoldPaginationWrap">
                                <span class="text-muted fs-7" id="isSoldPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="isSoldPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Unsold products --}}
                    <div id="isUnsoldCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-cart-x fs-2 me-2 text-danger"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.unsold_products') }}</h3>
                                </div>
                                <span class="badge badge-light-danger fs-7" id="isUnsoldBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('auth.product') }}</th>
                                    <th>{{ __('auth.sku') }}</th>
                                    <th class="text-center">{{ __('auth.current_stock') }}</th>
                                    <th class="text-end">{{ __('auth.price') }}</th>
                                    <th class="text-end pe-4">{{ __('auth.stock_value') }}</th>
                                </tr></thead>
                                <tbody id="isUnsoldBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="isUnsoldPaginationWrap">
                                <span class="text-muted fs-7" id="isUnsoldPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="isUnsoldPagination"></ul></nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DRAWER — sold product detail --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="isDrawer" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-box-seam fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="isDrawerLabel">{{ __('auth.sold_products') }}</h4>
                <span class="text-white opacity-75 fs-7" id="isDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="isDrawerSkeleton">
            <div class="is-sk is-sk-text mb-3" style="width:60%;"></div>
            <div class="is-sk is-sk-text mb-3" style="width:40%;"></div>
            <div class="is-sk is-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="isDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .is-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: is-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .is-sk-text   { height: 12px; border-radius: 4px; }
    .is-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes is-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #isSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .is-row { cursor: pointer; }
    .is-row:hover { background-color: #f9f9f9; }
    @media print {
        .app-toolbar, #isFilterForm, .dropdown, .no-print, .card-header .btn { display: none !important; }
        .badge { border: 1px solid #E5E7EB !important; }
        .symbol { display: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
    window.IS_ROUTES = {
        data:    '{{ route("reports.orders.inventory-sales-data") }}',
        product: '{{ route("reports.orders.inventory-sales-product", ["variantId" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.IS_BOOTSTRAP = {
        startDate:         @json($startDate),
        endDate:           @json($endDate),
        locationId:        @json($locationId),
        departmentId:      @json($departmentId),
        soldPerPage:       @json($soldPerPage),
        unsoldPerPage:     @json($unsoldPerPage),
        deadStockPerPage:  @json($deadStockPerPage),
        isSingleShop:      @json($isSingleShop),
    };
    window.IS_I18N = {
        // summary
        totalProducts:       @json(__('auth.total_products')),
        sellThroughRate:     @json(__('auth.sell_through_rate')),
        totalUnits:          @json(__('auth.total_units')),
        soldInventoryValue:  @json(__('auth.sold_inventory_value')),
        cost:                @json(__('auth.cost')),
        costOfGoodsSold:     @json(__('auth.cost_of_goods_sold')),
        soldInventoryProfit: @json(__('auth.sold_inventory_profit')),
        turnover:            @json(__('auth.turnover')),
        margin:              @json(__('auth.margin')),
        day:                 @json(__('auth.day')),

        // movement
        fastMovers:          @json(__('auth.fast_movers')),
        mediumMovers:        @json(__('auth.medium_movers')),
        slowMovers:          @json(__('auth.slow_movers')),

        // dead
        deadStockAlert:      @json(__('auth.dead_stock_alert')),
        items:               @json(__('auth.items')),
        product:             @json(__('auth.product')),
        sku:                 @json(__('auth.sku')),
        currentStock:        @json(__('auth.current_stock')),
        price:               @json(__('auth.price')),
        stockValue:          @json(__('auth.stock_value')),

        // sold
        soldProducts:        @json(__('auth.sold_products')),
        rank:                @json(__('accounting.rank')),
        quantitySold:        @json(__('auth.quantity_sold')),
        revenueGenerated:    @json(__('auth.revenue_generated')),
        profit:              @json(__('auth.profit')),
        timesOrdered:        @json(__('auth.times_ordered')),
        dailySalesRate:      @json(__('auth.daily_sales_rate')),
        movementCategory:    @json(__('auth.movement_category')),
        lastSoldDate:        @json(__('auth.last_sold_date')),
        products:            @json(__('auth.products')),

        // unsold
        unsoldProducts:      @json(__('auth.unsold_products')),
        deadStock:           @json(__('auth.dead_stock')),

        // drawer
        soldHistory:         @json(__('auth.sold_history')),
        orderNumber:         @json(__('auth.order_number')),
        orderDate:           @json(__('auth.order_date')),
        customer:            @json(__('auth.customer')),
        qty:                 @json(__('auth.qty')),
        unitPrice:           @json(__('auth.unit_price')),
        totalPrice:          @json(__('auth.total_price')),
        totalCost:           @json(__('auth.total_cost')),
        totalRevenue:        @json(__('auth.total_revenue')),
        totalUnitsSold:      @json(__('auth.total_units')),
        marginLbl:           @json(__('auth.margin')),
        recentSales:         @json(__('auth.recent_sales')),
        noData:              @json(__('accounting.no_data_available')),
    };
</script>
@include('reports.orders.inventory-sales-scripts')
@endpush