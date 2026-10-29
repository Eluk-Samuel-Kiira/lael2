@extends('layouts.app')

@section('title', __('accounting.expenses_by_employee'))

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
                                {{ __('accounting.expenses_by_employee') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('accounting.employee_analysis') }}</li>
                            </ul>
                        </div>

                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" id="expEmpExport" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span>{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="employeeTable"
                                           data-export-filename="expenses_by_employee"
                                           data-export-sheet="Employee Breakdown">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="employeeTable"
                                           data-export-filename="expenses_by_employee"
                                           data-export-format="csv">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('accounting.export_to_csv') }}
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
                                    <span class="badge badge-light-info fs-7" id="expDateBadge">
                                        <i class="ki-duotone ki-calendar-8 fs-4 me-1"></i>
                                        <span id="expDateBadgeText">—</span>
                                    </span>
                                </div>
                            </div>

                            <div class="card-body pt-0">
                                <form id="expEmpFilterForm">
                                    <div class="row g-4 mb-4">
                                        <div class="col-xl-12">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ki-duotone ki-calendar-8 fs-2"></i></span>
                                                    <input type="date" class="form-control" name="start_date" required>
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-1">
                                                    {{ __('accounting.to') }}
                                                </span>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="ki-duotone ki-calendar-8 fs-2"></i></span>
                                                    <input type="date" class="form-control" name="end_date" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-4">
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.employee') }}</label>
                                            <select class="form-select" name="employee_id">
                                                <option value="">{{ __('accounting.all_employees') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.location') }}</label>
                                            <select class="form-select" name="location_id">
                                                <option value="">{{ __('pagination.all_locations') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.requires_approval') }}</label>
                                            <select class="form-select" name="requires_approval">
                                                <option value="">{{ __('accounting.all_statuses') }}</option>
                                                <option value="1">{{ __('accounting.yes') }}</option>
                                                <option value="0">{{ __('accounting.no') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3 d-flex align-items-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <button type="button" id="expEmpClear" class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.clear') }}</span>
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

                {{-- SUMMARY STATS --}}
                <div class="row mb-6" id="summaryCardRow">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.employee_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-user fs-2tx text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statTotalEmployees">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_employees') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-receipt fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statTotalTransactions">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_transactions') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-chart-simple fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statGrandTotal">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.grand_total') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-receipt-tax fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statTotalTax">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_tax') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-calculator fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statAvgPerEmployee">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.average_per_employee') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-ranking fs-2tx text-secondary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statTopEmployee">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.top_employee') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BREAKDOWN TABLE --}}
                <div class="card mt-6">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.employee_breakdown') }}</h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7" id="employeeCountBadge">—</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="empLoading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div id="empWrapper" class="table-responsive d-none">
                            <table class="table table-bordered table-striped align-middle mb-0" id="employeeTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50" class="text-center">#</th>
                                        <th>{{ __('accounting.employee') }}</th>
                                        <th width="150">{{ __('accounting.department') }}</th>
                                        <th width="120" class="text-center">{{ __('accounting.expenses_count') }}</th>
                                        <th width="150" class="text-end">{{ __('accounting.grand_total') }}</th>
                                        <th width="120" class="text-end">{{ __('accounting.average') }}</th>
                                        <th width="120" class="text-end">{{ __('accounting.max_expense') }}</th>
                                        <th width="180">{{ __('accounting.payment_status') }}</th>
                                        <th width="150" class="text-center">{{ __('accounting.distribution') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="empBody"></tbody>
                                <tfoot class="table-light" id="empFoot"></tfoot>
                            </table>
                        </div>
                        <div id="empEmpty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('accounting.no_expenses_found_for_employees') }}</p>
                        </div>
                    </div>
                </div>

                {{-- CHART --}}
                <div class="row mt-6" id="chartCard" style="display:none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_employee_spending') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlySpendingChart" style="height: 400px;"></div>
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.EXP_EMP_ROUTES = {
        data: '{{ route('reports.expenses.by-employee-data') }}',
        meta: '{{ route('reports.expenses.by-employee-meta') }}',
    };
    window.EXP_EMP_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.expenses.partials.by-employee-scripts')
@endpush