{{-- resources/views/reports/expenses/by-category.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.expenses_by_category'))

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
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($categoryBreakdown->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
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
                                <form method="GET" action="{{ route('reports.expenses.by-category') }}" id="filterForm">
                                    <div class="row g-6 mb-6">
                                        {{-- Date Range --}}
                                        <div class="col-md-12 col-lg-4">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="start_date" 
                                                    value="{{ $startDate }}" required
                                                    title="{{ __('accounting.start_date') }}">
                                                <span class="input-group-text bg-light">{{ __('accounting.to') }}</span>
                                                <input type="date" class="form-control" name="end_date" 
                                                    value="{{ $endDate }}" required
                                                    title="{{ __('accounting.end_date') }}">
                                            </div>
                                        </div>
                                        
                                        {{-- Specific Category --}}
                                        <div class="col-md-12 col-lg-4">
                                            <label class="form-label fw-semibold">{{ __('accounting.specific_category') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-category fs-2"></i>
                                                </span>
                                                <select class="form-select" name="category_id">
                                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}" 
                                                                {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                            @if($category->code)
                                                                <small class="text-muted">({{ $category->code }})</small>
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="col-md-12 col-lg-4 d-flex align-items-end justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('accounting.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.expenses.by-category') }}" class="btn btn-light btn-active-light-primary">
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
                                        ['key' => 'category_count', 'color' => 'primary', 'icon' => 'ki-category', 'label' => 'total_categories', 'value' => $categoryBreakdown->count()],
                                        ['key' => 'total_expenses', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'total_expenses', 'value' => $categoryBreakdown->sum('expense_count')],
                                        ['key' => 'grand_total', 'color' => 'info', 'icon' => 'ki-chart-simple', 'label' => 'grand_total_amount', 'value' => '$' . number_format($totalExpenses, 2)],
                                        ['key' => 'total_tax', 'color' => 'warning', 'icon' => 'ki-receipt-tax', 'label' => 'total_tax', 'value' => '$' . number_format($categoryBreakdown->sum('total_tax'), 2)],
                                        ['key' => 'avg_amount', 'color' => 'danger', 'icon' => 'ki-calculator', 'label' => 'average_per_category', 'value' => '$' . number_format($categoryBreakdown->avg('average_amount'), 2)],
                                        ['key' => 'top_category', 'color' => 'secondary', 'icon' => 'ki-ranking', 'label' => 'top_category', 'value' => $categoryBreakdown->first()->category_name ?? 'N/A']
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
                                                        <span class="text-gray-800 fw-semibold">${{ number_format($category->total_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-info">${{ number_format($category->total_tax, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($category->grand_total, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">${{ number_format($category->average_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger">${{ number_format($category->max_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-secondary">${{ number_format($category->min_amount, 2) }}</span>
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
                                        @if(request()->hasAny(['start_date', 'end_date', 'category_id']))
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
                @if($monthlyTrend->count() > 0)
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
                                                @foreach($monthlyTrend->keys() as $categoryName)
                                                <th>{{ $categoryName }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                // Get unique months
                                                $months = [];
                                                foreach($monthlyTrend as $categoryData) {
                                                    foreach($categoryData as $month) {
                                                        $key = $month->year . '-' . str_pad($month->month, 2, '0', STR_PAD_LEFT);
                                                        $months[$key] = [
                                                            'year' => $month->year,
                                                            'month' => $month->month,
                                                            'label' => date('M Y', strtotime($key . '-01'))
                                                        ];
                                                    }
                                                }
                                                ksort($months);
                                            @endphp
                                            
                                            @foreach($months as $monthData)
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $monthData['label'] }}</td>
                                                @foreach($monthlyTrend->keys() as $categoryName)
                                                    @php
                                                        $monthlyTotal = $monthlyTrend[$categoryName]->firstWhere('year', $monthData['year'])->monthly_total ?? 0;
                                                    @endphp
                                                <td class="text-end">
                                                    <span class="text-gray-800 fw-semibold">${{ number_format($monthlyTotal, 2) }}</span>
                                                </td>
                                                @endforeach
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold text-gray-800 bg-light">
                                                <td class="ps-4">{{ __('accounting.total') }}</td>
                                                @foreach($monthlyTrend->keys() as $categoryName)
                                                    @php
                                                        $categoryTotal = $categoryBreakdown->firstWhere('category_name', $categoryName)->grand_total ?? 0;
                                                    @endphp
                                                <td class="text-end">
                                                    <span class="text-success fw-bold">${{ number_format($categoryTotal, 2) }}</span>
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

@push('scripts')
@if($monthlyTrend->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for chart
        const categories = @json($monthlyTrend->keys());
        const months = @json(array_values($months));
        const seriesData = [];
        
        categories.forEach((category, index) => {
            const data = months.map(month => {
                const monthData = @json($monthlyTrend);
                const total = monthData[category]?.find(m => 
                    m.year == month.year && m.month == month.month
                )?.monthly_total || 0;
                return total;
            });
            
            seriesData.push({
                name: category,
                data: data,
                type: 'line',
                color: getCategoryColor(index)
            });
        });
        
        const monthLabels = months.map(m => m.label);
        
        // Initialize chart
        const chartOptions = {
            series: seriesData,
            chart: {
                type: 'line',
                height: 400,
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
            stroke: {
                width: [3, 3, 3],
                curve: 'smooth'
            },
            xaxis: {
                categories: monthLabels,
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Amount ($)'
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
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center',
                fontSize: '14px',
                fontFamily: 'Helvetica, Arial',
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            },
            markers: {
                size: 5
            },
            grid: {
                borderColor: '#f1f1f1',
            }
        };
        
        const chart = new ApexCharts(document.querySelector("#monthlyTrendChart"), chartOptions);
        chart.render();
        
        // Function to get category color
        function getCategoryColor(index) {
            const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'];
            return colors[index % colors.length];
        }
    });
</script>
@endif

@endpush

@endsection