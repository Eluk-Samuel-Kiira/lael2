{{-- resources/views/reports/production/batch-tracking.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_batch_tracking'))

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
                                {{ __('pagination.production_batch_tracking') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_batch_tracking') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($paginatedBatches->count() > 0)
                            <button class="btn btn-sm btn-success" onclick="exportBatchTracking()">
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
                                <form method="GET" action="{{ route('reports.production.batch-tracking') }}" id="filterForm">
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
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.batch_type') }}</label>
                                            <select class="form-select" name="batch_type" data-control="select2">
                                                @foreach($batchTypes as $b)
                                                    <option value="{{ $b['value'] }}" {{ $batchType == $b['value'] ? 'selected' : '' }}>
                                                        {{ $b['label'] }}
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
                                                <a href="{{ route('reports.production.batch-tracking') }}" class="btn btn-light">
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

                {{-- Batch Summary Cards --}}
                <div class="row g-6 mb-6">
                    {{-- Total Batches --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-bucket fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-primary">{{ number_format($batchSummary['total_batches']) }}</span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_batches') }}</span>
                                <span class="text-muted fs-8">
                                    {{ $batchSummary['unique_batch_numbers'] }} {{ __('pagination.unique_batches') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Produced vs Consumed --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-arrows-circle fs-2tx text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <div>
                                        <span class="fs-4 fw-bold text-success">{{ $batchSummary['produced_batches'] }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.produced') }}</span>
                                    </div>
                                    <div class="vr"></div>
                                    <div>
                                        <span class="fs-4 fw-bold text-danger">{{ $batchSummary['consumed_batches'] }}</span>
                                        <span class="text-muted fs-8 d-block">{{ __('pagination.consumed') }}</span>
                                    </div>
                                </div>
                                <span class="text-gray-600 fw-semibold mt-2">{{ __('pagination.batch_balance') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Batch Quantity --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $batchSummary['net_batch_quantity'] >= 0 ? 'success' : 'danger' }} border border-{{ $batchSummary['net_batch_quantity'] >= 0 ? 'success' : 'danger' }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-calculator fs-2tx text-{{ $batchSummary['net_batch_quantity'] >= 0 ? 'success' : 'danger' }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $batchSummary['net_batch_quantity'] >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($batchSummary['net_batch_quantity'], 1) }}
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.net_batch_quantity') }}</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($batchSummary['total_produced_quantity'], 1) }} / {{ number_format($batchSummary['total_consumed_quantity'], 1) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Batch Cost --}}
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-{{ $batchSummary['net_batch_cost'] >= 0 ? 'success' : 'danger' }} border border-{{ $batchSummary['net_batch_cost'] >= 0 ? 'success' : 'danger' }} border-dashed">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-dollar fs-2tx text-{{ $batchSummary['net_batch_cost'] >= 0 ? 'success' : 'danger' }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-1 fw-bold text-{{ $batchSummary['net_batch_cost'] >= 0 ? 'success' : 'danger' }}">
                                    {{ currency_symbol() }}{{ number_format($batchSummary['net_batch_cost'], 2) }}
                                </span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.net_batch_cost') }}</span>
                                <span class="text-muted fs-8">
                                    {{ $batchSummary['unique_variants'] }} {{ __('pagination.variants_affected') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Batch Status --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.active_batches') }}</span>
                                    <div class="fs-2 fw-bold text-success">{{ $batchStatus['active'] }}</div>
                                </div>
                                <i class="ki-duotone ki-check-circle fs-2tx text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.depleted_batches') }}</span>
                                    <div class="fs-2 fw-bold text-danger">{{ $batchStatus['depleted'] }}</div>
                                </div>
                                <i class="ki-duotone ki-cross-circle fs-2tx text-danger">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.expired_batches') }}</span>
                                    <div class="fs-2 fw-bold text-warning">{{ $batchStatus['expired'] }}</div>
                                </div>
                                <i class="ki-duotone ki-clock fs-2tx text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts Section --}}
                <div class="row g-6 mb-6">
                    {{-- Monthly Batch Trends --}}
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.monthly_batch_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="batchTrendChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Batch by Variant --}}
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.batch_by_variant') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="variantBatchChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Top Batches --}}
                <div class="row g-6 mb-6">
                    {{-- Top Produced Batches --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-exit fs-2 me-2 text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_produced_batches') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.batch_number') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th class="text-center">{{ __('pagination.quantity') }}</th>
                                                <th class="text-center">{{ __('pagination.cost') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topProducedBatches as $index => $batch)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><span class="badge badge-light-success">{{ $batch->batch_number }}</span></td>
                                                <td>{{ $batch->variant_name }}</td>
                                                <td class="text-center fw-bold text-success">+{{ number_format($batch->quantity_change, 1) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($batch->total_cost, 2) }}</td>
                                            </tr>
                                            @endforeach
                                            @if($topProducedBatches->isEmpty())
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    {{ __('pagination.no_batches_found') }}
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Top Consumed Batches --}}
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-enter fs-2 me-2 text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_consumed_batches') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.batch_number') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th class="text-center">{{ __('pagination.quantity') }}</th>
                                                <th class="text-center">{{ __('pagination.cost') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($topConsumedBatches as $index => $batch)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><span class="badge badge-light-danger">{{ $batch->batch_number }}</span></td>
                                                <td>{{ $batch->variant_name }}</td>
                                                <td class="text-center fw-bold text-danger">{{ number_format($batch->quantity_change, 1) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($batch->total_cost, 2) }}</td>
                                            </tr>
                                            @endforeach
                                            @if($topConsumedBatches->isEmpty())
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    {{ __('pagination.no_batches_found') }}
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

                {{-- Batch Logs Table --}}
                @if($paginatedBatches->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.batch_logs') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $paginatedBatches->count() }} {{ __('pagination.of') }} {{ $paginatedBatches->total() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.batch_number') }}</th>
                                                <th>{{ __('pagination.type') }}</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.order') }}</th>
                                                <th class="text-center">{{ __('pagination.quantity_change') }}</th>
                                                <th class="text-center">{{ __('pagination.before') }}</th>
                                                <th class="text-center">{{ __('pagination.after') }}</th>
                                                <th class="text-center">{{ __('pagination.unit_cost') }}</th>
                                                <th class="text-center">{{ __('pagination.total_cost') }}</th>
                                                <th>{{ __('pagination.expiry_date') }}</th>
                                                <th>{{ __('pagination.event_date') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedBatches as $index => $batch)
                                            <tr>
                                                <td>{{ $paginatedBatches->firstItem() + $index }}</td>
                                                <td>
                                                    <span class="badge badge-light-{{ $batch->type_color }}">
                                                        {{ $batch->batch_number }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $batch->type_color }}">
                                                        <i class="ki-duotone {{ $batch->type_icon }} fs-2 me-1"></i>
                                                        {{ $batch->type_label }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $batch->variant_name }}</div>
                                                    <div class="text-muted fs-8">{{ $batch->variant_sku }}</div>
                                                </td>
                                                <td>
                                                    <span class="text-muted">{{ $batch->production_number }}</span>
                                                </td>
                                                <td class="text-center fw-bold text-{{ $batch->type_color }}">
                                                    {{ $batch->type === 'produced' ? '+' : '' }}{{ number_format($batch->quantity_change, 1) }}
                                                </td>
                                                <td class="text-center">{{ number_format($batch->quantity_before, 1) }}</td>
                                                <td class="text-center">{{ number_format($batch->quantity_after, 1) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($batch->unit_cost, 2) }}</td>
                                                <td class="text-center">{{ currency_symbol() }}{{ number_format($batch->total_cost, 2) }}</td>
                                                <td>
                                                    @if($batch->expiry_date)
                                                        @php
                                                            $isExpired = Carbon\Carbon::parse($batch->expiry_date)->lt(now());
                                                        @endphp
                                                        <span class="{{ $isExpired ? 'text-danger' : 'text-success' }}">
                                                            {{ Carbon\Carbon::parse($batch->expiry_date)->format('Y-m-d') }}
                                                            @if($isExpired)
                                                                <i class="ki-duotone ki-cross-circle fs-2 text-danger ms-1"></i>
                                                            @endif
                                                        </span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $batch->event_date ? Carbon\Carbon::parse($batch->event_date)->format('Y-m-d H:i') : '-' }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="12" class="text-end fw-bold">
                                                    {{ __('pagination.total_batches') }}: {{ $paginatedBatches->total() }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $paginatedBatches,
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
                                        <p class="text-muted fs-6">{{ __('pagination.no_batches_found') }}</p>
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
    // ─── Monthly Batch Trends ──────────────────────────────────────
    const monthlyData = @json($batchByMonth);
    
    if (monthlyData.length > 0) {
        const months = monthlyData.map(d => d.month);
        const produced = monthlyData.map(d => d.produced_quantity);
        const consumed = monthlyData.map(d => d.consumed_quantity);
        
        const trendChart = new ApexCharts(document.querySelector("#batchTrendChart"), {
            series: [
                {
                    name: 'Produced',
                    data: produced,
                    type: 'bar'
                },
                {
                    name: 'Consumed',
                    data: consumed,
                    type: 'bar'
                }
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
            dataLabels: { enabled: false },
            xaxis: {
                categories: months,
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: 'Quantity' }
            },
            colors: ['#50CD89', '#F1416C'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return val.toFixed(1);
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
    
    // ─── Batch by Variant Chart ──────────────────────────────────────
    const variantData = @json($batchByVariant);
    
    if (variantData.length > 0) {
        const variants = variantData.map(d => d.variant_name).slice(0, 10);
        const produced = variantData.map(d => d.produced_quantity).slice(0, 10);
        const consumed = variantData.map(d => d.consumed_quantity).slice(0, 10);
        
        const variantChart = new ApexCharts(document.querySelector("#variantBatchChart"), {
            series: [
                {
                    name: 'Produced',
                    data: produced,
                    type: 'bar'
                },
                {
                    name: 'Consumed',
                    data: consumed,
                    type: 'bar'
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
            dataLabels: { enabled: false },
            xaxis: {
                categories: variants,
                labels: { style: { fontSize: '11px' } }
            },
            yaxis: {
                title: { text: 'Quantity' }
            },
            colors: ['#50CD89', '#F1416C'],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return val.toFixed(1);
                    }
                }
            },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            }
        });
        variantChart.render();
    }
});

// ─── Export Function ──────────────────────────────────────────────
function exportBatchTracking() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    
    window.location.href = `/reports/production/batch-tracking/export?${params.toString()}`;
}
</script>
@endpush

@endsection