@extends('layouts.app')

@section('title', __('auth.sales_by_product'))

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
                                {{ __('auth.sales_by_product') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.sales_by_product') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="bpExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
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
                        <form id="bpFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="bpStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="bpEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="bpLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="bpDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.min_quantity') }}</label>
                                <input type="number" min="0" step="1" class="form-control" id="bpMinQty" value="{{ $minQuantity }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.max_quantity') }}</label>
                                <input type="number" min="0" step="1" class="form-control" id="bpMaxQty" value="{{ $maxQuantity }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.min_revenue') }}</label>
                                <input type="number" min="0" step="0.01" class="form-control" id="bpMinRevenue" value="{{ $minRevenue }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.max_revenue') }}</label>
                                <input type="number" min="0" step="0.01" class="form-control" id="bpMaxRevenue" value="{{ $maxRevenue }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.min_profit') }}</label>
                                <input type="number" step="0.01" class="form-control" id="bpMinProfit" value="{{ $minProfit }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.sales_velocity') }}</label>
                                <select class="form-select" id="bpVelocity">
                                    <option value="">—</option>
                                    <option value="Fast Mover"   @selected($velocityFilter === 'Fast Mover')>{{ __('auth.fast_mover') }}</option>
                                    <option value="Medium Mover" @selected($velocityFilter === 'Medium Mover')>{{ __('auth.medium_mover') }}</option>
                                    <option value="Slow Mover"   @selected($velocityFilter === 'Slow Mover')>{{ __('auth.slow_mover') }}</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="bpApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="bpClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="bpSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 6; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card h-100"><div class="card-body text-center">
                            <div class="bp-sk bp-sk-circle mx-auto mb-3"></div>
                            <div class="bp-sk bp-sk-text mx-auto" style="width:60%;height:24px;"></div>
                            <div class="bp-sk bp-sk-text mx-auto mt-2" style="width:80%;"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6"><div class="col-12"><div class="card"><div class="card-body">
                        <div class="bp-sk" style="height:400px;border-radius:8px;"></div>
                    </div></div></div></div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="bp-sk bp-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="bp-sk bp-sk-text mb-2" style="width:35%;"></div>
                                <div class="bp-sk bp-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="bp-sk bp-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="bpContent" class="d-none">

                    <div id="bpEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_products_found_for_period') }}</p>
                    </div></div>

                    {{-- Summary stats --}}
                    <div id="bpSummaryStats" class="row g-6 mb-6"></div>

                    {{-- Top products chart --}}
                    <div id="bpChartCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-bar-chart fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.top_products_by_revenue') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div id="bpChart" style="height:400px;"></div>
                        </div>
                    </div>

                    {{-- Products table --}}
                    <div id="bpTableCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.product_sales_report') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="bpTableBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="bpProductsTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.rank') }}</th>
                                    <th>{{ __('auth.product') }}</th>
                                    <th>{{ __('auth.sku') }}</th>
                                    <th class="text-end">{{ __('auth.quantity_sold') }}</th>
                                    <th class="text-end">{{ __('auth.revenue_generated') }}</th>
                                    <th class="text-end">{{ __('accounting.total_tax') }}</th>
                                    <th class="text-end">{{ __('auth.total_discount') }}</th>
                                    <th class="text-end">{{ __('accounting.average_amount') }}</th>
                                    <th class="text-center">{{ __('auth.times_ordered') }}</th>
                                    <th>{{ __('auth.sales_velocity') }}</th>
                                    <th>{{ __('auth.last_sold_date') }}</th>
                                    <th class="text-center">{{ __('auth.serial_numbers') }}</th>
                                    <th class="pe-4" style="width:15%;">{{ __('accounting.percentage') }}</th>
                                </tr></thead>
                                <tbody id="bpProductsBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="bpPaginationWrap">
                                <span class="text-muted fs-7" id="bpPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="bpPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Top 5 / Bottom 5 --}}
                    <div class="row g-6 mt-6">
                        <div class="col-lg-6"><div id="bpTop5Card" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-trophy fs-2 me-2 text-success"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_performers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-success">
                                        <th class="ps-4">{{ __('accounting.rank') }}</th>
                                        <th>{{ __('auth.product') }}</th>
                                        <th class="text-end">{{ __('auth.revenue_generated') }}</th>
                                        <th class="text-end pe-4">{{ __('auth.quantity_sold') }}</th>
                                    </tr></thead>
                                    <tbody id="bpTop5Body"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div id="bpBottom5Card" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-arrow-down-circle fs-2 me-2 text-danger"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.bottom_performers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                        <th class="ps-4">{{ __('accounting.rank') }}</th>
                                        <th>{{ __('auth.product') }}</th>
                                        <th class="text-end">{{ __('auth.revenue_generated') }}</th>
                                        <th class="text-end pe-4">{{ __('auth.quantity_sold') }}</th>
                                    </tr></thead>
                                    <tbody id="bpBottom5Body"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- DRAWER 1 — Recipe Ingredients                                  --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="bpRecipeDrawer" style="width: 640px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-book fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="bpRecipeLabel">{{ __('passwords.recipe_ingredients') }}</h4>
                <span class="text-white opacity-75 fs-7" id="bpRecipeSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="bpRecipeSkeleton">
            <div class="bp-sk bp-sk-text mb-3" style="width:60%;"></div>
            <div class="bp-sk bp-sk-text mb-3" style="width:40%;"></div>
            <div class="bp-sk bp-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="bpRecipeContent" class="d-none"></div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- DRAWER 2 — Serial Numbers                                      --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="bpSerialDrawer" style="width: 900px; max-width: 100vw;">
    <div class="offcanvas-header bg-warning py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-upc-scan fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="bpSerialLabel">{{ __('passwords.serial_numbers') }}</h4>
                <span class="text-white opacity-75 fs-7" id="bpSerialSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        {{-- Summary --}}
        <div class="row g-3 mb-4" id="bpSerialSummary">
            <div class="col-md-2"><div class="card bg-light-primary"><div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-primary" id="bpTotalSerials">0</div>
                <div class="text-muted fs-8">{{ __('passwords.total') }}</div>
            </div></div></div>
            <div class="col-md-2"><div class="card bg-light-success"><div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-success" id="bpAvailableSerials">0</div>
                <div class="text-muted fs-8">{{ __('passwords.available') }}</div>
            </div></div></div>
            <div class="col-md-2"><div class="card bg-light-danger"><div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-danger" id="bpSoldSerials">0</div>
                <div class="text-muted fs-8">{{ __('passwords.sold') }}</div>
            </div></div></div>
            <div class="col-md-2"><div class="card bg-light-warning"><div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-warning" id="bpReservedSerials">0</div>
                <div class="text-muted fs-8">{{ __('passwords.reserved') }}</div>
            </div></div></div>
            <div class="col-md-2"><div class="card bg-light-info"><div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-info" id="bpReturnedSerials">0</div>
                <div class="text-muted fs-8">{{ __('passwords.returned') }}</div>
            </div></div></div>
            <div class="col-md-2"><div class="card bg-light-secondary"><div class="card-body text-center py-2">
                <div class="fs-4 fw-bold text-secondary" id="bpDamagedSerials">0</div>
                <div class="text-muted fs-8">{{ __('passwords.damaged') }}</div>
            </div></div></div>
        </div>

        {{-- Filters --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label fw-semibold">{{ __('passwords.filter_by_status') }}</label>
                <select class="form-select" id="bpSerialStatusFilter">
                    <option value="all">{{ __('passwords.all_statuses') }}</option>
                    <option value="available">{{ __('passwords.available') }}</option>
                    <option value="sold">{{ __('passwords.sold') }}</option>
                    <option value="reserved">{{ __('passwords.reserved') }}</option>
                    <option value="returned">{{ __('passwords.returned') }}</option>
                    <option value="lost">{{ __('passwords.lost') }}</option>
                    <option value="damaged">{{ __('passwords.damaged') }}</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">{{ __('passwords.search') }}</label>
                <input type="text" class="form-control" id="bpSerialSearchInput" placeholder="{{ __('passwords.search_serial_number') }}">
            </div>
            <div class="col-md-4 d-flex align-items-end justify-content-end">
                <button type="button" class="btn btn-sm btn-primary" id="bpSerialRefreshBtn">
                    <i class="bi bi-arrow-clockwise me-1"></i> {{ __('passwords.refresh') }}
                </button>
            </div>
        </div>

        <div id="bpSerialSkeleton">
            <div class="bp-sk bp-sk-text mb-3" style="width:60%;"></div>
            <div class="bp-sk bp-sk-text mb-3" style="width:40%;"></div>
        </div>

        <div id="bpSerialContent" class="d-none">
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                    <thead><tr class="fw-bold fs-7 text-gray-500 border-bottom-0">
                        <th class="min-w-50px">#</th>
                        <th class="min-w-150px">{{ __('passwords.serial_number') }}</th>
                        <th class="min-w-100px">{{ __('passwords.status') }}</th>
                        <th class="min-w-150px">{{ __('passwords.location') }}</th>
                        <th class="min-w-150px">{{ __('passwords.department') }}</th>
                        <th class="min-w-150px">{{ __('passwords.order') }}</th>
                        <th class="min-w-150px">{{ __('passwords.sold_date') }}</th>
                        <th class="min-w-100px text-end">{{ __('auth._actions') }}</th>
                    </tr></thead>
                    <tbody id="bpSerialBody"></tbody>
                </table>
            </div>
            <div id="bpSerialEmpty" class="text-center py-10 d-none">
                <i class="bi bi-inbox fs-4tx text-gray-400 mb-4"></i>
                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_serials_found') }}</h4>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .bp-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: bp-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .bp-sk-text   { height: 12px; border-radius: 4px; }
    .bp-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .bp-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes bp-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #bpSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.BP_ROUTES = {
        data:     '{{ route("reports.orders.by-product-data") }}',
        recipe:   '/recipes/__ID__/ingredients',
        serials:  '/variants/__ID__/serials',
        serialStatus: '/serials/__ID__/status',
        serialDelete: '/serials/__ID__',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.CSRF_TOKEN = '{{ csrf_token() }}';
    window.BP_BOOTSTRAP = {
        startDate:      @json($startDate),
        endDate:        @json($endDate),
        locationId:     @json($locationId),
        departmentId:   @json($departmentId),
        minQuantity:    @json($minQuantity),
        maxQuantity:    @json($maxQuantity),
        minRevenue:     @json($minRevenue),
        maxRevenue:     @json($maxRevenue),
        minProfit:      @json($minProfit),
        velocityFilter: @json($velocityFilter),
        perPage:        @json($perPage),
        isSingleShop:   @json($isSingleShop),
    };
    window.BP_I18N = {
        totalProducts:    @json(__('auth.total_products')),
        totalRevenue:     @json(__('auth.total_revenue')),
        totalQuantitySold:@json(__('auth.total_quantity_sold')),
        averageDailySales:@json(__('auth.average_daily_sales')),
        topProduct:       @json(__('auth.top_product')),
        topProductRevenue:@json(__('auth.top_product_revenue')),
        rank:             @json(__('accounting.rank')),
        product:          @json(__('auth.product')),
        sku:              @json(__('auth.sku')),
        quantitySold:     @json(__('auth.quantity_sold')),
        revenueGenerated: @json(__('auth.revenue_generated')),
        totalTax:         @json(__('accounting.total_tax')),
        totalDiscount:    @json(__('auth.total_discount')),
        averageAmount:    @json(__('accounting.average_amount')),
        timesOrdered:     @json(__('auth.times_ordered')),
        salesVelocity:    @json(__('auth.sales_velocity')),
        lastSoldDate:     @json(__('auth.last_sold_date')),
        serialNumbers:    @json(__('auth.serial_numbers')),
        percentage:       @json(__('accounting.percentage')),
        top:              @json(__('accounting.top')),
        products:         @json(__('auth.products')),
        // drawers
        recipeIngredients: @json(__('passwords.recipe_ingredients')),
        loading:           @json(__('passwords.loading')),
        ingredient:        @json(__('passwords.ingredient')),
        quantityRequired:  @json(__('passwords.quantity_required')),
        unit:              @json(__('passwords.unit')),
        noIngredients:     @json(__('passwords.no_ingredients_found')),
        loadingSerials:    @json(__('passwords.loading_serials')),
        noSerials:         @json(__('passwords.no_serials_found')),
        serialNumber:      @json(__('passwords.serial_number')),
        status:            @json(__('passwords.status')),
        location:          @json(__('passwords.location')),
        department:        @json(__('passwords.department')),
        order:             @json(__('passwords.order')),
        soldDate:          @json(__('passwords.sold_date')),
        actions:           @json(__('auth._actions')),
        available:         @json(__('passwords.available')),
        sold:              @json(__('passwords.sold')),
        reserved:          @json(__('passwords.reserved')),
        returned:          @json(__('passwords.returned')),
        lost:              @json(__('passwords.lost')),
        damaged:           @json(__('passwords.damaged')),
    };
</script>
@include('reports.orders.by-product-scripts')
@endpush