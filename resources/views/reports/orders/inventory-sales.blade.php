{{-- resources/views/reports/orders/inventory-sales.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.inventory_sales_report'))

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
                                {{ __('auth.inventory_sales_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.inventory_sales_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($soldProducts->count() > 0 || $unsoldProducts->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'soldProductsTable', filename: 'inventory_sales_{{ date('Y_m_d') }}', sheetName: 'Sold Products'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'soldProductsTable', filename: 'inventory_sales_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ request()->url() }}" id="filterForm">
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
                                        
                                        {{-- Action Buttons --}}
                                        <div class="col-md-6 col-lg-8 d-flex align-items-end justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('accounting.apply_filters') }}
                                                </button>
                                                <a href="{{ request()->url() }}" class="btn btn-light btn-active-light-primary">
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

                {{-- Summary Cards --}}
                <div class="row mb-6">
                    @php
                        $totalProducts = $soldProducts->count() + $unsoldProducts->count();
                        $sellThroughRate = $totalProducts > 0 ? ($soldProducts->count() / $totalProducts) * 100 : 0;
                        $avgDailySales = $soldProducts->sum('quantity_sold') / max(Carbon\Carbon::parse($startDate)->diffInDays(Carbon\Carbon::parse($endDate)) + 1, 1);
                    @endphp
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-box fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ $totalProducts }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.total_products') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-primary">
                                        {{ $soldProducts->count() }} {{ __('auth.sold') }} / {{ $unsoldProducts->count() }} {{ __('auth.unsold') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-chart-line fs-2tx text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ number_format($sellThroughRate, 1) }}%
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.sell_through_rate') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-success">
                                        ${{ number_format($turnoverRate, 2) }}% {{ __('auth.turnover') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-dollar-circle fs-2tx text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        ${{ number_format($soldInventoryValue, 2) }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.sold_inventory_value') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-info">
                                        ${{ number_format($totalInventoryValue, 2) }} {{ __('auth.total_value') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4">
                                    <i class="ki-duotone ki-speedometer fs-2tx text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">
                                        {{ number_format($avgDailySales, 1) }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold">
                                    {{ __('auth.avg_daily_sales') }}
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-light-warning">
                                        {{ $soldProducts->sum('quantity_sold') }} {{ __('auth.total_units') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Product Movement Analysis --}}
                @if($productMovement->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.product_movement_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $fastMovers = $productMovement->where('movement_category', 'Fast Mover')->count();
                                        $mediumMovers = $productMovement->where('movement_category', 'Medium Mover')->count();
                                        $slowMovers = $productMovement->where('movement_category', 'Slow Mover')->count();
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
                                                        {{ $soldProducts->count() > 0 ? number_format(($fastMovers / $soldProducts->count()) * 100, 1) : 0 }}%
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
                                                        {{ $soldProducts->count() > 0 ? number_format(($mediumMovers / $soldProducts->count()) * 100, 1) : 0 }}%
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
                                                        {{ $soldProducts->count() > 0 ? number_format(($slowMovers / $soldProducts->count()) * 100, 1) : 0 }}%
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

                {{-- Dead Stock Analysis --}}
                @if($deadStock->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-warning-2 fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.dead_stock_alert') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                                <th class="ps-4">{{ __('auth.product') }}</th>
                                                <th>{{ __('auth.sku') }}</th>
                                                <th>{{ __('auth.current_stock') }}</th>
                                                <th>{{ __('auth.price') }}</th>
                                                <th>{{ __('auth.stock_value') }}</th>
                                                <th>{{ __('auth.days_unsold') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($deadStock->take(10) as $product)
                                            @php
                                                $stockValue = $product->current_stock * $product->price;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-40px symbol-circle me-3">
                                                            <div class="symbol-label bg-light-danger">
                                                                <i class="ki-duotone ki-box fs-2 text-danger"></i>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex justify-content-start flex-column">
                                                            <span class="text-gray-800 fw-bold">{{ $product->name }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span class="badge badge-light-primary">{{ $product->sku }}</span></td>
                                                <td><span class="fw-bold text-danger">{{ $product->current_stock }}</span></td>
                                                <td><span class="text-info">${{ number_format($product->price, 2) }}</span></td>
                                                <td><span class="fw-bold text-warning">${{ number_format($stockValue, 2) }}</span></td>
                                                <td><span class="badge badge-light-danger">>30 days</span></td>
                                                
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if($deadStock->count() > 10)
                                <div class="card-footer text-center">
                                    <span class="text-muted">{{ __('auth.showing_top_10_of') }} {{ $deadStock->count() }} {{ __('auth.dead_stock_items') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Sold Products Table --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-cart-tick fs-2 me-2 text-success">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('auth.sold_products') }}</h3>
                                    </div>
                                    @if($soldProducts->count() > 0)
                                    <span class="badge badge-light-success fs-7">
                                        {{ __('accounting.showing') }} {{ $soldProducts->count() }} {{ __('auth.products') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($soldProducts->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="soldProductsTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-50px ps-4">{{ __('accounting.rank') }}</th>
                                                    <th class="min-w-200px">{{ __('auth.product') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.sku') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.quantity_sold') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.revenue_generated') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.current_stock') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.stock_value') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.times_ordered') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.daily_sales_rate') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.movement_category') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.last_sold_date') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($productMovement as $index => $product)
                                                @php
                                                    $stockValue = $product->current_stock * $product->price;
                                                    $movementColors = [
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
                                                                <div class="symbol-label bg-light-{{ $movementColors[$product->movement_category] ?? 'secondary' }}">
                                                                    <i class="ki-duotone ki-box fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $product->name }}</span>
                                                                <span class="badge badge-light-{{ $movementColors[$product->movement_category] ?? 'secondary' }} badge-sm mt-1">
                                                                    {{ $product->movement_category }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">{{ $product->sku }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-primary">{{ number_format($product->quantity_sold) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($product->revenue_generated, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $stockClass = $product->current_stock <= 5 ? 'danger' : ($product->current_stock <= 10 ? 'warning' : 'success');
                                                        @endphp
                                                        <span class="badge badge-light-{{ $stockClass }}">
                                                            {{ $product->current_stock }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="text-info">${{ number_format($stockValue, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-info">{{ $product->times_ordered }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge badge-light-{{ $movementColors[$product->movement_category] ?? 'secondary' }} me-2">
                                                                {{ number_format($product->daily_sales_rate, 2) }}/day
                                                            </span>
                                                            @if($product->daily_sales_rate > 1)
                                                            <i class="ki-duotone ki-arrow-up-right fs-2 text-success"></i>
                                                            @elseif($product->daily_sales_rate < 0.1)
                                                            <i class="ki-duotone ki-arrow-down-right fs-2 text-danger"></i>
                                                            @else
                                                            <i class="ki-duotone ki-minus fs-2 text-gray-400"></i>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $movementColors[$product->movement_category] ?? 'secondary' }}">
                                                            {{ $product->movement_category }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($product->last_sold_date)
                                                        <span class="text-gray-700">{{ \Carbon\Carbon::parse($product->last_sold_date)->format('M d, Y') }}</span>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
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
                                        <i class="ki-duotone ki-cart-tick fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('auth.no_sold_products_found_for_period') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Unsold Products Table --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-cart-cross fs-2 me-2 text-danger">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('auth.unsold_products') }}</h3>
                                    </div>
                                    @if($unsoldProducts->count() > 0)
                                    <span class="badge badge-light-danger fs-7">
                                        {{ __('accounting.showing') }} {{ $unsoldProducts->count() }} {{ __('auth.products') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($unsoldProducts->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-200px">{{ __('auth.product') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.sku') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.current_stock') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.price') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.stock_value') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.stock_aging') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($unsoldProducts as $product)
                                                @php
                                                    $stockValue = $product->current_stock * $product->price;
                                                    $stockClass = $product->current_stock <= 5 ? 'danger' : ($product->current_stock <= 10 ? 'warning' : 'info');
                                                    $isDeadStock = $product->current_stock > 10;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-40px symbol-circle me-3">
                                                                <div class="symbol-label bg-light-{{ $isDeadStock ? 'danger' : $stockClass }}">
                                                                    <i class="ki-duotone ki-box fs-2 text-{{ $isDeadStock ? 'danger' : $stockClass }}"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $product->name }}</span>
                                                                @if($isDeadStock)
                                                                <span class="badge badge-light-danger badge-sm mt-1">
                                                                    {{ __('auth.dead_stock') }}
                                                                </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">{{ $product->sku }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $stockClass }}">
                                                            {{ $product->current_stock }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="text-info">${{ number_format($product->price, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-warning">${{ number_format($stockValue, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-secondary">>30 days</span>
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
                                        <i class="ki-duotone ki-cart-cross fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('auth.all_products_were_sold_in_period') }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Top vs Bottom Performers --}}
                @if($soldProducts->count() > 5)
                <div class="row mt-6">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-crown fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_sellers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-success">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('auth.product') }}</th>
                                                <th>{{ __('auth.quantity_sold') }}</th>
                                                <th>{{ __('auth.revenue') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($soldProducts->take(5) as $index => $product)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-gray-800">{{ $index + 1 }}</span>
                                                </td>
                                                <td>{{ substr($product->name, 0, 20) }}{{ strlen($product->name) > 20 ? '...' : '' }}</td>
                                                <td><span class="fw-bold text-success">{{ number_format($product->quantity_sold) }}</span></td>
                                                <td><span class="badge badge-light-success">${{ number_format($product->revenue_generated, 2) }}</span></td>
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
                                    <h3 class="fw-bold m-0">{{ __('auth.slow_sellers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('auth.product') }}</th>
                                                <th>{{ __('auth.quantity_sold') }}</th>
                                                <th>{{ __('auth.revenue') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($soldProducts->slice(-5)->reverse() as $index => $product)
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold text-gray-800">{{ $soldProducts->count() - $index }}</span>
                                                </td>
                                                <td>{{ substr($product->name, 0, 20) }}{{ strlen($product->name) > 20 ? '...' : '' }}</td>
                                                <td><span class="fw-bold text-danger">{{ number_format($product->quantity_sold) }}</span></td>
                                                <td><span class="badge badge-light-danger">${{ number_format($product->revenue_generated, 2) }}</span></td>
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
<script>
    // Form validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const startDate = new Date(document.querySelector('[name="start_date"]').value);
        const endDate = new Date(document.querySelector('[name="end_date"]').value);
        
        if (startDate > endDate) {
            e.preventDefault();
            alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
            return false;
        }
    });
</script>
@endpush

@endsection