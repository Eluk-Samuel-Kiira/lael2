{{-- resources/views/reports/inventory/movement-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.movement_analysis_report'))

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
                                {{ __('pagination.movement_analysis_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.movement_analysis') }}</li>
                            </ul>
                        </div>
                        @if($movementData->count() > 0)
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('movementTable', 'movement_analysis_{{ date('Y_m_d') }}')">
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
                        <form method="GET" action="{{ route('reports.inventory.movement-analysis') }}" id="filterForm">
                            {{-- First Line --}}
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
                                
                                {{-- Movement Type --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('pagination.movement_type') }}</label>
                                    <select class="form-select" name="movement_type">
                                        <option value="all" {{ $movementType == 'all' ? 'selected' : '' }}>
                                            {{ __('pagination.all_types') }}
                                        </option>
                                        <option value="fast" {{ $movementType == 'fast' ? 'selected' : '' }}>
                                            {{ __('pagination.fast_moving') }}
                                        </option>
                                        <option value="slow" {{ $movementType == 'slow' ? 'selected' : '' }}>
                                            {{ __('pagination.slow_moving') }}
                                        </option>
                                        <option value="non-moving" {{ $movementType == 'non-moving' ? 'selected' : '' }}>
                                            {{ __('pagination.non_moving') }}
                                        </option>
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
                                    <a href="{{ route('reports.inventory.movement-analysis') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('pagination.clear_filters') }}
                                    </a>
                                    @if($movementData->count() > 0)
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('pagination.showing') }} <strong>{{ $movementData->count() }}</strong> {{ __('pagination.items') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Movement Statistics --}}
                @if($movementData->count() > 0)
                <div class="row g-6 mb-6">
                    @php
                        $stats = [
                            [
                                'key' => 'fast_moving', 
                                'color' => 'success', 
                                'icon' => 'ki-rocket', 
                                'label' => 'Fast Moving',
                                'value' => number_format($movementStats['fast_moving']),
                                'subtitle' => __('pagination.high_demand_items')
                            ],
                            [
                                'key' => 'slow_moving', 
                                'color' => 'warning', 
                                'icon' => 'ki-walk', 
                                'label' => 'Slow Moving',
                                'value' => number_format($movementStats['slow_moving']),
                                'subtitle' => __('pagination.low_demand_items')
                            ],
                            [
                                'key' => 'non_moving', 
                                'color' => 'danger', 
                                'icon' => 'ki-pause-circle', 
                                'label' => 'Non-Moving',
                                'value' => number_format($movementStats['non_moving']),
                                'subtitle' => __('pagination.no_movement_items')
                            ],
                            [
                                'key' => 'total_items', 
                                'color' => 'primary', 
                                'icon' => 'ki-box', 
                                'label' => 'Total Items',
                                'value' => number_format($movementData->total()),
                                'subtitle' => __('pagination.period') . ': ' . $startDate . ' - ' . $endDate
                            ]
                        ];
                    @endphp
                    
                    @foreach($stats as $stat)
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

                {{-- Movement Analysis Table --}}
                @if($movementData->count() > 0)
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.movement_analysis_details') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">
                                {{ $movementData->count() }} {{ __('pagination.items') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="movementTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4" style="width: 12%;">{{ __('pagination.sku') }}</th>
                                        <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                        <th style="width: 10%;" class="text-center">{{ __('pagination.total_movement') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.transactions') }}</th>
                                        <th style="width: 10%;" class="text-center">{{ __('pagination.avg_daily') }}</th>
                                        <th style="width: 12%;">{{ __('pagination.first_movement') }}</th>
                                        <th style="width: 12%;">{{ __('pagination.last_movement') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.days_since_last') }}</th>
                                        <th style="width: 10%;" class="text-center">{{ __('pagination.movement_category') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($movementData as $item)
                                    @php
                                        $daysSinceColor = 'success';
                                        if ($item->days_since_last_movement > 30) {
                                            $daysSinceColor = 'warning';
                                        }
                                        if ($item->days_since_last_movement > 90) {
                                            $daysSinceColor = 'danger';
                                        }
                                        $progressWidth = min(100, ($item->avg_daily_movement / 20) * 100);
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
                                        <td class="text-center">
                                            <span class="fw-bold text-primary">{{ number_format($item->total_movement) }}</span>
                                            <div class="progress mt-1 mx-auto" style="height: 4px; width: 60px;">
                                                <div class="progress-bar bg-{{ $item->movement_color }}" 
                                                    style="width: {{ min(100, $progressWidth) }}%"></div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-info">{{ number_format($item->transaction_count) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold">{{ number_format($item->avg_daily_movement, 1) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $item->first_movement ? Carbon\Carbon::parse($item->first_movement)->format('Y-m-d') : '-' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $item->last_movement ? Carbon\Carbon::parse($item->last_movement)->format('Y-m-d') : '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-{{ $daysSinceColor }}">
                                                {{ number_format($item->days_since_last_movement) }}
                                                <small class="d-block text-muted">{{ __('pagination.days') }}</small>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $item->movement_color }} py-2 px-3">
                                                <i class="ki-duotone {{ $item->movement_icon }} fs-3 me-1"></i>
                                                {{ $item->movement_label }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pagination --}}
                    @if($movementData->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $movementData,
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
                            <p class="text-muted fs-6">{{ __('pagination.no_movement_data_found') }}</p>
                            @if(request()->hasAny(['start_date', 'end_date', 'movement_type', 'department_id', 'location_id', 'variant_id']))
                            <a href="{{ route('reports.inventory.movement-analysis') }}" class="btn btn-light-primary mt-3">
                                <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                {{ __('pagination.clear_filters') }}
                            </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Metadata Footer --}}
                @if($movementData->count() > 0)
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
                        | {{ $movementData->total() }} {{ __('pagination.items') }}
                        | {{ number_format($movementStats['total_movement'] ?? 0) }} {{ __('pagination.total_movement') }}
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
        XLSX.utils.book_append_sheet(wb, ws, 'Movement Analysis');
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