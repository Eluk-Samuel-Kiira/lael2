@extends('layouts.app')

@section('title', __('auth.invoice_delivery_report'))

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
                                {{ __('auth.invoice_delivery_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.invoice_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.delivery') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="dlExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export_to_excel') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="dlPrintBtn">
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
                        <form id="dlFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="dlStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="dlEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="dlLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="dlDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.channel') }}</label>
                                <select class="form-select" id="dlChannel">
                                    @foreach($channels as $c)
                                        <option value="{{ $c['value'] }}" @selected($channel === $c['value'])>{{ $c['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.delivery_status') }}</label>
                                <select class="form-select" id="dlStatus">
                                    @foreach($sendStatuses as $s)
                                        <option value="{{ $s['value'] }}" @selected($sendStatus === $s['value'])>{{ $s['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="dlApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="dlClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="dlSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="dl-sk dl-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="dl-sk dl-sk-text mb-2" style="width:60%;"></div>
                                    <div class="dl-sk dl-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="dl-sk dl-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="dl-sk dl-sk-text mb-2" style="width:35%;"></div>
                                <div class="dl-sk dl-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="dl-sk dl-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="dlContent" class="d-none">

                    <div id="dlEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-send fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_delivery_records_for_period') }}</p>
                    </div></div>

                    {{-- KPI cards --}}
                    <div id="dlKpiCards" class="row g-6 mb-6"></div>

                    {{-- By channel --}}
                    <div id="dlByChannelCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-signpost-split fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.delivery_by_channel') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.channel') }}</th>
                                    <th class="text-center">{{ __('auth.total') }}</th>
                                    <th class="text-center">{{ __('auth.sent') }}</th>
                                    <th class="text-center">{{ __('auth.delivered') }}</th>
                                    <th class="text-center">{{ __('auth.failed') }}</th>
                                    <th class="text-center">{{ __('auth.bounced') }}</th>
                                    <th class="text-center">{{ __('auth.pending') }}</th>
                                    <th class="pe-4 text-end">{{ __('auth.success_rate') }}</th>
                                </tr></thead>
                                <tbody id="dlByChannelBody"></tbody>
                                <tfoot class="bg-light" id="dlByChannelFoot"></tfoot>
                            </table></div>
                        </div>
                    </div>

                    {{-- Failure reasons --}}
                    <div id="dlFailuresCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle fs-2 me-2 text-danger"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.top_failure_reasons') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">#</th>
                                    <th>{{ __('auth.reason') }}</th>
                                    <th class="pe-4 text-center">{{ __('auth.occurrences') }}</th>
                                </tr></thead>
                                <tbody id="dlFailuresBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- Daily trend --}}
                    <div id="dlTrendCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.daily_delivery_trend') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.date') }}</th>
                                    <th class="text-center">{{ __('auth.sent') }}</th>
                                    <th class="text-center">{{ __('auth.delivered') }}</th>
                                    <th class="pe-4 text-center">{{ __('auth.failed') }}</th>
                                </tr></thead>
                                <tbody id="dlTrendBody"></tbody>
                                <tfoot class="bg-light" id="dlTrendFoot"></tfoot>
                            </table></div>
                        </div>
                    </div>

                    {{-- Send attempts --}}
                    <div id="dlTableCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.send_attempts') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="dlTableBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="dlSendsTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.invoice_number') }}</th>
                                    <th>{{ __('auth.recipient') }}</th>
                                    <th>{{ __('auth.channel') }}</th>
                                    <th>{{ __('auth.status') }}</th>
                                    <th>{{ __('auth.sent_at') }}</th>
                                    <th>{{ __('auth.delivered_at') }}</th>
                                    <th>{{ __('auth.provider') }}</th>
                                    <th>{{ __('auth.sent_by') }}</th>
                                    <th class="pe-4">{{ __('auth.error_message') }}</th>
                                </tr></thead>
                                <tbody id="dlSendsBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="dlPaginationWrap">
                                <span class="text-muted fs-7" id="dlPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="dlPagination"></ul></nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DRAWER — send attempt detail --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="dlDrawer" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-info py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-send fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="dlDrawerLabel">{{ __('auth.send_attempts') }}</h4>
                <span class="text-white opacity-75 fs-7" id="dlDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="dlDrawerSkeleton">
            <div class="dl-sk dl-sk-text mb-3" style="width:60%;"></div>
            <div class="dl-sk dl-sk-text mb-3" style="width:40%;"></div>
            <div class="dl-sk dl-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="dlDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .dl-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: dl-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .dl-sk-text   { height: 12px; border-radius: 4px; }
    .dl-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .dl-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes dl-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #dlSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .dl-row { cursor: pointer; }
    .dl-row:hover { background-color: #f9f9f9; }
    .dl-pre { white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .78rem; line-height: 1.5; }
</style>
@endpush

@push('scripts')
<script>
    window.DL_ROUTES = {
        data:   '{{ route("reports.invoices.delivery-data") }}',
        detail: '{{ route("reports.invoices.delivery-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.DL_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        channel:      @json($channel ?? 'all'),
        sendStatus:   @json($sendStatus ?? 'all'),
        perPage:      @json($perPage),
    };
    window.DL_I18N = {
        // KPI
        totalSendAttempts: @json(__('auth.total_send_attempts')),
        uniqueInvoices:    @json(__('auth.unique_invoices')),
        deliveryRate:      @json(__('auth.delivery_rate')),
        delivered:         @json(__('auth.delivered')),
        sent:              @json(__('auth.sent')),
        failureRate:       @json(__('auth.failure_rate')),
        failed:            @json(__('auth.failed')),
        bounced:           @json(__('auth.bounced')),
        pendingDelivery:   @json(__('auth.pending_delivery')),
        awaitingProvider:  @json(__('auth.awaiting_provider')),

        // by channel
        channel:           @json(__('auth.channel')),
        total:             @json(__('auth.total')),
        pending:           @json(__('auth.pending')),
        successRate:       @json(__('auth.success_rate')),
        totalAverage:      @json(__('accounting.total_average')),

        // failure
        reason:            @json(__('auth.reason')),
        occurrences:       @json(__('auth.occurrences')),

        // trend
        date:              @json(__('accounting.date')),

        // table
        invoiceNumber:     @json(__('auth.invoice_number')),
        recipient:         @json(__('auth.recipient')),
        status:            @json(__('auth.status')),
        sentAt:            @json(__('auth.sent_at')),
        deliveredAt:       @json(__('auth.delivered_at')),
        provider:          @json(__('auth.provider')),
        sentBy:            @json(__('auth.sent_by')),
        errorMessage:      @json(__('auth.error_message')),
        attempts:          @json(__('auth.attempts')),

        // drawer
        sendAttempt:       @json(__('auth.send_attempt')),
        providerMessageId: @json(__('auth.provider_message_id')),
        invoice:           @json(__('auth.invoice')),
        orderNumber:       @json(__('auth.order_number')),
        billingName:       @json(__('auth.billing_name')),
        billingEmail:      @json(__('auth.billing_email')),
        billingPhone:      @json(__('auth.billing_phone')),
        issueDate:         @json(__('auth.issue_date')),
        dueDate:           @json(__('auth.due_date')),
        totalAmount:       @json(__('auth.total')),
        amountPaid:        @json(__('auth.paid')),
        balanceDue:        @json(__('auth.balance_due')),
        history:           @json(__('auth.send_history')),
        currentAttempt:    @json(__('auth.current_attempt')),
        noData:            @json(__('accounting.no_data_available')),
    };
</script>
@include('reports.invoices.delivery-scripts')
@endpush