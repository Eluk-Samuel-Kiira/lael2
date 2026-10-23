{{-- resources/views/reports/inventory/valuation.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_valuation_report'))

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
                                {{ __('pagination.inventory_valuation_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_valuation') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="valExportBtn">
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
                        <form id="valFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('pagination.valuation_method') }}</label>
                                <select class="form-select" id="valMethod">
                                    <option value="cost"             @selected($valuationMethod === 'cost')>{{ __('pagination.cost_method') }}</option>
                                    <option value="fifo"             @selected($valuationMethod === 'fifo')>{{ __('pagination.fifo_method') }}</option>
                                    <option value="lifo"             @selected($valuationMethod === 'lifo')>{{ __('pagination.lifo_method') }}</option>
                                    <option value="weighted_average" @selected($valuationMethod === 'weighted_average')>{{ __('pagination.weighted_average_method') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                <select class="form-select" id="valVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
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
                                <select class="form-select" id="valLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('accounting.department') }}</label>
                                <select class="form-select" id="valDepartment" data-control="select2" data-placeholder="{{ __('pagination.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected((int)$departmentId === $dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="valApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="valClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="valSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="val-sk val-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="val-sk val-sk-text" style="width:55%;height:28px;"></div></div>
                            <div class="d-flex justify-content-center mb-1"><div class="val-sk val-sk-text" style="width:70%;"></div></div>
                            <div class="d-flex justify-content-center"><div class="val-sk val-sk-text" style="width:45%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="val-sk val-sk-chart"></div></div></div></div>
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="val-sk val-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="val-sk val-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="val-sk val-sk-text mb-2" style="width:35%;"></div>
                                <div class="val-sk val-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="val-sk val-sk-badge"></div>
                            <div class="val-sk val-sk-badge"></div>
                            <div class="val-sk val-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="valContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-box fs-2tx text-primary"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumTotalItems">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Total Items</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.items_in_stock') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-upc-scan fs-2tx text-info"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumTotalQty">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Total Quantity</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.units_in_stock') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-currency-dollar fs-2tx text-success"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumTotalValue">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Total Value</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.current_inventory_value') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-graph-up fs-2tx text-danger"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumPotentialProfit">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Potential Profit</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.potential_profit_margin') }}</span>
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
                                    <h3 class="fw-bold m-0">{{ __('pagination.value_by_department') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="valDeptChart" style="height: 320px;"></div>
                                <div id="valDeptEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-bar-chart fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.value_by_location') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="valLocChart" style="height: 320px;"></div>
                                <div id="valLocEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.inventory_valuation_details') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="valBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="valTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4" style="width: 10%;">{{ __('pagination.sku') }}</th>
                                    <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                    <th style="width: 10%;">{{ __('pagination.department') }}</th>
                                    <th style="width: 10%;">{{ __('pagination.location') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.quantity') }}</th>
                                    <th style="width: 10%;" class="text-end">{{ __('pagination.unit_cost') }}</th>
                                    <th style="width: 10%;" class="text-end">{{ __('pagination.unit_price') }}</th>
                                    <th style="width: 10%;" class="text-end">{{ __('pagination.total_value') }}</th>
                                    <th style="width: 10%;" class="text-end">{{ __('pagination.potential_profit') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.profit_margin') }}</th>
                                </tr></thead>
                                <tbody id="valTableBody"></tbody>
                                <tfoot class="bg-light d-none" id="valTableFooter">
                                    <tr>
                                        <td colspan="7" class="text-end fw-bold">{{ __('pagination.page_total') }}:</td>
                                        <td class="text-end fw-bold text-success" id="ftPageValue">—</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table></div>
                            <div id="valEmpty" class="text-center py-12 d-none">
                                <i class="bi bi-file-earmark-text fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.no_valuation_data_found') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="valPaginationWrap">
                                <span class="text-muted fs-7" id="valPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="valPagination"></ul></nav>
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
    .val-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: val-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .val-sk-text   { height: 12px; border-radius: 4px; }
    .val-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .val-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .val-sk-chart  { width: 100%; height: 320px; border-radius: 8px; }
    @keyframes val-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #valSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.VAL_ROUTES = {
        data: '{{ route("reports.inventory.valuation-data") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.VAL_BOOTSTRAP = {
        departmentId:    @json($departmentId),
        locationId:      @json($locationId),
        variantId:       @json($variantId),
        valuationMethod: @json($valuationMethod),
        perPage:         @json($perPage),
    };
</script>
@include('reports.inventory.valuation-scripts')
@endpush