{{-- resources/views/reports/inventory/dead-stock.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.dead_stock'))

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
                                {{ __('pagination.dead_stock') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.dead_stock') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($summary['total_items'] > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'deadStockTable', filename: 'dead_stock_{{ date('Y_m_d') }}', sheetName: 'Dead Stock'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'deadStockTable', filename: 'dead_stock_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.inventory.dead-stock') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
                                        {{-- Days Threshold --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.days_threshold') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="days_threshold" 
                                                    value="{{ $daysThreshold }}" min="30" max="1095"
                                                    title="{{ __('pagination.minimum_days_without_movement') }}">
                                                <span class="input-group-text">{{ __('pagination.days') }}</span>
                                            </div>
                                            <div class="form-text">{{ __('pagination.items_without_movement_for_x_days') }}</div>
                                        </div>
                                        
                                        {{-- Location --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                            <div class="input-group w-100">
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
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.department') }}</label>
                                            <div class="input-group w-100">
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
                                    
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
                                        {{-- Include Expired --}}
                                        <div class="flex-grow-1">
                                            <div class="form-check form-switch form-check-custom form-check-solid">
                                                <input class="form-check-input" type="checkbox" name="include_expired" 
                                                    value="1" id="include_expired" 
                                                    {{ $includeExpired ? 'checked' : '' }}>
                                                <label class="form-check-label" for="include_expired">
                                                    <span class="fw-semibold">{{ __('pagination.include_expired') }}</span>
                                                    <div class="form-text">{{ __('pagination.include_expired_items_in_report') }}</div>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('pagination.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('pagination.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.inventory.dead-stock') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('pagination.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('pagination.clear') }}</span>
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
                                    <h3 class="fw-bold m-0">{{ __('pagination.dead_stock_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'total_items', 'color' => 'primary', 'icon' => 'ki-box', 'label' => 'total_dead_items', 'value' => $summary['total_items']],
                                        ['key' => 'total_quantity', 'color' => 'danger', 'icon' => 'ki-barcode', 'label' => 'total_dead_quantity', 'value' => number_format($summary['total_quantity'])],
                                        ['key' => 'total_value', 'color' => 'warning', 'icon' => 'ki-dollar', 'label' => 'total_dead_value', 'value' => '$' . number_format($summary['total_value'], 2)],
                                        ['key' => 'avg_days_idle', 'color' => 'info', 'icon' => 'ki-clock', 'label' => 'avg_days_idle', 'value' => number_format($summary['avg_days_idle'], 0)],
                                        ['key' => 'expired_items', 'color' => 'dark', 'icon' => 'ki-cross', 'label' => 'expired_items', 'value' => $summary['expired_items']],
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
                @if($deadStockItems->count() > 0)
                <div class="row mb-6">
                    {{-- Idle Time Distribution --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.idle_time_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="idleTimeChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Dead Stock by Department --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.dead_stock_by_department') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="departmentChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Days Idle Analysis --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.days_idle_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="daysIdleChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Dead Stock Items Table --}}
                @if($deadStockItems->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.dead_stock_items') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $deadStockItems->count() }} {{ __('pagination.of') }} {{ $deadStockItems->total() }} {{ __('pagination.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="deadStockTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('pagination.sku') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.department') }}</th>
                                                <th>{{ __('pagination.location') }}</th>
                                                <th>{{ __('pagination.quantity') }}</th>
                                                <th>{{ __('pagination.last_movement_date') }}</th>
                                                <th>{{ __('pagination.days_idle') }}</th>
                                                <th>{{ __('pagination.expiry_date') }}</th>
                                                <th>{{ __('pagination.inventory_value') }}</th>
                                                <th>{{ __('pagination.total_movement') }}</th>
                                                <th>{{ __('pagination.action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($deadStockItems as $item)
                                            @php
                                                $daysIdle = \Carbon\Carbon::parse($item->last_movement_date)->diffInDays(now());
                                                // Determine idle severity
                                                if ($daysIdle >= 730) {
                                                    $idleColor = 'danger';
                                                    $idleText = __('pagination.critical');
                                                } elseif ($daysIdle >= 365) {
                                                    $idleColor = 'warning';
                                                    $idleText = __('pagination.high');
                                                } elseif ($daysIdle >= 180) {
                                                    $idleColor = 'info';
                                                    $idleText = __('pagination.medium');
                                                } else {
                                                    $idleColor = 'success';
                                                    $idleText = __('pagination.low');
                                                }
                                                
                                                // Check if expired
                                                $isExpired = $item->expiry_date && \Carbon\Carbon::parse($item->expiry_date)->lt(now());
                                                $expiryColor = $isExpired ? 'danger' : ($item->expiry_date ? 'success' : 'dark');
                                                $expiryText = $isExpired ? __('pagination.expired') : ($item->expiry_date ? \Carbon\Carbon::parse($item->expiry_date)->format('Y-m-d') : '-');
                                                
                                                // Calculate inventory value
                                                $inventoryValue = $item->quantity_on_hand * ($item->variant->cost_price ?? 0);
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
                                                    <span class="fw-bold {{ $isExpired ? 'text-danger' : 'text-dark' }}">
                                                        {{ number_format($item->quantity_on_hand) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="text-muted fs-7">{{ \Carbon\Carbon::parse($item->last_movement_date)->format('Y-m-d') }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $idleColor }}">
                                                        {{ $daysIdle }} {{ __('pagination.days') }}
                                                        <small class="ms-1">({{ $idleText }})</small>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $expiryColor }}">
                                                        {{ $expiryText }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $isExpired ? 'text-danger' : 'text-dark' }}">
                                                        ${{ number_format($inventoryValue, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-dark">
                                                        {{ number_format($item->total_movement) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-light-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            {{ __('pagination.actions') }}
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)">
                                                                    <i class="ki-duotone ki-discount fs-2 me-2"></i>
                                                                    {{ __('pagination.mark_for_clearance') }}
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)">
                                                                    <i class="ki-duotone ki-trash fs-2 me-2 text-danger"></i>
                                                                    {{ __('pagination.write_off') }}
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)">
                                                                    <i class="ki-duotone ki-gift fs-2 me-2 text-success"></i>
                                                                    {{ __('pagination.donate') }}
                                                                </a>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <a class="dropdown-item" href="javascript:void(0)">
                                                                    <i class="ki-duotone ki-eye fs-2 me-2"></i>
                                                                    {{ __('pagination.view_details') }}
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($deadStockItems->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $deadStockItems->firstItem() }} - {{ $deadStockItems->lastItem() }} {{ __('pagination.of') }} {{ $deadStockItems->total() }}
                                        </div>
                                        <div>
                                            {{ $deadStockItems->links() }}
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
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_dead_stock_found') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.all_items_have_recent_movement') }}</p>
                                        @if(request()->hasAny(['days_threshold', 'location_id', 'department_id', 'include_expired']))
                                        <a href="{{ route('reports.inventory.dead-stock') }}" class="btn btn-light-primary">
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
@if($deadStockItems->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Idle Time Distribution Chart
        const idleTimeData = [
            {{ $idleCategories['180_365'] ?? 0 }},
            {{ $idleCategories['365_730'] ?? 0 }},
            {{ $idleCategories['over_730'] ?? 0 }}
        ];
        
        const idleTimeChart = new ApexCharts(document.querySelector("#idleTimeChart"), {
            series: idleTimeData,
            chart: {
                type: 'donut',
                height: 300
            },
            labels: [
                '{{ __("pagination.180_365_days") }}',
                '{{ __("pagination.365_730_days") }}',
                '{{ __("pagination.over_730_days") }}'
            ],
            colors: ['#FFC700', '#F1416C', '#7E8299'],
            legend: {
                position: 'bottom'
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' units'
                    }
                }
            }
        });
        idleTimeChart.render();
        
        // Dead Stock by Department Chart
        @php
            $departmentData = $deadStockItems->groupBy('department.name')->map(function($items) {
                return $items->sum('quantity_on_hand');
            });
        @endphp
        
        const departmentChart = new ApexCharts(document.querySelector("#departmentChart"), {
            series: [{
                name: 'Quantity',
                data: Object.values(@json($departmentData))
            }],
            chart: {
                type: 'bar',
                height: 300
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
                categories: Object.keys(@json($departmentData))
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
                        return val + ' units'
                    }
                }
            }
        });
        departmentChart.render();
        
        // Days Idle Analysis Chart
        @php
            $daysIdleData = $deadStockItems->pluck('last_movement_date')->map(function($date) {
                return \Carbon\Carbon::parse($date)->diffInDays(now());
            })->toArray();
            // Sort and take top 15 for readability
            arsort($daysIdleData);
            $topDaysIdle = array_slice($daysIdleData, 0, 15);
            $productNames = $deadStockItems->take(15)->pluck('variant.name')->toArray();
        @endphp
        
        const daysIdleChart = new ApexCharts(document.querySelector("#daysIdleChart"), {
            series: [{
                name: 'Days Idle',
                data: @json($topDaysIdle)
            }],
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
                title: {
                    text: 'Days Since Last Movement'
                }
            },
            yaxis: {
                labels: {
                    show: false
                }
            },
            colors: ['#7239EA'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' days'
                    }
                }
            }
        });
        daysIdleChart.render();
    });
    
</script>
@endif

<script>
    // Form validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const daysThreshold = document.querySelector('[name="days_threshold"]').value;
        
        if (daysThreshold < 30 || daysThreshold > 1095) {
            e.preventDefault();
            alert('{{ __("pagination.days_threshold_must_be_between_30_and_1095") }}');
            return false;
        }
    });
</script>
@endpush

@endsection