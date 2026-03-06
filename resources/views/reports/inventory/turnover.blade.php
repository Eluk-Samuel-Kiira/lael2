{{-- resources/views/reports/inventory/turnover.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_turnover'))

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
                                {{ __('pagination.inventory_turnover') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_turnover') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($turnoverData->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'turnoverTable', filename: 'inventory_turnover_{{ date('Y_m_d') }}', sheetName: 'Inventory Turnover'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'turnoverTable', filename: 'inventory_turnover_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.inventory.turnover') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap">
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
                                                        title="{{ __('pagination.start_date') }}">
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-2">{{ __('accounting.to') }}</span>
                                                <span class="d-flex d-sm-none text-gray-500 text-center">{{ __('accounting.to') }}</span>
                                                <div class="input-group w-100">
                                                    <span class="input-group-text bg-light">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control" name="end_date" 
                                                        value="{{ $endDate }}" required
                                                        title="{{ __('pagination.end_date') }}">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Product Variant --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-abstract-42 fs-2"></i>
                                                </span>
                                                <select class="form-select" name="variant_id" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
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
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.inventory.turnover') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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
                @if($turnoverData->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.turnover_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @php
                                    $avgTurnoverRate = $turnoverData->avg('turnover_rate');
                                    $avgDaysHeld = $turnoverData->avg('days_inventory_held');
                                    $totalMovement = $turnoverData->sum('total_movement');
                                    $totalTransactions = $turnoverData->sum('transaction_count');
                                    $fastMoving = $turnoverData->where('turnover_rate', '>=', 10)->count();
                                    $slowMoving = $turnoverData->whereBetween('turnover_rate', [1, 9.99])->count();
                                    $nonMoving = $turnoverData->where('turnover_rate', '<', 1)->count();
                                @endphp
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'avg_turnover_rate', 'color' => 'primary', 'icon' => 'ki-repeat', 'label' => 'avg_turnover_rate', 'value' => number_format($avgTurnoverRate, 2)],
                                        ['key' => 'avg_days_held', 'color' => 'success', 'icon' => 'ki-clock', 'label' => 'avg_days_held', 'value' => number_format($avgDaysHeld, 1)],
                                        ['key' => 'total_movement', 'color' => 'info', 'icon' => 'ki-switch', 'label' => 'total_movement', 'value' => number_format($totalMovement)],
                                        ['key' => 'total_transactions', 'color' => 'warning', 'icon' => 'ki-exchange', 'label' => 'total_transactions', 'value' => number_format($totalTransactions)],
                                        ['key' => 'fast_moving', 'color' => 'danger', 'icon' => 'ki-rocket', 'label' => 'fast_moving_items', 'value' => $fastMoving],
                                        ['key' => 'slow_moving', 'color' => 'secondary', 'icon' => 'ki-speedometer', 'label' => 'slow_moving_items', 'value' => $slowMoving],
                                        ['key' => 'non_moving', 'color' => 'dark', 'icon' => 'ki-pause', 'label' => 'non_moving_items', 'value' => $nonMoving],
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
                @if($turnoverData->count() > 0)
                <div class="row mb-6">
                    {{-- Turnover Rate Distribution --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.turnover_rate_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="turnoverChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Movement Category Distribution --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.movement_category_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="movementCategoryChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Turnover Data Table --}}
                @if($turnoverData->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.inventory_turnover_analysis') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $turnoverData->count() }} {{ __('pagination.of') }} {{ $turnoverData->total() }} {{ __('pagination.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="turnoverTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('pagination.sku') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.total_movement') }}</th>
                                                <th>{{ __('pagination.transaction_count') }}</th>
                                                <th>{{ __('pagination.avg_stock_level') }}</th>
                                                <th>{{ __('pagination.turnover_rate') }}</th>
                                                <th>{{ __('pagination.days_inventory_held') }}</th>
                                                <th>{{ __('pagination.movement_category') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($turnoverData as $item)
                                            @php
                                                // Movement category logic
                                                if ($item->turnover_rate >= 10) {
                                                    $categoryColor = 'danger';
                                                    $categoryText = __('pagination.fast_moving');
                                                } elseif ($item->turnover_rate >= 1) {
                                                    $categoryColor = 'warning';
                                                    $categoryText = __('pagination.slow_moving');
                                                } else {
                                                    $categoryColor = 'dark';
                                                    $categoryText = __('pagination.non_moving');
                                                }
                                                
                                                // Turnover rate color logic
                                                $turnoverColor = 'success';
                                                if ($item->turnover_rate < 1) {
                                                    $turnoverColor = 'danger';
                                                } elseif ($item->turnover_rate < 5) {
                                                    $turnoverColor = 'warning';
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-semibold">{{ $item->variant->sku ?? '-' }}</div>
                                                    <small class="text-muted">{{ $item->variant->barcode ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if(isset($item->variant->image_url) && $item->variant->image_url)
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
                                                    <span class="fw-bold text-primary">
                                                        {{ number_format($item->total_movement) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $item->transaction_count }}</span>
                                                </td>
                                                <td>
                                                    {{ number_format($item->avg_stock_level, 1) }}
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $turnoverColor }}">
                                                        {{ number_format($item->turnover_rate, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $item->days_inventory_held > 90 ? 'text-danger' : 'text-success' }}">
                                                        {{ number_format($item->days_inventory_held, 1) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $categoryColor }}">
                                                        {{ $categoryText }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($turnoverData->hasPages())
                                <div class="card-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $turnoverData->firstItem() }} - {{ $turnoverData->lastItem() }} {{ __('pagination.of') }} {{ $turnoverData->total() }}
                                        </div>
                                        <div>
                                            {{ $turnoverData->links() }}
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
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.no_turnover_data_found') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'variant_id']))
                                        <a href="{{ route('reports.inventory.turnover') }}" class="btn btn-light-primary">
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
@if($turnoverData->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Turnover Rate Chart
        const turnoverRates = @json($turnoverData->pluck('turnover_rate'));
        const productNames = @json($turnoverData->pluck('variant.name'));
        
        const turnoverChart = new ApexCharts(document.querySelector("#turnoverChart"), {
            series: [{
                name: 'Turnover Rate',
                data: turnoverRates
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
                categories: productNames,
                labels: {
                    rotate: -45,
                    trim: true
                }
            },
            yaxis: {
                title: {
                    text: 'Turnover Rate'
                }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val.toFixed(2)
                    }
                }
            }
        });
        turnoverChart.render();
        
        // Movement Category Chart
        const fastMoving = @json($turnoverData->where('turnover_rate', '>=', 10)->count());
        const slowMoving = @json($turnoverData->whereBetween('turnover_rate', [1, 9.99])->count());
        const nonMoving = @json($turnoverData->where('turnover_rate', '<', 1)->count());
        
        const movementCategoryChart = new ApexCharts(document.querySelector("#movementCategoryChart"), {
            series: [fastMoving, slowMoving, nonMoving],
            chart: {
                type: 'donut',
                height: 350
            },
            labels: [
                '{{ __("pagination.fast_moving") }}',
                '{{ __("pagination.slow_moving") }}',
                '{{ __("pagination.non_moving") }}'
            ],
            colors: ['#F1416C', '#FFC700', '#7E8299'],
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
        movementCategoryChart.render();
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
            alert('{{ __("pagination.start_date_cannot_be_after_end_date") }}');
            return false;
        }
    });
</script>
@endpush

@endsection