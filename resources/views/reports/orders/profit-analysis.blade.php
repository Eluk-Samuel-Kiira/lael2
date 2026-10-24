@extends('layouts.app')

@section('title', __('auth.profit_analysis_report'))

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
                                {{ __('auth.profit_analysis_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.profit_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="paExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export_to_excel') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="paPrintBtn">
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
                        <form id="paFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="paStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="paEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="paLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="paDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.order_type') }}</label>
                                <select class="form-select" id="paType">
                                    <option value="">{{ __('auth.all_types') }}</option>
                                    <option value="sale"   @selected($orderType === 'sale')>{{ __('auth.sale') }}</option>
                                    <option value="return" @selected($orderType === 'return')>{{ __('auth.return') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.min_profit') }}</label>
                                <input type="number" step="0.01" class="form-control" id="paMinProfit" value="{{ $minProfit }}" placeholder="Min">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.max_profit') }}</label>
                                <input type="number" step="0.01" class="form-control" id="paMaxProfit" value="{{ $maxProfit }}" placeholder="Max">
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="paApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="paClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="paSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="pa-sk pa-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="pa-sk pa-sk-text mb-2" style="width:60%;"></div>
                                    <div class="pa-sk pa-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="pa-sk pa-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="pa-sk pa-sk-text mb-2" style="width:35%;"></div>
                                <div class="pa-sk pa-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="pa-sk pa-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="paContent" class="d-none">

                    <div id="paEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_orders_found_for_period') }}</p>
                    </div></div>

                    {{-- KPI cards --}}
                    <div id="paKpiCards" class="row g-6 mb-6"></div>

                    {{-- Summary stats row --}}
                    <div id="paSummaryStats" class="row g-6 mb-6"></div>

                    {{-- By location --}}
                    <div id="paLocationCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-geo-alt fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.profit_by_location') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.location') }}</th>
                                    <th class="text-center">{{ __('auth.orders') }}</th>
                                    <th class="text-end">{{ __('auth.revenue') }}</th>
                                    <th class="text-end">{{ __('auth.cost') }}</th>
                                    <th class="text-end">{{ __('auth.net_profit') }}</th>
                                    <th class="text-end pe-4">{{ __('auth.avg_margin') }}</th>
                                </tr></thead>
                                <tbody id="paLocationBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- Top / worst products --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6"><div id="paTopCard" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-arrow-up-circle fs-2 me-2 text-success"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_profitable_products') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="ps-4">{{ __('auth.product') }}</th>
                                        <th class="text-center">{{ __('auth.qty_sold') }}</th>
                                        <th class="text-end">{{ __('auth.profit') }}</th>
                                        <th class="text-end pe-4">{{ __('auth.margin') }}</th>
                                    </tr></thead>
                                    <tbody id="paTopBody"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                        <div class="col-md-6"><div id="paWorstCard" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-arrow-down-circle fs-2 me-2 text-danger"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.least_profitable_products') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="ps-4">{{ __('auth.product') }}</th>
                                        <th class="text-center">{{ __('auth.qty_sold') }}</th>
                                        <th class="text-end">{{ __('auth.profit') }}</th>
                                        <th class="text-end pe-4">{{ __('auth.margin') }}</th>
                                    </tr></thead>
                                    <tbody id="paWorstBody"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Daily profit trend (paginated) --}}
                    <div id="paTrendCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.daily_profit_trend') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="paTrendBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.date') }}</th>
                                    <th class="text-center">{{ __('auth.orders') }}</th>
                                    <th class="text-end">{{ __('auth.revenue') }}</th>
                                    <th class="text-end">{{ __('auth.cost') }}</th>
                                    <th class="text-end">{{ __('auth.net_profit') }}</th>
                                    <th class="text-end pe-4">{{ __('auth.avg_margin') }}</th>
                                </tr></thead>
                                <tbody id="paTrendBody"></tbody>
                                <tfoot class="bg-light" id="paTrendFoot"></tfoot>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="paTrendPaginationWrap">
                                <span class="text-muted fs-7" id="paTrendPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="paTrendPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Orders profit table (paginated + drawer) --}}
                    <div id="paOrdersCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.order_profit_breakdown') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="paOrdersBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="paOrdersTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.order') }}</th>
                                    <th>{{ __('auth.date') }}</th>
                                    <th>{{ __('auth.location') }}</th>
                                    <th>{{ __('auth.department') }}</th>
                                    <th class="text-end">{{ __('auth.revenue') }}</th>
                                    <th class="text-end">{{ __('auth.cost') }}</th>
                                    <th class="text-end">{{ __('auth.discount') }}</th>
                                    <th class="text-end">{{ __('auth.gross_profit') }}</th>
                                    <th class="text-end">{{ __('auth.net_profit') }}</th>
                                    <th class="text-end">{{ __('auth.margin') }}</th>
                                    <th class="text-end pe-4">{{ __('auth.items') }}</th>
                                </tr></thead>
                                <tbody id="paOrdersBody"></tbody>
                                <tfoot class="bg-light" id="paOrdersFoot"></tfoot>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="paOrdersPaginationWrap">
                                <span class="text-muted fs-7" id="paOrdersPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="paOrdersPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- DRAWER — per-order item profit breakdown                       --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="paDrawer" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-receipt fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="paDrawerLabel">{{ __('auth.order_profit_breakdown') }}</h4>
                <span class="text-white opacity-75 fs-7" id="paDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6" id="paDrawerBody"></div>
</div>
@endsection

@push('styles')
<style>
    .pa-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: pa-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .pa-sk-text   { height: 12px; border-radius: 4px; }
    .pa-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .pa-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes pa-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #paSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .pa-row { cursor: pointer; }
    .pa-row:hover { background-color: #f9f9f9; }
</style>
@endpush

@push('scripts')
<script>
    window.PA_ROUTES = { data: '{{ route("reports.orders.profit-analysis-data") }}' };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.PA_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        orderType:    @json($orderType),
        minProfit:    @json($minProfit),
        maxProfit:    @json($maxProfit),
        perPage:      @json($perPage),
    };
    window.PA_I18N = {
        totalOrders:      @json(__('auth.total_orders')),
        totalRevenue:     @json(__('auth.total_revenue')),
        totalCost:        @json(__('auth.total_cost')),
        totalDiscount:    @json(__('auth.total_discount')),
        grossProfit:      @json(__('auth.gross_profit')),
        netProfit:        @json(__('auth.net_profit')),
        avgProfitMargin:  @json(__('auth.avg_profit_margin')),
        avgNetProfit:     @json(__('auth.avg_net_profit')),
        highestProfitOrder:@json(__('auth.highest_profit_order')),
        discountReducedProfit:@json(__('auth.discount_reduced_profit')),
        lossMakingOrders: @json(__('auth.loss_making_orders')),
        ordersWithNegativeProfit:@json(__('auth.orders_with_negative_profit')),
        profitabilityRate:@json(__('auth.profitability_rate')),
        orders:           @json(__('auth.orders')),
        profitable:       @json(__('auth.profitable_orders')),
        loss:             @json(__('auth.loss_making_orders')),
        breakEven:        @json(__('auth.break_even_orders')),

        location:         @json(__('auth.location')),
        revenue:          @json(__('auth.revenue')),
        cost:             @json(__('auth.cost')),
        avgMargin:        @json(__('auth.avg_margin')),
        product:          @json(__('auth.product')),
        qtySold:          @json(__('auth.qty_sold')),
        profit:           @json(__('auth.profit')),
        margin:           @json(__('auth.margin')),
        qty:              @json(__('auth.qty')),
        unitCost:         @json(__('auth.unit_cost')),
        unitPrice:        @json(__('auth.unit_price')),
        date:             @json(__('auth.date')),
        department:       @json(__('auth.department')),
        discount:         @json(__('auth.discount')),
        items:            @json(__('auth.items')),
        order:            @json(__('auth.order')),
        totalAverage:     @json(__('accounting.total_average')),
        itemBreakdown:    @json(__('auth.order_profit_breakdown')),
        customer:         @json(__('auth.customer')),
        pricingSource:    @json(__('auth.pricing_source')),
    };
</script>
@include('reports.orders.profit-analysis-scripts')
@endpush