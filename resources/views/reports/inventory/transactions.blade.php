{{-- resources/views/reports/inventory/transactions.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_transactions'))

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
                                {{ __('pagination.inventory_transactions') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_transactions') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($transactions->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'transactionsTable', filename: 'inventory_transactions_{{ date('Y_m_d') }}', sheetName: 'Transactions'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'transactionsTable', filename: 'inventory_transactions_{{ date('Y_m_d') }}', format: 'csv'})">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('pagination.export_to_csv') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                {{-- Filter Section --}}
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form method="GET" action="{{ route('reports.inventory.transactions') }}" id="filterForm">
                            {{-- First Line - Date Range and Type --}}
                            <div class="row g-3 mb-3">
                                {{-- Date Range --}}
                                <div class="col-md-4">
                                    <label class="form-label required fw-semibold">{{ __('pagination.date_range') }}</label>
                                    <div class="d-flex gap-2">
                                        <div class="input-group w-100">
                                            <span class="input-group-text">
                                                <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                            </span>
                                            <input type="date" class="form-control" name="start_date" 
                                                value="{{ $startDate }}" required>
                                        </div>
                                        <div class="input-group w-100">
                                            <span class="input-group-text">
                                                <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                            </span>
                                            <input type="date" class="form-control" name="end_date" 
                                                value="{{ $endDate }}" required>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Transaction Type --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('pagination.transaction_type') }}</label>
                                    <select class="form-select" name="type">
                                        <option value="all">{{ __('pagination.all_types') }}</option>
                                        <option value="purchase" {{ $type == 'purchase' ? 'selected' : '' }}>{{ __('pagination.purchase') }}</option>
                                        <option value="sale" {{ $type == 'sale' ? 'selected' : '' }}>{{ __('pagination.sale') }}</option>
                                        <option value="return" {{ $type == 'return' ? 'selected' : '' }}>{{ __('pagination.return') }}</option>
                                        <option value="adjustment" {{ $type == 'adjustment' ? 'selected' : '' }}>{{ __('pagination.adjustment') }}</option>
                                        <option value="transfer_in" {{ $type == 'transfer_in' ? 'selected' : '' }}>{{ __('pagination.transfer_in') }}</option>
                                        <option value="transfer_out" {{ $type == 'transfer_out' ? 'selected' : '' }}>{{ __('pagination.transfer_out') }}</option>
                                    </select>
                                </div>
                                
                                {{-- Product Variant --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                    <select class="form-select" name="variant_id" data-control="select2">
                                        <option value="">{{ __('pagination.all_variants') }}</option>
                                        @foreach($variants ?? [] as $variant)
                                            <option value="{{ $variant->id }}" {{ ($variantId ?? '') == $variant->id ? 'selected' : '' }}>
                                                {{ Str::limit($variant->name, 30) }} ({{ $variant->sku }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- Location & Department --}}
                                <div class="col-md-3">
                                    <x-liveblade-dependent-dropdown 
                                        id="filter_location_department"
                                        parentName="location_id"
                                        childName="department_id"
                                        parentLabel="auth.location"
                                        childLabel="accounting.department"
                                        :parentOptions="$locations ?? []"
                                        :childOptions="$departments ?? []"
                                        route="{{ route('get.departments') }}"
                                        selectedParent="{{ $locationId ?? null }}"
                                        selectedChild="{{ $departmentId ?? null }}"
                                        skipAjax="false"
                                    />
                                </div>
                            </div>

                            {{-- Second Line - Action Buttons --}}
                            <div class="row">
                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="applyFilters">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('pagination.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.inventory.transactions') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('pagination.clear_filters') }}
                                    </a>
                                    @if(isset($transactions) && $transactions->count() > 0)
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('pagination.showing') }} <strong>{{ $transactions->count() }}</strong> {{ __('pagination.transactions') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Summary Statistics --}}
                @if($transactions->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.transaction_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $totalTransactions = $transactions->total();
                                        $totalQuantity = $typeSummary->sum('total_quantity');
                                        $positiveTransactions = $typeSummary->whereIn('type', ['purchase', 'return', 'transfer_in'])->sum('total_quantity');
                                        $negativeTransactions = $typeSummary->whereIn('type', ['sale', 'adjustment', 'transfer_out'])->sum('total_quantity');
                                        $netChange = $positiveTransactions - abs($negativeTransactions);
                                    @endphp
                                    
                                    @foreach([
                                        ['key' => 'total_transactions', 'color' => 'primary', 'icon' => 'ki-repeat', 'label' => 'total_transactions', 'value' => number_format($totalTransactions)],
                                        ['key' => 'total_quantity', 'color' => 'success', 'icon' => 'ki-barcode', 'label' => 'total_quantity_moved', 'value' => number_format(abs($totalQuantity))],
                                        ['key' => 'positive_movements', 'color' => 'info', 'icon' => 'ki-arrow-up', 'label' => 'positive_movements', 'value' => '+' . number_format($positiveTransactions)],
                                        ['key' => 'negative_movements', 'color' => 'warning', 'icon' => 'ki-arrow-down', 'label' => 'negative_movements', 'value' => '-' . number_format(abs($negativeTransactions))],
                                        ['key' => 'net_change', 'color' => 'danger', 'icon' => 'ki-calculator', 'label' => 'net_change', 'value' => $netChange >= 0 ? '+' . number_format($netChange) : number_format($netChange)],
                                        ['key' => 'avg_daily', 'color' => 'secondary', 'icon' => 'ki-clock', 'label' => 'avg_daily_transactions', 'value' => number_format($totalTransactions / max(1, \Carbon\Carbon::parse($startDate)->diffInDays($endDate) + 1), 1)],
                                    ] as $stat)
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-3">
                                                    <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}">
                                                        @for($i = 1; $i <= 2; $i++)
                                                        <span class="path{{ $i }}"></span>
                                                        @endfor
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-2 fw-bold text-gray-800">
                                                        {{ $stat['value'] }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold fs-7">
                                                    {{ __('pagination.' . $stat['label']) }}
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

                {{-- Charts Section --}}
                @if($transactions->count() > 0)
                <div class="row mb-6">
                    {{-- Transaction Type Distribution --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.transaction_type_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="typeDistributionChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Daily Transaction Trend with Moving Average --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.daily_transaction_trend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="dailyTrendChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                    {{-- Transaction Volume by Type (Bar Chart) --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.transaction_volume_by_type') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="volumeByTypeChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Type Breakdown Table --}}
                @if($typeSummary->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.transaction_type_breakdown') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="min-w-150px">{{ __('pagination.type') }}</th>
                                                <th class="min-w-120px text-center">{{ __('pagination.transaction_count') }}</th>
                                                <th class="min-w-150px text-end">{{ __('pagination.total_quantity') }}</th>
                                                <th class="min-w-120px text-center">{{ __('pagination.avg_quantity') }}</th>
                                                <th class="min-w-200px">{{ __('pagination.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalCount = $typeSummary->sum('count');
                                                $totalQuantityAll = $typeSummary->sum('total_quantity');
                                            @endphp
                                            @foreach($typeSummary as $summaryItem)
                                            @php
                                                $typeColors = [
                                                    'purchase' => 'success',
                                                    'sale' => 'danger',
                                                    'return' => 'info',
                                                    'adjustment' => 'warning',
                                                    'transfer_in' => 'primary',
                                                    'transfer_out' => 'secondary'
                                                ];
                                                $typeColor = $typeColors[$summaryItem->type] ?? 'dark';
                                                $percentage = $totalCount > 0 ? ($summaryItem->count / $totalCount) * 100 : 0;
                                                $avgQuantity = $summaryItem->count > 0 ? $summaryItem->total_quantity / $summaryItem->count : 0;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light-{{ $typeColor }} fs-6 py-2 px-3">
                                                        <i class="ki-duotone 
                                                            @if($summaryItem->type == 'purchase') ki-basket
                                                            @elseif($summaryItem->type == 'sale') ki-cart
                                                            @elseif($summaryItem->type == 'return') ki-arrow-right
                                                            @elseif($summaryItem->type == 'adjustment') ki-switch
                                                            @elseif($summaryItem->type == 'transfer_in') ki-enter
                                                            @elseif($summaryItem->type == 'transfer_out') ki-exit
                                                            @else ki-status
                                                            @endif fs-3 me-2">
                                                        </i>
                                                        {{ __('pagination.' . $summaryItem->type) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold fs-5">{{ number_format($summaryItem->count) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold {{ $summaryItem->total_quantity >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $summaryItem->total_quantity >= 0 ? '+' : '' }}{{ number_format($summaryItem->total_quantity) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-secondary">{{ number_format($avgQuantity, 1) }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 10px;">
                                                            <div class="progress-bar bg-{{ $typeColor }}" 
                                                                style="width: {{ $percentage }}%"
                                                                role="progressbar"></div>
                                                        </div>
                                                        <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                            {{ number_format($percentage, 1) }}%
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td class="fw-bold">{{ __('pagination.total') }}</td>
                                                <td class="text-center fw-bold">{{ number_format($totalCount) }}</td>
                                                <td class="text-end fw-bold">
                                                    <span class="{{ $totalQuantityAll >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $totalQuantityAll >= 0 ? '+' : '' }}{{ number_format($totalQuantityAll) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">-</td>
                                                <td class="text-end fw-bold">100%</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Transactions Table --}}
                @if($transactions->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100 flex-wrap gap-3">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.transaction_details') }}</h3>
                                    </div>
                                    <div>
                                        <span class="badge badge-light-primary fs-7">
                                            {{ $transactions->total() }} {{ __('pagination.total_transactions') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="transactionsTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4 min-w-150px">{{ __('pagination.date_time') }}</th>
                                                <th class="min-w-120px">{{ __('pagination.sku') }}</th>
                                                <th class="min-w-200px">{{ __('pagination.product') }}</th>
                                                <th class="min-w-100px text-center">{{ __('pagination.type') }}</th>
                                                <th class="min-w-100px text-center">{{ __('pagination.quantity') }}</th>
                                                <th class="min-w-150px">{{ __('pagination.department') }}</th>
                                                <th class="min-w-150px">{{ __('pagination.location') }}</th>
                                                <th class="min-w-150px">{{ __('pagination.reference') }}</th>
                                                <th class="min-w-200px">{{ __('pagination.notes') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($transactions as $transaction)
                                            @php
                                                $typeColors = [
                                                    'purchase' => 'success',
                                                    'sale' => 'danger',
                                                    'return' => 'info',
                                                    'adjustment' => 'warning',
                                                    'transfer_in' => 'primary',
                                                    'transfer_out' => 'secondary'
                                                ];
                                                $typeColor = $typeColors[$transaction->type] ?? 'dark';
                                                $quantityColor = $transaction->quantity >= 0 ? 'success' : 'danger';
                                                $quantitySign = $transaction->quantity >= 0 ? '+' : '';
                                                
                                                // Get inventory item
                                                $inventoryItem = $transaction->InventoryItems;
                                                
                                                // Try multiple ways to get variant
                                                $variant = null;
                                                $product = null;
                                                $department = null;
                                                $location = null;
                                                
                                                if ($inventoryItem) {
                                                    $variant = $inventoryItem->variant;
                                                    $department = $inventoryItem->departmentItem;
                                                    $location = $inventoryItem->itemLocation;
                                                }
                                                
                                                if ($variant) {
                                                    $product = $variant->product;
                                                }
                                                
                                                // If still no variant, try to get from notes (custom parsing for your data)
                                                $notesSku = null;
                                                if (!$variant && $transaction->notes && preg_match('/Sold (\d+) units of ([A-Z0-9-]+)/', $transaction->notes, $matches)) {
                                                    $notesSku = $matches[2] ?? null;
                                                    if ($notesSku) {
                                                        $variant = ProductVariant::where('sku', $notesSku)
                                                            ->where('tenant_id', $tenantId)
                                                            ->first();
                                                        if ($variant) {
                                                            $product = $variant->product;
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold">{{ $transaction->created_at ? $transaction->created_at->format('Y-m-d') : '-' }}</div>
                                                    <small class="text-muted">{{ $transaction->created_at ? $transaction->created_at->format('H:i:s') : '-' }}</small>
                                                </td>
                                                <td>
                                                    @if($variant)
                                                        <div class="fw-semibold text-primary">{{ $variant->sku ?? 'N/A' }}</div>
                                                        @if($variant->barcode)
                                                            <small class="text-muted">{{ $variant->barcode }}</small>
                                                        @endif
                                                    @else
                                                        <div class="fw-semibold text-muted">SKU Not Found</div>
                                                        @if($notesSku)
                                                            <small class="text-warning">From notes: {{ $notesSku }}</small>
                                                        @elseif($inventoryItem && $inventoryItem->variant_id)
                                                            <small class="text-danger">Variant ID: {{ $inventoryItem->variant_id }}</small>
                                                        @else
                                                            <small class="text-muted">Inventory ID: {{ $transaction->inventory_id ?? '?' }}</small>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($variant)
                                                        <div class="d-flex align-items-center">
                                                            @if($variant->image_url)
                                                                <div class="symbol symbol-40px me-3">
                                                                    <img src="{{ productVariantImage($variant->image_url) }}" class="rounded" alt="{{ $variant->name }}">
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <div class="fw-bold">{{ $variant->name ?? 'Unknown Product' }}</div>
                                                                @if($product)
                                                                    <div class="text-muted fs-7">{{ $product->name ?? '' }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @elseif($notesSku)
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-40px me-3 bg-light-warning">
                                                                <i class="ki-duotone ki-danger fs-2 text-warning"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-warning">{{ __('pagination.product_from_notes') }}</div>
                                                                <small class="text-muted">SKU: {{ $notesSku }}</small>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-40px me-3 bg-light-secondary">
                                                                <i class="ki-duotone ki-box fs-2 text-muted"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold text-muted">{{ __('pagination.product_not_found') }}</div>
                                                                @if($inventoryItem && $inventoryItem->variant_id)
                                                                    <small class="text-muted">Variant ID: {{ $inventoryItem->variant_id }}</small>
                                                                @elseif($transaction->inventory_id)
                                                                    <small class="text-muted">Inventory ID: {{ $transaction->inventory_id }}</small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $typeColor }} py-2 px-3">
                                                        <i class="ki-duotone 
                                                            @if($transaction->type == 'purchase') ki-basket
                                                            @elseif($transaction->type == 'sale') ki-cart
                                                            @elseif($transaction->type == 'return') ki-arrow-right
                                                            @elseif($transaction->type == 'adjustment') ki-switch
                                                            @elseif($transaction->type == 'transfer_in') ki-enter
                                                            @elseif($transaction->type == 'transfer_out') ki-exit
                                                            @else ki-status
                                                            @endif fs-3 me-2">
                                                        </i>
                                                        {{ __('pagination.' . $transaction->type) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold fs-5 text-{{ $quantityColor }}">
                                                        {{ $quantitySign }}{{ number_format($transaction->quantity) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($department)
                                                        <span class="badge badge-light-primary">
                                                            <i class="ki-duotone ki-building fs-3 me-1"></i>
                                                            {{ $department->name ?? '-' }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-light-secondary">
                                                            <i class="ki-duotone ki-building fs-3 me-1"></i>
                                                            {{ __('pagination.not_assigned') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($location)
                                                        <span class="badge badge-light-info">
                                                            <i class="ki-duotone ki-location fs-3 me-1"></i>
                                                            {{ $location->name ?? '-' }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-light-secondary">
                                                            <i class="ki-duotone ki-location fs-3 me-1"></i>
                                                            {{ __('pagination.not_assigned') }}
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($transaction->reference_id && $transaction->reference_type)
                                                        <a href="#" class="text-hover-primary text-decoration-none">
                                                            <span class="badge badge-light-dark">
                                                                <i class="ki-duotone ki-document fs-3 me-1"></i>
                                                                {{ strtoupper(str_replace('_', ' ', $transaction->reference_type)) }} 
                                                                <strong>#{{ $transaction->reference_id }}</strong>
                                                            </span>
                                                        </a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($transaction->notes)
                                                        <span class="text-muted fs-7" data-bs-toggle="tooltip" title="{{ $transaction->notes }}">
                                                            {{ Str::limit($transaction->notes, 40) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        @php
                                            $pageTotalQuantity = $transactions->sum('quantity');
                                            $pagePositiveQuantity = $transactions->where('quantity', '>', 0)->sum('quantity');
                                            $pageNegativeQuantity = abs($transactions->where('quantity', '<', 0)->sum('quantity'));
                                        @endphp
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold">{{ __('pagination.current_page') }}: </td>
                                                <td class="text-center fw-bold">
                                                    <span class="text-{{ $pageTotalQuantity >= 0 ? 'success' : 'danger' }}">
                                                        {{ $pageTotalQuantity >= 0 ? '+' : '' }}{{ number_format($pageTotalQuantity) }}
                                                    </span>
                                                    <div class="text-muted fs-8">
                                                        +{{ number_format($pagePositiveQuantity) }} / -{{ number_format($pageNegativeQuantity) }}
                                                    </div>
                                                </td>
                                                <td colspan="4"></td>
                                            </tr>
                                            @if($transactions->total() > $transactions->count())
                                            <tr>
                                                <td colspan="4" class="text-end fw-bold text-muted">{{ __('pagination.grand_total') }}: </td>
                                                <td class="text-center fw-bold">
                                                    <span class="text-{{ $netChange >= 0 ? 'success' : 'danger' }}">
                                                        {{ $netChange >= 0 ? '+' : '' }}{{ number_format($netChange) }}
                                                    </span>
                                                    <div class="text-muted fs-8">
                                                        +{{ number_format($positiveTransactions) }} / -{{ number_format($negativeTransactions) }}
                                                    </div>
                                                </td>
                                                <td colspan="4"></td>
                                            </tr>
                                            @endif
                                        </tfoot>
                                    </table>
                                </div>
                                
                                {{-- Pagination Component --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $transactions,
                                        'pageName' => 'page',
                                        'perPageName' => 'per_page',
                                        'showPerPage' => true
                                    ])
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
    // ============================================
    // Inventory Transactions Report Scripts
    // ============================================
    
    // Global translations
    window.transactionTranslations = {
        purchase: '{{ __("pagination.purchase") }}',
        sale: '{{ __("pagination.sale") }}',
        return: '{{ __("pagination.return") }}',
        adjustment: '{{ __("pagination.adjustment") }}',
        transfer_in: '{{ __("pagination.transfer_in") }}',
        transfer_out: '{{ __("pagination.transfer_out") }}',
        transactions: '{{ __("pagination.transactions") }}'
    };
</script>

@if($transactions->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ============================================
        // 1. Transaction Type Distribution Chart (Donut)
        // ============================================
        const typeData = @json($typeSummary);
        
        if (typeData && typeData.length > 0) {
            const typeSeries = typeData.map(item => item.count);
            const typeLabels = typeData.map(item => {
                const labelMap = {
                    'purchase': 'Purchase',
                    'sale': 'Sale',
                    'return': 'Return',
                    'adjustment': 'Adjustment',
                    'transfer_in': 'Transfer In',
                    'transfer_out': 'Transfer Out'
                };
                return labelMap[item.type] || item.type;
            });
            
            const typeColors = {
                'purchase': '#50CD89',
                'sale': '#F1416C',
                'return': '#3E97FF',
                'adjustment': '#FFC700',
                'transfer_in': '#7239EA',
                'transfer_out': '#7E8299'
            };
            
            const seriesColors = typeData.map(item => typeColors[item.type] || '#A8A8A8');
            
            const typeChartElement = document.querySelector("#typeDistributionChart");
            if (typeChartElement) {
                const typeChart = new ApexCharts(typeChartElement, {
                    series: typeSeries,
                    chart: {
                        type: 'donut',
                        height: 350,
                        width: '100%',
                        toolbar: {
                            show: true,
                            tools: {
                                download: true
                            }
                        }
                    },
                    labels: typeLabels,
                    colors: seriesColors,
                    legend: {
                        position: 'bottom',
                        fontSize: '12px',
                        labels: {
                            colors: '#5B5B5B'
                        }
                    },
                    tooltip: {
                        y: {
                            formatter: function(val, { seriesIndex }) {
                                const total = typeSeries.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                return `${val} ${window.transactionTranslations.transactions} (${percentage}%)`;
                            }
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: function(val, opts) {
                            const total = typeSeries.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                            return `${percentage}%`;
                        },
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: {
                                height: 300
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }]
                });
                typeChart.render();
            }
        }
        
        // ============================================
        // 2. Daily Transaction Trend Chart (with Moving Average)
        // ============================================
        @php
            $dailyTrendData = [];
            if (isset($dailyTrend) && $dailyTrend->count() > 0) {
                foreach ($dailyTrend as $item) {
                    $dailyTrendData[] = [
                        'date' => $item->date,
                        'count' => $item->count,
                        'quantity' => $item->quantity
                    ];
                }
            }
        @endphp

        const dailyTrendData = @json($dailyTrendData);
        const dailyLabels = dailyTrendData.map(item => {
            const date = new Date(item.date);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });
        const dailyCounts = dailyTrendData.map(item => item.count);

        // Calculate 7-day moving average
        function calculateMovingAverage(data, windowSize = 7) {
            const result = [];
            for (let i = 0; i < data.length; i++) {
                let sum = 0;
                let count = 0;
                for (let j = Math.max(0, i - windowSize + 1); j <= i; j++) {
                    sum += data[j];
                    count++;
                }
                result.push(count > 0 ? sum / count : 0);
            }
            return result;
        }

        const movingAverage = calculateMovingAverage(dailyCounts);

        const trendChartElement = document.querySelector("#dailyTrendChart");
        if (trendChartElement && dailyCounts.length > 0) {
            new ApexCharts(trendChartElement, {
                series: [
                    {
                        name: '{{ __("pagination.transactions") }}',
                        data: dailyCounts,
                        type: 'bar'
                    },
                    {
                        name: '{{ __("pagination.7_day_average") }}',
                        data: movingAverage,
                        type: 'line'
                    }
                ],
                chart: {
                    type: 'line',
                    height: 320,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true,
                            reset: true
                        }
                    },
                    zoom: {
                        enabled: true,
                        type: 'x'
                    }
                },
                stroke: {
                    width: [0, 3],
                    curve: 'smooth'
                },
                fill: {
                    opacity: [0.8, 1]
                },
                plotOptions: {
                    bar: {
                        columnWidth: '60%',
                        borderRadius: 4
                    }
                },
                xaxis: {
                    categories: dailyLabels,
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '10px',
                            colors: '#5B5B5B'
                        },
                        trim: true
                    },
                    title: {
                        text: '{{ __("pagination.date") }}',
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: '{{ __("pagination.number_of_transactions") }}',
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    },
                    min: 0,
                    tickAmount: 5
                },
                colors: ['#3E97FF', '#F1416C'],
                markers: {
                    size: [0, 3],
                    colors: ['#3E97FF', '#F1416C']
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val, { seriesIndex }) {
                            if (seriesIndex === 1) {
                                return val.toFixed(1) + ' {{ __("pagination.avg") }}';
                            }
                            return val + ' {{ __("pagination.transactions") }}';
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center',
                    fontSize: '12px'
                }
            }).render();
        }

        // ============================================
        // 3. Transaction Volume by Type (Bar Chart)
        // ============================================
        const volumeByType = @json($typeSummary);
        const volumeLabels = volumeByType.map(item => {
            const labelMap = {
                'purchase': '{{ __("pagination.purchase") }}',
                'sale': '{{ __("pagination.sale") }}',
                'return': '{{ __("pagination.return") }}',
                'adjustment': '{{ __("pagination.adjustment") }}',
                'transfer_in': '{{ __("pagination.transfer_in") }}',
                'transfer_out': '{{ __("pagination.transfer_out") }}'
            };
            return labelMap[item.type] || item.type;
        });
        const volumeData = volumeByType.map(item => Math.abs(item.total_quantity));

        const volumeColors = {
            'purchase': '#50CD89',
            'sale': '#F1416C',
            'return': '#3E97FF',
            'adjustment': '#FFC700',
            'transfer_in': '#7239EA',
            'transfer_out': '#7E8299'
        };

        const volumeChartColors = volumeByType.map(item => volumeColors[item.type] || '#A8A8A8');

        const volumeChartElement = document.querySelector("#volumeByTypeChart");
        if (volumeChartElement && volumeData.length > 0) {
            new ApexCharts(volumeChartElement, {
                series: [{
                    name: '{{ __("pagination.volume") }}',
                    data: volumeData
                }],
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: {
                        show: true
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 4,
                        horizontal: true,
                        barHeight: '60%',
                        distributed: true,
                        colors: {
                            backgroundBarColors: ['#f0f0f0'],
                            backgroundBarOpacity: 0.5
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return Math.abs(val).toLocaleString();
                    },
                    style: {
                        fontSize: '11px',
                        fontWeight: 'bold'
                    }
                },
                xaxis: {
                    categories: volumeLabels,
                    labels: {
                        style: {
                            fontSize: '11px'
                        }
                    },
                    title: {
                        text: '{{ __("pagination.quantity_moved") }}',
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            fontSize: '11px'
                        }
                    }
                },
                colors: volumeChartColors,
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return Math.abs(val).toLocaleString() + ' {{ __("pagination.units") }}';
                        }
                    }
                }
            }).render();
        }
        
        // ============================================
        // 3. Export Functionality
        // ============================================
        window.exportCurrentPage = function(options) {
            const { tableId, filename, format = 'csv' } = options;
            const table = document.getElementById(tableId);
            
            if (!table) {
                toastr?.error?.('{{ __("pagination.table_not_found") }}') || alert('{{ __("pagination.table_not_found") }}');
                return;
            }
            
            try {
                let csv = [];
                const rows = table.querySelectorAll('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const row = [];
                    const cols = rows[i].querySelectorAll('td, th');
                    
                    for (let j = 0; j < cols.length; j++) {
                        let text = cols[j].innerText
                            .replace(/(\r\n|\n|\r)/gm, ' ')
                            .replace(/\s+/g, ' ')
                            .trim();
                        
                        if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                            text = '"' + text.replace(/"/g, '""') + '"';
                        }
                        row.push(text);
                    }
                    csv.push(row.join(','));
                }
                
                const csvContent = '\uFEFF' + csv.join('\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                
                link.href = url;
                link.setAttribute('download', `${filename}.${format === 'excel' ? 'csv' : format}`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
                toastr?.success?.('{{ __("pagination.export_successful") }}') || null;
            } catch (error) {
                console.error('Export error:', error);
                toastr?.error?.('{{ __("pagination.export_failed") }}') || alert('{{ __("pagination.export_failed") }}');
            }
        };
    });
</script>
@endif

<script>
    // ============================================
    // Form Validation and Filters
    // ============================================
    document.addEventListener('DOMContentLoaded', function() {
        const filterForm = document.getElementById('filterForm');
        
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                const startDateInput = document.querySelector('[name="start_date"]');
                const endDateInput = document.querySelector('[name="end_date"]');
                
                if (startDateInput && endDateInput) {
                    const startDate = new Date(startDateInput.value);
                    const endDate = new Date(endDateInput.value);
                    
                    if (startDate > endDate) {
                        e.preventDefault();
                        const errorMessage = '{{ __("pagination.start_date_cannot_be_after_end_date") }}';
                        
                        if (typeof toastr !== 'undefined') {
                            toastr.error(errorMessage);
                        } else {
                            alert(errorMessage);
                        }
                        return false;
                    }
                }
            });
        }
        
        // Initialize any select2 or other plugins
        const selectElements = document.querySelectorAll('[data-control="select2"]');
        if (selectElements.length > 0 && typeof $.fn.select2 !== 'undefined') {
            selectElements.forEach(select => {
                $(select).select2({
                    placeholder: $(select).data('placeholder') || '{{ __("pagination.select_option") }}',
                    allowClear: true,
                    width: '100%'
                });
            });
        }
    });
</script>
@endpush
@endsection