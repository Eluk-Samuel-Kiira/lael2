@extends('layouts.app')

@section('title', __('accounting.recurring_expenses_report'))

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
                                {{ __('accounting.recurring_expenses_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('accounting.recurring_analysis') }}</li>
                            </ul>
                        </div>

                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" id="expRecExport" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span>{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="recurringTable"
                                           data-export-filename="recurring_expenses"
                                           data-export-sheet="Recurring Expenses">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="recurringTable"
                                           data-export-filename="recurring_expenses"
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
                            </div>
                            <div class="card-body pt-0">
                                <form id="expRecFilterForm">
                                    <div class="row g-4 mb-4">
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.frequency') }}</label>
                                            <select class="form-select" name="frequency">
                                                <option value="">{{ __('accounting.all_frequencies') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <select class="form-select" name="category_id">
                                                <option value="">{{ __('accounting.all_categories') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.location') }}</label>
                                            <select class="form-select" name="location_id">
                                                <option value="">{{ __('pagination.all_locations') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.status') }}</label>
                                            <select class="form-select" name="status">
                                                <option value="active">{{ __('accounting.active') }}</option>
                                                <option value="upcoming">{{ __('accounting.upcoming') }} (7 {{ __('accounting.days') }})</option>
                                                <option value="overdue">{{ __('accounting.overdue') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row g-4">
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <button type="button" id="expRecClear" class="btn btn-light btn-active-light-primary">
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

                {{-- SUMMARY CARDS --}}
                <div class="row mb-6" id="summaryCardRow">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.recurring_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-repeat fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statTotalRecurring">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_recurring_expenses') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-dollar fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statMonthlyCost">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_monthly_recurring') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-chart-simple fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statAnnual">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.annual_projection') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-calendar-8 fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statUpcoming">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.upcoming_30_days') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-dollar fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statUpcomingAmount">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.upcoming_amount') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-calculator fs-2tx text-secondary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statAvg">—</span></div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.average_recurring') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RECURRING TABLE --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('accounting.recurring_expenses') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7" id="recCountBadge">—</span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div id="recLoading" class="text-center py-10 d-none">
                                    <div class="spinner-border text-primary"></div>
                                </div>
                                <div id="recWrapper" class="table-responsive d-none">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="recurringTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="min-w-200px ps-4">{{ __('accounting.description') }}</th>
                                                <th class="min-w-120px">{{ __('accounting.category') }}</th>
                                                <th class="min-w-120px">{{ __('accounting.frequency') }}</th>
                                                <th class="min-w-120px">{{ __('accounting.next_date') }}</th>
                                                <th class="min-w-120px">{{ __('accounting.amount') }}</th>
                                                <th class="min-w-120px">{{ __('accounting.annual_total') }}</th>
                                                <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                                <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                                <th class="min-w-150px">{{ __('accounting.days_until') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recBody"></tbody>
                                    </table>
                                </div>
                                <div id="recEmpty" class="text-center py-10 d-none">
                                    <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                    <p class="text-muted fs-6">{{ __('accounting.no_recurring_expenses_found') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CHART --}}
                <div class="row mb-6" id="chartCard" style="display:none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_projection') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyProjectionChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FREQUENCY CARDS --}}
                <div class="row mb-6" id="freqCardRow" style="display:none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.breakdown_by_frequency') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6" id="freqCardBody"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- UPCOMING --}}
                <div class="row" id="upcomingCard" style="display:none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.upcoming_next_30_days') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="upcomingTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.date') }}</th>
                                                <th>{{ __('accounting.description') }}</th>
                                                <th>{{ __('accounting.amount') }}</th>
                                                <th>{{ __('accounting.frequency') }}</th>
                                                <th>{{ __('accounting.payment_method') }}</th>
                                                <th>{{ __('accounting.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="upcomingBody"></tbody>
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.EXP_REC_ROUTES = {
        data: '{{ route('reports.expenses.recurring-data') }}',
        meta: '{{ route('reports.expenses.recurring-meta') }}',
    };
    window.EXP_REC_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.expenses.partials.recurring-scripts')
@endpush