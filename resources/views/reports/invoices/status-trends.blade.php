@extends('layouts.app')

@section('title', __('auth.invoice_status_trends'))

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
                                {{ __('auth.invoice_status_trends') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.invoice_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.status_trends') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="stExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export_to_excel') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="stPrintBtn">
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
                        <form id="stFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="stStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="stEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="stLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="stDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.group_by') }}</label>
                                <select class="form-select" id="stGroupBy">
                                    @foreach($groupOptions as $opt)
                                        <option value="{{ $opt['value'] }}" @selected($groupBy === $opt['value'])>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="stApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="stClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="stSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="st-sk st-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="st-sk st-sk-text mb-2" style="width:60%;"></div>
                                    <div class="st-sk st-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="st-sk st-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="st-sk st-sk-text mb-2" style="width:35%;"></div>
                                <div class="st-sk st-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="st-sk st-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="stContent" class="d-none">

                    <div id="stEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-bar-chart fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_invoices_for_status_trends') }}</p>
                    </div></div>

                    {{-- KPI cards --}}
                    <div id="stKpiCards" class="row g-6 mb-6"></div>

                    {{-- Speed buckets --}}
                    <div id="stSpeedCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-speedometer2 fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.payment_speed_distribution') }}</h3>
                            </div>
                        </div>
                        <div class="card-body"><div id="stSpeedBuckets" class="row g-4"></div></div>
                    </div>

                    {{-- Status funnel --}}
                    <div id="stFunnelCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-funnel fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.status_funnel') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.status') }}</th>
                                    <th class="text-center">{{ __('auth.count') }}</th>
                                    <th class="text-end">{{ __('auth.share') }}</th>
                                    <th class="pe-4" style="width:45%;">{{ __('auth.distribution') }}</th>
                                </tr></thead>
                                <tbody id="stFunnelBody"></tbody>
                                <tfoot class="bg-light" id="stFunnelFoot"></tfoot>
                            </table></div>
                        </div>
                    </div>

                    {{-- Time-to-payment metrics --}}
                    <div id="stTimeMetricsCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-stopwatch fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.time_to_payment_metrics') }}</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="stTimeMetrics" class="row g-4"></div>
                            <div id="stTimeAlerts" class="row g-4 mt-2"></div>
                        </div>
                    </div>

                    {{-- Trend table --}}
                    <div id="stTrendCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.invoice_trend') }}</h3>
                                <span class="badge badge-light-primary ms-2" id="stTrendGroupBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.period') }}</th>
                                    <th class="text-center">{{ __('auth.invoices') }}</th>
                                    <th class="text-center">{{ __('auth.paid') }}</th>
                                    <th class="text-end">{{ __('auth.total_value') }}</th>
                                    <th class="text-end">{{ __('auth.collected') }}</th>
                                    <th class="text-end">{{ __('auth.outstanding') }}</th>
                                    <th class="text-end">{{ __('auth.avg_days_to_pay') }}</th>
                                    <th class="pe-4 text-end">{{ __('auth.collection_rate') }}</th>
                                </tr></thead>
                                <tbody id="stTrendBody"></tbody>
                                <tfoot class="bg-light" id="stTrendFoot"></tfoot>
                            </table></div>
                        </div>
                    </div>

                    {{-- Detail table --}}
                    <div id="stTableCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.invoice_lifecycle_detail') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="stTableBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="stInvoicesTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.invoice_number') }}</th>
                                    <th>{{ __('auth.customer') }}</th>
                                    <th>{{ __('auth.issue_date') }}</th>
                                    <th>{{ __('auth.status') }}</th>
                                    <th class="text-center">{{ __('auth.send_days') }}</th>
                                    <th class="text-center">{{ __('auth.view_days') }}</th>
                                    <th class="text-center">{{ __('auth.pay_days') }}</th>
                                    <th class="text-end">{{ __('auth.total') }}</th>
                                    <th class="pe-4 text-end">{{ __('auth.balance_due') }}</th>
                                </tr></thead>
                                <tbody id="stInvoicesBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="stPaginationWrap">
                                <span class="text-muted fs-7" id="stPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="stPagination"></ul></nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DRAWER — invoice detail --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="stDrawer" style="width: 900px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-receipt fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="stDrawerLabel">{{ __('auth.invoice_lifecycle_detail') }}</h4>
                <span class="text-white opacity-75 fs-7" id="stDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="stDrawerSkeleton">
            <div class="st-sk st-sk-text mb-3" style="width:60%;"></div>
            <div class="st-sk st-sk-text mb-3" style="width:40%;"></div>
            <div class="st-sk st-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="stDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .st-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: st-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .st-sk-text   { height: 12px; border-radius: 4px; }
    .st-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .st-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes st-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #stSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .st-row { cursor: pointer; }
    .st-row:hover { background-color: #f9f9f9; }
    .st-notes { white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; line-height: 1.5; }
</style>
@endpush

@push('scripts')
<script>
    window.ST_ROUTES = {
        data:   '{{ route("reports.invoices.status-trends-data") }}',
        detail: '{{ route("reports.invoices.status-trends-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.ST_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        groupBy:      @json($groupBy ?? 'monthly'),
        perPage:      @json($perPage),
    };
    window.ST_STATUS_COLORS = @json(\App\Models\Invoice::STATUS_COLORS);
    window.ST_I18N = {
        // KPI
        totalInvoices:     @json(__('auth.total_invoices')),
        paid:              @json(__('auth.paid')),
        unpaid:            @json(__('auth.unpaid')),
        avgDaysToPay:      @json(__('auth.avg_days_to_pay')),
        median:            @json(__('auth.median')),
        days:              @json(__('auth.days')),
        avgDaysToSend:     @json(__('auth.avg_days_to_send')),
        avgDaysToView:     @json(__('auth.avg_days_to_view')),
        paidWithinTerms:   @json(__('auth.paid_within_terms')),
        paidLate:          @json(__('auth.paid_late')),

        // speed buckets
        sameDay:           @json(__('auth.same_day')),
        days1_7:           @json(__('auth.days_1_7')),
        days8_14:          @json(__('auth.days_8_14')),
        days15_30:         @json(__('auth.days_15_30')),
        days31_60:         @json(__('auth.days_31_60')),
        days60plus:        @json(__('auth.days_60_plus')),

        // funnel
        status:            @json(__('auth.status')),
        count:             @json(__('auth.count')),
        share:             @json(__('auth.share')),
        distribution:      @json(__('auth.distribution')),
        totalAverage:      @json(__('accounting.total_average')),

        // metrics
        issueToSend:       @json(__('auth.issue_to_send')),
        sendToView:        @json(__('auth.send_to_view')),
        issueToPay:        @json(__('auth.issue_to_pay')),
        fastestPayment:    @json(__('auth.fastest_payment')),
        slowestPayment:    @json(__('auth.slowest_payment')),

        // trend
        period:            @json(__('auth.period')),
        invoices:          @json(__('auth.invoices')),
        totalValue:        @json(__('auth.total_value')),
        collected:         @json(__('auth.collected')),
        outstanding:       @json(__('auth.outstanding')),
        collectionRate:    @json(__('auth.collection_rate')),

        // table
        invoiceNumber:     @json(__('auth.invoice_number')),
        customer:          @json(__('auth.customer')),
        issueDate:         @json(__('auth.issue_date')),
        sendDays:          @json(__('auth.send_days')),
        viewDays:          @json(__('auth.view_days')),
        payDays:           @json(__('auth.pay_days')),
        total:             @json(__('auth.total')),
        balanceDue:        @json(__('auth.balance_due')),
        late:              @json(__('auth.late')),
        guest:             @json(__('auth.guest')),

        // drawer
        invoiceDetails:    @json(__('auth.invoice_details')),
        orderInfo:         @json(__('auth.order')),
        orderNumber:       @json(__('auth.order_number')),
        location:          @json(__('auth.location')),
        department:        @json(__('auth.department')),
        createdBy:         @json(__('auth.created_by')),
        voidedBy:          @json(__('auth.voided_by')),
        items:             @json(__('auth.item')),
        qty:               @json(__('auth.qty')),
        unitPrice:         @json(__('auth.unit_price')),
        discount:          @json(__('auth.discount')),
        tax:               @json(__('auth.tax')),
        lineTotal:         @json(__('auth.total')),
        subtotal:          @json(__('auth.subtotal')),
        payments:          @json(__('auth.payments')),
        amount:            @json(__('auth.amount')),
        method:            @json(__('auth.payment_method')),
        processedBy:       @json(__('auth.processed_by')),
        paymentDate:       @json(__('auth.payment_date')),
        transactionId:     @json(__('auth.transaction_id')),
        sends:             @json(__('auth.send_history')),
        channel:           @json(__('auth.channel')),
        recipient:         @json(__('auth.recipient')),
        sentAt:            @json(__('auth.sent_at')),
        notes:             @json(__('auth.notes')),
        noData:            @json(__('accounting.no_data_available')),
    };
</script>
@include('reports.invoices.status-trends-scripts')
@endpush