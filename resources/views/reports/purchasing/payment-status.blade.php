{{-- resources/views/reports/purchasing/payment-status.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.payment_status_report'))

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
                                {{ __('pagination.payment_status_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.payment_status_report') }}</li>
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
                                        <label class="form-label fw-semibold">{{ __('passwords.payment_status') }}</label>
                                        <select class="form-select" id="psStatusFilter">
                                            <option value="all"       @selected($paymentStatus === 'all')>{{ __('passwords.all_statuses') }}</option>
                                            <option value="pending"   @selected($paymentStatus === 'pending')>{{ __('passwords.pending') }}</option>
                                            <option value="partial"   @selected($paymentStatus === 'partial')>{{ __('passwords.partial') }}</option>
                                            <option value="paid"      @selected($paymentStatus === 'paid')>{{ __('passwords.paid') }}</option>
                                            <option value="overdue"   @selected($paymentStatus === 'overdue')>{{ __('passwords.overdue') }}</option>
                                            <option value="cancelled" @selected($paymentStatus === 'cancelled')>{{ __('passwords.cancelled') }}</option>
                                        </select>
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
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="symbol symbol-60px flex-shrink-0">
                                            <div class="symbol-label bg-warning bg-opacity-10">
                                                <i class="bi bi-currency-dollar fs-2x text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_amount_due') }}</div>
                                            <div class="fs-2 fw-bold text-warning lh-1 mt-1" id="sumDue" style="word-break:break-word;">—</div>
                                            <div class="text-muted fs-8 mt-1">{{ __('passwords.amount_outstanding') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="symbol symbol-60px flex-shrink-0">
                                            <div class="symbol-label bg-success bg-opacity-10">
                                                <i class="bi bi-check-circle fs-2x text-success"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_amount_paid') }}</div>
                                            <div class="fs-2 fw-bold text-success lh-1 mt-1" id="sumPaid" style="word-break:break-word;">—</div>
                                            <div class="text-muted fs-8 mt-1">{{ __('passwords.amount_settled') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center gap-4">
                                        <div class="symbol symbol-60px flex-shrink-0">
                                            <div class="symbol-label bg-danger bg-opacity-10">
                                                <i class="bi bi-exclamation-triangle fs-2x text-danger"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.overdue_amount') }}</div>
                                            <div class="fs-2 fw-bold text-danger lh-1 mt-1" id="sumOverdue" style="word-break:break-word;">—</div>
                                            <div class="text-muted fs-8 mt-1" id="sumOverdueCount"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
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
                                            <div class="text-muted fs-8 mt-1">{{ __('passwords.items_analyzed') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment status chart --}}
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-pie-chart fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.payment_status_distribution') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7" id="psPeriodBadge"></span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="psChart" style="height: 350px;"></div>
                                <div id="psChartEmpty" class="text-center text-muted py-10 d-none">{{ __('passwords.no_payment_data') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Items table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.payment_transactions') }}</h3>
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
                                    <th>{{ __('passwords.product') }}</th>
                                    <th class="text-end">{{ __('passwords.amount') }}</th>
                                    <th>{{ __('passwords.payment_date') }}</th>
                                    <th>{{ __('passwords.payment_method') }}</th>
                                    <th>{{ __('passwords.due_date') }}</th>
                                    <th class="text-center">{{ __('passwords.days_remaining') }}</th>
                                    <th class="text-end pe-4">{{ __('passwords.payment_status') }}</th>
                                </tr></thead>
                                <tbody id="psTableBody"></tbody>
                            </table></div>
                            <div id="psEmpty" class="text-center py-10 d-none">
                                <i class="bi bi-currency-dollar fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_payments_found') }}</h4>
                                <p class="text-muted fs-6">{{ __('passwords.no_payment_transactions_match_filters') }}</p>
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
<div class="offcanvas offcanvas-end" tabindex="-1" id="psDrawer" style="width: 720px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-currency-dollar fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="psDrawerLabel">{{ __('passwords.payment_details') }}</h4>
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
    .ps-row.is-overdue { background-color: #fff5f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.PS_ROUTES = {
        data:   '{{ route("reports.purchasing.payment-status-data") }}',
        detail: '{{ route("reports.purchasing.payment-status-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.PS_BOOTSTRAP = {
        startDate:     @json($startDate),
        endDate:       @json($endDate),
        paymentStatus: @json($paymentStatus),
        supplierId:    @json($supplierId),
        perPage:       @json($perPage),
    };
</script>
@include('reports.purchasing.payment-status-scripts')
@endpush