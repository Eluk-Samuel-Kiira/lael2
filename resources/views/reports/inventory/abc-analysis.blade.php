{{-- resources/views/reports/inventory/abc-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.abc_analysis'))

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
                                {{ __('pagination.abc_analysis') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('pagination.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.abc_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($totalValue > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'abcAnalysisTable', filename: 'abc_analysis_{{ date('Y_m_d') }}', sheetName: 'ABC Analysis'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'abcAnalysisTable', filename: 'abc_analysis_{{ date('Y_m_d') }}', format: 'csv'})">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('pagination.export_to_csv') }}
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
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.inventory.abc-analysis') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap">
                                        {{-- Date Range --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('pagination.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group w-100">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="start_date" 
                                                        value="{{ $startDate }}" required
                                                        title="{{ __('pagination.start_date') }}">
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('pagination.to') }}</span>
                                                <span class="d-flex d-sm-none text-gray-500 text-center">{{ __('pagination.to') }}</span>
                                                <div class="input-group w-100">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="end_date" 
                                                        value="{{ $endDate }}" required
                                                        title="{{ __('pagination.end_date') }}">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Department --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.department') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-building fs-2"></i>
                                                </span>
                                                <select class="form-select" name="department_id">
                                                    <option value="">{{ __('pagination.all_departments') }}</option>
                                                    @foreach($departments as $department)
                                                        <option value="{{ $department->id }}" 
                                                                {{ $departmentId == $department->id ? 'selected' : '' }}>
                                                            {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('pagination.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('pagination.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.inventory.abc-analysis') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('pagination.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('pagination.clear') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ABC Analysis Summary --}}
                @if($totalValue > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.abc_analysis_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach(['A' => 'danger', 'B' => 'warning', 'C' => 'success'] as $category => $color)
                                    @php
                                        $categoryData = $abcCategories[$category];
                                        $iconMap = ['A' => 'ki-star', 'B' => 'ki-medal-star', 'C' => 'ki-moon'];
                                    @endphp
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card card-flush bg-light-{{ $color }} border border-{{ $color }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-3">
                                                    <i class="ki-duotone {{ $iconMap[$category] }} fs-2tx text-{{ $color }}">
                                                        @for($i = 1; $i <= 2; $i++)
                                                        <span class="path{{ $i }}"></span>
                                                        @endfor
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-2 fw-bold text-gray-800">
                                                        {{ __('pagination.abc_category_' . strtolower($category)) }}
                                                    </span>
                                                </div>
                                                <div class="mb-2">
                                                    <span class="fs-3 fw-bold text-{{ $color }}">
                                                        ${{ number_format($categoryData['value'], 2) }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold fs-7">
                                                    {{ $categoryData['count'] }} {{ __('pagination.items') }} • 
                                                    {{ number_format($categoryData['percentage'], 1) }}% {{ __('pagination.of_total') }}
                                                </div>
                                                <div class="mt-3">
                                                    <div class="progress" style="height: 10px;">
                                                        <div class="progress-bar bg-{{ $color }}" 
                                                             style="width: {{ $categoryData['percentage'] }}%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                
                                {{-- Total Inventory Value --}}
                                <div class="row mt-6">
                                    <div class="col-12">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8">
                                                        <div class="d-flex align-items-center">
                                                            <i class="ki-duotone ki-dollar fs-2tx text-primary me-3">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                            <div>
                                                                <div class="fs-4 fw-bold text-gray-800">
                                                                    {{ __('pagination.total_inventory_value') }}
                                                                </div>
                                                                <div class="text-gray-600">
                                                                    {{ __('pagination.based_on_cost_price') }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 text-end">
                                                        <span class="fs-1 fw-bold text-primary">
                                                            ${{ number_format($totalValue, 2) }}
                                                        </span>
                                                    </div>
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

                {{-- Charts Section --}}
                @if($totalValue > 0)
                <div class="row mb-6">
                    {{-- ABC Value Distribution --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.abc_value_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="abcDistributionChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- ABC Item Count Distribution --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.abc_item_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="abcItemChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Pareto Chart --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.pareto_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="paretoChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ABC Analysis Table --}}
                @php
                    $allItems = collect();
                    foreach (['A', 'B', 'C'] as $category) {
                        $allItems = $allItems->merge($abcCategories[$category]['items'] ?? []);
                    }
                    $sortedItems = $allItems->sortByDesc('inventory_value');
                @endphp
                
                @if($sortedItems->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.abc_analysis_details') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $sortedItems->count() }} {{ __('pagination.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="abcAnalysisTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('pagination.abc_category') }}</th>
                                                <th>{{ __('pagination.sku') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.department') }}</th>
                                                <th>{{ __('pagination.inventory_value') }}</th>
                                                <th>{{ __('pagination.value_percentage') }}</th>
                                                <th>{{ __('pagination.cumulative_percentage') }}</th>
                                                <th>{{ __('pagination.total_movement') }}</th>
                                                <th>{{ __('pagination.recommendation') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sortedItems as $index => $item)
                                            @php
                                                $categoryColors = ['A' => 'danger', 'B' => 'warning', 'C' => 'success'];
                                                $categoryColor = $categoryColors[$item->abc_category] ?? 'dark';
                                                
                                                // Recommendations based on ABC category
                                                $recommendations = [
                                                    'A' => __('pagination.abc_recommendation_a'),
                                                    'B' => __('pagination.abc_recommendation_b'),
                                                    'C' => __('pagination.abc_recommendation_c')
                                                ];
                                                $recommendation = $recommendations[$item->abc_category] ?? '';
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $index + 1 }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $categoryColor }}">
                                                        {{ __('pagination.abc_category_' . strtolower($item->abc_category)) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $item->variant->sku ?? '-' }}</div>
                                                    <small class="text-muted">{{ $item->variant->barcode ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($item->variant->image_url ?? false)
                                                        <div class="symbol symbol-50px me-3">
                                                            <img src="{{ $item->variant->image_url }}" class="img-fluid" alt="{{ $item->variant->name }}">
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <div class="fw-bold">{{ $item->variant->name ?? '-' }}</div>
                                                            <div class="text-muted">{{ $item->variant->product->name ?? '' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $item->department->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-{{ $categoryColor }}">
                                                        ${{ number_format($item->inventory_value, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-dark">
                                                        {{ number_format($item->value_percentage, 1) }}%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $item->cumulative_percentage <= 80 ? 'text-success' : ($item->cumulative_percentage <= 95 ? 'text-warning' : 'text-info') }}">
                                                        {{ number_format($item->cumulative_percentage, 1) }}%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">
                                                        {{ number_format($item->total_movement) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-muted fs-7">{{ $recommendation }}</span>
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
                    {{-- No Data Message --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-center py-10">
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.no_inventory_items_for_abc_analysis') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'department_id']))
                                        <a href="{{ route('reports.inventory.abc-analysis') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('pagination.clear_filters_view_all') }}
                                        </a>
                                        @endif
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
@if($totalValue > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ABC Value Distribution Chart
        const abcValueData = [
            {{ $abcCategories['A']['value'] ?? 0 }},
            {{ $abcCategories['B']['value'] ?? 0 }},
            {{ $abcCategories['C']['value'] ?? 0 }}
        ];
        
        const abcDistributionChart = new ApexCharts(document.querySelector("#abcDistributionChart"), {
            series: abcValueData,
            chart: {
                type: 'donut',
                height: 350
            },
            labels: [
                '{{ __("pagination.abc_category_a") }}',
                '{{ __("pagination.abc_category_b") }}',
                '{{ __("pagination.abc_category_c") }}'
            ],
            colors: ['#F1416C', '#FFC700', '#50CD89'],
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2})
                    }
                }
            }
        });
        abcDistributionChart.render();
        
        // ABC Item Count Chart
        const abcItemCountData = [
            {{ $abcCategories['A']['count'] ?? 0 }},
            {{ $abcCategories['B']['count'] ?? 0 }},
            {{ $abcCategories['C']['count'] ?? 0 }}
        ];
        
        const abcItemChart = new ApexCharts(document.querySelector("#abcItemChart"), {
            series: [{
                name: 'Item Count',
                data: abcItemCountData
            }],
            chart: {
                type: 'bar',
                height: 350
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%'
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: ['A', 'B', 'C']
            },
            yaxis: {
                title: {
                    text: 'Number of Items'
                }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' items'
                    }
                }
            }
        });
        abcItemChart.render();
        
        // Pareto Chart (Cumulative Value)
        @php
            $paretoItems = $sortedItems->take(20); // Show top 20 items for clarity
            $paretoValues = $paretoItems->pluck('inventory_value')->toArray();
            $paretoCumulative = [];
            $cumulativeSum = 0;
            foreach ($paretoItems as $item) {
                $cumulativeSum += $item->inventory_value;
                $paretoCumulative[] = ($cumulativeSum / $totalValue) * 100;
            }
            $paretoLabels = $paretoItems->pluck('variant.name')->toArray();
        @endphp
        
        const paretoValues = @json($paretoValues);
        const paretoCumulative = @json($paretoCumulative);
        const paretoLabels = @json($paretoLabels);
        
        const paretoChart = new ApexCharts(document.querySelector("#paretoChart"), {
            series: [{
                name: 'Inventory Value',
                type: 'column',
                data: paretoValues
            }, {
                name: 'Cumulative %',
                type: 'line',
                data: paretoCumulative
            }],
            chart: {
                height: 400,
                type: 'line',
                toolbar: {
                    show: true
                }
            },
            stroke: {
                width: [0, 3]
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: paretoLabels,
                labels: {
                    rotate: -45,
                    trim: true
                }
            },
            yaxis: [{
                title: {
                    text: 'Value ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString()
                    }
                }
            }, {
                opposite: true,
                title: {
                    text: 'Cumulative %'
                },
                max: 100
            }],
            colors: ['#3E97FF', '#F1416C'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 0) {
                            return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                        return val.toFixed(1) + '%';
                    }
                }
            }
        });
        paretoChart.render();
    });
    
</script>
@endif

<script>
    // Form validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const startDate = new Date(document.querySelector('[name="start_date"]').value);
        const endDate = new Date(document.querySelector('[name="end_date"]').value);
        
        if (startDate > endDate) {
            e.preventDefault();
            alert('{{ __("pagination.start_date_cannot_be_after_end_date") }}');
            return false;
        }
    });
</script>
@endpush

@endsection