{{-- resources/views/reports/inventory/summary.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_summary'))

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
                                {{ __('pagination.inventory_summary') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($summary['total_items'] > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'inventorySummaryTable', filename: 'inventory_summary_{{ date('Y_m_d') }}', sheetName: 'Inventory Summary'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'inventorySummaryTable', filename: 'inventory_summary_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.inventory.summary') }}" id="filterForm">
                                    {{-- First Line --}}
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
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
                                                        title="{{ __('accounting.start_date') }}">
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('accounting.to') }}</span>
                                                <span class="d-flex d-sm-none text-gray-500 text-center">{{ __('accounting.to') }}</span>
                                                <div class="input-group w-100">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="end_date" 
                                                        value="{{ $endDate }}" required
                                                        title="{{ __('accounting.end_date') }}">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Location --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                            <div class="input-group w-100">
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
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.department') }}</label>
                                            <div class="input-group w-100">
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
                                    </div>
                                    
                                    {{-- Second Line --}}
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
                                        {{-- Product Variant --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-abstract-42 fs-2"></i>
                                                </span>
                                                <select class="form-select" name="variant_id">
                                                    <option value="">{{ __('pagination.all_variants') }}</option>
                                                    @foreach($variants as $variant)
                                                        <option value="{{ $variant->id }}" 
                                                                {{ $variantId == $variant->id ? 'selected' : '' }}>
                                                            {{ $variant->name }} ({{ $variant->sku }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Active Status --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.status') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-status fs-2"></i>
                                                </span>
                                                <select class="form-select" name="is_active">
                                                    <option value="all">{{ __('pagination.all_status') }}</option>
                                                    <option value="1" {{ $isActive === '1' ? 'selected' : '' }}>{{ __('accounting.active') }}</option>
                                                    <option value="0" {{ $isActive === '0' ? 'selected' : '' }}>{{ __('accounting.inactive') }}</option>
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
                                                <a href="{{ route('reports.inventory.summary') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- Summary Statistics --}}
                @if($summary['total_items'] > 0)
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
                                    @foreach([
                                        ['key' => 'total_items', 'color' => 'primary', 'icon' => 'ki-box', 'label' => 'total_items', 'value' => $summary['total_items']],
                                        ['key' => 'total_quantity', 'color' => 'success', 'icon' => 'ki-barcode', 'label' => 'total_quantity', 'value' => number_format($summary['total_quantity'])],
                                        ['key' => 'total_value', 'color' => 'info', 'icon' => 'ki-dollar', 'label' => 'total_value', 'value' => '$' . number_format($summary['total_value'], 2)],
                                        ['key' => 'average_stock_level', 'color' => 'warning', 'icon' => 'ki-calculator', 'label' => 'average_stock_level', 'value' => number_format($summary['average_stock_level'], 1)],
                                        ['key' => 'items_below_reorder', 'color' => 'danger', 'icon' => 'ki-warning-2', 'label' => 'items_below_reorder', 'value' => $summary['items_below_reorder']],
                                        ['key' => 'out_of_stock', 'color' => 'secondary', 'icon' => 'ki-cross', 'label' => 'out_of_stock', 'value' => $summary['out_of_stock']]
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
                @if($inventoryItems->count() > 0)
                <div class="row mb-6">
                    {{-- Stock Level Distribution --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.stock_level_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="stockDistributionChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Department Distribution --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.department_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="departmentDistributionChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Inventory Items Table --}}
                @if($inventoryItems->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.inventory_items') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $inventoryItems->count() }} {{ __('accounting.of') }} {{ $inventoryItems->total() }} {{ __('accounting.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="inventorySummaryTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('pagination.sku') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('accounting.department') }}</th>
                                                <th>{{ __('auth.location') }}</th>
                                                <th>{{ __('pagination.quantity') }}</th>
                                                <th>{{ __('pagination.reorder_point') }}</th>
                                                <th>{{ __('auth.stock_status') }}</th>
                                                <th>{{ __('auth.last_updated') }}</th>
                                                {{--<th class="text-end">{{ __('accounting.actions') }}</th>--}}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($inventoryItems as $item)
                                            @php
                                                $stockRatio = $item->reorder_point > 0 ? ($item->quantity_on_hand / $item->reorder_point) : 0;
                                                $statusColor = 'success';
                                                $statusText = __('pagination.in_stock');
                                                
                                                if ($item->quantity_on_hand == 0) {
                                                    $statusColor = 'danger';
                                                    $statusText = __('pagination.out_of_stock');
                                                } elseif ($item->quantity_on_hand <= $item->reorder_point) {
                                                    $statusColor = 'warning';
                                                    $statusText = __('pagination.low_stock');
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold">{{ $item->variant->sku }}</div>
                                                    <small class="text-muted">{{ $item->variant->barcode }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($item->variant->image_url)
                                                        <div class="symbol symbol-50px me-3">
                                                            <img src="{{ $item->variant->image_url }}" class="img-fluid" alt="{{ $item->variant->name }}">
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <div class="fw-bold">{{ $item->variant->name }}</div>
                                                            <div class="text-muted">{{ $item->variant->product->name ?? '' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $item->departmentItem->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $item->itemLocation->name ?? '-' }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $item->quantity_on_hand == 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ number_format($item->quantity_on_hand) }}
                                                    </span>
                                                    @if($item->quantity_allocated > 0)
                                                    <div class="text-muted fs-8">
                                                        {{ __('pagination.allocated') }}: {{ $item->quantity_allocated }}
                                                    </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $item->reorder_point ?? '-' }}
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $statusColor }}">{{ $statusText }}</span>
                                                    @if($stockRatio > 0 && $stockRatio < 2)
                                                    <div class="progress mt-1" style="height: 5px; width: 80px;">
                                                        <div class="progress-bar bg-{{ $statusColor }}" 
                                                             style="width: {{ min(100, $stockRatio * 100) }}%"></div>
                                                    </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $item->updated_at->format('Y-m-d H:i') }}
                                                </td>
                                                {{--
                                                <td class="text-end">
                                                    <button class="btn btn-sm btn-icon btn-light-primary" 
                                                            data-bs-toggle="tooltip" 
                                                            title="{{ __('accounting.view_details') }}">
                                                        <i class="ki-duotone ki-eye fs-2"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-light-info" 
                                                            data-bs-toggle="tooltip" 
                                                            title="{{ __('accounting.adjust_stock') }}">
                                                        <i class="ki-duotone ki-switch fs-2"></i>
                                                    </button>
                                                </td>
                                                --}}
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($inventoryItems->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('accounting.showing') }} {{ $inventoryItems->firstItem() }} - {{ $inventoryItems->lastItem() }} {{ __('accounting.of') }} {{ $inventoryItems->total() }}
                                        </div>
                                        <div>
                                            {{ $inventoryItems->links() }}
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
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.no_inventory_items_found') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'location_id', 'department_id', 'variant_id']))
                                        <a href="{{ route('reports.inventory.summary') }}" class="btn btn-light-primary">
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
@if($inventoryItems->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Stock Level Distribution Chart
        const stockData = @json($inventoryItems->pluck('quantity_on_hand'));
        const labels = @json($inventoryItems->pluck('variant.name'));
        
        const stockChart = new ApexCharts(document.querySelector("#stockDistributionChart"), {
            series: [{
                name: 'Stock Level',
                data: stockData
            }],
            chart: {
                type: 'bar',
                height: 350,
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
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: labels,
                labels: {
                    rotate: -45,
                    trim: true
                }
            },
            yaxis: {
                title: {
                    text: 'Quantity'
                }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toLocaleString()
                    }
                }
            }
        });
        stockChart.render();
        
        // Department Distribution Chart
        const departmentData = @json($inventoryItems->groupBy('department.name')->map->count());
        
        const departmentChart = new ApexCharts(document.querySelector("#departmentDistributionChart"), {
            series: Object.values(departmentData),
            chart: {
                type: 'donut',
                height: 350
            },
            labels: Object.keys(departmentData),
            colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'],
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' items'
                    }
                }
            }
        });
        departmentChart.render();
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
            alert('{{ __("accounting.start_date_cannot_be_after_end_date") }}');
            return false;
        }
    });
</script>
@endpush

@endsection