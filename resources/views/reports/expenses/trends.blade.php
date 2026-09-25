{{-- resources/views/reports/expenses/trends.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.expense_trends_report'))

@section('content')

@if (tenant_can('advanced_reports'))
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
               {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <!-- Left side - Title and Breadcrumb -->
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('accounting.expense_trends_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('accounting.trend_analysis') }}</li>
                            </ul>
                        </div>

                        <!-- Right side - Actions -->
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if(!empty($trendData))
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'trendsTable', filename: 'expense_trends_{{ date('Y_m_d') }}', sheetName: 'Expense Trends'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'trendsTable', filename: 'expense_trends_{{ date('Y_m_d') }}', format: 'csv'})">
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

                {{-- ═══════════════════════════════════════════════════════════
                    FILTERS
                ═══════════════════════════════════════════════════════════ --}}
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
                                <form method="GET" action="{{ route('reports.expenses.trends') }}" id="filterForm">

                                    {{-- Row 1: Period + Year + Category + Location --}}
                                    <div class="row g-4 mb-4">
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.period') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="period" id="periodSelect" data-control="select2">
                                                    <option value="monthly"   {{ $period === 'monthly'   ? 'selected' : '' }}>{{ __('accounting.monthly') }}</option>
                                                    <option value="quarterly" {{ $period === 'quarterly' ? 'selected' : '' }}>{{ __('accounting.quarterly') }}</option>
                                                    <option value="yearly"    {{ $period === 'yearly'    ? 'selected' : '' }}>{{ __('accounting.yearly') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-3" id="yearField">
                                            <label class="form-label fw-semibold">{{ __('accounting.year') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="year" data-control="select2">
                                                    @foreach($years as $y)
                                                        <option value="{{ $y }}" {{ (int) $year === (int) $y ? 'selected' : '' }}>
                                                            {{ $y }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="category_id" data-control="select2">
                                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}"
                                                                {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}@if($category->code) ({{ $category->code }})@endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.location') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="location_id" data-control="select2">
                                                    <option value="">{{ __('pagination.all_locations') }}</option>
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}"
                                                                {{ (string) $locationId === (string) $location->id ? 'selected' : '' }}>
                                                            {{ $location->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Row 2: Actions --}}
                                    <div class="row g-4">
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.expenses.trends') }}"
                                                class="btn btn-light btn-active-light-primary">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.clear') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                {{-- Active filter chips --}}
                                @php
                                    $activeFilters = array_filter([
                                        ($period !== 'monthly')
                                            ? ['label' => __('accounting.period'), 'value' => __($period)]
                                            : null,
                                        $categoryId
                                            ? ['label' => __('accounting.category'), 'value' => $categories->firstWhere('id', $categoryId)?->name]
                                            : null,
                                        $locationId
                                            ? ['label' => __('accounting.location'), 'value' => $locations->firstWhere('id', $locationId)?->name]
                                            : null,
                                        ($period !== 'yearly' && (int) $year !== (int) date('Y'))
                                            ? ['label' => __('accounting.year'), 'value' => $year]
                                            : null,
                                    ]);
                                @endphp

                                @if(count($activeFilters) > 0)
                                    <div class="separator separator-dashed my-4"></div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="text-muted fw-semibold me-2">
                                            <i class="ki-duotone ki-filter fs-5 me-1"></i>
                                            {{ __('accounting.active_filters') }}:
                                        </span>
                                        @foreach($activeFilters as $filter)
                                            <span class="badge badge-light-primary fs-7">
                                                <strong>{{ $filter['label'] }}:</strong>&nbsp;{{ $filter['value'] }}
                                            </span>
                                        @endforeach
                                        <a href="{{ route('reports.expenses.trends') }}"
                                        class="text-danger fs-7 text-hover-primary ms-2 d-inline-flex align-items-center gap-1">
                                            <i class="ki-duotone ki-cross fs-5"></i>
                                            {{ __('accounting.clear_all') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary Statistics --}}
                @if(!empty($trendData))
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">
                                        @if($period == 'monthly')
                                            {{ __('accounting.monthly_trends_summary') }} {{ $year }}
                                        @elseif($period == 'quarterly')
                                            {{ __('accounting.quarterly_trends_summary') }} {{ $year }}
                                        @else
                                            {{ __('accounting.yearly_trends_summary') }}
                                        @endif
                                    </h3>
                                </div>
                            </div>
                            @if(!empty($trendData))
                                @php
                                    $currentTotal  = (float) collect($trendData)->sum('current_year')
                                        ?: (float) collect($trendData)->sum('total');
                                    $previousTotal = (float) collect($trendData)->sum('previous_year');
                                    $totalExpenses = collect($trendData)->sum('expense_count');
                                    $averageAmount = count($trendData) > 0 ? $currentTotal / count($trendData) : 0;

                                    $growthRate = $previousTotal > 0
                                        ? (($currentTotal - $previousTotal) / $previousTotal) * 100
                                        : 0;

                                    $stats = [
                                        ['color' => 'primary',   'icon' => 'ki-chart-up',    'label' => 'total_expenses',        'value' => $currentTotal,                                              'money' => true],
                                        ['color' => $growthRate >= 0 ? 'success' : 'danger',
                                                                'icon' => 'ki-growth',      'label' => 'growth_rate',           'value' => number_format($growthRate, 1) . '%',                        'money' => false],
                                        ['color' => 'info',      'icon' => 'ki-receipt',     'label' => 'expense_count',         'value' => $totalExpenses,                                             'money' => false],
                                        ['color' => 'warning',   'icon' => 'ki-calculator',  'label' => 'average_amount',        'value' => $averageAmount,                                             'money' => true],
                                        ['color' => 'secondary', 'icon' => 'ki-calendar',    'label' => 'previous_year_total',   'value' => $previousTotal,                                             'money' => true],
                                        ['color' => $currentTotal >= $previousTotal ? 'danger' : 'success',
                                                                'icon' => 'ki-chart',       'label' => 'variance',              'value' => abs($currentTotal - $previousTotal),                        'money' => true],
                                    ];
                                @endphp

                                <div class="row mb-6">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header border-0">
                                                <div class="card-title d-flex align-items-center">
                                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                    <h3 class="fw-bold m-0">
                                                        @if($period === 'monthly')
                                                            {{ __('accounting.monthly_trends_summary') }} {{ $year }}
                                                        @elseif($period === 'quarterly')
                                                            {{ __('accounting.quarterly_trends_summary') }} {{ $year }}
                                                        @else
                                                            {{ __('accounting.yearly_trends_summary') }}
                                                        @endif
                                                    </h3>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="row g-6">
                                                    @foreach($stats as $stat)
                                                        <div class="col-md-6 col-lg-2">
                                                            <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                                                    <div class="mb-4">
                                                                        <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}">
                                                                            <span class="path1"></span>
                                                                            <span class="path2"></span>
                                                                        </i>
                                                                    </div>
                                                                    <div class="mb-1">
                                                                        <span class="fs-2 fw-bold text-gray-800">
                                                                            @if($stat['money'])
                                                                                {{ currency_symbol() }} {{ number_format($stat['value'], 2) }}
                                                                            @else
                                                                                {{ $stat['value'] }}
                                                                            @endif
                                                                        </span>
                                                                    </div>
                                                                    <div class="text-gray-600 fw-semibold fs-7">
                                                                        {{ __('accounting.' . $stat['label']) }}
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
                        </div>
                    </div>
                </div>
                @endif

                {{-- Trends Chart --}}
                @if(!empty($trendData))
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
                                        @if($period == 'monthly')
                                            {{ __('accounting.monthly_expense_trends') }} {{ $year }}
                                        @elseif($period == 'quarterly')
                                            {{ __('accounting.quarterly_expense_trends') }} {{ $year }}
                                        @else
                                            {{ __('accounting.yearly_expense_trends') }}
                                        @endif
                                    </h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="trendsChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Trends Table --}}
                <div class="row mb-6">
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
                                            @if($period == 'monthly')
                                                {{ __('accounting.monthly_trend_data') }}
                                            @elseif($period == 'quarterly')
                                                {{ __('accounting.quarterly_trend_data') }}
                                            @else
                                                {{ __('accounting.yearly_trend_data') }}
                                            @endif
                                        </h3>
                                    </div>
                                    @if(!empty($trendData))
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ count($trendData) }} {{ __('accounting.periods') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if(!empty($trendData))
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="trendsTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    @if($period == 'monthly')
                                                        <th class="min-w-150px ps-4">{{ __('accounting.month') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.current_year') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.previous_year') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.growth') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.expense_count') }}</th>
                                                        @if(isset($movingAverages))
                                                        <th class="min-w-150px">{{ __('accounting.moving_average') }}</th>
                                                        @endif
                                                        @if(isset($momGrowth))
                                                        <th class="min-w-150px">{{ __('accounting.month_over_month') }}</th>
                                                        @endif
                                                    @elseif($period == 'quarterly')
                                                        <th class="min-w-150px ps-4">{{ __('accounting.quarter') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.period') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.total_amount') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.quarterly_growth') }}</th>
                                                    @else
                                                        <th class="min-w-150px ps-4">{{ __('accounting.year') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.total_amount') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.expense_count') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.average_amount') }}</th>
                                                        <th class="min-w-150px">{{ __('accounting.yearly_growth') }}</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if($period == 'monthly')
                                                    @php
                                                        $monthNames = [
                                                            1 => __('accounting.january'), 2 => __('accounting.february'), 
                                                            3 => __('accounting.march'), 4 => __('accounting.april'),
                                                            5 => __('accounting.may'), 6 => __('accounting.june'),
                                                            7 => __('accounting.july'), 8 => __('accounting.august'),
                                                            9 => __('accounting.september'), 10 => __('accounting.october'),
                                                            11 => __('accounting.november'), 12 => __('accounting.december')
                                                        ];
                                                    @endphp
                                                    @foreach($trendData as $month => $data)
                                                    <tr>
                                                        <td class="ps-4 fw-semibold">
                                                            {{ $monthNames[$month] }}
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-success">{{ currency_symbol() }} {{ number_format($data['current_year'], 2) }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="fw-semibold text-gray-600">{{ currency_symbol() }} {{ number_format($data['previous_year'], 2) }}</span>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $growthColor = $data['growth'] >= 0 ? 'text-danger' : 'text-success';
                                                                $growthIcon = $data['growth'] >= 0 ? 'ki-arrow-up' : 'ki-arrow-down';
                                                            @endphp
                                                            <span class="fw-bold {{ $growthColor }}">
                                                                <i class="ki-duotone {{ $growthIcon }} fs-3 me-1"></i>
                                                                {{ number_format(abs($data['growth']), 1) }}%
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-light-info">
                                                                {{ $data['expense_count'] }}
                                                            </span>
                                                        </td>
                                                        @if(isset($movingAverages) && isset($movingAverages[$month]))
                                                        <td>
                                                            <span class="fw-semibold text-primary"> {{ currency_symbol() }} {{ number_format($movingAverages[$month], 2) }}</span>
                                                        </td>
                                                        @endif
                                                        @if(isset($momGrowth) && isset($momGrowth[$month]))
                                                        <td>
                                                            @php
                                                                $momColor = $momGrowth[$month] >= 0 ? 'text-danger' : 'text-success';
                                                                $momIcon = $momGrowth[$month] >= 0 ? 'ki-arrow-up' : 'ki-arrow-down';
                                                            @endphp
                                                            <span class="fw-bold {{ $momColor }}">
                                                                <i class="ki-duotone {{ $momIcon }} fs-3 me-1"></i>
                                                                {{ number_format(abs($momGrowth[$month]), 1) }}%
                                                            </span>
                                                        </td>
                                                        @endif
                                                    </tr>
                                                    @endforeach
                                                @elseif($period == 'quarterly')
                                                    @foreach($trendData as $quarter => $data)
                                                    @php
                                                        $quarterNames = [
                                                            1 => __('accounting.q1'), 2 => __('accounting.q2'),
                                                            3 => __('accounting.q3'), 4 => __('accounting.q4')
                                                        ];
                                                        $prevQuarter = $trendData[$quarter - 1] ?? null;
                                                        $growth = $prevQuarter ? (($data['total'] - $prevQuarter['total']) / $prevQuarter['total'] * 100) : 0;
                                                    @endphp
                                                    <tr>
                                                        <td class="ps-4 fw-semibold">
                                                            {{ $quarterNames[$quarter] }}
                                                        </td>
                                                        <td>
                                                            @php
                                                                $monthNames = [
                                                                    1 => __('accounting.january'), 2 => __('accounting.february'), 
                                                                    3 => __('accounting.march'), 4 => __('accounting.april'),
                                                                    5 => __('accounting.may'), 6 => __('accounting.june'),
                                                                    7 => __('accounting.july'), 8 => __('accounting.august'),
                                                                    9 => __('accounting.september'), 10 => __('accounting.october'),
                                                                    11 => __('accounting.november'), 12 => __('accounting.december')
                                                                ];
                                                            @endphp
                                                            {{ $monthNames[$data['start_month']] }} - {{ $monthNames[$data['end_month']] }}
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-success">{{ currency_symbol() }} {{ number_format($data['total'], 2) }}</span>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $growthColor = $growth >= 0 ? 'text-danger' : 'text-success';
                                                                $growthIcon = $growth >= 0 ? 'ki-arrow-up' : 'ki-arrow-down';
                                                            @endphp
                                                            <span class="fw-bold {{ $growthColor }}">
                                                                <i class="ki-duotone {{ $growthIcon }} fs-3 me-1"></i>
                                                                {{ number_format(abs($growth), 1) }}%
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                @else
                                                    @php
                                                        $yearsData = collect($trendData)->sortKeys();
                                                        $prevYearAmount = null;
                                                    @endphp
                                                    @foreach($yearsData as $yearData)
                                                    @php
                                                        $growth = $prevYearAmount ? (($yearData['total'] - $prevYearAmount) / $prevYearAmount * 100) : 0;
                                                        $prevYearAmount = $yearData['total'];
                                                    @endphp
                                                    <tr>
                                                        <td class="ps-4 fw-semibold">
                                                            {{ $yearData['year'] }}
                                                        </td>
                                                        <td>
                                                            <span class="fw-bold text-success">{{ currency_symbol() }} {{ number_format($yearData['total'], 2) }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-light-info">
                                                                {{ $yearData['expense_count'] }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="fw-semibold text-gray-600">{{ currency_symbol() }} {{ number_format($yearData['average'], 2) }}</span>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $growthColor = $growth >= 0 ? 'text-danger' : 'text-success';
                                                                $growthIcon = $growth >= 0 ? 'ki-arrow-up' : 'ki-arrow-down';
                                                            @endphp
                                                            <span class="fw-bold {{ $growthColor }}">
                                                                <i class="ki-duotone {{ $growthIcon }} fs-3 me-1"></i>
                                                                {{ number_format(abs($growth), 1) }}%
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                @endif
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
                                        <p class="text-muted fs-6">{{ __('accounting.no_expense_trends_found') }}</p>
                                        @if(request()->hasAny(['period', 'year', 'category_id']))
                                        <a href="{{ route('reports.expenses.trends') }}" class="btn btn-light-primary">
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
                
                {{-- Category Trends Section (Monthly only) --}}
                @if($period == 'monthly' && isset($categoryTrends) && $categoryTrends->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.category_trends') }} {{ $year }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.category') }}</th>
                                                @for($month = 1; $month <= 12; $month++)
                                                    <th class="text-center">{{ __('accounting.month_' . $month) }}</th>
                                                @endfor
                                                <th class="text-end">{{ __('accounting.total') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($categoryTrends as $catData)
                                                @php
                                                    // Each $catData is a stdClass with:
                                                    //   ->category_name  (string)
                                                    //   ->monthly_data   (array keyed by month number 1..12)
                                                    //   ->total          (float, precomputed)
                                                    $categoryName  = $catData->category_name;
                                                    $monthlyData   = $catData->monthly_data;
                                                    $categoryTotal = $catData->total;
                                                @endphp
                                                <tr>
                                                    <td class="ps-4 fw-semibold">
                                                        <span class="badge badge-light-primary">{{ $categoryName }}</span>
                                                    </td>

                                                    @for($month = 1; $month <= 12; $month++)
                                                        @php $amount = $monthlyData[$month] ?? 0; @endphp
                                                        <td class="text-center">
                                                            @if($amount > 0)
                                                                <span class="fw-semibold text-gray-700">
                                                                    {{ currency_symbol() }} {{ number_format($amount, 0) }}
                                                                </span>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    @endfor

                                                    <td class="text-end fw-bold text-success">
                                                        {{ currency_symbol() }} {{ number_format($categoryTotal, 2) }}
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
                
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
@if(!empty($trendData))
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize trends chart
        const trendData = @json($trendData);
        const period = @json($period);
        
        let chartOptions = {};
        
        if (period === 'monthly') {
            const monthNames = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ];
            
            const currentYearData = [];
            const previousYearData = [];
            
            for (let month = 1; month <= 12; month++) {
                const data = trendData[month];
                currentYearData.push(data ? data.current_year : 0);
                previousYearData.push(data ? data.previous_year : 0);
            }
            
            chartOptions = {
                series: [
                    {
                        name: '{{ __("accounting.current_year") }}',
                        data: currentYearData,
                        color: '#3E97FF'
                    },
                    {
                        name: '{{ __("accounting.previous_year") }}',
                        data: previousYearData,
                        color: '#F1416C'
                    }
                ],
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: {
                        show: true
                    }
                },
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                markers: {
                    size: 5
                },
                xaxis: {
                    categories: monthNames,
                    title: {
                        text: '{{ __("accounting.month") }}'
                    }
                },
                yaxis: {
                    title: { text: '{{ __('accounting.amount') }} ({{ currency_symbol() }})' },
                    labels: {
                        formatter: function (val) {
                            return '{{ currency_symbol() }}' + val.toLocaleString(undefined, { minimumFractionDigits: 0 });
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return '{{ currency_symbol() }}' + val.toLocaleString(undefined, { minimumFractionDigits: 2 });
                        }
                    }
                },
                legend: {
                    position: 'top'
                }
            };
            
        } else if (period === 'quarterly') {
            const quarterNames = ['Q1', 'Q2', 'Q3', 'Q4'];
            const quarterData = [];
            
            for (let quarter = 1; quarter <= 4; quarter++) {
                quarterData.push(trendData[quarter] ? trendData[quarter].total : 0);
            }
            
            chartOptions = {
                series: [{
                    name: '{{ __("accounting.quarterly_total") }}',
                    data: quarterData,
                    color: '#50CD89'
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: true
                    }
                },
                plotOptions: {
                    bar: {
                        columnWidth: '50%'
                    }
                },
                xaxis: {
                    categories: quarterNames,
                    title: {
                        text: '{{ __("accounting.quarter") }}'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Amount ($)'
                    },
                    labels: {
                        formatter: function(val) {
                            return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 0});
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    }
                }
            };
            
        } else { // yearly
            const years = Object.keys(trendData).sort();
            const yearData = [];
            
            years.forEach(year => {
                yearData.push(trendData[year] ? trendData[year].total : 0);
            });
            
            chartOptions = {
                series: [{
                    name: '{{ __("accounting.yearly_total") }}',
                    data: yearData,
                    color: '#7239EA'
                }],
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: {
                        show: true
                    }
                },
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.3,
                        stops: [0, 90, 100]
                    }
                },
                xaxis: {
                    categories: years,
                    title: {
                        text: '{{ __("accounting.year") }}'
                    }
                },
                yaxis: {
                    title: {
                        text: 'Amount ($)'
                    },
                    labels: {
                        formatter: function(val) {
                            return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 0});
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    }
                }
            };
        }
        
        // Render the chart
        const chart = new ApexCharts(document.querySelector("#trendsChart"), chartOptions);
        chart.render();
        
        // Toggle year field based on period selection
        const periodSelect = document.getElementById('periodSelect');
        const yearField = document.getElementById('yearField');
        
        function toggleYearField() {
            if (periodSelect.value === 'yearly') {
                yearField.style.display = 'none';
            } else {
                yearField.style.display = 'block';
            }
        }
        
        periodSelect.addEventListener('change', toggleYearField);
        toggleYearField(); // Initial call
    });
</script>
@endif
@endpush

@endsection