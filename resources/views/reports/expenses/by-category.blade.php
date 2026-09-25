{{-- resources/views/reports/expenses/by-category.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.expenses_by_category'))

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
                                {{ __('accounting.expenses_by_category') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('accounting.category_breakdown') }}</li>
                            </ul>
                        </div>

                        <!-- Right side - Actions -->
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($categoryBreakdown->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'categoryTable', filename: 'expenses_by_category_{{ date('Y_m_d') }}', sheetName: 'Category Breakdown'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'categoryTable', filename: 'expenses_by_category_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <div class="card-toolbar">
                                    <span class="badge badge-light-info fs-7">
                                        <i class="ki-duotone ki-calendar-8 fs-4 me-1"></i>
                                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                                        &mdash;
                                        {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.expenses.by-category') }}" id="filterForm">

                                    {{-- Row 1: Date range + Category --}}
                                    <div class="row g-4 mb-4">
                                        <div class="col-xl-6">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control"
                                                        name="start_date" value="{{ $startDate }}" required>
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-1">
                                                    {{ __('accounting.to') }}
                                                </span>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control"
                                                        name="end_date" value="{{ $endDate }}" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.specific_category') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="category_id" data-control="select2">
                                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                                    @foreach($expenseCategories as $category)
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
                                                <a href="{{ route('reports.expenses.by-category') }}"
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
                                        $categoryId
                                            ? ['label' => __('accounting.category'), 'value' => $categories->firstWhere('id', $categoryId)?->name]
                                            : null,
                                        $locationId
                                            ? ['label' => __('accounting.location'), 'value' => $locations->firstWhere('id', $locationId)?->name]
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
                                        <a href="{{ route('reports.expenses.by-category') }}"
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
                @if($categoryBreakdown->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.category_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                @foreach([
                                    ['key' => 'category_count', 'color' => 'primary', 'icon' => 'ki-category', 'label' => 'total_categories',   'value' => $categoryBreakdown->count(),                                       'money' => false],
                                    ['key' => 'total_expenses', 'color' => 'success', 'icon' => 'ki-dollar',   'label' => 'total_expenses',     'value' => $categoryBreakdown->sum('expense_count'),                          'money' => false],
                                    ['key' => 'grand_total',    'color' => 'info',    'icon' => 'ki-chart-simple', 'label' => 'grand_total_amount', 'value' => $totalExpenses,                                                 'money' => true],
                                    ['key' => 'total_tax',      'color' => 'warning', 'icon' => 'ki-receipt-tax','label' => 'total_tax',          'value' => $categoryBreakdown->sum('total_tax'),                              'money' => true],
                                    ['key' => 'avg_amount',     'color' => 'danger',  'icon' => 'ki-calculator','label' => 'average_per_category','value' => $categoryBreakdown->avg('average_amount'),                        'money' => true],
                                    ['key' => 'top_category',   'color' => 'secondary','icon' => 'ki-ranking',  'label' => 'top_category',       'value' => $categoryBreakdown->first()->category_name ?? 'N/A',               'money' => false],
                                ] as $stat)
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

                {{-- Category Breakdown Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('accounting.category_breakdown') }}</h3>
                                    </div>
                                    @if($categoryBreakdown->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $categoryBreakdown->count() }} {{ __('accounting.categories') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($categoryBreakdown->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="categoryTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-50px ps-4">{{ __('accounting.rank') }}</th>
                                                    <th class="min-w-200px">{{ __('accounting.category_name') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.expense_count') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.total_amount') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.tax_amount') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.grand_total') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.average') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.max_amount') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.min_amount') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($categoryBreakdown as $index => $category)
                                                @php
                                                    $percentage = $totalExpenses > 0 ? ($category->grand_total / $totalExpenses) * 100 : 0;
                                                @endphp
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="fw-bold text-gray-800">{{ $index + 1 }}</span>
                                                        @if($index < 3)
                                                        <div class="mt-1">
                                                            <span class="badge badge-light-{{ $index == 0 ? 'danger' : ($index == 1 ? 'warning' : 'info') }}">
                                                                <i class="ki-duotone ki-{{ $index == 0 ? 'medal' : ($index == 1 ? 'ranking' : 'ranking-2') }} fs-4 me-1"></i>
                                                                {{ __('accounting.top') }} {{ $index + 1 }}
                                                            </span>
                                                        </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-40px symbol-circle me-3">
                                                                <div class="symbol-label bg-light-{{ ['primary', 'success', 'info', 'warning', 'danger'][$index % 5] }}">
                                                                    <i class="ki-duotone ki-category fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $category->category_name }}</span>
                                                                @if($category->category_code)
                                                                <small class="text-muted">
                                                                    {{ __('accounting.code') }}: {{ $category->category_code }}
                                                                </small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">{{ $category->expense_count }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-800 fw-semibold">{{ currency_symbol() }} {{ number_format($category->total_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-info">{{ currency_symbol() }} {{ number_format($category->total_tax, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">{{ currency_symbol() }} {{ number_format($category->grand_total, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">{{ currency_symbol() }} {{ number_format($category->average_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger">{{ currency_symbol() }} {{ number_format($category->max_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-secondary">{{ currency_symbol() }} {{ number_format($category->min_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress w-100 me-3" style="height: 8px;">
                                                                <div class="progress-bar bg-{{ ['primary', 'success', 'info', 'warning', 'danger'][$index % 5] }}" 
                                                                    role="progressbar" 
                                                                    style="width: {{ min($percentage, 100) }}%;" 
                                                                    aria-valuenow="{{ $percentage }}" 
                                                                    aria-valuemin="0" 
                                                                    aria-valuemax="100">
                                                                </div>
                                                            </div>
                                                            <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                                {{ number_format($percentage, 1) }}%
                                                            </span>
                                                        </div>
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
                                        <p class="text-muted fs-6">{{ __('accounting.no_expenses_found_for_categories') }}</p>
                                        @if(request()->filled('category_id') || request()->filled('location_id'))
                                            <a href="{{ route('reports.expenses.by-category') }}" class="btn btn-light-primary">
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
                
                {{-- Monthly Trend Chart --}}
                @if($monthlyTrendGrouped && $monthlyTrendGrouped->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_trend_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyTrendChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Monthly Trend Table --}}
                <div class="row mt-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_data') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.month_year') }}</th>
                                                @foreach($monthlyTrendGrouped->keys() as $categoryName)
                                                <th class="text-end">{{ $categoryName }}</th>
                                                @endforeach
                                            </td>
                                        </thead>
                                        <tbody>
                                            @foreach($uniqueMonths as $monthData)
                                            @php
                                                $monthKey = $monthData->year . '-' . str_pad($monthData->month, 2, '0', STR_PAD_LEFT);
                                            @endphp
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $monthData->label }}</td>
                                                @foreach($monthlyTrendGrouped->keys() as $categoryName)
                                                    @php
                                                        $monthlyTotal = $monthlyDataMatrix[$monthKey][$categoryName] ?? 0;
                                                    @endphp
                                                    <td class="text-end">
                                                        <span class="text-gray-800 fw-semibold">{{ currency_symbol() }} {{ number_format($monthlyTotal, 2) }}</span>
                                                    </td>
                                                @endforeach
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold text-gray-800 bg-light">
                                                <td class="ps-4">{{ __('accounting.total') }}</td>
                                                @foreach($monthlyTrendGrouped->keys() as $categoryName)
                                                    @php
                                                        $total = $categoryTotals[$categoryName] ?? 0;
                                                    @endphp
                                                    <td class="text-end">
                                                        <span class="text-success fw-bold">{{ currency_symbol() }} {{ number_format($total, 2) }}</span>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        </tfoot>
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
@if(isset($monthlyTrendGrouped) && $monthlyTrendGrouped->count() > 0 && isset($uniqueMonths) && $uniqueMonths->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for chart
        const categories = @json($monthlyTrendGrouped->keys());
        const months = @json($uniqueMonths);
        const seriesData = [];
        
        categories.forEach((category, index) => {
            const data = months.map(month => {
                const monthData = @json($monthlyTrendGrouped);
                const found = monthData[category]?.find(m => 
                    m.year == month.year && m.month == month.month
                );
                return found?.monthly_total || 0;
            });
            
            seriesData.push({
                name: category,
                data: data,
                type: 'line'
            });
        });
        
        const monthLabels = months.map(m => m.label);
        const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#14B8A6', '#F97316', '#8B5CF6'];
        
        const chartOptions = {
            series: seriesData,
            chart: {
                type: 'line',
                height: 400,
                toolbar: { show: true },
                zoom: { enabled: true }
            },
            stroke: { width: 3, curve: 'smooth' },
            colors: colors,
            xaxis: {
                categories: monthLabels,
                labels: { rotate: -45, style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: '{{ __('accounting.amount') }} ({{ currency_symbol() }})' },
                labels: { formatter: (val) => '{{ currency_symbol() }}' + val.toLocaleString() }
            },
            tooltip: {
                y: {
                    formatter: (val) => '{{ currency_symbol() }}' +
                        val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                }
            },
            legend: { position: 'top', horizontalAlign: 'center', fontSize: '12px' },
            markers: { size: 4 },
            grid: { borderColor: '#f1f1f1' }
        };
        
        const chart = new ApexCharts(document.querySelector("#monthlyTrendChart"), chartOptions);
        chart.render();
    });
</script>
@endif
@endpush


@endsection