{{-- resources/views/reports/orders/time-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.time_analysis'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                
                {{-- ============================================================ --}}
                {{-- TOOLBAR SECTION --}}
                {{-- ============================================================ --}}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.time_analysis') }}</li>
                            </ul>
                        </div>
                        @if($timeAnalysis->count() > 0)
                        <div class="dropdown">
                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                       onclick="exportTableToExcel('timeAnalysisTable', 'time_analysis')">
                                        <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                        {{ __('accounting.export_to_excel') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                       onclick="exportTableToCSV('timeAnalysisTable', 'time_analysis')">
                                        <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                        {{ __('accounting.export_to_csv') }}
                                    </a>
                                </li>
                            </ul>
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
                        <form method="GET" action="{{ route('reports.orders.time-analysis') }}" id="filterForm">
                            <div class="row g-3">
                                {{-- Date Range --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('accounting.start_date') }}</label>
                                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('accounting.end_date') }}</label>
                                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                </div>
                                
                                {{-- Location --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                    <select class="form-select" name="location_id" data-control="select2">
                                        <option value="">{{ __('auth.all_locations') }}</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- Department --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('auth.department') }}</label>
                                    <select class="form-select" name="department_id" data-control="select2">
                                        <option value="">{{ __('auth.all_departments') }}</option>
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}" {{ $departmentId == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- Order Type --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('auth.order_type') }}</label>
                                    <select class="form-select" name="order_type">
                                        <option value="all">{{ __('auth.all_types') }}</option>
                                        <option value="sale" {{ $orderType == 'sale' ? 'selected' : '' }}>{{ __('auth.sale') }}</option>
                                        <option value="return" {{ $orderType == 'return' ? 'selected' : '' }}>{{ __('auth.return') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                {{-- Group By --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('auth.group_by') }}</label>
                                    <select class="form-select" name="group_by" id="groupBySelect">
                                        <option value="daily" {{ $groupBy == 'daily' ? 'selected' : '' }}>{{ __('auth.daily') }}</option>
                                        <option value="hourly" {{ $groupBy == 'hourly' ? 'selected' : '' }}>{{ __('auth.hourly') }}</option>
                                        <option value="weekly" {{ $groupBy == 'weekly' ? 'selected' : '' }}>{{ __('auth.weekly') }}</option>
                                        <option value="monthly" {{ $groupBy == 'monthly' ? 'selected' : '' }}>{{ __('auth.monthly') }}</option>
                                    </select>
                                </div>
                                
                                {{-- Actions --}}
                                <div class="col-md-9 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.orders.time-analysis') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </a>
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('accounting.showing') }} {{ $timeAnalysis->count() }} 
                                        @switch($groupBy)
                                            @case('hourly') {{ __('accounting.hours') }} @break
                                            @case('weekly') {{ __('accounting.weeks') }} @break
                                            @case('monthly') {{ __('accounting.months') }} @break
                                            @default {{ __('accounting.days') }}
                                        @endswitch
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA --}}
                {{-- ============================================================ --}}
                @if($timeAnalysis->count() == 0)
                <div class="card">
                    <div class="card-body text-center py-10">
                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_orders_found_for_period') }}</p>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- SUMMARY STATISTICS --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    @php
                        $totalOrders = $timeAnalysis->sum('order_count');
                        $totalSales = $timeAnalysis->sum('total_sales');
                        $totalTax = $timeAnalysis->sum('total_tax');
                        $totalDiscount = $timeAnalysis->sum('total_discount');
                        $avgSale = $timeAnalysis->avg('average_sale') ?? 0;
                        $periods = $timeAnalysis->count();
                    @endphp
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-primary h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-primary">{{ $periods }}</div>
                                <div class="text-muted">{{ __('auth.total_periods') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-info h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-info">{{ number_format($totalOrders) }}</div>
                                <div class="text-muted">{{ __('auth.total_orders') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-success h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-success">{{ currency_symbol() }}{{ number_format($totalSales, 2) }}</div>
                                <div class="text-muted">{{ __('accounting.total_sales') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-warning h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-warning">{{ currency_symbol() }}{{ number_format($totalTax, 2) }}</div>
                                <div class="text-muted">{{ __('accounting.total_tax') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-danger h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-danger">{{ currency_symbol() }}{{ number_format($totalDiscount, 2) }}</div>
                                <div class="text-muted">{{ __('accounting.total_discount') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-gray-800">{{ currency_symbol() }}{{ number_format($avgSale, 2) }}</div>
                                <div class="text-muted">{{ __('auth.average_sale') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- GROWTH METRICS --}}
                {{-- ============================================================ --}}
                @if(!empty($growthMetrics) && $timeAnalysis->count() > 1)
                <div class="row g-6 mb-6">
                    @php
                        $trendColors = ['upward' => 'success', 'downward' => 'danger', 'stable' => 'warning'];
                        $trendIcons = ['upward' => 'ki-arrow-up-right', 'downward' => 'ki-arrow-down-right', 'stable' => 'ki-minus'];
                    @endphp
                    
                    <div class="col-md-4">
                        <div class="card bg-light-{{ $trendColors[$growthMetrics['trend'] ?? 'stable'] }} border border-{{ $trendColors[$growthMetrics['trend'] ?? 'stable'] }} border-dashed">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-{{ $trendColors[$growthMetrics['trend'] ?? 'stable'] }}">
                                    {{ number_format($growthMetrics['daily_growth'] ?? 0, 1) }}%
                                </div>
                                <div class="text-gray-600">{{ __('auth.daily_growth') }}</div>
                                <span class="badge badge-light-{{ $trendColors[$growthMetrics['trend'] ?? 'stable'] }} mt-2">
                                    {{ ucfirst($growthMetrics['trend'] ?? 'stable') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light-info border border-info border-dashed">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-info">
                                    {{ number_format($growthMetrics['weekly_growth'] ?? 0, 1) }}%
                                </div>
                                <div class="text-gray-600">{{ __('auth.weekly_growth') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light-secondary border border-secondary border-dashed">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-gray-800">
                                    {{ currency_symbol() }}{{ number_format($growthMetrics['current_average'] ?? 0, 2) }}
                                </div>
                                <div class="text-gray-600">{{ __('auth.current_daily_average') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- TIME ANALYSIS CHART --}}
                {{-- ============================================================ --}}
                @if($timeAnalysis->count() > 0)
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">
                                @switch($groupBy)
                                    @case('hourly') {{ __('auth.hourly_sales_analysis') }} @break
                                    @case('weekly') {{ __('auth.weekly_sales_analysis') }} @break
                                    @case('monthly') {{ __('auth.monthly_sales_analysis') }} @break
                                    @default {{ __('auth.daily_sales_analysis') }}
                                @endswitch
                            </h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="timeAnalysisChart" style="height: 400px;"></div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- PEAK ANALYSIS --}}
                {{-- ============================================================ --}}
                @if(!empty($peakAnalysis) && $timeAnalysis->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card h-100">
                            <div class="card-header bg-light-warning">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-warning"></i>
                                    {{ __('auth.peak_days') }}
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                                <th class="ps-4">{{ __('accounting.date') }}</th>
                                                <th>{{ __('accounting.day') }}</th>
                                                <th>{{ __('auth.order_count') }}</th>
                                                <th class="text-end">{{ __('accounting.total_sales') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(isset($peakAnalysis['peak_days']) && $peakAnalysis['peak_days']->count() > 0)
                                                @foreach($peakAnalysis['peak_days'] as $peak)
                                                @php
                                                    $date = \Carbon\Carbon::parse($peak->date);
                                                    $dayName = $date->format('l');
                                                    $isWeekend = in_array($dayName, ['Saturday', 'Sunday']);
                                                @endphp
                                                <tr>
                                                    <td class="ps-4 fw-bold">{{ $date->format('M d, Y') }}</td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $isWeekend ? 'danger' : 'primary' }}">
                                                            {{ $dayName }}
                                                        </span>
                                                    </td>
                                                    <td><span class="badge badge-light-primary">{{ $peak->order_count }}</span></td>
                                                    <td class="text-end fw-bold text-success">{{ currency_symbol() }}{{ number_format($peak->total_sales, 2) }}</td>
                                                </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="4" class="text-center py-3 text-muted">
                                                        {{ __('accounting.no_peak_days_found') }}
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- TIME ANALYSIS TABLE --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">
                                @switch($groupBy)
                                    @case('hourly') {{ __('auth.hourly_sales_report') }} @break
                                    @case('weekly') {{ __('auth.weekly_sales_report') }} @break
                                    @case('monthly') {{ __('auth.monthly_sales_report') }} @break
                                    @default {{ __('auth.daily_sales_report') }}
                                @endswitch
                            </h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">
                                {{ $timeAnalysis->count() }} 
                                @switch($groupBy)
                                    @case('hourly') {{ __('accounting.hours') }} @break
                                    @case('weekly') {{ __('accounting.weeks') }} @break
                                    @case('monthly') {{ __('accounting.months') }} @break
                                    @default {{ __('accounting.days') }}
                                @endswitch
                            </span>
                            <span class="badge badge-light-secondary ms-2 fs-7">
                                {{ $startDate }} {{ __('accounting.to') }} {{ $endDate }}
                            </span>
                        </div>
                    </div>
                    
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
                                    @foreach($timeAnalysisPaginated ?? $timeAnalysis as $index => $period)
                                    @php
                                        $prevPeriod = $loop->index > 0 ? ($timeAnalysisPaginated[$loop->index - 1] ?? null) : null;
                                        $trend = $prevPeriod ? $period->total_sales - $prevPeriod->total_sales : 0;
                                        $trendPercent = $prevPeriod && $prevPeriod->total_sales > 0 ? ($trend / $prevPeriod->total_sales) * 100 : 0;
                                        
                                        if ($groupBy == 'hourly') {
                                            $hour = intval($period->time_period);
                                            $displayTime = $hour >= 12 ? ($hour % 12 ?: 12) . ':00 PM' : ($hour == 0 ? '12:00 AM' : $hour . ':00 AM');
                                            $dayPart = $hour < 12 ? 'Morning' : ($hour < 17 ? 'Afternoon' : ($hour < 21 ? 'Evening' : 'Night'));
                                            $periodName = $hour . ':00';
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
                                                <td class="ps-4 fw-bold">{{ $periodName }}</td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $displayTime }}</span>
                                                    <span class="badge badge-light-info badge-sm ms-1">{{ $dayPart }}</span>
                                                </td>
                                                @break
                                            @case('weekly')
                                                <td class="ps-4 fw-bold">{{ $periodName }}</td>
                                                <td>
                                                    <span class="text-gray-700">{{ $dateRange }}</span>
                                                    <span class="badge badge-light-primary badge-sm ms-1">{{ $period->year }}</span>
                                                </td>
                                                @break
                                            @case('monthly')
                                                <td class="ps-4 fw-bold">{{ $periodName }}</td>
                                                <td><span class="badge badge-light-primary">{{ $period->year }}</span></td>
                                                @break
                                            @default
                                                <td class="ps-4 fw-bold">{{ $periodName }}</td>
                                                <td>
                                                    <span class="badge badge-light-{{ $isWeekend ? 'danger' : 'primary' }}">
                                                        {{ $dayName }}
                                                    </span>
                                                </td>
                                        @endswitch
                                        <td><span class="badge badge-light-primary">{{ $period->order_count }}</span></td>
                                        <td class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($period->total_sales, 2) }}</td>
                                        <td class="text-gray-600">{{ currency_symbol() }}{{ number_format($period->average_sale, 2) }}</td>
                                        <td class="text-info">{{ currency_symbol() }}{{ number_format($period->total_tax, 2) }}</td>
                                        <td class="text-warning">{{ currency_symbol() }}{{ number_format($period->total_discount, 2) }}</td>
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
                                <tfoot class="bg-light">
                                    <tr>
                                        <th colspan="3" class="text-end fw-bold">{{ __('accounting.totals') }}</th>
                                        <th class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($timeAnalysis->sum('total_sales'), 2) }}</th>
                                        <th class="fw-bold text-gray-600">{{ currency_symbol() }}{{ number_format($timeAnalysis->avg('average_sale') ?? 0, 2) }}</th>
                                        <th class="fw-bold text-info">{{ currency_symbol() }}{{ number_format($timeAnalysis->sum('total_tax'), 2) }}</th>
                                        <th class="fw-bold text-warning">{{ currency_symbol() }}{{ number_format($timeAnalysis->sum('total_discount'), 2) }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- PAGINATION --}}
                    {{-- ============================================================ --}}
                    @if(isset($timeAnalysisPaginated) && $timeAnalysisPaginated->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $timeAnalysisPaginated,
                            'pageName' => 'page',
                            'perPageName' => 'per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif

                    {{-- LEGEND --}}
                    <div class="card-footer text-muted fs-7">
                        <i class="ki-duotone ki-information-4 fs-2 me-1"></i>
                        <strong>{{ __('auth.legend') }}:</strong>
                        <span class="badge badge-light-success mx-1">⬆️</span> {{ __('auth.positive_growth') }}
                        <span class="badge badge-light-danger mx-1">⬇️</span> {{ __('auth.negative_growth') }}
                        <span class="badge badge-light-secondary mx-1">➖</span> {{ __('auth.no_change') }}
                        <span class="badge badge-light-primary mx-1">📊</span> {{ __('accounting.total_sales') }}
                        <span class="badge badge-light-info mx-1">📈</span> {{ __('accounting.trend') }}
                    </div>
                </div>

                {{-- Peak Hours Chart (only for hourly grouping) --}}
                @if($groupBy == 'hourly' && isset($peakAnalysis['peak_hours']) && $peakAnalysis['peak_hours']->count() > 0)
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-light-success">
                            <h3 class="card-title">
                                <i class="ki-duotone ki-clock fs-2 me-2 text-success"></i>
                                {{ __('auth.peak_hours') }}
                            </h3>
                        </div>
                        <div class="card-body">
                            <div id="peakHoursChart" style="height: 250px;"></div>
                        </div>
                    </div>
                </div>

                @push('scripts')
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const peakHoursData = @json($peakAnalysis['peak_hours'] ?? []);
                    if (peakHoursData.length > 0) {
                        const hours = peakHoursData.map(p => {
                            const h = parseInt(p.hour);
                            const display = h % 12 || 12;
                            return display + ':00';
                        });
                        const sales = peakHoursData.map(p => parseFloat(p.hourly_total));
                        
                        const options = {
                            series: [{ name: 'Sales', data: sales }],
                            chart: { type: 'bar', height: 250, toolbar: { show: false } },
                            plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
                            colors: ['#50CD89'],
                            xaxis: { categories: hours },
                            yaxis: {
                                labels: {
                                    formatter: function(val) {
                                        return '{{ currency_symbol() }}' + val.toFixed(2);
                                    }
                                }
                            },
                            tooltip: {
                                y: {
                                    formatter: function(val) {
                                        return '{{ currency_symbol() }}' + val.toFixed(2);
                                    }
                                }
                            }
                        };
                        
                        new ApexCharts(document.querySelector("#peakHoursChart"), options).render();
                    }
                });
                </script>
                @endpush
                @endif

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
                        | {{ $timeAnalysis->count() ?? 0 }} {{ __('accounting.periods') }}
                        | {{ __('auth.total_orders') }}: {{ $timeAnalysis->sum('order_count') ?? 0 }}
                        | {{ __('accounting.total_sales') }}: {{ currency_symbol() }}{{ number_format($timeAnalysis->sum('total_sales') ?? 0, 2) }}
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
@if($timeAnalysis->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeData = @json($timeAnalysis);
    let categories = [];
    let salesData = [];
    let orderData = [];
    
    @switch($groupBy)
        @case('hourly')
            timeData.forEach(item => {
                const hour = parseInt(item.time_period);
                const displayHour = hour % 12 || 12;
                const period = hour >= 12 ? 'PM' : 'AM';
                categories.push(displayHour + ' ' + period);
                salesData.push(parseFloat(item.total_sales));
                orderData.push(parseFloat(item.order_count));
            });
            @break
        @case('weekly')
            timeData.forEach(item => {
                const start = new Date(item.week_start);
                const end = new Date(item.week_end);
                categories.push('W' + item.week_number);
                salesData.push(parseFloat(item.total_sales));
                orderData.push(parseFloat(item.order_count));
            });
            @break
        @case('monthly')
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            timeData.forEach(item => {
                categories.push(monthNames[item.month_number - 1] + ' ' + item.year);
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
    
    const options = {
        series: [
            { name: '{{ __("accounting.total_sales") }}', type: 'line', data: salesData },
            { name: '{{ __("auth.order_count") }}', type: 'column', data: orderData }
        ],
        chart: {
            height: 400,
            type: 'line',
            stacked: false,
            toolbar: { show: true }
        },
        dataLabels: { enabled: false },
        stroke: { width: [3, 1], curve: 'smooth' },
        colors: ['#50CD89', '#009EF7'],
        fill: { opacity: [0.85, 0.25] },
        markers: { size: 4 },
        xaxis: {
            categories: categories,
            labels: { rotate: -45, style: { fontSize: '11px' } }
        },
        yaxis: [
            {
                title: { text: '{{ __("accounting.total_sales") }} ($)', style: { color: '#50CD89' } },
                labels: { formatter: function(val) { return '$' + val.toFixed(2); } }
            },
            {
                opposite: true,
                title: { text: '{{ __("auth.order_count") }}', style: { color: '#009EF7' } }
            }
        ],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(value, { seriesIndex }) {
                    return seriesIndex === 0 ? '$' + value.toFixed(2) : value;
                }
            }
        },
        legend: { position: 'top', horizontalAlign: 'left' },
        grid: { borderColor: '#E5E7EB', strokeDashArray: 4 }
    };
    
    new ApexCharts(document.querySelector("#timeAnalysisChart"), options).render();
});

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return alert('{{ __("accounting.table_not_found") }}');
    try {
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, XLSX.utils.table_to_sheet(table), 'Time Analysis');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch(e) { alert('{{ __("accounting.export_error") }}: ' + e.message); }
}

function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return alert('{{ __("accounting.table_not_found") }}');
    try {
        const rows = table.querySelectorAll('tr');
        let csv = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            csv.push(Array.from(cols).map(c => c.innerText.trim()).join(','));
        });
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename + '.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    } catch(e) { alert('{{ __("accounting.export_error") }}: ' + e.message); }
}

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

@endsection