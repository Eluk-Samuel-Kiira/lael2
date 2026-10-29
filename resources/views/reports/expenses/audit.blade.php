@extends('layouts.app')

@section('title', __('accounting.expense_audit_report'))

@section('content')
@if (tenant_can('advanced_reports'))
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- TOOLBAR --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('accounting.expense_audit_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('accounting.audit_and_compliance') }}</li>
                            </ul>
                        </div>

                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" id="expAuditExport" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span>{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="auditItemsTable"
                                           data-export-filename="expense_audit"
                                           data-export-sheet="Audit Items">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="auditItemsTable"
                                           data-export-filename="expense_audit"
                                           data-export-format="csv">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('accounting.export_to_csv') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" id="btnAuditPDF">
                                            <i class="ki-duotone ki-file-pdf fs-2 me-2 text-danger"></i>
                                            {{ __('accounting.export_to_pdf') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FILTERS --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-info fs-7" id="auditDateBadge">
                                        <i class="ki-duotone ki-calendar-8 fs-4 me-1"></i>
                                        <span id="auditDateBadgeText">—</span>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="auditFilterForm">
                                    {{-- Row 1: date range --}}
                                    <div class="row g-4 mb-4">
                                        <div class="col-xl-12">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ki-duotone ki-calendar-8 fs-2"></i></span>
                                                    <input type="date" class="form-control" name="start_date" required>
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-1">{{ __('accounting.to') }}</span>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ki-duotone ki-calendar-8 fs-2"></i></span>
                                                    <input type="date" class="form-control" name="end_date" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Row 2: single row of filters + actions --}}
                                    <div class="row g-4">
                                        <div class="col-xl-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.audit_type') }}</label>
                                            <select class="form-select" name="audit_type" id="auditTypeSelect"></select>
                                        </div>
                                        <div class="col-xl-2" id="thresholdField" style="display:none;">
                                            <label class="form-label fw-semibold">{{ __('accounting.threshold') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text" id="thresholdCurrency">—</span>
                                                <input type="number" class="form-control" name="threshold" min="0" step="100">
                                            </div>
                                        </div>
                                        <div class="col-xl-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.employee') }}</label>
                                            <select class="form-select" name="employee_id">
                                                <option value="">{{ __('accounting.all_employees') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.location') }}</label>
                                            <select class="form-select" name="location_id">
                                                <option value="">{{ __('pagination.all_locations') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.per_page') }}</label>
                                            <select class="form-select" name="per_page" id="auditPerPage">
                                                <option value="15">15 / page</option>
                                                <option value="25">25 / page</option>
                                                <option value="50">50 / page</option>
                                                <option value="100">100 / page</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-2 d-flex align-items-end">
                                            <div class="d-flex gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <button type="button" id="auditClear" class="btn btn-light" title="{{ __('accounting.clear_filters') }}">
                                                    <i class="ki-duotone ki-cross fs-2"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div id="activeFiltersBar" class="mt-4 d-none">
                                    <div class="separator separator-dashed my-4"></div>
                                    <div class="d-flex align-items-center flex-wrap gap-2" id="activeFiltersList"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STAT CARDS --}}
                <div class="row mb-6" id="summaryCardRow">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.audit_statistics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-receipt fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statTotalItems">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_audit_items') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-dollar fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statTotalAmount">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_amount_audited') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-document fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statMissingReceipts">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.missing_receipts') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-shield-check fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statUnapproved">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.unapproved_expenses') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-chart-line-up fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statHighValue">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.high_value_items') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-clock fs-2tx text-secondary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statAvgAge">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.average_age_days') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="card card-flush bg-light-dark border border-dark border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-check fs-2tx text-dark"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statComplianceRate">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.compliance_rate') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-calculator fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statAvgItemAmount">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.average_item_amount') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- AUDIT ITEMS --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0" id="auditItemsTitle">{{ __('accounting.all_audit_items') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7" id="auditItemsBadge">—</span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div id="auditLoading" class="text-center py-10 d-none">
                                    <div class="spinner-border text-primary"></div>
                                </div>
                                <div id="auditWrapper" class="d-none">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="auditItemsTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-100px ps-4">{{ __('accounting.date') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.description') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.category') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.employee') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.amount') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.status') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.receipt') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.approval') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.age_days') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.audit_flags') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="auditBody"></tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex flex-stack flex-wrap p-6">
                                        <div class="fs-6 fw-semibold text-gray-700" id="auditPageInfo">—</div>
                                        <ul class="pagination m-0" id="auditPagination"></ul>
                                    </div>
                                </div>
                                <div id="auditEmpty" class="text-center py-10 d-none">
                                    <i class="ki-duotone ki-shield-check fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                    <p class="text-muted fs-6">{{ __('accounting.no_audit_items_found') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BY CATEGORY --}}
                <div class="row mb-6" id="catCard" style="display:none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.audit_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="catTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.category') }}</th>
                                                <th>{{ __('accounting.expense_count') }}</th>
                                                <th>{{ __('accounting.total_amount') }}</th>
                                                <th>{{ __('accounting.average_amount') }}</th>
                                                <th>{{ __('accounting.missing_receipts') }}</th>
                                                <th>{{ __('accounting.unapproved') }}</th>
                                                <th>{{ __('accounting.compliance_rate') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="catBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- EMPLOYEE COMPLIANCE --}}
                <div class="row mb-6" id="empCard" style="display:none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-user-tick fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.employee_compliance') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="empTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.employee') }}</th>
                                                <th>{{ __('accounting.employee_id') }}</th>
                                                <th>{{ __('accounting.total_expenses') }}</th>
                                                <th>{{ __('accounting.average_expense') }}</th>
                                                <th>{{ __('accounting.missing_receipts') }}</th>
                                                <th>{{ __('accounting.unapproved') }}</th>
                                                <th>{{ __('accounting.compliance_rate') }}</th>
                                                <th>{{ __('accounting.risk_level') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="empBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MONTHLY TREND --}}
                <div class="row" id="monthlyCard" style="display:none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_audit_trend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="monthlyTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.period') }}</th>
                                                <th>{{ __('accounting.total_expenses') }}</th>
                                                <th>{{ __('accounting.total_amount') }}</th>
                                                <th>{{ __('accounting.missing_receipts') }}</th>
                                                <th>{{ __('accounting.unapproved') }}</th>
                                                <th>{{ __('accounting.missing_receipt_rate') }}</th>
                                                <th>{{ __('accounting.unapproved_rate') }}</th>
                                                <th>{{ __('accounting.compliance_trend') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="monthlyBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    window.EXP_AUDIT_ROUTES = {
        data: '{{ route('reports.expenses.audit-data') }}',
        meta: '{{ route('reports.expenses.audit-meta') }}',
    };
    window.EXP_AUDIT_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.expenses.partials.audit-scripts')
@endpush