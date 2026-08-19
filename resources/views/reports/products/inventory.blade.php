@extends('layouts.app')

@section('title', __('auth.inventory_valuation'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                
                {{-- ============================================================ --}}
                {{-- TOOLBAR --}}
                {{-- ============================================================ --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('auth.inventory_valuation') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('auth._dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.inventory_valuation') }}</li>
                            </ul>
                        </div>
                        @if($variants->count() > 0)
                        <div class="dropdown">
                            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" onclick="exportTable('inventoryTable', 'inventory_valuation')">
                                        <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                        {{ __('accounting.export_to_excel') }}
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" onclick="exportTableCSV('inventoryTable', 'inventory_valuation')">
                                        <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                        {{ __('accounting.export_to_csv') }}
                                    </a>
                                </li>
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- FILTERS --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form method="GET" action="{{ route('reports.products.inventory') }}" id="filterForm">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                    <select class="form-select" name="category_id">
                                        <option value="">{{ __('accounting.all_categories') }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                    {{ $categoryId == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('auth.stock_status') }}</label>
                                    <select class="form-select" name="stock_status">
                                        <option value="">{{ __('accounting.all_statuses') }}</option>
                                        <option value="low" {{ $stockStatus == 'low' ? 'selected' : '' }}>{{ __('auth.low_stock') }}</option>
                                        <option value="out" {{ $stockStatus == 'out' ? 'selected' : '' }}>{{ __('auth.out_of_stock') }}</option>
                                        <option value="overstock" {{ $stockStatus == 'overstock' ? 'selected' : '' }}>{{ __('auth.overstock') }}</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-5 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.products.inventory') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- SUMMARY CARDS --}}
                {{-- ============================================================ --}}
                @if($variants->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-abstract-44 fs-2tx text-primary mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800">{{ number_format($totalValuation['total_items']) }}</div>
                                <div class="text-muted">{{ __('auth.total_items') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-success border border-success border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-inbox fs-2tx text-success mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800">{{ number_format($totalValuation['total_quantity']) }}</div>
                                <div class="text-muted">{{ __('auth.total_quantity') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-money fs-2tx text-warning mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800">{{ currency_symbol() }}{{ number_format($totalValuation['total_cost_value'], 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_cost_value') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-info border border-info border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-dollar fs-2tx text-info mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800">{{ currency_symbol() }}{{ number_format($totalValuation['total_revenue_value'], 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_revenue_value') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-danger border border-danger border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-growth fs-2tx text-danger mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800">{{ currency_symbol() }}{{ number_format($totalValuation['total_potential_profit'], 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_potential_profit') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-secondary border border-secondary border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-chart-line fs-2tx text-secondary mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800">{{ number_format($totalValuation['avg_margin'] ?? 0, 1) }}%</div>
                                <div class="text-muted">{{ __('auth.avg_margin') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- CHARTS --}}
                {{-- ============================================================ --}}
                @if($totalValuation['total_items'] > 0)
                <div class="row g-6 mb-6">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i>
                                    {{ __('auth.stock_health_distribution') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div id="stockHealthChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i>
                                    {{ __('auth.value_by_stock_status') }}
                                </h3>
                            </div>
                            <div class="card-body">
                                <div id="valueDistributionChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- TABLE --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.inventory_valuation') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">
                                {{ __('accounting.showing') }} {{ $variants->count() }} {{ __('auth.items') }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="inventoryTable">
                                <thead class="bg-light">
                                    <tr class="fw-bold fs-6 text-gray-800">
                                        <th class="ps-4 min-w-100px">{{ __('auth.sku') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.name') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-center">{{ __('auth.quantity') }}</th>
                                        <th class="min-w-120px text-end">{{ __('auth.selling_price') }}</th>
                                        <th class="min-w-120px text-end">{{ __('auth.cost_price') }}</th>
                                        <th class="min-w-120px text-end">{{ __('auth.potential_profit') }}</th>
                                        <th class="min-w-100px text-center">{{ __('auth.margin_percentage') }}</th>
                                        <th class="min-w-120px text-center">{{ __('auth.stock_health') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($variants as $variant)
                                    @php
                                        $costPrice = (float)($variant->grand_total_cost_price ?? 0);
                                        $sellingPrice = (float)($variant->selling_price ?? 0);
                                        $quantity = (float)($variant->overal_quantity_at_hand ?? 0);
                                        $costValue = $costPrice * $quantity;
                                        $revenueValue = $sellingPrice * $quantity;
                                        $potentialProfit = $revenueValue - $costValue;
                                        $marginPercentage = $sellingPrice > 0 ? (($sellingPrice - $costPrice) / $sellingPrice) * 100 : 0;
                                        
                                        if ($quantity == 0) {
                                            $stockHealth = 'out_of_stock';
                                            $stockColor = 'danger';
                                            $stockStatusLabel = __('auth.out_of_stock');
                                        } elseif ($quantity < 10) {
                                            $stockHealth = 'low_stock';
                                            $stockColor = 'warning';
                                            $stockStatusLabel = __('auth.low_stock');
                                        } elseif ($quantity > 100) {
                                            $stockHealth = 'overstock';
                                            $stockColor = 'info';
                                            $stockStatusLabel = __('auth.overstock');
                                        } else {
                                            $stockHealth = 'in_stock';
                                            $stockColor = 'success';
                                            $stockStatusLabel = __('auth.in_stock');
                                        }
                                    @endphp
                                    <tr>
                                        <td class="ps-4 fw-semibold">{{ $variant->sku }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($variant->product?->image_url)
                                                <div class="symbol symbol-40px me-3">
                                                    <img src="{{ asset($variant->product->image_url) }}" 
                                                         alt="{{ $variant->name }}" class="rounded">
                                                </div>
                                                @endif
                                                <div>
                                                    <div class="fw-bold text-gray-800">{{ $variant->name }}</div>
                                                    @if($variant->product && $variant->product->name != $variant->name)
                                                    <div class="text-muted fs-7">{{ $variant->product->name }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($variant->product?->category)
                                            <span class="badge badge-light-info">{{ $variant->product->category->name }}</span>
                                            @else
                                            <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $stockColor }}">
                                                {{ number_format($quantity) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-semibold text-gray-800">
                                                {{ currency_symbol() }}{{ number_format($sellingPrice, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-gray-600">
                                                {{ currency_symbol() }}{{ number_format($costPrice, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-{{ $potentialProfit >= 0 ? 'success' : 'danger' }}">
                                                {{ currency_symbol() }}{{ number_format($potentialProfit, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $marginPercentage >= 30 ? 'success' : ($marginPercentage >= 10 ? 'warning' : 'danger') }}">
                                                {{ number_format($marginPercentage, 1) }}%
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $stockColor }}">
                                                {{ $stockStatusLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            {{ __('accounting.no_data_available') }}
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                @if($variants->count() > 0)
                                <tfoot class="bg-light">
                                    <tr>
                                        <td colspan="6" class="text-end fw-bold">{{ __('auth.total') }}:</td>
                                        <td class="fw-bold text-{{ $totalValuation['total_potential_profit'] >= 0 ? 'success' : 'danger' }}">
                                            {{ currency_symbol() }}{{ number_format($totalValuation['total_potential_profit'], 2) }}
                                        </td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                    
                    @if($variants->count() > 0)
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $variants,
                            'pageName' => 'page',
                            'perPageName' => 'per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif
                </div>
                @else
                {{-- No Data --}}
                <div class="card">
                    <div class="card-body text-center py-10">
                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted">{{ __('auth.no_products_found') }}</p>
                        @if(request()->hasAny(['category_id', 'stock_status']))
                        <a href="{{ route('reports.products.inventory') }}" class="btn btn-light-primary mt-3">
                            <i class="ki-duotone ki-cross fs-2 me-2"></i> {{ __('accounting.clear_filters_view_all') }}
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- FOOTER --}}
                {{-- ============================================================ --}}
                <div class="mt-6 text-muted text-center fs-7">
                    <hr>
                    <p>
                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                        {{ __('accounting.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }}
                        | {{ __('auth.total_items') }}: {{ method_exists($variants, 'total') ? $variants->total() : $variants->count() }}
                        | {{ __('auth.total_quantity') }}: {{ number_format($totalValuation['total_quantity'] ?? 0) }}
                        | {{ __('auth.total_cost_value') }}: {{ currency_symbol() }}{{ number_format($totalValuation['total_cost_value'] ?? 0, 2) }}
                    </p>
                </div>
                
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($variants->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @php
        $healthyCount = $totalValuation['healthy_count'] ?? 0;
        $warningCount = $totalValuation['warning_count'] ?? 0;
        $criticalCount = $totalValuation['critical_count'] ?? 0;
        $healthyValue = $totalValuation['healthy_value'] ?? 0;
        $lowStockValue = $totalValuation['low_stock_value'] ?? 0;
        $overstockValue = $totalValuation['overstock_value'] ?? 0;
    @endphp
    
    // Stock Health Distribution Chart
    new ApexCharts(document.querySelector("#stockHealthChart"), {
        series: [{{ $healthyCount }}, {{ $warningCount }}, {{ $criticalCount }}],
        chart: { type: 'pie', height: 300, toolbar: { show: true } },
        labels: ['{{ __("auth.healthy") }}', '{{ __("auth.warning") }}', '{{ __("auth.critical") }}'],
        colors: ['#50CD89', '#FFC700', '#F1416C'],
        legend: { position: 'bottom' },
        tooltip: { y: { formatter: function(v) { return v + ' {{ __("auth.items") }}'; } } },
        dataLabels: { enabled: true, formatter: function(val, opts) { 
            return opts.w.config.series[opts.seriesIndex]; 
        } }
    }).render();
    
    // Value Distribution Chart
    new ApexCharts(document.querySelector("#valueDistributionChart"), {
        series: [{ 
            name: '{{ __("auth.stock_value") }}', 
            data: [{{ $healthyValue }}, {{ $lowStockValue }}, 0, {{ $overstockValue }}]
        }],
        chart: { type: 'bar', height: 300, toolbar: { show: true } },
        plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '55%' } },
        xaxis: {
            categories: [
                '{{ __("auth.healthy_stock") }}',
                '{{ __("auth.low_stock") }}',
                '{{ __("auth.out_of_stock") }}',
                '{{ __("auth.overstock") }}'
            ]
        },
        yaxis: {
            labels: { formatter: function(v) { return '{{ currency_symbol() }}' + v.toLocaleString(); } }
        },
        colors: ['#3E97FF'],
        tooltip: {
            y: { formatter: function(v) { return '{{ currency_symbol() }}' + v.toLocaleString(undefined, {minimumFractionDigits: 2}); } }
        }
    }).render();
});

function exportTable(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) { alert('{{ __("accounting.table_not_found") }}'); return; }
    try {
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, XLSX.utils.table_to_sheet(table), 'Inventory Valuation');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch(e) {
        alert('{{ __("accounting.export_error") }}: ' + e.message);
        exportTableCSV(tableId, filename);
    }
}

function exportTableCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) { alert('{{ __("accounting.table_not_found") }}'); return; }
    try {
        const rows = table.querySelectorAll('tr');
        let csv = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowData = Array.from(cols).map(c => {
                let text = c.innerText.trim();
                if (text.includes(',') || text.includes('"')) {
                    text = '"' + text.replace(/"/g, '""') + '"';
                }
                return text;
            });
            csv.push(rowData.join(','));
        });
        const blob = new Blob(["\uFEFF" + csv.join('\n')], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename + '.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    } catch(e) {
        alert('{{ __("accounting.export_error") }}: ' + e.message);
    }
}
</script>
@endif
@endpush
@endsection