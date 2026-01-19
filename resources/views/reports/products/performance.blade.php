@extends('layouts.app')

@section('title', __('auth.product_performance'))

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
                                {{ __('auth.product_performance') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_performance') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($products->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'performanceTable', filename: 'product_performance_{{ date('Y_m_d') }}', sheetName: 'Product Performance'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'performanceTable', filename: 'product_performance_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.products.performance') }}" id="filterForm">
                                    <div class="row g-6 mb-6">
                                        {{-- Category --}}
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-category fs-2"></i>
                                                </span>
                                                <select class="form-select" name="category_id">
                                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}" 
                                                                {{ $categoryId == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Product Type --}}
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label fw-semibold">{{ __('auth.product_type') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-bag fs-2"></i>
                                                </span>
                                                <select class="form-select" name="product_type">
                                                    <option value="all">{{ __('auth.all_types') }}</option>
                                                    <option value="physical" {{ $productType == 'physical' ? 'selected' : '' }}>{{ __('auth.physical') }}</option>
                                                    <option value="digital" {{ $productType == 'digital' ? 'selected' : '' }}>{{ __('auth.digital') }}</option>
                                                    <option value="service" {{ $productType == 'service' ? 'selected' : '' }}>{{ __('auth.service') }}</option>
                                                    <option value="composite" {{ $productType == 'composite' ? 'selected' : '' }}>{{ __('auth.composite') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Action Buttons --}}
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('accounting.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.products.performance') }}" class="btn btn-light btn-active-light-primary">
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

                {{-- Performance Metrics Summary --}}
                @if($products->count() > 0)
                @php
                    $totalProducts = $products->count();
                    $totalStock = $products->sum('total_stock');
                    $totalCostValue = $products->sum('total_cost_value');
                    $totalRevenueValue = $products->sum('total_revenue_value');
                    $totalMargin = $products->sum('total_margin');
                    $avgMarginPercentage = $products->avg('margin_percentage');
                    $topProduct = $sortedProducts->first();
                    $bottomProduct = $sortedProducts->last();
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
                                    <h3 class="fw-bold m-0">{{ __('auth.performance_metrics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'total_products', 'color' => 'primary', 'icon' => 'ki-package', 'label' => 'total_products', 'value' => $totalProducts],
                                        ['key' => 'total_stock', 'color' => 'success', 'icon' => 'ki-inbox', 'label' => 'total_stock', 'value' => $totalStock],
                                        ['key' => 'total_cost_value', 'color' => 'warning', 'icon' => 'ki-money', 'label' => 'total_cost_value', 'value' => '$' . number_format($totalCostValue, 2)],
                                        ['key' => 'total_revenue_value', 'color' => 'info', 'icon' => 'ki-dollar', 'label' => 'total_revenue_value', 'value' => '$' . number_format($totalRevenueValue, 2)],
                                        ['key' => 'total_margin', 'color' => 'danger', 'icon' => 'ki-growth', 'label' => 'total_margin', 'value' => '$' . number_format($totalMargin, 2)],
                                        ['key' => 'avg_margin', 'color' => 'secondary', 'icon' => 'ki-percentage', 'label' => 'average_margin', 'value' => number_format($avgMarginPercentage, 1) . '%']
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

                {{-- Performance Charts --}}
                <div class="row mb-6">
                    {{-- Margin Distribution Chart --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.margin_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="marginDistributionChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Top Performers Chart --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_performers') }} (Top 10)</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="topPerformersChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Top & Bottom Performers --}}
                <div class="row mb-6">
                    {{-- Top Performer --}}
                    @if($topProduct)
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-crown fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_performer') }}</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    @if($topProduct->image_url)
                                    <div class="symbol symbol-80px me-5">
                                        <img src="{{ asset($topProduct->image_url) }}" alt="{{ $topProduct->name }}" class="rounded">
                                    </div>
                                    @endif
                                    <div>
                                        <h4 class="fw-bold text-gray-800 mb-2">{{ $topProduct->name }}</h4>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="text-gray-600 fs-7">{{ __('auth.sku') }}</div>
                                                <div class="fw-bold">{{ $topProduct->sku }}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-gray-600 fs-7">{{ __('auth.margin_percentage') }}</div>
                                                <div class="fw-bold text-success">{{ number_format($topProduct->margin_percentage, 1) }}%</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-gray-600 fs-7">{{ __('auth.total_margin') }}</div>
                                                <div class="fw-bold">${{ number_format($topProduct->total_margin, 2) }}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-gray-600 fs-7">{{ __('auth.total_stock') }}</div>
                                                <div class="fw-bold">{{ $topProduct->total_stock }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    {{-- Bottom Performer --}}
                    @if($bottomProduct && $bottomProduct->id != $topProduct->id)
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-flag fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.needs_improvement') }}</h3>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    @if($bottomProduct->image_url)
                                    <div class="symbol symbol-80px me-5">
                                        <img src="{{ asset($bottomProduct->image_url) }}" alt="{{ $bottomProduct->name }}" class="rounded">
                                    </div>
                                    @endif
                                    <div>
                                        <h4 class="fw-bold text-gray-800 mb-2">{{ $bottomProduct->name }}</h4>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="text-gray-600 fs-7">{{ __('auth.sku') }}</div>
                                                <div class="fw-bold">{{ $bottomProduct->sku }}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-gray-600 fs-7">{{ __('auth.margin_percentage') }}</div>
                                                <div class="fw-bold text-danger">{{ number_format($bottomProduct->margin_percentage, 1) }}%</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-gray-600 fs-7">{{ __('auth.total_margin') }}</div>
                                                <div class="fw-bold">${{ number_format($bottomProduct->total_margin, 2) }}</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-gray-600 fs-7">{{ __('auth.total_stock') }}</div>
                                                <div class="fw-bold">{{ $bottomProduct->total_stock }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Products Performance Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.product_performance') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $products->firstItem() }}-{{ $products->lastItem() }} {{ __('accounting.of') }} {{ $products->total() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="performanceTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('auth.sku') }}</th>
                                                <th>{{ __('accounting.name') }}</th>
                                                <th>{{ __('accounting.category') }}</th>
                                                <th>{{ __('accounting.type') }}</th>
                                                <th>{{ __('auth.total_stock') }}</th>
                                                <th>{{ __('auth.total_cost_value') }}</th>
                                                <th>{{ __('auth.total_revenue_value') }}</th>
                                                <th>{{ __('auth.total_margin') }}</th>
                                                <th>{{ __('auth.margin_percentage') }}</th>
                                                <th>{{ __('accounting.performance') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sortedProducts as $product)
                                            @php
                                                // Determine performance rating
                                                if ($product->margin_percentage >= 50) {
                                                    $performance = 'excellent';
                                                    $performanceLabel = __('auth.excellent');
                                                    $performanceColor = 'success';
                                                } elseif ($product->margin_percentage >= 30) {
                                                    $performance = 'good';
                                                    $performanceLabel = __('auth.good');
                                                    $performanceColor = 'primary';
                                                } elseif ($product->margin_percentage >= 10) {
                                                    $performance = 'average';
                                                    $performanceLabel = __('auth.average');
                                                    $performanceColor = 'warning';
                                                } else {
                                                    $performance = 'poor';
                                                    $performanceLabel = __('auth.needs_improvement');
                                                    $performanceColor = 'danger';
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $product->sku }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($product->image_url)
                                                        <div class="symbol symbol-50px me-3">
                                                            <img src="{{ asset($product->image_url) }}" alt="{{ $product->name }}" class="rounded">
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <span class="fw-bold text-gray-800">{{ $product->name }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($product->category)
                                                    <span class="badge badge-light-info">{{ $product->category->name }}</span>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $product->type == 'physical' ? 'primary' : ($product->type == 'digital' ? 'success' : 'warning') }}">
                                                        {{ ucfirst($product->type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $product->total_stock }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-600">${{ number_format($product->total_cost_value, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-semibold">${{ number_format($product->total_revenue_value, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-{{ $product->total_margin >= 0 ? 'success' : 'danger' }} fw-bold">
                                                        ${{ number_format($product->total_margin, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $performanceColor }}">
                                                        {{ number_format($product->margin_percentage, 1) }}%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $performanceColor }}">
                                                        {{ $performanceLabel }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($products->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted fs-7">
                                            {{ __('accounting.showing') }} {{ $products->firstItem() }}-{{ $products->lastItem() }} {{ __('accounting.of') }} {{ $products->total() }}
                                        </div>
                                        <div>
                                            {{ $products->links() }}
                                        </div>
                                    </div>
                                </div>
                                @endif
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
                                        <p class="text-muted fs-6">{{ __('auth.no_products_found') }}</p>
                                        @if(request()->hasAny(['category_id', 'product_type']))
                                        <a href="{{ route('reports.products.performance') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('accounting.clear_filters_view_all') }}
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
@if($products->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for charts
        const topProducts = @json($sortedProducts->take(10)->map(function($product) {
            return [
                'name' => $product->name,
                'margin' => $product->margin_percentage,
                'total_margin' => $product->total_margin
            ];
        }));
        
        // Calculate margin categories in PHP and pass as JSON
        @php
            $excellentCount = $sortedProducts->where('margin_percentage', '>=', 50)->count();
            $goodCount = $sortedProducts->where('margin_percentage', '>=', 30)
                ->where('margin_percentage', '<', 50)
                ->count();
            $averageCount = $sortedProducts->where('margin_percentage', '>=', 10)
                ->where('margin_percentage', '<', 30)
                ->count();
            $poorCount = $sortedProducts->where('margin_percentage', '<', 10)->count();
        @endphp
        
        const marginCategories = {
            excellent: {{ $excellentCount }},
            good: {{ $goodCount }},
            average: {{ $averageCount }},
            poor: {{ $poorCount }}
        };
        
        // Margin Distribution Chart (Pie Chart)
        const marginDistributionChart = new ApexCharts(document.querySelector("#marginDistributionChart"), {
            series: [marginCategories.excellent, marginCategories.good, marginCategories.average, marginCategories.poor],
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
            labels: [
                '{{ __("auth.excellent") }} (≥50%)',
                '{{ __("auth.good") }} (30-49%)',
                '{{ __("auth.average") }} (10-29%)',
                '{{ __("auth.needs_improvement") }} (<10%)'
            ],
            colors: ['#50CD89', '#3E97FF', '#FFC700', '#F1416C'],
            legend: {
                position: 'bottom',
                fontSize: '12px'
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' {{ __("auth.product") }}' + (value !== 1 ? 's' : '');
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val, opts) {
                    return opts.w.config.series[opts.seriesIndex];
                }
            }
        });
        marginDistributionChart.render();
        
        // Top Performers Chart (Bar Chart)
        const topPerformersChart = new ApexCharts(document.querySelector("#topPerformersChart"), {
            series: [{
                name: '{{ __("auth.margin_percentage") }}',
                data: topProducts.map(product => product.margin)
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
                    horizontal: false,
                    borderRadius: 4,
                    columnWidth: '60%'
                }
            },
            xaxis: {
                categories: topProducts.map(product => {
                    // Shorten product names for display
                    return product.name.length > 15 ? 
                        product.name.substring(0, 15) + '...' : 
                        product.name;
                }),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '10px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: '{{ __("auth.margin_percentage") }} (%)'
                },
                labels: {
                    formatter: function(val) {
                        return val.toFixed(1) + '%';
                    }
                }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(value, { seriesIndex, dataPointIndex, w }) {
                        const product = topProducts[dataPointIndex];
                        return value.toFixed(1) + '%<br>' + 
                               '{{ __("auth.total_margin") }}: $' + product.total_margin.toFixed(2);
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                },
                offsetY: -20,
                style: {
                    fontSize: '10px',
                    colors: ["#333"]
                }
            }
        });
        topPerformersChart.render();
        
        // Add export functionality
        window.exportCurrentPage = function(options) {
            const { tableId, filename, sheetName, format = 'excel' } = options;
            const table = document.getElementById(tableId);
            
            if (!table) {
                alert('Table not found');
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