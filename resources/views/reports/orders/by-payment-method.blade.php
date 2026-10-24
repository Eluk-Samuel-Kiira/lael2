@extends('layouts.app')

@section('title', __('auth.payment_method_analysis'))

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
                                {{ __('auth.payment_method_analysis') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.payment_method_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="pmExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" id="pmPrintBtn">
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
                        <form id="pmFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" id="pmStartDate" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" id="pmEndDate" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" id="pmLocation" data-control="select2" data-placeholder="{{ __('auth.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" id="pmDepartment" data-control="select2" data-placeholder="{{ __('auth.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dep)
                                        <option value="{{ $dep->id }}" @selected((int)$departmentId === $dep->id)>{{ $dep->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.payment_type') }}</label>
                                <select class="form-select" id="pmType">
                                    <option value="all">{{ __('auth.all_types') }}</option>
                                    @foreach($paymentTypes as $t)
                                        <option value="{{ $t }}" @selected($paymentType === $t)>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.payment_status') }}</label>
                                <select class="form-select" id="pmStatus">
                                    <option value="all"       @selected($paymentStatus === 'all')>{{ __('auth.all_statuses') }}</option>
                                    <option value="completed" @selected($paymentStatus === 'completed')>{{ __('auth.completed') }}</option>
                                    <option value="failed"    @selected($paymentStatus === 'failed')>{{ __('auth.failed') }}</option>
                                    <option value="pending"   @selected($paymentStatus === 'pending')>{{ __('auth.pending') }}</option>
                                    <option value="refunded"  @selected($paymentStatus === 'refunded')>{{ __('auth.refunded') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.method_status') }}</label>
                                <select class="form-select" id="pmMethodStatus">
                                    <option value="all"      @selected($paymentMethodStatus === 'all')>{{ __('auth.all_methods') }}</option>
                                    <option value="active"   @selected($paymentMethodStatus === 'active')>{{ __('auth.active') }}</option>
                                    <option value="inactive" @selected($paymentMethodStatus === 'inactive')>{{ __('auth.inactive') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.min_amount') }}</label>
                                <input type="number" min="0" step="0.01" class="form-control" id="pmMinAmount" value="{{ $minAmount }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.max_amount') }}</label>
                                <input type="number" min="0" step="0.01" class="form-control" id="pmMaxAmount" value="{{ $maxAmount }}">
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="pmApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('accounting.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="pmClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('accounting.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div></div></div>

                {{-- SKELETON --}}
                <div id="pmSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 6; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card h-100"><div class="card-body text-center">
                            <div class="pm-sk pm-sk-circle mx-auto mb-3"></div>
                            <div class="pm-sk pm-sk-text mx-auto" style="width:60%;height:24px;"></div>
                            <div class="pm-sk pm-sk-text mx-auto mt-2" style="width:80%;"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body">
                            <div class="pm-sk" style="height:400px;border-radius:8px;"></div>
                        </div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="pm-sk pm-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="pm-sk pm-sk-text mb-2" style="width:35%;"></div>
                                <div class="pm-sk pm-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="pm-sk pm-sk-badge"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="pmContent" class="d-none">

                    <div id="pmEmpty" class="card d-none"><div class="card-body text-center py-10">
                        <i class="bi bi-file-earmark fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_payment_methods_found_for_period') }}</p>
                    </div></div>

                    {{-- KPI cards --}}
                    <div id="pmKpiCards" class="row g-6 mb-6"></div>

                    {{-- Distribution chart --}}
                    <div id="pmDistChartCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-pie-chart fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.payment_method_distribution') }}</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="pmDistChart" style="height:400px;"></div>
                        </div>
                    </div>

                    {{-- Trends chart --}}
                    <div id="pmTrendsChartCard" class="card mb-6 d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('auth.payment_method_trends') }}</h3>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="pmTrendsChart" style="height:400px;"></div>
                        </div>
                    </div>

                    {{-- Failed transactions --}}
                    <div id="pmFailedCard" class="card mb-6 d-none border border-danger">
                        <div class="card-header bg-light-danger border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-x-circle fs-2 me-2 text-danger"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.failed_transactions_analysis') }}</h3>
                                </div>
                                <span class="badge badge-light-danger fs-7" id="pmFailedBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                    <th class="ps-4">{{ __('auth.payment_method') }}</th>
                                    <th class="text-center">{{ __('accounting.type') }}</th>
                                    <th class="text-center">{{ __('auth.failed_count') }}</th>
                                    <th class="text-end">{{ __('auth.failed_amount') }}</th>
                                    <th class="pe-4" style="width:30%;">{{ __('auth.failure_rate') }}</th>
                                </tr></thead>
                                <tbody id="pmFailedBody"></tbody>
                                <tfoot class="bg-light" id="pmFailedFoot"></tfoot>
                            </table></div>
                        </div>
                    </div>

                    {{-- Methods table --}}
                    <div id="pmMethodsCard" class="card d-none">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.payment_method_analysis_details') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="pmMethodsBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="pmMethodsTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4 text-center">{{ __('accounting.rank') }}</th>
                                    <th>{{ __('auth.payment_method') }}</th>
                                    <th class="text-center">{{ __('accounting.type') }}</th>
                                    <th class="text-center">{{ __('accounting.status') }}</th>
                                    <th class="text-center">{{ __('auth.txn_count') }}</th>
                                    <th class="text-end">{{ __('accounting.total_amount') }}</th>
                                    <th class="text-end">{{ __('auth.avg_txn') }}</th>
                                    <th class="text-end">{{ __('auth.largest') }}</th>
                                    <th class="text-center">{{ __('auth.failed_txn') }}</th>
                                    <th class="text-center">{{ __('auth.success_rate') }}</th>
                                    <th>{{ __('auth.last_transaction') }}</th>
                                    <th class="pe-4 text-center" style="width:12%;">{{ __('auth.share') }}</th>
                                </tr></thead>
                                <tbody id="pmMethodsBody"></tbody>
                                <tfoot class="bg-light" id="pmMethodsFoot"></tfoot>
                            </table></div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="pmPaginationWrap">
                                <span class="text-muted fs-7" id="pmPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="pmPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════ --}}
{{-- DRAWER — transactions (successful or failed)                   --}}
{{-- ═══════════════════════════════════════════════════════════════ --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="pmDrawer" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-receipt fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="pmDrawerLabel">Transactions</h4>
                <span class="text-white opacity-75 fs-7" id="pmDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="pmDrawerSkeleton">
            <div class="pm-sk pm-sk-text mb-3" style="width:60%;"></div>
            <div class="pm-sk pm-sk-text mb-3" style="width:40%;"></div>
            <div class="pm-sk pm-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="pmDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .pm-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: pm-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .pm-sk-text   { height: 12px; border-radius: 4px; }
    .pm-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .pm-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes pm-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #pmSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .pm-row { cursor: pointer; }
    .pm-row:hover { background-color: #f9f9f9; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.PM_ROUTES = {
        data:      '{{ route("reports.orders.by-payment-method-data") }}',
        txns:      '{{ route("reports.orders.by-payment-method-transactions", ["methodId" => "__ID__"]) }}',
        failedTxns:'{{ route("reports.orders.by-payment-method-failed", ["methodId" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.PM_BOOTSTRAP = {
        startDate:           @json($startDate),
        endDate:             @json($endDate),
        locationId:          @json($locationId),
        departmentId:        @json($departmentId),
        paymentType:         @json($paymentType),
        paymentStatus:       @json($paymentStatus ?? 'all'),
        paymentMethodStatus: @json($paymentMethodStatus ?? 'all'),
        minAmount:           @json($minAmount),
        maxAmount:           @json($maxAmount),
        perPage:             @json($perPage),
    };
    window.PM_I18N = {
        // KPI
        paymentMethods:      @json(__('auth.payment_methods')),
        totalTransactions:   @json(__('auth.total_transactions')),
        totalProcessed:      @json(__('auth.total_processed')),
        avgTransaction:      @json(__('auth.avg_transaction')),
        successRate:         @json(__('auth.success_rate')),
        mostUsed:            @json(__('auth.most_used_method')),

        // table
        rank:                @json(__('accounting.rank')),
        paymentMethod:       @json(__('auth.payment_method')),
        type:                @json(__('accounting.type')),
        status:              @json(__('accounting.status')),
        txnCount:            @json(__('auth.txn_count')),
        totalAmount:         @json(__('accounting.total_amount')),
        avgTxn:              @json(__('auth.avg_txn')),
        largest:             @json(__('auth.largest')),
        failedTxn:           @json(__('auth.failed_txn')),
        lastTransaction:     @json(__('auth.last_transaction')),
        share:               @json(__('auth.share')),
        active:              @json(__('auth.active')),
        inactive:            @json(__('auth.inactive')),

        // failed
        failedAnalysis:      @json(__('auth.failed_transactions_analysis')),
        failedCount:         @json(__('auth.failed_count')),
        failedAmount:        @json(__('auth.failed_amount')),
        failureRate:         @json(__('auth.failure_rate')),

        // charts
        totalAmountLbl:      @json(__('auth.total_amount')),
        dailyAmount:         @json(__('auth.daily_amount')),
        noTrends:            @json(__('auth.no_trend_data_available')),

        // drawer
        transactions:        @json(__('auth.transactions')),
        transactionId:       @json(__('auth.transaction_id')),
        orderNumber:         @json(__('auth.order_number')),
        processedAt:         @json(__('auth.processed_at')),
        processor:           @json(__('auth.processor')),
        recordedVia:         @json(__('auth.recorded_via')),
        notes:               @json(__('auth.notes')),
        noTransactions:      @json(__('auth.no_transactions_found')),
    };
</script>
@include('reports.orders.by-payment-method-scripts')
@endpush