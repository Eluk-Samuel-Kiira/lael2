@extends('layouts.app')

@section('title', __('pagination.production_batch_tracking'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar --}}
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
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
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

                {{-- Filter --}}
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
                                        <div class="col-md-2">
                                            <label class="form-label fw-semibold">{{ __('pagination.search') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-magnifier fs-2"></i>
                                                </span>
                                                <input type="text" class="form-control" name="search"
                                                    value="{{ $search }}" placeholder="{{ __('pagination.search_batches') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-12 d-flex justify-content-end gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                                {{ __('pagination.apply') }}
                                            </button>
                                            <a href="{{ route('reports.production.batch-tracking') }}" class="btn btn-light">
                                                <i class="ki-duotone ki-cross fs-2 me-1"></i>
                                                {{ __('pagination.clear') }}
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Primary summary cards --}}
                <div class="row g-6 mb-6">
                    {{-- Total Batches --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-bucket fs-2tx text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-2 fw-bold text-primary">{{ number_format($batchSummary['total_batches']) }}</span>
                                <span class="text-gray-600 fw-semibold fs-7">{{ __('pagination.total_logs') }}</span>
                                <span class="text-muted fs-8">
                                    {{ $batchSummary['unique_batch_numbers'] }} {{ __('pagination.unique_batches') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Produced (KG) --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-exit fs-2tx text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-2 fw-bold text-success">
                                    {{ number_format($batchSummary['total_produced_kg'], 0) }}
                                </span>
                                <span class="text-gray-600 fw-semibold fs-7">Produced (kg)</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($batchSummary['total_produced_quantity'], 0) }} units ·
                                    {{ $batchSummary['produced_batches'] }} logs
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Consumed (KG) --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-enter fs-2tx text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-2 fw-bold text-danger">
                                    {{ number_format($batchSummary['total_consumed_kg'], 0) }}
                                </span>
                                <span class="text-gray-600 fw-semibold fs-7">Consumed (kg)</span>
                                <span class="text-muted fs-8">
                                    {{ number_format($batchSummary['total_consumed_quantity'], 0) }} units ·
                                    {{ $batchSummary['consumed_batches'] }} logs
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Net KG --}}
                    @php $netColor = $batchSummary['net_batch_kg'] >= 0 ? 'success' : 'warning'; @endphp
                    <div class="col-md-6 col-lg-2">
                        <div class="card card-flush bg-light-{{ $netColor }} border border-{{ $netColor }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-calculator fs-2tx text-{{ $netColor }}">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-2 fw-bold text-{{ $netColor }}">
                                    {{ $batchSummary['net_batch_kg'] >= 0 ? '+' : '' }}{{ number_format($batchSummary['net_batch_kg'], 0) }}
                                </span>
                                <span class="text-gray-600 fw-semibold fs-7">Net (kg)</span>
                                <span class="text-muted fs-8">produced − consumed</span>
                            </div>
                        </div>
                    </div>

                    {{-- Produced Cost --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-dollar fs-2tx text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-2 fw-bold text-success">
                                    {{ currency_symbol() }} {{ number_format($batchSummary['total_produced_cost'], 0) }}
                                </span>
                                <span class="text-gray-600 fw-semibold fs-7">{{ __('pagination.produced_cost') }}</span>
                                <span class="text-muted fs-8">
                                    Avg: {{ currency_symbol() }} {{ number_format($batchSummary['avg_unit_cost_per_kg'], 0) }}/kg
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Consumed Cost --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-dollar fs-2tx text-danger">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                                <span class="fs-2 fw-bold text-danger">
                                    {{ currency_symbol() }} {{ number_format($batchSummary['total_consumed_cost'], 0) }}
                                </span>
                                <span class="text-gray-600 fw-semibold fs-7">{{ __('pagination.consumed_cost') }}</span>
                                <span class="text-muted fs-8">
                                    {{ $batchSummary['unique_purchase_orders'] }} POs
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Secondary: batch status + supplier/variant counts --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-success">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted fs-7">{{ __('pagination.active_batches') }}</span>
                                    <div class="fs-3 fw-bold text-success">{{ $batchStatus['active'] }}</div>
                                </div>
                                <i class="ki-duotone ki-check-circle fs-2tx text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-danger">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted fs-7">{{ __('pagination.depleted_batches') }}</span>
                                    <div class="fs-3 fw-bold text-danger">{{ $batchStatus['depleted'] }}</div>
                                </div>
                                <i class="ki-duotone ki-cross-circle fs-2tx text-danger">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-warning">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted fs-7">{{ __('pagination.expired_batches') }}</span>
                                    <div class="fs-3 fw-bold text-warning">{{ $batchStatus['expired'] }}</div>
                                </div>
                                <i class="ki-duotone ki-clock fs-2tx text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-info">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted fs-7">{{ __('pagination.suppliers_involved') }}</span>
                                    <div class="fs-3 fw-bold text-info">{{ $batchSummary['unique_suppliers'] }}</div>
                                    <span class="text-muted fs-8">
                                        {{ $batchSummary['unique_variants'] }} {{ __('pagination.variants') }}
                                    </span>
                                </div>
                                <i class="ki-duotone ki-shop fs-2tx text-info">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Charts --}}
                <div class="row g-6 mb-6">
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

                {{-- Top produced / consumed --}}
                <div class="row g-6 mb-6">
                    {{-- Top produced --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
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
                                                <th class="text-center">Qty</th>
                                                <th class="text-center">KG</th>
                                                <th class="text-center">Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topProducedBatches as $index => $batch)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><span class="badge badge-light-success">{{ $batch->batch_number }}</span></td>
                                                <td>
                                                    <div class="fw-bold">{{ $batch->variant_name }}</div>
                                                    <div class="text-muted fs-8">
                                                        {{ $batch->variant_sku }}
                                                        @if($batch->weight > 0) · {{ $batch->weight }} kg @endif
                                                    </div>
                                                </td>
                                                <td class="text-center">{{ number_format($batch->quantity_change, 0) }}</td>
                                                <td class="text-center fw-bold text-success">
                                                    {{ number_format($batch->quantity_change_kg, 0) }}
                                                </td>
                                                <td class="text-center">
                                                    {{ currency_symbol() }} {{ number_format($batch->total_cost, 0) }}
                                                </td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="6" class="text-center text-muted py-3">{{ __('pagination.no_batches_found') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Top consumed --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
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
                                                <th class="text-center">Qty</th>
                                                <th class="text-center">KG</th>
                                                <th class="text-center">Cost</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($topConsumedBatches as $index => $batch)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td><span class="badge badge-light-danger">{{ $batch->batch_number }}</span></td>
                                                <td>
                                                    <div class="fw-bold">{{ $batch->variant_name }}</div>
                                                    <div class="text-muted fs-8">
                                                        {{ $batch->variant_sku }}
                                                        @if($batch->weight > 0) · {{ $batch->weight }} kg @endif
                                                    </div>
                                                </td>
                                                <td class="text-center text-danger fw-bold">
                                                    {{ number_format(abs($batch->quantity_change), 0) }}
                                                </td>
                                                <td class="text-center text-danger fw-bold">
                                                    {{ number_format(abs($batch->quantity_change_kg), 0) }}
                                                </td>
                                                <td class="text-center">
                                                    {{ currency_symbol() }} {{ number_format($batch->total_cost, 0) }}
                                                </td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="6" class="text-center text-muted py-3">{{ __('pagination.no_batches_found') }}</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Batch by variant --}}
                @if($batchByVariant->count() > 0)
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-cube-2 fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h3 class="fw-bold m-0">{{ __('pagination.batch_by_variant') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">
                                {{ $batchByVariant->count() }} {{ __('pagination.variants') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                <thead>
                                    <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                        <th class="ps-4">{{ __('pagination.product') }}</th>
                                        <th class="text-center">Weight/Unit</th>
                                        <th class="text-center">{{ __('pagination.unique_batches') }}</th>
                                        <th class="text-center">{{ __('pagination.produced_count') }}</th>
                                        <th class="text-center">{{ __('pagination.consumed_count') }}</th>
                                        <th class="text-end">Produced (kg)</th>
                                        <th class="text-end">Consumed (kg)</th>
                                        <th class="text-end">Net (kg)</th>
                                        <th class="text-end">Cost / kg</th>
                                        <th class="text-end pe-4">{{ __('pagination.net_cost') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($batchByVariant as $row)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-gray-800">{{ $row->variant_name }}</div>
                                            @if($row->variant_sku)
                                                <small class="text-muted">SKU: {{ $row->variant_sku }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center text-muted">
                                            {{ $row->weight > 0 ? $row->weight . ' kg' : '—' }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ $row->unique_batches }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-success">{{ $row->produced_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-danger">{{ $row->consumed_count }}</span>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            +{{ number_format($row->produced_kg, 0) }}
                                        </td>
                                        <td class="text-end fw-bold text-danger">
                                            -{{ number_format($row->consumed_kg, 0) }}
                                        </td>
                                        <td class="text-end fw-bold {{ $row->net_kg >= 0 ? 'text-success' : 'text-warning' }}">
                                            {{ $row->net_kg >= 0 ? '+' : '' }}{{ number_format($row->net_kg, 0) }}
                                        </td>
                                        <td class="text-end text-muted">
                                            {{ currency_symbol() }} {{ number_format($row->avg_unit_cost_per_kg, 0) }}
                                        </td>
                                        <td class="text-end pe-4 fw-bold {{ $row->net_cost >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ currency_symbol() }} {{ number_format($row->net_cost, 0) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Monthly breakdown --}}
                @if($batchByMonth->count() > 0)
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h3 class="fw-bold m-0">{{ __('pagination.monthly_batch_breakdown') }}</h3>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                <thead>
                                    <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                        <th class="ps-4">{{ __('pagination.period') }}</th>
                                        <th class="text-center">{{ __('pagination.produced_logs') }}</th>
                                        <th class="text-center">{{ __('pagination.consumed_logs') }}</th>
                                        <th class="text-end">Produced (kg)</th>
                                        <th class="text-end">Consumed (kg)</th>
                                        <th class="text-end">Net (kg)</th>
                                        <th class="text-end">{{ __('pagination.produced_cost') }}</th>
                                        <th class="text-end pe-4">{{ __('pagination.consumed_cost') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($batchByMonth as $row)
                                    <tr>
                                        <td class="ps-4 fw-semibold">{{ $row->month }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-success">{{ $row->produced_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-danger">{{ $row->consumed_count }}</span>
                                        </td>
                                        <td class="text-end text-success fw-bold">
                                            +{{ number_format($row->produced_kg, 0) }}
                                        </td>
                                        <td class="text-end text-danger fw-bold">
                                            -{{ number_format($row->consumed_kg, 0) }}
                                        </td>
                                        <td class="text-end fw-bold {{ $row->net_kg >= 0 ? 'text-success' : 'text-warning' }}">
                                            {{ $row->net_kg >= 0 ? '+' : '' }}{{ number_format($row->net_kg, 0) }}
                                        </td>
                                        <td class="text-end text-success">
                                            {{ currency_symbol() }} {{ number_format($row->produced_cost, 0) }}
                                        </td>
                                        <td class="text-end pe-4 text-danger">
                                            {{ currency_symbol() }} {{ number_format($row->consumed_cost, 0) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Batch logs (main table) --}}
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
                                                <th class="ps-4 min-w-60px">#</th>
                                                <th class="min-w-140px">{{ __('pagination.event_date') }}</th>
                                                <th class="min-w-200px">{{ __('pagination.batch_product') }}</th>
                                                <th class="min-w-140px">{{ __('pagination.type') }}</th>
                                                <th class="min-w-180px">{{ __('pagination.source') }}</th>
                                                <th class="text-center min-w-160px">Movement</th>
                                                <th class="text-center min-w-140px">Balance (kg)</th>
                                                <th class="text-end min-w-120px">Cost/kg</th>
                                                <th class="text-end min-w-120px">{{ __('pagination.total_cost') }}</th>
                                                <th class="min-w-120px">{{ __('pagination.expiry') }}</th>
                                                <th class="pe-4 min-w-140px">{{ __('pagination.performed_by') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($paginatedBatches as $index => $batch)
                                                @php
                                                    $isNegative = $batch->quantity_change < 0;
                                                    $isPositive = $batch->quantity_change > 0;
                                                    $rowAccent  = $batch->type === 'produced' ? 'success'
                                                                : ($batch->type === 'depleted' ? 'danger'
                                                                : ($batch->type === 'received' ? 'info' : 'secondary'));
                                                    $expired      = $batch->expiry_date && \Carbon\Carbon::parse($batch->expiry_date)->isPast();
                                                    $expiringSoon = !$expired
                                                        && $batch->expiry_date
                                                        && \Carbon\Carbon::parse($batch->expiry_date)->diffInDays(now()) <= 30;
                                                @endphp
                                                <tr>
                                                    <td class="ps-4 text-muted fw-semibold">
                                                        {{ $paginatedBatches->firstItem() + $index }}
                                                    </td>

                                                    <td>
                                                        <div class="fw-semibold text-gray-800">
                                                            {{ $batch->event_date ? \Carbon\Carbon::parse($batch->event_date)->format('d M Y') : '-' }}
                                                        </div>
                                                        <small class="text-muted">
                                                            {{ $batch->event_date ? \Carbon\Carbon::parse($batch->event_date)->format('H:i') : '' }}
                                                        </small>
                                                    </td>

                                                    <td>
                                                        <div class="d-flex flex-column">
                                                            <span class="fw-bold text-gray-800">{{ $batch->variant_name ?? 'N/A' }}</span>
                                                            @if($batch->variant_sku)
                                                                <small class="text-muted">SKU: {{ $batch->variant_sku }}</small>
                                                            @endif
                                                            @if($batch->weight > 0)
                                                                <small class="text-muted">{{ $batch->weight }} kg/unit</small>
                                                            @endif
                                                            <span class="badge badge-light-{{ $rowAccent }} badge-sm mt-1 align-self-start">
                                                                {{ $batch->batch_number }}
                                                            </span>
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <span class="badge badge-light-{{ $batch->type_color }} d-inline-flex align-items-center gap-1">
                                                            <i class="ki-duotone {{ $batch->type_icon }} fs-5"></i>
                                                            {{ $batch->type_label }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        @if($batch->source_type === 'purchase_order')
                                                            <div class="d-flex flex-column">
                                                                <span class="badge badge-light-info badge-sm align-self-start">
                                                                    <i class="ki-duotone ki-shop fs-5 me-1"></i>
                                                                    {{ $batch->source_label }}
                                                                </span>
                                                                @if($batch->source_sub)
                                                                    <small class="text-muted mt-1">{{ $batch->source_sub }}</small>
                                                                @endif
                                                            </div>
                                                        @elseif($batch->source_type === 'production_order')
                                                            <div class="d-flex flex-column">
                                                                <span class="badge badge-light-primary badge-sm align-self-start">
                                                                    <i class="ki-duotone ki-industry fs-5 me-1"></i>
                                                                    {{ $batch->source_label }}
                                                                </span>
                                                                @if($batch->source_sub)
                                                                    <small class="text-muted mt-1">{{ $batch->source_sub }}</small>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>

                                                    {{-- Movement: units + kg --}}
                                                    <td class="text-center">
                                                        <div class="d-flex flex-column align-items-center">
                                                            <span class="fw-bold {{ $isPositive ? 'text-success' : ($isNegative ? 'text-danger' : 'text-muted') }}">
                                                                {{ $isPositive ? '+' : '' }}{{ number_format($batch->quantity_change, 0) }}
                                                                <small class="text-muted">units</small>
                                                            </span>
                                                            <span class="{{ $isPositive ? 'text-success' : 'text-danger' }} fs-7">
                                                                {{ $isPositive ? '+' : '' }}{{ number_format($batch->quantity_change_kg, 0) }} kg
                                                            </span>
                                                        </div>
                                                    </td>

                                                    {{-- Balance (kg) --}}
                                                    <td class="text-center">
                                                        <span class="badge badge-light-{{ $batch->quantity_after > 0 ? 'success' : 'danger' }}">
                                                            {{ number_format($batch->quantity_after_kg, 0) }} kg
                                                        </span>
                                                        @if($batch->weight > 0 && $batch->quantity_after != $batch->quantity_after_kg)
                                                            <div class="text-muted fs-8">
                                                                ({{ number_format($batch->quantity_after, 0) }} units)
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td class="text-end">
                                                        <span class="text-gray-800">
                                                            {{ currency_symbol() }} {{ number_format($batch->unit_cost_per_kg, 0) }}
                                                        </span>
                                                    </td>

                                                    <td class="text-end fw-bold">
                                                        <span class="text-{{ $batch->type === 'produced' ? 'success' : 'danger' }}">
                                                            {{ currency_symbol() }} {{ number_format($batch->total_cost, 0) }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        @if($batch->expiry_date)
                                                            <span class="badge badge-light-{{ $expired ? 'danger' : ($expiringSoon ? 'warning' : 'success') }}">
                                                                {{ \Carbon\Carbon::parse($batch->expiry_date)->format('d M Y') }}
                                                            </span>
                                                            @if($expired)
                                                                <small class="text-danger d-block mt-1">Expired</small>
                                                            @elseif($expiringSoon)
                                                                <small class="text-warning d-block mt-1">Expiring soon</small>
                                                            @endif
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>

                                                    <td class="pe-4">
                                                        @if($batch->performed_by)
                                                            <div class="d-flex align-items-center gap-2">
                                                                <div class="symbol symbol-25px symbol-circle">
                                                                    <div class="symbol-label bg-light-primary">
                                                                        <span class="fs-8 fw-bold text-primary">
                                                                            {{ strtoupper(substr($batch->performed_by, 0, 1)) }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <span class="text-gray-700 fs-7">{{ $batch->performed_by }}</span>
                                                            </div>
                                                        @else
                                                            <span class="text-muted">System</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-light fw-bold">
                                            <tr>
                                                <td colspan="5" class="ps-4 text-end">
                                                    {{ __('pagination.totals') }}:
                                                </td>
                                                <td class="text-center">
                                                    <span class="text-success">
                                                        +{{ number_format($paginatedBatches->sum(fn($b) => $b->quantity_change_kg > 0 ? $b->quantity_change_kg : 0), 0) }} kg
                                                    </span>
                                                    <span class="text-muted mx-1">/</span>
                                                    <span class="text-danger">
                                                        {{ number_format($paginatedBatches->sum(fn($b) => $b->quantity_change_kg < 0 ? $b->quantity_change_kg : 0), 0) }} kg
                                                    </span>
                                                </td>
                                                <td></td>
                                                <td></td>
                                                <td class="text-end">
                                                    {{ currency_symbol() }} {{ number_format($paginatedBatches->sum('total_cost'), 0) }}
                                                </td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator'   => $paginatedBatches,
                                        'pageName'    => 'page',
                                        'perPageName' => 'per_page',
                                        'showPerPage' => true,
                                    ])
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
    // ─── Monthly Batch Trends (KG) ─────────────────────────────────
    const monthlyData = @json($batchByMonth);

    if (monthlyData.length > 0) {
        const months    = monthlyData.map(d => d.month);
        const produced  = monthlyData.map(d => d.produced_kg);
        const consumed  = monthlyData.map(d => d.consumed_kg);
        const netKg     = monthlyData.map(d => d.net_kg);

        const trendChart = new ApexCharts(document.querySelector("#batchTrendChart"), {
            series: [
                { name: 'Produced (kg)', data: produced, type: 'bar'  },
                { name: 'Consumed (kg)', data: consumed, type: 'bar'  },
                { name: 'Net (kg)',      data: netKg,    type: 'line' }
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
            stroke: { width: [0, 0, 3], curve: 'smooth' },
            dataLabels: { enabled: false },
            xaxis: {
                categories: months,
                labels: { rotate: -45, trim: true, style: { fontSize: '11px' } }
            },
            yaxis: [
                {
                    title: { text: 'Quantity (kg)' },
                    labels: { formatter: v => Number(v).toFixed(0) }
                },
                {
                    opposite: true,
                    title: { text: 'Net (kg)' },
                    labels: { formatter: v => Number(v).toFixed(0) }
                }
            ],
            colors: ['#50CD89', '#F1416C', '#3E97FF'],
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: v => Number(v).toFixed(1) + ' kg' }
            },
            legend: { position: 'top', horizontalAlign: 'center' }
        });
        trendChart.render();
    }

    // ─── Batch by Variant (top 10, KG) ─────────────────────────────
    const variantData = @json($batchByVariant);

    if (variantData.length > 0) {
        const top = variantData.slice(0, 10);
        const variants   = top.map(d => d.variant_name);
        const producedKg = top.map(d => d.produced_kg);
        const consumedKg = top.map(d => d.consumed_kg);

        const variantChart = new ApexCharts(document.querySelector("#variantBatchChart"), {
            series: [
                { name: 'Produced (kg)', data: producedKg, type: 'bar' },
                { name: 'Consumed (kg)', data: consumedKg, type: 'bar' }
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
                title: { text: 'Quantity (kg)' },
                labels: { formatter: v => Number(v).toFixed(0) }
            },
            colors: ['#50CD89', '#F1416C'],
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: v => Number(v).toFixed(1) + ' kg' }
            },
            legend: { position: 'bottom', horizontalAlign: 'center' }
        });
        variantChart.render();
    }
});

// ─── Export ────────────────────────────────────────────────────────
function exportBatchTracking() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    window.location.href = `/reports/production/batch-tracking/export?${params.toString()}`;
}
</script>
@endpush

@endsection