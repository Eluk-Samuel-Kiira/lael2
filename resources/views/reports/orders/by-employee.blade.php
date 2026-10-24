@extends('layouts.app')

@section('title', __('auth.employee_performance'))

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
                                {{ __('auth.employee_performance') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.employee_performance') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="epExportBtn">
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
                        <form id="epFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="epStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="epEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="epLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="epDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.employee') }}</label>
                                <select class="form-select" id="epEmployee" data-control="select2" data-placeholder="{{ __('auth.all_employees') }}">
                                    <option value=""></option>
                                    @foreach($employeesList as $emp)
                                        <option value="{{ $emp->id }}" @selected((int)$employeeId === $emp->id)>{{ $emp->first_name }} {{ $emp->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.min_sales') }}</label>
                                <input type="number" step="0.01" class="form-control" id="epMinSales" value="{{ $minSales }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.max_sales') }}</label>
                                <input type="number" step="0.01" class="form-control" id="epMaxSales" value="{{ $maxSales }}">
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="epApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="epClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="epSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 6; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card h-100"><div class="card-body text-center">
                            <div class="ep-sk ep-sk-circle mx-auto mb-3"></div>
                            <div class="ep-sk ep-sk-text mx-auto" style="width:60%;height:24px;"></div>
                            <div class="ep-sk ep-sk-text mx-auto mt-2" style="width:80%;"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body">
                            <div class="ep-sk" style="height:400px;border-radius:8px;"></div>
                        </div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="ep-sk ep-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="ep-sk ep-sk-text mb-2" style="width:35%;"></div>
                                <div class="ep-sk ep-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="ep-sk ep-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="epContent" class="d-none">

                    <div id="epEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_employees_found_for_period') }}</p>
                    </div></div>

                    {{-- Summary --}}
                    <div id="epSummaryCards" class="row g-6 mb-6"></div>

                    {{-- Rating distribution --}}
                    <div id="epRatingCards" class="row g-6 mb-6"></div>

                    {{-- Chart --}}
                    <div id="epChartCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-bar-chart fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.top_performers') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div id="epChart" style="height:400px;"></div>
                        </div>
                    </div>

                    {{-- Employees table --}}
                    <div id="epTableCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.employee_performance_report') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="epTableBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="epEmployeesTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.rank') }}</th>
                                    <th>{{ __('accounting.employee') }}</th>
                                    <th class="text-center">{{ __('auth.order_count') }}</th>
                                    <th class="text-end">{{ __('accounting.total_sales') }}</th>
                                    <th class="text-end">{{ __('accounting.total_tax') }}</th>
                                    <th class="text-end">{{ __('accounting.total_discount') }}</th>
                                    <th class="text-end">{{ __('accounting.average_amount') }}</th>
                                    <th class="text-end">{{ __('auth.largest_sale') }}</th>
                                    <th class="text-end">{{ __('auth.smallest_sale') }}</th>
                                    <th class="text-center">{{ __('auth.unique_customers') }}</th>
                                    <th class="text-center">{{ __('auth.orders_per_day') }}</th>
                                    <th class="text-end">{{ __('auth.sales_per_day') }}</th>
                                    <th>{{ __('auth.performance_rating') }}</th>
                                    <th>{{ __('auth.last_sale_date') }}</th>
                                    <th class="pe-4" style="width:15%;">{{ __('accounting.percentage') }}</th>
                                </tr></thead>
                                <tbody id="epEmployeesBody"></tbody>
                                <tfoot class="bg-light" id="epEmployeesFoot"></tfoot>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="epPaginationWrap">
                                <span class="text-muted fs-7" id="epPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="epPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Top / Bottom 5 --}}
                    <div class="row g-6 mt-6">
                        <div class="col-lg-6"><div id="epTop5Card" class="card h-100 d-none">
                            <div class="card-header bg-light-success border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-trophy fs-2 me-2 text-success"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_5_performers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4">{{ __('accounting.rank') }}</th>
                                        <th>{{ __('accounting.employee') }}</th>
                                        <th class="text-end pe-4">{{ __('accounting.total_sales') }}</th>
                                    </tr></thead>
                                    <tbody id="epTop5Body"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div id="epBottom5Card" class="card h-100 d-none">
                            <div class="card-header bg-light-danger border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-arrow-down-circle fs-2 me-2 text-danger"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.bottom_5_performers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4">{{ __('accounting.rank') }}</th>
                                        <th>{{ __('accounting.employee') }}</th>
                                        <th class="text-end pe-4">{{ __('accounting.total_sales') }}</th>
                                    </tr></thead>
                                    <tbody id="epBottom5Body"></tbody>
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
{{-- DRAWER — per-employee orders                                   --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="epDrawer" style="width: 900px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-person-badge fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="epDrawerLabel">{{ __('auth.employee_performance_report') }}</h4>
                <span class="text-white opacity-75 fs-7" id="epDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="epDrawerSkeleton">
            <div class="ep-sk ep-sk-text mb-3" style="width:60%;"></div>
            <div class="ep-sk ep-sk-text mb-3" style="width:40%;"></div>
            <div class="ep-sk ep-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="epDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .ep-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: ep-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .ep-sk-text   { height: 12px; border-radius: 4px; }
    .ep-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .ep-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes ep-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #epSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .ep-row { cursor: pointer; }
    .ep-row:hover { background-color: #f9f9f9; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.EP_ROUTES = {
        data:   '{{ route("reports.orders.by-employee-data") }}',
        orders: '{{ route("reports.orders.by-employee-orders", ["employeeId" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.EP_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        employeeId:   @json($employeeId),
        minSales:     @json($minSales),
        maxSales:     @json($maxSales),
        perPage:      @json($perPage),
    };
    window.EP_I18N = {
        totalEmployees:  @json(__('auth.total_employees')),
        totalSales:      @json(__('accounting.total_sales')),
        totalOrders:     @json(__('auth.total_orders')),
        avgSalesPerDay:  @json(__('auth.avg_sales_per_day')),
        avgOrderValue:   @json(__('accounting.average_amount')),
        topEmployee:     @json(__('auth.top_employee')),

        employee:        @json(__('accounting.employee')),
        orderCount:      @json(__('auth.order_count')),
        totalTax:        @json(__('accounting.total_tax')),
        totalDiscount:   @json(__('accounting.total_discount')),
        largestSale:     @json(__('auth.largest_sale')),
        smallestSale:    @json(__('auth.smallest_sale')),
        uniqueCustomers: @json(__('auth.unique_customers')),
        ordersPerDay:    @json(__('auth.orders_per_day')),
        salesPerDay:     @json(__('auth.sales_per_day')),
        performanceRating: @json(__('auth.performance_rating')),
        lastSaleDate:    @json(__('auth.last_sale_date')),
        rank:            @json(__('accounting.rank')),
        percentage:      @json(__('accounting.percentage')),
        totals:          @json(__('accounting.totals')),

        excellent:       @json(__('auth.excellent')),
        good:            @json(__('auth.good')),
        average:         @json(__('auth.average')),
        needsImprovement:@json(__('auth.needs_improvement')),

        // drawer
        orderNumber:     @json(__('auth.order_number')),
        date:            @json(__('auth.date')),
        status:          @json(__('auth.status')),
        customer:        @json(__('auth.customer')),
        location:        @json(__('auth.location')),
        department:      @json(__('auth.department')),
        total:           @json(__('auth.total')),
        paid:            @json(__('auth.paid')),
        balance:         @json(__('auth.balance')),
        noOrders:        @json(__('auth.no_orders_found_for_period')),
    };
</script>
@include('reports.orders.by-employee-scripts')
@endpush