@extends('layouts.app')

@section('title', __('pagination.supplier_risk_assessment'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- Toolbar --}}
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0 w-100">

                        {{-- Title block — extreme left --}}
                        <div class="page-title d-flex flex-column align-items-start text-start me-auto">
                            <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                                {{ __('pagination.supplier_risk_assessment') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.supplier_risk_assessment') }}</li>
                            </ul>
                        </div>

                        {{-- Export button — extreme right --}}
                        <div class="d-flex align-items-stretch w-100 w-lg-auto ms-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0)" id="srExportExcel">
                                        <i class="bi bi-file-earmark-excel fs-2 me-2 text-success"></i>{{ __('pagination.export_to_excel') }}
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Intro --}}
                <div class="row mb-6">
                    <div class="col-12"><div class="card"><div class="card-body">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shield-check fs-4tx text-primary me-4"></i>
                            <div>
                                <h3 class="fw-bold text-gray-800 mb-2">{{ __('passwords.supplier_risk_assessment') }}</h3>
                                <p class="text-muted mb-0">{{ __('passwords.risk_assessment_description') }}</p>
                            </div>
                        </div>
                    </div></div></div>
                </div>

                {{-- SKELETON --}}
                <div id="srSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="sr-sk sr-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="sr-sk sr-sk-text mb-2" style="width:60%;"></div>
                                    <div class="sr-sk sr-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="sr-sk sr-sk-chart"></div></div></div></div>
                        <div class="col-lg-4"><div class="card"><div class="card-body"><div class="sr-sk sr-sk-chart" style="height:350px;"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 8; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="sr-sk sr-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="sr-sk sr-sk-text mb-2" style="width:35%;"></div>
                                <div class="sr-sk sr-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="sr-sk sr-sk-badge"></div>
                            <div class="sr-sk sr-sk-text" style="width:100px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="srContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6" id="srSummaryCards"></div>

                    {{-- Risk distribution --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8">
                            <div class="card h-100">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="bi bi-pie-chart fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.risk_distribution') }}</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div id="srChart" style="height:350px;"></div>
                                    <div id="srChartEmpty" class="text-center text-muted py-10 d-none">{{ __('passwords.no_data') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card h-100">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="bi bi-info-circle fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.risk_legend') }}</h3>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bullet bullet-dot bg-danger h-10px w-10px me-3"></div>
                                            <div class="fw-bold text-gray-800">{{ __('passwords.high_risk') }}</div>
                                        </div>
                                        <p class="text-muted fs-7 mb-0">{{ __('passwords.high_risk_description') }}</p>
                                    </div>
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bullet bullet-dot bg-warning h-10px w-10px me-3"></div>
                                            <div class="fw-bold text-gray-800">{{ __('passwords.medium_risk') }}</div>
                                        </div>
                                        <p class="text-muted fs-7 mb-0">{{ __('passwords.medium_risk_description') }}</p>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="bullet bullet-dot bg-success h-10px w-10px me-3"></div>
                                            <div class="fw-bold text-gray-800">{{ __('passwords.low_risk') }}</div>
                                        </div>
                                        <p class="text-muted fs-7 mb-0">{{ __('passwords.low_risk_description') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Critical suppliers --}}
                    <div class="row g-6 mb-6 d-none" id="srCriticalWrap">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-exclamation-triangle fs-2 me-2 text-danger"></i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.critical_suppliers') }}</h3>
                                    </div>
                                    <span class="badge badge-light-danger fs-7" id="srCriticalBadge"></span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="ps-4">{{ __('passwords.supplier') }}</th>
                                        <th>{{ __('passwords.risk_score') }}</th>
                                        <th>{{ __('passwords.total_spent') }}</th>
                                        <th>{{ __('passwords.payment_terms') }}</th>
                                        <th>{{ __('passwords.delivery_performance') }}</th>
                                        <th class="pe-4">{{ __('passwords.primary_risk_factors') }}</th>
                                    </tr></thead>
                                    <tbody id="srCriticalBody"></tbody>
                                </table></div>
                                <div class="card-footer d-flex justify-content-between align-items-center d-none" id="srCriticalPaginationWrap">
                                    <span class="text-muted fs-7" id="srCriticalPaginationInfo"></span>
                                    <nav><ul class="pagination m-0" id="srCriticalPagination"></ul></nav>
                                </div>
                            </div>
                        </div></div>
                    </div>

                    {{-- All suppliers --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.all_suppliers_assessment') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="srBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">#</th>
                                    <th>{{ __('passwords.supplier') }}</th>
                                    <th>{{ __('passwords.risk_level') }}</th>
                                    <th>{{ __('passwords.risk_score') }}</th>
                                    <th>{{ __('passwords.financial_impact') }}</th>
                                    <th>{{ __('passwords.delivery_performance') }}</th>
                                    <th>{{ __('passwords.contract_terms') }}</th>
                                    <th class="pe-4">{{ __('passwords.risk_factors') }}</th>
                                </tr></thead>
                                <tbody id="srTableBody"></tbody>
                            </table></div>
                            <div id="srEmpty" class="text-center py-10 d-none">
                                <i class="bi bi-shield fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_suppliers_found') }}</h4>
                                <p class="text-muted fs-6">{{ __('passwords.no_active_suppliers_to_assess') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="srPaginationWrap">
                                <span class="text-muted fs-7" id="srPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="srPagination"></ul></nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .sr-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: sr-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .sr-sk-text   { height: 12px; border-radius: 4px; }
    .sr-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .sr-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .sr-sk-chart  { width: 100%; height: 350px; border-radius: 8px; }
    @keyframes sr-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #srSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.SR_ROUTES = { data: '{{ route("reports.purchasing.supplier-risk-assessment-data") }}' };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.SR_BOOTSTRAP = {
        perPage:         @json($perPage),
        criticalPerPage: @json($criticalPerPage),
    };
    window.SR_I18N = {
        highRisk:        @json(__('passwords.high_risk')),
        mediumRisk:      @json(__('passwords.medium_risk')),
        lowRisk:         @json(__('passwords.low_risk')),
        highRiskLabel:   @json(__('passwords.high_risk_suppliers')),
        mediumRiskLabel: @json(__('passwords.medium_risk_suppliers')),
        lowRiskLabel:    @json(__('passwords.low_risk_suppliers')),
        totalLabel:      @json(__('passwords.total_suppliers_assessed')),
        activeAnalyzed:  @json(__('passwords.active_suppliers_analyzed')),
        ofTotal:         @json(__('passwords.of_total')),
        orders:          @json(__('passwords.orders')),
        days:            @json(__('passwords.days')),
        daysAvg:         @json(__('passwords.days_avg')),
        deliveredOrders: @json(__('passwords.delivered_orders')),
        noContact:       @json(__('pagination.no_contact')),

        // risk factors
        low_order_frequency:    @json(__('passwords.low_order_frequency')),
        extended_payment_terms: @json(__('passwords.extended_payment_terms')),
        single_sourcing_risk:   @json(__('passwords.single_sourcing_risk')),
        slow_delivery:          @json(__('passwords.slow_delivery')),
        international_supplier: @json(__('passwords.international_supplier')),

        // financial impact
        critical_impact: @json(__('passwords.critical_impact')),
        high_impact:     @json(__('passwords.high_impact')),
        medium_impact:   @json(__('passwords.medium_impact')),
        low_impact:      @json(__('passwords.low_impact')),

        // delivery
        poor_delivery:     @json(__('passwords.poor_delivery')),
        average_delivery:  @json(__('passwords.average_delivery')),
        moderate_delivery: @json(__('passwords.moderate_delivery')),
        good_delivery:     @json(__('passwords.good_delivery')),

        // contract
        unfavorable_terms: @json(__('passwords.unfavorable_terms')),
        extended_terms:    @json(__('passwords.extended_terms')),
        standard_terms:    @json(__('passwords.standard_terms')),
        favorable_terms:   @json(__('passwords.favorable_terms')),

        // risk level labels
        high_risk:   @json(__('passwords.high_risk')),
        medium_risk: @json(__('passwords.medium_risk')),
        low_risk:    @json(__('passwords.low_risk')),
    };
</script>
@include('reports.purchasing.supplier-risk-assessment-scripts')
@endpush