@extends('layouts.app')

@section('title', __('auth.invoice_summary_report'))

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
                                {{ __('auth.invoice_summary_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.invoice_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="isExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export_to_excel') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="isPrintBtn">
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
                        <form id="isFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="isStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="isEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="isLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="isDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.status') }}</label>
                                <select class="form-select" id="isStatus">
                                    <option value="all" @selected($statusFilter === 'all')>{{ __('auth.all_statuses') }}</option>
                                    @foreach($statuses as $s)
                                        <option value="{{ $s['value'] }}" @selected($statusFilter === $s['value'])>{{ $s['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.customer') }}</label>
                                <select class="form-select" id="isCustomer" data-control="select2" data-placeholder="{{ __('auth.all_customers') }}">
                                    <option value=""></option>
                                    @foreach($customers as $cust)
                                        <option value="{{ $cust->id }}" @selected((int)$customerId === $cust->id)>
                                            {{ trim($cust->first_name . ' ' . $cust->last_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('accounting.search') }}</label>
                                <input type="text" class="form-control" id="isSearch" value="{{ $search }}"
                                       placeholder="{{ __('auth.invoice_number_or_billing_name') }}">
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="isApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="isClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="isSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="is-sk is-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="is-sk is-sk-text mb-2" style="width:60%;"></div>
                                    <div class="is-sk is-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="is-sk is-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="is-sk is-sk-text mb-2" style="width:35%;"></div>
                                <div class="is-sk is-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="is-sk is-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="isContent" class="d-none">

                    <div id="isEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_invoices_found_for_period') }}</p>
                    </div></div>

                    {{-- KPI cards --}}
                    <div id="isKpiCards" class="row g-6 mb-6"></div>

                    {{-- Summary stat row --}}
                    <div id="isStatRow" class="row g-6 mb-6"></div>

                    {{-- Status breakdown --}}
                    <div id="isStatusCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-pie-chart fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.status_breakdown') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.status') }}</th>
                                    <th class="text-center">{{ __('auth.count') }}</th>
                                    <th class="text-end">{{ __('auth.total_amount') }}</th>
                                    <th class="text-end">{{ __('auth.balance_due') }}</th>
                                    <th class="pe-4 text-end">{{ __('auth.share') }}</th>
                                </tr></thead>
                                <tbody id="isStatusBody"></tbody>
                                <tfoot class="bg-light" id="isStatusFoot"></tfoot>
                            </table></div>
                        </div>
                    </div>

                    {{-- Aging buckets --}}
                    <div id="isAgingCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-hourglass-split fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.aging_analysis') }}</h3>
                            </div>
                        </div>
                        <div class="card-body"><div id="isAgingBuckets" class="row g-4"></div></div>
                    </div>

                    {{-- Monthly trend --}}
                    <div id="isMonthlyCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.monthly_invoice_trend') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.month') }}</th>
                                    <th class="text-center">{{ __('auth.invoices') }}</th>
                                    <th class="text-end">{{ __('auth.total_invoiced') }}</th>
                                    <th class="text-end">{{ __('auth.total_collected') }}</th>
                                    <th class="pe-4 text-end">{{ __('auth.outstanding') }}</th>
                                </tr></thead>
                                <tbody id="isMonthlyBody"></tbody>
                                <tfoot class="bg-light" id="isMonthlyFoot"></tfoot>
                            </table></div>
                        </div>
                    </div>

                    {{-- Top customers --}}
                    <div id="isTopCustomersCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-people fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.top_customers_by_invoice_value') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.customer') }}</th>
                                    <th class="text-center">{{ __('auth.invoices') }}</th>
                                    <th class="text-end">{{ __('auth.total_billed') }}</th>
                                    <th class="text-end">{{ __('auth.total_paid') }}</th>
                                    <th class="pe-4 text-end">{{ __('auth.outstanding') }}</th>
                                </tr></thead>
                                <tbody id="isTopCustomersBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- Invoices table --}}
                    <div id="isTableCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.invoice_breakdown') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="isTableBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="isInvoicesTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('auth.invoice_number') }}</th>
                                    <th>{{ __('auth.issue_date') }}</th>
                                    <th>{{ __('auth.due_date') }}</th>
                                    <th>{{ __('auth.customer') }}</th>
                                    <th>{{ __('auth.status') }}</th>
                                    <th class="text-end">{{ __('auth.total') }}</th>
                                    <th class="text-end">{{ __('auth.paid') }}</th>
                                    <th class="text-end">{{ __('auth.balance_due') }}</th>
                                    <th class="pe-4">{{ __('auth.location') }}</th>
                                </tr></thead>
                                <tbody id="isInvoicesBody"></tbody>
                                <tfoot class="bg-light" id="isInvoicesFoot"></tfoot>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="isPaginationWrap">
                                <span class="text-muted fs-7" id="isPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="isPagination"></ul></nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- DRAWER — invoice detail                                        --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="isDrawer" style="width: 900px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-receipt fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="isDrawerLabel">{{ __('auth.invoice_breakdown') }}</h4>
                <span class="text-white opacity-75 fs-7" id="isDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="isDrawerSkeleton">
            <div class="is-sk is-sk-text mb-3" style="width:60%;"></div>
            <div class="is-sk is-sk-text mb-3" style="width:40%;"></div>
            <div class="is-sk is-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="isDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .is-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: is-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .is-sk-text   { height: 12px; border-radius: 4px; }
    .is-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .is-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes is-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #isSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .is-row { cursor: pointer; }
    .is-row:hover { background-color: #f9f9f9; }
    .is-notes { white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; line-height: 1.5; }
</style>
@endpush

@push('scripts')
<script>
    window.IS_ROUTES = {
        data:   '{{ route("reports.invoices.summary-data") }}',
        detail: '{{ route("reports.invoices.summary-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.IS_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        statusFilter: @json($statusFilter ?? 'all'),
        customerId:   @json($customerId),
        search:       @json($search),
        perPage:      @json($perPage),
    };
    window.IS_STATUS_META = {
        labels: @json(\App\Models\Invoice::STATUS_LABELS),
        colors: @json(\App\Models\Invoice::STATUS_COLORS),
    };
    window.IS_I18N = {
        totalInvoices:    @json(__('auth.total_invoices')),
        paid:             @json(__('auth.paid')),
        partial:          @json(__('auth.partial')),
        overdue:          @json(__('auth.overdue')),
        totalInvoiced:    @json(__('auth.total_invoiced')),
        totalCollected:   @json(__('auth.total_collected')),
        outstanding:      @json(__('auth.outstanding')),
        collectionRate:   @json(__('auth.collection_rate')),
        averageInvoice:   @json(__('auth.average_invoice')),
        largestInvoice:   @json(__('auth.largest_invoice')),

        totalTax:         @json(__('auth.total_tax')),
        totalDiscount:    @json(__('auth.total_discount')),
        overdueInvoices:  @json(__('auth.overdue_invoices')),
        fullyPaid:        @json(__('auth.fully_paid')),
        invoices:         @json(__('auth.invoices')),

        status:           @json(__('auth.status')),
        count:            @json(__('auth.count')),
        totalAmount:      @json(__('auth.total_amount')),
        balanceDue:       @json(__('auth.balance_due')),
        share:            @json(__('auth.share')),
        totalAverage:     @json(__('accounting.total_average')),

        current:          @json(__('auth.current')),
        days1_30:         @json(__('auth.days_1_30')),
        days31_60:        @json(__('auth.days_31_60')),
        days61_90:        @json(__('auth.days_61_90')),
        days90plus:       @json(__('auth.days_90_plus')),

        month:            @json(__('auth.month')),
        totalPaidLbl:     @json(__('auth.total_paid')),

        customer:         @json(__('auth.customer')),
        totalBilled:      @json(__('auth.total_billed')),
        guest:            @json(__('auth.guest')),

        invoiceNumber:    @json(__('auth.invoice_number')),
        issueDate:        @json(__('auth.issue_date')),
        dueDate:          @json(__('auth.due_date')),
        total:            @json(__('auth.total')),
        location:         @json(__('auth.location')),

        // drawer
        invoiceDetails:   @json(__('auth.invoice_details')),
        billing:          @json(__('auth.billing')),
        item:             @json(__('auth.item')),
        qty:              @json(__('auth.qty')),
        unitPrice:        @json(__('auth.unit_price')),
        discount:         @json(__('auth.discount')),
        tax:              @json(__('auth.tax')),
        lineTotal:        @json(__('auth.total')),
        subtotal:         @json(__('auth.subtotal')),
        payments:         @json(__('auth.payments')),
        amount:           @json(__('auth.amount')),
        method:           @json(__('auth.payment_method')),
        processedBy:      @json(__('auth.processed_by')),
        paymentDate:      @json(__('auth.payment_date')),
        transactionId:    @json(__('auth.transaction_id')),
        sends:            @json(__('auth.send_history')),
        channel:          @json(__('auth.channel')),
        recipient:        @json(__('auth.recipient')),
        sentAt:           @json(__('auth.sent_at')),
        error:            @json(__('auth.error')),
        notes:            @json(__('auth.notes')),
        orderInfo:        @json(__('auth.order')),
        orderNumber:      @json(__('auth.order_number')),
        department:       @json(__('auth.department')),
        createdBy:        @json(__('auth.created_by')),
        voidedBy:         @json(__('auth.voided_by')),
        noData:           @json(__('accounting.no_data_available')),
    };
</script>
@include('reports.invoices.summary-scripts')
@endpush