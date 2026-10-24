@extends('layouts.app')

@section('title', __('auth.discount_analysis_report'))

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
                                {{ __('auth.discount_analysis_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.discount_analysis_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="daExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="daPrintBtn">
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
                        <form id="daFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="daStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="daEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="daLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="daDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.employee') }}</label>
                                <select class="form-select" id="daEmployee" data-control="select2" data-placeholder="{{ __('auth.all_employees') }}">
                                    <option value=""></option>
                                    @foreach($employeesList as $emp)
                                        <option value="{{ $emp->id }}" @selected((int)$employeeId === $emp->id)>{{ $emp->first_name }} {{ $emp->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="daApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="daClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="daSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body text-center">
                            <div class="da-sk da-sk-text mx-auto" style="width:60%;height:24px;"></div>
                            <div class="da-sk da-sk-text mx-auto mt-2" style="width:80%;"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body">
                            <div class="da-sk" style="height:300px;border-radius:8px;"></div>
                        </div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="da-sk da-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="da-sk da-sk-text mb-2" style="width:35%;"></div>
                                <div class="da-sk da-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="da-sk da-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="daContent" class="d-none">

                    <div id="daEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-percent fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_discounted_orders_found_for_period') }}</p>
                    </div></div>

                    {{-- Summary cards --}}
                    <div id="daSummaryCards" class="row g-6 mb-6"></div>

                    {{-- Effectiveness comparison --}}
                    <div id="daEffectiveness" class="row g-6 mb-6"></div>

                    {{-- Charts: day, hour --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div id="daDayCard" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-bar-chart fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discount_by_day_of_week') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0"><div id="daDayChart" style="height:300px;"></div></div>
                        </div></div>
                        <div class="col-lg-6"><div id="daHourCard" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-clock fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discount_by_hour') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0"><div id="daHourChart" style="height:300px;"></div></div>
                        </div></div>
                    </div>

                    {{-- Range donut + breakdown --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div id="daRangeCard" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-pie-chart fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discount_range_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0"><div id="daRangeChart" style="height:300px;"></div></div>
                        </div></div>
                        <div class="col-lg-4"><div id="daRangeTableCard" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.breakdown') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4">{{ __('auth.discount_range') }}</th>
                                        <th>{{ __('auth.orders_count') }}</th>
                                        <th class="pe-4">{{ __('accounting.percentage') }}</th>
                                    </tr></thead>
                                    <tbody id="daRangeBody"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                    </div>

                    {{-- By employee --}}
                    <div id="daByEmployeeCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-people fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discount_by_employee') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="daByEmployeeBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('accounting.employee') }}</th>
                                    <th>{{ __('auth.discounted_orders') }}</th>
                                    <th class="text-end">{{ __('auth.total_discount_given') }}</th>
                                    <th class="text-end">{{ __('auth.average_discount') }}</th>
                                    <th class="text-end">{{ __('auth.discount_per_order') }}</th>
                                    <th class="pe-4">{{ __('auth.discount_sales_percentage') }}</th>
                                </tr></thead>
                                <tbody id="daByEmployeeBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- Discounted orders table --}}
                    <div id="daOrdersCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discounted_orders_list') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="daOrdersBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="daOrdersTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('auth.order_number') }}</th>
                                    <th>{{ __('auth.customer') }}</th>
                                    <th class="text-end">{{ __('auth.order_total') }}</th>
                                    <th class="text-end">{{ __('auth.discount_amount') }}</th>
                                    <th>{{ __('auth.discount_percentage') }}</th>
                                    <th class="text-end">{{ __('auth.final_amount') }}</th>
                                    <th>{{ __('auth.processed_by') }}</th>
                                    <th>{{ __('auth.order_date') }}</th>
                                    <th class="pe-4">{{ __('accounting.status') }}</th>
                                </tr></thead>
                                <tbody id="daOrdersBody"></tbody>
                                <tfoot class="bg-light" id="daOrdersFoot"></tfoot>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="daPaginationWrap">
                                <span class="text-muted fs-7" id="daPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="daPagination"></ul></nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DRAWER — Discounted order detail --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="daDrawer" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-percent fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="daDrawerLabel">{{ __('auth.discounted_orders_list') }}</h4>
                <span class="text-white opacity-75 fs-7" id="daDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="daDrawerSkeleton">
            <div class="da-sk da-sk-text mb-3" style="width:60%;"></div>
            <div class="da-sk da-sk-text mb-3" style="width:40%;"></div>
            <div class="da-sk da-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="daDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .da-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: da-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .da-sk-text   { height: 12px; border-radius: 4px; }
    .da-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes da-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #daSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .da-row { cursor: pointer; }
    .da-row:hover { background-color: #f9f9f9; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.DA_ROUTES = {
        data:  '{{ route("reports.orders.discount-analysis-data") }}',
        order: '{{ route("reports.orders.discount-analysis-order", ["orderId" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.DA_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        employeeId:   @json($employeeId),
        perPage:      @json($perPage),
    };
    window.DA_I18N = {
        // summary
        discountedOrders:       @json(__('auth.discounted_orders')),
        discountRate:           @json(__('auth.discount_rate')),
        totalDiscountGiven:     @json(__('auth.total_discount_given')),
        average:                @json(__('auth.average')),
        discountEffectiveness:  @json(__('auth.discount_effectiveness')),
        employeesGivingDiscounts:@json(__('auth.employees_giving_discounts')),
        none:                   @json(__('auth.none')),

        // with/without
        ordersWithDiscount:    @json(__('auth.orders_with_discount')),
        ordersWithoutDiscount: @json(__('auth.orders_without_discount')),
        avgOrderValue:         @json(__('auth.avg_order_value')),
        valueDifference:       @json(__('auth.value_difference')),
        perOrder:              @json(__('auth.per_order')),

        // by employee
        discountByEmployee:    @json(__('auth.discount_by_employee')),
        employee:              @json(__('accounting.employee')),
        averageDiscount:       @json(__('auth.average_discount')),
        discountPerOrder:      @json(__('auth.discount_per_order')),
        discountSalesPct:      @json(__('auth.discount_sales_percentage')),

        // by day/hour
        byDayOfWeek:           @json(__('auth.discount_by_day_of_week')),
        byHour:                @json(__('auth.discount_by_hour')),
        discountCount:         @json(__('auth.discount_count')),
        amountLbl:             @json(__('auth.amount')),

        // range
        rangeDistribution:     @json(__('auth.discount_range_distribution')),
        breakdown:             @json(__('auth.breakdown')),
        discountRange:         @json(__('auth.discount_range')),
        ordersCount:           @json(__('auth.orders_count')),

        // orders table
        discountedOrdersList:  @json(__('auth.discounted_orders_list')),
        orderNumber:           @json(__('auth.order_number')),
        customer:              @json(__('auth.customer')),
        orderTotal:            @json(__('auth.order_total')),
        discountAmount:        @json(__('auth.discount_amount')),
        discountPercentage:    @json(__('auth.discount_percentage')),
        finalAmount:           @json(__('auth.final_amount')),
        processedBy:           @json(__('auth.processed_by')),
        orderDate:             @json(__('auth.order_date')),
        status:                @json(__('accounting.status')),
        orders:                @json(__('auth.orders')),
        totals:                @json(__('accounting.totals')),

        // drawer
        orderDetails:          @json(__('auth.order_details')),
        subtotal:              @json(__('auth.subtotal')),
        subtotalBeforeBargain: @json(__('auth.subtotal_before_bargain')),
        lineDiscount:          @json(__('auth.line_discount')),
        bargainDiscount:       @json(__('auth.bargain_discount')),
        totalDiscount:         @json(__('auth.total_discount')),
        tax:                   @json(__('auth.tax')),
        total:                 @json(__('auth.total')),
        items:                 @json(__('auth.items')),
        product:               @json(__('auth.product')),
        qty:                   @json(__('auth.qty')),
        unitPrice:             @json(__('auth.unit_price')),
        lineTotal:             @json(__('auth.total')),
        noData:                @json(__('accounting.no_data_available')),
    };
</script>
@include('reports.orders.discount-analysis-scripts')
@endpush