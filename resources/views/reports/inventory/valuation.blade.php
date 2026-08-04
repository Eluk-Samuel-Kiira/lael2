{{-- resources/views/reports/inventory/valuation.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_valuation_report'))

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
                                {{ __('pagination.inventory_valuation_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_valuation') }}</li>
                            </ul>
                        </div>
                        @if($inventoryItems->count() > 0)
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('valuationTable', 'inventory_valuation_{{ date('Y_m_d') }}')">
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
                        <form method="GET" action="{{ route('reports.inventory.valuation') }}" id="filterForm">
                            {{-- First Line --}}
                            <div class="row g-3 mb-3">
                                {{-- Valuation Method --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('pagination.valuation_method') }}</label>
                                    <select class="form-select" name="valuation_method">
                                        <option value="cost" {{ $valuationMethod == 'cost' ? 'selected' : '' }}>
                                            {{ __('pagination.cost_method') }}
                                        </option>
                                        <option value="fifo" {{ $valuationMethod == 'fifo' ? 'selected' : '' }}>
                                            {{ __('pagination.fifo_method') }}
                                        </option>
                                        <option value="lifo" {{ $valuationMethod == 'lifo' ? 'selected' : '' }}>
                                            {{ __('pagination.lifo_method') }}
                                        </option>
                                        <option value="weighted_average" {{ $valuationMethod == 'weighted_average' ? 'selected' : '' }}>
                                            {{ __('pagination.weighted_average_method') }}
                                        </option>
                                    </select>
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
                                <div class="col-md-6">
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
                                    <a href="{{ route('reports.inventory.valuation') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('pagination.clear_filters') }}
                                    </a>
                                    @if($inventoryItems->count() > 0)
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('pagination.showing') }} <strong>{{ $inventoryItems->count() }}</strong> {{ __('pagination.items') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Valuation Summary --}}
                @if($inventoryItems->count() > 0)
                <div class="row g-6 mb-6">
                    @php
                        $summaryStats = [
                            [
                                'key' => 'total_items', 
                                'color' => 'primary', 
                                'icon' => 'ki-box', 
                                'label' => 'Total Items',
                                'value' => number_format($valuationSummary['total_items']),
                                'subtitle' => __('pagination.items_in_stock')
                            ],
                            [
                                'key' => 'total_quantity', 
                                'color' => 'info', 
                                'icon' => 'ki-barcode', 
                                'label' => 'Total Quantity',
                                'value' => number_format($valuationSummary['total_quantity']),
                                'subtitle' => __('pagination.units_in_stock')
                            ],
                            [
                                'key' => 'total_value', 
                                'color' => 'success', 
                                'icon' => 'ki-dollar', 
                                'label' => 'Total Value',
                                'value' => currency_symbol() . number_format($valuationSummary['total_value'], 2),
                                'subtitle' => __('pagination.current_inventory_value')
                            ],
                            [
                                'key' => 'potential_profit', 
                                'color' => 'danger', 
                                'icon' => 'ki-trend-up', 
                                'label' => 'Potential Profit',
                                'value' => currency_symbol() . number_format($valuationSummary['potential_profit'], 2),
                                'subtitle' => __('pagination.potential_profit_margin')
                            ]
                        ];
                    @endphp
                    
                    @foreach($summaryStats as $stat)
                    <div class="col-md-6 col-lg-3">
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
                                <div class="fs-8 text-muted mt-1">
                                    {{ $stat['subtitle'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Charts Section - Value by Department & Location --}}
                @if($inventoryItems->count() > 0)
                <div class="row g-6 mb-6">
                    {{-- Value by Department --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.value_by_department') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="departmentValuationChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Value by Location --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.value_by_location') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="locationValuationChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Valuation Details Table --}}
                @if($inventoryItems->count() > 0)
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.inventory_valuation_details') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">
                                {{ $inventoryItems->count() }} {{ __('pagination.items') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="valuationTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4" style="width: 10%;">{{ __('pagination.sku') }}</th>
                                        <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                        <th style="width: 10%;">{{ __('pagination.department') }}</th>
                                        <th style="width: 10%;">{{ __('pagination.location') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.quantity') }}</th>
                                        <th style="width: 10%;" class="text-end">{{ __('pagination.unit_cost') }}</th>
                                        <th style="width: 10%;" class="text-end">{{ __('pagination.unit_price') }}</th>
                                        <th style="width: 10%;" class="text-end">{{ __('pagination.total_value') }}</th>
                                        <th style="width: 10%;" class="text-end">{{ __('pagination.potential_profit') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.profit_margin') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($inventoryItems as $item)
                                    @php
                                        $costPrice = $item->cost_price ?? 0;
                                        $sellingPrice = $item->selling_price ?? 0;
                                        $quantity = $item->quantity_allocated;
                                        $profitMargin = $item->profit_margin ?? 0;
                                        
                                        $marginColor = 'success';
                                        if ($profitMargin < 20 && $profitMargin >= 0) {
                                            $marginColor = 'warning';
                                        }
                                        if ($profitMargin < 0) {
                                            $marginColor = 'danger';
                                        }
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold">{{ $item->variant->sku ?? '-' }}</div>
                                            <small class="text-muted">{{ $item->variant->barcode ?? '' }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($item->variant && $item->variant->image_url)
                                                <div class="symbol symbol-40px me-2">
                                                    <img src="{{ asset($item->variant->image_url) }}" class="rounded" alt="{{ $item->variant->name }}">
                                                </div>
                                                @endif
                                                <div>
                                                    <div class="fw-bold">{{ $item->variant->name ?? '-' }}</div>
                                                    <div class="text-muted fs-7">{{ $item->variant->product->name ?? '' }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary">{{ $item->departmentItem->name ?? '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-info">{{ $item->itemLocation->name ?? '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold">{{ number_format($quantity) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-gray-700">{{ currency_symbol() }}{{ number_format($costPrice, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-gray-700">{{ currency_symbol() }}{{ number_format($sellingPrice, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($item->valuation_value ?? 0, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-primary">{{ currency_symbol() }}{{ number_format($item->potential_profit ?? 0, 2) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $marginColor }}">
                                                {{ number_format($profitMargin, 1) }}%
                                            </span>
                                            <div class="progress mt-1 mx-auto" style="height: 4px; width: 50px;">
                                                <div class="progress-bar bg-{{ $marginColor }}" 
                                                     style="width: {{ min(100, max(0, $profitMargin)) }}%"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @php
                                    $pageTotalValue = $inventoryItems->sum('valuation_value');
                                @endphp
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="7" class="text-end fw-bold">{{ __('pagination.page_total') }}: </td>
                                        <td class="text-end fw-bold text-success">
                                            {{ currency_symbol() }}{{ number_format($pageTotalValue, 2) }}
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                    @if($inventoryItems->total() > $inventoryItems->count())
                                    <tr>
                                        <td colspan="7" class="text-end fw-bold text-muted">{{ __('pagination.grand_total') }}: </td>
                                        <td class="text-end fw-bold text-success">
                                            {{ currency_symbol() }}{{ number_format($valuationSummary['total_value'], 2) }}
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pagination --}}
                    @if($inventoryItems->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $inventoryItems,
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
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('pagination.no_valuation_data_found') }}</p>
                            @if(request()->hasAny(['department_id', 'location_id', 'variant_id', 'valuation_method']))
                            <a href="{{ route('reports.inventory.valuation') }}" class="btn btn-light-primary mt-3">
                                <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                {{ __('pagination.clear_filters') }}
                            </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Metadata Footer --}}
                @if($inventoryItems->count() > 0)
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
                        | {{ $inventoryItems->total() }} {{ __('pagination.items') }}
                        | {{ __('pagination.total_value') }}: {{ currency_symbol() }}{{ number_format($valuationSummary['total_value'], 2) }}
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
@if($inventoryItems->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // 1. Department Valuation Chart (Pie)
    // ============================================
    @php
        $deptNames = $valueByDepartment->pluck('name')->map(function($name) {
            return $name ?: 'Unspecified';
        })->toArray();
        $deptValues = $valueByDepartment->pluck('value')->toArray();
    @endphp
    
    const deptNames = @json($deptNames);
    const deptValues = @json($deptValues);
    
    if (deptValues.length > 0 && deptValues.some(v => v > 0)) {
        const colors = ['#3E97FF', '#50CD89', '#FFC700', '#F1416C', '#7239EA', '#7E8299', '#009ef7'];
        new ApexCharts(document.querySelector("#departmentValuationChart"), {
            series: deptValues,
            chart: {
                type: 'pie',
                height: 320,
                toolbar: { show: true }
            },
            labels: deptNames,
            colors: colors.slice(0, deptValues.length),
            legend: {
                position: 'bottom',
                fontSize: '11px',
                labels: { colors: '#5B5B5B' }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '{{ currency_symbol() }}' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '0%'
                    }
                }
            }
        }).render();
    }
    
    // ============================================
    // 2. Location Valuation Chart (Bar)
    // ============================================
    @php
        $locNames = $valueByLocation->pluck('name')->map(function($name) {
            return $name ?: 'Unspecified';
        })->toArray();
        $locValues = $valueByLocation->pluck('value')->toArray();
    @endphp
    
    const locNames = @json($locNames);
    const locValues = @json($locValues);
    
    if (locValues.length > 0 && locValues.some(v => v > 0)) {
        new ApexCharts(document.querySelector("#locationValuationChart"), {
            series: [{
                name: '{{ __("pagination.valuation_value") }}',
                data: locValues
            }],
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: true }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '50%',
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return '{{ currency_symbol() }}' + val.toLocaleString(undefined, {minimumFractionDigits: 0});
                },
                style: { fontSize: '10px' }
            },
            xaxis: {
                categories: locNames,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: '{{ __("pagination.location") }}' }
            },
            colors: ['#50CD89'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '{{ currency_symbol() }}' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            }
        }).render();
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
            // Validate if needed
            return true;
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
        if (typeof toastr !== 'undefined') {
            toastr.error('{{ __("pagination.table_not_found") }}');
        } else {
            alert('{{ __("pagination.table_not_found") }}');
        }
        return;
    }
    try {
        if (typeof XLSX === 'undefined') {
            alert('{{ __("pagination.export_library_missing") }}');
            return;
        }
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Inventory Valuation');
        XLSX.writeFile(wb, filename + '.xlsx');
        if (typeof toastr !== 'undefined') {
            toastr.success('{{ __("pagination.export_successful") }}');
        }
    } catch (e) {
        if (typeof toastr !== 'undefined') {
            toastr.error('{{ __("pagination.export_error") }}');
        } else {
            alert('{{ __("pagination.export_error") }}: ' + e.message);
        }
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
        .card-footer {
            display: none !important;
        }
        #departmentValuationChart,
        #locationValuationChart {
            height: 250px !important;
        }
    }
`;
document.head.appendChild(style);
</script>
@endpush

@endsection