{{-- resources/views/reports/inventory/movement-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.movement_analysis_report'))

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
                                {{ __('pagination.movement_analysis_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.movement_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="movExportBtn">
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
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="bi bi-funnel fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form id="movFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">{{ __('pagination.date_range') }}</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="movStartDate" value="{{ $startDate }}">
                                    <span class="input-group-text">→</span>
                                    <input type="date" class="form-control" id="movEndDate" value="{{ $endDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('pagination.movement_type') }}</label>
                                <select class="form-select" id="movType">
                                    <option value="all"        @selected($movementType === 'all')>{{ __('pagination.all_types') }}</option>
                                    <option value="fast"       @selected($movementType === 'fast')>{{ __('pagination.fast_moving') }}</option>
                                    <option value="slow"       @selected($movementType === 'slow')>{{ __('pagination.slow_moving') }}</option>
                                    <option value="non-moving" @selected($movementType === 'non-moving')>{{ __('pagination.non_moving') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                <select class="form-select" id="movVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                    <option value=""></option>
                                    @foreach($variants as $v)
                                        <option value="{{ $v->id }}" @selected((int)$variantId === $v->id)>
                                            {{ \Illuminate\Support\Str::limit($v->name, 30) }}{{ $v->sku ? ' (' . $v->sku . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                <select class="form-select" id="movLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('accounting.department') }}</label>
                                <select class="form-select" id="movDepartment" data-control="select2" data-placeholder="{{ __('pagination.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected((int)$departmentId === $dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="movApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="movClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="movSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="mov-sk mov-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="mov-sk mov-sk-text" style="width:55%;height:28px;"></div></div>
                            <div class="d-flex justify-content-center mb-1"><div class="mov-sk mov-sk-text" style="width:70%;"></div></div>
                            <div class="d-flex justify-content-center"><div class="mov-sk mov-sk-text" style="width:45%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="mov-sk mov-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="mov-sk mov-sk-text mb-2" style="width:35%;"></div>
                                <div class="mov-sk mov-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="mov-sk mov-sk-badge"></div>
                            <div class="mov-sk mov-sk-badge"></div>
                            <div class="mov-sk mov-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="movContent" class="d-none">

                    {{-- Stats cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-rocket-takeoff fs-2tx text-success"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="statFast">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Fast Moving</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.high_demand_items') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-speedometer2 fs-2tx text-warning"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="statSlow">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Slow Moving</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.low_demand_items') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-pause-circle fs-2tx text-danger"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="statNon">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Non-Moving</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.no_movement_items') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-box fs-2tx text-primary"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="statTotal">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Total Items</span>
                                    <span class="fs-8 text-muted mt-1" id="statPeriod">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.movement_analysis_details') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="movBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="movTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4" style="width: 12%;">{{ __('pagination.sku') }}</th>
                                    <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                    <th style="width: 10%;" class="text-center">{{ __('pagination.total_movement') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.transactions') }}</th>
                                    <th style="width: 10%;" class="text-center">{{ __('pagination.avg_daily') }}</th>
                                    <th style="width: 12%;">{{ __('pagination.first_movement') }}</th>
                                    <th style="width: 12%;">{{ __('pagination.last_movement') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.days_since_last') }}</th>
                                    <th style="width: 10%;" class="text-center">{{ __('pagination.movement_category') }}</th>
                                </tr></thead>
                                <tbody id="movTableBody"></tbody>
                            </table></div>
                            <div id="movEmpty" class="text-center py-12 d-none">
                                <i class="bi bi-file-earmark-text fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.no_movement_data_found') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="movPaginationWrap">
                                <span class="text-muted fs-7" id="movPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="movPagination"></ul></nav>
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
    .mov-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: mov-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .mov-sk-text   { height: 12px; border-radius: 4px; }
    .mov-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .mov-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes mov-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #movSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script>
    window.MOV_ROUTES = {
        data: '{{ route("reports.inventory.movement-analysis-data") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.MOV_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        movementType: @json($movementType),
        departmentId: @json($departmentId),
        locationId:   @json($locationId),
        variantId:    @json($variantId),
        perPage:      @json($perPage),
    };
</script>
@include('reports.inventory.movement-analysis-scripts')
@endpush