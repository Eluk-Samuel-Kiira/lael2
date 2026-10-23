{{-- resources/views/reports/production/quality-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_quality_analysis'))

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
                                {{ __('pagination.production_quality_analysis') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_quality_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-success" id="exportQualityBtn">
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
                                <form id="qualityFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                        <input type="date" class="form-control" id="qStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                        <input type="date" class="form-control" id="qEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                        <select class="form-select" id="qLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                            <option value=""></option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}" @selected((int)$locationId === $location->id)>{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                        <select class="form-select" id="qStatus" data-control="select2">
                                            @foreach($statuses as $s)
                                                <option value="{{ $s['value'] }}" @selected($status === $s['value'])>{{ $s['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.quality_status') }}</label>
                                        <select class="form-select" id="qQuality" data-control="select2">
                                            @foreach($qualityStatuses as $q)
                                                <option value="{{ $q['value'] }}" @selected($qualityStatus === $q['value'])>{{ $q['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.variant') }}</label>
                                        <select class="form-select" id="qVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                            <option value=""></option>
                                            @foreach($variants as $variant)
                                                <option value="{{ $variant->id }}" @selected((int)$variantId === $variant->id)>
                                                    {{ $variant->name }}{{ $variant->sku ? ' ('.$variant->sku.')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex gap-2">
                                        <button type="button" class="btn btn-primary" id="qApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"></i>{{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="qClearBtn">
                                            <i class="ki-duotone ki-cross fs-2 me-1"></i>{{ __('pagination.clear') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="qSkeleton">
                    <div class="row g-6 mb-6"><div class="col-12">
                        <div class="card"><div class="card-body d-flex justify-content-between align-items-center py-6">
                            <div class="d-flex align-items-center">
                                <div class="q-sk q-sk-circle me-4" style="width:80px;height:80px;"></div>
                                <div>
                                    <div class="q-sk q-sk-text mb-2" style="width:260px;height:20px;"></div>
                                    <div class="q-sk q-sk-text" style="width:320px;"></div>
                                </div>
                            </div>
                            <div class="d-flex gap-4">
                                @for($i = 0; $i < 4; $i++)
                                <div class="text-center">
                                    <div class="q-sk q-sk-text mb-2" style="width:60px;height:24px;"></div>
                                    <div class="q-sk q-sk-text" style="width:80px;"></div>
                                </div>
                                @endfor
                            </div>
                        </div></div>
                    </div></div>

                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card"><div class="card-body">
                            <div class="d-flex justify-content-center mb-3"><div class="q-sk q-sk-circle"></div></div>
                            <div class="q-sk q-sk-text mb-2" style="width:100%;height:24px;"></div>
                            <div class="q-sk q-sk-text" style="width:80%;"></div>
                        </div></div></div>
                        @endfor
                    </div>

                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="q-sk q-sk-chart"></div></div></div></div>
                        <div class="col-lg-4"><div class="card"><div class="card-body"><div class="q-sk q-sk-chart"></div></div></div></div>
                    </div>

                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="q-sk q-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="q-sk q-sk-text mb-2" style="width:35%;"></div>
                                <div class="q-sk q-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="q-sk q-sk-badge"></div>
                            <div class="q-sk q-sk-badge"></div>
                            <div class="q-sk q-sk-badge"></div>
                            <div class="q-sk q-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="qContent" class="d-none">

                    {{-- Overall quality score --}}
                    <div class="row g-6 mb-6"><div class="col-12">
                        <div class="card card-flush" id="overallCard">
                            <div class="card-body d-flex flex-column flex-md-row align-items-center justify-content-between py-6">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-80px me-4">
                                        <span class="symbol-label text-white fs-1 fw-bold" id="overallBadge">—</span>
                                    </div>
                                    <div>
                                        <h3 class="fw-bold mb-1">{{ __('pagination.overall_quality_score') }}</h3>
                                        <div class="text-muted" id="overallSubtitle">—</div>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-4 mt-3 mt-md-0">
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold text-success" id="overallInAcc">—</div>
                                        <span class="text-muted">Input Acceptance</span>
                                        <div class="text-muted fs-8" id="overallInAccSub"></div>
                                    </div>
                                    <div class="vr d-none d-md-block"></div>
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold text-info" id="overallOutApp">—</div>
                                        <span class="text-muted">Output Approval</span>
                                        <div class="text-muted fs-8" id="overallOutAppSub"></div>
                                    </div>
                                    <div class="vr d-none d-md-block"></div>
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold text-danger" id="overallDef">—</div>
                                        <span class="text-muted">Defective Rate</span>
                                        <div class="text-muted fs-8" id="overallDefSub"></div>
                                    </div>
                                    <div class="vr d-none d-md-block"></div>
                                    <div class="text-center">
                                        <div class="fs-3 fw-bold text-warning" id="overallWaste">—</div>
                                        <span class="text-muted">Waste Rate</span>
                                        <div class="text-muted fs-8" id="overallWasteSub"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div></div>

                    {{-- Breakdown cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-enter fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                    <div class="d-flex justify-content-center gap-3">
                                        <div><span class="fs-4 fw-bold text-success" id="bdInAcc">—</span><span class="text-muted fs-8 d-block">Accepted</span></div>
                                        <div><span class="fs-4 fw-bold text-danger" id="bdInRej">—</span><span class="text-muted fs-8 d-block">Rejected</span></div>
                                        <div><span class="fs-4 fw-bold text-secondary" id="bdInPend">—</span><span class="text-muted fs-8 d-block">Pending</span></div>
                                    </div>
                                    <span class="text-gray-600 fw-semibold mt-2">Input Quality (lines)</span>
                                    <span class="text-muted fs-8" id="bdInKg">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-exit fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                    <div class="d-flex justify-content-center gap-3">
                                        <div><span class="fs-4 fw-bold text-success" id="bdOutAcc">—</span><span class="text-muted fs-8 d-block">Approved</span></div>
                                        <div><span class="fs-4 fw-bold text-danger" id="bdOutRej">—</span><span class="text-muted fs-8 d-block">Rejected</span></div>
                                        <div><span class="fs-4 fw-bold text-secondary" id="bdOutPend">—</span><span class="text-muted fs-8 d-block">Pending</span></div>
                                    </div>
                                    <span class="text-gray-600 fw-semibold mt-2">Output Quality (lines)</span>
                                    <span class="text-muted fs-8" id="bdOutKg">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-trash fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i></div>
                                    <div class="d-flex justify-content-center gap-3">
                                        <div><span class="fs-4 fw-bold text-danger" id="bdDefKg">—</span><span class="text-muted fs-8 d-block">Defective (kg)</span></div>
                                        <div><span class="fs-4 fw-bold text-warning" id="bdWasteKg">—</span><span class="text-muted fs-8 d-block">Waste (kg)</span></div>
                                    </div>
                                    <span class="text-gray-600 fw-semibold mt-2">Total Loss</span>
                                    <span class="text-muted fs-8" id="bdLossSub">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-chart-line fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                    <div class="d-flex justify-content-center gap-3">
                                        <div><span class="fs-4 fw-bold text-danger" id="bdInTotKg">—</span><span class="text-muted fs-8 d-block">In (kg)</span></div>
                                        <div><span class="fs-4 fw-bold text-success" id="bdOutTotKg">—</span><span class="text-muted fs-8 d-block">Out (kg)</span></div>
                                    </div>
                                    <span class="text-gray-600 fw-semibold mt-2">Mass Balance</span>
                                    <span class="text-muted fs-8" id="bdMassLoss">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Charts --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.quality_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="qualityTrendChart" style="height: 350px;"></div>
                                <div id="qualityTrendEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-4"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.quality_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="categoryQualityChart" style="height: 350px;"></div>
                                <div id="categoryQualityEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Top products by quality --}}
                    <div class="row g-6 mb-6" id="topQualityCard">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-product fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_products_by_quality') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                    <thead><tr class="fw-bold fs-7 text-gray-800 bg-light">
                                        <th>#</th><th>{{ __('pagination.product') }}</th><th>{{ __('pagination.category') }}</th>
                                        <th class="text-center">Bags</th><th class="text-center">Total KG</th>
                                        <th class="text-center">Defective (kg)</th><th class="text-center">Defective %</th>
                                        <th class="text-center">Quality Score</th><th class="text-center">{{ __('pagination.orders') }}</th>
                                    </tr></thead>
                                    <tbody id="topQualityBody"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Quality by order --}}
                    <div class="row"><div class="col-12"><div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.quality_by_order') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="qOrderBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                <thead><tr class="fw-bold fs-7 text-gray-800 bg-light">
                                    <th>#</th><th>{{ __('pagination.order') }}</th><th>{{ __('pagination.status') }}</th>
                                    <th>{{ __('pagination.location') }}</th>
                                    <th class="text-center">Input kg</th><th class="text-center">Output kg</th>
                                    <th class="text-center">Input Accept</th><th class="text-center">Output Approve</th>
                                    <th class="text-center">Defective %</th><th class="text-center">Waste %</th>
                                    <th class="text-center">Score</th><th class="text-center">Rating</th>
                                </tr></thead>
                                <tbody id="qOrderBody"></tbody>
                            </table></div>
                            <div id="qOrderEmpty" class="text-center py-10 d-none">
                                <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="qPaginationWrap">
                                <span class="text-muted fs-7" id="qPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="qPagination"></ul></nav>
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
    .q-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: q-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .q-sk-text   { height: 12px; border-radius: 4px; }
    .q-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .q-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .q-sk-chart  { width: 100%; height: 350px; border-radius: 8px; }
    @keyframes q-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #qSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.Q_ROUTES = {
        data:   '{{ route("reports.production.quality-analysis-data") }}',
        export: '{{ route("reports.production.quality-analysis") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.Q_BOOTSTRAP = {
        startDate:     @json($startDate),
        endDate:       @json($endDate),
        locationId:    @json($locationId),
        variantId:     @json($variantId),
        status:        @json($status),
        qualityStatus: @json($qualityStatus),
        perPage:       @json($perPage),
    };
</script>
@include('reports.production.quality-analysis-scripts')
@endpush