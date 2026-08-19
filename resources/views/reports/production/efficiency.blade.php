{{-- resources/views/reports/production/efficiency.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_efficiency'))

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
                                {{ __('pagination.production_efficiency') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_efficiency') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($paginatedEfficiency->count() > 0)
                            <button class="btn btn-sm btn-success" onclick="exportEfficiency()">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
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
                                <form method="GET" action="{{ route('reports.production.efficiency') }}" id="filterForm">
                                    <div class="row g-3">
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                            <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                            <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                            <select class="form-select" name="location_id" data-control="select2">
                                                <option value="">{{ __('pagination.all_locations') }}</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                            <select class="form-select" name="status" data-control="select2">
                                                @foreach($statuses as $s)
                                                    <option value="{{ $s['value'] }}" {{ $status == $s['value'] ? 'selected' : '' }}>
                                                        {{ $s['label'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.variant') }}</label>
                                            <select class="form-select" name="variant_id" data-control="select2">
                                                <option value="">{{ __('pagination.all_variants') }}</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}" {{ $variantId == $variant->id ? 'selected' : '' }}>
                                                        {{ $variant->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <div class="d-flex gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                                    {{ __('pagination.apply') }}
                                                </button>
                                                <a href="{{ route('reports.production.efficiency') }}" class="btn btn-light">
                                                    <i class="ki-duotone ki-cross fs-2"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Efficiency Summary Cards --}}
                <div class="row g-6 mb-6">
                    {{-- Yield Rate --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $efficiencySummary['overall_yield'] >= 80 ? 'success' : ($efficiencySummary['overall_yield'] >= 60 ? 'warning' : 'danger') }} border border-{{ $efficiencySummary['overall_yield'] >= 80 ? 'success' : ($efficiencySummary['overall_yield'] >= 60 ? 'warning' : 'danger') }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-chart-line fs-2tx text-{{ $efficiencySummary['overall_yield'] >= 80 ? 'success' : ($efficiencySummary['overall_yield'] >= 60 ? 'warning' : 'danger') }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $efficiencySummary['overall_yield'] >= 80 ? 'success' : ($efficiencySummary['overall_yield'] >= 60 ? 'warning' : 'danger') }}">
                                    {{ number_format($efficiencySummary['overall_yield'], 1) }}%
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.overall_yield') }}</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($efficiencySummary['total_output_qty'], 1) }} / {{ number_format($efficiencySummary['total_input_qty'], 1) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Cost Efficiency --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $efficiencySummary['overall_cost_efficiency'] >= 80 ? 'success' : ($efficiencySummary['overall_cost_efficiency'] >= 60 ? 'warning' : 'danger') }} border border-{{ $efficiencySummary['overall_cost_efficiency'] >= 80 ? 'success' : ($efficiencySummary['overall_cost_efficiency'] >= 60 ? 'warning' : 'danger') }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-dollar fs-2tx text-{{ $efficiencySummary['overall_cost_efficiency'] >= 80 ? 'success' : ($efficiencySummary['overall_cost_efficiency'] >= 60 ? 'warning' : 'danger') }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $efficiencySummary['overall_cost_efficiency'] >= 80 ? 'success' : ($efficiencySummary['overall_cost_efficiency'] >= 60 ? 'warning' : 'danger') }}">
                                    {{ number_format($efficiencySummary['overall_cost_efficiency'], 1) }}%
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.cost_efficiency') }}</span>
                                <span class="text-muted fs-8">
                                    {{ currency_symbol() }}{{ number_format($efficiencySummary['total_output_cost'], 2) }} / {{ currency_symbol() }}{{ number_format($efficiencySummary['total_cost'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Quality Rate --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $efficiencySummary['quality_rate'] >= 95 ? 'success' : ($efficiencySummary['quality_rate'] >= 80 ? 'warning' : 'danger') }} border border-{{ $efficiencySummary['quality_rate'] >= 95 ? 'success' : ($efficiencySummary['quality_rate'] >= 80 ? 'warning' : 'danger') }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-check-circle fs-2tx text-{{ $efficiencySummary['quality_rate'] >= 95 ? 'success' : ($efficiencySummary['quality_rate'] >= 80 ? 'warning' : 'danger') }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $efficiencySummary['quality_rate'] >= 95 ? 'success' : ($efficiencySummary['quality_rate'] >= 80 ? 'warning' : 'danger') }}">
                                    {{ number_format($efficiencySummary['quality_rate'], 1) }}%
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.quality_rate') }}</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($efficiencySummary['total_defective'], 1) }} {{ __('pagination.defective') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Profit Margin --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $efficiencySummary['profit_margin'] >= 20 ? 'success' : ($efficiencySummary['profit_margin'] >= 10 ? 'warning' : 'danger') }} border border-{{ $efficiencySummary['profit_margin'] >= 20 ? 'success' : ($efficiencySummary['profit_margin'] >= 10 ? 'warning' : 'danger') }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-chart-pie fs-2tx text-{{ $efficiencySummary['profit_margin'] >= 20 ? 'success' : ($efficiencySummary['profit_margin'] >= 10 ? 'warning' : 'danger') }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $efficiencySummary['profit_margin'] >= 20 ? 'success' : ($efficiencySummary['profit_margin'] >= 10 ? 'warning' : 'danger') }}">
                                    {{ number_format($efficiencySummary['profit_margin'], 1) }}%
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.profit_margin') }}</span>
                                <span class="text-muted fs-8">
                                    {{ currency_symbol() }}{{ number_format($efficiencySummary['total_profit'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Metrics --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.waste_rate') }}</span>
                                    <div class="fs-2 fw-bold text-{{ $efficiencySummary['waste_rate'] <= 5 ? 'success' : ($efficiencySummary['waste_rate'] <= 15 ? 'warning' : 'danger') }}">
                                        {{ number_format($efficiencySummary['waste_rate'], 1) }}%
                                    </div>
                                </div>
                                <i class="ki-duotone ki-trash fs-2tx text-{{ $efficiencySummary['waste_rate'] <= 5 ? 'success' : ($efficiencySummary['waste_rate'] <= 15 ? 'warning' : 'danger') }}">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.avg_duration') }}</span>
                                    <div class="fs-2 fw-bold text-primary">{{ number_format($efficiencySummary['avg_duration_hours'], 1) }}h</div>
                                </div>
                                <i class="ki-duotone ki-clock fs-2tx text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.order_completion') }}</span>
                                    <div class="fs-2 fw-bold text-secondary">
                                        {{ $efficiencySummary['completed_orders'] }} / {{ $efficiencySummary['total_orders'] }}
                                    </div>
                                </div>
                                <i class="ki-duotone ki-check-square fs-2tx text-secondary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts Section --}}
                <div class="row g-6 mb-6">
                    {{-- Monthly Efficiency Trends --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.efficiency_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="efficiencyTrendChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Efficiency by Location --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-location fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.efficiency_by_location') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="locationEfficiencyChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Product Efficiency Summary --}}
                @if($productEfficiencySummary->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-product fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_products_by_efficiency') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.category') }}</th>
                                                <th class="text-center">{{ __('pagination.total_quantity') }}</th>
                                                <th class="text-center">{{ __('pagination.profit_margin') }}</th>
                                                <th class="text-center">{{ __('pagination.quality_rate') }}</th>
                                                <th class="text-center">{{ __('pagination.cost_per_unit') }}</th>
                                                <th class="text-center">{{ __('pagination.profit_per_unit') }}</th>
                                                <th class="text-center">{{ __('pagination.orders') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($productEfficiencySummary as $index => $product)
                                            @php
                                                $marginColor = $product->profit_margin >= 30 ? 'success' : ($product->profit_margin >= 10 ? 'warning' : 'danger');
                                                $qualityColor = $product->quality_rate >= 95 ? 'success' : ($product->quality_rate >= 80 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $product->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $product->variant_sku }}</div>
                                                </td>
                                                <td><span class="badge badge-light-primary">{{ $product->category }}</span></td>
                                                <td class="text-center">{{ number_format($product->total_quantity, 1) }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $marginColor }}">
                                                        {{ number_format($product->profit_margin, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $qualityColor }}">
                                                        {{ number_format($product->quality_rate, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($product->cost_per_unit, 2) }}</td>
                                                <td class="text-center text-{{ $product->profit_per_unit >= 0 ? 'success' : 'danger' }}">
                                                    {{ currency_symbol() }}{{ number_format($product->profit_per_unit, 2) }}
                                                </td>
                                                <td class="text-center">{{ $product->order_count }}</td>
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

                {{-- Efficiency Table --}}
                @if($paginatedEfficiency->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.efficiency_by_order') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedEfficiency->count() }} {{ __('pagination.of') }} {{ $paginatedEfficiency->total() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.order') }}</th>
                                                <th>{{ __('pagination.status') }}</th>
                                                <th>{{ __('pagination.location') }}</th>
                                                <th class="text-center">{{ __('pagination.yield') }}</th>
                                                <th class="text-center">{{ __('pagination.cost_efficiency') }}</th>
                                                <th class="text-center">{{ __('pagination.quality_rate') }}</th>
                                                <th class="text-center">{{ __('pagination.waste_rate') }}</th>
                                                <th class="text-center">{{ __('pagination.duration') }}</th>
                                                <th class="text-center">{{ __('pagination.profit') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedEfficiency as $index => $item)
                                            @php
                                                $yieldColor = $item->yield >= 80 ? 'success' : ($item->yield >= 60 ? 'warning' : 'danger');
                                                $costColor = $item->cost_efficiency >= 80 ? 'success' : ($item->cost_efficiency >= 60 ? 'warning' : 'danger');
                                                $qualityColor = $item->quality_rate >= 95 ? 'success' : ($item->quality_rate >= 80 ? 'warning' : 'danger');
                                                $wasteColor = $item->waste_rate <= 5 ? 'success' : ($item->waste_rate <= 15 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
                                                <td>{{ $paginatedEfficiency->firstItem() + $index }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->production_number }}</div>
                                                    <div class="text-muted fs-8">{{ $item->created_at->format('Y-m-d') }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $item->status_badge }}">
                                                        {{ $item->status_label }}
                                                    </span>
                                                </td>
                                                <td>{{ $item->location }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $yieldColor }}">
                                                        {{ number_format($item->yield, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $costColor }}">
                                                        {{ number_format($item->cost_efficiency, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $qualityColor }}">
                                                        {{ number_format($item->quality_rate, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $wasteColor }}">
                                                        {{ number_format($item->waste_rate, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">{{ number_format($item->duration_hours, 1) }}h</td>
                                                <td class="text-center text-{{ $item->profit >= 0 ? 'success' : 'danger' }}">
                                                    {{ currency_symbol() }}{{ number_format($item->profit, 2) }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="10" class="text-end fw-bold">
                                                    {{ __('pagination.total_orders') }}: {{ $paginatedEfficiency->total() }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $paginatedEfficiency,
                                        'pageName' => 'page',
                                        'perPageName' => 'per_page',
                                        'showPerPage' => true
                                    ])
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
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('pagination.no_production_data') }}</p>
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Efficiency Trends Chart ──────────────────────────────────
    const monthlyData = @json($monthlyEfficiency);
    
    if (monthlyData.length > 0) {
        const months = monthlyData.map(d => d.month);
        const yieldData = monthlyData.map(d => d.yield);
        const costData = monthlyData.map(d => d.cost_efficiency);
        const qualityData = monthlyData.map(d => d.quality_rate);
        const profitData = monthlyData.map(d => d.profit_margin);
        
        const trendChart = new ApexCharts(document.querySelector("#efficiencyTrendChart"), {
            series: [
                {
                    name: 'Yield',
                    data: yieldData,
                    type: 'line'
                },
                {
                    name: 'Cost Efficiency',
                    data: costData,
                    type: 'line'
                },
                {
                    name: 'Quality Rate',
                    data: qualityData,
                    type: 'line'
                },
                {
                    name: 'Profit Margin',
                    data: profitData,
                    type: 'line'
                }
            ],
            chart: {
                type: 'line',
                height: 350,
                toolbar: { show: true },
                zoom: { enabled: true }
            },
            stroke: { width: [3, 3, 3, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: months,
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: 'Percentage (%)' },
                min: 0,
                max: 100,
                labels: {
                    formatter: function(val) {
                        return val + '%';
                    }
                }
            },
            colors: ['#3E97FF', '#50CD89', '#FFC700', '#F1416C'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return val.toFixed(1) + '%';
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center'
            }
        });
        trendChart.render();
    }
    
    // ─── Location Efficiency Chart ──────────────────────────────────
    const locationData = @json($efficiencyByLocation);
    
    if (locationData.length > 0) {
        const locations = locationData.map(d => d.location_name);
        const yieldData = locationData.map(d => d.yield);
        const qualityData = locationData.map(d => d.quality_rate);
        const profitData = locationData.map(d => d.profit_margin);
        
        const locationChart = new ApexCharts(document.querySelector("#locationEfficiencyChart"), {
            series: [
                {
                    name: 'Yield',
                    data: yieldData,
                    type: 'bar'
                },
                {
                    name: 'Quality Rate',
                    data: qualityData,
                    type: 'bar'
                },
                {
                    name: 'Profit Margin',
                    data: profitData,
                    type: 'line'
                }
            ],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: true },
                stacked: false
            },
            plotOptions: {
                bar: { horizontal: true, columnWidth: '50%' }
            },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: locations,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: 'Percentage (%)' },
                labels: {
                    formatter: function(val) {
                        return val + '%';
                    }
                }
            },
            colors: ['#3E97FF', '#50CD89', '#F1416C'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return val.toFixed(1) + '%';
                    }
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            }
        });
        locationChart.render();
    }
});

// ─── Export Function ──────────────────────────────────────────────
function exportEfficiency() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    window.location.href = `/reports/production/efficiency/export?${params.toString()}`;
}
</script>
@endpush

@endsection