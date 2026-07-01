
{{-- resources/views/reports/orders/time-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.time_analysis'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
            {{-- Toolbar Section --}}
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                    <div class="page-title d-flex flex-column">
                        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                            {{ __('auth.time_analysis') }}
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
                            <li class="breadcrumb-item text-muted">{{ __('auth.time_analysis') }}</li>
                        </ul>
                    </div>
                    <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                        @if($timeAnalysis->count() > 0)
                        <div class="dropdown w-100 w-sm-auto">
                            <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                    onclick="exportCurrentPage({tableId: 'timeAnalysisTable', filename: 'time_analysis_{{ date('Y_m_d') }}', sheetName: 'Time Analysis'})">
                                        <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                        {{ __('accounting.export_to_excel') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                    onclick="exportCurrentPage({tableId: 'timeAnalysisTable', filename: 'time_analysis_{{ date('Y_m_d') }}', format: 'csv'})">
                                        <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                        {{ __('accounting.export_to_csv') }}
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
                            <form method="GET" action="{{ route('reports.orders.time-analysis') }}" id="filterForm">
                                <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-6">
                                    {{-- Date Range --}}
                                    <div class="flex-grow-1">
                                        <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                        <div class="d-flex flex-column flex-sm-row gap-2">
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="start_date" 
                                                    value="{{ $startDate }}" required
                                                    title="{{ __('auth.start_date') }}">
                                            </div>
                                            <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('accounting.to') }}</span>
                                            <span class="d-flex d-sm-none text-gray-500 text-center">{{ __('accounting.to') }}</span>
                                            <div class="input-group w-100">
                                                <span class="input-group-text bg-light">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="end_date" 
                                                    value="{{ $endDate }}" required
                                                    title="{{ __('auth.end_date') }}">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Location --}}
                                    <div class="flex-grow-1">
                                        <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                        <div class="input-group w-100">
                                            <span class="input-group-text">
                                                <i class="ki-duotone ki-location fs-2"></i>
                                            </span>
                                            <select class="form-select" name="location_id">
                                                <option value="">{{ __('auth.all_locations') }}</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" 
                                                            {{ $locationId == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    
                                    {{-- Department --}}
                                    <div class="flex-grow-1">
                                        <label class="form-label fw-semibold">{{ __('auth.department') }}</label>
                                        <div class="input-group w-100">
                                            <span class="input-group-text">
                                                <i class="ki-duotone ki-building fs-2"></i>
                                            </span>
                                            <select class="form-select" name="department_id">
                                                <option value="">{{ __('auth.all_departments') }}</option>
                                                @foreach($departments as $department)
                                                    <option value="{{ $department->id }}" 
                                                            {{ $departmentId == $department->id ? 'selected' : '' }}>
                                                        {{ $department->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    
                                    {{-- Order Type --}}
                                    <div class="flex-grow-1">
                                        <label class="form-label fw-semibold">{{ __('auth.order_type') }}</label>
                                        <div class="input-group w-100">
                                            <span class="input-group-text">
                                                <i class="ki-duotone ki-bag fs-2"></i>
                                            </span>
                                            <select class="form-select" name="order_type">
                                                <option value="all">{{ __('auth.all_types') }}</option>
                                                <option value="sale" {{ $orderType == 'sale' ? 'selected' : '' }}>{{ __('auth.sale') }}</option>
                                                <option value="return" {{ $orderType == 'return' ? 'selected' : '' }}>{{ __('auth.return') }}</option>
                                                <option value="quote" {{ $orderType == 'quote' ? 'selected' : '' }}>{{ __('auth.quote') }}</option>
                                                <option value="layby" {{ $orderType == 'layby' ? 'selected' : '' }}>{{ __('auth.layby') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    {{-- Group By --}}
                                    <div class="flex-grow-1">
                                        <label class="form-label fw-semibold">{{ __('auth.group_by') }}</label>
                                        <div class="input-group w-100">
                                            <span class="input-group-text">
                                                <i class="ki-duotone ki-chart-line fs-2"></i>
                                            </span>
                                            <select class="form-select" name="group_by" id="groupBySelect">
                                                <option value="daily" {{ $groupBy == 'daily' ? 'selected' : '' }}>{{ __('auth.daily') }}</option>
                                                <option value="hourly" {{ $groupBy == 'hourly' ? 'selected' : '' }}>{{ __('auth.hourly') }}</option>
                                                <option value="weekly" {{ $groupBy == 'weekly' ? 'selected' : '' }}>{{ __('auth.weekly') }}</option>
                                                <option value="monthly" {{ $groupBy == 'monthly' ? 'selected' : '' }}>{{ __('auth.monthly') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Action Buttons --}}
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn btn-primary" id="applyFilters">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                        <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                        <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                    </button>
                                    <a href="{{ route('reports.orders.time-analysis') }}" class="btn btn-light btn-active-light-primary">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i>
                                        <span class="d-none d-sm-inline">{{ __('accounting.clear_filters') }}</span>
                                        <span class="d-inline d-sm-none">{{ __('accounting.clear') }}</span>
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

                {{-- Summary Statistics --}}
                @if($timeAnalysis->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.summary_statistics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $totalOrders = $timeAnalysis->sum('order_count');
                                        $totalSales = $timeAnalysis->sum('total_sales');
                                        $totalTax = $timeAnalysis->sum('total_tax');
                                        $totalDiscount = $timeAnalysis->sum('total_discount');
                                        $avgSale = $timeAnalysis->avg('average_sale') ?? 0;
                                        $periods = $timeAnalysis->count();
                                    @endphp
                                    
                                    @foreach([
                                        ['key' => 'total_periods', 'color' => 'primary', 'icon' => 'ki-calendar-8', 'label' => 'total_periods', 'value' => $periods],
                                        ['key' => 'total_orders', 'color' => 'info', 'icon' => 'ki-bag', 'label' => 'total_orders', 'value' => number_format($totalOrders)],
                                        ['key' => 'total_sales', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'total_sales', 'value' => '$' . number_format($totalSales, 2)],
                                        ['key' => 'total_tax', 'color' => 'warning', 'icon' => 'ki-receipt-tax', 'label' => 'total_tax', 'value' => '$' . number_format($totalTax, 2)],
                                        ['key' => 'total_discount', 'color' => 'danger', 'icon' => 'ki-percentage', 'label' => 'total_discount', 'value' => '$' . number_format($totalDiscount, 2)],
                                        ['key' => 'average_sale', 'color' => 'secondary', 'icon' => 'ki-calculator', 'label' => 'average_sale', 'value' => '$' . number_format($avgSale, 2)]
                                    ] as $stat)
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}">
                                                        @for($i = 1; $i <= 2; $i++)
                                                        <span class="path{{ $i }}"></span>
                                                        @endfor
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $stat['value'] }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.' . $stat['label']) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Growth Metrics --}}
                @if(!empty($growthMetrics) && $timeAnalysis->count() > 1)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line-up fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.growth_metrics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $trendColors = [
                                            'upward' => 'success',
                                            'downward' => 'danger',
                                            'stable' => 'warning'
                                        ];
                                        $trendIcons = [
                                            'upward' => 'ki-arrow-up-right',
                                            'downward' => 'ki-arrow-down-right',
                                            'stable' => 'ki-minus'
                                        ];
                                    @endphp
                                    
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-{{ $trendColors[$growthMetrics['trend'] ?? 'stable'] }} border border-{{ $trendColors[$growthMetrics['trend'] ?? 'stable'] }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone {{ $trendIcons[$growthMetrics['trend'] ?? 'stable'] }} fs-2tx text-{{ $trendColors[$growthMetrics['trend'] ?? 'stable'] }}">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ number_format($growthMetrics['daily_growth'] ?? 0, 1) }}%
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.daily_growth') }}
                                                </div>
                                                <div class="mt-2">
                                                    <span class="badge badge-light-{{ $trendColors[$growthMetrics['trend'] ?? 'stable'] }}">
                                                        {{ ucfirst($growthMetrics['trend'] ?? 'stable') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-chart-line-up fs-2tx text-info">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ number_format($growthMetrics['weekly_growth'] ?? 0, 1) }}%
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.weekly_growth') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-dollar-circle fs-2tx text-secondary">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        ${{ number_format($growthMetrics['current_average'] ?? 0, 2) }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.current_daily_average') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Time Analysis Chart --}}
                @if($timeAnalysis->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">
                                        @switch($groupBy)
                                            @case('hourly')
                                                {{ __('auth.hourly_sales_analysis') }}
                                                @break
                                            @case('weekly')
                                                {{ __('auth.weekly_sales_analysis') }}
                                                @break
                                            @case('monthly')
                                                {{ __('auth.monthly_sales_analysis') }}
                                                @break
                                            @default
                                                {{ __('auth.daily_sales_analysis') }}
                                        @endswitch
                                    </h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="timeAnalysisChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Peak Analysis --}}
                @if(!empty($peakAnalysis) && $timeAnalysis->count() > 0)
                <div class="row mb-6">
                    @if($groupBy == 'hourly' || $groupBy == 'daily')
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-clock fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.peak_hours') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-success">
                                                <th class="ps-4">{{ __('accounting.time') }}</th>
                                                <th>{{ __('auth.order_count') }}</th>
                                                <th>{{ __('accounting.total_sales') }}</th>
                                                <th>{{ __('accounting.average_amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($peakAnalysis['peak_hours'] ?? [] as $peak)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-gray-800">
                                                        @if($groupBy == 'hourly')
                                                            @php
                                                                $hour = intval($peak->hour);
                                                                $period = $hour >= 12 ? 'PM' : 'AM';
                                                                $displayHour = $hour % 12;
                                                                if ($displayHour == 0) $displayHour = 12;
                                                            @endphp
                                                            {{ $displayHour }}:00 {{ $period }}
                                                        @else
                                                            {{ \Carbon\Carbon::parse($peak->date)->format('M d, Y') }}
                                                        @endif
                                                    </span>
                                                </td>
                                                <td><span class="badge badge-light-primary">{{ $peak->order_count }}</span></td>
                                                <td><span class="fw-bold text-success">${{ number_format($peak->total_sales ?? $peak->hourly_total, 2) }}</span></td>
                                                <td><span class="text-gray-600">${{ number_format($peak->average_order ?? $peak->hourly_average, 2) }}</span></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <div class="col-lg-{{ $groupBy == 'hourly' || $groupBy == 'daily' ? '6' : '12' }}">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.peak_days') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-warning">
                                                <th class="ps-4">{{ __('accounting.date') }}</th>
                                                <th>{{ __('accounting.day') }}</th>
                                                <th>{{ __('auth.order_count') }}</th>
                                                <th>{{ __('accounting.total_sales') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($peakAnalysis['peak_days'] ?? [] as $peak)
                                            @php
                                                $date = \Carbon\Carbon::parse($peak->date);
                                                $dayName = $date->format('l');
                                                $isWeekend = in_array($dayName, ['Saturday', 'Sunday']);
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-gray-800">{{ $date->format('M d, Y') }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $isWeekend ? 'danger' : 'primary' }}">
                                                        {{ $dayName }}
                                                    </span>
                                                </td>
                                                <td><span class="badge badge-light-primary">{{ $peak->order_count }}</span></td>
                                                <td><span class="fw-bold text-success">${{ number_format($peak->total_sales, 2) }}</span></td>
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

                {{-- Time Analysis Table --}}
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
                                        <h3 class="fw-bold m-0">
                                            @switch($groupBy)
                                                @case('hourly')
                                                    {{ __('auth.hourly_sales_report') }}
                                                    @break
                                                @case('weekly')
                                                    {{ __('auth.weekly_sales_report') }}
                                                    @break
                                                @case('monthly')
                                                    {{ __('auth.monthly_sales_report') }}
                                                    @break
                                                @default
                                                    {{ __('auth.daily_sales_report') }}
                                            @endswitch
                                        </h3>
                                    </div>
                                    @if($timeAnalysis->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $timeAnalysis->count() }} 
                                        @switch($groupBy)
                                            @case('hourly')
                                                {{ __('accounting.hours') }}
                                                @break
                                            @case('weekly')
                                                {{ __('accounting.weeks') }}
                                                @break
                                            @case('monthly')
                                                {{ __('accounting.months') }}
                                                @break
                                            @default
                                                {{ __('accounting.days') }}
                                        @endswitch
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($timeAnalysis->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="timeAnalysisTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    @switch($groupBy)
                                                        @case('hourly')
                                                            <th class="min-w-100px ps-4">{{ __('accounting.hour') }}</th>
                                                            <th class="min-w-100px">{{ __('accounting.time_period') }}</th>
                                                            @break
                                                        @case('weekly')
                                                            <th class="min-w-100px ps-4">{{ __('accounting.week') }}</th>
                                                            <th class="min-w-150px">{{ __('accounting.date_range') }}</th>
                                                            @break
                                                        @case('monthly')
                                                            <th class="min-w-100px ps-4">{{ __('accounting.month') }}</th>
                                                            <th class="min-w-100px">{{ __('accounting.year') }}</th>
                                                            @break
                                                        @default
                                                            <th class="min-w-100px ps-4">{{ __('accounting.date') }}</th>
                                                            <th class="min-w-100px">{{ __('accounting.day') }}</th>
                                                    @endswitch
                                                    <th class="min-w-100px">{{ __('auth.order_count') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.total_sales') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.average_amount') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.total_tax') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.total_discount') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.trend') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($timeAnalysis as $index => $period)
                                                @php
                                                    $prevPeriod = $loop->index > 0 ? $timeAnalysis[$loop->index - 1] : null;
                                                    $trend = $prevPeriod ? $period->total_sales - $prevPeriod->total_sales : 0;
                                                    $trendPercent = $prevPeriod && $prevPeriod->total_sales > 0 ? ($trend / $prevPeriod->total_sales) * 100 : 0;
                                                    
                                                    // Format based on group_by
                                                    if ($groupBy == 'hourly') {
                                                        $hour = intval($period->time_period);
                                                        $periodName = $hour . ':00';
                                                        $displayTime = $hour >= 12 ? ($hour % 12 == 0 ? 12 : $hour % 12) . ':00 PM' : ($hour == 0 ? '12:00 AM' : $hour . ':00 AM');
                                                        $dayPart = $hour < 12 ? 'Morning' : ($hour < 17 ? 'Afternoon' : ($hour < 21 ? 'Evening' : 'Night'));
                                                    } elseif ($groupBy == 'weekly') {
                                                        $weekStart = \Carbon\Carbon::parse($period->week_start);
                                                        $weekEnd = \Carbon\Carbon::parse($period->week_end);
                                                        $periodName = 'Week ' . $period->week_number;
                                                        $dateRange = $weekStart->format('M d') . ' - ' . $weekEnd->format('M d');
                                                    } elseif ($groupBy == 'monthly') {
                                                        $monthName = \Carbon\Carbon::createFromDate($period->year, $period->month_number, 1)->format('F');
                                                        $periodName = $monthName;
                                                    } else {
                                                        $date = \Carbon\Carbon::parse($period->date);
                                                        $dayName = $date->format('l');
                                                        $isWeekend = in_array($dayName, ['Saturday', 'Sunday']);
                                                        $periodName = $date->format('M d, Y');
                                                    }
                                                @endphp
                                                <tr>
                                                    @switch($groupBy)
                                                        @case('hourly')
                                                            <td class="ps-4 fw-semibold">{{ $periodName }}</td>
                                                            <td>
                                                                <span class="badge badge-light-primary">{{ $displayTime }}</span>
                                                                <div class="mt-1">
                                                                    <span class="badge badge-light-info badge-sm">{{ $dayPart }}</span>
                                                                </div>
                                                            </td>
                                                            @break
                                                        @case('weekly')
                                                            <td class="ps-4 fw-semibold">{{ $periodName }}</td>
                                                            <td>
                                                                <span class="text-gray-700">{{ $dateRange }}</span>
                                                                <div class="mt-1">
                                                                    <span class="badge badge-light-primary badge-sm">{{ $period->year }}</span>
                                                                </div>
                                                            </td>
                                                            @break
                                                        @case('monthly')
                                                            <td class="ps-4 fw-semibold">{{ $periodName }}</td>
                                                            <td>
                                                                <span class="badge badge-light-primary">{{ $period->year }}</span>
                                                            </td>
                                                            @break
                                                        @default
                                                            <td class="ps-4 fw-semibold">{{ $periodName }}</td>
                                                            <td>
                                                                <span class="badge badge-light-{{ $isWeekend ? 'danger' : 'primary' }}">
                                                                    {{ $dayName }}
                                                                </span>
                                                            </td>
                                                    @endswitch
                                                    <td>
                                                        <span class="badge badge-light-primary">{{ $period->order_count }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($period->total_sales, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">${{ number_format($period->average_sale, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-info">${{ number_format($period->total_tax, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-warning">${{ number_format($period->total_discount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        @if($prevPeriod)
                                                            <div class="d-flex align-items-center">
                                                                @if($trend > 0)
                                                                    <i class="ki-duotone ki-arrow-up-right fs-2 text-success me-1"></i>
                                                                    <span class="text-success fw-bold">+{{ number_format($trendPercent, 1) }}%</span>
                                                                @elseif($trend < 0)
                                                                    <i class="ki-duotone ki-arrow-down-right fs-2 text-danger me-1"></i>
                                                                    <span class="text-danger fw-bold">{{ number_format($trendPercent, 1) }}%</span>
                                                                @else
                                                                    <i class="ki-duotone ki-minus fs-2 text-gray-400 me-1"></i>
                                                                    <span class="text-gray-600">{{ number_format($trendPercent, 1) }}%</span>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="card-body">
                                    <div class="text-center py-10">
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('auth.no_orders_found_for_period') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'location_id', 'department_id', 'order_type', 'group_by']))
                                        <a href="{{ route('reports.orders.time-analysis') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('accounting.clear_filters_view_all') }}
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($timeAnalysis->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Time Analysis Chart
        const timeData = @json($timeAnalysis);
        let categories = [];
        let salesData = [];
        let orderData = [];
        
        // Prepare data based on group_by
        @switch($groupBy)
            @case('hourly')
                timeData.forEach(item => {
                    const hour = parseInt(item.time_period);
                    const period = hour >= 12 ? 'PM' : 'AM';
                    const displayHour = hour % 12 || 12;
                    categories.push(`${displayHour} ${period}`);
                    salesData.push(parseFloat(item.total_sales));
                    orderData.push(parseFloat(item.order_count));
                });
                @break
            
            @case('weekly')
                timeData.forEach(item => {
                    const weekStart = new Date(item.week_start).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    const weekEnd = new Date(item.week_end).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    categories.push(`W${item.week_number} (${weekStart}-${weekEnd})`);
                    salesData.push(parseFloat(item.total_sales));
                    orderData.push(parseFloat(item.order_count));
                });
                @break
            
            @case('monthly')
                timeData.forEach(item => {
                    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    categories.push(`${monthNames[item.month_number - 1]} ${item.year}`);
                    salesData.push(parseFloat(item.total_sales));
                    orderData.push(parseFloat(item.order_count));
                });
                @break
            
            @default

            timeData.forEach(item => {
                                const date = new Date(item.date);
                                categories.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                                salesData.push(parseFloat(item.total_sales));
                                orderData.push(parseFloat(item.order_count));
                            });
                            @endswitch
                    
                    // Create chart options
                    const options = {
                        series: [{
                            name: '{{ __("accounting.total_sales") }}',
                            type: 'line',
                            data: salesData
                        }, {
                            name: '{{ __("auth.order_count") }}',
                            type: 'column',
                            data: orderData
                        }],
                        chart: {
                            height: 400,
                            type: 'line',
                            stacked: false,
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
                            width: [3, 1],
                            curve: 'smooth'
                        },
                        colors: ['#50CD89', '#009EF7'],
                        fill: {
                            opacity: [0.85, 0.25]
                        },
                        markers: {
                            size: 4
                        },
                        xaxis: {
                            categories: categories,
                            labels: {
                                style: {
                                    colors: '#6B7280',
                                    fontSize: '12px'
                                },
                                rotate: -45,
                                rotateAlways: true
                            }
                        },
                        yaxis: [{
                            title: {
                                text: '{{ __("accounting.total_sales") }} ($)',
                                style: {
                                    color: '#50CD89',
                                    fontSize: '12px'
                                }
                            },
                            labels: {
                                formatter: function(val) {
                                    return '$' + val.toFixed(2);
                                },
                                style: {
                                    colors: '#6B7280',
                                    fontSize: '12px'
                                }
                            }
                        }, {
                            opposite: true,
                            title: {
                                text: '{{ __("auth.order_count") }}',
                                style: {
                                    color: '#009EF7',
                                    fontSize: '12px'
                                }
                            },
                            labels: {
                                style: {
                                    colors: '#6B7280',
                                    fontSize: '12px'
                                }
                            }
                        }],
                        tooltip: {
                            shared: true,
                            intersect: false,
                            y: {
                                formatter: function (value, { seriesIndex }) {
                                    if (seriesIndex === 0) {
                                        return '$' + value.toFixed(2);
                                    }
                                    return value;
                                }
                            }
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'left',
                            fontSize: '14px',
                            fontWeight: 600,
                            markers: {
                                width: 10,
                                height: 10,
                                radius: 6
                            }
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
                    };

                    // Render chart
                    const chart = new ApexCharts(document.querySelector("#timeAnalysisChart"), options);
                    chart.render();

                    // Update chart on window resize
                    window.addEventListener('resize', function() {
                        chart.updateOptions({
                            chart: {
                                height: 400
                            }
                        });
                    });
                });

                // Group by selector change handler
                document.getElementById('groupBySelect').addEventListener('change', function() {
                    const groupBy = this.value;
                    const today = new Date().toISOString().split('T')[0];
                    const startDateInput = document.querySelector('input[name="start_date"]');
                    const endDateInput = document.querySelector('input[name="end_date"]');
                    
                    // Adjust default dates based on group selection
                    switch(groupBy) {
                        case 'hourly':
                            if (!startDateInput.value) {
                                startDateInput.value = today;
                                endDateInput.value = today;
                            }
                            break;
                        case 'weekly':
                            if (!startDateInput.value) {
                                const startOfWeek = new Date();
                                startOfWeek.setDate(startOfWeek.getDate() - startOfWeek.getDay());
                                startDateInput.value = startOfWeek.toISOString().split('T')[0];
                                endDateInput.value = today;
                            }
                            break;
                        case 'monthly':
                            if (!startDateInput.value) {
                                const startOfMonth = new Date();
                                startOfMonth.setDate(1);
                                startDateInput.value = startOfMonth.toISOString().split('T')[0];
                                endDateInput.value = today;
                            }
                            break;
                    }
                });

                // Date validation
                document.querySelector('form').addEventListener('submit', function(e) {
                    const startDate = new Date(document.querySelector('input[name="start_date"]').value);
                    const endDate = new Date(document.querySelector('input[name="end_date"]').value);
                    const groupBy = document.getElementById('groupBySelect').value;
                    
                    // Validate date range
                    if (startDate > endDate) {
                        e.preventDefault();
                        toastr.error('{{ __("auth.start_date_cannot_be_greater_than_end_date") }}');
                        return;
                    }
                    
                    // Validate range based on group by
                    const daysDiff = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
                    
                    if (groupBy === 'hourly' && daysDiff > 7) {
                        if (!confirm('{{ __("auth.hourly_analysis_for_more_than_7_days_may_be_slow_continue") }}')) {
                            e.preventDefault();
                            return;
                        }
                    }
                    
                    if (groupBy === 'daily' && daysDiff > 365) {
                        if (!confirm('{{ __("auth.daily_analysis_for_more_than_1_year_may_be_slow_continue") }}')) {
                            e.preventDefault();
                            return;
                        }
                    }
                });

                // Export functionality
                function exportCurrentPage(options = {}) {
                    const {
                        tableId = 'timeAnalysisTable',
                        filename = 'time_analysis_' + new Date().toISOString().split('T')[0],
                        format = 'xlsx',
                        sheetName = 'Time Analysis'
                    } = options;
                    
                    const table = document.getElementById(tableId);
                    if (!table) return;
                    
                    // Get table data
                    const data = [];
                    const headers = [];
                    
                    // Get headers
                    table.querySelectorAll('thead th').forEach(th => {
                        headers.push(th.textContent.trim());
                    });
                    
                    // Get rows
                    table.querySelectorAll('tbody tr').forEach(row => {
                        const rowData = [];
                        row.querySelectorAll('td').forEach((td, index) => {
                            // Remove icons and keep only text
                            const tdClone = td.cloneNode(true);
                            tdClone.querySelectorAll('.ki-duotone, .badge, i').forEach(el => el.remove());
                            rowData.push(tdClone.textContent.trim());
                        });
                        data.push(rowData);
                    });
                    
                    // Create workbook
                    const wb = XLSX.utils.book_new();
                    const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
                    
                    // Add column widths
                    const wscols = headers.map(() => ({ wch: 20 }));
                    ws['!cols'] = wscols;
                    
                    // Add to workbook
                    XLSX.utils.book_append_sheet(wb, ws, sheetName);
                    
                    // Export
                    XLSX.writeFile(wb, `${filename}.${format === 'csv' ? 'csv' : 'xlsx'}`);
                }

            </script>
            @endif

@endpush

{{-- Add CSS --}}
@push('styles')
<style>
    .apexcharts-tooltip {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border-radius: 8px;
        border: 1px solid #E5E7EB;
    }
    
    .apexcharts-tooltip-title {
        font-weight: 600;
        background-color: #F9FAFB;
        border-bottom: 1px solid #E5E7EB;
    }
    
    .card-flush {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    
    .card-flush:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .table > :not(caption) > * > * {
        padding: 1rem 0.75rem;
    }
    
    .badge-light-primary {
        background-color: #E1F0FF;
        color: #3699FF;
    }
    
    .badge-light-success {
        background-color: #E8FFF3;
        color: #1BC5BD;
    }
    
    .badge-light-info {
        background-color: #E1F0FF;
        color: #3699FF;
    }
    
    .badge-light-warning {
        background-color: #FFF4DE;
        color: #FFA800;
    }
    
    .badge-light-danger {
        background-color: #FFE2E5;
        color: #F64E60;
    }
    
    .input-group-text {
        background-color: #F9FAFB;
        border-color: #E5E7EB;
    }
    
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .card-body {
            padding: 1rem;
        }
        
        #timeAnalysisChart {
            height: 300px !important;
        }
    }
</style>
@endpush

@endsection