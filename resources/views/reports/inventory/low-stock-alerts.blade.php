{{-- resources/views/reports/inventory/low-stock-alerts.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.low_stock_alerts'))

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
                                {{ __('pagination.low_stock_alerts') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.low_stock_alerts') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="lsExportBtn">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
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
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="lsFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                        <select class="form-select" id="lsLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                            <option value=""></option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('accounting.department') }}</label>
                                        <select class="form-select" id="lsDepartment" data-control="select2" data-placeholder="{{ __('pagination.all_departments') }}">
                                            <option value=""></option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}" @selected((int)$departmentId === $dept->id)>{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                        <select class="form-select" id="lsVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                            <option value=""></option>
                                            @foreach($variants as $v)
                                                <option value="{{ $v->id }}" @selected((int)$variantId === $v->id)>
                                                    {{ $v->name }}{{ $v->sku ? ' (' . $v->sku . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.severity_level') }}</label>
                                        <select class="form-select" id="lsSeverity">
                                            <option value="all"      @selected($severity === 'all')>{{ __('pagination.all') }}</option>
                                            <option value="critical" @selected($severity === 'critical')>{{ __('pagination.critical') }}</option>
                                            <option value="warning"  @selected($severity === 'warning')>{{ __('pagination.warning') }}</option>
                                            <option value="low"      @selected($severity === 'low')>{{ __('pagination.low') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-primary" id="lsApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                                            {{ __('accounting.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="lsClearBtn">
                                            <i class="ki-duotone ki-cross fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                                            {{ __('accounting.clear_filters') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="lsSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="ls-sk ls-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="ls-sk ls-sk-text" style="width:55%;height:28px;"></div></div>
                            <div class="d-flex justify-content-center"><div class="ls-sk ls-sk-text" style="width:70%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="ls-sk ls-sk-chart"></div></div></div></div>
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="ls-sk ls-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="ls-sk ls-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="ls-sk ls-sk-text mb-2" style="width:35%;"></div>
                                <div class="ls-sk ls-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="ls-sk ls-sk-badge"></div>
                            <div class="ls-sk ls-sk-badge"></div>
                            <div class="ls-sk ls-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="lsContent" class="d-none">

                   {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-solid ki-shield-cross fs-2tx text-danger"></i>
                                    </div>
                                    <span class="fs-1 fw-bold text-danger" id="lsCritical">—</span>
                                    <span class="text-muted">{{ __('pagination.critical_alerts') }}</span>
                                    <span class="badge badge-light-danger mt-2">{{ __('pagination.needs_immediate_action') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="bi bi-exclamation-triangle-fill fs-2tx text-warning"></i>
                                    </div>
                                    <span class="fs-1 fw-bold text-warning" id="lsWarning">—</span>
                                    <span class="text-muted">{{ __('pagination.warning_alerts') }}</span>
                                    <span class="badge badge-light-warning mt-2">{{ __('pagination.should_review_soon') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-solid ki-archive fs-2tx text-primary"></i>
                                    </div>
                                    <span class="fs-1 fw-bold text-primary" id="lsTotal">—</span>
                                    <span class="text-muted">{{ __('pagination.total_alerts') }}</span>
                                    <span class="badge badge-light-primary mt-2">{{ __('pagination.total_low_stock_items') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card bg-light-info border border-info border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2">
                                        <i class="ki-solid ki-dollar fs-2tx text-info"></i>
                                    </div>
                                    <span class="fs-1 fw-bold text-info" id="lsValueAtRisk">—</span>
                                    <span class="text-muted">{{ __('pagination.value_at_risk') }}</span>
                                    <span class="badge badge-light-info mt-2">{{ __('pagination.potential_loss_if_not_replenished') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Charts --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.severity_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="lsSeverityChart" style="height: 320px;"></div>
                                <div id="lsSeverityEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.department_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="lsDeptChart" style="height: 320px;"></div>
                                <div id="lsDeptEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.low_stock_items') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="lsBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="lsTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4" style="width: 10%;">{{ __('pagination.sku') }}</th>
                                    <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                    <th style="width: 12%;">{{ __('pagination.department') }}</th>
                                    <th style="width: 12%;">{{ __('pagination.location') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.current_stock') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.threshold') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.deficit') }}</th>
                                    <th style="width: 10%;" class="text-center">{{ __('pagination.severity') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.urgency') }}</th>
                                    <th style="width: 6%;" class="text-center">{{ __('pagination.source') }}</th>
                                </tr></thead>
                                <tbody id="lsTableBody"></tbody>
                            </table></div>
                            <div id="lsEmpty" class="text-center py-10 d-none">
                                <i class="ki-duotone ki-check-circle fs-4tx text-success mb-4"><span class="path1"></span><span class="path2"></span></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_low_stock_items') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.all_stock_levels_are_adequate') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="lsPaginationWrap">
                                <span class="text-muted fs-7" id="lsPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="lsPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Legend --}}
                    <div class="card-footer text-muted fs-7 mt-3">
                        <i class="ki-duotone ki-information-4 fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                        <strong>{{ __('pagination.legend') }}:</strong>
                        <span class="badge badge-light-danger mx-1">{{ __('pagination.critical') }}</span> {{ __('pagination.below_30_percent') }}
                        <span class="badge badge-light-warning mx-1">{{ __('pagination.warning') }}</span> {{ __('pagination.below_60_percent') }}
                        <span class="badge badge-light-info mx-1">{{ __('pagination.low') }}</span> {{ __('pagination.below_80_percent') }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .ls-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: ls-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .ls-sk-text   { height: 12px; border-radius: 4px; }
    .ls-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .ls-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .ls-sk-chart  { width: 100%; height: 320px; border-radius: 8px; }
    @keyframes ls-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #lsSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.LS_ROUTES = {
        data: '{{ route("reports.inventory.low-stock-alerts-data") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.LS_BOOTSTRAP = {
        departmentId: @json($departmentId),
        locationId:   @json($locationId),
        variantId:    @json($variantId),
        severity:     @json($severity),
        perPage:      @json($perPage),
    };
</script>
@include('reports.inventory.low-stock-alerts-scripts')
@endpush