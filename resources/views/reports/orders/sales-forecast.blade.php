{{-- resources/views/reports/orders/sales-forecast.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.sales_forecast_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
                        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                {{ __('auth.sales_forecast_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.sales_forecast_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($historicalData->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportForecastData()">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="printForecast()">
                                            <i class="ki-duotone ki-printer fs-2 me-2 text-primary"></i>
                                            {{ __('accounting.print_report') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Filter Section --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.orders.sales-forecast') }}" id="filterForm">
                                    <div class="row g-6 mb-6">
                                        {{-- Historical Date Range --}}
                                        <div class="col-md-12 col-lg-4">
                                            <label class="form-label required fw-semibold">{{ __('auth.historical_data_range') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="start_date" 
                                                    value="{{ $startDate }}" required
                                                    title="{{ __('auth.start_date') }}">
                                                <span class="input-group-text bg-light">{{ __('accounting.to') }}</span>
                                                <input type="date" class="form-control" name="end_date" 
                                                    value="{{ $endDate }}" required
                                                    title="{{ __('auth.end_date') }}">
                                            </div>
                                            <div class="form-text text-muted">{{ __('auth.historical_data_hint') }}</div>
                                        </div>
                                        
                                        {{-- Forecast Period --}}
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label required fw-semibold">{{ __('auth.forecast_period') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-tick fs-2"></i>
                                                </span>
                                                <select class="form-select" name="forecast_days">
                                                    <option value="7" {{ $forecastDays == 7 ? 'selected' : '' }}>{{ __('auth.next_7_days') }}</option>
                                                    <option value="14" {{ $forecastDays == 14 ? 'selected' : '' }}>{{ __('auth.next_14_days') }}</option>
                                                    <option value="30" {{ $forecastDays == 30 ? 'selected' : '' }}>{{ __('auth.next_30_days') }}</option>
                                                    <option value="60" {{ $forecastDays == 60 ? 'selected' : '' }}>{{ __('auth.next_60_days') }}</option>
                                                    <option value="90" {{ $forecastDays == 90 ? 'selected' : '' }}>{{ __('auth.next_90_days') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Quick Date Buttons --}}
                                        <div class="col-md-12 col-lg-5">
                                            <label class="form-label fw-semibold">{{ __('auth.quick_periods') }}</label>
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
                                    </div>
                                    
                                    {{-- Action Buttons --}}
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-calculator fs-2 me-2"></i>
                                                    {{ __('auth.generate_forecast') }}
                                                </button>
                                                <a href="{{ route('reports.orders.sales-forecast') }}" class="btn btn-light btn-active-light-primary">
                                                    <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                                    {{ __('accounting.clear_filters') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Forecast Summary Cards --}}
                @if($historicalData->count() > 0)
                <div class="row mb-6">
                    @php
                        $forecastSum = collect($forecast)->sum('forecast_sales');
                        $forecastAvg = collect($forecast)->avg('forecast_sales');
                        $forecastMax = collect($forecast)->max('forecast_sales');
                        $forecastMin = collect($forecast)->min('forecast_sales');
                        
                        $historicalAvg = $historicalData->avg('daily_sales');
                        $growthRatePercentage = $growthRate * 100;
                        $confidenceScore = min(95, max(60, 85 - abs($growthRatePercentage) * 2));
                    @endphp
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-chart-simple fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        ${{ number_format($forecastSum, 0) }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.total_forecast_sales') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-primary">
                                        {{ $forecastDays }} {{ __('auth.days') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-chart-line-up fs-2tx text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ number_format($growthRatePercentage, 1) }}%
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.daily_growth_rate') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-{{ $growthRatePercentage >= 0 ? 'success' : 'danger' }}">
                                        <i class="ki-duotone ki-arrow-{{ $growthRatePercentage >= 0 ? 'up' : 'down' }} fs-4 me-1"></i>
                                        {{ $growthRatePercentage >= 0 ? __('auth.increasing') : __('auth.decreasing') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-shield-tick fs-2tx text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ number_format($confidenceScore, 0) }}%
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.confidence_score') }}
                                </div>
                                <div class="mt-2">
                                    @if($confidenceScore >= 85)
                                    <span class="badge badge-light-success">{{ __('auth.high_confidence') }}</span>
                                    @elseif($confidenceScore >= 70)
                                    <span class="badge badge-light-warning">{{ __('auth.medium_confidence') }}</span>
                                    @else
                                    <span class="badge badge-light-danger">{{ __('auth.low_confidence') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-calculator fs-2tx text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        ${{ number_format($forecastAvg, 0) }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.daily_forecast_average') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-warning">
                                        ${{ number_format($forecastMin, 0) }} - ${{ number_format($forecastMax, 0) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Forecast vs Actual Chart --}}
                @if($historicalData->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.sales_forecast_chart') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="forecastChart" style="height: 450px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Forecast Details --}}
                @if($historicalData->count() > 0 && !empty($forecast))
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('auth.daily_forecast_details') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ $forecastDays }} {{ __('auth.days_forecast') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="forecastTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="min-w-100px">{{ __('accounting.date') }}</th>
                                                <th class="min-w-100px">{{ __('accounting.day') }}</th>
                                                <th class="min-w-120px">{{ __('auth.forecast_sales') }}</th>
                                                <th class="min-w-120px">{{ __('auth.forecast_orders') }}</th>
                                                <th class="min-w-120px">{{ __('auth.average_order_value') }}</th>
                                                <th class="min-w-120px">{{ __('auth.confidence_interval') }}</th>
                                                <th class="min-w-100px">{{ __('accounting.trend') }}</th>
                                                <th class="min-w-100px">{{ __('auth.seasonality') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {{-- In Forecast Details section --}}
                                            @foreach($forecast as $dateKey => $data)
                                            @php
                                                $date = \Carbon\Carbon::parse($dateKey);
                                                $dayName = $date->format('l');
                                                $isWeekend = in_array($dayName, ['Saturday', 'Sunday']);
                                                
                                                // Calculate confidence interval width
                                                $confidenceWidth = isset($data['confidence_low']) && isset($data['confidence_high']) 
                                                    ? ($data['confidence_high'] - $data['confidence_low']) / $data['forecast_sales'] * 100 
                                                    : 20;
                                                
                                                // Seasonality factor
                                                $seasonalityFactor = $data['seasonality_factor'] ?? 1.0;
                                                $seasonalityClass = $seasonalityFactor > 1.2 ? 'success' : ($seasonalityFactor > 0.8 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="fw-bold text-gray-800">{{ $date->format('M d, Y') }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $isWeekend ? 'danger' : 'primary' }}">
                                                        {{ $dayName }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <i class="ki-duotone ki-dollar-circle fs-2 text-success me-2"></i>
                                                        <div>
                                                            <span class="fw-bold text-success">${{ number_format($data['forecast_sales'], 0) }}</span>
                                                            @if(isset($data['confidence_low']) && isset($data['confidence_high']))
                                                            <div class="text-muted fs-8">
                                                                ${{ number_format($data['confidence_low'], 0) }} - ${{ number_format($data['confidence_high'], 0) }}
                                                            </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-gray-700">{{ number_format($data['forecast_orders'], 0) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-600">${{ number_format($data['average_order_value'], 0) }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 6px;">
                                                            <div class="progress-bar bg-{{ $confidenceWidth < 15 ? 'success' : ($confidenceWidth < 30 ? 'warning' : 'danger') }}" 
                                                                role="progressbar" 
                                                                style="width: {{ min($confidenceWidth, 100) }}%;" 
                                                                aria-valuenow="{{ $confidenceWidth }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                            {{ $data['confidence'] ?? 'medium' }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td>
                                                    @php
                                                        $trendValue = $data['trend'] ?? 0;
                                                        $trendClass = 'primary';
                                                        $trendClass = $trendValue > 0 ? 'success' : ($trendValue < 0 ? 'danger' : 'primary');
                                                    @endphp
                                                    <span class="badge badge-light-{{ $trendClass }}">
                                                        @if($trendValue != 0)
                                                            <i class="ki-duotone ki-arrow-{{ $trendValue > 0 ? 'up' : 'down' }} fs-4 me-1"></i>
                                                            {{ number_format(abs($trendValue), 1) }}%
                                                        @else
                                                            <i class="ki-duotone ki-minus fs-4 me-1"></i>
                                                            0%
                                                        @endif
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $seasonalityClass }}">
                                                        {{ number_format($seasonalityFactor, 2) }}x
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Seasonality Analysis --}}
                @if($seasonality->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie-3 fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.seasonality_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div id="seasonalityChart" style="height: 300px;"></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="table-responsive">
                                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                                <thead>
                                                    <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                        <th>{{ __('accounting.day') }}</th>
                                                        <th>{{ __('auth.average_sales') }}</th>
                                                        <th>{{ __('auth.order_count') }}</th>
                                                        <th>{{ __('auth.seasonality_factor') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $maxSales = $seasonality->max('average_sales');
                                                        $minSales = $seasonality->min('average_sales');
                                                    @endphp
                                                    @foreach($seasonality as $day)
                                                    @php
                                                        $dayNames = [
                                                            1 => 'Sunday',
                                                            2 => 'Monday',
                                                            3 => 'Tuesday',
                                                            4 => 'Wednesday',
                                                            5 => 'Thursday',
                                                            6 => 'Friday',
                                                            7 => 'Saturday'
                                                        ];
                                                        $isWeekend = in_array($dayNames[$day->day_of_week], ['Saturday', 'Sunday']);
                                                        $seasonalityFactor = $maxSales > 0 ? $day->average_sales / $maxSales : 0;
                                                        $salesPercentage = $maxSales > 0 ? ($day->average_sales / $maxSales) * 100 : 0;
                                                    @endphp
                                                    <tr>
                                                        <td>
                                                            <span class="badge badge-light-{{ $isWeekend ? 'danger' : 'primary' }}">
                                                                {{ $dayNames[$day->day_of_week] }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                                    <div class="progress-bar bg-{{ $isWeekend ? 'danger' : 'primary' }}" 
                                                                        role="progressbar" 
                                                                        style="width: {{ $salesPercentage }}%;" 
                                                                        aria-valuenow="{{ $salesPercentage }}" 
                                                                        aria-valuemin="0" 
                                                                        aria-valuemax="100">
                                                                    </div>
                                                                </div>
                                                                <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                                    ${{ number_format($day->average_sales, 0) }}
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-light-info">{{ $day->order_count }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-light-{{ $seasonalityFactor > 1.1 ? 'success' : ($seasonalityFactor > 0.9 ? 'warning' : 'danger') }}">
                                                                {{ number_format($seasonalityFactor, 2) }}x
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Historical Data Table --}}
                @if($historicalData->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('auth.historical_sales_data') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $historicalData->count() }} {{ __('auth.days') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="min-w-100px">{{ __('accounting.date') }}</th>
                                                <th class="min-w-100px">{{ __('accounting.day') }}</th>
                                                <th class="min-w-120px">{{ __('auth.daily_sales') }}</th>
                                                <th class="min-w-120px">{{ __('auth.order_count') }}</th>
                                                <th class="min-w-120px">{{ __('auth.average_order_value') }}</th>
                                                <th class="min-w-120px">{{ __('auth.trend') }}</th>
                                                <th class="min-w-100px">{{ __('auth.deviation') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($historicalData as $index => $day)
                                            @php
                                                $date = \Carbon\Carbon::parse($day->date);
                                                $dayName = $date->format('l');
                                                $isWeekend = in_array($dayName, ['Saturday', 'Sunday']);
                                                
                                                // Calculate trend
                                                $trend = $index > 0 ? $day->daily_sales - $historicalData[$index-1]->daily_sales : 0;
                                                $trendPercentage = $index > 0 && $historicalData[$index-1]->daily_sales > 0 
                                                    ? ($trend / $historicalData[$index-1]->daily_sales) * 100 
                                                    : 0;
                                                
                                                // Calculate deviation from average
                                                $deviation = $historicalAvg > 0 ? (($day->daily_sales - $historicalAvg) / $historicalAvg) * 100 : 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="fw-bold text-gray-800">{{ $date->format('M d, Y') }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $isWeekend ? 'danger' : 'primary' }}">
                                                        {{ $dayName }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">${{ number_format($day->daily_sales, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $day->order_count }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-600">${{ number_format($day->average_order_value, 2) }}</span>
                                                </td>
                                                <td>
                                                    @if($index > 0)
                                                        <div class="d-flex align-items-center">
                                                            @if($trend > 0)
                                                                <i class="ki-duotone ki-arrow-up-right fs-2 text-success me-1"></i>
                                                                <span class="text-success fw-bold">+{{ number_format($trendPercentage, 1) }}%</span>
                                                            @elseif($trend < 0)
                                                                <i class="ki-duotone ki-arrow-down-right fs-2 text-danger me-1"></i>
                                                                <span class="text-danger fw-bold">{{ number_format($trendPercentage, 1) }}%</span>
                                                            @else
                                                                <i class="ki-duotone ki-minus fs-2 text-gray-400 me-1"></i>
                                                                <span class="text-gray-600">{{ number_format($trendPercentage, 1) }}%</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $deviation >= 10 ? 'success' : ($deviation <= -10 ? 'danger' : 'primary') }}">
                                                        {{ number_format($deviation, 1) }}%
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center py-10">
                                    <i class="ki-duotone ki-chart-line fs-4tx text-gray-400 mb-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                    <p class="text-muted fs-6">{{ __('auth.no_historical_data_for_forecast') }}</p>
                                    <p class="text-muted fs-7 mb-4">{{ __('auth.need_at_least_14_days') }}</p>
                                    <a href="{{ route('reports.orders.sales-forecast') }}?start_date={{ \Carbon\Carbon::now()->subDays(90)->format('Y-m-d') }}&end_date={{ \Carbon\Carbon::now()->format('Y-m-d') }}" 
                                       class="btn btn-light-primary">
                                        <i class="ki-duotone ki-calendar-8 fs-2 me-2"></i>
                                        {{ __('auth.use_last_90_days') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($historicalData->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for charts
        const historicalDates = @json($historicalData->pluck('date'));
        const historicalSales = @json($historicalData->pluck('daily_sales'));
        const historicalOrders = @json($historicalData->pluck('order_count'));
        
        const forecastData = @json($forecast);
        const forecastDates = Object.keys(forecastData);
        const forecastSales = Object.values(forecastData).map(d => d.forecast_sales);
        const forecastConfidenceLow = Object.values(forecastData).map(d => d.confidence_low || d.forecast_sales * 0.8);
        const forecastConfidenceHigh = Object.values(forecastData).map(d => d.confidence_high || d.forecast_sales * 1.2);

        // Forecast Chart
        const forecastChart = new ApexCharts(document.querySelector("#forecastChart"), {
            series: [
                {
                    name: 'Historical Sales',
                    type: 'line',
                    data: historicalSales.map((sales, index) => ({
                        x: historicalDates[index],
                        y: sales
                    }))
                },
                {
                    name: 'Forecast Sales',
                    type: 'line',
                    data: forecastSales.map((sales, index) => ({
                        x: forecastDates[index],
                        y: sales
                    }))
                },
                {
                    name: 'Confidence Interval',
                    type: 'area',
                    data: forecastConfidenceHigh.map((high, index) => ({
                        x: forecastDates[index],
                        y: high
                    }))
                }
            ],
            chart: {
                height: 450,
                type: 'line',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                width: [3, 3, 0],
                curve: 'smooth',
                dashArray: [0, 5, 0]
            },
            colors: ['#009EF7', '#50CD89', 'rgba(80, 205, 137, 0.1)'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.3,
                    opacityTo: 0.1,
                    stops: [0, 100]
                }
            },
            markers: {
                size: [4, 4, 0],
                hover: {
                    size: 6
                }
            },
            xaxis: {
                type: 'datetime',
                labels: {
                    style: {
                        colors: '#6B7280',
                        fontSize: '12px'
                    },
                    datetimeFormatter: {
                        year: 'yyyy',
                        month: "MMM 'yy",
                        day: 'dd MMM',
                        hour: 'HH:mm'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Sales ($)',
                    style: {
                        color: '#6B7280',
                        fontSize: '12px'
                    }
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    },
                    style: {
                        colors: '#6B7280',
                        fontSize: '12px'
                    }
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                x: {
                    format: 'dd MMM yyyy'
                },
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                fontSize: '14px',
                fontWeight: 600
            },
            grid: {
                borderColor: '#E5E7EB',
                strokeDashArray: 4,
                yaxis: {
                    lines: {
                        show: true
                    }
                }
            }
        });
        forecastChart.render();

        // Seasonality Chart
        const seasonalityData = @json($seasonality);
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const seasonalityValues = [];
        
        for (let i = 1; i <= 7; i++) {
            const dayData = seasonalityData.find(d => d.day_of_week === i);
            seasonalityValues.push(dayData ? dayData.average_sales : 0);
        }

        const seasonalityChart = new ApexCharts(document.querySelector("#seasonalityChart"), {
            series: [{
                data: seasonalityValues
            }],
            chart: {
                type: 'bar',
                height: 300
            },
            colors: ['#009EF7', '#50CD89', '#7239EA', '#FFA800', '#F64E60', '#3E97FF', '#A1A5B7'],
            plotOptions: {
                bar: {
                    distributed: true,
                    borderRadius: 4,
                    columnWidth: '70%',
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: dayNames
            },
            yaxis: {
                title: {
                    text: 'Average Sales ($)',
                    style: {
                        color: '#6B7280',
                        fontSize: '12px'
                    }
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            }
        });
        seasonalityChart.render();
    });

    // Quick date range buttons
    function setHistoricalRange(range) {
        const today = new Date();
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        
        switch(range) {
            case 'last_30_days':
                const last30Days = new Date(today);
                last30Days.setDate(last30Days.getDate() - 30);
                startDateInput.value = last30Days.toISOString().split('T')[0];
                endDateInput.value = today.toISOString().split('T')[0];
                break;
            case 'last_90_days':
                const last90Days = new Date(today);
                last90Days.setDate(last90Days.getDate() - 90);
                startDateInput.value = last90Days.toISOString().split('T')[0];
                endDateInput.value = today.toISOString().split('T')[0];
                break;
            case 'last_180_days':
                const last180Days = new Date(today);
                last180Days.setDate(last180Days.getDate() - 180);
                startDateInput.value = last180Days.toISOString().split('T')[0];
                endDateInput.value = today.toISOString().split('T')[0];
                break;
            case 'this_year':
                const startOfYear = new Date(today.getFullYear(), 0, 1);
                startDateInput.value = startOfYear.toISOString().split('T')[0];
                endDateInput.value = today.toISOString().split('T')[0];
                break;
        }
        
        // Submit form
        document.getElementById('filterForm').submit();
    }

    // Form validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const startDate = new Date(document.querySelector('[name="start_date"]').value);
        const endDate = new Date(document.querySelector('[name="end_date"]').value);
        const daysDiff = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
        
        if (startDate > endDate) {
            e.preventDefault();
            alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
            return false;
        }
        
        if (daysDiff < 7) {
            if (!confirm('{{ __("auth.warning_less_than_7_days_data") }}')) {
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    });

    // Export forecast data
    function exportForecastData() {
        const historicalData = @json($historicalData);
        const forecastData = @json($forecast);
        
        // Prepare data for export
        const exportData = [];
        
        // Add headers
        exportData.push([
            'Date', 
            'Day', 
            'Historical Sales', 
            'Order Count', 
            'Average Order Value',
            'Forecast Sales',
            'Forecast Orders',
            'Average Order Value (Forecast)',
            'Confidence Low',
            'Confidence High',
            'Seasonality Factor'
        ]);
        
        // Combine historical and forecast data
        const allDates = new Set([
            ...historicalData.map(d => d.date),
            ...Object.keys(forecastData)
        ]);
        
        const sortedDates = Array.from(allDates).sort();
        
        sortedDates.forEach(date => {
            const historical = historicalData.find(d => d.date === date);
            const forecast = forecastData[date];
            
            const dayName = new Date(date).toLocaleDateString('en-US', { weekday: 'long' });
            
            exportData.push([
                date,
                dayName,
                historical ? historical.daily_sales : '',
                historical ? historical.order_count : '',
                historical ? historical.average_order_value : '',
                forecast ? forecast.forecast_sales : '',
                forecast ? forecast.forecast_orders : '',
                forecast ? forecast.average_order_value : '',
                forecast ? (forecast.confidence_low || forecast.forecast_sales * 0.8) : '',
                forecast ? (forecast.confidence_high || forecast.forecast_sales * 1.2) : '',
                forecast ? (forecast.seasonality_factor || 1.0) : ''
            ]);
        });
        
        // Create workbook
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.aoa_to_sheet(exportData);
        
        // Add column widths
        const wscols = [
            { wch: 15 }, // Date
            { wch: 12 }, // Day
            { wch: 15 }, // Historical Sales
            { wch: 12 }, // Order Count
            { wch: 20 }, // Average Order Value
            { wch: 15 }, // Forecast Sales
            { wch: 15 }, // Forecast Orders
            { wch: 20 }, // Avg Order Value (Forecast)
            { wch: 15 }, // Confidence Low
            { wch: 15 }, // Confidence High
            { wch: 18 }  // Seasonality Factor
        ];
        ws['!cols'] = wscols;
        
        // Add to workbook
        XLSX.utils.book_append_sheet(wb, ws, 'Sales Forecast');
        
        // Export
        const filename = `sales_forecast_${new Date().toISOString().split('T')[0]}`;
        XLSX.writeFile(wb, `${filename}.xlsx`);
    }

    // Print forecast
    function printForecast() {
        window.print();
    }

    // Auto-refresh chart on window resize
    window.addEventListener('resize', function() {
        if (typeof forecastChart !== 'undefined') {
            forecastChart.updateOptions({
                chart: {
                    height: 450
                }
            });
        }
    });
</script>
@endif
@endpush

{{-- Print Styles --}}
@push('styles')
<style>
    @media print {
        .app-toolbar,
        .card-header .btn,
        #filterForm,
        .dropdown,
        .no-print {
            display: none !important;
        }
        
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        
        .card-body {
            padding: 0 !important;
        }
        
        #forecastChart,
        #seasonalityChart {
            height: 300px !important;
        }
        
        .table-responsive {
            overflow: visible !important;
        }
        
        .badge {
            border: 1px solid #E5E7EB !important;
        }
    }
    
    .apexcharts-tooltip {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border-radius: 8px;
        border: 1px solid #E5E7EB;
    }
    
    .card-flush {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card-flush:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    @media (max-width: 768px) {
        #forecastChart {
            height: 300px !important;
        }
        
        .table-responsive {
            font-size: 0.875rem;
        }
    }
</style>
@endpush

@endsection