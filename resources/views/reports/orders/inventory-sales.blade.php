{{-- resources/views/reports/orders/inventory-sales.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.inventory_sales_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- ============================================================ --}}
                {{-- TOOLBAR --}}
                {{-- ============================================================ --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container"
                         class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('auth.inventory_sales_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.inventory_sales_report') }}</li>
                            </ul>
                        </div>
                        @if(($soldProducts->count() ?? 0) > 0 || ($unsoldProducts->count() ?? 0) > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary"
                                    onclick="exportTableToExcel('soldProductsTable', 'inventory_sales')">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                                <i class="ki-duotone ki-printer fs-2"></i> {{ __('accounting.print') }}
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- FILTER --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form method="GET" action="{{ route('reports.orders.inventory-sales') }}" id="filterForm">
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('accounting.start_date') }}</label>
                                    <input type="date" class="form-control" name="start_date"
                                           value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('accounting.end_date') }}</label>
                                    <input type="date" class="form-control" name="end_date"
                                           value="{{ $endDate ?? now()->endOfMonth()->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('auth.location') }}</label>
                                    <select class="form-select" name="location_id" data-control="select2">
                                        <option value="">{{ __('auth.all_locations') }}</option>
                                        @foreach($locations ?? [] as $location)
                                            <option value="{{ $location->id }}"
                                                {{ ($locationId ?? '') == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('auth.department') }}</label>
                                    <select class="form-select" name="department_id" data-control="select2">
                                        <option value="">{{ __('auth.all_departments') }}</option>
                                        @foreach($departments ?? [] as $department)
                                            <option value="{{ $department->id }}"
                                                {{ ($departmentId ?? '') == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.orders.inventory-sales') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </a>
                                    @isset($isSingleShop)
                                        <span class="text-muted ms-3 small">
                                            <i class="ki-duotone ki-information-4 fs-2"></i>
                                            {{ $isSingleShop ? '🏪 Single Shop Mode' : '🏢 Multi-Shop Mode' }}
                                        </span>
                                    @endisset
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA --}}
                {{-- ============================================================ --}}
                @if(($soldProducts->count() ?? 0) === 0 && ($unsoldProducts->count() ?? 0) === 0)
                    <div class="card">
                        <div class="card-body text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_inventory_data_found_for_period') }}</p>
                        </div>
                    </div>
                @else

                    @php
                        $soldCount   = $soldProducts->count() ?? 0;
                        $unsoldCount = $unsoldProducts->count() ?? 0;
                        $totalProducts = $soldCount + $unsoldCount;
                        $sellThroughRate = $totalProducts > 0 ? ($soldCount / $totalProducts) * 100 : 0;

                        $totalUnitsSold = $soldProducts->sum('quantity_sold') ?? 0;
                        $avgDailySales  = ($daysInPeriod ?? 0) > 0 ? $totalUnitsSold / $daysInPeriod : 0;

                        $soldRevenue = $soldProducts->sum('revenue_generated') ?? 0;
                        $soldCost    = $soldProducts->sum('total_cost_generated') ?? 0;
                        $soldProfit  = $soldRevenue - $soldCost;
                        $soldMargin  = $soldRevenue > 0 ? ($soldProfit / $soldRevenue) * 100 : 0;
                    @endphp

                    {{-- ============================================================ --}}
                    {{-- SUMMARY CARDS (6 across on lg) --}}
                    {{-- ============================================================ --}}
                    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-6 g-4 g-lg-5 mb-6">

                        {{-- Total products --}}
                        <div class="col">
                            <div class="card bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body text-center py-4">
                                    <div class="fs-2 fw-bold text-primary">{{ number_format($totalProducts) }}</div>
                                    <div class="text-muted fs-7">{{ __('auth.total_products') }}</div>
                                    <span class="badge badge-light-primary mt-2 fs-8">
                                        {{ $soldCount }} / {{ $unsoldCount }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Sell-through --}}
                        <div class="col">
                            <div class="card bg-light-success border border-success border-dashed h-100">
                                <div class="card-body text-center py-4">
                                    <div class="fs-2 fw-bold text-success">{{ number_format($sellThroughRate, 1) }}%</div>
                                    <div class="text-muted fs-7">{{ __('auth.sell_through_rate') }}</div>
                                    <span class="badge badge-light-success mt-2 fs-8">
                                        {{ number_format($turnoverRate ?? 0, 2) }}% {{ __('auth.turnover') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Units sold --}}
                        <div class="col">
                            <div class="card bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body text-center py-4">
                                    <div class="fs-2 fw-bold text-warning">{{ number_format($totalUnitsSold) }}</div>
                                    <div class="text-muted fs-7">{{ __('auth.total_units') }}</div>
                                    <span class="badge badge-light-warning mt-2 fs-8">
                                        {{ number_format($avgDailySales, 1) }}/{{ __('auth.day') ?? 'day' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Sold revenue --}}
                        <div class="col">
                            <div class="card bg-light-info border border-info border-dashed h-100">
                                <div class="card-body text-center py-4">
                                    <div class="fs-3 fw-bold text-info">
                                        {{ currency_symbol() }}{{ number_format($soldRevenue, 2) }}
                                    </div>
                                    <div class="text-muted fs-7">{{ __('auth.sold_inventory_value') }}</div>
                                    <span class="badge badge-light-info mt-2 fs-8">
                                        {{ currency_symbol() }}{{ number_format($totalInventoryValue ?? 0, 2) }} {{ __('auth.total_value') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Sold cost --}}
                        <div class="col">
                            <div class="card bg-light-secondary border border-secondary border-dashed h-100">
                                <div class="card-body text-center py-4">
                                    <div class="fs-3 fw-bold text-gray-800">
                                        {{ currency_symbol() }}{{ number_format($soldCost, 2) }}
                                    </div>
                                    <div class="text-muted fs-7">{{ __('auth.cost') }}</div>
                                    <span class="badge badge-light-secondary mt-2 fs-8">
                                        {{ __('auth.cost_of_goods_sold') ?? 'COGS' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Sold profit --}}
                        <div class="col">
                            <div class="card bg-light-{{ $soldProfit >= 0 ? 'success' : 'danger' }}
                                        border border-{{ $soldProfit >= 0 ? 'success' : 'danger' }} border-dashed h-100">
                                <div class="card-body text-center py-4">
                                    <div class="fs-3 fw-bold text-{{ $soldProfit >= 0 ? 'success' : 'danger' }}">
                                        {{ currency_symbol() }}{{ number_format($soldProfit, 2) }}
                                    </div>
                                    <div class="text-muted fs-7">{{ __('auth.sold_inventory_profit') }}</div>
                                    <span class="badge badge-light-{{ $soldProfit >= 0 ? 'success' : 'danger' }} mt-2 fs-8">
                                        {{ number_format($soldMargin, 1) }}% {{ __('auth.margin') ?? 'margin' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ============================================================ --}}
                    {{-- PRODUCT MOVEMENT --}}
                    {{-- ============================================================ --}}
                    @if(($productMovement->count() ?? 0) > 0)
                        @php
                            $fastMovers   = $productMovement->where('movement_category', 'Fast Mover')->count();
                            $mediumMovers = $productMovement->where('movement_category', 'Medium Mover')->count();
                            $slowMovers   = $productMovement->where('movement_category', 'Slow Mover')->count();
                            $totalMovers  = max($productMovement->count(), 1);
                        @endphp
                        <div class="row g-6 mb-6">
                            <div class="col-md-4">
                                <div class="card bg-light-success border border-success border-dashed h-100">
                                    <div class="card-body text-center">
                                        <div class="fs-1 fw-bold text-success">{{ number_format($fastMovers) }}</div>
                                        <div class="text-muted">🚀 {{ __('auth.fast_movers') }}
                                            <small class="text-muted">(≥1/day)</small>
                                        </div>
                                        <span class="badge badge-light-success mt-2">
                                            {{ number_format(($fastMovers / $totalMovers) * 100, 1) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light-warning border border-warning border-dashed h-100">
                                    <div class="card-body text-center">
                                        <div class="fs-1 fw-bold text-warning">{{ number_format($mediumMovers) }}</div>
                                        <div class="text-muted">🚚 {{ __('auth.medium_movers') }}
                                            <small class="text-muted">(0.1-1/day)</small>
                                        </div>
                                        <span class="badge badge-light-warning mt-2">
                                            {{ number_format(($mediumMovers / $totalMovers) * 100, 1) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light-danger border border-danger border-dashed h-100">
                                    <div class="card-body text-center">
                                        <div class="fs-1 fw-bold text-danger">{{ number_format($slowMovers) }}</div>
                                        <div class="text-muted">⏰ {{ __('auth.slow_movers') }}
                                            <small class="text-muted">(&lt;0.1/day)</small>
                                        </div>
                                        <span class="badge badge-light-danger mt-2">
                                            {{ number_format(($slowMovers / $totalMovers) * 100, 1) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ============================================================ --}}
                    {{-- DEAD STOCK --}}
                    {{-- ============================================================ --}}
                    @if(($deadStock->count() ?? 0) > 0)
                        <div class="card mb-6 border border-danger">
                            <div class="card-header bg-light-danger">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-warning-2 fs-2 me-2 text-danger"></i>
                                    {{ __('auth.dead_stock_alert') }}
                                </h3>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-danger fs-7">
                                        {{ $deadStock->count() }} {{ __('auth.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 bg-light-danger">
                                                <th class="ps-4">{{ __('auth.product') }}</th>
                                                <th>{{ __('auth.sku') }}</th>
                                                <th class="text-center">{{ __('auth.current_stock') }}</th>
                                                <th class="text-end">{{ __('auth.price') }}</th>
                                                <th class="text-end">{{ __('auth.stock_value') }}</th>
                                                <th>{{ __('auth.days_unsold') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($deadStock as $product)
                                                @php
                                                    $sv = (float) ($product->stock_value
                                                        ?? ($product->current_stock * ($product->price ?? 0)));
                                                @endphp
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-35px symbol-circle me-2">
                                                                <div class="symbol-label bg-light-danger">
                                                                    <i class="ki-duotone ki-box fs-2 text-danger"></i>
                                                                </div>
                                                            </div>
                                                            <span class="fw-bold">{{ Str::limit($product->name, 25) }}</span>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge badge-light-primary">{{ $product->sku }}</span></td>
                                                    <td class="text-center">
                                                        <span class="badge badge-light-danger">
                                                            {{ number_format($product->current_stock) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        {{ currency_symbol() }}{{ number_format($product->price ?? 0, 2) }}
                                                    </td>
                                                    <td class="text-end text-warning fw-bold">
                                                        {{ currency_symbol() }}{{ number_format($sv, 2) }}
                                                    </td>
                                                    <td><span class="badge badge-light-danger">&gt;30 days</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ============================================================ --}}
                    {{-- SOLD PRODUCTS --}}
                    {{-- ============================================================ --}}
                    <div class="card mb-6">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <i class="ki-duotone ki-cart-tick fs-2 me-2 text-success"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.sold_products') }}</h3>
                            </div>
                            <div class="card-toolbar">
                                <span class="badge badge-light-success fs-7">
                                    {{ $soldCount }} {{ __('auth.products') }}
                                </span>
                            </div>
                        </div>

                        @if($soldCount > 0)
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0"
                                           id="soldProductsTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                                <th class="ps-4">{{ __('accounting.rank') }}</th>
                                                <th>{{ __('auth.product') }}</th>
                                                <th>{{ __('auth.sku') }}</th>
                                                <th class="text-center">{{ __('auth.quantity_sold') }}</th>
                                                <th class="text-end">{{ __('auth.revenue_generated') }}</th>
                                                <th class="text-end">{{ __('auth.cost') }}</th>
                                                <th class="text-end">{{ __('auth.profit') }}</th>
                                                <th class="text-center">{{ __('auth.current_stock') }}</th>
                                                <th class="text-end">{{ __('auth.stock_value') }}</th>
                                                <th class="text-center">{{ __('auth.times_ordered') }}</th>
                                                <th class="text-center">{{ __('auth.daily_sales_rate') }}</th>
                                                <th class="text-center">{{ __('auth.movement_category') }}</th>
                                                <th>{{ __('auth.last_sold_date') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($soldProducts as $index => $product)
                                                @php
                                                    $globalIndex = ($soldProducts->currentPage() - 1) * $soldProducts->perPage() + $index + 1;
                                                    $sv = (float) ($product->stock_value
                                                        ?? ($product->current_stock * ($product->price ?? 0)));

                                                    $revenue = (float) ($product->revenue_generated ?? 0);
                                                    $cost    = (float) ($product->total_cost_generated ?? 0);
                                                    $profit  = $revenue - $cost;
                                                    $margin  = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                                                    $movementColors = [
                                                        'Fast Mover'   => 'success',
                                                        'Medium Mover' => 'warning',
                                                        'Slow Mover'   => 'danger',
                                                    ];
                                                    $mc    = $movementColors[$product->movement_category] ?? 'secondary';
                                                    $stockClass = $product->current_stock <= 5
                                                        ? 'danger'
                                                        : ($product->current_stock <= 10 ? 'warning' : 'success');
                                                @endphp
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="fw-bold">{{ $globalIndex }}</span>
                                                        @if($globalIndex <= 3)
                                                            <span class="badge badge-light-{{ $globalIndex == 1 ? 'danger' : ($globalIndex == 2 ? 'warning' : 'info') }}">
                                                                {{ $globalIndex == 1 ? '🥇' : ($globalIndex == 2 ? '🥈' : '🥉') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-35px symbol-circle me-2">
                                                                <div class="symbol-label bg-light-{{ $mc }}">
                                                                    <i class="ki-duotone ki-box fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold">{{ Str::limit($product->name, 25) }}</div>
                                                                <span class="badge badge-light-{{ $mc }} badge-sm">
                                                                    {{ $product->movement_category }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge badge-light-primary">{{ $product->sku }}</span></td>

                                                    <td class="text-center fw-bold text-primary">
                                                        {{ number_format($product->quantity_sold) }}
                                                    </td>

                                                    <td class="text-end fw-bold text-success">
                                                        {{ currency_symbol() }}{{ number_format($revenue, 2) }}
                                                    </td>

                                                    <td class="text-end text-muted">
                                                        {{ currency_symbol() }}{{ number_format($cost, 2) }}
                                                    </td>

                                                    <td class="text-end fw-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ currency_symbol() }}{{ number_format($profit, 2) }}
                                                        <span class="badge badge-light-{{ $profit >= 0 ? 'success' : 'danger' }} ms-1">
                                                            {{ number_format($margin, 1) }}%
                                                        </span>
                                                    </td>

                                                    <td class="text-center">
                                                        <span class="badge badge-light-{{ $stockClass }}">
                                                            {{ number_format($product->current_stock) }}
                                                        </span>
                                                    </td>

                                                    <td class="text-end text-info">
                                                        {{ currency_symbol() }}{{ number_format($sv, 2) }}
                                                    </td>

                                                    <td class="text-center">
                                                        <span class="badge badge-light-info">
                                                            {{ number_format($product->times_ordered) }}
                                                        </span>
                                                    </td>

                                                    <td class="text-center">
                                                        <span class="badge badge-light-{{ $mc }}">
                                                            {{ number_format($product->daily_sales_rate, 2) }}
                                                        </span>
                                                    </td>

                                                    <td class="text-center">
                                                        <span class="badge badge-light-{{ $mc }}">
                                                            {{ $product->movement_category }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        @if($product->last_sold_date)
                                                            {{ \Carbon\Carbon::parse($product->last_sold_date)->format('M d, Y') }}
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

                            @if($soldProducts->hasPages())
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator'   => $soldProducts,
                                        'pageName'    => 'sold_page',
                                        'perPageName' => 'sold_per_page',
                                        'showPerPage' => true,
                                    ])
                                </div>
                            @endif
                        @else
                            <div class="card-body text-center py-6">
                                <p class="text-muted">{{ __('auth.no_sold_products_found_for_period') }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- ============================================================ --}}
                    {{-- UNSOLD PRODUCTS --}}
                    {{-- ============================================================ --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title">
                                <i class="ki-duotone ki-cart-cross fs-2 me-2 text-danger"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.unsold_products') }}</h3>
                            </div>
                            <div class="card-toolbar">
                                <span class="badge badge-light-danger fs-7">
                                    {{ $unsoldCount }} {{ __('auth.products') }}
                                </span>
                            </div>
                        </div>

                        @if($unsoldCount > 0)
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                                <th>{{ __('auth.product') }}</th>
                                                <th>{{ __('auth.sku') }}</th>
                                                <th class="text-center">{{ __('auth.current_stock') }}</th>
                                                <th class="text-end">{{ __('auth.price') }}</th>
                                                <th class="text-end">{{ __('auth.stock_value') }}</th>
                                                <th>{{ __('auth.stock_aging') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($unsoldProducts as $product)
                                                @php
                                                    $sv = (float) ($product->stock_value
                                                        ?? ($product->current_stock * ($product->price ?? 0)));
                                                    $stockClass = $product->current_stock <= 5
                                                        ? 'danger'
                                                        : ($product->current_stock <= 10 ? 'warning' : 'info');
                                                    $isDeadStock = $product->current_stock > 10;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-35px symbol-circle me-2">
                                                                <div class="symbol-label bg-light-{{ $isDeadStock ? 'danger' : $stockClass }}">
                                                                    <i class="ki-duotone ki-box fs-2 text-{{ $isDeadStock ? 'danger' : $stockClass }}"></i>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <span class="fw-bold">{{ Str::limit($product->name, 30) }}</span>
                                                                @if($isDeadStock)
                                                                    <span class="badge badge-light-danger badge-sm d-block">
                                                                        {{ __('auth.dead_stock') }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><span class="badge badge-light-primary">{{ $product->sku }}</span></td>
                                                    <td class="text-center">
                                                        <span class="badge badge-light-{{ $stockClass }}">
                                                            {{ number_format($product->current_stock) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        {{ currency_symbol() }}{{ number_format($product->price ?? 0, 2) }}
                                                    </td>
                                                    <td class="text-end text-warning fw-bold">
                                                        {{ currency_symbol() }}{{ number_format($sv, 2) }}
                                                    </td>
                                                    <td><span class="badge badge-light-secondary">&gt;30 days</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @if($unsoldProducts->hasPages())
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator'   => $unsoldProducts,
                                        'pageName'    => 'unsold_page',
                                        'perPageName' => 'unsold_per_page',
                                        'showPerPage' => true,
                                    ])
                                </div>
                            @endif
                        @else
                            <div class="card-body text-center py-6">
                                <p class="text-muted">{{ __('auth.all_products_were_sold_in_period') }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- ============================================================ --}}
                    {{-- META FOOTER --}}
                    {{-- ============================================================ --}}
                    <div class="mt-6 text-muted text-center fs-7">
                        <hr>
                        <p>
                            <i class="ki-duotone ki-calendar-8 fs-2"></i>
                            {{ __('auth.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }}
                            | {{ __('accounting.period') }}:
                            {{ $startDate ?? 'N/A' }} {{ __('accounting.to') }} {{ $endDate ?? 'N/A' }}
                            @if(!empty($locationId))
                                | {{ __('auth.location') }}:
                                {{ optional($locations->firstWhere('id', $locationId))->name ?? 'N/A' }}
                            @endif
                            @if(!empty($departmentId))
                                | {{ __('auth.department') }}:
                                {{ optional($departments->firstWhere('id', $departmentId))->name ?? 'N/A' }}
                            @endif
                            | {{ $totalProducts }} {{ __('auth.products') }}
                            | {{ __('auth.sold') }}: {{ $soldCount }}
                            | {{ __('auth.unsold') }}: {{ $unsoldCount }}
                            @isset($isSingleShop)
                                | {{ $isSingleShop ? '🏪 Single Shop' : '🏢 Multi-Shop' }}
                            @endisset
                        </p>
                    </div>

                @endif

            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================================ --}}
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function (e) {
            const start = document.querySelector('[name="start_date"]');
            const end   = document.querySelector('[name="end_date"]');
            if (start && end && start.value && end.value) {
                if (new Date(start.value) > new Date(end.value)) {
                    e.preventDefault();
                    alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
                }
            }
        });
    }
});

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __("accounting.table_not_found") }}');
        return;
    }
    try {
        if (typeof XLSX === 'undefined') {
            alert('{{ __("accounting.export_library_missing") }}');
            return;
        }
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Inventory Sales');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch (e) {
        alert('{{ __("accounting.export_error") }}: ' + e.message);
    }
}
</script>
@endpush

{{-- ============================================================ --}}
{{-- PRINT STYLES --}}
{{-- ============================================================ --}}
@push('styles')
<style>
@media print {
    .app-toolbar,
    #filterForm,
    .dropdown,
    .no-print,
    .card-header .btn {
        display: none !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-body {
        padding: 0 !important;
    }
    .table-responsive {
        overflow: visible !important;
    }
    .badge {
        border: 1px solid #E5E7EB !important;
    }
    .symbol {
        display: none !important;
    }
    .progress {
        display: none !important;
    }
}
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    .card-body {
        padding: 0.5rem !important;
    }
}
</style>
@endpush

@endsection