{{-- resources/views/reports/production/input-output.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_input_output'))

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
                                {{ __('pagination.production_input_output') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_input_output') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($paginatedComparison->count() > 0)
                            <button class="btn btn-sm btn-success" onclick="exportInputOutput()">
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
                                <form method="GET" action="{{ route('reports.production.input-output') }}" id="filterForm">
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
                                            <label class="form-label fw-semibold">{{ __('pagination.comparison_type') }}</label>
                                            <select class="form-select" name="comparison_type">
                                                @foreach($comparisonTypes as $c)
                                                    <option value="{{ $c['value'] }}" {{ $comparisonType == $c['value'] ? 'selected' : '' }}>
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
                                                <a href="{{ route('reports.production.input-output') }}" class="btn btn-light">
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

                {{-- Input vs Output Summary Cards --}}
                <div class="row g-6 mb-6">
                    {{-- Quantity Comparison --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-arrows-circle fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-5 fw-bold text-danger">{{ number_format($inputOutputSummary['total_input_quantity'], 1) }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.input') }}</span>
                                    </div>
                                    <div class="vr"></div>
                                    <div>
                                        <span class="fs-5 fw-bold text-success">{{ number_format($inputOutputSummary['total_output_quantity'], 1) }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.output') }}</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">
                                    {{ __('pagination.quantity_ratio') }}: {{ number_format($inputOutputSummary['quantity_ratio'], 2) }}
                                </span>
                                <span class="text-muted fs-8">
                                    {{ $inputOutputSummary['net_quantity'] >= 0 ? '+' : '' }}{{ number_format($inputOutputSummary['net_quantity'], 1) }} {{ __('pagination.net') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Cost Comparison --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-dollar fs-2tx text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-5 fw-bold text-danger">{{ currency_symbol() }}{{ number_format($inputOutputSummary['total_input_cost'], 2) }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.input') }}</span>
                                    </div>
                                    <div class="vr"></div>
                                    <div>
                                        <span class="fs-5 fw-bold text-success">{{ currency_symbol() }}{{ number_format($inputOutputSummary['total_output_cost'], 2) }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.output') }}</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">
                                    {{ __('pagination.cost_ratio') }}: {{ number_format($inputOutputSummary['cost_ratio'], 2) }}
                                </span>
                                <span class="text-muted fs-8">
                                    {{ $inputOutputSummary['net_cost'] >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($inputOutputSummary['net_cost'], 2) }} {{ __('pagination.net') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Efficiency --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $inputOutputSummary['quantity_efficiency'] >= 100 ? 'success' : 'danger' }} border border-{{ $inputOutputSummary['quantity_efficiency'] >= 100 ? 'success' : 'danger' }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-chart-line fs-2tx text-{{ $inputOutputSummary['quantity_efficiency'] >= 100 ? 'success' : 'danger' }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $inputOutputSummary['quantity_efficiency'] >= 100 ? 'success' : 'danger' }}">
                                    {{ number_format($inputOutputSummary['quantity_efficiency'], 1) }}%
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.quantity_efficiency') }}</span>
                                <span class="text-muted fs-8">
                                    {{ $inputOutputSummary['quantity_ratio'] >= 1 ? __('pagination.efficient') : __('pagination.inefficient') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Cost Efficiency --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $inputOutputSummary['cost_efficiency'] >= 100 ? 'success' : 'danger' }} border border-{{ $inputOutputSummary['cost_efficiency'] >= 100 ? 'success' : 'danger' }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-chart-pie fs-2tx text-{{ $inputOutputSummary['cost_efficiency'] >= 100 ? 'success' : 'danger' }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $inputOutputSummary['cost_efficiency'] >= 100 ? 'success' : 'danger' }}">
                                    {{ number_format($inputOutputSummary['cost_efficiency'], 1) }}%
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.cost_efficiency') }}</span>
                                <span class="text-muted fs-8">
                                    {{ $inputOutputSummary['cost_ratio'] >= 1 ? __('pagination.profitable') : __('pagination.loss_making') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Metrics --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.total_orders') }}</span>
                                    <div class="fs-2 fw-bold">{{ $inputOutputSummary['total_orders'] }}</div>
                                </div>
                                <i class="ki-duotone ki-box fs-2tx text-secondary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.avg_input_per_order') }}</span>
                                    <div class="fs-2 fw-bold text-primary">{{ number_format($inputOutputSummary['avg_input_qty_per_order'], 1) }}</div>
                                </div>
                                <i class="ki-duotone ki-enter fs-2tx text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.avg_output_per_order') }}</span>
                                    <div class="fs-2 fw-bold text-success">{{ number_format($inputOutputSummary['avg_output_qty_per_order'], 1) }}</div>
                                </div>
                                <i class="ki-duotone ki-exit fs-2tx text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.completed_orders') }}</span>
                                    <div class="fs-2 fw-bold text-info">{{ $inputOutputSummary['completed_orders'] }}</div>
                                </div>
                                <i class="ki-duotone ki-check-square fs-2tx text-info">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts Section --}}
                <div class="row g-6 mb-6">
                    {{-- Monthly Input vs Output --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.monthly_input_output') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Category Comparison --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.category_comparison') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="categoryChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Product Summary --}}
                @if($productSummary->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-product fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_products_input_output') }}</h3>
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
                                                <th class="text-center">{{ __('pagination.input_qty') }}</th>
                                                <th class="text-center">{{ __('pagination.output_qty') }}</th>
                                                <th class="text-center">{{ __('pagination.net_change') }}</th>
                                                <th class="text-center">{{ __('pagination.type') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($productSummary as $index => $item)
                                            @php
                                                $typeColor = $item->type == 'net_producer' ? 'success' : ($item->type == 'net_consumer' ? 'danger' : 'secondary');
                                                $typeIcon = $item->type == 'net_producer' ? 'arrow-up' : ($item->type == 'net_consumer' ? 'arrow-down' : 'minus');
                                            @endphp
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $item->variant_sku }}</div>
                                                </td>
                                                <td><span class="badge badge-light-primary">{{ $item->category }}</span></td>
                                                <td class="text-center text-danger">{{ number_format($item->input_quantity, 1) }}</td>
                                                <td class="text-center text-success">{{ number_format($item->output_quantity, 1) }}</td>
                                                <td class="text-center fw-bold text-{{ $item->qty_difference >= 0 ? 'success' : 'danger' }}">
                                                    {{ $item->qty_difference >= 0 ? '+' : '' }}{{ number_format($item->qty_difference, 1) }}
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $typeColor }}">
                                                        <i class="ki-duotone ki-{{ $typeIcon }} fs-2 me-1"></i>
                                                        {{ __($item->type) }}
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

                {{-- Input vs Output by Order Table --}}
                @if($paginatedComparison->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.input_output_by_order') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedComparison->count() }} {{ __('pagination.of') }} {{ $paginatedComparison->total() }}
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
                                                <th class="text-center">{{ __('pagination.input_qty') }}</th>
                                                <th class="text-center">{{ __('pagination.output_qty') }}</th>
                                                <th class="text-center">{{ __('pagination.qty_diff') }}</th>
                                                <th class="text-center">{{ __('pagination.qty_ratio') }}</th>
                                                <th class="text-center">{{ __('pagination.input_cost') }}</th>
                                                <th class="text-center">{{ __('pagination.output_cost') }}</th>
                                                <th class="text-center">{{ __('pagination.cost_diff') }}</th>
                                                <th class="text-center">{{ __('pagination.cost_ratio') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedComparison as $index => $item)
                                            <tr>
                                                <td>{{ $paginatedComparison->firstItem() + $index }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->production_number }}</div>
                                                    <div class="text-muted fs-8">{{ $item->created_at->format('Y-m-d') }}</div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $item->status_badge }}">
                                                        {{ $item->status }}
                                                    </span>
                                                </td>
                                                <td>{{ $item->location }}</td>
                                                <td class="text-center text-danger">{{ number_format($item->input_quantity, 1) }}</td>
                                                <td class="text-center text-success">{{ number_format($item->output_quantity, 1) }}</td>
                                                <td class="text-center fw-bold text-{{ $item->qty_difference >= 0 ? 'success' : 'danger' }}">
                                                    {{ $item->qty_difference >= 0 ? '+' : '' }}{{ number_format($item->qty_difference, 1) }}
                                                </td>
                                                <td class="text-center">{{ number_format($item->qty_ratio, 2) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($item->input_cost, 2) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($item->output_cost, 2) }}</td>
                                                <td class="text-center fw-bold text-{{ $item->cost_difference >= 0 ? 'success' : 'danger' }}">
                                                    {{ $item->cost_difference >= 0 ? '+' : '' }}{{ currency_symbol() }}{{ number_format($item->cost_difference, 2) }}
                                                </td>
                                                <td class="text-center">{{ number_format($item->cost_ratio, 2) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="12" class="text-end fw-bold">
                                                    {{ __('pagination.total_orders') }}: {{ $paginatedComparison->total() }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $paginatedComparison,
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
    // ─── Monthly Input vs Output Chart ──────────────────────────────
    const monthlyData = @json($monthlyComparison);
    
    if (monthlyData.length > 0) {
        const months = monthlyData.map(d => d.month);
        const inputQty = monthlyData.map(d => d.input_quantity);
        const outputQty = monthlyData.map(d => d.output_quantity);
        const qtyDiff = monthlyData.map(d => d.qty_diff);
        
        const monthlyChart = new ApexCharts(document.querySelector("#monthlyChart"), {
            series: [
                {
                    name: 'Input Quantity',
                    data: inputQty,
                    type: 'bar'
                },
                {
                    name: 'Output Quantity',
                    data: outputQty,
                    type: 'bar'
                },
                {
                    name: 'Net Change',
                    data: qtyDiff,
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
                { title: { text: 'Quantity' } },
                { 
                    opposite: true, 
                    title: { text: 'Net Change' },
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(1);
                        }
                    }
                }
            ],
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 2) {
                            return (val >= 0 ? '+' : '') + val.toFixed(1);
                        }
                        return val.toFixed(1);
                    }
                }
            }
        });
        monthlyChart.render();
    }
    
    // ─── Category Comparison Chart ──────────────────────────────────
    const categoryData = @json($categoryComparison);
    
    if (categoryData.length > 0) {
        const categories = categoryData.map(d => d.category);
        const inputQty = categoryData.map(d => d.input_quantity);
        const outputQty = categoryData.map(d => d.output_quantity);
        const qtyDiff = categoryData.map(d => d.qty_difference);
        
        const categoryChart = new ApexCharts(document.querySelector("#categoryChart"), {
            series: [
                {
                    name: 'Input',
                    data: inputQty,
                    type: 'bar'
                },
                {
                    name: 'Output',
                    data: outputQty,
                    type: 'bar'
                },
                {
                    name: 'Difference',
                    data: qtyDiff,
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
                categories: categories,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: 'Quantity' }
            },
            colors: ['#F1416C', '#50CD89', '#3E97FF'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 2) {
                            return (val >= 0 ? '+' : '') + val.toFixed(1);
                        }
                        return val.toFixed(1);
                    }
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            }
        });
        categoryChart.render();
    }
});

// ─── Export Function ──────────────────────────────────────────────
function exportInputOutput() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    window.location.href = `/reports/production/input-output/export?${params.toString()}`;
}
</script>
@endpush

@endsection