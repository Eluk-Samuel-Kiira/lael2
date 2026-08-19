{{-- resources/views/reports/inventory/excess-stock.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.excess_stock_report'))

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
                                {{ __('pagination.excess_stock_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.excess_stock') }}</li>
                            </ul>
                        </div>
                        @if($excessStockItems->count() > 0)
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('excessStockTable', 'excess_stock_{{ date('Y_m_d') }}')">
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
                        <form method="GET" action="{{ route('reports.inventory.excess-stock') }}" id="filterForm">
                            {{-- First Line --}}
                            <div class="row g-3 mb-3">
                                {{-- Excess Threshold --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('pagination.excess_threshold') }}</label>
                                    <select class="form-select" name="excess_threshold">
                                        <option value="1.2" {{ $excessThreshold == 1.2 ? 'selected' : '' }}>
                                            120% (20% Excess)
                                        </option>
                                        <option value="1.3" {{ $excessThreshold == 1.3 ? 'selected' : '' }}>
                                            130% (30% Excess)
                                        </option>
                                        <option value="1.5" {{ $excessThreshold == 1.5 ? 'selected' : '' }}>
                                            150% (50% Excess)
                                        </option>
                                        <option value="2.0" {{ $excessThreshold == 2.0 ? 'selected' : '' }}>
                                            200% (100% Excess)
                                        </option>
                                        <option value="2.5" {{ $excessThreshold == 2.5 ? 'selected' : '' }}>
                                            250% (150% Excess)
                                        </option>
                                    </select>
                                    <div class="form-text">{{ __('pagination.items_above_threshold_are_excess') }}</div>
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
                                <div class="col-md-4">
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
                                    <a href="{{ route('reports.inventory.excess-stock') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('pagination.clear_filters') }}
                                    </a>
                                    @if($excessStockItems->count() > 0)
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('pagination.showing') }} <strong>{{ $excessStockItems->count() }}</strong> {{ __('pagination.items') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Excess Stock Summary --}}
                @if($excessStockItems->count() > 0)
                <div class="row g-6 mb-6">
                    @php
                        $summaryStats = [
                            [
                                'key' => 'total_items', 
                                'color' => 'warning', 
                                'icon' => 'ki-box', 
                                'label' => 'Excess Items',
                                'value' => number_format($summary['total_items']),
                                'subtitle' => __('pagination.items_exceeding_threshold')
                            ],
                            [
                                'key' => 'total_excess_quantity', 
                                'color' => 'danger', 
                                'icon' => 'ki-barcode', 
                                'label' => 'Excess Quantity',
                                'value' => number_format($summary['total_excess_quantity']),
                                'subtitle' => __('pagination.extra_units_above_threshold')
                            ],
                            [
                                'key' => 'total_excess_value', 
                                'color' => 'danger', 
                                'icon' => 'ki-dollar', 
                                'label' => 'Excess Value',
                                'value' => currency_symbol() . number_format($summary['total_excess_value'], 2),
                                'subtitle' => __('pagination.value_of_excess_stock')
                            ],
                            [
                                'key' => 'avg_excess_percentage', 
                                'color' => 'warning', 
                                'icon' => 'ki-percentage', 
                                'label' => 'Avg Excess %',
                                'value' => number_format($summary['avg_excess_percentage'], 1) . '%',
                                'subtitle' => __('pagination.average_excess_per_item')
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

                {{-- Excess Categories --}}
                @if($excessStockItems->count() > 0)
                <div class="row g-6 mb-6">
                    @php
                        $severityStats = [
                            [
                                'key' => '50_100', 
                                'color' => 'warning', 
                                'icon' => 'ki-warning-2', 
                                'label' => 'Moderate Excess',
                                'value' => number_format($excessCategories['50_100']),
                                'subtitle' => '50-100%',
                                'description' => __('pagination.moderate_excess_description')
                            ],
                            [
                                'key' => '100_200', 
                                'color' => 'danger', 
                                'icon' => 'ki-danger', 
                                'label' => 'High Excess',
                                'value' => number_format($excessCategories['100_200']),
                                'subtitle' => '100-200%',
                                'description' => __('pagination.high_excess_description')
                            ],
                            [
                                'key' => 'over_200', 
                                'color' => 'danger', 
                                'icon' => 'ki-cross', 
                                'label' => 'Critical Excess',
                                'value' => number_format($excessCategories['over_200']),
                                'subtitle' => '>200%',
                                'description' => __('pagination.critical_excess_description')
                            ]
                        ];
                    @endphp
                    
                    @foreach($severityStats as $stat)
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-2 h-100">
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
                                <div class="fs-7 text-{{ $stat['color'] }} fw-bold mb-1">
                                    {{ $stat['subtitle'] }}
                                </div>
                                <div class="fs-8 text-muted">
                                    {{ $stat['description'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Excess Stock Table --}}
                @if($excessStockItems->count() > 0)
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.excess_stock_details') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-warning fs-7">
                                {{ $excessStockItems->count() }} {{ __('pagination.items') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="excessStockTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4" style="width: 10%;">{{ __('pagination.sku') }}</th>
                                        <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                        <th style="width: 10%;">{{ __('pagination.department') }}</th>
                                        <th style="width: 10%;">{{ __('pagination.location') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.current_stock') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.preferred_stock') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.excess_quantity') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.excess_percentage') }}</th>
                                        <th style="width: 10%;" class="text-center">{{ __('pagination.severity') }}</th>
                                        <th style="width: 10%;" class="text-end">{{ __('pagination.excess_value') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($excessStockItems as $item)
                                    @php
                                        $progressWidth = min(100, $item->excess_percentage);
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-semibold">{{ $item->sku }}</div>
                                            <small class="text-muted">{{ $item->barcode }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($item->image_url)
                                                <div class="symbol symbol-40px me-2">
                                                    <img src="{{ productVariantImage($item->image_url) }}" class="rounded" alt="{{ $item->variant_name }}">
                                                </div>
                                                @endif
                                                <div>
                                                    <div class="fw-bold">{{ $item->variant_name }}</div>
                                                    <div class="text-muted fs-7">{{ $item->product_name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary">{{ $item->department_name }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-info">{{ $item->location_name }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold">{{ number_format($item->quantity_on_hand) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-success">{{ number_format($item->preferred_stock_level) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-{{ $item->severity_color }}">
                                                +{{ number_format($item->excess_quantity) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-{{ $item->severity_color }}">
                                                {{ number_format($item->excess_percentage, 1) }}%
                                            </span>
                                            <div class="progress mt-1 mx-auto" style="height: 4px; width: 60px;">
                                                <div class="progress-bar bg-{{ $item->severity_color }}" 
                                                    style="width: {{ min(100, $progressWidth) }}%"></div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $item->severity_color }} py-2 px-3">
                                                <i class="ki-duotone {{ $item->severity_icon }} fs-3 me-1"></i>
                                                {{ $item->severity_text }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-{{ $item->severity_color }}">
                                                {{ currency_symbol() }}{{ number_format($item->excess_value, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @php
                                    $pageTotalValue = $excessStockItems->sum('excess_value');
                                @endphp
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="9" class="text-end fw-bold">{{ __('pagination.page_total') }}: </td>
                                        <td class="text-end fw-bold text-danger">
                                            {{ currency_symbol() }}{{ number_format($pageTotalValue, 2) }}
                                        </td>
                                    </tr>
                                    @if($excessStockItems->total() > $excessStockItems->count())
                                    <tr>
                                        <td colspan="9" class="text-end fw-bold text-muted">{{ __('pagination.grand_total') }}: </td>
                                        <td class="text-end fw-bold text-danger">
                                            {{ currency_symbol() }}{{ number_format($summary['total_excess_value'], 2) }}
                                        </td>
                                    </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pagination --}}
                    @if($excessStockItems->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $excessStockItems,
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
                            <i class="ki-duotone ki-check-circle fs-4tx text-success mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_excess_stock_found') }}</h4>
                            <p class="text-muted fs-6">{{ __('pagination.all_stock_within_limits') }}</p>
                            @if(request()->hasAny(['department_id', 'location_id', 'variant_id', 'excess_threshold']))
                            <a href="{{ route('reports.inventory.excess-stock') }}" class="btn btn-light-primary mt-3">
                                <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                {{ __('pagination.clear_filters') }}
                            </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Metadata Footer --}}
                @if($excessStockItems->count() > 0)
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
                        | {{ $excessStockItems->total() }} {{ __('pagination.items') }}
                        | {{ currency_symbol() }}{{ number_format($summary['total_excess_value'], 2) }} {{ __('pagination.excess_value') }}
                        | {{ __('pagination.threshold') }}: {{ ($excessThreshold * 100) }}%
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
        XLSX.utils.book_append_sheet(wb, ws, 'Excess Stock');
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
    }
`;
document.head.appendChild(style);
</script>
@endpush

@endsection