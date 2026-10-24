{{-- resources/views/reports/purchasing/purchase-order-items.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.purchase_order_items_analysis'))

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
                                {{ __('pagination.purchase_order_items_analysis') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchase_order_items_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="poiExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
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
                                    <i class="bi bi-funnel fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="poiFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.start_date') }}</label>
                                        <input type="date" class="form-control" id="poiStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.end_date') }}</label>
                                        <input type="date" class="form-control" id="poiEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                        <select class="form-select" id="poiSupplier" data-control="select2" data-placeholder="{{ __('passwords.all_suppliers') }}">
                                            <option value=""></option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}" @selected((int)$supplierId === $sup->id)>{{ $sup->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('passwords.product_variant') }}</label>
                                        <select class="form-select" id="poiVariant" data-control="select2" data-placeholder="{{ __('passwords.all_products') }}">
                                            <option value=""></option>
                                            @foreach($variants as $v)
                                                <option value="{{ $v->id }}" @selected((int)$productVariantId === $v->id)>
                                                    {{ $v->name }}{{ $v->product ? ' (' . $v->product->name . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-primary" id="poiApplyBtn">
                                            <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="poiClearBtn">
                                            <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SKELETON --}}
                <div id="poiSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 5; $i++)
                        <div class="col-6 col-md-4 col-lg-2"><div class="card card-flush h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="poi-sk poi-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="poi-sk poi-sk-text mb-2" style="width:60%;"></div>
                                    <div class="poi-sk poi-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body"><div class="poi-sk poi-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="poi-sk poi-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="poi-sk poi-sk-text mb-2" style="width:35%;"></div>
                                <div class="poi-sk poi-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="poi-sk poi-sk-badge"></div>
                            <div class="poi-sk poi-sk-text" style="width:100px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="poiContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">

                        {{-- Row 1 · Quantity & units --}}

                        {{-- Total Items --}}
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="symbol symbol-60px flex-shrink-0">
                                            <div class="symbol-label bg-primary bg-opacity-10">
                                                <i class="bi bi-list-check fs-2x text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_items') }}</div>
                                            <div class="fs-2 fw-bold text-primary lh-1 mt-1" id="sumTotalItems">—</div>
                                            <div class="text-muted fs-8 mt-1">{{ __('passwords.items_purchased') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Total Quantity --}}
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="symbol symbol-60px flex-shrink-0">
                                            <div class="symbol-label bg-success bg-opacity-10">
                                                <i class="bi bi-box-seam fs-2x text-success"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_quantity') }}</div>
                                            <div class="fs-2 fw-bold text-success lh-1 mt-1" id="sumTotalQty">—</div>
                                            <div class="text-muted fs-8 mt-1">{{ __('passwords.total_units') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Unique Products --}}
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="symbol symbol-60px flex-shrink-0">
                                            <div class="symbol-label bg-warning bg-opacity-10">
                                                <i class="bi bi-boxes fs-2x text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.unique_products') }}</div>
                                            <div class="fs-2 fw-bold text-warning lh-1 mt-1" id="sumUniqueProducts">—</div>
                                            <div class="text-muted fs-8 mt-1">{{ __('passwords.different_items') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Row 2 · Money --}}

                        {{-- Total Value — full width of the row, money visible --}}
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="symbol symbol-60px flex-shrink-0">
                                            <div class="symbol-label bg-info bg-opacity-10">
                                                <i class="bi bi-currency-dollar fs-2x text-info"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_value') }}</div>
                                            <div class="fs-2 fw-bold text-info lh-1 mt-1" id="sumTotalValue" style="word-break: break-word;">—</div>
                                            <div class="text-muted fs-8 mt-1">{{ __('passwords.total_spend') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Avg Unit Cost --}}
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card card-flush bg-light-dark border border-dark border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="symbol symbol-60px flex-shrink-0">
                                            <div class="symbol-label bg-dark bg-opacity-10">
                                                <i class="bi bi-graph-up fs-2x text-dark"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.avg_unit_cost') }}</div>
                                            <div class="fs-2 fw-bold text-dark lh-1 mt-1" id="sumAvgCost" style="word-break: break-word;">—</div>
                                            <div class="text-muted fs-8 mt-1">{{ __('passwords.per_unit_average') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Unique Suppliers --}}
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="symbol symbol-60px flex-shrink-0">
                                            <div class="symbol-label bg-danger bg-opacity-10">
                                                <i class="bi bi-people fs-2x text-danger"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.unique_suppliers') }}</div>
                                            <div class="fs-2 fw-bold text-danger lh-1 mt-1" id="sumUniqueSuppliers">—</div>
                                            <div class="text-muted fs-8 mt-1">{{ __('passwords.suppliers_used') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Top items chart --}}
                    <div class="row g-6 mb-6" id="poiTopCard">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-bar-chart fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.top_items_by_quantity') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">Top 10</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="poiTopChart" style="height: 400px;"></div>
                                <div id="poiTopEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.purchase_order_items') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="poiBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4" style="min-width:50px;">#</th>
                                    <th style="min-width:150px;">{{ __('passwords.po_number') }}</th>
                                    <th style="min-width:180px;">{{ __('passwords.product') }}</th>
                                    <th style="min-width:150px;">{{ __('passwords.supplier') }}</th>
                                    <th class="text-end" style="min-width:100px;">{{ __('passwords.quantity') }}</th>
                                    <th class="text-end" style="min-width:120px;">{{ __('passwords.unit_cost') }}</th>
                                    <th class="text-end" style="min-width:130px;">{{ __('passwords.total_cost') }}</th>
                                    <th style="min-width:150px;">{{ __('passwords.received_qty') }}</th>
                                    <th class="text-center" style="min-width:100px;">{{ __('passwords.balance') }}</th>
                                    <th style="min-width:150px;">{{ __('passwords.order_date') }}</th>
                                    <th class="text-end pe-4" style="min-width:120px;">{{ __('passwords.status') }}</th>
                                </tr></thead>
                                <tbody id="poiTableBody"></tbody>
                            </table></div>
                            <div id="poiEmpty" class="text-center py-10 d-none">
                                <i class="bi bi-bag fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_items_found') }}</h4>
                                <p class="text-muted fs-6">{{ __('passwords.no_purchase_order_items_match_filters') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="poiPaginationWrap">
                                <span class="text-muted fs-7" id="poiPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="poiPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Drawer --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="poiDrawer" style="width: 720px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-box fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="poiDrawerLabel">{{ __('passwords.purchase_order_item_details') }}</h4>
                <span class="text-white opacity-75 fs-7" id="poiDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="poiDrawerSkeleton">
            <div class="poi-sk poi-sk-text mb-3" style="width:60%;"></div>
            <div class="poi-sk poi-sk-text mb-3" style="width:40%;"></div>
            <div class="poi-sk poi-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="poiDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .poi-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: poi-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .poi-sk-text   { height: 12px; border-radius: 4px; }
    .poi-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .poi-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .poi-sk-chart  { width: 100%; height: 400px; border-radius: 8px; }
    @keyframes poi-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #poiSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .poi-row { cursor: pointer; }
    .poi-row:hover { background-color: #f9f9f9; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.POI_ROUTES = {
        data:   '{{ route("reports.purchasing.purchase-order-items-data") }}',
        detail: '{{ route("reports.purchasing.purchase-order-items-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.POI_BOOTSTRAP = {
        startDate:        @json($startDate),
        endDate:          @json($endDate),
        supplierId:       @json($supplierId),
        productVariantId: @json($productVariantId),
        perPage:          @json($perPage),
    };
</script>
@include('reports.purchasing.purchase-order-items-scripts')
@endpush