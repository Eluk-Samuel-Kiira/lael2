@extends('layouts.app')

@section('title', __('auth.time_analysis'))

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
                                {{ __('auth.time_analysis') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.time_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="taExportBtn">
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
                        <form id="taFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="taStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="taEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="taLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="taDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.order_type') }}</label>
                                <select class="form-select" id="taType">
                                    <option value="all"    @selected($orderType === 'all')>{{ __('auth.all_types') }}</option>
                                    <option value="sale"   @selected($orderType === 'sale')>{{ __('auth.sale') }}</option>
                                    <option value="return" @selected($orderType === 'return')>{{ __('auth.return') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.group_by') }}</label>
                                <select class="form-select" id="taGroupBy">
                                    <option value="daily"   @selected($groupBy === 'daily')>{{ __('auth.daily') }}</option>
                                    <option value="hourly"  @selected($groupBy === 'hourly')>{{ __('auth.hourly') }}</option>
                                    <option value="weekly"  @selected($groupBy === 'weekly')>{{ __('auth.weekly') }}</option>
                                    <option value="monthly" @selected($groupBy === 'monthly')>{{ __('auth.monthly') }}</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="taApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="taClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="taSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 6; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card h-100"><div class="card-body text-center">
                            <div class="ta-sk ta-sk-circle mx-auto mb-3"></div>
                            <div class="ta-sk ta-sk-text mx-auto" style="width:60%;height:24px;"></div>
                            <div class="ta-sk ta-sk-text mx-auto mt-2" style="width:80%;"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body">
                            <div class="ta-sk" style="height:400px;border-radius:8px;"></div>
                        </div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="ta-sk ta-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="ta-sk ta-sk-text mb-2" style="width:35%;"></div>
                                <div class="ta-sk ta-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="ta-sk ta-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="taContent" class="d-none">

                    <div id="taEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_orders_found_for_period') }}</p>
                    </div></div>

                    {{-- Summary cards --}}
                    <div id="taSummaryCards" class="row g-6 mb-6"></div>

                    {{-- Growth cards --}}
                    <div id="taGrowthCards" class="row g-6 mb-6"></div>

                    {{-- Chart --}}
                    <div id="taChartCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0" id="taChartTitle">{{ __('auth.daily_sales_analysis') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div id="taChart" style="height:400px;"></div>
                        </div>
                    </div>

                    {{-- Peak days --}}
                    <div id="taPeakCard" class="card mb-6 d-none">
                        <div class="card-header bg-light-warning border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-calendar3 fs-2 me-2 text-warning"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.peak_days') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('accounting.date') }}</th>
                                    <th>{{ __('accounting.day') }}</th>
                                    <th>{{ __('auth.order_count') }}</th>
                                    <th class="text-end pe-4">{{ __('accounting.total_sales') }}</th>
                                </tr></thead>
                                <tbody id="taPeakBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- Peak hours (only when hourly) --}}
                    <div id="taPeakHoursCard" class="card mb-6 d-none">
                        <div class="card-header bg-light-success border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-clock fs-2 me-2 text-success"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.peak_hours') }}</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="taPeakHoursChart" style="height:250px;"></div>
                        </div>
                    </div>

                    {{-- Time analysis table --}}
                    <div id="taTableCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0" id="taTableTitle">{{ __('auth.daily_sales_report') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="taTableBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="taTable">
                                <thead id="taTableHead"></thead>
                                <tbody id="taTableBody"></tbody>
                                <tfoot class="bg-light" id="taTableFoot"></tfoot>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="taPaginationWrap">
                                <span class="text-muted fs-7" id="taPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="taPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .ta-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: ta-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .ta-sk-text   { height: 12px; border-radius: 4px; }
    .ta-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .ta-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes ta-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #taSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.TA_ROUTES = { data: '{{ route("reports.orders.time-analysis-data") }}' };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.TA_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        orderType:    @json($orderType ?? 'all'),
        groupBy:      @json($groupBy ?? 'daily'),
        perPage:      @json($perPage),
    };
    window.TA_I18N = {
        // summary
        totalPeriods:   @json(__('auth.total_periods')),
        totalOrders:    @json(__('auth.total_orders')),
        totalSales:     @json(__('accounting.total_sales')),
        totalTax:       @json(__('accounting.total_tax')),
        totalDiscount:  @json(__('accounting.total_discount')),
        averageSale:    @json(__('auth.average_sale')),

        // growth
        dailyGrowth:    @json(__('auth.daily_growth')),
        weeklyGrowth:   @json(__('auth.weekly_growth')),
        currentAverage: @json(__('auth.current_daily_average')),
        trendUpward:    @json(__('auth.trend_upward')),
        trendDownward:  @json(__('auth.trend_downward')),
        trendStable:    @json(__('auth.trend_stable')),

        // chart titles
        dailyAnalysis:   @json(__('auth.daily_sales_analysis')),
        hourlyAnalysis:  @json(__('auth.hourly_sales_analysis')),
        weeklyAnalysis:  @json(__('auth.weekly_sales_analysis')),
        monthlyAnalysis: @json(__('auth.monthly_sales_analysis')),

        // table titles
        dailyReport:   @json(__('auth.daily_sales_report')),
        hourlyReport:  @json(__('auth.hourly_sales_report')),
        weeklyReport:  @json(__('auth.weekly_sales_report')),
        monthlyReport: @json(__('auth.monthly_sales_report')),

        // table headers
        hour:          @json(__('accounting.hour')),
        timePeriod:    @json(__('accounting.time_period')),
        week:          @json(__('accounting.week')),
        dateRange:     @json(__('accounting.date_range')),
        month:         @json(__('accounting.month')),
        year:          @json(__('accounting.year')),
        date:          @json(__('accounting.date')),
        day:           @json(__('accounting.day')),
        orderCount:    @json(__('auth.order_count')),
        totalSalesLbl: @json(__('accounting.total_sales')),
        avgAmount:     @json(__('accounting.average_amount')),
        totalTaxLbl:   @json(__('accounting.total_tax')),
        totalDiscLbl:  @json(__('accounting.total_discount')),
        trend:         @json(__('accounting.trend')),
        totals:        @json(__('accounting.totals')),
        periods:       @json(__('accounting.periods')),
        days:          @json(__('accounting.days')),
        hours:         @json(__('accounting.hours')),
        weeks:         @json(__('accounting.weeks')),
        months:        @json(__('accounting.months')),

        // peak
        peakDays:      @json(__('auth.peak_days')),
        peakHours:     @json(__('auth.peak_hours')),
        noPeakDays:    @json(__('accounting.no_peak_days_found')),
    };
</script>
@include('reports.orders.time-analysis-scripts')
@endpush