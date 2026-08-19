{{-- resources/views/reports/production/cost-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_cost_analysis'))

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
                                {{ __('pagination.production_cost_analysis') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_cost_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($paginatedCostPerUnit->count() > 0)
                            <button class="btn btn-sm btn-success" onclick="exportCostAnalysis()">
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
                                <form method="GET" action="{{ route('reports.production.cost-analysis') }}" id="filterForm">
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
                                            <label class="form-label fw-semibold">{{ __('pagination.cost_type') }}</label>
                                            <select class="form-select" name="cost_type">
                                                @foreach($costTypes as $c)
                                                    <option value="{{ $c['value'] }}" {{ $costType == $c['value'] ? 'selected' : '' }}>
                                                        {{ $c['label'] }}
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
                                        <div class="col-12">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                                    {{ __('pagination.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.production.cost-analysis') }}" class="btn btn-light">
                                                    <i class="ki-duotone ki-cross fs-2 me-1"></i>
                                                    {{ __('pagination.clear') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Cost Summary Cards --}}
                <div class="row g-6 mb-6">
                    {{-- Input Cost --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-enter fs-2tx text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-danger">{{ currency_symbol() }}{{ number_format($costSummary['total_input_cost'], 2) }}</span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_input_cost') }}</span>
                                <span class="text-muted fs-8">
                                    {{ __('pagination.avg') }}: {{ currency_symbol() }}{{ number_format($costSummary['avg_input_cost_per_order'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Output Cost --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-exit fs-2tx text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-success">{{ currency_symbol() }}{{ number_format($costSummary['total_output_cost'], 2) }}</span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_output_cost') }}</span>
                                <span class="text-muted fs-8">
                                    {{ __('pagination.avg') }}: {{ currency_symbol() }}{{ number_format($costSummary['avg_output_cost_per_order'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Total Cost --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-dollar fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-primary">{{ currency_symbol() }}{{ number_format($costSummary['total_cost'], 2) }}</span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_cost') }}</span>
                                <span class="text-muted fs-8">
                                    {{ __('pagination.avg_per_order') }}: {{ currency_symbol() }}{{ number_format($costSummary['avg_cost_per_order'], 2) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Profit --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $costSummary['total_profit'] >= 0 ? 'success' : 'danger' }} border border-{{ $costSummary['total_profit'] >= 0 ? 'success' : 'danger' }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-chart-line fs-2tx text-{{ $costSummary['total_profit'] >= 0 ? 'success' : 'danger' }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $costSummary['total_profit'] >= 0 ? 'success' : 'danger' }}">
                                    {{ currency_symbol() }}{{ number_format($costSummary['total_profit'], 2) }}
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_profit') }}</span>
                                <span class="text-muted fs-8">
                                    {{ __('pagination.margin') }}: {{ number_format($costSummary['profit_margin'], 1) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Efficiency Metrics --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.cost_efficiency') }}</span>
                                    <div class="fs-2 fw-bold text-info">{{ number_format($costSummary['cost_efficiency'], 1) }}%</div>
                                </div>
                                <i class="ki-duotone ki-chart-bar fs-2tx text-info">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-{{ $costSummary['profit_margin'] >= 0 ? 'success' : 'danger' }} border border-{{ $costSummary['profit_margin'] >= 0 ? 'success' : 'danger' }} border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.profit_margin') }}</span>
                                    <div class="fs-2 fw-bold text-{{ $costSummary['profit_margin'] >= 0 ? 'success' : 'danger' }}">
                                        {{ number_format($costSummary['profit_margin'], 1) }}%
                                    </div>
                                </div>
                                <i class="ki-duotone ki-chart-pie fs-2tx text-{{ $costSummary['profit_margin'] >= 0 ? 'success' : 'danger' }}">
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
                                    <span class="text-muted">{{ __('pagination.input_to_output_ratio') }}</span>
                                    <div class="fs-2 fw-bold text-secondary">
                                        {{ $costSummary['total_input_cost'] > 0 ? number_format($costSummary['total_output_cost'] / $costSummary['total_input_cost'], 2) : 0 }}
                                    </div>
                                </div>
                                <i class="ki-duotone ki-arrows-circle fs-2tx text-secondary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts Section --}}
                <div class="row g-6 mb-6">
                    {{-- Monthly Cost Trends --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.monthly_cost_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyCostChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Cost by Status --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.cost_by_status') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="costByStatusChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Category Cost Breakdown --}}
                <div class="row g-6 mb-6">
                    {{-- Input Cost by Category --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-enter fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.input_cost_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>{{ __('pagination.category') }}</th>
                                                <th class="text-center">{{ __('pagination.quantity') }}</th>
                                                <th class="text-center">{{ __('pagination.total_cost') }}</th>
                                                <th class="text-center">{{ __('pagination.avg_per_unit') }}</th>
                                                <th class="text-center">{{ __('pagination.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($categoryInputCosts as $category)
                                            @php
                                                $percentage = $costSummary['total_input_cost'] > 0 
                                                    ? ($category->total_cost / $costSummary['total_input_cost']) * 100 
                                                    : 0;
                                            @endphp
                                            <tr>
                                                <td class="fw-bold">{{ $category->category }}</td>
                                                <td class="text-center">{{ number_format($category->total_quantity, 1) }}</td>
                                                <td class="text-center text-danger">{{ currency_symbol() }}{{ number_format($category->total_cost, 2) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($category->avg_cost_per_unit, 2) }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <span class="badge badge-light-danger">{{ number_format($percentage, 1) }}%</span>
                                                        <div class="progress ms-2" style="width: 60px; height: 5px;">
                                                            <div class="progress-bar bg-danger" style="width: {{ $percentage }}%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                            @if($categoryInputCosts->isEmpty())
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    {{ __('pagination.no_data_available') }}
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Output Cost by Category --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-exit fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.output_cost_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>{{ __('pagination.category') }}</th>
                                                <th class="text-center">{{ __('pagination.quantity') }}</th>
                                                <th class="text-center">{{ __('pagination.total_cost') }}</th>
                                                <th class="text-center">{{ __('pagination.avg_per_unit') }}</th>
                                                <th class="text-center">{{ __('pagination.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($categoryOutputCosts as $category)
                                            @php
                                                $percentage = $costSummary['total_output_cost'] > 0 
                                                    ? ($category->total_cost / $costSummary['total_output_cost']) * 100 
                                                    : 0;
                                            @endphp
                                            <tr>
                                                <td class="fw-bold">{{ $category->category }}</td>
                                                <td class="text-center">{{ number_format($category->total_quantity, 1) }}</td>
                                                <td class="text-center text-success">{{ currency_symbol() }}{{ number_format($category->total_cost, 2) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($category->avg_cost_per_unit, 2) }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <span class="badge badge-light-success">{{ number_format($percentage, 1) }}%</span>
                                                        <div class="progress ms-2" style="width: 60px; height: 5px;">
                                                            <div class="progress-bar bg-success" style="width: {{ $percentage }}%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                            @if($categoryOutputCosts->isEmpty())
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    {{ __('pagination.no_data_available') }}
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Cost Per Unit Analysis Table --}}
                @if($paginatedCostPerUnit->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-calculator fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.cost_per_unit_analysis') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedCostPerUnit->count() }} {{ __('pagination.of') }} {{ $paginatedCostPerUnit->total() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th class="text-center">{{ __('pagination.total_quantity') }}</th>
                                                <th class="text-center">{{ __('pagination.avg_cost_per_unit') }}</th>
                                                <th class="text-center">{{ __('pagination.avg_selling_price') }}</th>
                                                <th class="text-center">{{ __('pagination.avg_profit_per_unit') }}</th>
                                                <th class="text-center">{{ __('pagination.profit_margin') }}</th>
                                                <th class="text-center">{{ __('pagination.orders') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedCostPerUnit as $index => $item)
                                            @php
                                                $marginColor = $item->avg_profit_margin >= 30 ? 'success' : ($item->avg_profit_margin >= 10 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
                                                <td>{{ $paginatedCostPerUnit->firstItem() + $index }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $item->variant_sku }}</div>
                                                </td>
                                                <td class="text-center">{{ number_format($item->total_quantity, 1) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($item->avg_cost_per_unit, 2) }}</td>
                                                <td class="text-center text-primary">{{ currency_symbol() }}{{ number_format($item->avg_selling_price, 2) }}</td>
                                                <td class="text-center text-{{ $item->avg_profit_per_unit >= 0 ? 'success' : 'danger' }}">
                                                    {{ currency_symbol() }}{{ number_format($item->avg_profit_per_unit, 2) }}
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $marginColor }}">
                                                        {{ number_format($item->avg_profit_margin, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">{{ $item->order_count }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="8" class="text-end fw-bold">
                                                    {{ __('pagination.total_products') }}: {{ $paginatedCostPerUnit->total() }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $paginatedCostPerUnit,
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
    // ─── Monthly Cost Trends ──────────────────────────────────────
    const monthlyData = @json($monthlyCostTrends);
    
    if (monthlyData.length > 0) {
        const months = monthlyData.map(d => d.month);
        const inputCost = monthlyData.map(d => d.input_cost);
        const outputCost = monthlyData.map(d => d.output_cost);
        const profit = monthlyData.map(d => d.profit);
        
        const monthlyChart = new ApexCharts(document.querySelector("#monthlyCostChart"), {
            series: [
                {
                    name: 'Input Cost',
                    data: inputCost,
                    type: 'bar'
                },
                {
                    name: 'Output Cost',
                    data: outputCost,
                    type: 'bar'
                },
                {
                    name: 'Profit',
                    data: profit,
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
                bar: { horizontal: false, columnWidth: '35%' }
            },
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: months,
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } }
            },
            yaxis: [
                { 
                    title: { text: 'Cost' },
                    labels: {
                        formatter: function(val) {
                            return '{{ currency_symbol() }}' + val.toFixed(0);
                        }
                    }
                },
                { 
                    opposite: true, 
                    title: { text: 'Profit' },
                    labels: {
                        formatter: function(val) {
                            return '{{ currency_symbol() }}' + val.toFixed(0);
                        }
                    }
                }
            ],
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return '{{ currency_symbol() }}' + val.toFixed(2);
                    }
                }
            }
        });
        monthlyChart.render();
    }
    
    // ─── Cost by Status Chart ──────────────────────────────────────
    const statusData = @json($costByStatus);
    
    if (statusData.length > 0) {
        const statusChart = new ApexCharts(document.querySelector("#costByStatusChart"), {
            series: statusData.map(s => s.total_cost),
            chart: { type: 'donut', height: 350 },
            labels: statusData.map(s => s.status),
            colors: statusData.map(s => {
                const colors = {
                    'Draft': '#A1A5B7',
                    'In Progress': '#FFC700',
                    'Completed': '#50CD89',
                    'Cancelled': '#F1416C'
                };
                return colors[s.status] || '#A1A5B7';
            }),
            legend: { position: 'bottom', fontSize: '12px' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Cost',
                                formatter: function(w) {
                                    return '{{ currency_symbol() }}' + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toFixed(2);
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
        });
        statusChart.render();
    }
});

// ─── Export Function ──────────────────────────────────────────────
function exportCostAnalysis() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    window.location.href = `/reports/production/cost-analysis/export?${params.toString()}`;
}
</script>
@endpush

@endsection