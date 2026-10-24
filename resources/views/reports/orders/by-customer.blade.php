@extends('layouts.app')

@section('title', __('auth.sales_by_customer'))

@section('content')
@if (tenant_can('advanced_reports'))
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- Toolbar --}}
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0 w-100">
                        <div class="page-title d-flex flex-column align-items-start text-start me-auto">
                            <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                                {{ __('auth.sales_by_customer') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.sales_by_customer') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="bcExportBtn">
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
                        <form id="bcFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="bcStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="bcEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="bcLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="bcDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.customer_type') }}</label>
                                <select class="form-select" id="bcCustomerType">
                                    <option value="all"        @selected($customerType === 'all')>{{ __('auth.all_customers') }}</option>
                                    <option value="registered" @selected($customerType === 'registered')>{{ __('auth.registered') }}</option>
                                    <option value="guest"      @selected($customerType === 'guest')>{{ __('auth.guest') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.customer_segment') }}</label>
                                <select class="form-select" id="bcSegment">
                                    <option value="">{{ __('auth.all_segments') }}</option>
                                    <option value="New"       @selected($segmentFilter === 'New')>{{ __('auth.new') }}</option>
                                    <option value="Returning" @selected($segmentFilter === 'Returning')>{{ __('auth.returning') }}</option>
                                    <option value="Regular"   @selected($segmentFilter === 'Regular')>{{ __('auth.regular') }}</option>
                                    <option value="VIP"       @selected($segmentFilter === 'VIP')>{{ __('auth.vip') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.min_spent') }}</label>
                                <input type="number" step="0.01" class="form-control" id="bcMinSpent" value="{{ $minSpent }}" placeholder="0.00">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.max_spent') }}</label>
                                <input type="number" step="0.01" class="form-control" id="bcMaxSpent" value="{{ $maxSpent }}" placeholder="—">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.min_orders') }}</label>
                                <input type="number" class="form-control" id="bcMinOrders" value="{{ $minOrders }}" placeholder="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.max_orders') }}</label>
                                <input type="number" class="form-control" id="bcMaxOrders" value="{{ $maxOrders }}" placeholder="—">
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="bcApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="bcClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="bcSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 6; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card h-100"><div class="card-body text-center">
                            <div class="bc-sk bc-sk-circle mx-auto mb-3"></div>
                            <div class="bc-sk bc-sk-text mx-auto" style="width:60%;height:24px;"></div>
                            <div class="bc-sk bc-sk-text mx-auto mt-2" style="width:80%;"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="bc-sk bc-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="bc-sk bc-sk-text mb-2" style="width:35%;"></div>
                                <div class="bc-sk bc-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="bc-sk bc-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="bcContent" class="d-none">

                    <div id="bcEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_customers_found_for_period') }}</p>
                    </div></div>

                    {{-- KPI cards --}}
                    <div id="bcKpiCards" class="row g-6 mb-6"></div>

                    {{-- Segments --}}
                    <div id="bcSegments" class="row g-6 mb-6"></div>

                    {{-- Top customers chart --}}
                    <div id="bcChartCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-bar-chart fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.top_customers') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div id="bcChart" style="height:400px;"></div>
                        </div>
                    </div>

                    {{-- Customers table --}}
                    <div id="bcTableCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.customer_sales_report') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="bcTableBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="bcCustomersTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.rank') }}</th>
                                    <th>{{ __('accounting.customer') }}</th>
                                    <th>{{ __('auth.segment') }}</th>
                                    <th class="text-center">{{ __('auth.order_count') }}</th>
                                    <th class="text-end">{{ __('auth.total_spent') }}</th>
                                    <th class="text-end">{{ __('accounting.average_amount') }}</th>
                                    <th class="text-end">{{ __('auth.min_order') }}</th>
                                    <th class="text-end">{{ __('auth.max_order') }}</th>
                                    <th class="text-end">{{ __('auth.total_tax') }}</th>
                                    <th class="text-end">{{ __('auth.total_discount') }}</th>
                                    <th>{{ __('auth.last_order') }}</th>
                                    <th class="pe-4" style="width:15%;">{{ __('accounting.percentage') }}</th>
                                </tr></thead>
                                <tbody id="bcCustomersBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="bcPaginationWrap">
                                <span class="text-muted fs-7" id="bcPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="bcPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Top 5 / Bottom 5 --}}
                    <div class="row g-6 mt-6">
                        <div class="col-lg-6"><div id="bcTop5Card" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-trophy fs-2 me-2 text-success"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_5_customers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-success">
                                        <th class="ps-4">{{ __('accounting.rank') }}</th>
                                        <th>{{ __('accounting.customer') }}</th>
                                        <th class="text-end">{{ __('auth.total_spent') }}</th>
                                        <th class="text-end pe-4">{{ __('auth.order_count') }}</th>
                                    </tr></thead>
                                    <tbody id="bcTop5Body"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div id="bcBottom5Card" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-clock fs-2 me-2 text-danger"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.bottom_5_customers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                        <th class="ps-4">{{ __('accounting.rank') }}</th>
                                        <th>{{ __('accounting.customer') }}</th>
                                        <th class="text-end">{{ __('auth.total_spent') }}</th>
                                        <th class="text-end pe-4">{{ __('auth.order_count') }}</th>
                                    </tr></thead>
                                    <tbody id="bcBottom5Body"></tbody>
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
{{-- DRAWER — per-customer order history                            --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="bcDrawer" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-person-badge fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="bcDrawerLabel">{{ __('auth.customer_sales_report') }}</h4>
                <span class="text-white opacity-75 fs-7" id="bcDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="bcDrawerSkeleton">
            <div class="bc-sk bc-sk-text mb-3" style="width:60%;"></div>
            <div class="bc-sk bc-sk-text mb-3" style="width:40%;"></div>
            <div class="bc-sk bc-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="bcDrawerContent" class="d-none"></div>
    </div>
</div>

@endif
@endsection

@push('styles')
<style>
    .bc-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: bc-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .bc-sk-text   { height: 12px; border-radius: 4px; }
    .bc-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .bc-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes bc-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #bcSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .bc-row { cursor: pointer; }
    .bc-row:hover { background-color: #f9f9f9; }
    .bc-row-top3 { background-color: rgba(80, 205, 137, .08); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.BC_ROUTES = {
        data:   '{{ route("reports.orders.by-customer-data") }}',
        orders: '{{ route("reports.orders.by-customer-orders", ["customerKey" => "__KEY__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.BC_BOOTSTRAP = {
        startDate:     @json($startDate),
        endDate:       @json($endDate),
        locationId:    @json($locationId),
        departmentId:  @json($departmentId),
        minSpent:      @json($minSpent),
        maxSpent:      @json($maxSpent),
        minOrders:     @json($minOrders),
        maxOrders:     @json($maxOrders),
        customerType:  @json($customerType ?? 'all'),
        segmentFilter: @json($segmentFilter),
        perPage:       @json($perPage),
    };
    window.BC_I18N = {
        // KPI cards
        totalCustomers:        @json(__('auth.total_customers')),
        totalSales:            @json(__('auth.total_sales')),
        totalOrders:           @json(__('auth.total_orders')),
        averagePerCustomer:    @json(__('auth.average_per_customer')),
        averageOrderValue:     @json(__('auth.average_order_value')),
        totalTax:              @json(__('auth.total_tax')),
        totalDiscount:         @json(__('auth.total_discount')),
        registered:            @json(__('auth.registered')),
        guest:                 @json(__('auth.guest')),
        avgPerCustomer:        @json(__('auth.average_orders_per_customer')),
        avgCustomer:           @json(__('auth.avg_per_customer')),

        // segments
        newCustomers:          @json(__('auth.new_customers')),
        returningCustomers:    @json(__('auth.returning_customers')),
        regularCustomers:      @json(__('auth.regular_customers')),
        vipCustomers:          @json(__('auth.vip_customers')),
        oneOrder:              @json(__('auth.one_order')),
        twoToFiveOrders:       @json(__('auth.two_to_five_orders')),
        sixToTwentyOrders:     @json(__('auth.six_to_twenty_orders')),
        overTwentyOrders:      @json(__('auth.over_twenty_orders')),

        // table
        rank:                  @json(__('accounting.rank')),
        customer:              @json(__('accounting.customer')),
        segment:               @json(__('auth.segment')),
        orderCount:            @json(__('auth.order_count')),
        totalSpent:            @json(__('auth.total_spent')),
        averageAmount:         @json(__('accounting.average_amount')),
        minOrder:              @json(__('auth.min_order')),
        maxOrder:              @json(__('auth.max_order')),
        lastOrder:             @json(__('auth.last_order')),
        percentage:            @json(__('accounting.percentage')),
        orders:                @json(__('auth.orders')),
        customers:             @json(__('auth.customers')),
        top:                   @json(__('accounting.top')),

        // drawer
        orderHistory:          @json(__('auth.order_history')),
        orderNumber:           @json(__('auth.order_number')),
        date:                  @json(__('auth.date')),
        status:                @json(__('auth.status')),
        location:              @json(__('auth.location')),
        department:            @json(__('auth.department')),
        items:                 @json(__('auth.items')),
        subtotal:              @json(__('auth.subtotal')),
        tax:                   @json(__('auth.total_tax')),
        discount:              @json(__('auth.total_discount')),
        total:                 @json(__('auth.total')),
        paid:                  @json(__('auth.paid')),
        balance:               @json(__('auth.balance')),
        close:                 @json(__('auth.close')),
        noOrderHistory:        @json(__('auth.no_order_history')),
    };
</script>
@include('reports.orders.by-customer-scripts')
@endpush