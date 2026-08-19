{{-- resources/views/reports/inventory/adjustments.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_adjustments_report'))

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
                                {{ __('pagination.inventory_adjustments_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_adjustments') }}</li>
                            </ul>
                        </div>
                        @if($adjustments->count() > 0)
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('adjustmentsTable', 'inventory_adjustments_{{ date('Y_m_d') }}')">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('pagination.export') }}
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                                <i class="ki-duotone ki-printer fs-2"></i> {{ __('pagination.print') }}
                            </button>
                        </div>
                        @endif
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
                        <form method="GET" action="{{ route('reports.inventory.adjustments') }}" id="filterForm">
                            {{-- First Row --}}
                            <div class="row g-3 mb-3">
                                {{-- Date Range --}}
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">{{ __('pagination.date_range') }}</label>
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
                                
                                {{-- Product Variant --}}
                                <div class="col-md-3">
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
                                <div class="col-md-5">
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

                            {{-- Second Row - Action Buttons --}}
                            <div class="row">
                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="applyFilters">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('pagination.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.inventory.adjustments') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('pagination.clear_filters') }}
                                    </a>
                                    @if($adjustments->count() > 0)
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('pagination.showing') }} <strong>{{ $adjustments->count() }}</strong> {{ __('pagination.adjustments') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Summary Statistics --}}
                @if($adjustments->count() > 0)
                <div class="row g-6 mb-6">
                    @php
                        $summaryStats = [
                            ['key' => 'total_adjustments', 'color' => 'primary', 'icon' => 'ki-switch', 'label' => 'Total Adjustments', 'value' => number_format($summary['total_adjustments'] ?? 0)],
                            ['key' => 'total_quantity_changed', 'color' => 'info', 'icon' => 'ki-arrow-change', 'label' => 'Total Quantity Changed', 'value' => number_format($summary['total_quantity_changed'] ?? 0)],
                            ['key' => 'net_change', 'color' => ($summary['net_change'] ?? 0) >= 0 ? 'success' : 'danger', 'icon' => 'ki-trend-up', 'label' => 'Net Change', 'value' => (($summary['net_change'] ?? 0) >= 0 ? '+' : '') . number_format($summary['net_change'] ?? 0)],
                            ['key' => 'increase_count', 'color' => 'success', 'icon' => 'ki-arrow-up', 'label' => 'Increases', 'value' => number_format($summary['increase_count'] ?? 0)],
                            ['key' => 'decrease_count', 'color' => 'danger', 'icon' => 'ki-arrow-down', 'label' => 'Decreases', 'value' => number_format($summary['decrease_count'] ?? 0)],
                        ];
                    @endphp
                    
                    @foreach($summaryStats as $stat)
                    <div class="col-md-6 col-lg-2">
                        <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-3">
                                    <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}"></i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-1 fw-bold text-gray-800">{{ $stat['value'] }}</span>
                                </div>
                                <div class="text-gray-600 fw-semibold fs-7">
                                    {{ __($stat['label']) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Charts Section --}}
                <div class="row g-6 mb-6">
                    {{-- Adjustment Distribution by Type --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.adjustment_type_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="adjustmentTypeChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Daily Adjustment Trend --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.daily_adjustment_trend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="dailyAdjustmentChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Adjustments Table --}}
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.adjustment_history') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">
                                {{ $adjustments->count() }} {{ __('pagination.adjustments') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="adjustmentsTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4" style="width: 12%;">{{ __('pagination.date_time') }}</th>
                                        <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                        <th style="width: 12%;">{{ __('pagination.sku') }}</th>
                                        <th style="width: 12%;">{{ __('pagination.department') }}</th>
                                        <th style="width: 10%;">{{ __('pagination.location') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.before') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.after') }}</th>
                                        <th style="width: 10%;" class="text-center">{{ __('pagination.change') }}</th>
                                        <th style="width: 10%;">{{ __('pagination.reason') }}</th>
                                        <th style="width: 10%;">{{ __('pagination.adjusted_by') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($adjustments as $adjustment)
                                    @php
                                        $change = $adjustment->quantity_after - $adjustment->quantity_before;
                                        $changeColor = $change > 0 ? 'success' : ($change < 0 ? 'danger' : 'info');
                                        $changeIcon = $change > 0 ? 'ki-arrow-up' : ($change < 0 ? 'ki-arrow-down' : 'ki-minus');
                                        $changeSign = $change > 0 ? '+' : '';
                                        
                                        // Get inventory item
                                        $inventoryItem = $adjustment->inventoryItems;
                                        $variant = $inventoryItem ? $inventoryItem->variant : null;
                                        $department = $inventoryItem ? $inventoryItem->departmentItem : null;
                                        $location = $inventoryItem ? $inventoryItem->itemLocation : null;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold">{{ $adjustment->created_at ? $adjustment->created_at->format('Y-m-d') : '-' }}</div>
                                            <small class="text-muted">{{ $adjustment->created_at ? $adjustment->created_at->format('H:i:s') : '-' }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($variant && $variant->image_url)
                                                <div class="symbol symbol-40px me-2">
                                                    <img src="{{ productVariantImage($variant->image_url) }}" class="rounded" alt="{{ $variant->name }}">
                                                </div>
                                                @endif
                                                <div>
                                                    <div class="fw-bold">{{ $variant->name ?? '-' }}</div>
                                                    <div class="text-muted fs-7">{{ $variant->product->name ?? '' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($variant)
                                            <span class="badge badge-light-secondary">{{ $variant->sku ?? '-' }}</span>
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary">
                                                <i class="ki-duotone ki-building fs-3 me-1"></i>
                                                {{ $department->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-info">
                                                <i class="ki-duotone ki-location fs-3 me-1"></i>
                                                {{ $location->name ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-gray-600">{{ number_format($adjustment->quantity_before) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-gray-600">{{ number_format($adjustment->quantity_after) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-{{ $changeColor }}">
                                                <i class="ki-duotone {{ $changeIcon }} fs-3 me-1"></i>
                                                {{ $changeSign }}{{ number_format(abs($change)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-dark">
                                                {{ str_replace('_', ' ', $adjustment->reason) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-30px symbol-circle me-2">
                                                    <div class="symbol-label bg-light-primary">
                                                        <span class="fs-7 fw-bold text-primary">
                                                            {{ $adjustment->createdBy ? strtoupper(substr($adjustment->createdBy->name, 0, 1)) : 'U' }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <span class="text-muted">{{ $adjustment->createdBy->name ?? '-' }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @php
                                    $totalChange = $adjustments->sum(function($item) {
                                        return $item->quantity_after - $item->quantity_before;
                                    });
                                    $totalBefore = $adjustments->sum('quantity_before');
                                    $totalAfter = $adjustments->sum('quantity_after');
                                @endphp
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">{{ __('pagination.current_page') }}: </td>
                                        <td class="text-center fw-bold">{{ number_format($totalBefore) }}</td>
                                        <td class="text-center fw-bold">{{ number_format($totalAfter) }}</td>
                                        <td class="text-center fw-bold">
                                            <span class="text-{{ $totalChange >= 0 ? 'success' : 'danger' }}">
                                                {{ $totalChange >= 0 ? '+' : '' }}{{ number_format($totalChange) }}
                                            </span>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                    @if($adjustments->total() > $adjustments->count())
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold text-muted">{{ __('pagination.grand_total') }}: </td>
                                        <td class="text-center fw-bold">{{ number_format($summary['total_before'] ?? $adjustments->sum('quantity_before')) }}</td>
                                        <td class="text-center fw-bold">{{ number_format($summary['total_after'] ?? $adjustments->sum('quantity_after')) }}</td>
                                        <td class="text-center fw-bold">
                                            <span class="text-{{ ($summary['net_change'] ?? 0) >= 0 ? 'success' : 'danger' }}">
                                                {{ ($summary['net_change'] ?? 0) >= 0 ? '+' : '' }}{{ number_format($summary['net_change'] ?? 0) }}
                                            </span>
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pagination --}}
                    @if($adjustments->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $adjustments,
                            'pageName' => 'page',
                            'perPageName' => 'per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif
                </div>
                @else
                    {{-- No Data Message --}}
                    <div class="card">
                        <div class="card-body text-center py-12">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_adjustments_found') }}</h4>
                            <p class="text-muted fs-6">{{ __('pagination.no_adjustments_in_date_range') }}</p>
                            @if(request()->hasAny(['start_date', 'end_date', 'department_id', 'location_id', 'variant_id']))
                            <a href="{{ route('reports.inventory.adjustments') }}" class="btn btn-light-primary mt-3">
                                <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                {{ __('pagination.clear_filters') }}
                            </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Metadata Footer --}}
                @if($adjustments->count() > 0)
                <div class="mt-6 text-muted text-center fs-7">
                    <hr>
                    <p>
                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                        {{ __('pagination.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }}
                        @if(isset($locationId) && $locationId)
                            | {{ __('auth.location') }}: {{ $locations->where('id', $locationId)->first()->name ?? 'N/A' }}
                        @endif
                        @if(isset($departmentId) && $departmentId)
                            | {{ __('auth.department') }}: {{ $departments->where('id', $departmentId)->first()->name ?? 'N/A' }}
                        @endif
                        @if(isset($variantId) && $variantId)
                            | {{ __('pagination.variant') }}: {{ $variants->where('id', $variantId)->first()->name ?? 'N/A' }}
                        @endif
                        | {{ $summary['total_adjustments'] ?? 0 }} {{ __('pagination.adjustments') }}
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
@if($adjustments->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // 1. Adjustment Type Distribution Chart
    // ============================================
    @php
        // Calculate type distribution from adjustments
        $typeData = [
            'increase' => $adjustments->filter(function($item) {
                return $item->quantity_after > $item->quantity_before;
            })->count(),
            'decrease' => $adjustments->filter(function($item) {
                return $item->quantity_after < $item->quantity_before;
            })->count(),
            'no_change' => $adjustments->filter(function($item) {
                return $item->quantity_after == $item->quantity_before;
            })->count(),
        ];
    @endphp
    
    const typeData = @json($typeData);
    const typeSeries = [typeData.increase, typeData.decrease, typeData.no_change];
    const typeLabels = [
        '{{ __("pagination.increase") }}',
        '{{ __("pagination.decrease") }}',
        '{{ __("pagination.no_change") }}'
    ];
    const typeColors = ['#50CD89', '#F1416C', '#A8A8A8'];
    
    if (typeSeries.some(v => v > 0)) {
        new ApexCharts(document.querySelector("#adjustmentTypeChart"), {
            series: typeSeries,
            chart: {
                type: 'donut',
                height: 320,
                toolbar: { show: true }
            },
            labels: typeLabels,
            colors: typeColors,
            legend: {
                position: 'bottom',
                fontSize: '12px'
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: '{{ __("pagination.total") }}',
                                formatter: function() {
                                    return typeSeries.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val, { seriesIndex }) {
                        const total = typeSeries.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                        return val + ' ({{ __("pagination.adjustments") }}, ' + percentage + '%)';
                    }
                }
            }
        }).render();
    } else {
        document.querySelector("#adjustmentTypeChart").innerHTML = 
            '<div class="text-center text-muted py-10">{{ __("pagination.no_data_available") }}</div>';
    }
    
    // ============================================
    // 2. Daily Adjustment Trend Chart
    // ============================================
    @php
        $dailyTrend = $adjustments->groupBy(function($item) {
            return $item->created_at->format('Y-m-d');
        })->map(function($items, $date) {
            return [
                'date' => $date,
                'count' => $items->count(),
                'net_change' => $items->sum(function($item) {
                    return $item->quantity_after - $item->quantity_before;
                })
            ];
        })->sortKeys()->values();
    @endphp
    
    const dailyData = @json($dailyTrend);
    const dailyLabels = dailyData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    const dailyCounts = dailyData.map(item => item.count);
    
    if (dailyCounts.some(v => v > 0)) {
        new ApexCharts(document.querySelector("#dailyAdjustmentChart"), {
            series: [{
                name: '{{ __("pagination.adjustments") }}',
                data: dailyCounts,
                type: 'bar'
            }],
            chart: {
                type: 'line',
                height: 320,
                toolbar: { show: true },
                zoom: { enabled: true }
            },
            stroke: {
                width: [0, 2]
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
                    style: { fontSize: '10px' }
                },
                title: {
                    text: '{{ __("pagination.date") }}',
                    style: { fontSize: '12px', fontWeight: 'bold' }
                }
            },
            yaxis: {
                title: {
                    text: '{{ __("pagination.number_of_adjustments") }}',
                    style: { fontSize: '12px', fontWeight: 'bold' }
                },
                min: 0,
                tickAmount: 5
            },
            colors: ['#3E97FF'],
            markers: {
                size: 5,
                colors: ['#3E97FF'],
                strokeColors: '#fff',
                strokeWidth: 2
            },
            tooltip: {
                y: {
                    formatter: function(val, { dataPointIndex }) {
                        const item = dailyData[dataPointIndex];
                        let tooltip = `<strong>${val} {{ __("pagination.adjustments") }}</strong>`;
                        if (item && item.net_change !== 0) {
                            const sign = item.net_change >= 0 ? '+' : '';
                            tooltip += `<br>{{ __("pagination.net_change") }}: ${sign}${Math.abs(item.net_change)}`;
                        }
                        return tooltip;
                    }
                }
            }
        }).render();
    } else {
        document.querySelector("#dailyAdjustmentChart").innerHTML = 
            '<div class="text-center text-muted py-10">{{ __("pagination.no_data_available") }}</div>';
    }
});
</script>
@endif

<script>
// ============================================
// Form Validation
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
    
    // Initialize Select2 for variant dropdown
    const variantSelect = document.querySelector('[name="variant_id"]');
    if (variantSelect && typeof $.fn.select2 !== 'undefined') {
        $(variantSelect).select2({
            placeholder: '{{ __("pagination.search_variant") }}',
            allowClear: true,
            width: '100%'
        });
    }
});

// ============================================
// Export Functions
// ============================================
function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __("pagination.table_not_found") }}');
        return;
    }
    try {
        if (typeof XLSX === 'undefined') {
            alert('{{ __("pagination.export_library_missing") }}');
            return;
        }
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Adjustments');
        XLSX.writeFile(wb, filename + '.xlsx');
        if (typeof toastr !== 'undefined') {
            toastr.success('{{ __("pagination.export_successful") }}');
        }
    } catch (e) {
        alert('{{ __("pagination.export_error") }}: ' + e.message);
    }
}

// ============================================
// Print Styles
// ============================================
const style = document.createElement('style');
style.innerHTML = `
    @media print {
        .app-toolbar .dropdown,
        #filterForm,
        .btn,
        .kt_app_toolbar .d-flex.gap-2 {
            display: none !important;
        }
        .card {
            border: 1px solid #ddd !important;
            break-inside: avoid;
        }
        .table {
            font-size: 10px !important;
        }
        .badge {
            border: 1px solid #ddd !important;
        }
        .symbol {
            display: none !important;
        }
        #adjustmentTypeChart,
        #dailyAdjustmentChart {
            height: 250px !important;
        }
        .card-footer {
            display: none !important;
        }
    }
`;
document.head.appendChild(style);
</script>
@endpush

@endsection