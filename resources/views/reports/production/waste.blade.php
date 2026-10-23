{{-- resources/views/reports/production/waste.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_waste_report'))

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
                                {{ __('pagination.production_waste_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_waste_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-success" id="wExportBtn">
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
                                <form id="wFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                        <input type="date" class="form-control" id="wStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                        <input type="date" class="form-control" id="wEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                        <select class="form-select" id="wLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                            <option value=""></option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}" @selected((int)$locationId === $location->id)>{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                        <select class="form-select" id="wStatus" data-control="select2">
                                            @foreach($statuses as $s)
                                                <option value="{{ $s['value'] }}" @selected($status === $s['value'])>{{ $s['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.waste_type') }}</label>
                                        <select class="form-select" id="wWasteType" data-control="select2">
                                            @foreach($wasteTypes as $w)
                                                <option value="{{ $w['value'] }}" @selected($wasteType === $w['value'])>{{ $w['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.variant') }}</label>
                                        <select class="form-select" id="wVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                            <option value=""></option>
                                            @foreach($variants as $variant)
                                                <option value="{{ $variant->id }}" @selected((int)$variantId === $variant->id)>
                                                    {{ $variant->name }}{{ $variant->sku ? ' ('.$variant->sku.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex gap-2">
                                        <button type="button" class="btn btn-primary" id="wApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"></i>{{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="wClearBtn">
                                            <i class="ki-duotone ki-cross fs-2 me-1"></i>{{ __('pagination.clear') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="wSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="w-sk w-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="w-sk w-sk-text" style="width:55%;height:28px;"></div></div>
                            <div class="d-flex justify-content-center mb-1"><div class="w-sk w-sk-text" style="width:70%;"></div></div>
                            <div class="d-flex justify-content-center"><div class="w-sk w-sk-text" style="width:45%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-3"><div class="card card-flush"><div class="card-body d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <div class="w-sk w-sk-text mb-2" style="width:50%;"></div>
                                <div class="w-sk w-sk-text" style="width:35%;height:24px;"></div>
                            </div>
                            <div class="w-sk w-sk-circle"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body">
                            <div class="row g-6">
                                @for($i = 0; $i < 4; $i++)
                                <div class="col-md-3"><div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                                    <div class="w-sk w-sk-text" style="width:45%;"></div>
                                    <div class="w-sk w-sk-badge"></div>
                                </div></div>
                                @endfor
                            </div>
                        </div></div></div>
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="w-sk w-sk-chart"></div></div></div></div>
                        <div class="col-lg-4"><div class="card"><div class="card-body"><div class="w-sk w-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="w-sk w-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="w-sk w-sk-text mb-2" style="width:35%;"></div>
                                <div class="w-sk w-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="w-sk w-sk-badge"></div>
                            <div class="w-sk w-sk-badge"></div>
                            <div class="w-sk w-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="wContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100" id="wRateCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-trash fs-2tx" id="wRateIcon"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-1 fw-bold" id="wRate">—</span>
                                    <span class="text-gray-600 fw-semibold">Total Waste Rate (kg/kg)</span>
                                    <span class="text-muted fs-8" id="wRateSub">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-enter fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-2 fw-bold text-warning" id="wInRate">—</span>
                                    <span class="text-gray-600 fw-semibold">Input Waste Rate</span>
                                    <span class="text-muted fs-8" id="wInRateSub">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-exit fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-2 fw-bold text-danger" id="wDefRate">—</span>
                                    <span class="text-gray-600 fw-semibold">Defective Rate</span>
                                    <span class="text-muted fs-8" id="wDefRateSub">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush h-100" id="wCostCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-dollar fs-2tx" id="wCostIcon"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-2 fw-bold" id="wCost">—</span>
                                    <span class="text-gray-600 fw-semibold">Waste Cost</span>
                                    <span class="text-muted fs-8" id="wCostSub">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Secondary metrics --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-3"><div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">Good Output</span>
                                    <div class="fs-2 fw-bold text-success" id="wGood">—</div>
                                    <span class="text-muted fs-8" id="wGoodSub">—</span>
                                </div>
                                <i class="ki-duotone ki-check-circle fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                        </div></div>
                        <div class="col-md-3"><div class="card card-flush bg-light-secondary border border-secondary border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">{{ __('pagination.total_orders') }}</span>
                                    <div class="fs-2 fw-bold" id="wOrders">—</div>
                                    <span class="text-muted fs-8" id="wOrdersSub">—</span>
                                </div>
                                <i class="ki-duotone ki-box fs-2tx text-secondary"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                        </div></div>
                        <div class="col-md-3"><div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">Input Waste Cost</span>
                                    <div class="fs-2 fw-bold text-info" id="wInCost">—</div>
                                </div>
                                <i class="ki-duotone ki-enter fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                        </div></div>
                        <div class="col-md-3"><div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="text-muted">Defective Cost</span>
                                    <div class="fs-2 fw-bold text-primary" id="wDefCost">—</div>
                                </div>
                                <i class="ki-duotone ki-exit fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i>
                            </div>
                        </div></div>
                    </div>

                    {{-- Severity distribution --}}
                    <div class="row g-6 mb-6"><div class="col-12"><div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                <h3 class="fw-bold m-0">{{ __('pagination.waste_severity_distribution') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-6" id="wSeverityRow"></div>
                        </div>
                    </div></div></div>

                    {{-- Charts --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.monthly_waste_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="wTrendChart" style="height: 350px;"></div>
                                <div id="wTrendEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-4"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.waste_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="wCatChart" style="height: 350px;"></div>
                                <div id="wCatEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Top products by waste --}}
                    <div class="row g-6 mb-6" id="wTopCard">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-product fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_products_by_waste') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                    <thead><tr class="fw-bold fs-7 text-gray-800 bg-light">
                                        <th>#</th><th>{{ __('pagination.product') }}</th><th>{{ __('pagination.category') }}</th>
                                        <th class="text-center">Input Waste (kg)</th><th class="text-center">Defective (kg)</th>
                                        <th class="text-center">Total Waste (kg)</th><th class="text-center">Rate</th>
                                        <th class="text-center">Waste Cost</th>
                                    </tr></thead>
                                    <tbody id="wTopBody"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Waste by order --}}
                    <div class="row"><div class="col-12"><div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.waste_by_order') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="wOrderBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                <thead><tr class="fw-bold fs-7 text-gray-800 bg-light">
                                    <th>#</th><th>{{ __('pagination.order') }}</th><th>{{ __('pagination.status') }}</th>
                                    <th>{{ __('pagination.location') }}</th>
                                    <th class="text-center">Input Waste (kg)</th><th class="text-center">Input Rate</th>
                                    <th class="text-center">Defective (kg)</th><th class="text-center">Defective Rate</th>
                                    <th class="text-center">Total Waste (kg)</th><th class="text-center">Total Rate</th>
                                    <th class="text-center">Waste Cost</th>
                                </tr></thead>
                                <tbody id="wOrderBody"></tbody>
                            </table></div>
                            <div id="wOrderEmpty" class="text-center py-10 d-none">
                                <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="wPaginationWrap">
                                <span class="text-muted fs-7" id="wPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="wPagination"></ul></nav>
                            </div>
                        </div>
                    </div></div></div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .w-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: w-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .w-sk-text   { height: 12px; border-radius: 4px; }
    .w-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .w-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .w-sk-chart  { width: 100%; height: 350px; border-radius: 8px; }
    @keyframes w-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #wSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.W_ROUTES = {
        data:   '{{ route("reports.production.waste-data") }}',
        export: '{{ route("reports.production.waste") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.W_BOOTSTRAP = {
        startDate:  @json($startDate),
        endDate:    @json($endDate),
        locationId: @json($locationId),
        variantId:  @json($variantId),
        status:     @json($status),
        wasteType:  @json($wasteType),
        perPage:    @json($perPage),
    };
</script>
@include('reports.production.waste-scripts')
@endpush