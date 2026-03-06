@extends('layouts.app')

@section('title', __('auth.inventory_valuation'))

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
                                {{ __('auth.inventory_valuation') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.inventory_valuation') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($variants->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'inventoryTable', filename: 'inventory_valuation_{{ date('Y_m_d') }}', sheetName: 'Inventory Valuation'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'inventoryTable', filename: 'inventory_valuation_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.products.inventory') }}" id="filterForm">
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
                                        
                                        {{-- Stock Status --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.stock_status') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-status fs-2"></i>
                                                </span>
                                                <select class="form-select" name="stock_status">
                                                    <option value="">{{ __('accounting.all_statuses') }}</option>
                                                    <option value="low" {{ $stockStatus == 'low' ? 'selected' : '' }}>{{ __('auth.low_stock') }} (< 10)</option>
                                                    <option value="out" {{ $stockStatus == 'out' ? 'selected' : '' }}>{{ __('auth.out_of_stock') }} (0)</option>
                                                    <option value="overstock" {{ $stockStatus == 'overstock' ? 'selected' : '' }}>{{ __('auth.overstock') }} (> 100)</option>
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
                                                <a href="{{ route('reports.products.inventory') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- Valuation Summary --}}
                @if($variants->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.inventory_valuation_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'total_items', 'color' => 'primary', 'icon' => 'ki-abstract-44', 'label' => 'total_items', 'value' => $totalValuation['total_items']],
                                        ['key' => 'total_quantity', 'color' => 'success', 'icon' => 'ki-inbox', 'label' => 'total_quantity', 'value' => $totalValuation['total_quantity']],
                                        ['key' => 'total_cost_value', 'color' => 'warning', 'icon' => 'ki-money', 'label' => 'total_cost_value', 'value' => '$' . number_format($totalValuation['total_cost_value'], 2)],
                                        ['key' => 'total_revenue_value', 'color' => 'info', 'icon' => 'ki-dollar', 'label' => 'total_revenue_value', 'value' => '$' . number_format($totalValuation['total_revenue_value'], 2)],
                                        ['key' => 'total_potential_profit', 'color' => 'danger', 'icon' => 'ki-growth', 'label' => 'total_potential_profit', 'value' => '$' . number_format($totalValuation['total_potential_profit'], 2)],
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

                {{-- Stock Health Charts --}}
                <div class="row mb-6">
                    {{-- Stock Status Distribution --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.stock_health_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="stockHealthChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Value Distribution --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.value_by_stock_status') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="valueDistributionChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stock Health Summary --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-health fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.stock_health_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    {{-- Healthy Stock --}}
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-check fs-2tx text-success">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        @php
                                                            $healthyCount = $variants->where('stock_health', 'healthy')->count();
                                                            $healthyValue = $variants->where('stock_health', 'healthy')->sum('cost_value');
                                                        @endphp
                                                        {{ $healthyCount }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.healthy_stock') }}
                                                </div>
                                                <div class="text-success fs-7 mt-2">
                                                    ${{ number_format($healthyValue, 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Low Stock --}}
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-warning fs-2tx text-warning">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $totalValuation['low_stock_count'] }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.low_stock') }}
                                                </div>
                                                <div class="text-warning fs-7 mt-2">
                                                    @php
                                                        $lowStockValue = $variants->where('overal_quantity_at_hand', '<', 10)
                                                            ->where('overal_quantity_at_hand', '>', 0)
                                                            ->sum('cost_value');
                                                    @endphp
                                                    ${{ number_format($lowStockValue, 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Out of Stock --}}
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-cross fs-2tx text-danger">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $totalValuation['out_of_stock_count'] }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.out_of_stock') }}
                                                </div>
                                                <div class="text-danger fs-7 mt-2">
                                                    $0.00
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Overstock --}}
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-inbox fs-2tx text-info">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $totalValuation['overstock_count'] }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.overstock') }}
                                                </div>
                                                <div class="text-info fs-7 mt-2">
                                                    @php
                                                        $overstockValue = $variants->where('overal_quantity_at_hand', '>', 100)->sum('cost_value');
                                                    @endphp
                                                    ${{ number_format($overstockValue, 2) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Inventory Valuation Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.inventory_valuation') }}</h3>
                                    </div>
                                    @if($variants->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $variants->count() }} {{ __('auth.items') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-0">
                                @if($variants->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="inventoryTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('auth.sku') }}</th>
                                                <th>{{ __('accounting.name') }}</th>
                                                <th>{{ __('accounting.category') }}</th>
                                                <th>{{ __('auth.quantity') }}</th>
                                                <th>{{ __('auth.price') }}</th>
                                                <th>{{ __('auth.cost_price') }}</th>
                                                <th>{{ __('auth.cost_value') }}</th>
                                                <th>{{ __('auth.revenue_value') }}</th>
                                                <th>{{ __('auth.potential_profit') }}</th>
                                                <th>{{ __('auth.margin_percentage') }}</th>
                                                <th>{{ __('auth.stock_health') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sortedVariants as $variant)
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $variant->sku }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($variant->image_url)
                                                        <div class="symbol symbol-50px me-3">
                                                            <img src="{{ asset($variant->image_url) }}" alt="{{ $variant->name }}" class="rounded">
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <span class="fw-bold text-gray-800">{{ $variant->name }}</span>
                                                            @if($variant->product)
                                                            <div class="text-muted fs-7">{{ $variant->product->name }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($variant->product && $variant->product->category)
                                                    <span class="badge badge-light-info">{{ $variant->product->category->name }}</span>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $variant->stock_color }}">
                                                        {{ $variant->overal_quantity_at_hand }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-semibold">${{ number_format($variant->price, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-600">${{ number_format($variant->cost_price ?? 0, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-warning fw-semibold">${{ number_format($variant->cost_value, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-info fw-semibold">${{ number_format($variant->revenue_value, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-{{ $variant->potential_profit >= 0 ? 'success' : 'danger' }} fw-bold">
                                                        ${{ number_format($variant->potential_profit, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $variant->margin_percentage >= 30 ? 'success' : ($variant->margin_percentage >= 10 ? 'warning' : 'danger') }}">
                                                        {{ number_format($variant->margin_percentage, 1) }}%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-{{ $variant->stock_color }}">
                                                        {{ $variant->stock_status }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        {{-- Footer with totals --}}
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="6" class="text-end fw-bold">{{ __('auth.total') }}:</td>
                                                <td class="fw-bold text-warning">${{ number_format($totalValuation['total_cost_value'], 2) }}</td>
                                                <td class="fw-bold text-info">${{ number_format($totalValuation['total_revenue_value'], 2) }}</td>
                                                <td class="fw-bold text-{{ $totalValuation['total_potential_profit'] >= 0 ? 'success' : 'danger' }}">
                                                    ${{ number_format($totalValuation['total_potential_profit'], 2) }}
                                                </td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                @else
                                <div class="text-center py-10">
                                    <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                    <p class="text-muted fs-6">{{ __('auth.no_products_found') }}</p>
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
                                        @if(request()->hasAny(['category_id', 'stock_status']))
                                        <a href="{{ route('reports.products.inventory') }}" class="btn btn-light-primary">
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
@if($variants->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for charts
        @php
            // Calculate stock health distribution
            $healthyCount = $variants->where('stock_health', 'healthy')->count();
            $warningCount = $variants->where('stock_health', 'warning')->count();
            $criticalCount = $variants->where('stock_health', 'critical')->count();
            
            // Calculate value by stock status
            $healthyValue = $variants->where('stock_health', 'healthy')->sum('cost_value');
            $lowStockValue = $variants->where('overal_quantity_at_hand', '<', 10)
                ->where('overal_quantity_at_hand', '>', 0)
                ->sum('cost_value');
            $outOfStockValue = 0; // Always 0
            $overstockValue = $variants->where('overal_quantity_at_hand', '>', 100)->sum('cost_value');
        @endphp
        
        // Stock Health Distribution Chart (Pie Chart)
        const stockHealthChart = new ApexCharts(document.querySelector("#stockHealthChart"), {
            series: [{{ $healthyCount }}, {{ $warningCount }}, {{ $criticalCount }}],
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
                '{{ __("auth.healthy") }}',
                '{{ __("auth.warning") }}',
                '{{ __("auth.critical") }}'
            ],
            colors: ['#50CD89', '#FFC700', '#F1416C'],
            legend: {
                position: 'bottom',
                fontSize: '12px'
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' {{ __("auth.item") }}' + (value !== 1 ? 's' : '');
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
        stockHealthChart.render();
        
        // Value Distribution Chart (Bar Chart)
        const valueDistributionChart = new ApexCharts(document.querySelector("#valueDistributionChart"), {
            series: [{
                name: '{{ __("auth.stock_value") }}',
                data: [{{ $healthyValue }}, {{ $lowStockValue }}, {{ $outOfStockValue }}, {{ $overstockValue }}]
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
                categories: [
                    '{{ __("auth.healthy_stock") }}',
                    '{{ __("auth.low_stock") }}',
                    '{{ __("auth.out_of_stock") }}',
                    '{{ __("auth.overstock") }}'
                ],
                labels: {
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: '{{ __("auth.value") }} ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(value) {
                        return '$' + value.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            },
            dataLabels: {
                enabled: false
            }
        });
        valueDistributionChart.render();
        
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