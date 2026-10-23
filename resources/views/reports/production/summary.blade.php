{{-- resources/views/reports/production/summary.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_summary'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- ─── TOOLBAR ─────────────────────────────────────── --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container"
                         class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
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
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-success" id="exportSummaryBtn">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
                            <a href="{{ route('production-orders.index') }}" class="btn btn-sm btn-primary">
                                <i class="ki-duotone ki-plus fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.new_production_order') }}</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- ─── FILTERS ─────────────────────────────────────── --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="summaryFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                        <input type="date" class="form-control" id="summaryStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                        <input type="date" class="form-control" id="summaryEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                        <select class="form-select" id="summaryLocation">
                                            <option value="">{{ __('pagination.all_locations') }}</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}" @selected((int)$locationId === $location->id)>
                                                    {{ $location->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                        <select class="form-select" id="summaryStatus">
                                            @foreach($statuses as $s)
                                                <option value="{{ $s['value'] }}" @selected($status === $s['value'])>
                                                    {{ $s['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex gap-2">
                                        <button type="button" class="btn btn-primary" id="summaryApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                            {{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="summaryClearBtn">
                                            <i class="ki-duotone ki-cross fs-2 me-1"></i>
                                            {{ __('pagination.clear') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── LOADING SKELETON ────────────────────────────── --}}
                <div id="summaryLoading" class="d-none">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                            <div class="col-md-6 col-lg-3">
                                <div class="card card-flush h-100">
                                    <div class="card-body">
                                        <div class="skeleton skeleton-text" style="width:60%;height:28px;"></div>
                                        <div class="skeleton skeleton-text mt-3" style="width:40%;"></div>
                                        <div class="skeleton skeleton-text" style="width:80%;"></div>
                                    </div>
                                </div>
                            </div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8">
                            <div class="card"><div class="card-body">
                                <div class="skeleton" style="height:350px;width:100%;"></div>
                            </div></div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card"><div class="card-body">
                                <div class="skeleton" style="height:350px;width:100%;"></div>
                            </div></div>
                        </div>
                    </div>
                </div>

                {{-- ─── CONTENT (JS fills every value) ─────────────── --}}
                <div id="summaryContent" class="d-none">

                    {{-- Row 1: KPI cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-box fs-2tx text-primary">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                    <span class="fs-1 fw-bold text-gray-800" id="kpiTotalOrders">—</span>
                                    <span class="text-gray-600 fw-semibold">{{ __('pagination.total_orders') }}</span>
                                    <span class="text-muted fs-8" id="kpiCompletionRate"></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-arrows-circle fs-2tx text-success">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                    <div class="d-flex justify-content-center gap-3">
                                        <div>
                                            <span class="fs-5 fw-bold text-success" id="kpiOutputKg">—</span>
                                            <span class="text-muted fs-8 d-block">{{ __('pagination.output') }}</span>
                                        </div>
                                        <div class="vr"></div>
                                        <div>
                                            <span class="fs-5 fw-bold text-danger" id="kpiInputKg">—</span>
                                            <span class="text-muted fs-8 d-block">{{ __('pagination.input') }}</span>
                                        </div>
                                    </div>
                                    <span class="text-gray-600 fw-semibold mt-2">
                                        {{ __('pagination.yield') }}: <span id="kpiYield">—</span>
                                    </span>
                                    <span class="text-danger fs-8" id="kpiLoss"></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-dollar fs-2tx text-info">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                    <div class="d-flex justify-content-center gap-3">
                                        <div>
                                            <span class="fs-5 fw-bold text-info" id="kpiRevenue">—</span>
                                            <span class="text-muted fs-8 d-block">Revenue</span>
                                        </div>
                                        <div class="vr"></div>
                                        <div>
                                            <span class="fs-5 fw-bold text-danger" id="kpiBatchCost">—</span>
                                            <span class="text-muted fs-8 d-block">Batch Cost</span>
                                        </div>
                                    </div>
                                    <span class="text-gray-600 fw-semibold mt-2" id="kpiAvgCost"></span>
                                    <span class="text-muted fs-8" id="kpiCostEfficiency"></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100"
                                 id="kpiProfitCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-chart-line fs-2tx" id="kpiProfitIcon">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                    <span class="fs-1 fw-bold" id="kpiProfit">—</span>
                                    <span class="text-gray-600 fw-semibold">{{ __('pagination.total_profit') }}</span>
                                    <span class="text-muted fs-8" id="kpiAvgProfit"></span>
                                    <span class="text-muted fs-8" id="kpiMargin"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Row 2: Secondary stats --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted">{{ __('pagination.quality_metrics') }}</span>
                                            <div class="fs-4 fw-bold text-warning" id="kpiQualityRate">—</div>
                                            <div class="mt-1 d-flex gap-2">
                                                <span class="badge badge-light-success">
                                                    <i class="ki-duotone ki-check-circle fs-2 me-1"></i>
                                                    <span id="kpiAccepted">0</span>
                                                </span>
                                                <span class="badge badge-light-danger">
                                                    <i class="ki-duotone ki-cross-circle fs-2 me-1"></i>
                                                    <span id="kpiRejected">0</span>
                                                </span>
                                            </div>
                                        </div>
                                        <i class="ki-duotone ki-check-square fs-2tx text-warning">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted">{{ __('pagination.defective_waste') }}</span>
                                            <div class="d-flex gap-3 mt-1">
                                                <div>
                                                    <span class="fs-4 fw-bold text-danger" id="kpiDefective">0</span>
                                                    <span class="text-muted fs-8 d-block">{{ __('pagination.defective') }}</span>
                                                </div>
                                                <div>
                                                    <span class="fs-4 fw-bold text-warning" id="kpiWaste">0</span>
                                                    <span class="text-muted fs-8 d-block">{{ __('pagination.waste') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <i class="ki-duotone ki-trash fs-2tx text-danger">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted">{{ __('pagination.payment_summary') }}</span>
                                            <div class="d-flex gap-3 mt-1">
                                                <div>
                                                    <span class="fs-4 fw-bold text-success" id="kpiWithPayment">0</span>
                                                    <span class="text-muted fs-8 d-block">{{ __('pagination.with_payment') }}</span>
                                                </div>
                                                <div>
                                                    <span class="fs-4 fw-bold text-secondary" id="kpiWithoutPayment">0</span>
                                                    <span class="text-muted fs-8 d-block">{{ __('pagination.without_payment') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <i class="ki-duotone ki-wallet fs-2tx text-success">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-muted">{{ __('pagination.time_metrics') }}</span>
                                            <div class="fs-4 fw-bold text-primary" id="kpiAvgDuration">—</div>
                                            <span class="text-muted fs-8">{{ __('pagination.avg_duration') }}</span>
                                        </div>
                                        <i class="ki-duotone ki-clock fs-2tx text-primary">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Charts --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8">
                            <div class="card h-100">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.monthly_trends') }}</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div id="monthlyTrendChart" style="height: 350px;"></div>
                                    <div id="monthlyTrendEmpty" class="text-center text-muted py-10 d-none">
                                        <i class="ki-duotone ki-chart fs-4tx text-gray-400 mb-3">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <div>{{ __('pagination.no_data_available') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card h-100">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.status_distribution') }}</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div id="statusChart" style="height: 350px;"></div>
                                    <div id="statusChartEmpty" class="text-center text-muted py-10 d-none">
                                        <i class="ki-duotone ki-chart fs-4tx text-gray-400 mb-3">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <div>{{ __('pagination.no_data_available') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Top products / materials --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6">
                            <div class="card h-100">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-product fs-2 me-2 text-success">
                                            <span class="path1"></span><span class="path2"></span>
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
                                            <tbody id="topProductsBody"></tbody>
                                        </table>
                                    </div>
                                    <div id="topProductsEmpty" class="text-center text-muted py-10 d-none">
                                        {{ __('pagination.no_production_data') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card h-100">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-barcode fs-2 me-2 text-danger">
                                            <span class="path1"></span><span class="path2"></span>
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
                                            <tbody id="topMaterialsBody"></tbody>
                                        </table>
                                    </div>
                                    <div id="topMaterialsEmpty" class="text-center text-muted py-10 d-none">
                                        {{ __('pagination.no_production_data') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Location breakdown (hidden by JS when only 1 location) --}}
                    <div class="row mb-6 d-none" id="locationBreakdownCard">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-location fs-2 me-2 text-info">
                                            <span class="path1"></span><span class="path2"></span>
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
                                            <tbody id="locationBreakdownBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>{{-- /summaryContent --}}
            </div>
        </div>
    </div>
</div>

@include('reports.production.drawer')
@endsection

@push('styles')
<style>
    .skeleton {
        display: inline-block;
        background: linear-gradient(90deg, #f1f1f4 0%, #e9e9ed 50%, #f1f1f4 100%);
        background-size: 200% 100%;
        animation: skeleton-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .skeleton-text { height: 12px; margin: 4px 0; display: block; }
    @keyframes skeleton-shimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
</style>
@endpush

@push('scripts')
<script>
    window.REPORT_ROUTES = {
        summaryData: '{{ route("reports.production.summary-data") }}',
        export:      '{{ route("reports.production.export") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.REPORT_BOOTSTRAP = {
        startDate:  @json($startDate),
        endDate:    @json($endDate),
        locationId: @json($locationId),
        status:     @json($status),
    };
</script>
@include('reports.production.summary-scripts')
@endpush