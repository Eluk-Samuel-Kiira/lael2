{{-- resources/views/reports/inventory/adjustments.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_adjustments_report'))

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
                                {{ __('pagination.inventory_adjustments_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_adjustments') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="adjExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="window.print()">
                                <i class="bi bi-printer fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.print') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="row mb-6"><div class="col-12"><div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="bi bi-funnel fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form id="adjFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">{{ __('pagination.date_range') }}</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="adjStartDate" value="{{ $startDate }}">
                                    <span class="input-group-text">→</span>
                                    <input type="date" class="form-control" id="adjEndDate" value="{{ $endDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                <select class="form-select" id="adjVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                    <option value=""></option>
                                    @foreach($variants as $v)
                                        <option value="{{ $v->id }}" @selected((int)$variantId === $v->id)>
                                            {{ \Illuminate\Support\Str::limit($v->name, 30) }}{{ $v->sku ? ' (' . $v->sku . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                <select class="form-select" id="adjLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('accounting.department') }}</label>
                                <select class="form-select" id="adjDepartment" data-control="select2" data-placeholder="{{ __('pagination.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected((int)$departmentId === $dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="adjApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="adjClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="adjSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 5; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="adj-sk adj-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="adj-sk adj-sk-text" style="width:55%;height:28px;"></div></div>
                            <div class="d-flex justify-content-center"><div class="adj-sk adj-sk-text" style="width:70%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="adj-sk adj-sk-chart"></div></div></div></div>
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="adj-sk adj-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="adj-sk adj-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="adj-sk adj-sk-text mb-2" style="width:35%;"></div>
                                <div class="adj-sk adj-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="adj-sk adj-sk-badge"></div>
                            <div class="adj-sk adj-sk-badge"></div>
                            <div class="adj-sk adj-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="adjContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-arrow-left-right fs-2tx text-primary"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumTotal">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Total Adjustments</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-arrow-repeat fs-2tx text-info"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumQtyChanged">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Total Quantity Changed</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush h-100" id="sumNetCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-graph-up fs-2tx" id="sumNetIcon"></i></div>
                                    <span class="fs-1 fw-bold" id="sumNet">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Net Change</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-arrow-up-circle fs-2tx text-success"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumIncrease">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Increases</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-arrow-down-circle fs-2tx text-danger"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumDecrease">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Decreases</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Charts --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-pie-chart fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.adjustment_type_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="adjTypeChart" style="height: 320px;"></div>
                                <div id="adjTypeEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-bar-chart-line fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.daily_adjustment_trend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="adjDailyChart" style="height: 320px;"></div>
                                <div id="adjDailyEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.adjustment_history') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="adjBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="adjTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4" style="width: 12%;">{{ __('pagination.date_time') }}</th>
                                    <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                    <th style="width: 10%;">{{ __('pagination.sku') }}</th>
                                    <th style="width: 12%;">{{ __('pagination.department') }}</th>
                                    <th style="width: 10%;">{{ __('pagination.location') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.before') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.after') }}</th>
                                    <th style="width: 10%;" class="text-center">{{ __('pagination.change') }}</th>
                                    <th style="width: 10%;">{{ __('pagination.reason') }}</th>
                                    <th style="width: 10%;">{{ __('pagination.adjusted_by') }}</th>
                                    <th style="width: 2%;"></th>
                                </tr></thead>
                                <tbody id="adjTableBody"></tbody>
                                <tfoot class="bg-light d-none" id="adjTableFooter">
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">{{ __('pagination.current_page') }}:</td>
                                        <td class="text-center fw-bold" id="ftBefore">—</td>
                                        <td class="text-center fw-bold" id="ftAfter">—</td>
                                        <td class="text-center fw-bold" id="ftNet">—</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table></div>
                            <div id="adjEmpty" class="text-center py-12 d-none">
                                <i class="bi bi-file-earmark-text fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_adjustments_found') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.no_adjustments_in_date_range') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="adjPaginationWrap">
                                <span class="text-muted fs-7" id="adjPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="adjPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═════════ DRAWER (reason + notes + detail) ═════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="adjDrawer" style="width: 620px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-info-circle fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="adjDrawerLabel">Adjustment Detail</h4>
                <span class="text-white opacity-75 fs-7" id="adjDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="adjDrawerSkeleton">
            <div class="adj-sk adj-sk-text mb-3" style="width:60%;"></div>
            <div class="adj-sk adj-sk-text mb-3" style="width:40%;"></div>
            <div class="adj-sk adj-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="adjDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .adj-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: adj-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .adj-sk-text   { height: 12px; border-radius: 4px; }
    .adj-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .adj-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .adj-sk-chart  { width: 100%; height: 320px; border-radius: 8px; }
    @keyframes adj-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #adjSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .adj-row { cursor: pointer; }
    .adj-row:hover { background-color: #f9f9f9; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.ADJ_ROUTES = {
        data:   '{{ route("reports.inventory.adjustments-data") }}',
        detail: '{{ route("reports.inventory.adjustments-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.ADJ_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        departmentId: @json($departmentId),
        locationId:   @json($locationId),
        variantId:    @json($variantId),
        perPage:      @json($perPage),
    };
</script>
@include('reports.inventory.adjustments-scripts')
@endpush