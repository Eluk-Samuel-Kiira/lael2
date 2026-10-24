@extends('layouts.app')

@section('title', __('auth.sales_forecast_report'))

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
                                {{ __('auth.sales_forecast_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.sales_forecast_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="sfExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="sfPrintBtn">
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
                        <form id="sfFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="sfStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="sfEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.forecast_period') }}</label>
                                <select class="form-select" id="sfForecastDays">
                                    <option value="7"  @selected((int)$forecastDays === 7)>{{ __('auth.next_7_days') }}</option>
                                    <option value="14" @selected((int)$forecastDays === 14)>{{ __('auth.next_14_days') }}</option>
                                    <option value="30" @selected((int)$forecastDays === 30)>{{ __('auth.next_30_days') }}</option>
                                    <option value="60" @selected((int)$forecastDays === 60)>{{ __('auth.next_60_days') }}</option>
                                    <option value="90" @selected((int)$forecastDays === 90)>{{ __('auth.next_90_days') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="sfLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="sfDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('auth.quick_periods') }}</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-light-primary sf-quick-range" data-days="30">{{ __('auth.last_30_days') }}</button>
                                    <button type="button" class="btn btn-sm btn-light-primary sf-quick-range" data-days="90">{{ __('auth.last_90_days') }}</button>
                                    <button type="button" class="btn btn-sm btn-light-primary sf-quick-range" data-days="180">{{ __('auth.last_180_days') }}</button>
                                    <button type="button" class="btn btn-sm btn-light-primary sf-quick-range" data-days="this_year">{{ __('accounting.this_year') }}</button>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="sfApplyBtn">
                                    <i class="bi bi-calculator me-1"></i>{{ __('auth.generate_forecast') }}
                                </button>
                                <button type="button" class="btn btn-light" id="sfClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="sfSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body text-center">
                            <div class="sf-sk sf-sk-text mx-auto" style="width:60%;height:24px;"></div>
                            <div class="sf-sk sf-sk-text mx-auto mt-2" style="width:80%;"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body">
                            <div class="sf-sk" style="height:450px;border-radius:8px;"></div>
                        </div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="sf-sk sf-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="sf-sk sf-sk-text mb-2" style="width:35%;"></div>
                                <div class="sf-sk sf-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="sf-sk sf-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="sfContent" class="d-none">

                    <div id="sfEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_historical_data_for_forecast') }}</p>
                    </div></div>

                    {{-- Summary cards --}}
                    <div id="sfSummaryCards" class="row g-6 mb-6"></div>

                    {{-- Forecast chart --}}
                    <div id="sfForecastCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.sales_forecast_chart') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0"><div id="sfForecastChart" style="height:450px;"></div></div>
                    </div>

                    {{-- Forecast details --}}
                    <div id="sfForecastTableCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-calendar3 fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.daily_forecast_details') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('accounting.date') }}</th>
                                    <th>{{ __('accounting.day') }}</th>
                                    <th class="text-end">{{ __('auth.forecast_sales') }}</th>
                                    <th class="text-center">{{ __('auth.forecast_orders') }}</th>
                                    <th class="text-end">{{ __('auth.average_order_value') }}</th>
                                    <th class="text-center">{{ __('auth.confidence') }}</th>
                                    <th class="pe-4 text-center">{{ __('accounting.trend') }}</th>
                                </tr></thead>
                                <tbody id="sfForecastBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="sfForecastPaginationWrap">
                                <span class="text-muted fs-7" id="sfForecastPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="sfForecastPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Seasonality --}}
                    <div id="sfSeasonalityCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-bar-chart fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.seasonality_analysis') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0"><div id="sfSeasonalityChart" style="height:300px;"></div></div>
                    </div>

                    {{-- Historical --}}
                    <div id="sfHistoricalCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.historical_sales_data') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="sfHistoricalBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('accounting.date') }}</th>
                                    <th>{{ __('accounting.day') }}</th>
                                    <th class="text-end">{{ __('auth.daily_sales') }}</th>
                                    <th class="text-center">{{ __('auth.order_count') }}</th>
                                    <th class="text-end">{{ __('auth.average_order_value') }}</th>
                                    <th class="text-center">{{ __('auth.trend') }}</th>
                                    <th class="pe-4 text-center">{{ __('auth.deviation') }}</th>
                                </tr></thead>
                                <tbody id="sfHistoricalBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="sfHistoricalPaginationWrap">
                                <span class="text-muted fs-7" id="sfHistoricalPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="sfHistoricalPagination"></ul></nav>
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
    .sf-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: sf-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .sf-sk-text   { height: 12px; border-radius: 4px; }
    .sf-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes sf-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #sfSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    @media print {
        .app-toolbar, #sfFilterForm, .dropdown, .no-print { display: none !important; }
        #sfForecastChart, #sfSeasonalityChart { height: 300px !important; }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.SF_ROUTES = { data: '{{ route("reports.orders.sales-forecast-data") }}' };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.SF_BOOTSTRAP = {
        startDate:           @json($startDate),
        endDate:             @json($endDate),
        forecastDays:        @json($forecastDays),
        locationId:          @json($locationId),
        departmentId:        @json($departmentId),
        historicalPerPage:   @json($historicalPerPage),
        forecastPerPage:     @json($forecastPerPage),
    };
    window.SF_I18N = {
        // summary
        totalForecastSales:    @json(__('auth.total_forecast_sales')),
        dailyGrowthRate:       @json(__('auth.daily_growth_rate')),
        increasing:            @json(__('auth.increasing')),
        decreasing:            @json(__('auth.decreasing')),
        confidenceScore:       @json(__('auth.confidence_score')),
        highConfidence:        @json(__('auth.high_confidence')),
        mediumConfidence:      @json(__('auth.medium_confidence')),
        lowConfidence:         @json(__('auth.low_confidence')),
        dailyForecastAverage:  @json(__('auth.daily_forecast_average')),
        days:                  @json(__('auth.days')),

        // table headers
        date:                  @json(__('accounting.date')),
        day:                   @json(__('accounting.day')),
        forecastSales:         @json(__('auth.forecast_sales')),
        forecastOrders:        @json(__('auth.forecast_orders')),
        averageOrderValue:     @json(__('auth.average_order_value')),
        confidence:            @json(__('auth.confidence')),
        trend:                 @json(__('auth.trend')),
        deviation:             @json(__('auth.deviation')),
        dailySales:            @json(__('auth.daily_sales')),
        orderCount:            @json(__('auth.order_count')),

        // chart labels
        historicalSales:       @json(__('auth.historical_sales')),
        confidenceInterval:    @json(__('auth.confidence_interval')),
        averageSales:          @json(__('auth.average_sales')),
        totalSales:            @json(__('accounting.total_sales')),

        // misc
        daysLabel:             @json(__('accounting.days')),
        historicalData:        @json(__('auth.historical_sales_data')),
        dailyForecast:         @json(__('auth.daily_forecast_details')),
    };
</script>
@include('reports.orders.sales-forecast-scripts')
@endpush