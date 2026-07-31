{{-- resources/views/reports/orders/by-product.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.sales_by_product'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                
                {{-- ============================================================ --}}
                {{-- TOOLBAR SECTION --}}
                {{-- ============================================================ --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
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
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if(isset($productSales) && $productSales->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportTableToExcel('productSalesTable', 'sales_by_product')">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportTableToCSV('productSalesTable', 'sales_by_product')">
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

                {{-- ============================================================ --}}
                {{-- FILTER SECTION --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
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
                            <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-6">
                                {{-- Date Range --}}
                                <div class="flex-grow-1">
                                    <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <div class="input-group w-100">
                                            <span class="input-group-text">
                                                <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                            </span>
                                            <input type="date" class="form-control" name="start_date" 
                                                value="{{ $startDate }}" required
                                                title="{{ __('auth.start_date') }}">
                                        </div>
                                        <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('accounting.to') }}</span>
                                        <span class="d-flex d-sm-none text-gray-500 text-center">{{ __('accounting.to') }}</span>
                                        <div class="input-group w-100">
                                            <span class="input-group-text bg-light">
                                                <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                            </span>
                                            <input type="date" class="form-control" name="end_date" 
                                                value="{{ $endDate }}" required
                                                title="{{ __('auth.end_date') }}">
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Location --}}
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                    <div class="input-group w-100">
                                        <select class="form-select" name="location_id" data-control="select2">
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
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.department') }}</label>
                                    <div class="input-group w-100">
                                        <select class="form-select" name="department_id" data-control="select2">
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
                                
                                {{-- Min Quantity --}}
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.min_quantity') }}</label>
                                    <div class="input-group w-100">
                                        <span class="input-group-text">
                                            <i class="ki-duotone ki-box fs-2"></i>
                                        </span>
                                        <input type="number" class="form-control" name="min_quantity" 
                                            value="{{ $minQuantity }}" 
                                            placeholder="0"
                                            step="1" min="0">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap">
                                {{-- Max Quantity --}}
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.max_quantity') }}</label>
                                    <div class="input-group w-100">
                                        <span class="input-group-text">
                                            <i class="ki-duotone ki-box fs-2"></i>
                                        </span>
                                        <input type="number" class="form-control" name="max_quantity" 
                                            value="{{ $maxQuantity }}" 
                                            placeholder="10000"
                                            step="1" min="0">
                                    </div>
                                </div>
                                
                                {{-- Min Revenue --}}
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.min_revenue') }}</label>
                                    <div class="input-group w-100">
                                        <span class="input-group-text">
                                            <i class="ki-duotone ki-dollar fs-2"></i>
                                        </span>
                                        <input type="number" class="form-control" name="min_revenue" 
                                            value="{{ $minRevenue }}" 
                                            placeholder="0.00"
                                            step="0.01" min="0">
                                    </div>
                                </div>
                                
                                {{-- Max Revenue --}}
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold">{{ __('auth.max_revenue') }}</label>
                                    <div class="input-group w-100">
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
                                <div class="d-flex flex-column justify-content-end">
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                            <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                            <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                            <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                        </button>
                                        <a href="{{ route('reports.orders.by-product') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- ============================================================ --}}
                {{-- PRODUCT PERFORMANCE SUMMARY --}}
                {{-- ============================================================ --}}
                @if(isset($productSales) && $productSales->count() > 0)
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
                                        ['key' => 'total_revenue', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'total_revenue', 'value' => currency_symbol() . number_format($totalRevenue, 2)],
                                        ['key' => 'total_quantity', 'color' => 'info', 'icon' => 'ki-box-tick', 'label' => 'total_quantity_sold', 'value' => number_format($totalQuantity)],
                                        ['key' => 'avg_daily_sales', 'color' => 'warning', 'icon' => 'ki-chart-line', 'label' => 'average_daily_sales', 'value' => number_format($avgDailySales, 1)],
                                        ['key' => 'top_product', 'color' => 'danger', 'icon' => 'ki-crown', 'label' => 'top_product', 'value' => $topProduct ? substr($topProduct->variant_name, 0, 15) . '...' : 'N/A'],
                                        ['key' => 'top_revenue', 'color' => 'secondary', 'icon' => 'ki-dollar-circle', 'label' => 'top_product_revenue', 'value' => $topProduct ? currency_symbol() . number_format($topProduct->total_revenue, 2) : currency_symbol() . '0.00']
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

                {{-- ============================================================ --}}
                {{-- TOP PRODUCTS CHART --}}
                {{-- ============================================================ --}}
                @if(isset($topProducts) && $topProducts->count() > 0)
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

                {{-- ============================================================ --}}
                {{-- PRODUCT SALES TABLE --}}
                {{-- ============================================================ --}}
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
                                                    <th class="min-w-100px">{{ __('auth.sku') }}</th>
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
                                                @foreach($productSalesPaginated ?? $productSales as $index => $product)
                                                @php
                                                    $totalRevenueAll = $productSales->sum('total_revenue');
                                                    $percentage = $totalRevenueAll > 0 ? ($product->total_revenue / $totalRevenueAll) * 100 : 0;
                                                    $velocityColors = [
                                                        'Fast Mover' => 'success',
                                                        'Medium Mover' => 'warning',
                                                        'Slow Mover' => 'danger'
                                                    ];
                                                    $category = $product->movement_category ?? 'N/A';
                                                    $color = $velocityColors[$category] ?? 'secondary';
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
                                                                <div class="symbol-label bg-light-{{ $color }}">
                                                                    <i class="ki-duotone ki-box fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $product->variant_name }}</span>
                                                                <span class="badge badge-light-{{ $color }} badge-sm mt-1">
                                                                    {{ $category }}
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
                                                        <span class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($product->total_revenue, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-info">{{ currency_symbol() }}{{ number_format($product->total_tax, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-warning">{{ currency_symbol() }}{{ number_format($product->total_discount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">{{ currency_symbol() }}{{ number_format($product->average_selling_price, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-info">{{ $product->order_count }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge badge-light-{{ $color }} me-2">
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
                                                                <div class="progress-bar bg-{{ $color }}" 
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
                                
                                {{-- ✅ PAGINATION --}}
                                @if(isset($productSalesPaginated) && $productSalesPaginated->hasPages())
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $productSalesPaginated,
                                        'pageName' => 'page',
                                        'perPageName' => 'per_page',
                                        'showPerPage' => true
                                    ])
                                </div>
                                @endif
                                
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

                {{-- ============================================================ --}}
                {{-- TOP VS BOTTOM PRODUCTS --}}
                {{-- ============================================================ --}}
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
                                                <td>{{ Str::limit($product->variant_name, 25) }}</td>
                                                <td><span class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($product->total_revenue, 2) }}</span></td>
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
                                                <td>{{ Str::limit($product->variant_name, 25) }}</td>
                                                <td><span class="fw-bold text-danger">{{ currency_symbol() }}{{ number_format($product->total_revenue, 2) }}</span></td>
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

                {{-- ============================================================ --}}
                {{-- REPORT METADATA FOOTER --}}
                {{-- ============================================================ --}}
                <div class="mt-6 text-muted text-center fs-7">
                    <hr>
                    <p>
                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                        {{ __('accounting.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }} 
                        | {{ __('accounting.period') }}: {{ $startDate ?? 'N/A' }} {{ __('accounting.to') }} {{ $endDate ?? 'N/A' }}
                        @if(isset($locationId) && $locationId)
                            | {{ __('auth.location') }}: {{ $locations->where('id', $locationId)->first()->name ?? 'N/A' }}
                        @endif
                        @if(isset($departmentId) && $departmentId)
                            | {{ __('auth.department') }}: {{ $departments->where('id', $departmentId)->first()->name ?? 'N/A' }}
                        @endif
                        | {{ $productSales->count() ?? 0 }} {{ __('auth.products_analyzed') }}
                        | {{ $daysInPeriod ?? 0 }} {{ __('auth.days_analyzed') }}
                    </p>
                </div>
                
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- JAVASCRIPT - Charts & Export --}}
{{-- ============================================================ --}}
@push('scripts')
@if(isset($topProducts) && $topProducts->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Top Products Chart
    const topProductsData = @json($topProducts->take(10));
    const productNames = topProductsData.map(product => {
        let name = product.variant_name || 'Unknown';
        return name.length > 15 ? name.substring(0, 15) + '...' : name;
    });
    const productRevenue = topProductsData.map(product => parseFloat(product.total_revenue || 0));
    const productQuantity = topProductsData.map(product => parseFloat(product.total_quantity_sold || 0));
    
    const options = {
        series: [{
            name: '{{ __("auth.revenue") }}',
            data: productRevenue,
            type: 'bar'
        }, {
            name: '{{ __("auth.quantity") }}',
            data: productQuantity,
            type: 'line'
        }],
        chart: {
            type: 'bar',
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
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 4,
                borderRadiusApplication: 'end'
            }
        },
        stroke: {
            width: [0, 3],
            curve: 'smooth'
        },
        xaxis: {
            categories: productNames,
            labels: {
                rotate: -45,
                style: {
                    fontSize: '11px'
                }
            }
        },
        yaxis: [{
            title: {
                text: '{{ __("auth.revenue") }} ({{ currency_symbol() }})',
                style: {
                    fontSize: '12px'
                }
            },
            labels: {
                formatter: function(val) {
                    return '{{ currency_symbol() }}' + val.toLocaleString();
                }
            }
        }, {
            opposite: true,
            title: {
                text: '{{ __("auth.quantity") }}',
                style: {
                    fontSize: '12px'
                }
            }
        }],
        colors: ['#3E97FF', '#50CD89'],
        fill: {
            opacity: [1, 1],
            gradient: {
                enabled: true,
                shade: 'light',
                type: 'horizontal'
            }
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(val, { seriesIndex }) {
                    if (seriesIndex === 0) {
                        return '{{ currency_symbol() }}' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                    return val.toLocaleString();
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'center'
        },
        grid: {
            borderColor: '#f1f1f1',
            row: {
                colors: ['#f8f9fa', 'transparent'],
                opacity: 0.5
            }
        },
        dataLabels: {
            enabled: false
        }
    };
    
    const chart = new ApexCharts(document.querySelector("#topProductsChart"), options);
    chart.render();
});
</script>
@endif

<script>
// ============================================================
// Export Functions
// ============================================================

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __("accounting.table_not_found") }}');
        return;
    }
    
    try {
        // Check if XLSX is available
        if (typeof XLSX === 'undefined') {
            alert('{{ __("accounting.export_library_missing") }}');
            return;
        }
        
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Product Sales');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch (e) {
        console.error('Export error:', e);
        alert('{{ __("accounting.export_error") }}: ' + e.message);
    }
}

function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __("accounting.table_not_found") }}');
        return;
    }
    
    try {
        const rows = table.querySelectorAll('tr');
        let csv = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowData = Array.from(cols).map(col => {
                let text = col.innerText.trim();
                text = text.replace(/[🥇🥈🥉🚀🚚⏰🏆📉]/g, '').trim();
                if (text.includes(',')) {
                    text = '"' + text + '"';
                }
                return text;
            });
            csv.push(rowData.join(','));
        });
        
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename + '.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
    } catch (e) {
        console.error('Export error:', e);
        alert('{{ __("accounting.export_error") }}: ' + e.message);
    }
}

// ============================================================
// Form Validation
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const startDate = new Date(document.querySelector('[name="start_date"]').value);
            const endDate = new Date(document.querySelector('[name="end_date"]').value);
            const minQuantity = parseInt(document.querySelector('[name="min_quantity"]').value) || 0;
            const maxQuantity = parseInt(document.querySelector('[name="max_quantity"]').value) || 0;
            const minRevenue = parseFloat(document.querySelector('[name="min_revenue"]').value) || 0;
            const maxRevenue = parseFloat(document.querySelector('[name="max_revenue"]').value) || 0;
            const minProfit = parseFloat(document.querySelector('[name="min_profit"]').value) || 0;
            
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
            
            if (minProfit < 0) {
                e.preventDefault();
                alert('{{ __("auth.min_profit_cannot_be_negative") }}');
                return false;
            }
            
            return true;
        });
    }
    
    // ============================================================
    // Print Styles - Hide filter section when printing
    // ============================================================
    const style = document.createElement('style');
    style.innerHTML = `
        @media print {
            .card-header:has(.card-title:contains("Filter Report")),
            .app-toolbar .dropdown,
            #filterForm,
            .btn,
            .kt_app_toolbar .d-flex.gap-2 {
                display: none !important;
            }
            .card {
                border: 1px solid #ddd !important;
                break-inside: avoid;
            }
            .table {
                font-size: 10px !important;
            }
            .badge {
                border: 1px solid #ddd !important;
            }
            .progress {
                display: none !important;
            }
            .alert {
                border: 1px solid #ddd !important;
                background: #f8f9fa !important;
            }
            .page-title h1 {
                font-size: 18px !important;
            }
        }
    `;
    document.head.appendChild(style);
});

// ============================================================
// Quick Filter - Apply filters with Enter key
// ============================================================

document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && e.target.closest('#filterForm')) {
        e.preventDefault();
        document.getElementById('filterForm').submit();
    }
});
</script>
@endpush

@endsection