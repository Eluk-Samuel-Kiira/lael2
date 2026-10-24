@extends('layouts.app')

@section('title', __('auth.returns_refunds_report'))

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
                                {{ __('auth.returns_refunds_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.returns_refunds_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="rrExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="rrPrintBtn">
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
                        <form id="rrFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="rrStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="rrEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="rrLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="rrDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.status') }}</label>
                                <select class="form-select" id="rrStatus">
                                    <option value="all"        @selected($statusFilter === 'all')>{{ __('auth.all_statuses') }}</option>
                                    <option value="pending"    @selected($statusFilter === 'pending')>{{ __('auth.pending') }}</option>
                                    <option value="processing" @selected($statusFilter === 'processing')>{{ __('auth.processing') }}</option>
                                    <option value="completed"  @selected($statusFilter === 'completed')>{{ __('auth.completed') }}</option>
                                    <option value="refunded"   @selected($statusFilter === 'refunded')>{{ __('auth.refunded') }}</option>
                                    <option value="cancelled"  @selected($statusFilter === 'cancelled')>{{ __('auth.cancelled') }}</option>
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="rrApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="rrClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="rrSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card h-100"><div class="card-body text-center">
                            <div class="rr-sk rr-sk-text mx-auto" style="width:60%;height:24px;"></div>
                            <div class="rr-sk rr-sk-text mx-auto mt-2" style="width:80%;"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="rr-sk" style="height:350px;border-radius:8px;"></div></div></div></div>
                        <div class="col-lg-4"><div class="card"><div class="card-body"><div class="rr-sk" style="height:350px;border-radius:8px;"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="rr-sk rr-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="rr-sk rr-sk-text mb-2" style="width:35%;"></div>
                                <div class="rr-sk rr-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="rr-sk rr-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="rrContent" class="d-none">

                    <div id="rrEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_returns_found_for_period') }}</p>
                    </div></div>

                    {{-- Summary cards --}}
                    <div id="rrSummaryCards" class="row g-6 mb-6"></div>

                    {{-- Reasons + breakdown --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div id="rrReasonsCard" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-pie-chart fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.return_reasons_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0"><div id="rrReasonsChart" style="height:350px;"></div></div>
                        </div></div>
                        <div class="col-lg-4"><div id="rrReasonsTableCard" class="card h-100 d-none">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.breakdown') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4">{{ __('auth.reason') }}</th>
                                        <th>{{ __('auth.count') }}</th>
                                        <th class="text-end pe-4">{{ __('auth.amount') }}</th>
                                    </tr></thead>
                                    <tbody id="rrReasonsBody"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Monthly trends --}}
                    <div id="rrMonthlyCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-graph-up fs-2 me-2 text-info"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.monthly_return_trends') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0"><div id="rrMonthlyChart" style="height:350px;"></div></div>
                    </div>

                    {{-- Returns by payment method --}}
                    <div id="rrByMethodCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-credit-card fs-2 me-2 text-warning"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.returns_by_payment_method') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light-warning">
                                    <th class="ps-4">{{ __('accounting.payment_method') }}</th>
                                    <th>{{ __('auth.return_count') }}</th>
                                    <th class="text-end">{{ __('auth.return_value') }}</th>
                                    <th class="pe-4" style="width:30%;">{{ __('accounting.percentage') }}</th>
                                </tr></thead>
                                <tbody id="rrByMethodBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- Top returned products --}}
                    <div id="rrTopProductsCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-bar-chart fs-2 me-2 text-danger"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.top_returned_products') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="rrTopTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light-danger">
                                    <th class="ps-4">{{ __('accounting.rank') }}</th>
                                    <th>{{ __('auth.product') }}</th>
                                    <th>{{ __('accounting.sku') }}</th>
                                    <th class="text-center">{{ __('auth.quantity_returned') }}</th>
                                    <th class="text-end">{{ __('auth.return_value') }}</th>
                                    <th class="text-center">{{ __('auth.return_count') }}</th>
                                    <th class="text-end pe-4">{{ __('auth.average_return') }}</th>
                                </tr></thead>
                                <tbody id="rrTopBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- Refunded orders --}}
                    <div id="rrReturnOrdersCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.return_orders') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="rrReturnOrdersBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('auth.order_number') }}</th>
                                    <th>{{ __('auth.customer') }}</th>
                                    <th>{{ __('accounting.status') }}</th>
                                    <th class="text-end">{{ __('auth.order_total') }}</th>
                                    <th class="text-end">{{ __('auth.refund_amount') }}</th>
                                    <th class="text-center">Refunded lines</th>
                                    <th>{{ __('auth.return_reason') }}</th>
                                    <th class="pe-4">{{ __('auth.created_date') }}</th>
                                </tr></thead>
                                <tbody id="rrReturnOrdersBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="rrReturnsPaginationWrap">
                                <span class="text-muted fs-7" id="rrReturnsPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="rrReturnsPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                    {{-- Refund payments --}}
                    <div id="rrRefundsCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-cash-coin fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.refund_payments') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="rrRefundsBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('auth.order_number') }}</th>
                                    <th>{{ __('auth.payment_method') }}</th>
                                    <th class="text-end">{{ __('auth.refund_amount') }}</th>
                                    <th class="text-end">Original</th>
                                    <th>{{ __('auth.refund_percentage') }}</th>
                                    <th class="pe-4">{{ __('auth.processed_date') }}</th>
                                </tr></thead>
                                <tbody id="rrRefundsBody"></tbody>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="rrRefundsPaginationWrap">
                                <span class="text-muted fs-7" id="rrRefundsPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="rrRefundsPagination"></ul></nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DRAWER 1 — Refunded order detail --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="rrOrderDrawer" style="width: 900px; max-width: 100vw;">
    <div class="offcanvas-header bg-danger py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-arrow-return-left fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="rrOrderDrawerLabel">{{ __('auth.return_orders') }}</h4>
                <span class="text-white opacity-75 fs-7" id="rrOrderDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="rrOrderDrawerSkeleton">
            <div class="rr-sk rr-sk-text mb-3" style="width:60%;"></div>
            <div class="rr-sk rr-sk-text mb-3" style="width:40%;"></div>
            <div class="rr-sk rr-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="rrOrderDrawerContent" class="d-none"></div>
    </div>
</div>

{{-- DRAWER 2 — Refund payment detail --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="rrPaymentDrawer" style="width: 720px; max-width: 100vw;">
    <div class="offcanvas-header bg-warning py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-cash-coin fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="rrPaymentDrawerLabel">{{ __('auth.refund_payments') }}</h4>
                <span class="text-white opacity-75 fs-7" id="rrPaymentDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="rrPaymentDrawerSkeleton">
            <div class="rr-sk rr-sk-text mb-3" style="width:60%;"></div>
            <div class="rr-sk rr-sk-text mb-3" style="width:40%;"></div>
            <div class="rr-sk rr-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="rrPaymentDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .rr-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: rr-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .rr-sk-text   { height: 12px; border-radius: 4px; }
    .rr-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes rr-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #rrSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .rr-row { cursor: pointer; }
    .rr-row:hover { background-color: #f9f9f9; }
    .rr-notes { white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; line-height: 1.5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.RR_ROUTES = {
        data:    '{{ route("reports.orders.returns-refunds-data") }}',
        order:   '{{ route("reports.orders.returns-refunds-order-detail", ["orderId" => "__ID__"]) }}',
        payment: '{{ route("reports.orders.returns-refunds-payment-detail", ["paymentId" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.RR_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        statusFilter: @json($statusFilter ?? 'all'),
        perPage:      @json($perPage),
    };
    window.RR_I18N = {
        // summary
        totalReturns:       @json(__('auth.total_returns')),
        returnRate:         @json(__('auth.return_rate')),
        totalRefundedAmount:@json(__('auth.total_refunded_amount')),
        refundRate:         @json(__('auth.refund_rate')),
        averageReturnValue: @json(__('auth.average_return_value')),
        itemsReturned:      @json(__('auth.items_returned')),
        uniqueProducts:     @json(__('auth.unique_products')),
        returns:            @json(__('auth.returns')),

        // reasons
        returnReasons:      @json(__('auth.return_reasons_analysis')),
        breakdown:          @json(__('auth.breakdown')),
        reason:             @json(__('auth.reason')),
        count:              @json(__('auth.count')),
        amount:             @json(__('auth.amount')),
        unspecified:        @json(__('auth.unspecified_reason')),

        // monthly
        monthlyTrends:      @json(__('auth.monthly_return_trends')),
        returnValue:        @json(__('auth.return_value')),
        returnCount:        @json(__('auth.return_count')),

        // by method
        byPaymentMethod:    @json(__('auth.returns_by_payment_method')),
        paymentMethod:      @json(__('auth.payment_method')),

        // top products
        topReturned:        @json(__('auth.top_returned_products')),
        rank:               @json(__('accounting.rank')),
        product:            @json(__('auth.product')),
        sku:                @json(__('accounting.sku')),
        quantityReturned:   @json(__('auth.quantity_returned')),
        averageReturn:      @json(__('auth.average_return')),

        // return orders table
        returnOrders:       @json(__('auth.return_orders')),
        orderNumber:        @json(__('auth.order_number')),
        customer:           @json(__('auth.customer')),
        status:             @json(__('accounting.status')),
        returnAmount:       @json(__('auth.refund_amount')),
        returnReason:       @json(__('auth.return_reason')),
        createdDate:        @json(__('auth.created_date')),

        // refunds table
        refundPayments:     @json(__('auth.refund_payments')),
        refundAmount:       @json(__('auth.refund_amount')),
        orderTotal:         @json(__('auth.order_total')),
        refundPercentage:   @json(__('auth.refund_percentage')),
        processedDate:      @json(__('auth.processed_date')),

        // drawer 1 (order)
        orderDetail:        @json(__('auth.order_details')),
        subTotal:           @json(__('auth.subtotal')),
        discount:           @json(__('auth.discount')),
        tax:                @json(__('auth.tax')),
        total:              @json(__('auth.total')),
        createdBy:          @json(__('auth.created_by')),
        notes:              @json(__('auth.notes')),
        items:              @json(__('auth.items')),
        qty:                @json(__('auth.qty')),
        unitPrice:          @json(__('auth.unit_price')),
        lineTotal:          @json(__('auth.total')),
        refundedQty:        @json(__('auth.refunded_qty')),

        // summary inside drawer (mirrors the actual refund UI)
        alreadyRefunded:    @json(__('auth.already_refunded')),
        maxRefundable:      @json(__('auth.maximum_refundable')),
        itemsFullyRefunded: @json(__('auth.items_fully_refunded')),
        fullyRefunded:      @json(__('auth.fully_refunded')),
        partialRefunded:    @json(__('auth.partially_refunded')),
        refundable:         @json(__('auth.refundable')),

        // drawer 2 (payment)
        paymentDetail:      @json(__('auth.payment_details')),
        reference:          @json(__('auth.reference')),
        processor:          @json(__('auth.processed_by')),
        paymentTrail:       @json(__('auth.payment_trail')),
        originalAmount:     @json(__('auth.original_amount')),
        stillRefundable:    @json(__('auth.still_refundable')),
        noData:             @json(__('accounting.no_data_available')),
    };
</script>
@include('reports.orders.returns-refunds-scripts')
@endpush