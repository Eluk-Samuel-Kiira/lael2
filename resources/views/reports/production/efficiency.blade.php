{{-- resources/views/reports/production/efficiency.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_efficiency'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- TOOLBAR --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container"
                         class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('pagination.production_efficiency') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_efficiency') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-success" id="exportEfficiencyBtn">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- FILTERS --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="efficiencyFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                        <input type="date" class="form-control" id="effStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                        <input type="date" class="form-control" id="effEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                        <select class="form-select" id="effLocation">
                                            <option value="">{{ __('pagination.all_locations') }}</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}" @selected((int)$locationId === $location->id)>{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                        <select class="form-select" id="effStatus">
                                            @foreach($statuses as $s)
                                                <option value="{{ $s['value'] }}" @selected($status === $s['value'])>{{ $s['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.variant') }}</label>
                                        <select class="form-select" id="effVariant">
                                            <option value="">{{ __('pagination.all_variants') }}</option>
                                            @foreach($variants as $variant)
                                                <option value="{{ $variant->id }}" @selected((int)$variantId === $variant->id)>{{ $variant->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end gap-2">
                                        <button type="button" class="btn btn-primary flex-grow-1" id="effApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"></i>{{ __('pagination.apply') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="effClearBtn">
                                            <i class="ki-duotone ki-cross fs-2"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══════════ FACEBOOK-STYLE SKELETON ═══════════ --}}
                <div id="effSkeleton">
                    {{-- 4 KPI cards skeleton --}}
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="d-flex justify-content-center mb-3">
                                        <div class="eff-sk eff-sk-circle"></div>
                                    </div>
                                    <div class="d-flex justify-content-center mb-2">
                                        <div class="eff-sk eff-sk-text" style="width:55%;height:32px;"></div>
                                    </div>
                                    <div class="d-flex justify-content-center mb-1">
                                        <div class="eff-sk eff-sk-text" style="width:70%;"></div>
                                    </div>
                                    <div class="d-flex justify-content-center">
                                        <div class="eff-sk eff-sk-text" style="width:45%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>

                    {{-- 3 secondary metrics skeleton --}}
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 3; $i++)
                        <div class="col-md-4">
                            <div class="card card-flush">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="eff-sk eff-sk-text mb-2" style="width:50%;"></div>
                                        <div class="eff-sk eff-sk-text" style="width:35%;height:24px;"></div>
                                    </div>
                                    <div class="eff-sk eff-sk-circle"></div>
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>

                    {{-- 2 charts skeleton --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="eff-sk eff-sk-text" style="width:25%;height:20px;"></div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="eff-sk eff-sk-chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="eff-sk eff-sk-text" style="width:40%;height:20px;"></div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="eff-sk eff-sk-chart"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Table skeleton --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="eff-sk eff-sk-text" style="width:30%;height:20px;"></div>
                                </div>
                                <div class="card-body pt-0">
                                    @for($i = 0; $i < 8; $i++)
                                    <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                        <div class="eff-sk eff-sk-text" style="width:60px;"></div>
                                        <div class="flex-grow-1">
                                            <div class="eff-sk eff-sk-text mb-2" style="width:35%;"></div>
                                            <div class="eff-sk eff-sk-text" style="width:55%;height:8px;"></div>
                                        </div>
                                        <div class="eff-sk eff-sk-badge"></div>
                                        <div class="eff-sk eff-sk-badge"></div>
                                        <div class="eff-sk eff-sk-badge"></div>
                                        <div class="eff-sk eff-sk-text" style="width:80px;"></div>
                                    </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══════════ CONTENT (JS fills) ═══════════ --}}
                <div id="effContent" class="d-none">

                    {{-- 4 summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100" id="kpiYieldCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-chart-line fs-2tx" id="kpiYieldIcon"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <span class="fs-1 fw-bold" id="kpiYield">—</span>
                                    <span class="text-gray-600 fw-semibold">True Yield (kg/kg)</span>
                                    <span class="text-muted fs-8" id="kpiYieldSub"></span>
                                    <span class="text-danger fs-8" id="kpiYieldLoss"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100" id="kpiCostEffCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-dollar fs-2tx" id="kpiCostEffIcon"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <span class="fs-1 fw-bold" id="kpiCostEff">—</span>
                                    <span class="text-gray-600 fw-semibold">Cost Efficiency</span>
                                    <span class="text-muted fs-8" id="kpiCostEffSub"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100" id="kpiQualityCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-check-circle fs-2tx" id="kpiQualityIcon"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <span class="fs-1 fw-bold" id="kpiQuality">—</span>
                                    <span class="text-gray-600 fw-semibold">Quality Rate</span>
                                    <span class="text-muted fs-8" id="kpiQualitySub"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100" id="kpiProfitCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-chart-pie fs-2tx" id="kpiProfitIcon"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <span class="fs-1 fw-bold" id="kpiProfit">—</span>
                                    <span class="text-gray-600 fw-semibold">{{ __('pagination.profit_margin') }}</span>
                                    <span class="text-muted fs-8" id="kpiProfitSub"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 3 secondary metrics --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-info border border-info border-dashed">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.waste_rate') }}</span>
                                        <div class="fs-2 fw-bold" id="kpiWasteRate">—</div>
                                        <span class="text-muted fs-8" id="kpiWasteKg"></span>
                                    </div>
                                    <i class="ki-duotone ki-trash fs-2tx" id="kpiWasteIcon"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.avg_duration') }}</span>
                                        <div class="fs-2 fw-bold text-primary" id="kpiDuration">—</div>
                                    </div>
                                    <i class="ki-duotone ki-clock fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.order_completion') }}</span>
                                        <div class="fs-2 fw-bold text-secondary" id="kpiCompletion">—</div>
                                    </div>
                                    <i class="ki-duotone ki-check-square fs-2tx text-secondary"><span class="path1"></span><span class="path2"></span></i>
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
                                        <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.efficiency_trends') }}</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div id="efficiencyTrendChart" style="height: 350px;"></div>
                                    <div id="efficiencyTrendEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-location fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.efficiency_by_location') }}</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div id="locationEfficiencyChart" style="height: 350px;"></div>
                                    <div id="locationEfficiencyEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Top products --}}
                    <div class="row g-6 mb-6" id="topProductsCard">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-product fs-2 me-2 text-success"><span class="path1"></span><span class="path2"></span></i>
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
                                                    <th class="text-center">Bags</th>
                                                    <th class="text-center">Total KG</th>
                                                    <th class="text-center">{{ __('pagination.profit_margin') }}</th>
                                                    <th class="text-center">{{ __('pagination.quality_rate') }}</th>
                                                    <th class="text-center">Cost/KG</th>
                                                    <th class="text-center">Profit/KG</th>
                                                </tr>
                                            </thead>
                                            <tbody id="topProductsBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Efficiency by order --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center justify-content-between w-100">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                            <h3 class="fw-bold m-0">{{ __('pagination.efficiency_by_order') }}</h3>
                                        </div>
                                        <span class="badge badge-light-primary fs-7" id="effOrderBadge"></span>
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
                                                    <th class="text-center">Yield</th>
                                                    <th class="text-center">Cost Eff.</th>
                                                    <th class="text-center">Quality</th>
                                                    <th class="text-center">Waste</th>
                                                    <th class="text-center">{{ __('pagination.duration') }}</th>
                                                    <th class="text-center">{{ __('pagination.profit') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="efficiencyOrderBody"></tbody>
                                        </table>
                                    </div>
                                    <div id="efficiencyOrderEmpty" class="text-center py-10 d-none">
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between align-items-center d-none" id="effPaginationWrap">
                                        <span class="text-muted fs-7" id="effPaginationInfo"></span>
                                        <nav><ul class="pagination m-0" id="effPagination"></ul></nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* ─── Facebook-style shimmer skeleton ─────────────────────── */
    .eff-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: eff-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .eff-sk-text   { height: 12px; border-radius: 4px; }
    .eff-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .eff-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .eff-sk-chart  { width: 100%; height: 350px; border-radius: 8px; }
    @keyframes eff-shimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    #effSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.EFF_ROUTES = {
        data:   '{{ route("reports.production.efficiency-data") }}',
        export: '{{ route("reports.production.efficiency") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.EFF_BOOTSTRAP = {
        startDate:  @json($startDate),
        endDate:    @json($endDate),
        locationId: @json($locationId),
        variantId:  @json($variantId),
        status:     @json($status),
        perPage:    @json($perPage),
    };
</script>
@include('reports.production.efficiency-scripts')
@endpush