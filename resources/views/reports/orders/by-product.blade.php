{{-- resources/views/reports/orders/by-product.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.sales_by_product'))

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
                                {{ __('auth.sales_by_product') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.sales_by_product') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($productSales->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'productSalesTable', filename: 'sales_by_product_{{ date('Y_m_d') }}', sheetName: 'Product Sales'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'productSalesTable', filename: 'sales_by_product_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.orders.by-product') }}" id="filterForm">
                                    <div class="row g-6 mb-6">
                                        {{-- Date Range --}}
                                        <div class="col-md-6 col-lg-4">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="start_date" 
                                                    value="{{ $startDate }}" required
                                                    title="{{ __('auth.start_date') }}">
                                                <span class="input-group-text bg-light">{{ __('accounting.to') }}</span>
                                                <input type="date" class="form-control" name="end_date" 
                                                    value="{{ $endDate }}" required
                                                    title="{{ __('auth.end_date') }}">
                                            </div>
                                        </div>
                                        
                                        {{-- Location --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-location fs-2"></i>
                                                </span>
                                                <select class="form-select" name="location_id">
                                                    <option value="">{{ __('auth.all_locations') }}</option>
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}" 
                                                                {{ $locationId == $location->id ? 'selected' : '' }}>
                                                            {{ $location->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Department --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.department') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-building fs-2"></i>
                                                </span>
                                                <select class="form-select" name="department_id">
                                                    <option value="">{{ __('auth.all_departments') }}</option>
                                                    @foreach($departments as $department)
                                                        <option value="{{ $department->id }}" 
                                                                {{ $departmentId == $department->id ? 'selected' : '' }}>
                                                            {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Quantity Range --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.min_quantity') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-box fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="min_quantity" 
                                                    value="{{ $minQuantity }}" 
                                                    placeholder="0"
                                                    step="1" min="0">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.max_quantity') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-box fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="max_quantity" 
                                                    value="{{ $maxQuantity }}" 
                                                    placeholder="10000"
                                                    step="1" min="0">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-6 mb-6">
                                        {{-- Revenue Range --}}
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label fw-semibold">{{ __('auth.min_revenue') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-dollar fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="min_revenue" 
                                                    value="{{ $minRevenue }}" 
                                                    placeholder="0.00"
                                                    step="0.01" min="0">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label fw-semibold">{{ __('auth.max_revenue') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-dollar fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="max_revenue" 
                                                    value="{{ $maxRevenue }}" 
                                                    placeholder="1000000.00"
                                                    step="0.01" min="0">
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="col-md-6 col-lg-6 d-flex align-items-end justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('accounting.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.orders.by-product') }}" class="btn btn-light btn-active-light-primary">
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

                {{-- Product Performance Summary --}}
                @if($productSales->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.product_performance_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $totalRevenue = $productSales->sum('total_revenue');
                                        $totalQuantity = $productSales->sum('total_quantity_sold');
                                        $avgDailySales = $totalQuantity / max($daysInPeriod, 1);
                                        $topProduct = $productSales->first();
                                    @endphp
                                    
                                    @foreach([
                                        ['key' => 'total_products', 'color' => 'primary', 'icon' => 'ki-box', 'label' => 'total_products', 'value' => $productSales->count()],
                                        ['key' => 'total_revenue', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'total_revenue', 'value' => '$' . number_format($totalRevenue, 2)],
                                        ['key' => 'total_quantity', 'color' => 'info', 'icon' => 'ki-box-tick', 'label' => 'total_quantity_sold', 'value' => number_format($totalQuantity)],
                                        ['key' => 'avg_daily_sales', 'color' => 'warning', 'icon' => 'ki-chart-line', 'label' => 'average_daily_sales', 'value' => number_format($avgDailySales, 1)],
                                        ['key' => 'top_product', 'color' => 'danger', 'icon' => 'ki-crown', 'label' => 'top_product', 'value' => $topProduct ? substr($topProduct->variant_name, 0, 15) . '...' : 'N/A'],
                                        ['key' => 'top_revenue', 'color' => 'secondary', 'icon' => 'ki-dollar-circle', 'label' => 'top_product_revenue', 'value' => $topProduct ? '$' . number_format($topProduct->total_revenue, 2) : '$0.00']
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
                @endif

                {{-- Top Products Chart --}}
                @if($topProducts->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_products_by_revenue') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="topProductsChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Product Velocity Analysis --}}
                @if($productSales->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-speedometer fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.sales_velocity_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $fastMovers = $productSales->where('velocity_category', 'Fast Mover')->count();
                                        $mediumMovers = $productSales->where('velocity_category', 'Medium Mover')->count();
                                        $slowMovers = $productSales->where('velocity_category', 'Slow Mover')->count();
                                        $totalProducts = $productSales->count();
                                    @endphp
                                    
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-rocket fs-2tx text-success">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">{{ $fastMovers }}</span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">{{ __('auth.fast_movers') }}</div>
                                                <div class="mt-2">
                                                    <span class="badge badge-light-success">
                                                        {{ $totalProducts > 0 ? number_format(($fastMovers / $totalProducts) * 100, 1) : 0 }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-truck fs-2tx text-warning">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">{{ $mediumMovers }}</span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">{{ __('auth.medium_movers') }}</div>
                                                <div class="mt-2">
                                                    <span class="badge badge-light-warning">
                                                        {{ $totalProducts > 0 ? number_format(($mediumMovers / $totalProducts) * 100, 1) : 0 }}%
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone ki-clock fs-2tx text-danger">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">{{ $slowMovers }}</span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">{{ __('auth.slow_movers') }}</div>
                                                <div class="mt-2">
                                                    <span class="badge badge-light-danger">
                                                        {{ $totalProducts > 0 ? number_format(($slowMovers / $totalProducts) * 100, 1) : 0 }}%
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
                @endif

                {{-- Product Sales Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.product_sales_report') }}</h3>
                                    </div>
                                    @if($productSales->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $productSales->count() }} {{ __('auth.products') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($productSales->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="productSalesTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-50px ps-4">{{ __('accounting.rank') }}</th>
                                                    <th class="min-w-200px">{{ __('auth.product') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.sku') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.quantity_sold') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.revenue_generated') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.total_tax') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.total_discount') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.average_amount') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.times_ordered') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.sales_velocity') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.last_sold_date') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.percentage') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($productSales as $index => $product)
                                                @php
                                                    $totalRevenueAll = $productSales->sum('total_revenue');
                                                    $percentage = $totalRevenueAll > 0 ? ($product->total_revenue / $totalRevenueAll) * 100 : 0;
                                                    $velocityColors = [
                                                        'Fast Mover' => 'success',
                                                        'Medium Mover' => 'warning',
                                                        'Slow Mover' => 'danger'
                                                    ];
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
                                                                <div class="symbol-label bg-light-{{ $velocityColors[$product->velocity_category] ?? 'secondary' }}">
                                                                    <i class="ki-duotone ki-box fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $product->variant_name }}</span>
                                                                <span class="badge badge-light-{{ $velocityColors[$product->velocity_category] ?? 'secondary' }} badge-sm mt-1">
                                                                    {{ $product->velocity_category }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">{{ $product->sku }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-primary">{{ number_format($product->total_quantity_sold) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($product->total_revenue, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-info">${{ number_format($product->total_tax, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-warning">${{ number_format($product->total_discount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">${{ number_format($product->average_selling_price, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-info">{{ $product->order_count }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge badge-light-{{ $velocityColors[$product->velocity_category] ?? 'secondary' }} me-2">
                                                                {{ number_format($product->daily_sales_rate, 1) }}/day
                                                            </span>
                                                            @if($product->daily_sales_rate > 5)
                                                            <i class="ki-duotone ki-arrow-up-right fs-2 text-success"></i>
                                                            @elseif($product->daily_sales_rate < 1)
                                                            <i class="ki-duotone ki-arrow-down-right fs-2 text-danger"></i>
                                                            @else
                                                            <i class="ki-duotone ki-minus fs-2 text-gray-400"></i>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($product->last_sold_date)
                                                        <span class="text-gray-700">{{ \Carbon\Carbon::parse($product->last_sold_date)->format('M d, Y') }}</span>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress w-100 me-3" style="height: 8px;">
                                                                <div class="progress-bar bg-{{ $velocityColors[$product->velocity_category] ?? 'secondary' }}" 
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
                                        <p class="text-muted fs-6">{{ __('auth.no_products_found_for_period') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'location_id', 'department_id', 'min_quantity', 'max_quantity', 'min_revenue', 'max_revenue']))
                                        <a href="{{ route('reports.orders.by-product') }}" class="btn btn-light-primary">
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
                
                {{-- Top vs Bottom Products --}}
                @if($topProducts->count() > 0 && $bottomProducts->count() > 0)
                <div class="row mt-6">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-crown fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_performers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-success">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('auth.product') }}</th>
                                                <th>{{ __('auth.revenue_generated') }}</th>
                                                <th>{{ __('auth.quantity_sold') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topProducts->take(5) as $index => $product)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-gray-800">{{ $index + 1 }}</span>
                                                </td>
                                                <td>{{ substr($product->variant_name, 0, 25) }}{{ strlen($product->variant_name) > 25 ? '...' : '' }}</td>
                                                <td><span class="fw-bold text-success">${{ number_format($product->total_revenue, 2) }}</span></td>
                                                <td><span class="badge badge-light-primary">{{ number_format($product->total_quantity_sold) }}</span></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-clock fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.bottom_performers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('auth.product') }}</th>
                                                <th>{{ __('auth.revenue_generated') }}</th>
                                                <th>{{ __('auth.quantity_sold') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($bottomProducts->take(5) as $index => $product)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-gray-800">{{ $productSales->count() - $index }}</span>
                                                </td>
                                                <td>{{ substr($product->variant_name, 0, 25) }}{{ strlen($product->variant_name) > 25 ? '...' : '' }}</td>
                                                <td><span class="fw-bold text-danger">${{ number_format($product->total_revenue, 2) }}</span></td>
                                                <td><span class="badge badge-light-primary">{{ number_format($product->total_quantity_sold) }}</span></td>
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

@push('scripts')
@if($topProducts->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Top Products Chart
        const topProductsData = @json($topProducts->take(10));
        const productNames = topProductsData.map(product => product.variant_name.substring(0, 15) + '...');
        const productRevenue = topProductsData.map(product => parseFloat(product.total_revenue));
        const productQuantity = topProductsData.map(product => parseFloat(product.total_quantity_sold));
        
        const topProductsChart = new ApexCharts(document.querySelector("#topProductsChart"), {
            series: [{
                name: 'Revenue',
                data: productRevenue,
                type: 'bar'
            }, {
                name: 'Quantity',
                data: productQuantity,
                type: 'line'
            }],
            chart: {
                type: 'bar',
                height: 400,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '60%'
                }
            },
            stroke: {
                width: [0, 3]
            },
            xaxis: {
                categories: productNames,
                labels: {
                    rotate: -45
                }
            },
            yaxis: [{
                title: {
                    text: 'Revenue ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            }, {
                opposite: true,
                title: {
                    text: 'Quantity'
                }
            }],
            colors: ['#3E97FF', '#50CD89'],
            tooltip: {
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 0) {
                            return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                        return val.toLocaleString();
                    }
                }
            }
        });
        topProductsChart.render();
    });
</script>
@endif

<script>
    // Form validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const startDate = new Date(document.querySelector('[name="start_date"]').value);
        const endDate = new Date(document.querySelector('[name="end_date"]').value);
        const minQuantity = parseInt(document.querySelector('[name="min_quantity"]').value) || 0;
        const maxQuantity = parseInt(document.querySelector('[name="max_quantity"]').value) || 0;
        const minRevenue = parseFloat(document.querySelector('[name="min_revenue"]').value) || 0;
        const maxRevenue = parseFloat(document.querySelector('[name="max_revenue"]').value) || 0;
        
        if (startDate > endDate) {
            e.preventDefault();
            alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
            return false;
        }
        
        if (minQuantity > 0 && maxQuantity > 0 && minQuantity > maxQuantity) {
            e.preventDefault();
            alert('{{ __("auth.min_quantity_cannot_exceed_max_quantity") }}');
            return false;
        }
        
        if (minRevenue > 0 && maxRevenue > 0 && minRevenue > maxRevenue) {
            e.preventDefault();
            alert('{{ __("auth.min_revenue_cannot_exceed_max_revenue") }}');
            return false;
        }
    });
</script>
@endpush

@endsection