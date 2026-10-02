{{-- resources/views/reports/production/summary.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_summary'))

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
                                {{ __('pagination.production_summary') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <a href="{{ route('production-orders.index') }}" class="btn btn-sm btn-primary">
                                <i class="ki-duotone ki-plus fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.new_production_order') }}</span>
                            </a>
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
                                <form method="GET" action="{{ route('reports.production.summary') }}" id="filterForm">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                            <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                            <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                        </div>
                                        <div class="col-md-3">
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
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                            <select class="form-select" name="status" data-control="select2">
                                                @foreach($statuses as $s)
                                                    <option value="{{ $s['value'] }}" {{ $status == $s['value'] ? 'selected' : '' }}>
                                                        {{ $s['label'] }}
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
                                                <a href="{{ route('reports.production.summary') }}" class="btn btn-light">
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

                {{-- Summary Statistics --}}
                <div class="row g-6 mb-6">
                    {{-- Total Orders --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-box fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-gray-800">{{ number_format($summary['total_orders']) }}</span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_orders') }}</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($summary['completion_rate'], 1) }}% {{ __('pagination.completed') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Quantity Summary (KG) --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-arrows-circle fs-2tx text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-5 fw-bold text-success">
                                            {{ number_format($summary['total_output_quantity'], 0) }} kg
                                        </span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.output') }}</span>
                                    </div>
                                    <div class="vr"></div>
                                    <div>
                                        <span class="fs-5 fw-bold text-danger">
                                            {{ number_format($summary['total_input_quantity'], 0) }} kg
                                        </span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.input') }}</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">
                                    {{ __('pagination.yield') }}: {{ number_format($summary['overall_yield'], 1) }}%
                                </span>
                                <span class="text-danger fs-8">
                                    Loss: {{ number_format($summary['total_loss_quantity'], 0) }} kg
                                    ({{ number_format($summary['loss_rate'], 1) }}%)
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Cost Summary --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-dollar fs-2tx text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-5 fw-bold text-info">
                                            {{ currency_symbol() }} {{ number_format($summary['total_output_cost'], 0) }}
                                        </span>
                                        <span class="text-muted fs-8 d-block">Revenue</span>
                                    </div>
                                    <div class="vr"></div>
                                    <div>
                                        <span class="fs-5 fw-bold text-danger">
                                            {{ currency_symbol() }} {{ number_format($summary['total_input_cost'], 0) }}
                                        </span>
                                        <span class="text-muted fs-8 d-block">Batch Cost</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">
                                    {{ currency_symbol() }} {{ number_format($summary['avg_cost_per_order'], 0) }}
                                    {{ __('pagination.per_order') }}
                                </span>
                                <span class="text-muted fs-8">
                                    Cost efficiency: {{ number_format($summary['cost_efficiency'], 1) }}%
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Profit Summary --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $summary['total_profit'] >= 0 ? 'success' : 'danger' }} border border-{{ $summary['total_profit'] >= 0 ? 'success' : 'danger' }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-chart-line fs-2tx text-{{ $summary['total_profit'] >= 0 ? 'success' : 'danger' }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $summary['total_profit'] >= 0 ? 'success' : 'danger' }}">
                                    {{ currency_symbol() }} {{ number_format($summary['total_profit'], 0) }}
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_profit') }}</span>
                                <span class="text-muted fs-8">
                                    {{ currency_symbol() }} {{ number_format($summary['avg_profit_per_order'], 0) }}
                                    {{ __('pagination.per_order') }}
                                </span>
                                <span class="text-muted fs-8">
                                    Margin: {{ number_format($summary['profit_margin'], 1) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Additional Statistics Row --}}
                <div class="row g-6 mb-6">
                    {{-- Quality Metrics --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.quality_metrics') }}</span>
                                        <div class="fs-4 fw-bold text-warning">{{ number_format($summary['quality_acceptance_rate'], 1) }}%</div>
                                        <div class="mt-1">
                                            <span class="badge badge-light-success me-1">
                                                <i class="ki-duotone ki-check-circle fs-2 me-1"></i>
                                                {{ $summary['total_quality_accepted'] }}
                                            </span>
                                            <span class="badge badge-light-danger">
                                                <i class="ki-duotone ki-cross-circle fs-2 me-1"></i>
                                                {{ $summary['total_quality_rejected'] }}
                                            </span>
                                        </div>
                                    </div>
                                    <i class="ki-duotone ki-check-square fs-2tx text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Defective & Waste --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.defective_waste') }}</span>
                                        <div class="d-flex gap-3 mt-1">
                                            <div>
                                                <span class="fs-4 fw-bold text-danger">{{ number_format($summary['total_defective'], 1) }}</span>
                                                <span class="text-muted fs-8 d-block">{{ __('pagination.defective') }}</span>
                                            </div>
                                            <div>
                                                <span class="fs-4 fw-bold text-warning">{{ number_format($summary['total_waste'], 1) }}</span>
                                                <span class="text-muted fs-8 d-block">{{ __('pagination.waste') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <i class="ki-duotone ki-trash fs-2tx text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Metrics --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.payment_summary') }}</span>
                                        <div class="d-flex gap-3 mt-1">
                                            <div>
                                                <span class="fs-4 fw-bold text-success">{{ $summary['orders_with_payment'] }}</span>
                                                <span class="text-muted fs-8 d-block">{{ __('pagination.with_payment') }}</span>
                                            </div>
                                            <div>
                                                <span class="fs-4 fw-bold text-secondary">{{ $summary['orders_without_payment'] }}</span>
                                                <span class="text-muted fs-8 d-block">{{ __('pagination.without_payment') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <i class="ki-duotone ki-wallet fs-2tx text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Time Metrics --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.time_metrics') }}</span>
                                        <div class="fs-4 fw-bold text-primary">{{ number_format($summary['avg_duration_hours'], 1) }}h</div>
                                        <span class="text-muted fs-8">{{ __('pagination.avg_duration') }}</span>
                                    </div>
                                    <i class="ki-duotone ki-clock fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts Section --}}
                <div class="row g-6 mb-6">
                    {{-- Monthly Trends Chart --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.monthly_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyTrendChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Status Distribution --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.status_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="statusChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Top Products & Materials --}}
                <div class="row g-6 mb-6">
                    {{-- Top Produced Products --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-product fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_produced_products') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th class="text-center">Quantity (KG)</th>
                                                <th class="text-center">Revenue</th>
                                                <th class="text-center">{{ __('pagination.orders') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topProducts as $index => $product)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $product->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $product->sku }}</div>
                                                </td>
                                                <td class="text-center fw-bold text-success">
                                                    {{ number_format($product->total_kg, 0) }} kg
                                                    <div class="text-muted fs-8">
                                                        {{ number_format($product->total_quantity, 0) }} bags
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    {{ currency_symbol() }} {{ number_format($product->total_revenue, 0) }}
                                                </td>
                                                <td class="text-center">{{ $product->order_count }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5">
                                                    {{ __('pagination.no_production_data') }}
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Top Consumed Materials --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-barcode fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_consumed_materials') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.material') }}</th>
                                                <th class="text-center">Quantity (KG)</th>
                                                <th class="text-center">Cost</th>
                                                <th class="text-center">{{ __('pagination.orders') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topMaterials as $index => $material)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="fw-bold">{{ $material->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $material->sku }}</div>
                                                </td>
                                                <td class="text-center fw-bold text-danger">
                                                    {{ number_format($material->total_kg, 0) }} kg
                                                </td>
                                                <td class="text-center">
                                                    {{ currency_symbol() }} {{ number_format($material->total_cost, 0) }}
                                                </td>
                                                <td class="text-center">{{ $material->order_count }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5">
                                                    {{ __('pagination.no_production_data') }}
                                                </td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Location Breakdown --}}
                @if($locationBreakdown->count() > 1)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-location fs-2 me-2 text-info">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.location_breakdown') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>{{ __('pagination.location') }}</th>
                                                <th class="text-center">{{ __('pagination.total_orders') }}</th>
                                                <th class="text-center">{{ __('pagination.completed') }}</th>
                                                <th class="text-center">Yield</th>
                                                <th class="text-center">Revenue</th>
                                                <th class="text-center">Batch Cost</th>
                                                <th class="text-center">{{ __('pagination.profit') }}</th>
                                                <th class="text-center">{{ __('pagination.completion_rate') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($locationBreakdown as $location)
                                            @php
                                                $rate = $location->orders > 0
                                                    ? ($location->completed / $location->orders) * 100
                                                    : 0;
                                            @endphp
                                            <tr>
                                                <td class="fw-bold">{{ $location->location_name }}</td>
                                                <td class="text-center">{{ $location->orders }}</td>
                                                <td class="text-center text-success">{{ $location->completed }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $location->yield >= 90 ? 'success' : ($location->yield >= 70 ? 'warning' : 'danger') }}">
                                                        {{ number_format($location->yield, 1) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    {{ currency_symbol() }} {{ number_format($location->revenue, 0) }}
                                                </td>
                                                <td class="text-center">
                                                    {{ currency_symbol() }} {{ number_format($location->total_cost, 0) }}
                                                </td>
                                                <td class="text-center text-{{ $location->profit >= 0 ? 'success' : 'danger' }}">
                                                    {{ currency_symbol() }} {{ number_format($location->profit, 0) }}
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex align-items-center justify-content-center">
                                                        <span class="badge badge-light-{{ $rate >= 70 ? 'success' : ($rate >= 40 ? 'warning' : 'danger') }}">
                                                            {{ number_format($rate, 1) }}%
                                                        </span>
                                                    </div>
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

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Monthly Trends Chart ──────────────────────────────────────
    const monthlyData = @json($monthlyTrends);

    if (monthlyData.length > 0) {
        const months    = monthlyData.map(d => d.month);
        const orders    = monthlyData.map(d => d.orders);
        const completed = monthlyData.map(d => d.completed);
        const profit    = monthlyData.map(d => d.profit);
        const yieldPct  = monthlyData.map(d => d.yield);

        const trendChart = new ApexCharts(document.querySelector("#monthlyTrendChart"), {
            series: [
                { name: 'Total Orders',  data: orders,    type: 'bar'  },
                { name: 'Completed',     data: completed, type: 'bar'  },
                { name: 'Profit',        data: profit,    type: 'line' },
                { name: 'Yield %',       data: yieldPct,  type: 'line' }
            ],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: true },
                stacked: false
            },
            plotOptions: {
                bar: { horizontal: false, columnWidth: '40%' }
            },
            stroke: { width: [0, 0, 3, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: months,
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } }
            },
            yaxis: [
                {
                    title: { text: 'Orders' }
                },
                {
                    opposite: true,
                    title: { text: 'Profit / Yield' },
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0);
                        }
                    }
                }
            ],
            colors: ['#3E97FF', '#50CD89', '#F1416C', '#FFC700'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 2) {
                            return '{{ currency_symbol() }} ' + Number(val).toFixed(2);
                        }
                        if (seriesIndex === 3) {
                            return Number(val).toFixed(2) + '%';
                        }
                        return val + ' orders';
                    }
                }
            }
        });
        trendChart.render();
    }

    // ─── Status Distribution Chart ──────────────────────────────────
    const statusData = @json($statusBreakdown);

    if (statusData.length > 0) {
        const statusChart = new ApexCharts(document.querySelector("#statusChart"), {
            series: statusData.map(s => s.count),
            chart: { type: 'donut', height: 350 },
            labels: statusData.map(s => s.label),
            colors: statusData.map(s => {
                const colors = {
                    'secondary': '#A1A5B7',
                    'warning':   '#FFC700',
                    'success':   '#50CD89',
                    'danger':    '#F1416C',
                    'primary':   '#3E97FF',
                    'info':      '#7239EA'
                };
                return colors[s.color] || '#A1A5B7';
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
                                label: 'Total',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: { y: { formatter: function(val) { return val + ' orders'; } } }
        });
        statusChart.render();
    }
});
</script>
@endpush

@endsection