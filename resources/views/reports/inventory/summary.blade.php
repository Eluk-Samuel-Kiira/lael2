{{-- resources/views/reports/inventory/summary.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_summary'))

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
                                {{ __('pagination.inventory_summary') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="invExportBtn">
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
                                <form id="invFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('accounting.date_range') }}</label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="invStartDate" value="{{ $startDate }}">
                                            <span class="input-group-text">→</span>
                                            <input type="date" class="form-control" id="invEndDate" value="{{ $endDate }}">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                        <select class="form-select" id="invLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                            <option value=""></option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('accounting.department') }}</label>
                                        <select class="form-select" id="invDepartment" data-control="select2" data-placeholder="{{ __('pagination.all_departments') }}">
                                            <option value=""></option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}" @selected((int)$departmentId === $dept->id)>{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                        <select class="form-select" id="invVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                            <option value=""></option>
                                            @foreach($variants as $v)
                                                <option value="{{ $v->id }}" @selected((int)$variantId === $v->id)>
                                                    {{ $v->name }}{{ $v->sku ? ' (' . $v->sku . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('accounting.status') }}</label>
                                        <select class="form-select" id="invStatus">
                                            <option value="all" @selected($isActive === 'all')>{{ __('pagination.all_status') }}</option>
                                            <option value="1"   @selected($isActive === '1')>{{ __('accounting.active') }}</option>
                                            <option value="0"   @selected($isActive === '0')>{{ __('accounting.inactive') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end gap-2">
                                        <button type="button" class="btn btn-primary flex-grow-1" id="invApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            <span class="d-none d-sm-inline">{{ __('accounting.apply') }}</span>
                                            <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                        </button>
                                        <button type="button" class="btn btn-light" id="invClearBtn" title="{{ __('accounting.clear') }}">
                                            <i class="ki-duotone ki-cross fs-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="invSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 6; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="inv-sk inv-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="inv-sk inv-sk-text" style="width:55%;height:24px;"></div></div>
                            <div class="d-flex justify-content-center"><div class="inv-sk inv-sk-text" style="width:70%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="inv-sk inv-sk-chart"></div></div></div></div>
                        <div class="col-lg-4"><div class="card"><div class="card-body"><div class="inv-sk inv-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="inv-sk inv-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="inv-sk inv-sk-text mb-2" style="width:35%;"></div>
                                <div class="inv-sk inv-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="inv-sk inv-sk-badge"></div>
                            <div class="inv-sk inv-sk-badge"></div>
                            <div class="inv-sk inv-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="invContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-box fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumTotalItems">—</span>
                                    <span class="text-gray-600 fw-semibold">{{ __('pagination.total_items') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-barcode fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumTotalQty">—</span>
                                    <span class="text-gray-600 fw-semibold">{{ __('pagination.quantity_allocated') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-dollar fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumTotalValue">—</span>
                                    <span class="text-gray-600 fw-semibold">{{ __('pagination.total_value') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-calculator fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumAvgStock">—</span>
                                    <span class="text-gray-600 fw-semibold">{{ __('pagination.average_stock_level') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-warning-2 fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumBelowReorder">—</span>
                                    <span class="text-gray-600 fw-semibold">{{ __('pagination.items_below_reorder') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-secondary border border-secondary border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-cross fs-2tx text-secondary"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumOutOfStock">—</span>
                                    <span class="text-gray-600 fw-semibold">{{ __('pagination.out_of_stock') }}</span>
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
                                    <h3 class="fw-bold m-0">{{ __('pagination.stock_level_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="stockChart" style="height: 350px;"></div>
                                <div id="stockChartEmpty" class="text-center text-muted py-10 d-none">{{ __('accounting.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-4"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.department_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="deptChart" style="height: 350px;"></div>
                                <div id="deptChartEmpty" class="text-center text-muted py-10 d-none">{{ __('accounting.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Table --}}
                    <div class="row"><div class="col-12"><div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.inventory_items') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="invBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="invTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4 min-w-120px">{{ __('pagination.sku') }}</th>
                                    <th class="min-w-200px">{{ __('pagination.product') }}</th>
                                    <th class="min-w-150px">{{ __('accounting.department') }}</th>
                                    <th class="min-w-150px">{{ __('auth.location') }}</th>
                                    <th class="min-w-100px text-center">{{ __('pagination.quantity_on_hand') }}</th>
                                    <th class="min-w-100px text-center">{{ __('pagination.quantity_allocated') }}</th>
                                    <th class="min-w-100px text-center">{{ __('pagination.preferred_stock_level') }}</th>
                                    <th class="min-w-100px text-center">{{ __('pagination.reorder_point') }}</th>
                                    <th class="min-w-120px text-center">{{ __('auth.stock_status') }}</th>
                                    <th class="min-w-150px">{{ __('auth.last_updated') }}</th>
                                </tr></thead>
                                <tbody id="invTableBody"></tbody>
                                <tfoot class="bg-light d-none" id="invTableFooter">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">{{ __('accounting.current_page') }}:</td>
                                        <td class="text-center fw-bold" id="ftQty">—</td>
                                        <td class="text-center fw-bold" id="ftAllocated">—</td>
                                        <td class="text-center fw-bold" id="ftPreferred">—</td>
                                        <td class="text-center fw-bold" id="ftReorder">—</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table></div>
                            <div id="invEmpty" class="text-center py-10 d-none">
                                <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.no_inventory_items_found') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="invPaginationWrap">
                                <span class="text-muted fs-7" id="invPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="invPagination"></ul></nav>
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
    .inv-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: inv-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .inv-sk-text   { height: 12px; border-radius: 4px; }
    .inv-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .inv-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .inv-sk-chart  { width: 100%; height: 350px; border-radius: 8px; }
    @keyframes inv-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #invSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.INV_ROUTES = {
        data:   '{{ route("reports.inventory.summary-data") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.INV_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        variantId:    @json($variantId),
        isActive:     @json($isActive),
        perPage:      @json($perPage),
    };
</script>
@include('reports.inventory.summary-scripts')
@endpush