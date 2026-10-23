{{-- resources/views/reports/production/cost-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_cost_analysis'))

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
                                {{ __('pagination.production_cost_analysis') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_cost_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-success" id="exportCostAnalysisBtn">
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
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="costFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                        <input type="date" class="form-control" id="costStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                        <input type="date" class="form-control" id="costEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                        <select class="form-select" id="costLocation">
                                            <option value="">{{ __('pagination.all_locations') }}</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}" @selected((int)$locationId === $location->id)>
                                                    {{ $location->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                        <select class="form-select" id="costStatus">
                                            @foreach($statuses as $s)
                                                <option value="{{ $s['value'] }}" @selected($status === $s['value'])>
                                                    {{ $s['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.cost_type') }}</label>
                                        <select class="form-select" id="costCostType">
                                            @foreach($costTypes as $c)
                                                <option value="{{ $c['value'] }}" @selected($costType === $c['value'])>
                                                    {{ $c['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.variant') }}</label>
                                        <select class="form-select" id="costVariant">
                                            <option value="">{{ __('pagination.all_variants') }}</option>
                                            @foreach($variants as $variant)
                                                <option value="{{ $variant->id }}" @selected((int)$variantId === $variant->id)>
                                                    {{ $variant->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex gap-2">
                                        <button type="button" class="btn btn-primary" id="costApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                            {{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="costClearBtn">
                                            <i class="ki-duotone ki-cross fs-2 me-1"></i>
                                            {{ __('pagination.clear') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUMMARY CARDS --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-enter fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                                <span class="fs-2 fw-bold text-danger" id="kpiInputCost">—</span>
                                <span class="text-gray-600 fw-semibold">Batch Cost (Input)</span>
                                <span class="text-muted fs-8" id="kpiInputCostSub"></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-exit fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                                <span class="fs-2 fw-bold text-success" id="kpiOutputCost">—</span>
                                <span class="text-gray-600 fw-semibold">Revenue (Output)</span>
                                <span class="text-muted fs-8" id="kpiOutputCostSub"></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush h-100" id="kpiProfitCard">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-chart-line fs-2tx" id="kpiProfitIcon"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                                <span class="fs-2 fw-bold" id="kpiProfit">—</span>
                                <span class="text-gray-600 fw-semibold">{{ __('pagination.total_profit') }}</span>
                                <span class="text-muted fs-8" id="kpiProfitSub"></span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-2">
                                    <i class="ki-duotone ki-arrows-circle fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                                <span class="fs-2 fw-bold text-warning" id="kpiYield">—</span>
                                <span class="text-gray-600 fw-semibold">True Yield (kg/kg)</span>
                                <span class="text-danger fs-8" id="kpiLossSub"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- EFFICIENCY METRICS --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.cost_efficiency') }}</span>
                                    <div class="fs-2 fw-bold text-info" id="kpiCostEfficiency">—</div>
                                    <span class="text-muted fs-8">revenue ÷ batch cost</span>
                                </div>
                                <i class="ki-duotone ki-chart-bar fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush h-100" id="kpiProfitMarginCard">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.profit_margin') }}</span>
                                    <div class="fs-2 fw-bold" id="kpiProfitMargin">—</div>
                                    <span class="text-muted fs-8">profit ÷ revenue</span>
                                </div>
                                <i class="ki-duotone ki-chart-pie fs-2tx" id="kpiProfitMarginIcon"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.input_to_output_ratio') }}</span>
                                    <div class="fs-2 fw-bold text-secondary" id="kpiRatio">—</div>
                                    <span class="text-muted fs-8">revenue ÷ batch cost</span>
                                </div>
                                <i class="ki-duotone ki-arrows-circle fs-2tx text-secondary"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CHARTS --}}
                <div class="row g-6 mb-6">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.monthly_cost_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyCostChart" style="height: 350px;"></div>
                                <div id="monthlyCostEmpty" class="text-center text-muted py-10 d-none">
                                    {{ __('pagination.no_data_available') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.cost_by_status') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="costByStatusChart" style="height: 350px;"></div>
                                <div id="costByStatusEmpty" class="text-center text-muted py-10 d-none">
                                    {{ __('pagination.no_data_available') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CATEGORY COST BREAKDOWN --}}
                <div class="row g-6 mb-6">
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-enter fs-2 me-2 text-danger"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.input_cost_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>{{ __('pagination.category') }}</th>
                                                <th class="text-center">KG</th>
                                                <th class="text-center">{{ __('pagination.total_cost') }}</th>
                                                <th class="text-center">Cost/kg</th>
                                                <th class="text-center">{{ __('pagination.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="inputCategoryBody"></tbody>
                                    </table>
                                </div>
                                <div id="inputCategoryEmpty" class="text-center text-muted py-5 d-none">
                                    {{ __('pagination.no_data_available') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-exit fs-2 me-2 text-success"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.output_cost_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>{{ __('pagination.category') }}</th>
                                                <th class="text-center">KG</th>
                                                <th class="text-center">Revenue</th>
                                                <th class="text-center">Rev/kg</th>
                                                <th class="text-center">{{ __('pagination.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="outputCategoryBody"></tbody>
                                    </table>
                                </div>
                                <div id="outputCategoryEmpty" class="text-center text-muted py-5 d-none">
                                    {{ __('pagination.no_data_available') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- COST PER UNIT TABLE --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-calculator fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.cost_per_unit_analysis') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7" id="costPerUnitBadge"></span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                <th>#</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th class="text-center">Bags</th>
                                                <th class="text-center">Total KG</th>
                                                <th class="text-center">Cost/Bag</th>
                                                <th class="text-center">Cost/KG</th>
                                                <th class="text-center">Price</th>
                                                <th class="text-center">Profit/Bag</th>
                                                <th class="text-center">Profit/KG</th>
                                                <th class="text-center">Margin</th>
                                                <th class="text-center">Orders</th>
                                            </tr>
                                        </thead>
                                        <tbody id="costPerUnitBody"></tbody>
                                    </table>
                                </div>
                                <div id="costPerUnitEmpty" class="text-center py-10 d-none">
                                    <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                    <p class="text-muted fs-6">{{ __('pagination.no_production_data') }}</p>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center"
                                     id="costPerUnitPaginationWrap" style="display:none !important;">
                                    <span class="text-muted fs-7" id="costPerUnitPaginationInfo"></span>
                                    <nav><ul class="pagination m-0" id="costPerUnitPagination"></ul></nav>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.COST_ROUTES = {
        data:   '{{ route("reports.production.cost-analysis-data") }}',
        export: '{{ route("reports.production.cost-analysis") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.COST_BOOTSTRAP = {
        startDate:  @json($startDate),
        endDate:    @json($endDate),
        locationId: @json($locationId),
        variantId:  @json($variantId),
        status:     @json($status),
        costType:   @json($costType),
        perPage:    @json($perPage),
    };
</script>
@include('reports.production.cost-analysis-scripts')
@endpush