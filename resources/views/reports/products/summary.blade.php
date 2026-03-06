@extends('layouts.app')

@section('title', __('auth.product_summary'))

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
                                {{ __('auth.product_summary') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($summary['total_products'] > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'productSummaryTable', filename: 'product_summary_{{ date('Y_m_d') }}', sheetName: 'Product Summary'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'productSummaryTable', filename: 'product_summary_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.products.summary') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-6">
                                        {{-- Category --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group w-100">
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
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.product_type') }}</label>
                                            <div class="input-group w-100">
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
                                        
                                        {{-- Status --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.status') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-status fs-2"></i>
                                                </span>
                                                <select class="form-select" name="is_active">
                                                    <option value="">{{ __('auth.all_statuses') }}</option>
                                                    <option value="1" {{ $isActive === '1' ? 'selected' : '' }}>{{ __('auth.active') }}</option>
                                                    <option value="0" {{ $isActive === '0' ? 'selected' : '' }}>{{ __('auth.inactive') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Tax Status --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.tax_status') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-dollar fs-2"></i>
                                                </span>
                                                <select class="form-select" name="is_taxable">
                                                    <option value="">{{ __('auth.all_tax_statuses') }}</option>
                                                    <option value="1" {{ $isTaxable === '1' ? 'selected' : '' }}>{{ __('auth.taxable') }}</option>
                                                    <option value="0" {{ $isTaxable === '0' ? 'selected' : '' }}>{{ __('auth.non_taxable') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.products.summary') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.clear') }}</span>
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
                @if($summary['total_products'] > 0)
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
                                    @foreach([
                                        ['key' => 'total_products', 'color' => 'primary', 'icon' => 'ki-package', 'label' => 'total_products', 'value' => $summary['total_products']],
                                        ['key' => 'total_variants', 'color' => 'success', 'icon' => 'ki-abstract-44', 'label' => 'total_variants', 'value' => $summary['total_variants']],
                                        ['key' => 'total_stock', 'color' => 'info', 'icon' => 'ki-inbox', 'label' => 'total_stock', 'value' => $summary['total_stock']],
                                        ['key' => 'average_price', 'color' => 'warning', 'icon' => 'ki-dollar', 'label' => 'average_price', 'value' => '$' . number_format($summary['average_price'], 2)],
                                        ['key' => 'average_cost', 'color' => 'danger', 'icon' => 'ki-money', 'label' => 'average_cost', 'value' => '$' . number_format($summary['average_cost'], 2)],
                                        ['key' => 'active_products', 'color' => 'secondary', 'icon' => 'ki-check', 'label' => 'active', 'value' => $summary['active_products']]
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

                {{-- Distribution Charts --}}
                <div class="row mb-6">
                    {{-- Category Distribution --}}
                    @if($categoryBreakdown->count() > 0)
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.category_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="categoryChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    {{-- Type Distribution --}}
                    @if($typeBreakdown->count() > 0)
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.type_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="typeChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Products Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.products') }}</h3>
                                    </div>
                                    @if($products->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $products->firstItem() }}-{{ $products->lastItem() }} {{ __('accounting.of') }} {{ $products->total() }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="productSummaryTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('auth.sku') }}</th>
                                                <th>{{ __('accounting.name') }}</th>
                                                <th>{{ __('accounting.category') }}</th>
                                                <th>{{ __('accounting.type') }}</th>
                                                <th>{{ __('auth.status') }}</th>
                                                <th>{{ __('auth.tax_status') }}</th>
                                                <th>{{ __('auth.variant_count') }}</th>
                                                <th>{{ __('auth.created_at') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($products as $product)
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
                                                            @if($product->description)
                                                            <div class="text-muted fs-7">{{ Str::limit($product->description, 50) }}</div>
                                                            @endif
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
                                                    <span class="badge badge-light-{{ $product->is_active ? 'success' : 'danger' }}">
                                                        {{ $product->is_active ? __('auth.active') : __('auth.inactive') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $product->is_taxable ? 'primary' : 'secondary' }}">
                                                        {{ $product->is_taxable ? __('auth.taxable') : __('auth.non_taxable') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $product->variants->count() }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $product->created_at->format('Y-m-d') }}</span>
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
                                            {{ __('auth.showing') }} {{ $products->firstItem() }}-{{ $products->lastItem() }} {{ __('auth.of') }} {{ $products->total() }}
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
                                        @if(request()->hasAny(['category_id', 'product_type', 'is_active', 'is_taxable']))
                                        <a href="{{ route('reports.products.summary') }}" class="btn btn-light-primary">
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

<!-- {{-- Debug Section (Remove after testing) --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Debug Data</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Category Breakdown:</h6>
                        <pre>{{ json_encode($categoryBreakdown, JSON_PRETTY_PRINT) }}</pre>
                        <p>Count: {{ $categoryBreakdown->count() }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Type Breakdown:</h6>
                        <pre>{{ json_encode($typeBreakdown, JSON_PRETTY_PRINT) }}</pre>
                        <p>Count: {{ $typeBreakdown->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->

@push('scripts')
@if($categoryBreakdown->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Category Distribution Chart - Only render if we have data
        @if($categoryBreakdown->count() > 0)
        const categoryData = {
            labels: @json($categoryBreakdown->pluck('name')),
            series: @json($categoryBreakdown->pluck('product_count'))
        };
        
        // Log data for debugging
        console.log('Category Data:', categoryData);
        
        if (categoryData.labels.length > 0 && categoryData.series.length > 0) {
            const categoryChart = new ApexCharts(document.querySelector("#categoryChart"), {
                series: categoryData.series,
                chart: {
                    type: 'pie',
                    height: 300,
                    toolbar: {
                        show: false
                    }
                },
                labels: categoryData.labels,
                colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'],
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return value + ' products';
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
            categoryChart.render();
        } else {
            document.querySelector("#categoryChart").innerHTML = 
                '<div class="text-center py-10"><p class="text-muted">No category data available</p></div>';
        }
        @endif
        
        // Type Distribution Chart - Only render if we have data
        @if($typeBreakdown->count() > 0)
        const typeData = @json($typeBreakdown);
        
        console.log('Type Data:', typeData);
        
        if (typeData && typeData.length > 0) {
            const typeChart = new ApexCharts(document.querySelector("#typeChart"), {
                series: [{
                    name: 'Product Count',
                    data: typeData.map(item => item.count)
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        columnWidth: '60%'
                    }
                },
                xaxis: {
                    categories: typeData.map(item => item.type),
                    labels: {
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Product Count'
                    }
                },
                colors: ['#3E97FF'],
                tooltip: {
                    y: {
                        formatter: function(value) {
                            return value + ' products';
                        }
                    }
                },
                dataLabels: {
                    enabled: false
                }
            });
            typeChart.render();
        } else {
            document.querySelector("#typeChart").innerHTML = 
                '<div class="text-center py-10"><p class="text-muted">No type data available</p></div>';
        }
        @endif
    });
</script>
@else
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hide chart containers if no data
        const categoryChartEl = document.querySelector("#categoryChart");
        const typeChartEl = document.querySelector("#typeChart");
        
        if (categoryChartEl) {
            categoryChartEl.innerHTML = 
                '<div class="text-center py-10"><p class="text-muted">No category data available</p></div>';
        }
        
        if (typeChartEl) {
            typeChartEl.innerHTML = 
                '<div class="text-center py-10"><p class="text-muted">No type data available</p></div>';
        }
    });
</script>
@endif
@endpush

@endsection