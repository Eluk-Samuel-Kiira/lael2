@extends('layouts.app')

@section('title', __('auth.by_category'))

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
                                {{ __('auth.by_category') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('auth._dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.by_category') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($categories->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'categoryTable', filename: 'products_by_category_{{ date('Y_m_d') }}', sheetName: 'Products by Category'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'categoryTable', filename: 'products_by_category_{{ date('Y_m_d') }}', format: 'csv'})">
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

                {{-- Category Summary --}}
                @if($categories->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.category_performance_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'total_categories', 'color' => 'primary', 'icon' => 'ki-category', 'label' => 'total_categories', 'value' => $categorySummary['total_categories']],
                                        ['key' => 'total_products', 'color' => 'success', 'icon' => 'ki-package', 'label' => 'total_products', 'value' => $categorySummary['total_products']],
                                        ['key' => 'total_variants', 'color' => 'info', 'icon' => 'ki-abstract-44', 'label' => 'total_variants', 'value' => $categorySummary['total_variants']],
                                        ['key' => 'total_stock', 'color' => 'warning', 'icon' => 'ki-inbox', 'label' => 'total_stock', 'value' => $categorySummary['total_stock']],
                                        ['key' => 'total_value', 'color' => 'danger', 'icon' => 'ki-dollar', 'label' => 'total_value', 'value' => '$' . number_format($categorySummary['total_value'], 2)],
                                        ['key' => 'average_margin', 'color' => 'secondary', 'icon' => 'ki-percentage', 'label' => 'average_margin', 'value' => number_format($categorySummary['average_margin'], 1) . '%']
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

                {{-- Category Charts --}}
                <div class="row mb-6">
                    {{-- Top Categories by Product Count --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_categories_by_products') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="topCategoriesChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Category Margin Distribution --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.category_value_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="valueDistributionChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Category Performance Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.category_performance') }}</h3>
                                    </div>
                                    @if($categories->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $categories->count() }} {{ __('auth.categories') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="categoryTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.name') }}</th>
                                                <th>{{ __('auth.product_count') }}</th>
                                                <th>{{ __('auth.variant_count') }}</th>
                                                <th>{{ __('auth.total_stock') }}</th>
                                                <th>{{ __('auth.total_cost_value') }}</th>
                                                <th>{{ __('auth.total_revenue_value') }}</th>
                                                <th>{{ __('auth.total_margin') }}</th>
                                                <th>{{ __('auth.margin_percentage') }}</th>
                                                <th>{{ __('auth.performance') }}</th>
                                                <th>{{ __('auth.ranking') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sortedCategories as $index => $category)
                                            @php
                                                // Determine performance rating
                                                if ($category->margin_percentage >= 50) {
                                                    $performance = 'excellent';
                                                    $performanceLabel = __('auth.excellent');
                                                    $performanceColor = 'success';
                                                } elseif ($category->margin_percentage >= 30) {
                                                    $performance = 'good';
                                                    $performanceLabel = __('auth.good');
                                                    $performanceColor = 'primary';
                                                } elseif ($category->margin_percentage >= 10) {
                                                    $performance = 'average';
                                                    $performanceLabel = __('auth.average');
                                                    $performanceColor = 'warning';
                                                } else {
                                                    $performance = 'poor';
                                                    $performanceLabel = __('auth.needs_improvement');
                                                    $performanceColor = 'danger';
                                                }
                                                
                                                // Determine ranking
                                                $ranking = $index + 1;
                                                if ($ranking === 1) {
                                                    $rankingColor = 'gold';
                                                    $rankingIcon = 'ki-crown';
                                                } elseif ($ranking === 2) {
                                                    $rankingColor = 'silver';
                                                    $rankingIcon = 'ki-medal-star';
                                                } elseif ($ranking === 3) {
                                                    $rankingColor = 'bronze';
                                                    $rankingIcon = 'ki-medal';
                                                } else {
                                                    $rankingColor = 'secondary';
                                                    $rankingIcon = 'ki-number';
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        @if($category->image_url)
                                                        <div class="symbol symbol-50px me-3">
                                                            <img src="{{ asset($category->image_url) }}" alt="{{ $category->name }}" class="rounded">
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <span class="fw-bold text-gray-800">{{ $category->name }}</span>
                                                            @if($category->description)
                                                            <div class="text-muted fs-7">{{ Str::limit($category->description, 50) }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $category->product_count }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $category->variant_count }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-success">{{ $category->total_stock }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-warning fw-semibold">${{ number_format($category->total_cost_value, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-info fw-semibold">${{ number_format($category->total_revenue_value, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-{{ $category->total_margin >= 0 ? 'success' : 'danger' }} fw-bold">
                                                        ${{ number_format($category->total_margin, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $performanceColor }}">
                                                        {{ number_format($category->margin_percentage, 1) }}%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $performanceColor }}">
                                                        {{ $performanceLabel }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($ranking <= 3)
                                                    <div class="d-flex align-items-center">
                                                        <i class="ki-duotone {{ $rankingIcon }} fs-2 me-2 text-{{ $rankingColor }}"></i>
                                                        <span class="fw-bold text-{{ $rankingColor }}">#{{ $ranking }}</span>
                                                    </div>
                                                    @else
                                                    <span class="badge badge-light-secondary">#{{ $ranking }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        {{-- Footer with totals --}}
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td class="text-end fw-bold">{{ __('auth.total') }}:</td>
                                                <td class="fw-bold">{{ $categorySummary['total_products'] }}</td>
                                                <td class="fw-bold">{{ $categorySummary['total_variants'] }}</td>
                                                <td class="fw-bold">{{ $categorySummary['total_stock'] }}</td>
                                                <td class="fw-bold text-warning">${{ number_format($categories->sum('total_cost_value'), 2) }}</td>
                                                <td class="fw-bold text-info">${{ number_format($categories->sum('total_revenue_value'), 2) }}</td>
                                                <td class="fw-bold text-{{ $categories->sum('total_margin') >= 0 ? 'success' : 'danger' }}">
                                                    ${{ number_format($categories->sum('total_margin'), 2) }}
                                                </td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
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
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('auth.no_categories_found') }}</p>
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
@if($categories->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for charts
        @php
            // Get top 10 categories by product count
            $topCategories = $sortedCategories->take(10);
            
            // Get top 10 categories by revenue value
            $topValueCategories = $sortedCategories->sortByDesc('total_revenue_value')->take(10)->values();
        @endphp
        
        // Top Categories by Product Count Chart (Bar Chart)
        const topCategoriesChart = new ApexCharts(document.querySelector("#topCategoriesChart"), {
            series: [{
                name: '{{ __("auth.product_count") }}',
                data: @json($topCategories->pluck('product_count'))
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: {
                    show: true,
                    tools: {
                        download: true
                    }
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            },
            xaxis: {
                categories: @json($topCategories->pluck('name')),
                labels: {
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: '{{ __("auth.number_of_products") }}'
                }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(value, { seriesIndex, dataPointIndex, w }) {
                        const category = @json($topCategories->values())[dataPointIndex];
                        return value + ' {{ __("auth.products") }}<br>' + 
                               '{{ __("auth.variants") }}: ' + category.variant_count + '<br>' +
                               '{{ __("auth.total_stock") }}: ' + category.total_stock;
                    }
                }
            },
            dataLabels: {
                enabled: false
            }
        });
        topCategoriesChart.render();
        
        // Category Value Distribution Chart (Pie Chart)
        const valueDistributionChart = new ApexCharts(document.querySelector("#valueDistributionChart"), {
            series: @json($topValueCategories->pluck('total_revenue_value')),
            chart: {
                type: 'pie',
                height: 300,
                toolbar: {
                    show: true,
                    tools: {
                        download: true
                    }
                }
            },
            labels: @json($topValueCategories->pluck('name')),
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A8A8A8', '#009EF7', '#FFA800', '#181C32', '#6D6D6D'],
            legend: {
                position: 'bottom',
                fontSize: '11px'
            },
            tooltip: {
                y: {
                    formatter: function(value, { seriesIndex, dataPointIndex, w }) {
                        const category = @json($topValueCategories)[dataPointIndex];
                        return '$' + value.toLocaleString(undefined, {minimumFractionDigits: 2}) + '<br>' +
                               '{{ __("auth.margin") }}: ' + category.margin_percentage.toFixed(1) + '%<br>' +
                               '{{ __("auth.products") }}: ' + category.product_count;
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val, opts) {
                    return opts.w.config.labels[opts.seriesIndex];
                }
            }
        });
        valueDistributionChart.render();
        
        // Add export functionality
        window.exportCurrentPage = function(options) {
            const { tableId, filename, sheetName, format = 'excel' } = options;
            const table = document.getElementById(tableId);
            
            if (!table) {
                alert('{{ __("accounting.table_not_found") }}');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    // Clean text - remove HTML tags and trim
                    let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").trim();
                    // Escape quotes and wrap in quotes if contains comma
                    if (text.includes(',') || text.includes('"')) {
                        text = '"' + text.replace(/"/g, '""') + '"';
                    }
                    row.push(text);
                }
                csv.push(row.join(","));
            }
            
            const csvContent = csv.join("\n");
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            
            if (navigator.msSaveBlob) {
                navigator.msSaveBlob(blob, filename + '.csv');
            } else {
                link.href = URL.createObjectURL(blob);
                link.setAttribute("download", filename + '.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        };
    });
</script>
@endif
@endpush

@endsection