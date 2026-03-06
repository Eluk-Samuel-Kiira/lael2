{{-- resources/views/reports/purchasing/purchase-cost-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.purchase_cost_analysis'))

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
                                {{ __('pagination.purchase_cost_analysis') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchase_cost_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($productAnalysis->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="exportReport('excel')">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="exportReport('pdf')">
                                            <i class="ki-duotone ki-file-pdf fs-2 me-2 text-danger"></i>
                                            {{ __('pagination.export_to_pdf') }}
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
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.purchasing.purchase-cost-analysis') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
                                        {{-- Start Date --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('pagination.start_date') }}</label>
                                            <input type="date" class="form-control w-100" name="start_date" 
                                                value="{{ $startDate }}" required>
                                        </div>
                                        
                                        {{-- End Date --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('pagination.end_date') }}</label>
                                            <input type="date" class="form-control w-100" name="end_date" 
                                                value="{{ $endDate }}" required>
                                        </div>
                                        
                                        {{-- Product Variant --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('passwords.product_variant') }}</label>
                                            <select class="form-select w-100" name="variant_id">
                                                <option value="">{{ __('passwords.all_products') }}</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}" 
                                                            {{ $productVariantId == $variant->id ? 'selected' : '' }}>
                                                        {{ $variant->name }} 
                                                        @if($variant->product)
                                                            ({{ $variant->product->name }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        {{-- Supplier --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                            <select class="form-select w-100" name="supplier_id">
                                                <option value="">{{ __('passwords.all_suppliers') }}</option>
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" 
                                                            {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <div class="d-flex flex-column flex-sm-row gap-2">
                                            <button type="submit" class="btn btn-primary flex-grow-1 flex-sm-grow-0" id="applyFilters">
                                                <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                <span class="d-none d-sm-inline">{{ __('pagination.apply_filters') }}</span>
                                                <span class="d-inline d-sm-none">{{ __('pagination.apply') }}</span>
                                            </button>
                                            <a href="{{ route('reports.purchasing.purchase-cost-analysis') }}" class="btn btn-light btn-active-light-primary flex-grow-1 flex-sm-grow-0">
                                                <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                <span class="d-none d-sm-inline">{{ __('pagination.clear_filters') }}</span>
                                                <span class="d-inline d-sm-none">{{ __('pagination.clear') }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="row mb-6">
                    @php
                        $summaryCards = [
                            [
                                'title' => __('passwords.total_products_analyzed'),
                                'value' => number_format($summary['total_products']),
                                'color' => 'primary',
                                'icon' => 'ki-element-plus',
                                'description' => __('passwords.unique_products')
                            ],
                            [
                                'title' => __('passwords.total_quantity'),
                                'value' => number_format($summary['total_quantity']),
                                'color' => 'success',
                                'icon' => 'ki-arrow-up',
                                'description' => __('passwords.total_units_purchased')
                            ],
                            [
                                'title' => __('passwords.total_cost'),
                                'value' => '$' . number_format($summary['total_cost'], 2),
                                'color' => 'info',
                                'icon' => 'ki-dollar',
                                'description' => __('passwords.total_spend')
                            ],
                            [
                                'title' => __('passwords.avg_price_change'),
                                'value' => number_format($summary['avg_price_increase'] ?? 0, 2) . '%',
                                'color' => $summary['avg_price_increase'] > 0 ? 'danger' : 'success',
                                'icon' => 'ki-chart-line',
                                'description' => __('passwords.average_price_movement')
                            ]
                        ];
                    @endphp
                    
                    @foreach($summaryCards as $card)
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card card-flush bg-light-{{ $card['color'] }} border border-{{ $card['color'] }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone {{ $card['icon'] }} fs-2tx text-{{ $card['color'] }} me-3">
                                        @for($i = 1; $i <= 2; $i++)
                                        <span class="path{{ $i }}"></span>
                                        @endfor
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ $card['title'] }}</div>
                                        <div class="fs-2 fw-bold text-{{ $card['color'] }}">{{ $card['value'] }}</div>
                                        @if($card['description'])
                                        <div class="text-muted fs-7">{{ $card['description'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Price Movement Cards --}}
                <div class="row mb-6">
                    @php
                        $priceMovementCards = [
                            [
                                'title' => __('passwords.products_with_price_increase'),
                                'value' => number_format($summary['products_with_price_increase']),
                                'color' => 'danger',
                                'icon' => 'ki-arrow-up-right',
                                'description' => __('passwords.price_increased')
                            ],
                            [
                                'title' => __('passwords.products_with_price_decrease'),
                                'value' => number_format($summary['products_with_price_decrease']),
                                'color' => 'success',
                                'icon' => 'ki-arrow-down-right',
                                'description' => __('passwords.price_decreased')
                            ],
                            [
                                'title' => __('passwords.price_stable_products'),
                                'value' => number_format($summary['total_products'] - $summary['products_with_price_increase'] - $summary['products_with_price_decrease']),
                                'color' => 'warning',
                                'icon' => 'ki-chart-line',
                                'description' => __('passwords.price_remained_stable')
                            ]
                        ];
                    @endphp
                    
                    @foreach($priceMovementCards as $card)
                    <div class="col-md-4 mb-4">
                        <div class="card card-flush bg-light-{{ $card['color'] }} border border-{{ $card['color'] }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone {{ $card['icon'] }} fs-2tx text-{{ $card['color'] }} me-3">
                                        @for($i = 1; $i <= 2; $i++)
                                        <span class="path{{ $i }}"></span>
                                        @endfor
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ $card['title'] }}</div>
                                        <div class="fs-2 fw-bold text-{{ $card['color'] }}">{{ $card['value'] }}</div>
                                        @if($card['description'])
                                        <div class="text-muted fs-7">{{ $card['description'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Price Trend Chart --}}
                @if($priceTrends->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-chart-line-up fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.price_trend_analysis') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.period') }}: {{ $startDate }} {{ __('pagination.to') }} {{ $endDate }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="priceTrendChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Top Products by Spend --}}
                @if($productAnalysis->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-crown fs-2 me-2 text-warning">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.top_products_by_spend') }}</h3>
                                    </div>
                                    <span class="badge badge-light-warning fs-7">
                                        {{ __('pagination.top') }} 10 {{ __('passwords.products') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('passwords.product') }}</th>
                                                <th>{{ __('passwords.total_quantity') }}</th>
                                                <th>{{ __('passwords.avg_unit_cost') }}</th>
                                                <th>{{ __('passwords.total_cost') }}</th>
                                                <th>{{ __('passwords.price_change') }}</th>
                                                <th>{{ __('passwords.price_change_percentage') }}</th>
                                                <th>{{ __('passwords.purchase_count') }}</th>
                                                <th class="text-end pe-4">{{ __('passwords.trend') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($productAnalysis->take(10) as $analysis)
                                            @php
                                                $product = $analysis['product'] ?? null;
                                                $priceChange = $analysis['price_change'] ?? 0;
                                                $priceChangePercentage = $analysis['price_change_percentage'] ?? 0;
                                                
                                                // Determine trend color
                                                $trendColor = $priceChangePercentage > 5 ? 'danger' : 
                                                             ($priceChangePercentage > 0 ? 'warning' : 
                                                             ($priceChangePercentage < -5 ? 'success' : 'info'));
                                                $trendIcon = $priceChangePercentage > 0 ? 'ki-arrow-up-right' : 
                                                            ($priceChangePercentage < 0 ? 'ki-arrow-down-right' : 'ki-chart-line');
                                                $trendText = $priceChangePercentage > 5 ? __('passwords.significant_increase') :
                                                            ($priceChangePercentage > 0 ? __('passwords.moderate_increase') :
                                                            ($priceChangePercentage < -5 ? __('passwords.significant_decrease') :
                                                            ($priceChangePercentage < 0 ? __('passwords.moderate_decrease') : __('passwords.stable'))));
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    @if($product)
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-50px me-3">
                                                            <div class="symbol-label bg-light-primary text-primary fw-bold">
                                                                {{ strtoupper(substr($product->name, 0, 2)) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $product->name }}</div>
                                                            <small class="text-muted">
                                                                {{ $product->sku ?? 'N/A' }}
                                                                @if($product->product)
                                                                    <br>{{ $product->product->name }}
                                                                @endif
                                                            </small>
                                                        </div>
                                                    </div>
                                                    @else
                                                    <span class="text-muted">{{ __('passwords.product_not_found') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($analysis['total_quantity'] ?? 0) }}</span>
                                                    <div class="text-muted fs-8">{{ __('passwords.units') }}</div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        ${{ number_format($analysis['avg_unit_cost'] ?? 0, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">
                                                        ${{ number_format($analysis['total_cost'] ?? 0, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-{{ $trendColor }}">
                                                        ${{ number_format($priceChange, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="fw-bold text-{{ $trendColor }} me-2">
                                                            {{ number_format($priceChangePercentage, 2) }}%
                                                        </span>
                                                        @if($priceChangePercentage != 0)
                                                        <i class="ki-duotone {{ $trendIcon }} fs-2 text-{{ $trendColor }}">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">
                                                        {{ $analysis['purchase_count'] ?? 0 }} {{ __('passwords.purchases') }}
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="badge badge-{{ $trendColor }}">
                                                        {{ $trendText }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Complete Cost Analysis Table --}}
                @if($productAnalysis->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('passwords.complete_cost_analysis') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $productAnalysis->count() }} {{ __('passwords.products') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('passwords.product') }}</th>
                                                <th>{{ __('passwords.category') }}</th>
                                                <th>{{ __('passwords.total_quantity') }}</th>
                                                <th>{{ __('passwords.avg_unit_cost') }}</th>
                                                <th>{{ __('passwords.total_cost') }}</th>
                                                <th>{{ __('passwords.price_change') }}</th>
                                                <th>{{ __('passwords.price_change_percentage') }}</th>
                                                <th>{{ __('passwords.purchase_frequency') }}</th>
                                                <th class="text-end pe-4">{{ __('passwords.cost_efficiency') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($productAnalysis as $index => $analysis)
                                            @php
                                                $product = $analysis['product'] ?? null;
                                                $priceChange = $analysis['price_change'] ?? 0;
                                                $priceChangePercentage = $analysis['price_change_percentage'] ?? 0;
                                                $avgUnitCost = $analysis['avg_unit_cost'] ?? 0;
                                                $totalCost = $analysis['total_cost'] ?? 0;
                                                $totalQuantity = $analysis['total_quantity'] ?? 0;
                                                $purchaseCount = $analysis['purchase_count'] ?? 0;
                                                
                                                // Determine trend color
                                                $trendColor = $priceChangePercentage > 5 ? 'danger' : 
                                                             ($priceChangePercentage > 0 ? 'warning' : 
                                                             ($priceChangePercentage < -5 ? 'success' : 'info'));
                                                
                                                // Calculate cost efficiency
                                                $costEfficiency = 'high';
                                                $efficiencyColor = 'success';
                                                $avgCostPerPurchase = $purchaseCount > 0 ? $totalCost / $purchaseCount : 0;
                                                
                                                if ($avgCostPerPurchase > 1000) {
                                                    $costEfficiency = 'low';
                                                    $efficiencyColor = 'danger';
                                                } elseif ($avgCostPerPurchase > 500) {
                                                    $costEfficiency = 'medium';
                                                    $efficiencyColor = 'warning';
                                                }
                                                
                                                // Calculate purchase frequency
                                                $purchaseFrequency = 'high';
                                                $frequencyColor = 'success';
                                                $daysInPeriod = \Carbon\Carbon::parse($endDate)->diffInDays(\Carbon\Carbon::parse($startDate));
                                                $frequencyPerMonth = $daysInPeriod > 0 ? ($purchaseCount / $daysInPeriod) * 30 : 0;
                                                
                                                if ($frequencyPerMonth > 4) {
                                                    $purchaseFrequency = 'very_high';
                                                    $frequencyColor = 'primary';
                                                } elseif ($frequencyPerMonth > 2) {
                                                    $purchaseFrequency = 'high';
                                                    $frequencyColor = 'success';
                                                } elseif ($frequencyPerMonth > 1) {
                                                    $purchaseFrequency = 'moderate';
                                                    $frequencyColor = 'info';
                                                } elseif ($frequencyPerMonth > 0.5) {
                                                    $purchaseFrequency = 'low';
                                                    $frequencyColor = 'warning';
                                                } else {
                                                    $purchaseFrequency = 'very_low';
                                                    $frequencyColor = 'danger';
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $loop->iteration }}</span>
                                                </td>
                                                <td>
                                                    @if($product)
                                                    <div class="fw-bold">{{ $product->name }}</div>
                                                    <small class="text-muted">
                                                        {{ $product->sku ?? 'N/A' }}
                                                    </small>
                                                    @else
                                                    <span class="text-muted">{{ __('passwords.product_not_found') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($product && $product->product && $product->product->category)
                                                    <span class="badge badge-light-info">
                                                        {{ $product->product->category->name ?? 'N/A' }}
                                                    </span>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ number_format($totalQuantity) }}</span>
                                                    <div class="text-muted fs-8">{{ __('passwords.units') }}</div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        ${{ number_format($avgUnitCost, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">
                                                        ${{ number_format($totalCost, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="fw-bold text-{{ $trendColor }} me-2">
                                                            ${{ number_format($priceChange, 2) }}
                                                        </span>
                                                        @if($priceChange != 0)
                                                        <i class="ki-duotone {{ $priceChange > 0 ? 'ki-arrow-up-right' : 'ki-arrow-down-right' }} fs-2 text-{{ $trendColor }}">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $trendColor }}">
                                                        {{ number_format($priceChangePercentage, 2) }}%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $frequencyColor }}">
                                                        {{ __("passwords.{$purchaseFrequency}_frequency") }}
                                                    </span>
                                                    <div class="text-muted fs-8">
                                                        {{ $purchaseCount }} {{ __('passwords.orders') }}
                                                    </div>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="badge badge-{{ $efficiencyColor }}">
                                                        {{ __("passwords.{$costEfficiency}_efficiency") }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="text-center py-10">
                                    <i class="ki-duotone ki-chart-line fs-4tx text-gray-400 mb-4">
                                        <span class="path1"></span>
                                    </i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_cost_data') }}</h4>
                                    <p class="text-muted fs-6">{{ __('passwords.no_purchase_cost_data_available') }}</p>
                                    @if(request()->hasAny(['start_date', 'end_date', 'variant_id', 'supplier_id']))
                                    <a href="{{ route('reports.purchasing.purchase-cost-analysis') }}" class="btn btn-light-primary">
                                        <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                        {{ __('passwords.clear_filters_view_all') }}
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
@if($priceTrends->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Price Trend Chart
        @php
            $chartDates = [];
            $chartPrices = [];
            $chartQuantities = [];
            
            foreach ($priceTrends as $date => $trend) {
                $chartDates[] = $date;
                $chartPrices[] = $trend['avg_unit_cost'] ?? 0;
                $chartQuantities[] = $trend['total_quantity'] ?? 0;
            }
        @endphp

        const priceTrendChart = new ApexCharts(document.querySelector("#priceTrendChart"), {
            series: [
                {
                    name: '{{ __("passwords.avg_unit_cost") }}',
                    type: 'line',
                    data: @json($chartPrices)
                },
                {
                    name: '{{ __("passwords.total_quantity") }}',
                    type: 'bar',
                    data: @json($chartQuantities)
                }
            ],
            chart: {
                height: 400,
                type: 'line',
                toolbar: {
                    show: false
                }
            },
            colors: ['#3E97FF', '#50CD89'],
            stroke: {
                width: [3, 0]
            },
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: @json($chartDates),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: [
                {
                    title: {
                        text: '{{ __("passwords.avg_unit_cost_usd") }}'
                    },
                    labels: {
                        formatter: function(val) {
                            return '$' + val.toFixed(2);
                        }
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: '{{ __("passwords.total_quantity") }}'
                    },
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0);
                        }
                    }
                }
            ],
            tooltip: {
                shared: true,
                intersect: false,
                y: [
                    {
                        formatter: function(val) {
                            return '$' + val.toFixed(2);
                        }
                    },
                    {
                        formatter: function(val) {
                            return val.toFixed(0) + ' {{ __("passwords.units") }}';
                        }
                    }
                ]
            },
            legend: {
                position: 'top'
            }
        });
        
        priceTrendChart.render();
        
        // Simple export function
        function exportReport(format) {
            // Your existing export implementation
            console.log('Exporting as', format);
        }
        
        window.exportReport = exportReport;
    });
</script>
@endif
@endpush
@endsection