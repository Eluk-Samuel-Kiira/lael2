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
                                <div id="marginDistributionChart" style="height: 350px;"></div>
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
                                <div id="topMarginChart" style="height: 350px;"></div>
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
                                                <th class="ps-4 min-w-100px">{{ __('auth.sku') }}</th>
                                                <th class="min-w-200px">{{ __('accounting.name') }}</th>
                                                <th class="min-w-150px">{{ __('accounting.category') }}</th>
                                                <th class="min-w-120px text-end">{{ __('auth.price') }}</th>
                                                <th class="min-w-120px text-end">{{ __('auth.cost_price') }}</th>
                                                <th class="min-w-120px text-end">{{ __('auth.margin_amount') }}</th>
                                                <th class="min-w-120px text-center">{{ __('auth.margin_percentage') }}</th>
                                                <th class="min-w-100px text-center">{{ __('auth.quantity') }}</th>
                                                <th class="min-w-150px text-end">{{ __('auth.total_margin_value') }}</th>
                                                <th class="min-w-150px text-center">{{ __('auth.margin_category') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($variants as $variant)
                                            @php
                                                // ✅ Calculate everything in blade
                                                $price = (float)($variant->price ?? 0);
                                                $costPrice = (float)($variant->cost_price ?? 0);
                                                $quantity = (float)($variant->overal_quantity_at_hand ?? 0);
                                                
                                                // Calculate margin amount
                                                $marginAmount = $price - $costPrice;
                                                
                                                // Calculate margin percentage
                                                $marginPercentage = $price > 0 ? ($marginAmount / $price) * 100 : 0;
                                                
                                                // Calculate total margin value
                                                $totalMarginValue = $marginAmount * $quantity;
                                                
                                                // Determine margin category
                                                if ($marginPercentage >= 50) {
                                                    $marginCategory = 'high';
                                                    $marginLabel = __('auth.high_margin');
                                                    $marginColor = 'success';
                                                    $badgeClass = 'success';
                                                } elseif ($marginPercentage >= 30) {
                                                    $marginCategory = 'medium';
                                                    $marginLabel = __('auth.medium_margin');
                                                    $marginColor = 'primary';
                                                    $badgeClass = 'primary';
                                                } elseif ($marginPercentage >= 10) {
                                                    $marginCategory = 'low';
                                                    $marginLabel = __('auth.low_margin');
                                                    $marginColor = 'warning';
                                                    $badgeClass = 'warning';
                                                } else {
                                                    $marginCategory = 'very_low';
                                                    $marginLabel = __('auth.very_low_margin');
                                                    $marginColor = 'danger';
                                                    $badgeClass = 'danger';
                                                }
                                                
                                                // Determine text color for margin amount
                                                $marginAmountClass = $marginAmount >= 0 ? 'success' : 'danger';
                                                $totalMarginClass = $totalMarginValue >= 0 ? 'success' : 'danger';
                                            @endphp
                                            <tr>
                                                <td class="ps-4 fw-semibold">{{ $variant->sku }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($variant->product && $variant->product->image_url)
                                                        <div class="symbol symbol-50px me-3">
                                                            <img src="{{ asset($variant->product->image_url) }}" alt="{{ $variant->name }}" class="rounded">
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <span class="fw-bold text-gray-800">{{ $variant->product?->name ?? $variant->name }}</span>
                                                            @if($variant->name !== ($variant->product?->name ?? ''))
                                                            <div class="text-muted fs-7">{{ $variant->name }}</div>
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
                                                <td class="text-end">
                                                    <span class="text-gray-800 fw-semibold">${{ number_format($price, 2) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="text-gray-600">${{ number_format($costPrice, 2) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="text-{{ $marginAmountClass }} fw-bold">
                                                        ${{ number_format($marginAmount, 2) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-{{ $badgeClass }}">
                                                        {{ number_format($marginPercentage, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-primary">{{ number_format($quantity) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="text-{{ $totalMarginClass }} fw-bold">
                                                        ${{ number_format($totalMarginValue, 2) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-{{ $marginColor }}">
                                                        {{ $marginLabel }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        @php
                                            // Calculate totals from the current page variants
                                            $pageTotalMarginValue = 0;
                                            foreach($variants as $variant) {
                                                $price = (float)($variant->price ?? 0);
                                                $costPrice = (float)($variant->cost_price ?? 0);
                                                $quantity = (float)($variant->overal_quantity_at_hand ?? 0);
                                                $marginAmount = $price - $costPrice;
                                                $pageTotalMarginValue += $marginAmount * $quantity;
                                            }
                                        @endphp
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="8" class="text-end fw-bold">{{ __('auth.total') }} ({{ __('accounting.current_page') }}):</td>
                                                <td class="fw-bold text-{{ $pageTotalMarginValue >= 0 ? 'success' : 'danger' }} text-end">
                                                    ${{ number_format($pageTotalMarginValue, 2) }}
                                                </td>
                                                <td></td>
                                            </tr>
                                            @if(isset($marginSummary['total_margin_value']))
                                            <tr>
                                                <td colspan="8" class="text-end fw-bold text-muted">{{ __('auth.grand_total') }} ({{ __('accounting.all_pages') }}):</td>
                                                <td class="fw-bold text-{{ $marginSummary['total_margin_value'] >= 0 ? 'success' : 'danger' }} text-end">
                                                    ${{ number_format($marginSummary['total_margin_value'], 2) }}
                                                </td>
                                                <td></td>
                                            </tr>
                                            @endif
                                        </tfoot>
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
        // ✅ Use data from marginSummary (already calculated from filtered data)
        const highMarginCount = {{ $marginSummary['high_margin_count'] ?? 0 }};
        const mediumMarginCount = {{ $marginSummary['medium_margin_count'] ?? 0 }};
        const lowMarginCount = {{ $marginSummary['low_margin_count'] ?? 0 }};
        const veryLowMarginCount = {{ $marginSummary['very_low_margin_count'] ?? 0 }};
        
        console.log('Chart Data:', { highMarginCount, mediumMarginCount, lowMarginCount, veryLowMarginCount });
        
        // Margin Distribution Chart (Pie Chart)
        const marginDistElement = document.querySelector("#marginDistributionChart");
        if (marginDistElement && (highMarginCount > 0 || mediumMarginCount > 0 || lowMarginCount > 0 || veryLowMarginCount > 0)) {
            const marginDistributionChart = new ApexCharts(marginDistElement, {
                series: [highMarginCount, mediumMarginCount, lowMarginCount, veryLowMarginCount],
                chart: {
                    type: 'pie',
                    height: 350,
                    width: '100%',
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
                    fontSize: '12px',
                    labels: {
                        colors: '#5B5B5B'
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value) {
                            const total = highMarginCount + mediumMarginCount + lowMarginCount + veryLowMarginCount;
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return value + ' {{ __("auth.items") }} (' + percentage + '%)';
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        const total = highMarginCount + mediumMarginCount + lowMarginCount + veryLowMarginCount;
                        const percentage = total > 0 ? ((opts.w.config.series[opts.seriesIndex] / total) * 100).toFixed(1) : 0;
                        return opts.w.config.series[opts.seriesIndex] + ' (' + percentage + '%)';
                    },
                    style: {
                        fontSize: '11px',
                        fontWeight: 'bold'
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            height: 300
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            });
            marginDistributionChart.render();
        } else {
            console.warn('No data for margin distribution chart');
            if (marginDistElement) {
                marginDistElement.innerHTML = '<div class="text-center text-muted py-5">{{ __("accounting.no_data_available") }}</div>';
            }
        }
        
        // Top Margin Products Chart (Bar Chart)
        // Top Margin Products Chart (Bar Chart)
        @php
            // Get top 10 products by margin percentage from sortedVariants
            $topProducts = isset($sortedVariants) ? $sortedVariants->take(10) : collect();
            $topProductNames = [];
            $topProductMargins = [];
            $topProductPrices = [];
            $topProductCosts = [];
            
            foreach($topProducts as $product) {
                $price = (float)($product->price ?? 0);
                $costPrice = (float)($product->cost_price ?? 0);
                $marginPercentage = $price > 0 ? (($price - $costPrice) / $price) * 100 : 0;
                
                $name = $product->product?->name ?? $product->name;
                $topProductNames[] = strlen($name) > 20 ? substr($name, 0, 17) . '...' : $name;
                $topProductMargins[] = $marginPercentage;
                $topProductPrices[] = $price;
                $topProductCosts[] = $costPrice;
            }
        @endphp
        
        const topProductsElement = document.querySelector("#topMarginChart");
        if (topProductsElement && {{ count($topProductMargins) }} > 0) {
            const topMarginChart = new ApexCharts(topProductsElement, {
                series: [{
                    name: '{{ __("auth.margin_percentage") }}',
                    data: @json($topProductMargins)
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    width: '100%',
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
                        columnWidth: '60%',
                        distributed: true
                    }
                },
                xaxis: {
                    categories: @json($topProductNames),
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '11px',
                            colors: '#5B5B5B'
                        },
                        trim: true
                    },
                    title: {
                        text: '{{ __("accounting.product") }}',
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: '{{ __("auth.margin_percentage") }} (%)',
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    },
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(1) + '%';
                        }
                    },
                    min: 0
                },
                colors: ['#3E97FF'],
                tooltip: {
                    y: {
                        formatter: function(value, { dataPointIndex }) {
                            const product = @json($topMarginProducts->values())[dataPointIndex];
                            return value.toFixed(1) + '%\n' + 
                                   '{{ __("auth.price") }}: $' + (product.price || 0).toFixed(2) + '\n' +
                                   '{{ __("auth.cost_price") }}: $' + (product.cost_price || 0).toFixed(2) + '\n' +
                                   '{{ __("auth.margin_amount") }}: $' + (product.margin_amount || 0).toFixed(2);
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
                        fontWeight: 'bold',
                        colors: ["#333"]
                    }
                },
                grid: {
                    borderColor: '#e7e7e7',
                    row: {
                        colors: ['#f3f3f3', 'transparent'],
                        opacity: 0.5
                    }
                }
            });
            topMarginChart.render();
        } else {
            console.warn('No data for top margin chart');
            if (topProductsElement) {
                topProductsElement.innerHTML = '<div class="text-center text-muted py-5">{{ __("accounting.no_data_available") }}</div>';
            }
        }
        
        // Add export functionality
        window.exportCurrentPage = function(options) {
            const { tableId, filename } = options;
            const table = document.getElementById(tableId);
            
            if (!table) {
                alert('Table not found');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = [];
                const cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, "").trim();
                    if (text.includes(',') || text.includes('"')) {
                        text = '"' + text.replace(/"/g, '""') + '"';
                    }
                    row.push(text);
                }
                csv.push(row.join(","));
            }
            
            const csvContent = "\uFEFF" + csv.join("\n");
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            
            link.href = url;
            link.setAttribute("download", filename + '.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        };
    });
</script>
@endif
@endpush

@endsection