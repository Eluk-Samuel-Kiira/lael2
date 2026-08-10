@extends('layouts.app')

@section('title', __('auth.profit_analysis_report'))

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
                                {{ __('auth.profit_analysis_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.profit_analysis') }}</li>
                            </ul>
                        </div>
                        @if(isset($summary) && $summary->total_orders > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('profitTable', 'profit_analysis')">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export_to_excel') }}
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                                <i class="ki-duotone ki-printer fs-2"></i> {{ __('accounting.print') }}
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- FILTER SECTION --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.filter_by') }}</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.orders.profit-analysis') }}" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" name="location_id" data-control="select2">
                                    <option value="">{{ __('auth.all_locations') }}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" name="department_id" data-control="select2">
                                    <option value="">{{ __('auth.all_departments') }}</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ $departmentId == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.order_type') }}</label>
                                <select class="form-select" name="order_type">
                                    <option value="">{{ __('auth.all_types') }}</option>
                                    <option value="sale" {{ $orderType == 'sale' ? 'selected' : '' }}>{{ __('auth.sale') }}</option>
                                    <option value="return" {{ $orderType == 'return' ? 'selected' : '' }}>{{ __('auth.return') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.min_profit') }}</label>
                                <input type="number" class="form-control" name="min_profit" value="{{ $minProfit }}" placeholder="Min profit" step="0.01">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.max_profit') }}</label>
                                <input type="number" class="form-control" name="max_profit" value="{{ $maxProfit }}" placeholder="Max profit" step="0.01">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">{{ __('accounting.apply_filters') }}</button>
                                <a href="{{ route('reports.orders.profit-analysis') }}" class="btn btn-light">{{ __('accounting.clear_filters') }}</a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA MESSAGE --}}
                {{-- ============================================================ --}}
                @if(!isset($summary) || $summary->total_orders == 0)
                <div class="card">
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_orders_found_for_period') }}</p>
                        </div>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- KPI CARDS - Key Performance Indicators --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-primary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-primary">
                                            <i class="ki-duotone ki-basket fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-2 fw-bold">{{ number_format($summary->total_orders) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_orders') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    <span class="badge badge-light-success me-1">{{ $summary->profitable_orders }}</span> Profitable
                                    <span class="badge badge-light-danger ms-2 me-1">{{ $summary->loss_making_orders }}</span> Loss
                                    <span class="badge badge-light-secondary ms-2 me-1">{{ $summary->break_even_orders }}</span> Break-even
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-success h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-success">
                                            <i class="ki-duotone ki-dollar fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-4 fw-bold">{{ currency_symbol() }}{{ number_format($summary->total_revenue, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_revenue') }}</div>
                                        <div class="fs-4 fw-bold text-danger mt-1">{{ currency_symbol() }}{{ number_format($summary->total_cost, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_cost') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-warning h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-warning">
                                            <i class="ki-duotone ki-calculator fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-4 fw-bold text-success">{{ currency_symbol() }}{{ number_format($summary->total_gross_profit, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.gross_profit') }}</div>
                                        <div class="fs-4 fw-bold text-primary mt-1">{{ currency_symbol() }}{{ number_format($summary->total_net_profit, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.net_profit') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-secondary">
                                            <i class="ki-duotone ki-chart fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-4 fw-bold text-success">{{ number_format($summary->average_profit_margin, 1) }}%</div>
                                        <div class="text-muted fs-6">{{ __('auth.avg_profit_margin') }}</div>
                                        <div class="fs-4 fw-bold text-primary mt-1">{{ currency_symbol() }}{{ number_format($summary->average_net_profit, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.avg_net_profit') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- SUMMARY STATS ROW --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-3">
                        <div class="card bg-light-success">
                            <div class="card-body text-center">
                                <div class="fs-6 text-muted">{{ __('auth.highest_profit_order') }}</div>
                                <div class="fs-3 fw-bold text-success">{{ $summary->max_profit_order }}</div>
                                <div class="fs-5 text-success">{{ currency_symbol() }}{{ number_format($summary->max_profit_amount, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-info">
                            <div class="card-body text-center">
                                <div class="fs-6 text-muted">{{ __('auth.total_discount') }}</div>
                                <div class="fs-3 fw-bold text-info">{{ currency_symbol() }}{{ number_format($summary->total_discount, 2) }}</div>
                                <div class="fs-6 text-muted">{{ __('auth.discount_reduced_profit') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-danger">
                            <div class="card-body text-center">
                                <div class="fs-6 text-muted">{{ __('auth.loss_making_orders') }}</div>
                                <div class="fs-3 fw-bold text-danger">{{ $summary->loss_making_orders }}</div>
                                <div class="fs-6 text-muted">{{ __('auth.orders_with_negative_profit') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-primary">
                            <div class="card-body text-center">
                                <div class="fs-6 text-muted">{{ __('auth.profitability_rate') }}</div>
                                @php
                                    $profitabilityRate = $summary->total_orders > 0 ? ($summary->profitable_orders / $summary->total_orders) * 100 : 0;
                                @endphp
                                <div class="fs-3 fw-bold text-primary">{{ number_format($profitabilityRate, 1) }}%</div>
                                <div class="fs-6 text-muted">{{ $summary->profitable_orders }} / {{ $summary->total_orders }} {{ __('auth.orders') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- PROFIT BY LOCATION --}}
                {{-- ============================================================ --}}
                @if($profitByLocation->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-geolocation fs-2 me-2"></i>
                            {{ __('auth.profit_by_location') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('auth.location') }}</th>
                                        <th class="text-center">{{ __('auth.orders') }}</th>
                                        <th class="text-end">{{ __('auth.revenue') }}</th>
                                        <th class="text-end">{{ __('auth.cost') }}</th>
                                        <th class="text-end">{{ __('auth.net_profit') }}</th>
                                        <th class="text-end">{{ __('auth.avg_margin') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($profitByLocation as $location)
                                    <tr>
                                        <td><span class="fw-bold">{{ $location->location }}</span></td>
                                        <td class="text-center">{{ number_format($location->order_count) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($location->total_revenue, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($location->total_cost, 2) }}</td>
                                        <td class="text-end fw-bold {{ $location->total_net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ currency_symbol() }}{{ number_format($location->total_net_profit, 2) }}
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-{{ $location->average_margin >= 10 ? 'success' : ($location->average_margin >= 0 ? 'warning' : 'danger') }}">
                                                {{ number_format($location->average_margin, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- TOP PRODUCTS BY PROFIT --}}
                {{-- ============================================================ --}}
                @if($topProducts->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-arrow-up fs-2 text-success me-2"></i>
                                    {{ __('auth.top_profitable_products') }}
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('auth.product') }}</th>
                                                <th class="text-center">{{ __('auth.qty_sold') }}</th>
                                                <th class="text-end">{{ __('auth.profit') }}</th>
                                                <th class="text-end">{{ __('auth.margin') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topProducts as $product)
                                            <tr>
                                                <td>
                                                    <span class="fw-bold">{{ $product->variant_name }}</span>
                                                    <br><small class="text-muted">{{ $product->sku }}</small>
                                                </td>
                                                <td class="text-center">{{ number_format($product->quantity_sold) }}</td>
                                                <td class="text-end text-success">{{ currency_symbol() }}{{ number_format($product->total_profit, 2) }}</td>
                                                <td class="text-end">
                                                    <span class="badge badge-light-success">{{ number_format($product->profit_margin, 1) }}%</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-arrow-down fs-2 text-danger me-2"></i>
                                    {{ __('auth.least_profitable_products') }}
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('auth.product') }}</th>
                                                <th class="text-center">{{ __('auth.qty_sold') }}</th>
                                                <th class="text-end">{{ __('auth.profit') }}</th>
                                                <th class="text-end">{{ __('auth.margin') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($worstProducts as $product)
                                            <tr>
                                                <td>
                                                    <span class="fw-bold">{{ $product->variant_name }}</span>
                                                    <br><small class="text-muted">{{ $product->sku }}</small>
                                                </td>
                                                <td class="text-center">{{ number_format($product->quantity_sold) }}</td>
                                                <td class="text-end {{ $product->total_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                                    {{ currency_symbol() }}{{ number_format($product->total_profit, 2) }}
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge badge-light-{{ $product->profit_margin >= 10 ? 'success' : ($product->profit_margin >= 0 ? 'warning' : 'danger') }}">
                                                        {{ number_format($product->profit_margin, 1) }}%
                                                    </span>
                                                </td>
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
                {{-- DAILY PROFIT TREND --}}
                {{-- ============================================================ --}}
                @if($dailyProfitTrend->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-chart-line fs-2 me-2"></i>
                            {{ __('auth.daily_profit_trend') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.date') }}</th>
                                        <th class="text-center">{{ __('auth.orders') }}</th>
                                        <th class="text-end">{{ __('auth.revenue') }}</th>
                                        <th class="text-end">{{ __('auth.cost') }}</th>
                                        <th class="text-end">{{ __('auth.net_profit') }}</th>
                                        <th class="text-end">{{ __('auth.avg_margin') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyProfitTrend as $day)
                                    <tr>
                                        <td><span class="fw-bold">{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</span></td>
                                        <td class="text-center">{{ number_format($day->order_count) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($day->total_revenue, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($day->total_cost, 2) }}</td>
                                        <td class="text-end fw-bold {{ $day->total_net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ currency_symbol() }}{{ number_format($day->total_net_profit, 2) }}
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-{{ $day->average_margin >= 10 ? 'success' : ($day->average_margin >= 0 ? 'warning' : 'danger') }}">
                                                {{ number_format($day->average_margin, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($dailyProfitTrend->sum('order_count')) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($dailyProfitTrend->sum('total_revenue'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($dailyProfitTrend->sum('total_cost'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($dailyProfitTrend->sum('total_net_profit'), 2) }}</th>
                                        <th class="text-end">{{ number_format($dailyProfitTrend->avg('average_margin'), 1) }}%</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- PROFIT TABLE - Detailed Order Profits --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2"></i>
                            {{ __('auth.order_profit_breakdown') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-6">{{ $profitData->count() }} {{ __('auth.orders') }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="profitTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 100px;">{{ __('auth.order') }}</th>
                                        <th style="min-width: 120px;">{{ __('auth.date') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.location') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.department') }}</th>
                                        <th class="text-end" style="min-width: 120px;">{{ __('auth.revenue') }}</th>
                                        <th class="text-end" style="min-width: 120px;">{{ __('auth.cost') }}</th>
                                        <th class="text-end" style="min-width: 120px;">{{ __('auth.discount') }}</th>
                                        <th class="text-end" style="min-width: 120px;">{{ __('auth.gross_profit') }}</th>
                                        <th class="text-end" style="min-width: 120px;">{{ __('auth.net_profit') }}</th>
                                        <th class="text-end" style="min-width: 100px;">{{ __('auth.margin') }}</th>
                                        <th style="min-width: 150px;">{{ __('auth.items') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($profitDataPaginated as $order)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $order->order_number }}</span>
                                            <br><small class="text-muted">{{ ucfirst($order->order_type) }}</small>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $order->created_at->format('M d, Y') }}</span>
                                            <br><small class="text-muted">{{ $order->created_at->format('H:i A') }}</small>
                                        </td>
                                        <td>{{ $order->location }}</td>
                                        <td>{{ $order->department }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($order->total_revenue, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($order->total_cost, 2) }}</td>
                                        <td class="text-end text-danger">{{ currency_symbol() }}{{ number_format($order->total_discount, 2) }}</td>
                                        <td class="text-end {{ $order->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ currency_symbol() }}{{ number_format($order->gross_profit, 2) }}
                                        </td>
                                        <td class="text-end fw-bold {{ $order->net_profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ currency_symbol() }}{{ number_format($order->net_profit, 2) }}
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-{{ $order->profit_margin >= 10 ? 'success' : ($order->profit_margin >= 0 ? 'warning' : 'danger') }}">
                                                {{ number_format($order->profit_margin, 1) }}%
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-light-primary" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#items-{{ $order->order_id }}"
                                                    aria-expanded="false">
                                                <i class="ki-duotone ki-eye fs-2"></i>
                                                {{ $order->item_count }} items
                                            </button>
                                            <div class="collapse mt-2" id="items-{{ $order->order_id }}">
                                                <div class="card card-body p-2 bg-light">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>{{ __('auth.product') }}</th>
                                                                    <th class="text-center">{{ __('auth.qty') }}</th>
                                                                    <th class="text-end">{{ __('auth.unit_cost') }}</th>
                                                                    <th class="text-end">{{ __('auth.unit_price') }}</th>
                                                                    <th class="text-end">{{ __('auth.profit') }}</th>
                                                                    <th class="text-end">{{ __('auth.margin') }}</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($order->item_profits as $item)
                                                                <tr>
                                                                    <td>
                                                                        <span class="fw-bold">{{ $item->variant_name }}</span>
                                                                        <br><small class="text-muted">{{ $item->sku }}</small>
                                                                    </td>
                                                                    <td class="text-center">{{ number_format($item->quantity) }}</td>
                                                                    <td class="text-end">{{ currency_symbol() }}{{ number_format($item->unit_cost, 2) }}</td>
                                                                    <td class="text-end">{{ currency_symbol() }}{{ number_format($item->unit_price, 2) }}</td>
                                                                    <td class="text-end {{ $item->profit >= 0 ? 'text-success' : 'text-danger' }}">
                                                                        {{ currency_symbol() }}{{ number_format($item->profit, 2) }}
                                                                    </td>
                                                                    <td class="text-end">
                                                                        <span class="badge badge-light-{{ $item->profit_margin >= 10 ? 'success' : ($item->profit_margin >= 0 ? 'warning' : 'danger') }}">
                                                                            {{ number_format($item->profit_margin, 1) }}%
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4">{{ __('accounting.total_average') }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($profitDataPaginated->sum('total_revenue'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($profitDataPaginated->sum('total_cost'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($profitDataPaginated->sum('total_discount'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($profitDataPaginated->sum('gross_profit'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($profitDataPaginated->sum('net_profit'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($profitDataPaginated->avg('profit_margin'), 1) }}%</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        {{ $profitDataPaginated->links() }}
                        <small class="text-muted float-end">
                            {{ __('accounting.showing') }} {{ $profitDataPaginated->firstItem() ?? 0 }} - {{ $profitDataPaginated->lastItem() ?? 0 }} {{ __('accounting.of') }} {{ $profitDataPaginated->total() }} {{ __('auth.orders') }}
                        </small>
                    </div>
                </div>

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
                    </p>
                </div>

                @endif {{-- End of data check --}}
                
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- JAVASCRIPT - Export Functions --}}
{{-- ============================================================ --}}
<script>
function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __('accounting.table_not_found') }}');
        return;
    }
    
    try {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch (e) {
        alert('{{ __('accounting.export_error') }}: ' + e.message);
    }
}

function printReport() {
    window.print();
}
</script>
@endsection