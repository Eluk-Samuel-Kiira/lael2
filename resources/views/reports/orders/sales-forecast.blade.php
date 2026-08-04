{{-- resources/views/reports/orders/sales-forecast.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.sales_forecast_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                
                {{-- ============================================================ --}}
                {{-- TOOLBAR --}}
                {{-- ============================================================ --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('auth.sales_forecast_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.sales_forecast_report') }}</li>
                            </ul>
                        </div>
                        @if(isset($historicalData) && $historicalData->count() > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportForecastData()">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                                <i class="ki-duotone ki-printer fs-2"></i> {{ __('accounting.print') }}
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- FILTER SECTION --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form method="GET" action="{{ route('reports.orders.sales-forecast') }}" id="filterForm">
                            {{-- Row 1: Date Range & Forecast Period --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('accounting.start_date') }}</label>
                                    <input type="date" class="form-control" name="start_date" value="{{ $startDate ?? now()->subDays(90)->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('accounting.end_date') }}</label>
                                    <input type="date" class="form-control" name="end_date" value="{{ $endDate ?? now()->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('auth.forecast_period') }}</label>
                                    <select class="form-select" name="forecast_days">
                                        <option value="7" {{ ($forecastDays ?? 30) == 7 ? 'selected' : '' }}>{{ __('auth.next_7_days') }}</option>
                                        <option value="14" {{ ($forecastDays ?? 30) == 14 ? 'selected' : '' }}>{{ __('auth.next_14_days') }}</option>
                                        <option value="30" {{ ($forecastDays ?? 30) == 30 ? 'selected' : '' }}>{{ __('auth.next_30_days') }}</option>
                                        <option value="60" {{ ($forecastDays ?? 30) == 60 ? 'selected' : '' }}>{{ __('auth.next_60_days') }}</option>
                                        <option value="90" {{ ($forecastDays ?? 30) == 90 ? 'selected' : '' }}>{{ __('auth.next_90_days') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('auth.location') }}</label>
                                    <select class="form-select" name="location_id" data-control="select2">
                                        <option value="">{{ __('auth.all_locations') }}</option>
                                        @foreach($locations ?? [] as $location)
                                            <option value="{{ $location->id }}" {{ ($locationId ?? '') == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Row 2: Department & Quick Periods --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('auth.department') }}</label>
                                    <select class="form-select" name="department_id" data-control="select2">
                                        <option value="">{{ __('auth.all_departments') }}</option>
                                        @foreach($departments ?? [] as $department)
                                            <option value="{{ $department->id }}" {{ ($departmentId ?? '') == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">{{ __('auth.quick_periods') }}</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-light-primary" onclick="setHistoricalRange('last_30_days')">
                                            {{ __('auth.last_30_days') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light-primary" onclick="setHistoricalRange('last_90_days')">
                                            {{ __('auth.last_90_days') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light-primary" onclick="setHistoricalRange('last_180_days')">
                                            {{ __('auth.last_180_days') }}
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light-primary" onclick="setHistoricalRange('this_year')">
                                            {{ __('accounting.this_year') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ki-duotone ki-calculator fs-2 me-1"></i> {{ __('auth.generate_forecast') }}
                                    </button>
                                    <a href="{{ route('reports.orders.sales-forecast') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </a>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <span class="text-muted small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('auth.historical_data_hint') }}
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA --}}
                {{-- ============================================================ --}}
                @if(!isset($historicalData) || $historicalData->count() == 0)
                <div class="card">
                    <div class="card-body text-center py-10">
                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_historical_data_for_forecast') }}</p>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- FORECAST SUMMARY CARDS --}}
                {{-- ============================================================ --}}
                @php
                    $forecastSum = isset($forecastCollection) ? $forecastCollection->sum('forecast_sales') : 0;
                    $forecastAvg = isset($forecastCollection) ? $forecastCollection->avg('forecast_sales') : 0;
                    $forecastMax = isset($forecastCollection) ? $forecastCollection->max('forecast_sales') : 0;
                    $forecastMin = isset($forecastCollection) ? $forecastCollection->min('forecast_sales') : 0;
                    $growthRatePercentage = isset($growthRate) ? $growthRate * 100 : 0;
                    $confidenceScore = min(95, max(60, 85 - abs($growthRatePercentage) * 2));
                @endphp
                
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-primary">{{ currency_symbol() }}{{ number_format($forecastSum, 0) }}</div>
                                <div class="text-muted">{{ __('auth.total_forecast_sales') }}</div>
                                <span class="badge badge-light-primary mt-2">{{ $forecastDays ?? 30 }} {{ __('auth.days') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-success border border-success border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-{{ $growthRatePercentage >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($growthRatePercentage, 1) }}%
                                </div>
                                <div class="text-muted">{{ __('auth.daily_growth_rate') }}</div>
                                <span class="badge badge-light-{{ $growthRatePercentage >= 0 ? 'success' : 'danger' }} mt-2">
                                    <i class="ki-duotone ki-arrow-{{ $growthRatePercentage >= 0 ? 'up' : 'down' }} fs-4 me-1"></i>
                                    {{ $growthRatePercentage >= 0 ? __('auth.increasing') : __('auth.decreasing') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-{{ $confidenceScore >= 85 ? 'success' : ($confidenceScore >= 70 ? 'warning' : 'danger') }} border border-{{ $confidenceScore >= 85 ? 'success' : ($confidenceScore >= 70 ? 'warning' : 'danger') }} border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold">{{ number_format($confidenceScore, 0) }}%</div>
                                <div class="text-muted">{{ __('auth.confidence_score') }}</div>
                                <span class="badge badge-light-{{ $confidenceScore >= 85 ? 'success' : ($confidenceScore >= 70 ? 'warning' : 'danger') }} mt-2">
                                    {{ $confidenceScore >= 85 ? __('auth.high_confidence') : ($confidenceScore >= 70 ? __('auth.medium_confidence') : __('auth.low_confidence')) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-warning">{{ currency_symbol() }}{{ number_format($forecastAvg, 0) }}</div>
                                <div class="text-muted">{{ __('auth.daily_forecast_average') }}</div>
                                <span class="badge badge-light-warning mt-2">{{ currency_symbol() }}{{ number_format($forecastMin, 0) }} - {{ currency_symbol() }}{{ number_format($forecastMax, 0) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- FORECAST CHART --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.sales_forecast_chart') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="forecastChart" style="height: 450px;"></div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- FORECAST DETAILS TABLE --}}
                {{-- ============================================================ --}}
                @if(isset($forecastData) && $forecastData->count() > 0)
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.daily_forecast_details') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label me-2 mb-0 fs-7">{{ __('accounting.show') }}</label>
                                <select class="form-select form-select-sm w-auto" id="forecastPerPageSelect" onchange="changeForecastPerPage(this.value)">
                                    <option value="7" {{ ($forecastPerPage ?? 15) == 7 ? 'selected' : '' }}>7</option>
                                    <option value="15" {{ ($forecastPerPage ?? 15) == 15 ? 'selected' : '' }}>15</option>
                                    <option value="30" {{ ($forecastPerPage ?? 15) == 30 ? 'selected' : '' }}>30</option>
                                    <option value="50" {{ ($forecastPerPage ?? 15) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ ($forecastPerPage ?? 15) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th>{{ __('accounting.date') }}</th>
                                        <th>{{ __('accounting.day') }}</th>
                                        <th class="text-end">{{ __('auth.forecast_sales') }}</th>
                                        <th class="text-center">{{ __('auth.forecast_orders') }}</th>
                                        <th class="text-end">{{ __('auth.average_order_value') }}</th>
                                        <th class="text-center">{{ __('auth.confidence') }}</th>
                                        <th class="text-center">{{ __('accounting.trend') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($forecastData as $data)
                                    @php
                                        $date = \Carbon\Carbon::parse($data['date']);
                                        $dayName = $date->format('l');
                                        $isWeekend = in_array($dayName, ['Saturday', 'Sunday']);
                                        $confidenceLevel = $data['confidence'] ?? 'medium';
                                        $confidenceColor = $confidenceLevel == 'high' ? 'success' : ($confidenceLevel == 'medium' ? 'warning' : 'danger');
                                        $trendValue = $data['trend'] ?? 0;
                                        $trendClass = $trendValue > 0 ? 'success' : ($trendValue < 0 ? 'danger' : 'primary');
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $date->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ $isWeekend ? 'danger' : 'primary' }}">
                                                {{ $dayName }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($data['forecast_sales'], 0) }}</span>
                                            <br>
                                            <small class="text-muted">{{ currency_symbol() }}{{ number_format($data['confidence_low'] ?? $data['forecast_sales'] * 0.8, 0) }} - {{ currency_symbol() }}{{ number_format($data['confidence_high'] ?? $data['forecast_sales'] * 1.2, 0) }}</small>
                                        </td>
                                        <td class="text-center"><span class="badge badge-light-info">{{ number_format($data['forecast_orders'], 0) }}</span></td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($data['average_order_value'], 0) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $confidenceColor }}">{{ ucfirst($confidenceLevel) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $trendClass }}">
                                                @if($trendValue != 0)
                                                    <i class="ki-duotone ki-arrow-{{ $trendValue > 0 ? 'up' : 'down' }} fs-4 me-1"></i>
                                                    {{ number_format(abs($trendValue), 1) }}%
                                                @else
                                                    <i class="ki-duotone ki-minus fs-4 me-1"></i> 0%
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Forecast Pagination --}}
                    @if(isset($forecastData) && $forecastData->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $forecastData,
                            'pageName' => 'forecast_page',
                            'perPageName' => 'forecast_per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- SEASONALITY ANALYSIS --}}
                {{-- ============================================================ --}}
                @if(isset($seasonality) && $seasonality->count() > 0)
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-chart-pie-3 fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.seasonality_analysis') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="seasonalityChart" style="height: 300px;"></div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- HISTORICAL DATA TABLE --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.historical_sales_data') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label me-2 mb-0 fs-7">{{ __('accounting.show') }}</label>
                                <select class="form-select form-select-sm w-auto" id="historicalPerPageSelect" onchange="changeHistoricalPerPage(this.value)">
                                    <option value="10" {{ ($historicalPerPage ?? 15) == 10 ? 'selected' : '' }}>10</option>
                                    <option value="15" {{ ($historicalPerPage ?? 15) == 15 ? 'selected' : '' }}>15</option>
                                    <option value="25" {{ ($historicalPerPage ?? 15) == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ ($historicalPerPage ?? 15) == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ ($historicalPerPage ?? 15) == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th>{{ __('accounting.date') }}</th>
                                        <th>{{ __('accounting.day') }}</th>
                                        <th class="text-end">{{ __('auth.daily_sales') }}</th>
                                        <th class="text-center">{{ __('auth.order_count') }}</th>
                                        <th class="text-end">{{ __('auth.average_order_value') }}</th>
                                        <th class="text-center">{{ __('auth.trend') }}</th>
                                        <th class="text-center">{{ __('auth.deviation') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historicalData as $day)
                                    @php
                                        $date = \Carbon\Carbon::parse($day->date);
                                        $dayName = $date->format('l');
                                        $isWeekend = in_array($dayName, ['Saturday', 'Sunday']);
                                        $deviation = isset($historicalAvg) && $historicalAvg > 0 ? (($day->daily_sales - $historicalAvg) / $historicalAvg) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $date->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ $isWeekend ? 'danger' : 'primary' }}">
                                                {{ $dayName }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-bold text-success">{{ currency_symbol() }}{{ number_format($day->daily_sales, 2) }}</td>
                                        <td class="text-center"><span class="badge badge-light-info">{{ number_format($day->order_count) }}</span></td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($day->average_order_value, 2) }}</td>
                                        <td class="text-center">
                                            @if($loop->index < $loop->count - 1)
                                                @php
                                                    $next = $historicalData[$loop->index + 1];
                                                    $trend = $day->daily_sales - $next->daily_sales;
                                                    $trendPercent = $next->daily_sales > 0 ? ($trend / $next->daily_sales) * 100 : 0;
                                                @endphp
                                                <span class="badge badge-light-{{ $trend >= 0 ? 'success' : 'danger' }}">
                                                    {{ $trend >= 0 ? '+' : '' }}{{ number_format($trendPercent, 1) }}%
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ abs($deviation) >= 10 ? ($deviation > 0 ? 'success' : 'danger') : 'primary' }}">
                                                {{ number_format($deviation, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Historical Pagination --}}
                    @if(isset($historicalData) && $historicalData->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $historicalData,
                            'pageName' => 'historical_page',
                            'perPageName' => 'historical_per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif
                </div>

                {{-- ============================================================ --}}
                {{-- METADATA FOOTER --}}
                {{-- ============================================================ --}}
                <div class="mt-6 text-muted text-center fs-7">
                    <hr>
                    <p>
                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                        {{ __('auth.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }} 
                        | {{ __('accounting.period') }}: {{ $startDate ?? 'N/A' }} {{ __('accounting.to') }} {{ $endDate ?? 'N/A' }}
                        @if(isset($locationId) && $locationId)
                            | {{ __('auth.location') }}: {{ $locations->where('id', $locationId)->first()->name ?? 'N/A' }}
                        @endif
                        @if(isset($departmentId) && $departmentId)
                            | {{ __('auth.department') }}: {{ $departments->where('id', $departmentId)->first()->name ?? 'N/A' }}
                        @endif
                        | {{ $historicalData->count() ?? 0 }} {{ __('accounting.days') }} {{ __('auth.analyzed') }}
                        | {{ __('auth.confidence_score') }}: {{ number_format($confidenceScore, 0) }}%
                    </p>
                </div>
                
                @endif
                
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================================ --}}
@push('scripts')
@if(isset($historicalData) && $historicalData->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Prepare Data ──────────────────────────────────────────────
    const historicalData = @json($historicalData->items());
    const forecastData = @json($forecast ?? []);
    
    const historicalDates = historicalData.map(d => d.date);
    const historicalSales = historicalData.map(d => parseFloat(d.daily_sales));
    const historicalOrders = historicalData.map(d => d.order_count);
    
    const forecastDates = Object.keys(forecastData || {});
    const forecastSales = Object.values(forecastData || {}).map(d => d.forecast_sales);
    const forecastConfidenceHigh = Object.values(forecastData || {}).map(d => d.confidence_high || d.forecast_sales * 1.2);
    const forecastConfidenceLow = Object.values(forecastData || {}).map(d => d.confidence_low || d.forecast_sales * 0.8);

    // ─── Forecast Chart ────────────────────────────────────────────
    new ApexCharts(document.querySelector("#forecastChart"), {
        series: [
            {
                name: '{{ __("auth.historical_sales") }}',
                type: 'line',
                data: historicalSales.map((sales, index) => ({ x: historicalDates[index], y: sales }))
            },
            {
                name: '{{ __("auth.forecast_sales") }}',
                type: 'line',
                data: forecastSales.map((sales, index) => ({ x: forecastDates[index], y: sales }))
            },
            {
                name: '{{ __("auth.confidence_interval") }}',
                type: 'area',
                data: forecastConfidenceHigh.map((high, index) => ({ x: forecastDates[index], y: high }))
            }
        ],
        chart: { height: 450, type: 'line', toolbar: { show: true } },
        dataLabels: { enabled: false },
        stroke: { width: [3, 3, 0], curve: 'smooth', dashArray: [0, 5, 0] },
        colors: ['#009EF7', '#50CD89', 'rgba(80, 205, 137, 0.15)'],
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05, stops: [0, 100] }
        },
        markers: { size: [4, 4, 0] },
        xaxis: {
            type: 'datetime',
            labels: { style: { fontSize: '11px' }, datetimeFormatter: { day: 'dd MMM' } }
        },
        yaxis: {
            title: { text: '{{ __("accounting.total_sales") }} ($)' },
            labels: { formatter: function(val) { return '$' + val.toLocaleString(); } }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: { formatter: function(val) { return '$' + val.toLocaleString(); } }
        },
        legend: { position: 'top', horizontalAlign: 'left' },
        grid: { borderColor: '#E5E7EB', strokeDashArray: 4 }
    }).render();

    // ─── Seasonality Chart ──────────────────────────────────────────
    const seasonalityData = @json($seasonality ?? []);
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const seasonalityValues = [];
    
    for (let i = 1; i <= 7; i++) {
        const dayData = seasonalityData.find(d => d.day_of_week === i);
        seasonalityValues.push(dayData ? dayData.average_sales : 0);
    }

    new ApexCharts(document.querySelector("#seasonalityChart"), {
        series: [{ data: seasonalityValues }],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        colors: ['#009EF7', '#50CD89', '#7239EA', '#FFA800', '#F64E60', '#3E97FF', '#A1A5B7'],
        plotOptions: { bar: { distributed: true, borderRadius: 4, columnWidth: '70%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: dayNames },
        yaxis: {
            title: { text: '{{ __("auth.average_sales") }} ($)' },
            labels: { formatter: function(val) { return '$' + val.toLocaleString(); } }
        },
        tooltip: { y: { formatter: function(val) { return '$' + val.toLocaleString(); } } }
    }).render();
});

// ─── Quick Date Range ─────────────────────────────────────────────
function setHistoricalRange(range) {
    const today = new Date();
    const startInput = document.querySelector('input[name="start_date"]');
    const endInput = document.querySelector('input[name="end_date"]');
    
    let startDate = new Date(today);
    switch(range) {
        case 'last_30_days': startDate.setDate(today.getDate() - 30); break;
        case 'last_90_days': startDate.setDate(today.getDate() - 90); break;
        case 'last_180_days': startDate.setDate(today.getDate() - 180); break;
        case 'this_year': startDate = new Date(today.getFullYear(), 0, 1); break;
    }
    
    startInput.value = startDate.toISOString().split('T')[0];
    endInput.value = today.toISOString().split('T')[0];
    document.getElementById('filterForm').submit();
}

// ─── Pagination Controls ──────────────────────────────────────────
function changeHistoricalPerPage(perPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('historical_per_page', perPage);
    url.searchParams.set('historical_page', '1');
    window.location.href = url.toString();
}

function changeForecastPerPage(perPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('forecast_per_page', perPage);
    url.searchParams.set('forecast_page', '1');
    window.location.href = url.toString();
}

// ─── Export Functions ─────────────────────────────────────────────
function exportForecastData() {
    const historicalData = @json($historicalData->items());
    const forecastData = @json($forecast ?? []);
    
    const rows = [['Date', 'Day', 'Historical Sales', 'Order Count', 'Avg Order Value', 'Forecast Sales', 'Forecast Orders', 'Confidence Low', 'Confidence High']];
    
    const allDates = new Set([...historicalData.map(d => d.date), ...Object.keys(forecastData)]);
    Array.from(allDates).sort().forEach(date => {
        const hist = historicalData.find(d => d.date === date);
        const fcst = forecastData[date];
        const dayName = new Date(date).toLocaleDateString('en-US', { weekday: 'long' });
        rows.push([
            date, dayName,
            hist ? hist.daily_sales : '',
            hist ? hist.order_count : '',
            hist ? hist.average_order_value : '',
            fcst ? fcst.forecast_sales : '',
            fcst ? fcst.forecast_orders : '',
            fcst ? (fcst.confidence_low || fcst.forecast_sales * 0.8) : '',
            fcst ? (fcst.confidence_high || fcst.forecast_sales * 1.2) : ''
        ]);
    });
    
    const csv = rows.map(row => row.join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `sales_forecast_${new Date().toISOString().split('T')[0]}.csv`;
    link.click();
    URL.revokeObjectURL(link.href);
}

// ─── Form Validation ──────────────────────────────────────────────
document.getElementById('filterForm')?.addEventListener('submit', function(e) {
    const start = new Date(document.querySelector('[name="start_date"]').value);
    const end = new Date(document.querySelector('[name="end_date"]').value);
    if (start > end) {
        e.preventDefault();
        alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
    }
});
</script>
@endif
@endpush

{{-- ============================================================ --}}
{{-- PRINT STYLES --}}
{{-- ============================================================ --}}
@push('styles')
<style>
@media print {
    .app-toolbar, #filterForm, .dropdown, .no-print, .card-header .btn { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
    .card-body { padding: 0 !important; }
    #forecastChart, #seasonalityChart { height: 300px !important; }
    .table-responsive { overflow: visible !important; }
    .badge { border: 1px solid #E5E7EB !important; }
}
.apexcharts-tooltip { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-radius: 8px; border: 1px solid #E5E7EB; }
.card-flush { transition: transform 0.2s ease-in-out; }
.card-flush:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
@media (max-width: 768px) {
    #forecastChart { height: 300px !important; }
    .table-responsive { font-size: 0.875rem; }
}
</style>
@endpush

@endsection