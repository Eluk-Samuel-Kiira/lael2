{{-- resources/views/reports/inventory/stock-aging.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.stock_aging_report'))

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
                                {{ __('pagination.stock_aging_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.stock_aging') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($agingItems->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('pagination.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'stockAgingTable', filename: 'stock_aging_{{ date('Y_m_d') }}', sheetName: '{{ __('pagination.stock_aging') }}'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'stockAgingTable', filename: 'stock_aging_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.inventory.stock-aging') }}" id="filterForm">
                                    <div class="row g-4 mb-4">
                                        {{-- Department --}}
                                        <div class="col-md-6 col-lg-4">
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
                                        
                                        {{-- Location --}}
                                        <div class="col-md-6 col-lg-4">
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
                                        
                                        {{-- Aging Category --}}
                                        <div class="col-md-12 col-lg-4">
                                            <label class="form-label fw-semibold">{{ __('pagination.aging_category') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-time fs-2"></i>
                                                </span>
                                                <select class="form-select" name="category">
                                                    <option value="">{{ __('pagination.all_categories') }}</option>
                                                    <option value="expired" {{ $category == 'expired' ? 'selected' : '' }}>
                                                        {{ __('pagination.expired') }}
                                                    </option>
                                                    <option value="1_week" {{ $category == '1_week' ? 'selected' : '' }}>
                                                        {{ __('pagination.within_1_week') }}
                                                    </option>
                                                    <option value="1_month" {{ $category == '1_month' ? 'selected' : '' }}>
                                                        {{ __('pagination.within_1_month') }}
                                                    </option>
                                                    <option value="3_months" {{ $category == '3_months' ? 'selected' : '' }}>
                                                        {{ __('pagination.within_3_months') }}
                                                    </option>
                                                    <option value="6_months" {{ $category == '6_months' ? 'selected' : '' }}>
                                                        {{ __('pagination.within_6_months') }}
                                                    </option>
                                                    <option value="over_6_months" {{ $category == 'over_6_months' ? 'selected' : '' }}>
                                                        {{ __('pagination.over_6_months') }}
                                                    </option>
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
                                                <a href="{{ route('reports.inventory.stock-aging') }}" class="btn btn-light btn-active-light-primary">
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

                {{-- Aging Summary Statistics --}}
                @if($agingItems->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.stock_aging_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $agingStats = [
                                            [
                                                'key' => 'expired', 
                                                'color' => 'danger', 
                                                'icon' => 'ki-cross', 
                                                'label' => 'expired',
                                                'value' => number_format($agingCategories['expired']),
                                                'subtitle' => 'pagination.immediate_action_required'
                                            ],
                                            [
                                                'key' => '1_week', 
                                                'color' => 'warning', 
                                                'icon' => 'ki-clock', 
                                                'label' => 'within_1_week',
                                                'value' => number_format($agingCategories['1_week']),
                                                'subtitle' => 'pagination.urgent_attention'
                                            ],
                                            [
                                                'key' => '1_month', 
                                                'color' => 'warning', 
                                                'icon' => 'ki-time', 
                                                'label' => 'within_1_month',
                                                'value' => number_format($agingCategories['1_month']),
                                                'subtitle' => 'pagination.prioritize_usage'
                                            ],
                                            [
                                                'key' => '3_months', 
                                                'color' => 'info', 
                                                'icon' => 'ki-calendar-8', 
                                                'label' => 'within_3_months',
                                                'value' => number_format($agingCategories['3_months']),
                                                'subtitle' => 'pagination.monitor_closely'
                                            ],
                                            [
                                                'key' => '6_months', 
                                                'color' => 'success', 
                                                'icon' => 'ki-calendar-tick', 
                                                'label' => 'within_6_months',
                                                'value' => number_format($agingCategories['6_months']),
                                                'subtitle' => 'pagination.good_stock'
                                            ],
                                            [
                                                'key' => 'over_6_months', 
                                                'color' => 'success', 
                                                'icon' => 'ki-shield-tick', 
                                                'label' => 'over_6_months',
                                                'value' => number_format($agingCategories['over_6_months']),
                                                'subtitle' => 'pagination.long_term_stock'
                                            ]
                                        ];
                                    @endphp
                                    
                                    @foreach($agingStats as $stat)
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush border border-{{ $stat['color'] }} border-dashed h-100">
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
                                                <div class="text-gray-600 fw-semibold mb-2">
                                                    {{ __($stat['subtitle']) }}
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

                {{-- Aging Items Table --}}
                @if($agingItems->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.stock_aging_details') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $agingItems->count() }} {{ __('pagination.of') }} {{ $agingItems->total() }} {{ __('pagination.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="stockAgingTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('pagination.sku') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.department') }}</th>
                                                <th>{{ __('pagination.location') }}</th>
                                                <th>{{ __('pagination.batch_number') }}</th>
                                                <th>{{ __('pagination.expiry_date') }}</th>
                                                <th>{{ __('pagination.days_to_expiry') }}</th>
                                                <th>{{ __('pagination.quantity') }}</th>
                                                <th>{{ __('pagination.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($agingItems as $item)
                                            @php
                                                $daysToExpiry = \Carbon\Carbon::parse($item->expiry_date)->diffInDays(now(), false) * -1;
                                                
                                                if ($daysToExpiry < 0) {
                                                    $statusColor = 'danger';
                                                    $statusText = __('pagination.expired');
                                                    $progressColor = 'danger';
                                                } elseif ($daysToExpiry <= 7) {
                                                    $statusColor = 'warning';
                                                    $statusText = __('pagination.critical');
                                                    $progressColor = 'warning';
                                                } elseif ($daysToExpiry <= 30) {
                                                    $statusColor = 'warning';
                                                    $statusText = __('pagination.warning');
                                                    $progressColor = 'warning';
                                                } elseif ($daysToExpiry <= 90) {
                                                    $statusColor = 'info';
                                                    $statusText = __('pagination.monitor');
                                                    $progressColor = 'info';
                                                } elseif ($daysToExpiry <= 180) {
                                                    $statusColor = 'success';
                                                    $statusText = __('pagination.good');
                                                    $progressColor = 'success';
                                                } else {
                                                    $statusColor = 'primary';
                                                    $statusText = __('pagination.excellent');
                                                    $progressColor = 'primary';
                                                }
                                            @endphp
                                            <tr class="{{ $daysToExpiry < 0 ? 'table-danger' : '' }}">
                                                <td class="ps-4">
                                                    <div class="fw-semibold">{{ $item->variant->sku ?? '-' }}</div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->variant->name ?? '-' }}</div>
                                                    <div class="text-muted">{{ $item->variant->product->name ?? '' }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $item->departmentItem->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $item->itemLocation->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-dark">{{ $item->batch_number ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $daysToExpiry < 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ \Carbon\Carbon::parse($item->expiry_date)->format('Y-m-d') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $daysToExpiry < 0 ? 'text-danger' : ($daysToExpiry <= 30 ? 'text-warning' : 'text-success') }}">
                                                        {{ $daysToExpiry < 0 ? __('pagination.expired') : abs($daysToExpiry) . ' ' . __('pagination.days') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($item->quantity_on_hand) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $statusColor }}">
                                                        {{ $statusText }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($agingItems->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $agingItems->firstItem() }} - {{ $agingItems->lastItem() }} {{ __('pagination.of') }} {{ $agingItems->total() }}
                                        </div>
                                        <div>
                                            {{ $agingItems->links() }}
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
                                        <p class="text-muted fs-6">{{ __('pagination.no_stock_aging_data_found') }}</p>
                                        @if(request()->hasAny(['department_id', 'location_id', 'category']))
                                        <a href="{{ route('reports.inventory.stock-aging') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('pagination.clear_filters') }}
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
    // Simple export function
    function exportCurrentPage(config) {
        const { tableId, filename, sheetName = 'Sheet1', format = 'excel' } = config;
        const table = document.getElementById(tableId);
        
        if (!table) {
            alert('Table not found');
            return;
        }
        
        // Simple export logic - in real app, use a proper library
        console.log(`Exporting ${tableId} to ${format} as ${filename}`);
        alert('Export functionality would be implemented here. Use a library like SheetJS or TableExport.js');
    }
</script>
@endpush

@endsection