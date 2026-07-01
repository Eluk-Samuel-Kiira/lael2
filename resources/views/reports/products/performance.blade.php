@extends('layouts.app')

@section('title', __('auth.product_performance'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">{{ __('auth.product_performance') }}</h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('auth._dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_performance') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($products->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportTableToExcel()"><i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i> {{ __('accounting.export_to_excel') }}</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" onclick="exportTableToCSV()"><i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i> {{ __('accounting.export_to_csv') }}</a></li>
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
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.products.performance') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap">
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
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary"><i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}</button>
                                                <a href="{{ route('reports.products.performance') }}" class="btn btn-light"><i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary Cards --}}
                @if($products->count() > 0)
                @php
                    $totalProducts = $products->total();
                    $totalStock = $products->sum('total_stock');
                    $totalRevenueValue = $products->sum('total_revenue_value');
                    $totalMargin = $products->sum('total_margin');
                    $avgMargin = $totalRevenueValue > 0 ? ($totalMargin / $totalRevenueValue) * 100 : 0;
                @endphp
                <div class="row mb-6">
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-package fs-2tx text-primary mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800">{{ number_format($totalProducts) }}</div>
                                <div class="text-gray-600">{{ __('auth.total_products') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-inbox fs-2tx text-success mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800">{{ number_format($totalStock) }}</div>
                                <div class="text-gray-600">{{ __('auth.total_stock') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-dollar fs-2tx text-info mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800">${{ number_format($totalRevenueValue, 2) }}</div>
                                <div class="text-gray-600">{{ __('auth.total_revenue_value') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-growth fs-2tx text-danger mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800">${{ number_format($totalMargin, 2) }}</div>
                                <div class="text-gray-600">{{ __('auth.total_margin') }} ({{ number_format($avgMargin, 1) }}%)</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts Row --}}
                <div class="row mb-6">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i> {{ __('auth.margin_distribution') }}</h3>
                            </div>
                            <div class="card-body">
                                <div id="marginDistributionChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i> {{ __('auth.top_performers') }} (Top 10)</h3>
                            </div>
                            <div class="card-body">
                                <div id="topPerformersChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Products Table --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.product_performance') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="performanceTable">
                                        <thead class="bg-light">
                                            <tr class="fw-bold fs-6 text-gray-800">
                                                <th class="ps-4">{{ __('auth.sku') }}</th>
                                                <th>{{ __('accounting.name') }}</th>
                                                <th>{{ __('accounting.category') }}</th>
                                                <th>{{ __('accounting.type') }}</th>
                                                <th class="text-center">{{ __('auth.total_stock') }}</th>
                                                <th class="text-end">{{ __('auth.total_cost_value') }}</th>
                                                <th class="text-end">{{ __('auth.total_revenue_value') }}</th>
                                                <th class="text-end">{{ __('auth.total_margin') }}</th>
                                                <th class="text-center">{{ __('auth.margin_percentage') }}</th>
                                                <th class="text-center">{{ __('accounting.performance') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($products as $product)
                                            @php
                                                if ($product->margin_percentage >= 50) {
                                                    $perfLabel = __('auth.excellent');
                                                    $perfColor = 'success';
                                                } elseif ($product->margin_percentage >= 30) {
                                                    $perfLabel = __('auth.good');
                                                    $perfColor = 'primary';
                                                } elseif ($product->margin_percentage >= 10) {
                                                    $perfLabel = __('auth.average');
                                                    $perfColor = 'warning';
                                                } else {
                                                    $perfLabel = __('auth.needs_improvement');
                                                    $perfColor = 'danger';
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
                                                        <div class="fw-bold text-gray-800">{{ $product->name }}</div>
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
                                                <td class="text-center"><span class="badge badge-light-primary">{{ number_format($product->total_stock) }}</span></td>
                                                <td class="text-end text-gray-600">${{ number_format($product->total_cost_value, 2) }}</td>
                                                <td class="text-end text-gray-800 fw-semibold">${{ number_format($product->total_revenue_value, 2) }}</td>
                                                <td class="text-end {{ $product->total_margin >= 0 ? 'text-success' : 'text-danger' }} fw-bold">${{ number_format($product->total_margin, 2) }}</td>
                                                <td class="text-center"><span class="badge badge-light-{{ $perfColor }}">{{ number_format($product->margin_percentage, 1) }}%</span></td>
                                                <td class="text-center"><span class="badge badge-{{ $perfColor }}">{{ $perfLabel }}</span></td>
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
                        </div>
                    </div>
                </div>
                @else
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-10">
                                <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600">{{ __('accounting.no_data_available') }}</h4>
                                <p class="text-muted">{{ __('auth.no_products_found') }}</p>
                                @if(request()->hasAny(['category_id', 'product_type']))
                                <a href="{{ route('reports.products.performance') }}" class="btn btn-light-primary"><i class="ki-duotone ki-cross fs-2 me-2"></i> {{ __('accounting.clear_filters_view_all') }}</a>
                                @endif
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
        @if($products->count() > 0)
        // Margin categories
        const excellent = {{ $products->filter(function($p) { return $p->margin_percentage >= 50; })->count() }};
        const good = {{ $products->filter(function($p) { return $p->margin_percentage >= 30 && $p->margin_percentage < 50; })->count() }};
        const average = {{ $products->filter(function($p) { return $p->margin_percentage >= 10 && $p->margin_percentage < 30; })->count() }};
        const poor = {{ $products->filter(function($p) { return $p->margin_percentage < 10; })->count() }};

        // Margin Distribution Chart
        new ApexCharts(document.querySelector("#marginDistributionChart"), {
            series: [excellent, good, average, poor],
            chart: { type: 'pie', height: 350, toolbar: { show: true } },
            labels: ['Excellent (≥50%)', 'Good (30-49%)', 'Average (10-29%)', 'Needs Improvement (<10%)'],
            colors: ['#50CD89', '#3E97FF', '#FFC700', '#F1416C'],
            legend: { position: 'bottom' },
            tooltip: { y: { formatter: function(v) { return v + ' products'; } } },
            dataLabels: { enabled: true }
        }).render();

        // Top Performers Chart
        const topProducts = @json($products->sortByDesc('margin_percentage')->take(10)->map(function($p) {
            return ['name' => Str::limit($p->name, 20), 'margin' => $p->margin_percentage];
        })->values());

        new ApexCharts(document.querySelector("#topPerformersChart"), {
            series: [{ name: 'Margin %', data: topProducts.map(p => p.margin) }],
            chart: { type: 'bar', height: 350, toolbar: { show: true } },
            plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
            xaxis: { categories: topProducts.map(p => p.name) },
            colors: ['#3E97FF'],
            tooltip: { y: { formatter: function(v) { return v.toFixed(1) + '%'; } } },
            dataLabels: { enabled: true, formatter: function(v) { return v.toFixed(1) + '%'; } }
        }).render();
        @endif
    });

    function exportTableToExcel() {
        const table = document.getElementById('performanceTable');
        const html = table.outerHTML;
        const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'product_performance_{{ date('Y-m-d') }}.xls';
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function exportTableToCSV() {
        const table = document.getElementById('performanceTable');
        const rows = table.querySelectorAll('tr');
        let csv = [];
        rows.forEach(row => {
            const rowData = [];
            row.querySelectorAll('td, th').forEach(cell => rowData.push('"' + cell.innerText.replace(/"/g, '""') + '"'));
            csv.push(rowData.join(','));
        });
        const blob = new Blob(["\uFEFF" + csv.join('\n')], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'product_performance_{{ date('Y-m-d') }}.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    }
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