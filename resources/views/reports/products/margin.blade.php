@extends('layouts.app')

@section('title', __('auth.product_margin'))

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
                                {{ __('auth.product_margin') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_margin') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($variants->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'marginTable', filename: 'product_margin_{{ date('Y_m_d') }}', sheetName: 'Product Margin'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'marginTable', filename: 'product_margin_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.products.margin') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-6">
                                        {{-- Category --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-category fs-2"></i>
                                                </span>
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
                                        </div>
                                        
                                        {{-- Min Margin --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.min_margin') }} (%)</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-arrow-down-right fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="min_margin" 
                                                    value="{{ $minMargin }}" min="0" max="100" step="0.1"
                                                    placeholder="{{ __('auth.min_margin') }}">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                        
                                        {{-- Max Margin --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('auth.max_margin') }} (%)</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-arrow-up-right fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="max_margin" 
                                                    value="{{ $maxMargin }}" min="0" max="100" step="0.1"
                                                    placeholder="{{ __('auth.max_margin') }}">
                                                <span class="input-group-text">%</span>
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
                                                <a href="{{ route('reports.products.margin') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- Margin Summary --}}
                @if($variants->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.margin_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'total_variants', 'color' => 'primary', 'icon' => 'ki-abstract-44', 'label' => 'total_variants', 'value' => $marginSummary['total_variants']],
                                        ['key' => 'average_margin', 'color' => 'success', 'icon' => 'ki-percentage', 'label' => 'average_margin', 'value' => number_format($marginSummary['average_margin'] ?? 0, 1) . '%'],
                                        ['key' => 'total_margin_value', 'color' => 'info', 'icon' => 'ki-dollar', 'label' => 'total_margin_value', 'value' => '$' . number_format($marginSummary['total_margin_value'] ?? 0, 2)],
                                        ['key' => 'high_margin_count', 'color' => 'danger', 'icon' => 'ki-arrow-up', 'label' => 'high_margin_count', 'value' => $marginSummary['high_margin_count']],
                                        ['key' => 'medium_margin_count', 'color' => 'warning', 'icon' => 'ki-minus', 'label' => 'medium_margin_count', 'value' => $marginSummary['medium_margin_count']],
                                        ['key' => 'low_margin_count', 'color' => 'secondary', 'icon' => 'ki-arrow-down', 'label' => 'low_margin_count', 'value' => $marginSummary['low_margin_count']]
                                    ] as $stat)
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}">
                                                        @for($i = 1; $i <= 2; $i++)
                                                        <span class="path{{ $i }}"></span>
                                                        @endfor
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $stat['value'] }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.' . $stat['label']) }}
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

                {{-- Margin Distribution Charts --}}
                <div class="row mb-6">
                    {{-- Margin Category Distribution --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.margin_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="marginDistributionChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Top Margin Products --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_margin_products') }} (Top 10)</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="topMarginChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Margin Analysis Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.margin_analysis') }}</h3>
                                    </div>
                                    @if($variants->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $variants->count() }} {{ __('auth.items') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="marginTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('auth.sku') }}</th>
                                                <th>{{ __('accounting.name') }}</th>
                                                <th>{{ __('accounting.category') }}</th>
                                                <th>{{ __('auth.price') }}</th>
                                                <th>{{ __('auth.cost_price') }}</th>
                                                <th>{{ __('auth.margin_amount') }}</th>
                                                <th>{{ __('auth.margin_percentage') }}</th>
                                                <th>{{ __('auth.quantity') }}</th>
                                                <th>{{ __('auth.total_margin_value') }}</th>
                                                <th>{{ __('auth.margin_category') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($variants->sortByDesc('margin_percentage') as $variant)
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $variant->sku }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($variant->image_url)
                                                        <div class="symbol symbol-50px me-3">
                                                            <img src="{{ asset($variant->image_url) }}" alt="{{ $variant->name }}" class="rounded">
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <span class="fw-bold text-gray-800">{{ $variant->name }}</span>
                                                            @if($variant->product)
                                                            <div class="text-muted fs-7">{{ $variant->product->name }}</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($variant->product && $variant->product->category)
                                                    <span class="badge badge-light-info">{{ $variant->product->category->name }}</span>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-semibold">${{ number_format($variant->price, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-gray-600">${{ number_format($variant->cost_price ?? 0, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-{{ $variant->margin_amount >= 0 ? 'success' : 'danger' }} fw-bold">
                                                        ${{ number_format($variant->margin_amount, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($variant->margin_category == 'high')
                                                        <span class="badge badge-success">{{ number_format($variant->margin_percentage, 1) }}%</span>
                                                    @elseif($variant->margin_category == 'medium')
                                                        <span class="badge badge-primary">{{ number_format($variant->margin_percentage, 1) }}%</span>
                                                    @elseif($variant->margin_category == 'low')
                                                        <span class="badge badge-warning">{{ number_format($variant->margin_percentage, 1) }}%</span>
                                                    @else
                                                        <span class="badge badge-danger">{{ number_format($variant->margin_percentage, 1) }}%</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">{{ $variant->overal_quantity_at_hand }}</span>
                                                </td>
                                                <td>
                                                    @php
                                                        $total_margin_value = $variant->margin_amount * $variant->overal_quantity_at_hand;
                                                    @endphp
                                                    <span class="text-{{ $total_margin_value >= 0 ? 'success' : 'danger' }} fw-bold">
                                                        ${{ number_format($total_margin_value, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($variant->margin_category == 'high')
                                                        <span class="badge badge-success">{{ __('auth.high_margin') }}</span>
                                                    @elseif($variant->margin_category == 'medium')
                                                        <span class="badge badge-primary">{{ __('auth.medium_margin') }}</span>
                                                    @elseif($variant->margin_category == 'low')
                                                        <span class="badge badge-warning">{{ __('auth.low_margin') }}</span>
                                                    @else
                                                        <span class="badge badge-danger">{{ __('auth.very_low_margin') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        {{-- Footer with totals --}}
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="8" class="text-end fw-bold">{{ __('auth.total') }}:</td>
                                                <td class="fw-bold text-{{ $marginSummary['total_margin_value'] >= 0 ? 'success' : 'danger' }}">
                                                    ${{ number_format($marginSummary['total_margin_value'], 2) }}
                                                </td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
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
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('auth.no_products_found') }}</p>
                                        @if(request()->hasAny(['category_id', 'min_margin', 'max_margin']))
                                        <a href="{{ route('reports.products.margin') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('accounting.clear_filters_view_all') }}
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
@if($variants->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare data for charts
        @php
            // Get top 10 products by margin percentage
            $topMarginProducts = $variants->sortByDesc('margin_percentage')->take(10);
            
            // Margin distribution data
            $highMarginCount = $variants->where('margin_category', 'high')->count();
            $mediumMarginCount = $variants->where('margin_category', 'medium')->count();
            $lowMarginCount = $variants->where('margin_category', 'low')->count();
            $veryLowMarginCount = $variants->where('margin_category', 'very_low')->count();
        @endphp
        
        // Margin Distribution Chart (Pie Chart)
        const marginDistributionChart = new ApexCharts(document.querySelector("#marginDistributionChart"), {
            series: [{{ $highMarginCount }}, {{ $mediumMarginCount }}, {{ $lowMarginCount }}, {{ $veryLowMarginCount }}],
            chart: {
                type: 'pie',
                height: 300,
                toolbar: {
                    show: true,
                    tools: {
                        download: true
                    }
                }
            },
            labels: [
                '{{ __("auth.high_margin") }} (≥50%)',
                '{{ __("auth.medium_margin") }} (30-49%)',
                '{{ __("auth.low_margin") }} (10-29%)',
                '{{ __("auth.very_low_margin") }} (<10%)'
            ],
            colors: ['#50CD89', '#3E97FF', '#FFC700', '#F1416C'],
            legend: {
                position: 'bottom',
                fontSize: '12px'
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value + ' {{ __("auth.item") }}' + (value !== 1 ? 's' : '');
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val, opts) {
                    return opts.w.config.series[opts.seriesIndex];
                }
            }
        });
        marginDistributionChart.render();
        
        // Top Margin Products Chart (Bar Chart)
        const topMarginChart = new ApexCharts(document.querySelector("#topMarginChart"), {
            series: [{
                name: '{{ __("auth.margin_percentage") }}',
                data: @json($topMarginProducts->pluck('margin_percentage'))
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: {
                    show: true,
                    tools: {
                        download: true
                    }
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 4,
                    columnWidth: '60%'
                }
            },
            xaxis: {
                categories: @json($topMarginProducts->map(function($product) {
                    return strlen($product->name) > 15 ? 
                        substr($product->name, 0, 15) + '...' : 
                        $product->name;
                })),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '10px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: '{{ __("auth.margin_percentage") }} (%)'
                },
                labels: {
                    formatter: function(val) {
                        return val.toFixed(1) + '%';
                    }
                }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(value, { seriesIndex, dataPointIndex, w }) {
                        const product = @json($topMarginProducts->values())[dataPointIndex];
                        return value.toFixed(1) + '%<br>' + 
                               '{{ __("auth.price") }}: $' + product.price.toFixed(2) + '<br>' +
                               '{{ __("auth.cost_price") }}: $' + (product.cost_price || 0).toFixed(2);
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(1) + '%';
                },
                offsetY: -20,
                style: {
                    fontSize: '10px',
                    colors: ["#333"]
                }
            }
        });
        topMarginChart.render();
        
        // Add export functionality
        window.exportCurrentPage = function(options) {
            const { tableId, filename, sheetName, format = 'excel' } = options;
            const table = document.getElementById(tableId);
            
            if (!table) {
                alert('{{ __("accounting.table_not_found") }}');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    // Clean text - remove HTML tags and trim
                    let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").trim();
                    // Escape quotes and wrap in quotes if contains comma
                    if (text.includes(',') || text.includes('"')) {
                        text = '"' + text.replace(/"/g, '""') + '"';
                    }
                    row.push(text);
                }
                csv.push(row.join(","));
            }
            
            const csvContent = csv.join("\n");
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            
            if (navigator.msSaveBlob) {
                navigator.msSaveBlob(blob, filename + '.csv');
            } else {
                link.href = URL.createObjectURL(blob);
                link.setAttribute("download", filename + '.csv');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        };
    });
</script>
@endif
@endpush

@endsection