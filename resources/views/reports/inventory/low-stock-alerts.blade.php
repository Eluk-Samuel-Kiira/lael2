{{-- resources/views/reports/inventory/low-stock-alerts.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.low_stock_alerts'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                
                {{-- ============================================================ --}}
                {{-- TOOLBAR SECTION --}}
                {{-- ============================================================ --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('pagination.low_stock_alerts') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.low_stock_alerts') }}</li>
                            </ul>
                        </div>
                        @if(isset($summary) && $summary['total_items'] > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('lowStockTable', 'low_stock_alerts')">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                                <i class="ki-duotone ki-printer fs-2"></i> {{ __('accounting.print') }}
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- FILTER SECTION --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form method="GET" action="{{ route('reports.inventory.low-stock-alerts') }}" id="filterForm">
                            <div class="row g-3 mb-3">
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
                                
                                {{-- Product Variant --}}
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">{{ __('pagination.product_variant') }}</label>
                                    <select class="form-select" name="variant_id" data-control="select2">
                                        <option value="">{{ __('pagination.all_variants') }}</option>
                                        @foreach($variants ?? [] as $variant)
                                            <option value="{{ $variant->id }}" {{ ($variantId ?? '') == $variant->id ? 'selected' : '' }}>
                                                {{ $variant->name }} ({{ $variant->sku }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- Severity Level --}}
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">{{ __('pagination.severity_level') }}</label>
                                    <div class="d-flex flex-wrap gap-3 mt-1">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="severity" 
                                                value="all" id="severity_all" 
                                                {{ ($severity ?? 'all') === 'all' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="severity_all">
                                                <span class="badge badge-light-dark">{{ __('pagination.all') }}</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="severity" 
                                                value="critical" id="severity_critical" 
                                                {{ ($severity ?? '') === 'critical' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="severity_critical">
                                                <span class="badge badge-light-danger">{{ __('pagination.critical') }}</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="severity" 
                                                value="warning" id="severity_warning" 
                                                {{ ($severity ?? '') === 'warning' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="severity_warning">
                                                <span class="badge badge-light-warning">{{ __('pagination.warning') }}</span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="radio" name="severity" 
                                                value="low" id="severity_low" 
                                                {{ ($severity ?? '') === 'low' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="severity_low">
                                                <span class="badge badge-light-info">{{ __('pagination.low') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.inventory.low-stock-alerts') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </a>
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('pagination.showing') }} <strong>{{ $lowStockItems->count() ?? 0 }}</strong> {{ __('pagination.items') }}
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA MESSAGE --}}
                {{-- ============================================================ --}}
                @if(!isset($summary) || $summary['total_items'] == 0)
                <div class="card">
                    <div class="card-body text-center py-10">
                        <i class="ki-duotone ki-check-circle fs-4tx text-success mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_low_stock_items') }}</h4>
                        <p class="text-muted fs-6">{{ __('pagination.all_stock_levels_are_adequate') }}</p>
                        @if(request()->hasAny(['location_id', 'department_id', 'variant_id', 'severity']))
                        <a href="{{ route('reports.inventory.low-stock-alerts') }}" class="btn btn-light-primary mt-3">
                            <i class="ki-duotone ki-cross fs-2 me-2"></i> {{ __('pagination.clear_filters_view_all') }}
                        </a>
                        @endif
                    </div>
                </div>
                @else

                {{-- Summary Cards --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-danger border border-danger border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-danger">{{ number_format($summary['critical'] ?? 0) }}</div>
                                <div class="text-muted">{{ __('pagination.critical_alerts') }}</div>
                                <span class="badge badge-light-danger mt-2">{{ __('pagination.needs_immediate_action') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-warning">{{ number_format($summary['warning'] ?? 0) }}</div>
                                <div class="text-muted">{{ __('pagination.warning_alerts') }}</div>
                                <span class="badge badge-light-warning mt-2">{{ __('pagination.should_review_soon') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-primary">{{ number_format($summary['total_items'] ?? 0) }}</div>
                                <div class="text-muted">{{ __('pagination.total_alerts') }}</div>
                                <span class="badge badge-light-primary mt-2">{{ __('pagination.total_low_stock_items') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-info border border-info border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-info">{{ currency_symbol() }}{{ number_format($summary['total_value_at_risk'] ?? 0, 2) }}</div>
                                <div class="text-muted">{{ __('pagination.value_at_risk') }}</div>
                                <span class="badge badge-light-info mt-2">{{ __('pagination.potential_loss_if_not_replenished') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- CHARTS SECTION --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    {{-- Severity Distribution --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.severity_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="severityChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Department Distribution --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.department_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="departmentChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- LOW STOCK ITEMS TABLE --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.low_stock_items') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">
                                {{ $lowStockItems->count() ?? 0 }} {{ __('pagination.items') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="lowStockTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4" style="width: 10%;">{{ __('pagination.sku') }}</th>
                                        <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                        <th style="width: 12%;">{{ __('pagination.department') }}</th>
                                        <th style="width: 12%;">{{ __('pagination.location') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.current_stock') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.threshold') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.deficit') }}</th>
                                        <th style="width: 10%;" class="text-center">{{ __('pagination.severity') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.urgency') }}</th>
                                        <th style="width: 6%;" class="text-center">{{ __('pagination.source') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockItems as $item)
                                    @php
                                        $threshold = $item->low_stock_threshold ?? ($item->preferred_stock_level ?? 0);
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold">{{ $item->sku }}</span>
                                            @if($item->barcode)
                                            <br><small class="text-muted">{{ $item->barcode }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($item->image_url)
                                                <div class="symbol symbol-40px me-2">
                                                    <img src="{{ asset($item->image_url) }}" class="img-fluid rounded" alt="{{ $item->variant_name }}">
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
                                            <span class="fw-bold text-{{ $item->severity_color ?? 'success' }}">
                                                {{ number_format($item->quantity_on_hand) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ isset($isSingleShop) && $isSingleShop ? 'primary' : 'info' }}">
                                                {{ number_format($threshold) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold text-danger">
                                                -{{ number_format($item->deficit) }}
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                {{ currency_symbol() }}{{ number_format($item->deficit_value, 2) }}
                                            </small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $item->severity_color ?? 'secondary' }}">
                                                <i class="ki-duotone {{ $item->severity_icon ?? 'ki-information' }} fs-2 me-1"></i>
                                                {{ $item->severity_text ?? 'N/A' }}
                                            </span>
                                            <div class="progress mt-1 mx-auto" style="height: 4px; width: 60px;">
                                                <div class="progress-bar bg-{{ $item->severity_color ?? 'secondary' }}" 
                                                    style="width: {{ min(100, ($item->reorder_percentage ?? 0)) }}%;"></div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $item->urgency_color ?? 'secondary' }}">
                                                {{ $item->urgency_text ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ isset($isSingleShop) && $isSingleShop ? 'primary' : 'info' }}">
                                                {{ isset($isSingleShop) && $isSingleShop ? __('pagination.global') : __('pagination.dept') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pagination --}}
                    @if(isset($lowStockItems) && $lowStockItems->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $lowStockItems,
                            'pageName' => 'page',
                            'perPageName' => 'per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif

                    {{-- Legend --}}
                    <div class="card-footer text-muted fs-7">
                        <i class="ki-duotone ki-information-4 fs-2 me-1"></i>
                        <strong>{{ __('pagination.legend') }}:</strong>
                        <span class="badge badge-light-danger mx-1">🔴</span> {{ __('pagination.critical') }} - {{ __('pagination.below_30_percent') }}
                        <span class="badge badge-light-warning mx-1">🟡</span> {{ __('pagination.warning') }} - {{ __('pagination.below_60_percent') }}
                        <span class="badge badge-light-info mx-1">🔵</span> {{ __('pagination.low') }} - {{ __('pagination.below_80_percent') }}
                        @if(isset($isSingleShop))
                        <span class="badge badge-light-{{ $isSingleShop ? 'primary' : 'info' }} mx-1">
                            {{ $isSingleShop ? '🌐' : '🏢' }}
                        </span> {{ $isSingleShop ? __('pagination.global_stock') : __('pagination.department_stock') }}
                        @endif
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- METADATA FOOTER --}}
                {{-- ============================================================ --}}
                <div class="mt-6 text-muted text-center fs-7">
                    <hr>
                    <p>
                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                        {{ __('accounting.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }}
                        @if(isset($isSingleShop))
                            | {{ __('pagination.mode') }}: {{ $isSingleShop ? __('pagination.single_shop') : __('pagination.multi_shop') }}
                        @endif
                        @if(isset($locationId) && $locationId)
                            | {{ __('auth.location') }}: {{ $locations->where('id', $locationId)->first()->name ?? 'N/A' }}
                        @endif
                        @if(isset($departmentId) && $departmentId)
                            | {{ __('auth.department') }}: {{ $departments->where('id', $departmentId)->first()->name ?? 'N/A' }}
                        @endif
                        | {{ $summary['total_items'] ?? 0 }} {{ __('pagination.alerts') }}
                        | {{ __('pagination.value_at_risk') }}: {{ currency_symbol() }}{{ number_format($summary['total_value_at_risk'] ?? 0, 2) }}
                    </p>
                </div>

                @endif {{-- End of data check --}}
                
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================================ --}}
@push('scripts')
@if(isset($lowStockItems) && $lowStockItems->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Severity Distribution Chart ────────────────────────────────
    const criticalCount = {{ $summary['critical'] ?? 0 }};
    const warningCount = {{ $summary['warning'] ?? 0 }};
    const lowCount = {{ $summary['low'] ?? 0 }};
    const totalItems = criticalCount + warningCount + lowCount;
    
    const severityData = [];
    const severityLabels = [];
    const severityColors = [];
    
    if (criticalCount > 0) {
        severityData.push(criticalCount);
        severityLabels.push('{{ __("pagination.critical") }}');
        severityColors.push('#F1416C');
    }
    if (warningCount > 0) {
        severityData.push(warningCount);
        severityLabels.push('{{ __("pagination.warning") }}');
        severityColors.push('#FFC700');
    }
    if (lowCount > 0) {
        severityData.push(lowCount);
        severityLabels.push('{{ __("pagination.low") }}');
        severityColors.push('#3E97FF');
    }
    
    if (severityData.length > 0) {
        new ApexCharts(document.querySelector("#severityChart"), {
            series: severityData,
            chart: {
                type: 'donut',
                height: 320,
                toolbar: { show: true }
            },
            labels: severityLabels,
            colors: severityColors,
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
                                label: '{{ __("pagination.total_items") }}',
                                formatter: function() {
                                    return totalItems;
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        const percentage = totalItems > 0 ? ((val / totalItems) * 100).toFixed(1) : 0;
                        return val + ' items (' + percentage + '%)';
                    }
                }
            }
        }).render();
    } else {
        document.querySelector("#severityChart").innerHTML = 
            '<div class="text-center text-muted py-10">{{ __("pagination.no_data_available") }}</div>';
    }
    
    // ─── Department Distribution Chart ──────────────────────────────
    @php
        $departmentData = isset($lowStockItems) ? $lowStockItems->groupBy('department_name')->map(function($items) {
            return $items->count();
        })->sortDesc()->toArray() : [];
    @endphp
    
    const deptData = @json(array_values($departmentData));
    const deptLabels = @json(array_keys($departmentData));
    
    if (deptData.length > 0 && deptData.some(v => v > 0)) {
        new ApexCharts(document.querySelector("#departmentChart"), {
            series: [{
                name: '{{ __("pagination.low_stock_items") }}',
                data: deptData
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
                    return val + ' {{ __("pagination.items") }}';
                },
                style: { fontSize: '11px' }
            },
            xaxis: {
                categories: deptLabels,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: '{{ __("auth.department") }}' }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val + ' {{ __("pagination.low_stock_items") }}';
                    }
                }
            }
        }).render();
    } else {
        document.querySelector("#departmentChart").innerHTML = 
            '<div class="text-center text-muted py-10">{{ __("pagination.no_data_available") }}</div>';
    }
});
</script>
@endif

<script>
// ─── Form Validation ──────────────────────────────────────────────
document.getElementById('filterForm')?.addEventListener('submit', function(e) {
    return true;
});

// ─── Export Functions ──────────────────────────────────────────────
function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __("accounting.table_not_found") }}');
        return;
    }
    try {
        if (typeof XLSX === 'undefined') {
            alert('{{ __("accounting.export_library_missing") }}');
            return;
        }
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Low Stock Alerts');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch (e) {
        alert('{{ __("accounting.export_error") }}: ' + e.message);
    }
}

function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __("accounting.table_not_found") }}');
        return;
    }
    try {
        const rows = table.querySelectorAll('tr');
        let csv = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowData = Array.from(cols).map(col => col.innerText.trim());
            csv.push(rowData.join(','));
        });
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename + '.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    } catch (e) {
        alert('{{ __("accounting.export_error") }}: ' + e.message);
    }
}

// ─── Print Styles ──────────────────────────────────────────────────
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
        .progress {
            display: none !important;
        }
        .symbol {
            display: none !important;
        }
        #severityChart,
        #departmentChart {
            height: 250px !important;
        }
    }
`;
document.head.appendChild(style);
</script>
@endpush

@endsection