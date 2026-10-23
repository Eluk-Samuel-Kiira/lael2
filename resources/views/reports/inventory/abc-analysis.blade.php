{{-- resources/views/reports/inventory/abc-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.abc_analysis'))

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
                                {{ __('pagination.abc_analysis') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.abc_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="abcExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
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
                        <form id="abcFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">{{ __('pagination.date_range') }}</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="abcStartDate" value="{{ $startDate }}">
                                    <span class="input-group-text">→</span>
                                    <input type="date" class="form-control" id="abcEndDate" value="{{ $endDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                <select class="form-select" id="abcVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
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
                                <select class="form-select" id="abcLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">{{ __('accounting.department') }}</label>
                                <select class="form-select" id="abcDepartment" data-control="select2" data-placeholder="{{ __('pagination.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected((int)$departmentId === $dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="abcApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="abcClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="abcSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 3; $i++)
                        <div class="col-md-6 col-lg-4"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="abc-sk abc-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="abc-sk abc-sk-text" style="width:55%;height:28px;"></div></div>
                            <div class="d-flex justify-content-center"><div class="abc-sk abc-sk-text" style="width:70%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="abc-sk abc-sk-chart"></div></div></div></div>
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="abc-sk abc-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body"><div class="abc-sk abc-sk-chart" style="height:350px;"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="abc-sk abc-sk-text" style="width:40px;"></div>
                            <div class="abc-sk abc-sk-badge"></div>
                            <div class="flex-grow-1">
                                <div class="abc-sk abc-sk-text mb-2" style="width:35%;"></div>
                                <div class="abc-sk abc-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="abc-sk abc-sk-text" style="width:100px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="abcContent" class="d-none">

                    {{-- Category cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-star-fill fs-2tx text-danger"></i></div>
                                    <span class="fs-4 fw-bold text-danger">Category A</span>
                                    <span class="fs-3 fw-bold mt-2" id="catAValue">—</span>
                                    <div class="text-gray-600 fw-semibold fs-7 mt-1" id="catACount">—</div>
                                    <div class="progress mt-3" style="height: 10px;">
                                        <div class="progress-bar bg-danger" id="catABar" style="width:0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-award-fill fs-2tx text-warning"></i></div>
                                    <span class="fs-4 fw-bold text-warning">Category B</span>
                                    <span class="fs-3 fw-bold mt-2" id="catBValue">—</span>
                                    <div class="text-gray-600 fw-semibold fs-7 mt-1" id="catBCount">—</div>
                                    <div class="progress mt-3" style="height: 10px;">
                                        <div class="progress-bar bg-warning" id="catBBar" style="width:0%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-moon-stars-fill fs-2tx text-success"></i></div>
                                    <span class="fs-4 fw-bold text-success">Category C</span>
                                    <span class="fs-3 fw-bold mt-2" id="catCValue">—</span>
                                    <div class="text-gray-600 fw-semibold fs-7 mt-1" id="catCCount">—</div>
                                    <div class="progress mt-3" style="height: 10px;">
                                        <div class="progress-bar bg-success" id="catCBar" style="width:0%"></div>
                                    </div>
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
                                    <h3 class="fw-bold m-0">{{ __('pagination.abc_value_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="abcValueChart" style="height: 320px;"></div>
                                <div id="abcValueEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-bar-chart fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.abc_item_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="abcCountChart" style="height: 320px;"></div>
                                <div id="abcCountEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Pareto chart --}}
                    <div class="row g-6 mb-6" id="abcParetoCard">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.pareto_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="abcParetoChart" style="height: 350px;"></div>
                                <div id="abcParetoEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.abc_analysis_details') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="abcBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="abcTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4" style="width:5%;">#</th>
                                    <th style="width:10%;" class="text-center">{{ __('pagination.abc_category') }}</th>
                                    <th style="width:12%;">{{ __('pagination.sku') }}</th>
                                    <th style="width:18%;">{{ __('pagination.product') }}</th>
                                    <th style="width:12%;">{{ __('pagination.department') }}</th>
                                    <th style="width:12%;" class="text-end">{{ __('pagination.inventory_value') }}</th>
                                    <th style="width:10%;" class="text-center">{{ __('pagination.value_percentage') }}</th>
                                    <th style="width:10%;" class="text-center">{{ __('pagination.cumulative_percentage') }}</th>
                                    <th style="width:11%;">{{ __('pagination.recommendation') }}</th>
                                </tr></thead>
                                <tbody id="abcTableBody"></tbody>
                                <tfoot class="bg-light d-none" id="abcTableFooter">
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">{{ __('pagination.page_total') }}:</td>
                                        <td class="text-end fw-bold text-primary" id="ftPageValue">—</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table></div>
                            <div id="abcEmpty" class="text-center py-12 d-none">
                                <i class="bi bi-file-earmark-text fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.no_inventory_items_for_abc_analysis') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="abcPaginationWrap">
                                <span class="text-muted fs-7" id="abcPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="abcPagination"></ul></nav>
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
    .abc-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: abc-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .abc-sk-text   { height: 12px; border-radius: 4px; }
    .abc-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .abc-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .abc-sk-chart  { width: 100%; height: 320px; border-radius: 8px; }
    @keyframes abc-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #abcSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.ABC_ROUTES = {
        data: '{{ route("reports.inventory.abc-analysis-data") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.ABC_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        departmentId: @json($departmentId),
        locationId:   @json($locationId),
        variantId:    @json($variantId),
        perPage:      @json($perPage),
    };
</script>
@include('reports.inventory.abc-analysis-scripts')
@endpush