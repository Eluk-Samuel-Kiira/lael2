@extends('layouts.app')

@section('title', __('auth.invoice_payments_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- Toolbar --}}
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0 w-100">
                        <div class="page-title d-flex flex-column align-items-start text-start me-auto">
                            <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                                {{ __('auth.invoice_payments_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.invoice_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.payments') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="ipExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export_to_excel') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="ipPrintBtn">
                                <i class="bi bi-printer fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.print') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="row mb-6"><div class="col-12"><div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="bi bi-funnel fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form id="ipFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="ipStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="ipEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="ipLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="ipDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.payment_method') }}</label>
                                <select class="form-select" id="ipMethod" data-control="select2" data-placeholder="{{ __('auth.all_methods') }}">
                                    <option value=""></option>
                                    @foreach($paymentMethods as $m)
                                        <option value="{{ $m->id }}" @selected((int)$paymentMethod === $m->id)>{{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.recorded_via') }}</label>
                                <select class="form-select" id="ipChannel">
                                    @foreach($channels as $c)
                                        <option value="{{ $c['value'] }}" @selected($recordedVia === $c['value'])>{{ $c['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.status') }}</label>
                                <select class="form-select" id="ipStatus">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s['value'] }}" @selected($statusFilter === $s['value'])>{{ $s['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="ipApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="ipClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="ipSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="ip-sk ip-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="ip-sk ip-sk-text mb-2" style="width:60%;"></div>
                                    <div class="ip-sk ip-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="ip-sk ip-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="ip-sk ip-sk-text mb-2" style="width:35%;"></div>
                                <div class="ip-sk ip-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="ip-sk ip-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="ipContent" class="d-none">

                    <div id="ipEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_invoice_payments_for_period') }}</p>
                    </div></div>

                    {{-- KPI cards --}}
                    <div id="ipKpiCards" class="row g-6 mb-6"></div>

                    {{-- Breakdowns --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6"><div id="ipMethodCard" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-wallet2 fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.payments_by_method') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="ps-4">{{ __('auth.payment_method') }}</th>
                                        <th class="text-center">{{ __('auth.count') }}</th>
                                        <th class="text-end">{{ __('auth.total_amount') }}</th>
                                        <th class="pe-4 text-end">{{ __('auth.avg') }}</th>
                                    </tr></thead>
                                    <tbody id="ipMethodBody"></tbody>
                                    <tfoot class="bg-light" id="ipMethodFoot"></tfoot>
                                </table></div>
                            </div>
                        </div></div>
                        <div class="col-md-6"><div id="ipChannelCard" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-signpost-split fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.payments_by_channel') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="ps-4">{{ __('auth.channel') }}</th>
                                        <th class="text-center">{{ __('auth.count') }}</th>
                                        <th class="text-end">{{ __('auth.total_amount') }}</th>
                                        <th class="pe-4 text-end">{{ __('auth.share') }}</th>
                                    </tr></thead>
                                    <tbody id="ipChannelBody"></tbody>
                                    <tfoot class="bg-light" id="ipChannelFoot"></tfoot>
                                </table></div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Daily trend --}}
                    <div id="ipTrendCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.daily_payment_trend') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.date') }}</th>
                                    <th class="text-center">{{ __('auth.payments') }}</th>
                                    <th class="pe-4 text-end">{{ __('auth.total_amount') }}</th>
                                </tr></thead>
                                <tbody id="ipTrendBody"></tbody>
                                <tfoot class="bg-light" id="ipTrendFoot"></tfoot>
                            </table></div>
                        </div>
                    </div>

                    {{-- Payments table --}}
                    <div id="ipTableCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.payment_transactions') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="ipTableBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="ipPaymentsTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.invoice_number') }}</th>
                                    <th>{{ __('auth.customer') }}</th>
                                    <th>{{ __('auth.date') }}</th>
                                    <th>{{ __('auth.payment_method') }}</th>
                                    <th>{{ __('auth.channel') }}</th>
                                    <th>{{ __('auth.reference') }}</th>
                                    <th class="text-end">{{ __('auth.amount') }}</th>
                                    <th>{{ __('auth.status') }}</th>
                                    <th class="pe-4">{{ __('auth.processed_by') }}</th>
                                </tr></thead>
                                <tbody id="ipPaymentsBody"></tbody>
                                <tfoot class="bg-light" id="ipPaymentsFoot"></tfoot>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="ipPaginationWrap">
                                <span class="text-muted fs-7" id="ipPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="ipPagination"></ul></nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DRAWER — payment detail --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="ipDrawer" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-success py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-cash-coin fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="ipDrawerLabel">{{ __('auth.payment_transactions') }}</h4>
                <span class="text-white opacity-75 fs-7" id="ipDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="ipDrawerSkeleton">
            <div class="ip-sk ip-sk-text mb-3" style="width:60%;"></div>
            <div class="ip-sk ip-sk-text mb-3" style="width:40%;"></div>
            <div class="ip-sk ip-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="ipDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .ip-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: ip-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .ip-sk-text   { height: 12px; border-radius: 4px; }
    .ip-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .ip-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes ip-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #ipSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .ip-row { cursor: pointer; }
    .ip-row:hover { background-color: #f9f9f9; }
    .ip-notes { white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; line-height: 1.5; }
</style>
@endpush

@push('scripts')
<script>
    window.IP_ROUTES = {
        data:   '{{ route("reports.invoices.payments-data") }}',
        detail: '{{ route("reports.invoices.payments-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.IP_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        paymentMethod: @json($paymentMethod),
        recordedVia:  @json($recordedVia ?? 'all'),
        statusFilter: @json($statusFilter ?? 'all'),
        perPage:      @json($perPage),
    };
    window.IP_I18N = {
        totalCollected:    @json(__('auth.total_collected')),
        completedPayments: @json(__('auth.completed_payments')),
        totalPayments:     @json(__('auth.total_payments')),
        avg:               @json(__('auth.avg')),
        largestPayment:    @json(__('auth.largest_payment')),
        failedRefunded:    @json(__('auth.failed_refunded')),

        paymentMethod:     @json(__('auth.payment_method')),
        count:             @json(__('auth.count')),
        totalAmount:       @json(__('auth.total_amount')),
        channel:           @json(__('auth.channel')),
        share:             @json(__('auth.share')),
        totalAverage:      @json(__('accounting.total_average')),

        payments:          @json(__('auth.payments')),
        date:              @json(__('accounting.date')),

        invoiceNumber:     @json(__('auth.invoice_number')),
        customer:          @json(__('auth.customer')),
        reference:         @json(__('auth.reference')),
        amount:            @json(__('auth.amount')),
        status:            @json(__('auth.status')),
        processedBy:       @json(__('auth.processed_by')),

        // drawer
        paymentDetails:    @json(__('auth.payment_details')),
        transactionId:     @json(__('auth.transaction_id')),
        referenceNumber:   @json(__('auth.reference_number')),
        type:              @json(__('auth.type')),
        paymentDate:       @json(__('auth.payment_date')),
        verifiedAt:        @json(__('auth.verified_at')),
        refundedAt:        @json(__('auth.refunded_at')),
        recordedVia:       @json(__('auth.recorded_via')),
        currentBalance:    @json(__('auth.current_balance')),
        processedByEmail:  @json(__('auth.email')),
        invoice:           @json(__('auth.invoice')),
        invoiceTotal:      @json(__('auth.total')),
        invoicePaid:       @json(__('auth.paid')),
        invoiceBalance:    @json(__('auth.balance_due')),
        invoiceIssueDate:  @json(__('auth.issue_date')),
        invoiceDueDate:    @json(__('auth.due_date')),
        orderNumber:       @json(__('auth.order_number')),
        notes:             @json(__('auth.notes')),
        noData:            @json(__('accounting.no_data_available')),
    };
</script>
@include('reports.invoices.payments-scripts')
@endpush