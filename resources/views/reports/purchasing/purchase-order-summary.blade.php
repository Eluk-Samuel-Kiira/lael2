{{-- resources/views/reports/purchasing/purchase-order-summary.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.purchase_order_summary'))

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
                                {{ __('pagination.purchase_order_summary') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchase_order_summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="poExportBtn">
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
                                <form id="poFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.start_date') }}</label>
                                        <input type="date" class="form-control" id="poStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.end_date') }}</label>
                                        <input type="date" class="form-control" id="poEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                        <select class="form-select" id="poSupplier" data-control="select2" data-placeholder="{{ __('passwords.all_suppliers') }}">
                                            <option value=""></option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}" @selected((int)$supplierId === $sup->id)>{{ $sup->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                        <select class="form-select" id="poLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                            <option value=""></option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('passwords.order_status') }}</label>
                                        <select class="form-select" id="poStatus">
                                            <option value="all" @selected($status === 'all')>{{ __('passwords.all_statuses') }}</option>
                                            @foreach(['draft','sent','pending_approval','approved','partially_received','received','cancelled'] as $st)
                                                <option value="{{ $st }}" @selected($status === $st)>{{ __('passwords.' . $st) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-primary" id="poApplyBtn">
                                            <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="poClearBtn">
                                            <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SKELETON --}}
                <div id="poSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="po-sk po-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="po-sk po-sk-text mb-2" style="width:60%;"></div>
                                    <div class="po-sk po-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="po-sk po-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="po-sk po-sk-text mb-2" style="width:35%;"></div>
                                <div class="po-sk po-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="po-sk po-sk-badge"></div>
                            <div class="po-sk po-sk-text" style="width:100px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="poContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-file-earmark-text fs-2tx text-primary me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_orders') }}</div>
                                        <div class="fs-2 fw-bold text-primary" id="sumTotalOrders">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-currency-dollar fs-2tx text-success me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_spent') }}</div>
                                        <div class="fs-2 fw-bold text-success" id="sumTotalValue">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-graph-up fs-2tx text-info me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.avg_order_value') }}</div>
                                        <div class="fs-2 fw-bold text-info" id="sumAvgValue">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-clock-history fs-2tx text-warning me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.pending_orders') }}</div>
                                        <div class="fs-2 fw-bold text-warning" id="sumPending">—</div>
                                    </div>
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
                                    <h3 class="fw-bold m-0">{{ __('passwords.purchase_orders') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="poBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="poTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">#</th>
                                    <th>{{ __('passwords.po_number') }}</th>
                                    <th>{{ __('passwords.supplier') }}</th>
                                    <th>{{ __('passwords.order_date') }}</th>
                                    <th>{{ __('passwords.expected_delivery') }}</th>
                                    <th>{{ __('pagination.status') }}</th>
                                    <th>{{ __('pagination.total_amount') }}</th>
                                    <th class="text-center">{{ __('pagination.actions') }}</th>
                                </tr></thead>
                                <tbody id="poTableBody"></tbody>
                            </table></div>
                            <div id="poEmpty" class="text-center py-10 d-none">
                                <i class="bi bi-file-earmark-text fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_purchase_orders') }}</h4>
                                <p class="text-muted fs-6">{{ __('passwords.no_purchase_orders_in_period') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="poPaginationWrap">
                                <span class="text-muted fs-7" id="poPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="poPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Drawer --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="poDrawer" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-file-earmark-text fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="poDrawerLabel">{{ __('passwords.purchase_order_details') }}</h4>
                <span class="text-white opacity-75 fs-7" id="poDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="poDrawerSkeleton">
            <div class="po-sk po-sk-text mb-3" style="width:60%;"></div>
            <div class="po-sk po-sk-text mb-3" style="width:40%;"></div>
            <div class="po-sk po-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="poDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .po-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: po-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .po-sk-text   { height: 12px; border-radius: 4px; }
    .po-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .po-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes po-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #poSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .po-row { cursor: pointer; }
    .po-row:hover { background-color: #f9f9f9; }
</style>
@endpush

@push('scripts')
<script>
    window.PO_ROUTES = {
        data:   '{{ route("reports.purchasing.purchase-order-summary-data") }}',
        detail: '{{ route("reports.purchasing.purchase-order-summary-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.PO_BOOTSTRAP = {
        startDate:  @json($startDate),
        endDate:    @json($endDate),
        supplierId: @json($supplierId),
        status:     @json($status),
        locationId: @json($locationId),
        perPage:    @json($perPage),
    };
</script>
@include('reports.purchasing.purchase-order-summary-scripts')
@endpush