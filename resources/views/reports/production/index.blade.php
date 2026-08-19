{{-- resources/views/reports/production/index.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_order_report'))

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
                                {{ __('pagination.production_order_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('pagination.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_orders') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto gap-2">
                            @if($paginatedOrders->count() > 0)
                            <button class="btn btn-sm btn-success" onclick="exportReport()">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
                            @endif
                            <a href="{{ route('production-orders.index') }}" class="btn btn-sm btn-primary">
                                <i class="ki-duotone ki-plus fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.new_order') }}</span>
                            </a>
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
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedOrders->count() }} {{ __('pagination.of') }} {{ $paginatedOrders->total() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.production.index') }}" id="filterForm">
                                    {{-- Row 1: Date Range --}}
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                            <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                            <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                            <select class="form-select" name="status" data-control="select2">
                                                @foreach($statuses as $s)
                                                    <option value="{{ $s['value'] }}" {{ $status == $s['value'] ? 'selected' : '' }}>
                                                        {{ $s['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                            <select class="form-select" name="location_id" data-control="select2">
                                                <option value="">{{ __('pagination.all_locations') }}</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    
                                    {{-- Row 2: Variant, Category, Payment --}}
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                            <select class="form-select" name="variant_id" data-control="select2">
                                                <option value="">{{ __('pagination.all_variants') }}</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}" {{ $variantId == $variant->id ? 'selected' : '' }}>
                                                        {{ $variant->name }} ({{ $variant->sku }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.category') }}</label>
                                            <select class="form-select" name="category_id" data-control="select2">
                                                <option value="">{{ __('pagination.all_categories') }}</option>
                                                @foreach($categories as $category)
                                                    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.payment_status') }}</label>
                                            <select class="form-select" name="has_payment">
                                                <option value="all" {{ $hasPayment == 'all' ? 'selected' : '' }}>{{ __('pagination.all') }}</option>
                                                <option value="with_payment" {{ $hasPayment == 'with_payment' ? 'selected' : '' }}>{{ __('pagination.with_payment') }}</option>
                                                <option value="without_payment" {{ $hasPayment == 'without_payment' ? 'selected' : '' }}>{{ __('pagination.without_payment') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.search') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-magnifier fs-2"></i>
                                                </span>
                                                <input type="text" class="form-control" name="search" 
                                                    value="{{ $search }}" placeholder="{{ __('pagination.search_orders') }}">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Row 3: Cost Range & Actions --}}
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.min_cost') }}</label>
                                            <input type="number" class="form-control" name="min_cost" 
                                                value="{{ $minCost }}" step="0.01" placeholder="0.00">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.max_cost') }}</label>
                                            <input type="number" class="form-control" name="max_cost" 
                                                value="{{ $maxCost }}" step="0.01" placeholder="10000.00">
                                        </div>
                                        <div class="col-md-6 d-flex align-items-end">
                                            <div class="d-flex gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                                    {{ __('pagination.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.production.index') }}" class="btn btn-light">
                                                    <i class="ki-duotone ki-cross fs-2"></i>
                                                    {{ __('pagination.clear') }}
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
                @if($paginatedOrders->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.summary_statistics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    {{-- Total Orders --}}
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-2">
                                                    <i class="ki-duotone ki-box fs-2tx text-primary">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <span class="fs-1 fw-bold text-gray-800">{{ number_format($summary['total_orders']) }}</span>
                                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_orders') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Status Breakdown --}}
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card card-flush h-100">
                                            <div class="card-body d-flex flex-column justify-content-center">
                                                <div class="text-gray-600 fw-semibold mb-2">{{ __('pagination.by_status') }}</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($statusBreakdown as $s)
                                                        <span class="badge badge-light-{{ $s->color }} fs-6 py-2 px-3">
                                                            {{ $s->label }}: {{ $s->count }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Cost Summary --}}
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush h-100">
                                            <div class="card-body d-flex flex-column justify-content-center">
                                                <div class="text-gray-600 fw-semibold mb-2">{{ __('pagination.cost_summary') }}</div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge badge-light-info fs-6 py-2 px-3">
                                                        {{ __('pagination.input') }}: {{ currency_symbol() }}{{ number_format($summary['total_input_cost'], 2) }}
                                                    </span>
                                                    <span class="badge badge-light-success fs-6 py-2 px-3">
                                                        {{ __('pagination.output') }}: {{ currency_symbol() }}{{ number_format($summary['total_output_cost'], 2) }}
                                                    </span>
                                                    <span class="badge badge-light-danger fs-6 py-2 px-3">
                                                        {{ __('pagination.total') }}: {{ currency_symbol() }}{{ number_format($summary['total_cost'], 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Profit & Payment --}}
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush bg-light-{{ $summary['total_profit'] >= 0 ? 'success' : 'danger' }} border border-{{ $summary['total_profit'] >= 0 ? 'success' : 'danger' }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-2">
                                                    <i class="ki-duotone ki-dollar fs-2tx text-{{ $summary['total_profit'] >= 0 ? 'success' : 'danger' }}">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </div>
                                                <span class="fs-1 fw-bold text-gray-800">
                                                    {{ currency_symbol() }}{{ number_format($summary['total_profit'], 2) }}
                                                </span>
                                                <span class="text-gray-600 fw-semibold">
                                                    {{ __('pagination.total_profit') }} 
                                                    ({{ $summary['with_payment'] }} {{ __('pagination.with_payment') }})
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Additional Stats --}}
                                <div class="row mt-6">
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="text-muted">{{ __('pagination.total_input_quantity') }}</span>
                                                    <div class="fs-3 fw-bold">{{ number_format($summary['total_input_quantity'], 2) }}</div>
                                                </div>
                                                <i class="ki-duotone ki-enter fs-2tx text-secondary">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-success border border-success border-dashed">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="text-muted">{{ __('pagination.total_output_quantity') }}</span>
                                                    <div class="fs-3 fw-bold text-success">{{ number_format($summary['total_output_quantity'], 2) }}</div>
                                                </div>
                                                <i class="ki-duotone ki-exit fs-2tx text-success">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card card-flush bg-light-warning border border-warning border-dashed">
                                            <div class="card-body d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="text-muted">{{ __('pagination.average_order_cost') }}</span>
                                                    <div class="fs-3 fw-bold text-warning">{{ currency_symbol() }}{{ number_format($summary['avg_cost'], 2) }}</div>
                                                </div>
                                                <i class="ki-duotone ki-calculator fs-2tx text-warning">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Charts Section --}}
                @if($paginatedOrders->count() > 0)
                <div class="row mb-6">
                    {{-- Daily Trend Chart --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.daily_trend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="dailyTrendChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Status Distribution --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.status_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="statusChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Production Orders Table --}}
                @if($paginatedOrders->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.production_orders') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedOrders->count() }} {{ __('pagination.of') }} {{ $paginatedOrders->total() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="productionTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4 min-w-140px">{{ __('pagination.order_number') }}</th>
                                                <th class="min-w-120px">{{ __('pagination.status') }}</th>
                                                <th class="min-w-150px">{{ __('pagination.location') }}</th>
                                                <th class="min-w-120px text-center">{{ __('pagination.input_qty') }}</th>
                                                <th class="min-w-120px text-center">{{ __('pagination.output_qty') }}</th>
                                                <th class="min-w-150px text-center">{{ __('pagination.input_cost') }}</th>
                                                <th class="min-w-150px text-center">{{ __('pagination.output_cost') }}</th>
                                                <th class="min-w-120px text-center">{{ __('pagination.profit') }}</th>
                                                <th class="min-w-150px">{{ __('pagination.created_by') }}</th>
                                                <th class="min-w-100px text-center">{{ __('pagination.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedOrders as $index => $order)
                                            @php
                                                $profit = $order->total_output_cost - $order->total_input_cost;
                                                $profitColor = $profit >= 0 ? 'success' : 'danger';
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold">{{ $order->production_number }}</div>
                                                    <div class="text-muted fs-7">{{ $order->created_at->format('Y-m-d H:i') }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $order->status_badge }} fs-7 py-2 px-3">
                                                        {{ $order->status_label }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $order->location->name ?? '-' }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold">{{ number_format($order->total_input_quantity, 2) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold text-success">{{ number_format($order->total_output_quantity, 2) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="text-muted">{{ currency_symbol() }}{{ number_format($order->total_input_cost, 2) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="text-primary">{{ currency_symbol() }}{{ number_format($order->total_output_cost, 2) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold text-{{ $profitColor }}">
                                                        {{ currency_symbol() }}{{ number_format($profit, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>{{ $order->createdBy->name ?? '-' }}</div>
                                                    @if($order->payment_method_id)
                                                        <span class="badge badge-light-success fs-8">
                                                            <i class="ki-duotone ki-wallet fs-2 me-1"></i>
                                                            {{ __('pagination.paid') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="d-flex justify-content-start flex-column">
                                                    <button class="btn btn-sm btn-light-primary" 
                                                        onclick="viewDetails({{ $order->id }})">
                                                        <i class="ki-duotone ki-eye fs-2">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </button>
                                                    @if($order->status === \App\Models\ProductionOrder::STATUS_COMPLETED)
                                                        <button class="btn btn-sm btn-light-success" 
                                                            onclick="viewLogs({{ $order->id }})">
                                                            <i class="ki-duotone ki-calendar-8 fs-2">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="3" class="text-end fw-bold">{{ __('pagination.totals') }}:</td>
                                                <td class="text-center fw-bold">{{ number_format($paginatedOrders->sum('total_input_quantity'), 2) }}</td>
                                                <td class="text-center fw-bold">{{ number_format($paginatedOrders->sum('total_output_quantity'), 2) }}</td>
                                                <td class="text-center fw-bold">{{ currency_symbol() }}{{ number_format($paginatedOrders->sum('total_input_cost'), 2) }}</td>
                                                <td class="text-center fw-bold">{{ currency_symbol() }}{{ number_format($paginatedOrders->sum('total_output_cost'), 2) }}</td>
                                                <td class="text-center fw-bold text-{{ $paginatedOrders->sum(function($o) { return $o->total_output_cost - $o->total_input_cost; }) >= 0 ? 'success' : 'danger' }}">
                                                    {{ currency_symbol() }}{{ number_format($paginatedOrders->sum(function($o) { return $o->total_output_cost - $o->total_input_cost; }), 2) }}
                                                </td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $paginatedOrders,
                                        'pageName' => 'page',
                                        'perPageName' => 'per_page',
                                        'showPerPage' => true
                                    ])
                                </div>
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
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.no_production_orders_found') }}</p>
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

{{-- View Details Modal --}}
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="detailsModalLabel">
                    <i class="ki-duotone ki-information-5 fs-2 me-2 text-primary">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    {{ __('pagination.production_order_details') }}
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('pagination.close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- View Logs Modal --}}
<div class="modal fade" id="logsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="ki-duotone ki-history fs-2 me-2 text-info">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    {{ __('pagination.inventory_logs') }}
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="logsModalBody">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('pagination.close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
// ─── View Details ──────────────────────────────────────────────
function viewDetails(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
    const body = document.getElementById('detailsModalBody');
    
    body.innerHTML = `
        <div class="text-center py-10">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    const url = `/reports/production/detail/${orderId}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            // ─── Order Header ──────────────────────────────────────────
            html += `
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">${data.order.production_number}</h4>
                        <div class="text-muted">${data.order.created_at}</div>
                    </div>
                    <div>
                        <span class="badge badge-light-${data.order.status_badge} fs-6 py-2 px-4">
                            ${data.order.status_label}
                        </span>
                        ${data.order.payment_method_id ? `
                            <span class="badge badge-light-success fs-6 py-2 px-4 ms-2">
                                <i class="ki-duotone ki-wallet fs-2 me-1"></i>
                                Paid
                            </span>
                        ` : ''}
                    </div>
                </div>
            `;
            
            // ─── Metrics Cards ─────────────────────────────────────────
            html += `
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body text-center">
                                <span class="text-muted">Duration</span>
                                <div class="fs-2 fw-bold">${data.metrics.duration_hours}h</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body text-center">
                                <span class="text-muted">Yield</span>
                                <div class="fs-2 fw-bold text-success">${data.metrics.input_yield.toFixed(1)}%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body text-center">
                                <span class="text-muted">Efficiency</span>
                                <div class="fs-2 fw-bold text-info">${data.metrics.cost_efficiency.toFixed(1)}%</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-${data.metrics.profit >= 0 ? 'success' : 'danger'} border border-${data.metrics.profit >= 0 ? 'success' : 'danger'} border-dashed">
                            <div class="card-body text-center">
                                <span class="text-muted">Profit</span>
                                <div class="fs-2 fw-bold text-${data.metrics.profit >= 0 ? 'success' : 'danger'}">
                                    {{ currency_symbol() }}${data.metrics.profit.toFixed(2)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // ─── Inputs Table ──────────────────────────────────────────
            html += `
                <h6 class="fw-bold mb-3">Inputs (${data.input_stats.total_planned} planned, ${data.input_stats.total_actual} actual)</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Variant</th>
                                <th>Planned</th>
                                <th>Actual</th>
                                <th>Waste</th>
                                <th>Yield</th>
                                <th>Cost</th>
                                <th>Quality</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.order.inputs.forEach(input => {
                const yieldPct = input.planned_quantity > 0 ? (input.actual_quantity / input.planned_quantity * 100) : 0;
                const qualityColors = {
                    'pending': 'secondary',
                    'accepted': 'success',
                    'rejected': 'danger'
                };
                html += `
                    <tr>
                        <td>${input.productVariant?.name || 'N/A'}</td>
                        <td>${input.planned_quantity}</td>
                        <td>${input.actual_quantity}</td>
                        <td>${input.waste_quantity}</td>
                        <td>${yieldPct.toFixed(1)}%</td>
                        <td>{{ currency_symbol() }}${input.actual_cost.toFixed(2)}</td>
                        <td><span class="badge badge-light-${qualityColors[input.quality_status] || 'secondary'}">${input.quality_status}</span></td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // ─── Outputs Table ─────────────────────────────────────────
            html += `
                <h6 class="fw-bold mb-3">Outputs (${data.output_stats.total_planned} planned, ${data.output_stats.total_actual} actual)</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Variant</th>
                                <th>Strategy</th>
                                <th>Planned</th>
                                <th>Actual</th>
                                <th>Defective</th>
                                <th>Yield</th>
                                <th>Cost</th>
                                <th>Batch</th>
                                <th>Quality</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.order.outputs.forEach(output => {
                const yieldPct = output.planned_quantity > 0 ? (output.actual_quantity / output.planned_quantity * 100) : 0;
                const qualityColors = {
                    'pending': 'secondary',
                    'approved': 'success',
                    'rejected': 'danger'
                };
                html += `
                    <tr>
                        <td>${output.productVariant?.name || 'N/A'}</td>
                        <td><span class="badge badge-light-${output.inventory_strategy === 'batch' ? 'info' : output.inventory_strategy === 'serial' ? 'warning' : 'primary'}">${output.inventory_strategy}</span></td>
                        <td>${output.planned_quantity}</td>
                        <td class="text-success fw-bold">${output.actual_quantity}</td>
                        <td class="text-danger">${output.defective_quantity}</td>
                        <td>${yieldPct.toFixed(1)}%</td>
                        <td>{{ currency_symbol() }}${output.production_cost.toFixed(2)}</td>
                        <td>${output.batch_number || '-'}</td>
                        <td><span class="badge badge-light-${qualityColors[output.quality_status] || 'secondary'}">${output.quality_status}</span></td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            body.innerHTML = html;
        })
        .catch(error => {
            body.innerHTML = `
                <div class="text-center py-10 text-danger">
                    <i class="ki-duotone ki-cross-circle fs-4tx mb-4"></i>
                    <h4>Error Loading Details</h4>
                    <p class="text-muted">${error.message}</p>
                </div>
            `;
        });
}

// ─── View Logs ──────────────────────────────────────────────────
function viewLogs(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('logsModal'));
    const body = document.getElementById('logsModalBody');
    
    body.innerHTML = `
        <div class="text-center py-10">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    const url = `/reports/production/detail/${orderId}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            let html = '';
            
            // ─── Inventory Logs ─────────────────────────────────────────
            html += `
                <h6 class="fw-bold mb-3">Inventory Logs</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Variant</th>
                                <th>Change</th>
                                <th>Before</th>
                                <th>After</th>
                                <th>Reason</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (data.inventory_logs && data.inventory_logs.length > 0) {
                data.inventory_logs.forEach(log => {
                    const changeColor = log.quantity_change >= 0 ? 'success' : 'danger';
                    html += `
                        <tr>
                            <td>${log.variant_name || 'N/A'}</td>
                            <td class="text-${changeColor} fw-bold">${log.quantity_change >= 0 ? '+' : ''}${log.quantity_change}</td>
                            <td>${log.quantity_before}</td>
                            <td>${log.quantity_after}</td>
                            <td><span class="badge badge-light-info">${log.reason}</span></td>
                            <td>${log.created_at}</td>
                        </tr>
                    `;
                });
            } else {
                html += `
                    <tr>
                        <td colspan="6" class="text-center text-muted">No inventory logs found</td>
                    </tr>
                `;
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            // ─── Batch Logs ────────────────────────────────────────────
            html += `
                <h6 class="fw-bold mb-3">Batch Logs</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Batch Number</th>
                                <th>Variant</th>
                                <th>Type</th>
                                <th>Change</th>
                                <th>Before</th>
                                <th>After</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (data.batch_logs && data.batch_logs.length > 0) {
                data.batch_logs.forEach(log => {
                    const typeColors = {
                        'received': 'success',
                        'consumed': 'danger',
                        'produced': 'info',
                        'expired': 'warning',
                        'adjusted': 'secondary'
                    };
                    html += `
                        <tr>
                            <td><span class="badge badge-light-dark">${log.batch_number}</span></td>
                            <td>${log.variant_name}</td>
                            <td><span class="badge badge-light-${typeColors[log.type] || 'secondary'}">${log.type}</span></td>
                            <td class="${log.quantity_change >= 0 ? 'text-success' : 'text-danger'} fw-bold">${log.quantity_change >= 0 ? '+' : ''}${log.quantity_change}</td>
                            <td>${log.quantity_before}</td>
                            <td>${log.quantity_after}</td>
                            <td>${log.event_date}</td>
                        </tr>
                    `;
                });
            } else {
                html += `
                    <tr>
                        <td colspan="7" class="text-center text-muted">No batch logs found</td>
                    </tr>
                `;
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            body.innerHTML = html;
        })
        .catch(error => {
            body.innerHTML = `
                <div class="text-center py-10 text-danger">
                    <i class="ki-duotone ki-cross-circle fs-4tx mb-4"></i>
                    <h4>Error Loading Logs</h4>
                    <p class="text-muted">${error.message}</p>
                </div>
            `;
        });
}

// ─── Export Report ──────────────────────────────────────────────
function exportReport() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    window.location.href = `/reports/production/export?${params.toString()}`;
}

// ─── Charts ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    @if($paginatedOrders->count() > 0)
        // ─── Daily Trend Chart ──────────────────────────────────────
        const dailyData = @json($dailyTrend);
        if (dailyData.length > 0) {
            const trendChart = new ApexCharts(document.querySelector("#dailyTrendChart"), {
                series: [
                    {
                        name: 'Orders',
                        data: dailyData.map(d => d.count),
                        type: 'bar'
                    },
                    {
                        name: 'Total Cost',
                        data: dailyData.map(d => d.total_cost),
                        type: 'line'
                    }
                ],
                chart: {
                    type: 'bar',
                    height: 300,
                    toolbar: { show: true }
                },
                plotOptions: {
                    bar: { horizontal: false, columnWidth: '55%' }
                },
                stroke: { width: [0, 3], curve: 'smooth' },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: dailyData.map(d => d.date),
                    labels: { rotate: -45, trim: true, style: { fontSize: '11px' } }
                },
                yaxis: [
                    { title: { text: 'Orders' } },
                    { opposite: true, title: { text: 'Total Cost' } }
                ],
                colors: ['#3E97FF', '#50CD89'],
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val, { seriesIndex }) {
                            return seriesIndex === 0 ? val + ' orders' : '{{ currency_symbol() }}' + val.toFixed(2);
                        }
                    }
                }
            });
            trendChart.render();
        }
        
        // ─── Status Distribution Chart ──────────────────────────────
        const statusData = @json($statusBreakdown);
        if (statusData.length > 0) {
            const statusChart = new ApexCharts(document.querySelector("#statusChart"), {
                series: statusData.map(s => s.count),
                chart: { type: 'donut', height: 300 },
                labels: statusData.map(s => s.label),
                colors: statusData.map(s => {
                    const colors = {
                        'secondary': '#A1A5B7',
                        'warning': '#FFC700',
                        'success': '#50CD89',
                        'danger': '#F1416C',
                        'primary': '#3E97FF',
                        'info': '#7239EA'
                    };
                    return colors[s.color] || '#A1A5B7';
                }),
                legend: { position: 'bottom', fontSize: '12px' },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total',
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    }
                                }
                            }
                        }
                    }
                },
                tooltip: { y: { formatter: function(val) { return val + ' orders'; } } }
            });
            statusChart.render();
        }
    @endif
});
</script>
@endpush

@endsection