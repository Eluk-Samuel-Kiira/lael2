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
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
                        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
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
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($transactions->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('pagination.export') }}
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
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.inventory.transactions') }}" id="filterForm">
                                    <div class="row g-4 mb-4">
                                        {{-- Date Range --}}
                                        <div class="col-md-12 col-lg-4">
                                            <label class="form-label required fw-semibold">{{ __('pagination.date_range') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="start_date" 
                                                    value="{{ $startDate }}" required
                                                    title="{{ __('pagination.start_date') }}">
                                                <span class="input-group-text bg-light">{{ __('pagination.to') }}</span>
                                                <input type="date" class="form-control" name="end_date" 
                                                    value="{{ $endDate }}" required
                                                    title="{{ __('pagination.end_date') }}">
                                            </div>
                                        </div>
                                        
                                        {{-- Transaction Type --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.transaction_type') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-switch fs-2"></i>
                                                </span>
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
                                        </div>
                                        
                                        {{-- Location --}}
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-location fs-2"></i>
                                                </span>
                                                <select class="form-select" name="location_id">
                                                    <option value="">{{ __('pagination.all_locations') }}</option>
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
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.department') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-building fs-2"></i>
                                                </span>
                                                <select class="form-select" name="department_id">
                                                    <option value="">{{ __('pagination.all_departments') }}</option>
                                                    @foreach($departments as $department)
                                                        <option value="{{ $department->id }}" 
                                                                {{ $departmentId == $department->id ? 'selected' : '' }}>
                                                            {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Action Buttons --}}
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('pagination.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.inventory.transactions') }}" class="btn btn-light btn-active-light-primary">
                                                    <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                                    {{ __('pagination.clear_filters') }}
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
                    
                    {{-- Daily Transaction Trend --}}
                    <div class="col-lg-6">
                        <div class="card">
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
                                <div id="dailyTrendChart" style="height: 300px;"></div>
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
                                                <th>{{ __('pagination.type') }}</th>
                                                <th>{{ __('pagination.transaction_count') }}</th>
                                                <th>{{ __('pagination.total_quantity') }}</th>
                                                <th>{{ __('pagination.avg_quantity') }}</th>
                                                <th>{{ __('pagination.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($typeSummary as $summaryItem)
                                            @php
                                                $percentage = $totalTransactions > 0 ? ($summaryItem->count / $totalTransactions) * 100 : 0;
                                                $typeColors = [
                                                    'purchase' => 'success',
                                                    'sale' => 'danger',
                                                    'return' => 'info',
                                                    'adjustment' => 'warning',
                                                    'transfer_in' => 'primary',
                                                    'transfer_out' => 'secondary'
                                                ];
                                                $typeColor = $typeColors[$summaryItem->type] ?? 'dark';
                                            @endphp
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light-{{ $typeColor }}">
                                                        {{ __('pagination.' . $summaryItem->type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ $summaryItem->count }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $summaryItem->total_quantity >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $summaryItem->total_quantity >= 0 ? '+' : '' }}{{ number_format($summaryItem->total_quantity) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    {{ number_format($summaryItem->count > 0 ? $summaryItem->total_quantity / $summaryItem->count : 0, 1) }}
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 8px;">
                                                            <div class="progress-bar bg-{{ $typeColor }}" 
                                                                style="width: {{ $percentage }}%"></div>
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
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.transaction_details') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $transactions->count() }} {{ __('pagination.of') }} {{ $transactions->total() }} {{ __('pagination.transactions') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="transactionsTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('pagination.date_time') }}</th>
                                                <th>{{ __('pagination.sku') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.type') }}</th>
                                                <th>{{ __('pagination.quantity') }}</th>
                                                <th>{{ __('pagination.department') }}</th>
                                                <th>{{ __('pagination.location') }}</th>
                                                <th>{{ __('pagination.reference') }}</th>
                                                <th>{{ __('pagination.notes') }}</th>
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
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold">{{ $transaction->created_at->format('Y-m-d') }}</div>
                                                    <small class="text-muted">{{ $transaction->created_at->format('H:i:s') }}</small>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $transaction->inventoryItem->variant->sku ?? '-' }}</div>
                                                <small class="text-muted">{{ $transaction->inventoryItem->variant->barcode ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $transaction->inventoryItem->variant->name ?? '-' }}</div>
                                                    <small class="text-muted">{{ $transaction->inventoryItem->variant->product->name ?? '' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $typeColor }}">
                                                        {{ __('pagination.' . $transaction->type) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-{{ $quantityColor }}">
                                                        {{ $quantitySign }}{{ number_format($transaction->quantity) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $transaction->inventoryItem->departmentItem->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $transaction->inventoryItem->itemLocation->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    @if($transaction->reference_id && $transaction->reference_type)
                                                    <span class="badge badge-light-dark">
                                                        {{ strtoupper($transaction->reference_type) }} #{{ $transaction->reference_id }}
                                                    </span>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($transaction->notes)
                                                    <span class="text-muted fs-7">{{ Str::limit($transaction->notes, 30) }}</span>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($transactions->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $transactions->firstItem() }} - {{ $transactions->lastItem() }} {{ __('pagination.of') }} {{ $transactions->total() }}
                                        </div>
                                        <div>
                                            {{ $transactions->links() }}
                                        </div>
                                    </div>
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
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.no_transactions_found') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'type', 'location_id', 'department_id']))
                                        <a href="{{ route('reports.inventory.transactions') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('pagination.clear_filters_view_all') }}
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
<script>
    // Define translations object
    const paginationTranslations = {
        purchase: '{{ __("pagination.purchase") }}',
        sale: '{{ __("pagination.sale") }}',
        return: '{{ __("pagination.return") }}',
        adjustment: '{{ __("pagination.adjustment") }}',
        transfer_in: '{{ __("pagination.transfer_in") }}',
        transfer_out: '{{ __("pagination.transfer_out") }}',
        fast_moving: '{{ __("pagination.fast_moving") }}',
        slow_moving: '{{ __("pagination.slow_moving") }}',
        non_moving: '{{ __("pagination.non_moving") }}',
    };
</script>
@endpush

@push('scripts')
@if($transactions->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Transaction Type Distribution Chart
    const typeData = @json($typeSummary);
    const typeLabels = typeData.map(item => {
        // Map type to English label for JS, or use translated labels from PHP
        const typeMap = {
            'purchase': 'Purchase',
            'sale': 'Sale', 
            'return': 'Return',
            'adjustment': 'Adjustment',
            'transfer_in': 'Transfer In',
            'transfer_out': 'Transfer Out'
        };
        return typeMap[item.type] || item.type;
    });

    const typeChart = new ApexCharts(document.querySelector("#typeDistributionChart"), {
        series: typeData.map(item => item.count),
        chart: {
            type: 'donut',
            height: 300
        },
        labels: typeLabels,
        colors: ['#50CD89', '#F1416C', '#3E97FF', '#FFC700', '#7239EA', '#7E8299'],
        legend: {
            position: 'bottom'
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' transactions'
                }
            }
        }
    });
    typeChart.render();
        
        // Daily Transaction Trend Chart (simplified - you would need actual daily data from controller)
        const daysInRange = {{ \Carbon\Carbon::parse($startDate)->diffInDays($endDate) + 1 }};
        const avgDaily = {{ $totalTransactions }} / daysInRange;
        
        // Generate sample daily data (in real implementation, get this from controller)
        const dailyData = [];
        const dailyLabels = [];
        for(let i = 0; i < Math.min(daysInRange, 30); i++) {
            dailyData.push(Math.floor(avgDaily * (0.7 + Math.random() * 0.6)));
            const date = new Date('{{ $endDate }}');
            date.setDate(date.getDate() - i);
            dailyLabels.unshift(date.toISOString().split('T')[0]);
        }
        
        const dailyTrendChart = new ApexCharts(document.querySelector("#dailyTrendChart"), {
            series: [{
                name: 'Transactions',
                data: dailyData.reverse()
            }],
            chart: {
                type: 'line',
                height: 300,
                toolbar: {
                    show: true
                }
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            xaxis: {
                categories: dailyLabels,
                labels: {
                    rotate: -45
                }
            },
            yaxis: {
                title: {
                    text: 'Number of Transactions'
                }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' transactions'
                    }
                }
            }
        });
        dailyTrendChart.render();
    });
    
</script>
@endif

<script>
    // Form validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const startDate = new Date(document.querySelector('[name="start_date"]').value);
        const endDate = new Date(document.querySelector('[name="end_date"]').value);
        
        if (startDate > endDate) {
            e.preventDefault();
            alert('{{ __("pagination.start_date_cannot_be_after_end_date") }}');
            return false;
        }
    });
</script>
@endpush

@endsection