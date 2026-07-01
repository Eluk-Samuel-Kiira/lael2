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
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('auth._dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.summary') }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Filter Section --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.products.summary') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-6">
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text"><i class="ki-duotone ki-category fs-2"></i></span>
                                                <select class="form-select" name="category_id">
                                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.product_type') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text"><i class="ki-duotone ki-bag fs-2"></i></span>
                                                <select class="form-select" name="product_type">
                                                    <option value="all">{{ __('auth.all_types') }}</option>
                                                    <option value="physical" {{ $productType == 'physical' ? 'selected' : '' }}>{{ __('auth.physical') }}</option>
                                                    <option value="digital" {{ $productType == 'digital' ? 'selected' : '' }}>{{ __('auth.digital') }}</option>
                                                    <option value="service" {{ $productType == 'service' ? 'selected' : '' }}>{{ __('auth.service') }}</option>
                                                    <option value="composite" {{ $productType == 'composite' ? 'selected' : '' }}>{{ __('auth.composite') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.status') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text"><i class="ki-duotone ki-status fs-2"></i></span>
                                                <select class="form-select" name="is_active">
                                                    <option value="">{{ __('auth.all_statuses') }}</option>
                                                    <option value="1" {{ $isActive === '1' ? 'selected' : '' }}>{{ __('auth.active') }}</option>
                                                    <option value="0" {{ $isActive === '0' ? 'selected' : '' }}>{{ __('auth.inactive') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.tax_status') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text"><i class="ki-duotone ki-dollar fs-2"></i></span>
                                                <select class="form-select" name="is_taxable">
                                                    <option value="">{{ __('auth.all_tax_statuses') }}</option>
                                                    <option value="1" {{ $isTaxable === '1' ? 'selected' : '' }}>{{ __('auth.taxable') }}</option>
                                                    <option value="0" {{ $isTaxable === '0' ? 'selected' : '' }}>{{ __('auth.non_taxable') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1"><i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}</button>
                                                <a href="{{ route('reports.products.summary') }}" class="btn btn-light btn-active-light-primary flex-grow-1"><i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}</a>
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
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.summary_statistics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'total_products', 'color' => 'primary', 'icon' => 'ki-package', 'label' => 'total_products', 'value' => number_format($summary['total_products'])],
                                        ['key' => 'total_variants', 'color' => 'success', 'icon' => 'ki-abstract-44', 'label' => 'total_variants', 'value' => number_format($summary['total_variants'])],
                                        ['key' => 'total_stock', 'color' => 'info', 'icon' => 'ki-inbox', 'label' => 'total_stock', 'value' => number_format($summary['total_stock'])],
                                        ['key' => 'average_price', 'color' => 'warning', 'icon' => 'ki-dollar', 'label' => 'average_price', 'value' => '$' . number_format($summary['average_price'], 2)],
                                        ['key' => 'average_cost', 'color' => 'danger', 'icon' => 'ki-money', 'label' => 'average_cost', 'value' => '$' . number_format($summary['average_cost'], 2)],
                                        ['key' => 'active_products', 'color' => 'secondary', 'icon' => 'ki-check', 'label' => 'active', 'value' => number_format($summary['active_products'])]
                                    ] as $stat)
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}"></i></div>
                                                <div class="fs-1 fw-bold text-gray-800">{{ $stat['value'] }}</div>
                                                <div class="text-gray-600 fw-semibold">{{ __('auth.' . $stat['label']) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Distribution Row --}}
                <div class="row mb-6">
                    {{-- Category Distribution --}}
                    @if($categoryBreakdown->count() > 0)
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i> {{ __('auth.category_distribution') }}</h3>
                            </div>
                            <div class="card-body">
                                <div id="categoryChart" style="height: 280px;"></div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    {{-- Type Distribution --}}
                    @if($typeBreakdown->count() > 0)
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ki-duotone ki-chart-bar fs-2 me-2 text-info"></i> {{ __('auth.type_distribution') }}</h3>
                            </div>
                            <div class="card-body">
                                <div id="typeChart" style="height: 280px;"></div>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    {{-- Status Distribution --}}
                    @if($statusBreakdown->count() > 0)
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ki-duotone ki-status fs-2 me-2 text-success"></i> {{ __('auth.status_distribution') }}</h3>
                            </div>
                            <div class="card-body">
                                <div id="statusChart" style="height: 280px;"></div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Products Table --}}
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                    <h3 class="fw-bold m-0">{{ __('auth.products') }}</h3>
                </div>
            </div>
            
            @if($products->count() > 0)
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="productSummaryTable">
                        <thead class="bg-light">
                            <tr class="fw-bold fs-6 text-gray-800">
                                <th class="ps-4">{{ __('auth.sku') }}</th>
                                <th>{{ __('accounting.name') }}</th>
                                <th>{{ __('accounting.category') }}</th>
                                <th>{{ __('accounting.type') }}</th>
                                <th>{{ __('auth.status') }}</th>
                                <th>{{ __('auth.tax_status') }}</th>
                                <th class="text-center">{{ __('auth.variant_count') }}</th>
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
                                            <div class="fw-bold text-gray-800">{{ $product->name }}</div>
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
                                <td class="text-center">
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
            </div>
            <div class="card-footer">
                @include('partials.pagination', [
                    'paginator' => $products,
                    'pageName' => 'page',
                    'perPageName' => 'per_page',
                    'showPerPage' => true
                ])
            </div>
            @else
            <div class="card-body">
                <div class="text-center py-10">
                    <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                    <p class="text-muted fs-6">{{ __('auth.no_products_found') }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
                @else
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center py-10">
                                    <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                    <p class="text-muted fs-6">{{ __('auth.no_products_found') }}</p>
                                    @if(request()->hasAny(['category_id', 'product_type', 'is_active', 'is_taxable']))
                                    <a href="{{ route('reports.products.summary') }}" class="btn btn-light-primary"><i class="ki-duotone ki-cross fs-2 me-2"></i> {{ __('accounting.clear_filters_view_all') }}</a>
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        @if(isset($categoryBreakdown) && $categoryBreakdown->count() > 0)
        const categoryData = {
            labels: @json($categoryBreakdown->pluck('name')),
            series: @json($categoryBreakdown->pluck('product_count'))
        };
        if (categoryData.labels.length > 0 && categoryData.series.length > 0) {
            new ApexCharts(document.querySelector("#categoryChart"), {
                series: categoryData.series,
                chart: { type: 'pie', height: 280, toolbar: { show: false } },
                labels: categoryData.labels,
                colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A1A5B7'],
                legend: { position: 'bottom' },
                tooltip: { y: { formatter: function(v) { return v + ' products'; } } }
            }).render();
        }
        @endif
        
        @if(isset($typeBreakdown) && $typeBreakdown->count() > 0)
        const typeData = @json($typeBreakdown);
        if (typeData && typeData.length > 0) {
            new ApexCharts(document.querySelector("#typeChart"), {
                series: [{ data: typeData.map(item => item.count) }],
                chart: { type: 'bar', height: 280, toolbar: { show: false } },
                plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                xaxis: { categories: typeData.map(item => item.type) },
                colors: ['#3E97FF'],
                tooltip: { y: { formatter: function(v) { return v + ' products'; } } }
            }).render();
        }
        @endif
        
        @if(isset($statusBreakdown) && $statusBreakdown->count() > 0)
        const statusData = @json($statusBreakdown);
        if (statusData && statusData.length > 0) {
            new ApexCharts(document.querySelector("#statusChart"), {
                series: statusData.map(item => item.count),
                chart: { type: 'donut', height: 280, toolbar: { show: false } },
                labels: statusData.map(item => item.status),
                colors: statusData.map(item => item.color == 'success' ? '#50CD89' : '#F1416C'),
                legend: { position: 'bottom' },
                tooltip: { y: { formatter: function(v) { return v + ' products'; } } }
            }).render();
        }
        @endif
    });
</script>
@endpush

@push('styles')
<style>
    .card-flush:hover { transform: translateY(-2px); transition: all 0.2s ease; }
    .table > :not(caption) > * > * { padding: 1rem 0.75rem; vertical-align: middle; }
    @media (max-width: 768px) { .table-responsive { font-size: 0.85rem; } }
</style>
@endpush

@endsection