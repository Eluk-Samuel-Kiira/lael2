{{-- resources/views/reports/inventory/abc-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.abc_analysis'))

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
                                {{ __('pagination.abc_analysis') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.abc_analysis') }}</li>
                            </ul>
                        </div>
                        @if($totalValue > 0)
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'abcAnalysisTable', filename: 'abc_analysis_{{ date('Y_m_d') }}', sheetName: 'ABC Analysis'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'abcAnalysisTable', filename: 'abc_analysis_{{ date('Y_m_d') }}', format: 'csv'})">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('pagination.export_to_csv') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
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
                        <form method="GET" action="{{ route('reports.inventory.abc-analysis') }}" id="filterForm">
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

                            {{-- Second Line - Action Buttons --}}
                            <div class="row">
                                <div class="col-12 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="applyFilters">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('pagination.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.inventory.abc-analysis') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('pagination.clear_filters') }}
                                    </a>
                                    @if($totalValue > 0)
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('pagination.total_items') }}: <strong>{{ $sortedItemsList->count() }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ABC Analysis Summary --}}
                @if($totalValue > 0)
                <div class="row g-6 mb-6">
                    @foreach(['A' => 'danger', 'B' => 'warning', 'C' => 'success'] as $category => $color)
                    @php
                        $categoryData = $abcCategories[$category];
                        $iconMap = ['A' => 'ki-star', 'B' => 'ki-medal-star', 'C' => 'ki-moon'];
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-flush bg-light-{{ $color }} border border-{{ $color }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-3">
                                    <i class="ki-duotone {{ $iconMap[$category] }} fs-2tx text-{{ $color }}"></i>
                                </div>
                                <div class="mb-1">
                                    <span class="fs-2 fw-bold text-{{ $color }}">
                                        {{ __('pagination.abc_category_' . strtolower($category)) }}
                                    </span>
                                </div>
                                <div class="mb-2">
                                    <span class="fs-3 fw-bold">
                                        {{ currency_symbol() }}{{ number_format($categoryData['value'], 2) }}
                                    </span>
                                </div>
                                <div class="text-gray-600 fw-semibold fs-7">
                                    {{ $categoryData['count'] }} {{ __('pagination.items') }} • 
                                    {{ number_format($categoryData['percentage'], 1) }}% {{ __('pagination.of_total') }}
                                </div>
                                <div class="mt-3">
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-{{ $color }}" 
                                             style="width: {{ $categoryData['percentage'] }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Charts Section --}}
                @if($totalValue > 0)
                <div class="row g-6 mb-6">
                    {{-- ABC Value Distribution --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.abc_value_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="abcDistributionChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- ABC Item Count Distribution --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.abc_item_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="abcItemChart" style="height: 320px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Pareto Chart --}}
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.pareto_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="paretoChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ABC Analysis Table --}}
                @if($totalValue > 0)
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.abc_analysis_details') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">
                                {{ $paginatedItems->count() }} {{ __('pagination.items') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="abcAnalysisTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4" style="width: 5%;">#</th>
                                        <th style="width: 10%;" class="text-center">{{ __('pagination.abc_category') }}</th>
                                        <th style="width: 12%;">{{ __('pagination.sku') }}</th>
                                        <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                        <th style="width: 12%;">{{ __('pagination.department') }}</th>
                                        <th style="width: 12%;" class="text-end">{{ __('pagination.inventory_value') }}</th>
                                        <th style="width: 10%;" class="text-center">{{ __('pagination.value_percentage') }}</th>
                                        <th style="width: 10%;" class="text-center">{{ __('pagination.cumulative_percentage') }}</th>
                                        <th style="width: 11%;">{{ __('pagination.recommendation') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paginatedItems as $index => $item)
                                    @php
                                        $categoryColors = ['A' => 'danger', 'B' => 'warning', 'C' => 'success'];
                                        $categoryColor = $categoryColors[$item->abc_category] ?? 'dark';
                                        
                                        $recommendations = [
                                            'A' => __('pagination.abc_recommendation_a'),
                                            'B' => __('pagination.abc_recommendation_b'),
                                            'C' => __('pagination.abc_recommendation_c')
                                        ];
                                        $recommendation = $recommendations[$item->abc_category] ?? '';
                                        
                                        $globalIndex = $sortedItemsList->search(function($i) use ($item) {
                                            return $i->id === $item->id;
                                        });
                                        $displayIndex = $globalIndex !== false ? $globalIndex + 1 : ($index + 1);
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold">{{ $displayIndex }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $categoryColor }} fs-7 py-2 px-3">
                                                {{ __('pagination.abc_category_' . strtolower($item->abc_category)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">{{ $item->variant->sku ?? '-' }}</div>
                                            <small class="text-muted">{{ $item->variant->barcode ?? '-' }}</small>
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
                                        <td class="text-end">
                                            <span class="fw-bold text-{{ $categoryColor }}">
                                                {{ currency_symbol() }}{{ number_format($item->inventory_value, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-dark">
                                                {{ number_format($item->value_percentage, 1) }}%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="fw-bold {{ $item->cumulative_percentage <= 80 ? 'text-success' : ($item->cumulative_percentage <= 95 ? 'text-warning' : 'text-info') }}">
                                                {{ number_format($item->cumulative_percentage, 1) }}%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted fs-7">{{ $recommendation }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">{{ __('pagination.page_total') }}: </td>
                                        <td class="text-end fw-bold text-primary">
                                            {{ currency_symbol() }}{{ number_format($paginatedItems->sum('inventory_value'), 2) }}
                                        </td>
                                        <td colspan="3"></td>
                                    </tr>
                                    @if($sortedItemsList->count() > $paginatedItems->count())
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold text-muted">{{ __('pagination.grand_total') }}: </td>
                                        <td class="text-end fw-bold text-primary">
                                            {{ currency_symbol() }}{{ number_format($sortedItemsList->sum('inventory_value'), 2) }}
                                        </td>
                                        <td colspan="3"></td>
                                    </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pagination --}}
                    @if($paginatedItems->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $paginatedItems,
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
                            <p class="text-muted fs-6">{{ __('pagination.no_inventory_items_for_abc_analysis') }}</p>
                            @if(request()->hasAny(['start_date', 'end_date', 'department_id', 'location_id', 'variant_id']))
                            <a href="{{ route('reports.inventory.abc-analysis') }}" class="btn btn-light-primary mt-3">
                                <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                {{ __('pagination.clear_filters') }}
                            </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- Metadata Footer --}}
                @if($totalValue > 0)
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
                        | {{ $sortedItemsList->count() }} {{ __('pagination.items') }}
                        | {{ __('pagination.total_value') }}: {{ currency_symbol() }}{{ number_format($totalValue, 2) }}
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
@if($totalValue > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // 1. ABC Value Distribution Chart
    // ============================================
    @php
        $abcValueData = [
            $abcCategories['A']['value'] ?? 0,
            $abcCategories['B']['value'] ?? 0,
            $abcCategories['C']['value'] ?? 0
        ];
    @endphp
    
    const abcValueData = @json($abcValueData);
    
    new ApexCharts(document.querySelector("#abcDistributionChart"), {
        series: abcValueData,
        chart: {
            type: 'donut',
            height: 320,
            toolbar: { show: true }
        },
        labels: [
            '{{ __("pagination.abc_category_a") }} ({{ $abcCategories["A"]["count"] ?? 0 }} {{ __("pagination.items") }})',
            '{{ __("pagination.abc_category_b") }} ({{ $abcCategories["B"]["count"] ?? 0 }} {{ __("pagination.items") }})',
            '{{ __("pagination.abc_category_c") }} ({{ $abcCategories["C"]["count"] ?? 0 }} {{ __("pagination.items") }})'
        ],
        colors: ['#F1416C', '#FFC700', '#50CD89'],
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
                            label: '{{ __("pagination.total_value") }}',
                            formatter: function() {
                                return '{{ currency_symbol() }}{{ number_format($totalValue, 2) }}';
                            }
                        }
                    }
                }
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return '{{ currency_symbol() }}' + val.toFixed(2);
                }
            }
        }
    }).render();
    
    // ============================================
    // 2. ABC Item Count Distribution Chart
    // ============================================
    @php
        $abcCountData = [
            $abcCategories['A']['count'] ?? 0,
            $abcCategories['B']['count'] ?? 0,
            $abcCategories['C']['count'] ?? 0
        ];
    @endphp
    
    const abcCountData = @json($abcCountData);
    
    new ApexCharts(document.querySelector("#abcItemChart"), {
        series: [{
            name: '{{ __("pagination.items") }}',
            data: abcCountData
        }],
        chart: {
            type: 'bar',
            height: 320,
            toolbar: { show: true }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '60%',
                borderRadius: 4
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function(val) {
                return val + ' {{ __("pagination.items") }}';
            },
            style: { fontSize: '11px', fontWeight: 'bold' }
        },
        xaxis: {
            categories: [
                '{{ __("pagination.abc_category_a") }}',
                '{{ __("pagination.abc_category_b") }}',
                '{{ __("pagination.abc_category_c") }}'
            ],
            labels: { style: { fontSize: '12px' } }
        },
        yaxis: {
            title: {
                text: '{{ __("pagination.number_of_items") }}',
                style: { fontSize: '12px', fontWeight: 'bold' }
            }
        },
        colors: ['#3E97FF'],
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + ' {{ __("pagination.items") }}';
                }
            }
        }
    }).render();
    
    // ============================================
    // 3. Pareto Chart
    // ============================================
    @php
        $paretoItems = $sortedItemsList->take(20);
        $paretoValues = $paretoItems->pluck('inventory_value')->toArray();
        $paretoCumulative = [];
        $cumulativeSum = 0;
        foreach ($paretoItems as $item) {
            $cumulativeSum += $item->inventory_value;
            $paretoCumulative[] = $totalValue > 0 ? ($cumulativeSum / $totalValue) * 100 : 0;
        }
        $paretoLabels = $paretoItems->pluck('variant.name')->map(function($name) {
            return Str::limit($name, 20);
        })->toArray();
    @endphp
    
    const paretoValues = @json($paretoValues);
    const paretoCumulative = @json($paretoCumulative);
    const paretoLabels = @json($paretoLabels);
    
    new ApexCharts(document.querySelector("#paretoChart"), {
        series: [{
            name: '{{ __("pagination.inventory_value") }}',
            type: 'column',
            data: paretoValues
        }, {
            name: '{{ __("pagination.cumulative_percentage") }}',
            type: 'line',
            data: paretoCumulative
        }],
        chart: {
            height: 350,
            type: 'line',
            toolbar: { show: true },
            zoom: { enabled: true }
        },
        stroke: {
            width: [0, 3],
            curve: 'smooth'
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: paretoLabels,
            labels: {
                rotate: -45,
                style: { fontSize: '10px' },
                trim: true
            },
            title: {
                text: '{{ __("pagination.items_sorted_by_value") }}',
                style: { fontSize: '12px', fontWeight: 'bold' }
            }
        },
        yaxis: [{
            title: {
                text: '{{ __("pagination.value") }}',
                style: { fontSize: '12px', fontWeight: 'bold' }
            },
            labels: {
                formatter: function(val) {
                    return '{{ currency_symbol() }}' + val.toFixed(0);
                }
            }
        }, {
            opposite: true,
            title: {
                text: '{{ __("pagination.cumulative_percentage") }}',
                style: { fontSize: '12px', fontWeight: 'bold' }
            },
            max: 100,
            labels: {
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                }
            }
        }],
        colors: ['#3E97FF', '#F1416C'],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(val, { seriesIndex }) {
                    if (seriesIndex === 0) {
                        return '{{ currency_symbol() }}' + val.toFixed(2);
                    }
                    return val.toFixed(1) + '%';
                }
            }
        },
        grid: {
            borderColor: '#e7e7e7',
            row: {
                colors: ['#f3f3f3', 'transparent'],
                opacity: 0.3
            }
        }
    }).render();
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
function exportCurrentPage(options) {
    const { tableId, filename, format = 'excel' } = options;
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
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [];
            const cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                let text = cols[j].innerText
                    .replace(/(\r\n|\n|\r)/gm, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
                
                if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                    text = '"' + text.replace(/"/g, '""') + '"';
                }
                row.push(text);
            }
            csv.push(row.join(','));
        }
        
        const csvContent = '\uFEFF' + csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.href = url;
        link.setAttribute('download', `${filename}.${format === 'excel' ? 'csv' : format}`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        
        if (typeof toastr !== 'undefined') {
            toastr.success('{{ __("pagination.export_successful") }}');
        }
    } catch (error) {
        console.error('Export error:', error);
        if (typeof toastr !== 'undefined') {
            toastr.error('{{ __("pagination.export_failed") }}');
        } else {
            alert('{{ __("pagination.export_failed") }}');
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
        #abcDistributionChart,
        #abcItemChart,
        #paretoChart {
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