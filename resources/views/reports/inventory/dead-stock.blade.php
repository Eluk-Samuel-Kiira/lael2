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
                        @if($summary['total_items'] > 0)
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('deadStockTable', 'dead_stock_{{ date('Y_m_d') }}')">
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
                        <form method="GET" action="{{ route('reports.inventory.dead-stock') }}" id="filterForm">
                            {{-- First Line --}}
                            <div class="row g-3 mb-3">
                                {{-- Days Threshold --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('pagination.days_threshold') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                        </span>
                                        <input type="number" class="form-control" name="days_threshold" 
                                            value="{{ $daysThreshold }}" min="30" max="1095">
                                        <span class="input-group-text">{{ __('pagination.days') }}</span>
                                    </div>
                                    <div class="form-text">{{ __('pagination.items_without_movement_for_x_days') }}</div>
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
                                
                                {{-- Include Expired --}}
                                <div class="col-md-2">
                                    <div class="form-check form-switch form-check-custom form-check-solid mt-6">
                                        <input class="form-check-input" type="checkbox" name="include_expired" 
                                            value="1" id="include_expired" 
                                            {{ $includeExpired ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="include_expired">
                                            {{ __('pagination.include_expired_items') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Second Line - Action Buttons --}}
                            <div class="row">
                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="applyFilters">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('pagination.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.inventory.dead-stock') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('pagination.clear_filters') }}
                                    </a>
                                    @if($summary['total_items'] > 0)
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('pagination.showing') }} <strong>{{ $deadStockItems->count() }}</strong> {{ __('pagination.items') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Summary Statistics --}}
                @if($summary['total_items'] > 0)
                <div class="row g-6 mb-6">
                    @php
                        $summaryStats = [
                            ['key' => 'total_items', 'color' => 'primary', 'icon' => 'ki-box', 'label' => 'Total Dead Items', 'value' => number_format($summary['total_items'])],
                            ['key' => 'total_quantity', 'color' => 'danger', 'icon' => 'ki-barcode', 'label' => 'Total Dead Quantity', 'value' => number_format($summary['total_quantity'])],
                            ['key' => 'total_value', 'color' => 'warning', 'icon' => 'ki-dollar', 'label' => 'Total Dead Value', 'value' => currency_symbol() . number_format($summary['total_value'], 2)],
                            ['key' => 'avg_days_idle', 'color' => 'info', 'icon' => 'ki-clock', 'label' => 'Avg Days Idle', 'value' => number_format($summary['avg_days_idle'], 0)],
                            ['key' => 'expired_items', 'color' => 'dark', 'icon' => 'ki-cross', 'label' => 'Expired Items', 'value' => number_format($summary['expired_items'])],
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
                @endif

                {{-- Charts Section --}}
                @if($deadStockItems->count() > 0)
                <div class="row g-6 mb-6">
                    {{-- Idle Time Distribution --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.idle_time_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="idleTimeChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Dead Stock by Department --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.dead_stock_by_department') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="departmentChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Days Idle Analysis --}}
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.days_idle_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="daysIdleChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Dead Stock Items Table --}}
                @if($deadStockItems->count() > 0)
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.dead_stock_items') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">
                                {{ $deadStockItems->count() }} {{ __('pagination.items') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="deadStockTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4" style="width: 10%;">{{ __('pagination.sku') }}</th>
                                        <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                        <th style="width: 10%;">{{ __('pagination.department') }}</th>
                                        <th style="width: 10%;">{{ __('pagination.location') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.quantity') }}</th>
                                        <th style="width: 12%;">{{ __('pagination.last_movement_date') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.days_idle') }}</th>
                                        <th style="width: 10%;">{{ __('pagination.expiry_date') }}</th>
                                        <th style="width: 10%;" class="text-end">{{ __('pagination.inventory_value') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('pagination.total_movement') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($deadStockItems as $item)
                                    @php
                                        $daysIdle = $item->days_idle;
                                        $isExpired = $item->is_expired;
                                        
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
                                            <span class="fw-bold {{ $isExpired ? 'text-danger' : 'text-dark' }}">
                                                {{ number_format($item->quantity_on_hand) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-muted fs-7">{{ \Carbon\Carbon::parse($item->last_movement_date)->format('Y-m-d') }}</div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $idleColor }}">
                                                {{ $daysIdle }} {{ __('pagination.days') }}
                                                <small class="d-block">{{ $idleText }}</small>
                                            </span>
                                        </td>
                                        <td>
                                            @if($isExpired)
                                                <span class="badge badge-light-danger">{{ __('pagination.expired') }}</span>
                                            @elseif($item->expiry_date)
                                                <span class="badge badge-light-success">{{ \Carbon\Carbon::parse($item->expiry_date)->format('Y-m-d') }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold {{ $isExpired ? 'text-danger' : 'text-dark' }}">
                                                {{ currency_symbol() }}{{ number_format($item->inventory_value, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-dark">
                                                {{ number_format($item->total_movement) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @php
                                    $pageTotalValue = $deadStockItems->sum('inventory_value');
                                @endphp
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="8" class="text-end fw-bold">{{ __('pagination.page_total') }}: </td>
                                        <td class="text-end fw-bold text-danger">
                                            {{ currency_symbol() }}{{ number_format($pageTotalValue, 2) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                    @if($deadStockItems->total() > $deadStockItems->count())
                                    <tr>
                                        <td colspan="8" class="text-end fw-bold text-muted">{{ __('pagination.grand_total') }}: </td>
                                        <td class="text-end fw-bold text-danger">
                                            {{ currency_symbol() }}{{ number_format($summary['total_value'], 2) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pagination --}}
                    @if($deadStockItems->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $deadStockItems,
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
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_dead_stock_found') }}</h4>
                            <p class="text-muted fs-6">{{ __('pagination.all_items_have_recent_movement') }}</p>
                            @if(request()->hasAny(['days_threshold', 'location_id', 'department_id', 'variant_id', 'include_expired']))
                            <a href="{{ route('reports.inventory.dead-stock') }}" class="btn btn-light-primary mt-3">
                                <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                {{ __('pagination.clear_filters') }}
                            </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Metadata Footer --}}
                @if($deadStockItems->count() > 0)
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
                        | {{ $deadStockItems->total() }} {{ __('pagination.items') }}
                        | {{ currency_symbol() }}{{ number_format($summary['total_value'], 2) }} {{ __('pagination.value_at_risk') }}
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
@if($deadStockItems->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // 1. Idle Time Distribution Chart
    // ============================================
    @php
        $idleData = [
            $idleCategories['180_365'] ?? 0,
            $idleCategories['365_730'] ?? 0,
            $idleCategories['over_730'] ?? 0
        ];
    @endphp
    
    const idleData = @json($idleData);
    
    if (idleData.some(v => v > 0)) {
        new ApexCharts(document.querySelector("#idleTimeChart"), {
            series: idleData,
            chart: {
                type: 'donut',
                height: 320,
                toolbar: { show: true }
            },
            labels: [
                '{{ __("pagination.180_365_days") }}',
                '{{ __("pagination.365_730_days") }}',
                '{{ __("pagination.over_730_days") }}'
            ],
            colors: ['#FFC700', '#F1416C', '#7E8299'],
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
                                label: '{{ __("pagination.total_units") }}',
                                formatter: function() {
                                    return idleData.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        const total = idleData.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                        return val.toLocaleString() + ' units (' + percentage + '%)';
                    }
                }
            }
        }).render();
    }
    
    // ============================================
    // 2. Dead Stock by Department Chart
    // ============================================
    @php
        $deptData = $departmentDeadStock->pluck('quantity')->toArray();
        $deptNames = $departmentDeadStock->pluck('name')->toArray();
    @endphp
    
    const deptData = @json($deptData);
    const deptNames = @json($deptNames);
    
    if (deptData.length > 0 && deptData.some(v => v > 0)) {
        new ApexCharts(document.querySelector("#departmentChart"), {
            series: [{
                name: '{{ __("pagination.dead_stock_quantity") }}',
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
                    return val.toLocaleString();
                },
                style: { fontSize: '10px' }
            },
            xaxis: {
                categories: deptNames,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: '{{ __("pagination.department") }}' }
            },
            colors: ['#F1416C'],
            tooltip: {
                y: {
                    formatter: function(val, { dataPointIndex }) {
                        const item = @json($departmentDeadStock->toArray());
                        return val.toLocaleString() + ' units' + 
                               (item[dataPointIndex] ? '\nValue: {{ currency_symbol() }}' + item[dataPointIndex].value.toFixed(2) : '');
                    }
                }
            }
        }).render();
    }
    
    // ============================================
    // 3. Days Idle Analysis Chart
    // ============================================
    @php
        $topIdle = $deadStockItems->take(15);
        $topIdleDays = $topIdle->pluck('days_idle')->toArray();
        $topIdleNames = $topIdle->map(function($item) {
            $name = $item->variant_name;
            return strlen($name) > 25 ? substr($name, 0, 22) . '...' : $name;
        })->toArray();
    @endphp
    
    const idleDays = @json($topIdleDays);
    const idleNames = @json($topIdleNames);
    
    if (idleDays.length > 0 && idleDays.some(v => v > 0)) {
        new ApexCharts(document.querySelector("#daysIdleChart"), {
            series: [{
                name: '{{ __("pagination.days_idle") }}',
                data: idleDays
            }],
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: true }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '70%',
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(0) + ' {{ __("pagination.days") }}';
                },
                style: { fontSize: '10px' }
            },
            xaxis: {
                categories: idleNames,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: '{{ __("pagination.product") }}' }
            },
            colors: ['#7239EA'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(0) + ' {{ __("pagination.days") }} idle';
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
            const daysThreshold = document.querySelector('[name="days_threshold"]');
            if (daysThreshold) {
                const value = parseInt(daysThreshold.value);
                if (value < 30 || value > 1095) {
                    e.preventDefault();
                    const errorMsg = '{{ __("pagination.days_threshold_must_be_between_30_and_1095") }}';
                    if (typeof toastr !== 'undefined') {
                        toastr.error(errorMsg);
                    } else {
                        alert(errorMsg);
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
        XLSX.utils.book_append_sheet(wb, ws, 'Dead Stock');
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
        #idleTimeChart,
        #departmentChart,
        #daysIdleChart {
            height: 250px !important;
        }
    }
`;
document.head.appendChild(style);
</script>
@endpush

@endsection