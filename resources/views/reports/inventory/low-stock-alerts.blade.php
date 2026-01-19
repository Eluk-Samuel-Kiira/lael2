{{-- resources/views/reports/inventory/low-stock-alerts.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.low_stock_alerts'))

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
                                {{ __('pagination.low_stock_alerts') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.low_stock_alerts') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($summary['total_items'] > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'lowStockTable', filename: 'low_stock_alerts_{{ date('Y_m_d') }}', sheetName: 'Low Stock Alerts'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'lowStockTable', filename: 'low_stock_alerts_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.inventory.low-stock-alerts') }}" id="filterForm">
                                    <div class="row g-4 mb-4">
                                        {{-- Location --}}
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
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
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">{{ __('pagination.department') }}</label>
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
                                    </div>
                                    
                                    <div class="row g-4 mb-4">
                                        {{-- Severity Level --}}
                                        <div class="col-md-12">
                                            <label class="form-label fw-semibold">{{ __('pagination.severity_level') }}</label>
                                            <div class="d-flex flex-wrap gap-3">
                                                <div class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="radio" name="severity" 
                                                           value="all" id="severity_all" 
                                                           {{ $severity === 'all' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="severity_all">
                                                        <span class="badge badge-light-dark">{{ __('pagination.all_severities') }}</span>
                                                    </label>
                                                </div>
                                                <div class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="radio" name="severity" 
                                                           value="critical" id="severity_critical" 
                                                           {{ $severity === 'critical' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="severity_critical">
                                                        <span class="badge badge-light-danger">{{ __('pagination.critical') }}</span>
                                                    </label>
                                                </div>
                                                <div class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="radio" name="severity" 
                                                           value="warning" id="severity_warning" 
                                                           {{ $severity === 'warning' ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="severity_warning">
                                                        <span class="badge badge-light-warning">{{ __('pagination.warning') }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Action Buttons --}}
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('accounting.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.inventory.low-stock-alerts') }}" class="btn btn-light btn-active-light-primary">
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
                                    <h3 class="fw-bold m-0">{{ __('pagination.alert_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'critical', 'color' => 'danger', 'icon' => 'ki-shield-cross', 'label' => 'critical_alerts', 'value' => $summary['critical']],
                                        ['key' => 'warning', 'color' => 'warning', 'icon' => 'ki-shield-tick', 'label' => 'warning_alerts', 'value' => $summary['warning']],
                                        ['key' => 'total_items', 'color' => 'primary', 'icon' => 'ki-warning-2', 'label' => 'total_alerts', 'value' => $summary['total_items']],
                                        ['key' => 'total_value_at_risk', 'color' => 'info', 'icon' => 'ki-dollar', 'label' => 'value_at_risk', 'value' => '$' . number_format($summary['total_value_at_risk'], 2)],
                                    ] as $stat)
                                    <div class="col-md-6 col-lg-3">
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
                @if($lowStockItems->count() > 0)
                <div class="row mb-6">
                    {{-- Severity Distribution --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.severity_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="severityChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Department Distribution --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.department_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="departmentChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Low Stock Items Table --}}
                @if($lowStockItems->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.low_stock_items') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $lowStockItems->count() }} {{ __('pagination.of') }} {{ $lowStockItems->total() }} {{ __('pagination.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="lowStockTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('pagination.sku') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.department') }}</th>
                                                <th>{{ __('pagination.location') }}</th>
                                                <th>{{ __('pagination.current_stock') }}</th>
                                                <th>{{ __('pagination.reorder_point') }}</th>
                                                <th>{{ __('pagination.preferred_stock') }}</th>
                                                <th>{{ __('pagination.stock_deficit') }}</th>
                                                <th>{{ __('pagination.severity') }}</th>
                                                <th>{{ __('pagination.urgency') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($lowStockItems as $item)
                                            @php
                                                // Calculate severity
                                                $reorderPoint = $item->reorder_point ?: 0;
                                                $preferredStock = $item->preferred_stock_level ?: 0;
                                                $currentStock = $item->quantity_on_hand;
                                                
                                                // Calculate percentages
                                                $reorderPercentage = $reorderPoint > 0 ? ($currentStock / $reorderPoint) * 100 : 0;
                                                $preferredPercentage = $preferredStock > 0 ? ($currentStock / $preferredStock) * 100 : 0;
                                                
                                                // Determine severity
                                                if ($reorderPoint > 0 && $currentStock <= $reorderPoint * 0.5) {
                                                    $severityColor = 'danger';
                                                    $severityText = __('pagination.critical');
                                                    $urgencyText = __('pagination.immediate');
                                                } elseif ($reorderPoint > 0 && $currentStock <= $reorderPoint) {
                                                    $severityColor = 'warning';
                                                    $severityText = __('pagination.warning');
                                                    $urgencyText = __('pagination.soon');
                                                } else {
                                                    $severityColor = 'info';
                                                    $severityText = __('pagination.normal');
                                                    $urgencyText = __('pagination.later');
                                                }
                                                
                                                // Calculate stock deficit
                                                $deficit = max(0, $reorderPoint - $currentStock);
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold">{{ $item->variant->sku ?? '-' }}</div>
                                                    <small class="text-muted">{{ $item->variant->barcode ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($item->variant->image_url ?? false)
                                                        <div class="symbol symbol-50px me-3">
                                                            <img src="{{ $item->variant->image_url }}" class="img-fluid" alt="{{ $item->variant->name }}">
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <div class="fw-bold">{{ $item->variant->name ?? '-' }}</div>
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
                                                    <span class="fw-bold text-{{ $severityColor }}">
                                                        {{ number_format($currentStock) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    {{ $reorderPoint }}
                                                </td>
                                                <td>
                                                    {{ $preferredStock }}
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-danger">
                                                        -{{ $deficit }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $severityColor }}">
                                                        {{ $severityText }}
                                                    </span>
                                                    <div class="progress mt-1" style="height: 5px; width: 80px;">
                                                        <div class="progress-bar bg-{{ $severityColor }}" 
                                                             style="width: {{ min(100, $reorderPercentage) }}%"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-dark">{{ $urgencyText }}</span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($lowStockItems->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $lowStockItems->firstItem() }} - {{ $lowStockItems->lastItem() }} {{ __('pagination.of') }} {{ $lowStockItems->total() }}
                                        </div>
                                        <div>
                                            {{ $lowStockItems->links() }}
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
                                        <i class="ki-duotone ki-check fs-4tx text-success mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_low_stock_items') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.all_stock_levels_are_adequate') }}</p>
                                        @if(request()->hasAny(['location_id', 'department_id', 'severity']))
                                        <a href="{{ route('reports.inventory.low-stock-alerts') }}" class="btn btn-light-primary">
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
@if($lowStockItems->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Severity Distribution Chart
        const criticalCount = @json($summary['critical']);
        const warningCount = @json($summary['warning']);
        const normalCount = @json(max(0, $summary['total_items'] - $summary['critical'] - $summary['warning']));
        
        const severityChart = new ApexCharts(document.querySelector("#severityChart"), {
            series: [criticalCount, warningCount, normalCount],
            chart: {
                type: 'donut',
                height: 300
            },
            labels: [
                '{{ __("pagination.critical") }}',
                '{{ __("pagination.warning") }}',
                '{{ __("pagination.normal") }}'
            ],
            colors: ['#F1416C', '#FFC700', '#50CD89'],
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
        severityChart.render();
        
        // Department Distribution Chart
        const departmentData = @json($lowStockItems->groupBy('department.name')->map->count());
        
        const departmentChart = new ApexCharts(document.querySelector("#departmentChart"), {
            series: Object.values(departmentData),
            chart: {
                type: 'bar',
                height: 300
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '60%'
                }
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: Object.keys(departmentData)
            },
            yaxis: {
                title: {
                    text: 'Number of Items'
                }
            },
            colors: ['#3E97FF'],
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
@endpush

@endsection