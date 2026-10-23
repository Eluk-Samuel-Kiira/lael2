{{-- resources/views/reports/production/inventory-impact.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_inventory_impact'))

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
                                {{ __('pagination.production_inventory_impact') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_inventory_impact') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-success" id="exportImpactBtn">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
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
                                <form id="impactFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                        <input type="date" class="form-control" id="impStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                        <input type="date" class="form-control" id="impEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                        <select class="form-select" id="impLocation">
                                            <option value="">{{ __('pagination.all_locations') }}</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}" @selected((int)$locationId === $location->id)>{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                        <select class="form-select" id="impStatus">
                                            @foreach($statuses as $s)
                                                <option value="{{ $s['value'] }}" @selected($status === $s['value'])>{{ $s['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.variant') }}</label>
                                        <select class="form-select" id="impVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                            <option value=""></option>
                                            @foreach($variants as $variant)
                                                <option value="{{ $variant->id }}" @selected((int)$variantId === $variant->id)>
                                                    {{ $variant->name }}{{ $variant->sku ? ' ('.$variant->sku.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end gap-2">
                                        <button type="button" class="btn btn-primary flex-grow-1" id="impApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"></i>{{ __('pagination.apply') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="impClearBtn">
                                            <i class="ki-duotone ki-cross fs-2"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="impSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="d-flex justify-content-center mb-3"><div class="imp-sk imp-sk-circle"></div></div>
                                    <div class="d-flex justify-content-center mb-2"><div class="imp-sk imp-sk-text" style="width:55%;height:28px;"></div></div>
                                    <div class="d-flex justify-content-center mb-1"><div class="imp-sk imp-sk-text" style="width:70%;"></div></div>
                                    <div class="d-flex justify-content-center"><div class="imp-sk imp-sk-text" style="width:45%;"></div></div>
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 3; $i++)
                        <div class="col-md-4">
                            <div class="card card-flush">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="imp-sk imp-sk-text mb-2" style="width:50%;"></div>
                                        <div class="imp-sk imp-sk-text" style="width:35%;height:24px;"></div>
                                    </div>
                                    <div class="imp-sk imp-sk-circle"></div>
                                </div>
                            </div>
                        </div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="imp-sk imp-sk-chart"></div></div></div></div>
                        <div class="col-lg-4"><div class="card"><div class="card-body"><div class="imp-sk imp-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="card"><div class="card-body">
                                @for($i = 0; $i < 8; $i++)
                                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                    <div class="imp-sk imp-sk-text" style="width:60px;"></div>
                                    <div class="flex-grow-1">
                                        <div class="imp-sk imp-sk-text mb-2" style="width:35%;"></div>
                                        <div class="imp-sk imp-sk-text" style="width:55%;height:8px;"></div>
                                    </div>
                                    <div class="imp-sk imp-sk-badge"></div>
                                    <div class="imp-sk imp-sk-badge"></div>
                                    <div class="imp-sk imp-sk-badge"></div>
                                    <div class="imp-sk imp-sk-text" style="width:80px;"></div>
                                </div>
                                @endfor
                            </div></div>
                        </div>
                    </div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="impContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100" id="kpiNetKgCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-arrows-circle fs-2tx" id="kpiNetKgIcon"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <span class="fs-2 fw-bold" id="kpiNetKg">—</span>
                                    <span class="text-gray-600 fw-semibold">Net Inventory Change</span>
                                    <span class="text-muted fs-8" id="kpiNetKgSub"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100" id="kpiNetValueCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-dollar fs-2tx" id="kpiNetValueIcon"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <span class="fs-2 fw-bold" id="kpiNetValue">—</span>
                                    <span class="text-gray-600 fw-semibold">Net Value Change</span>
                                    <span class="text-muted fs-8" id="kpiNetValueSub"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100" id="kpiYieldCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-chart-line fs-2tx" id="kpiYieldIcon"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <span class="fs-2 fw-bold" id="kpiYield">—</span>
                                    <span class="text-gray-600 fw-semibold">True Yield (kg/kg)</span>
                                    <span class="text-danger fs-8" id="kpiYieldLoss"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-duotone ki-repeat fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i>
                                    </div>
                                    <span class="fs-2 fw-bold text-info" id="kpiTurnover">—</span>
                                    <span class="text-gray-600 fw-semibold">Inventory Turnover (kg/kg)</span>
                                    <span class="text-muted fs-8" id="kpiTurnoverSub"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Secondary metrics --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">Total Defective</span>
                                        <div class="fs-2 fw-bold text-danger" id="kpiDefective">—</div>
                                    </div>
                                    <i class="ki-duotone ki-cross-circle fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">Total Waste</span>
                                        <div class="fs-2 fw-bold text-warning" id="kpiWaste">—</div>
                                    </div>
                                    <i class="ki-duotone ki-trash fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-success border border-success border-dashed">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.order_completion') }}</span>
                                        <div class="fs-2 fw-bold text-success" id="kpiCompletion">—</div>
                                    </div>
                                    <i class="ki-duotone ki-check-square fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i>
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
                                        <h3 class="fw-bold m-0">{{ __('pagination.monthly_inventory_impact') }}</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div id="monthlyImpactChart" style="height: 350px;"></div>
                                    <div id="monthlyImpactEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.impact_by_category') }}</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div id="categoryImpactChart" style="height: 350px;"></div>
                                    <div id="categoryImpactEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Top consumed / produced --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6">
                            <div class="card h-100">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-enter fs-2 me-2 text-danger"><span class="path1"></span><span class="path2"></span></i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.top_consumed_products') }}</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                            <thead>
                                                <tr class="fw-bold fs-7 text-gray-800 bg-light">
                                                    <th>#</th>
                                                    <th>{{ __('pagination.product') }}</th>
                                                    <th class="text-center">Bags</th>
                                                    <th class="text-center">Total KG</th>
                                                    <th class="text-center">Cost</th>
                                                    <th class="text-center">{{ __('pagination.orders') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="consumedBody"></tbody>
                                        </table>
                                    </div>
                                    <div id="consumedEmpty" class="text-center text-muted py-5 d-none">{{ __('pagination.no_data_available') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card h-100">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-exit fs-2 me-2 text-success"><span class="path1"></span><span class="path2"></span></i>
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
                                                    <th class="text-center">Bags</th>
                                                    <th class="text-center">Total KG</th>
                                                    <th class="text-center">Revenue</th>
                                                    <th class="text-center">{{ __('pagination.orders') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="producedBody"></tbody>
                                        </table>
                                    </div>
                                    <div id="producedEmpty" class="text-center text-muted py-5 d-none">{{ __('pagination.no_data_available') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Net impact by product --}}
                    <div class="row g-6 mb-6" id="netImpactCard">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-balance fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                        <h3 class="fw-bold m-0">{{ __('pagination.net_inventory_impact_by_product') }}</h3>
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
                                                    <th class="text-center">Consumed (kg)</th>
                                                    <th class="text-center">Produced (kg)</th>
                                                    <th class="text-center">Net (kg)</th>
                                                    <th class="text-center">Net Value</th>
                                                    <th class="text-center">{{ __('pagination.impact_type') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="netImpactBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Impact by order --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center justify-content-between w-100">
                                        <div class="d-flex align-items-center">
                                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                            <h3 class="fw-bold m-0">{{ __('pagination.inventory_impact_by_order') }}</h3>
                                        </div>
                                        <span class="badge badge-light-primary fs-7" id="impactOrderBadge"></span>
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
                                                    <th class="text-center">In (kg)</th>
                                                    <th class="text-center">Out (kg)</th>
                                                    <th class="text-center">Net (kg)</th>
                                                    <th class="text-center">Batch Cost</th>
                                                    <th class="text-center">Revenue</th>
                                                    <th class="text-center">Net Value</th>
                                                    <th class="text-center">Defective (kg)</th>
                                                    <th class="text-center">Waste (kg)</th>
                                                </tr>
                                            </thead>
                                            <tbody id="impactOrderBody"></tbody>
                                        </table>
                                    </div>
                                    <div id="impactOrderEmpty" class="text-center py-10 d-none">
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between align-items-center d-none" id="impPaginationWrap">
                                        <span class="text-muted fs-7" id="impPaginationInfo"></span>
                                        <nav><ul class="pagination m-0" id="impPagination"></ul></nav>
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
    .imp-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: imp-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .imp-sk-text   { height: 12px; border-radius: 4px; }
    .imp-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .imp-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .imp-sk-chart  { width: 100%; height: 350px; border-radius: 8px; }
    @keyframes imp-shimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    #impSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.IMP_ROUTES = {
        data:   '{{ route("reports.production.inventory-impact-data") }}',
        export: '{{ route("reports.production.inventory-impact") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.IMP_BOOTSTRAP = {
        startDate:  @json($startDate),
        endDate:    @json($endDate),
        locationId: @json($locationId),
        variantId:  @json($variantId),
        status:     @json($status),
        perPage:    @json($perPage),
    };
</script>
@include('reports.production.inventory-impact-scripts')
@endpush