{{-- resources/views/reports/purchasing/purchase-order-status.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.purchase_order_status'))

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
                                {{ __('pagination.purchase_order_status') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchase_order_status') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="psExportBtn">
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
                                <form id="psFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.start_date') }}</label>
                                        <input type="date" class="form-control" id="psStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.end_date') }}</label>
                                        <input type="date" class="form-control" id="psEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                        <select class="form-select" id="psSupplier" data-control="select2" data-placeholder="{{ __('passwords.all_suppliers') }}">
                                            <option value=""></option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}" @selected((int)$supplierId === $sup->id)>{{ $sup->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.status_group') }}</label>
                                        <select class="form-select" id="psStatusGroup">
                                            <option value="all"      @selected($statusGroup === 'all')>{{ __('pagination.all_orders') }}</option>
                                            <option value="pending"  @selected($statusGroup === 'pending')>{{ __('passwords.pending_orders') }}</option>
                                            <option value="overdue"  @selected($statusGroup === 'overdue')>{{ __('passwords.overdue_orders') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-primary" id="psApplyBtn">
                                            <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="psClearBtn">
                                            <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SKELETON --}}
                <div id="psSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="ps-sk ps-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="ps-sk ps-sk-text mb-2" style="width:60%;"></div>
                                    <div class="ps-sk ps-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body"><div class="ps-sk ps-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="ps-sk ps-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="ps-sk ps-sk-text mb-2" style="width:35%;"></div>
                                <div class="ps-sk ps-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="ps-sk ps-sk-badge"></div>
                            <div class="ps-sk ps-sk-text" style="width:100px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="psContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-clipboard fs-2tx text-primary me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_orders') }}</div>
                                        <div class="fs-2 fw-bold text-primary" id="sumTotalOrders">—</div>
                                        <div class="text-muted fs-8" id="sumTotalValue"></div>
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
                                        <div class="text-muted fs-8">{{ __('passwords.needs_attention') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle fs-2tx text-danger me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.overdue_orders') }}</div>
                                        <div class="fs-2 fw-bold text-danger" id="sumOverdue">—</div>
                                        <div class="text-muted fs-8">{{ __('passwords.urgent') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-check-circle fs-2tx text-success me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.completed_orders') }}</div>
                                        <div class="fs-2 fw-bold text-success" id="sumCompleted">—</div>
                                        <div class="text-muted fs-8" id="sumCompletedValue"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Status distribution chart --}}
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-bar-chart fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.order_status_distribution') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7" id="psPeriodBadge"></span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="psChart" style="height: 350px;"></div>
                                <div id="psChartEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Main table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0" id="psTableTitle">{{ __('passwords.purchase_orders') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="psBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">#</th>
                                    <th>{{ __('passwords.po_number') }}</th>
                                    <th>{{ __('passwords.supplier') }}</th>
                                    <th>{{ __('passwords.location') }}</th>
                                    <th class="text-end">{{ __('passwords.total') }}</th>
                                    <th class="text-center">{{ __('passwords.status') }}</th>
                                    <th>{{ __('passwords.expected_delivery') }}</th>
                                    <th class="text-center">{{ __('passwords.days_overdue') }}</th>
                                    <th>{{ __('passwords.created') }}</th>
                                    <th class="text-center">{{ __('passwords.actions') }}</th>
                                </tr></thead>
                                <tbody id="psTableBody"></tbody>
                            </table></div>
                            <div id="psEmpty" class="text-center py-10 d-none">
                                <i class="bi bi-check-circle fs-4tx text-success mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_pending_orders') }}</h4>
                                <p class="text-muted fs-6">{{ __('passwords.all_purchase_orders_processed') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="psPaginationWrap">
                                <span class="text-muted fs-7" id="psPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="psPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Drawer --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="psDrawer" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-clipboard fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="psDrawerLabel">{{ __('passwords.purchase_order_details') }}</h4>
                <span class="text-white opacity-75 fs-7" id="psDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="psDrawerSkeleton">
            <div class="ps-sk ps-sk-text mb-3" style="width:60%;"></div>
            <div class="ps-sk ps-sk-text mb-3" style="width:40%;"></div>
            <div class="ps-sk ps-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="psDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .ps-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: ps-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .ps-sk-text   { height: 12px; border-radius: 4px; }
    .ps-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .ps-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .ps-sk-chart  { width: 100%; height: 350px; border-radius: 8px; }
    @keyframes ps-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #psSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .ps-row { cursor: pointer; }
    .ps-row:hover { background-color: #f9f9f9; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.PS_ROUTES = {
        data:   '{{ route("reports.purchasing.purchase-order-status-data") }}',
        detail: '{{ route("reports.purchasing.purchase-order-status-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.PS_BOOTSTRAP = {
        startDate:   @json($startDate),
        endDate:     @json($endDate),
        supplierId:  @json($supplierId),
        statusGroup: @json($statusGroup),
        perPage:     @json($perPage),
    };
</script>
@include('reports.purchasing.purchase-order-status-scripts')
@endpush