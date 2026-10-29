@extends('layouts.app')

@section('title', __('accounting.budget_vs_actual'))

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
                                {{ __('accounting.budget_vs_actual') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('accounting.budget_analysis') }}</li>
                            </ul>
                        </div>

                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" id="expBvAExport" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span>{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="budgetTable"
                                           data-export-filename="budget_vs_actual"
                                           data-export-sheet="Budget vs Actual">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="budgetTable"
                                           data-export-filename="budget_vs_actual"
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
                                <form id="expBvAFilterForm">
                                    <div class="row g-4 mb-4">
                                        <div class="col-xl-3">
                                            <label class="form-label required fw-semibold">{{ __('accounting.year') }}</label>
                                            <select class="form-select" name="year" required></select>
                                        </div>
                                        <div class="col-xl-3">
                                            <label class="form-label required fw-semibold">{{ __('accounting.month') }}</label>
                                            <select class="form-select" name="month" required></select>
                                        </div>
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.location') }}</label>
                                            <select class="form-select" name="location_id">
                                                <option value="">{{ __('pagination.all_locations') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3 d-flex align-items-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <button type="button" id="expBvAClear" class="btn btn-light btn-active-light-primary flex-grow-1">
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

                {{-- STAT CARDS --}}
                <div class="row mb-6" id="summaryCardRow">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.budget_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-3"><i class="ki-duotone ki-dollar fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-2"><span class="fs-2 fw-bold text-gray-800 d-block" id="statTotalBudgetMonthly">—</span><small class="text-gray-600 fw-semibold">{{ __('accounting.monthly') }}</small></div>
                                                <div class="border-top border-gray-300 pt-2 mt-2"><span class="fs-4 fw-bold text-gray-700 d-block" id="statTotalBudgetAnnual">—</span><small class="text-gray-600 fw-semibold">{{ __('accounting.annual') }}</small></div>
                                                <div class="text-gray-600 fw-semibold mt-3 fs-7">{{ __('accounting.total_budget') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-3"><i class="ki-duotone ki-chart-simple fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-2"><span class="fs-2 fw-bold text-gray-800 d-block" id="statTotalActualMonthly">—</span><small class="text-gray-600 fw-semibold">{{ __('accounting.monthly') }}</small></div>
                                                <div class="border-top border-gray-300 pt-2 mt-2"><span class="fs-4 fw-bold text-gray-700 d-block" id="statTotalActualAnnual">—</span><small class="text-gray-600 fw-semibold">{{ __('accounting.annual') }}</small></div>
                                                <div class="text-gray-600 fw-semibold mt-3 fs-7">{{ __('accounting.total_actual') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush border border-dashed h-100" id="statVarianceCard">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-3"><i class="ki-duotone ki-arrow-up fs-2tx" id="statVarianceIcon"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-2"><span class="fs-2 fw-bold text-gray-800 d-block" id="statVarianceMonthly">—</span><small class="text-gray-600 fw-semibold">{{ __('accounting.monthly') }}</small></div>
                                                <div class="border-top border-gray-300 pt-2 mt-2"><span class="fs-4 fw-bold text-gray-700 d-block" id="statVarianceAnnual">—</span><small class="text-gray-600 fw-semibold">{{ __('accounting.annual') }}</small></div>
                                                <div class="text-gray-600 fw-semibold mt-3 fs-7">{{ __('accounting.total_variance') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush border border-dashed h-100" id="statPctCard">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-3"><i class="ki-duotone ki-percentage fs-2tx" id="statPctIcon"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-2"><span class="fs-2 fw-bold text-gray-800 d-block" id="statPctMonthly">—</span><small class="text-gray-600 fw-semibold">{{ __('accounting.monthly') }}</small></div>
                                                <div class="border-top border-gray-300 pt-2 mt-2"><span class="fs-4 fw-bold text-gray-700 d-block" id="statPctAnnual">—</span><small class="text-gray-600 fw-semibold">{{ __('accounting.annual') }}</small></div>
                                                <div class="text-gray-600 fw-semibold mt-3 fs-7">{{ __('accounting.variance_percentage') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-3"><i class="ki-duotone ki-check-circle fs-2tx text-success"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></div>
                                                <div class="mb-2"><span class="fs-2 fw-bold text-gray-800 d-block" id="statUnderBudget">—</span></div>
                                                <div class="text-gray-600 fw-semibold mt-3 fs-7">{{ __('accounting.categories_under_budget') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-3"><i class="ki-duotone ki-warning-circle fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="mb-2"><span class="fs-2 fw-bold text-gray-800 d-block" id="statOverBudget">—</span></div>
                                                <div class="text-gray-600 fw-semibold mt-3 fs-7">{{ __('accounting.categories_over_budget') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- BUDGET TABLE --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('accounting.budget_vs_actual_details') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7" id="budgetCountBadge">—</span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div id="bvALoading" class="text-center py-10 d-none">
                                    <div class="spinner-border text-primary"></div>
                                </div>
                                <div id="bvAWrapper" class="table-responsive d-none">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="budgetTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="min-w-200px ps-4">{{ __('accounting.category') }}</th>
                                                <th class="min-w-120px">{{ __('accounting.budget') }}</th>
                                                <th class="min-w-120px">{{ __('accounting.actual') }}</th>
                                                <th class="min-w-120px">{{ __('accounting.variance') }}</th>
                                                <th class="min-w-100px">{{ __('accounting.variance_percentage') }}</th>
                                                <th class="min-w-120px">{{ __('accounting.status') }}</th>
                                                <th class="min-w-150px">{{ __('accounting.progress') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bvABody"></tbody>
                                        <tfoot class="fw-bold bg-light" id="bvAFoot"></tfoot>
                                    </table>
                                </div>
                                <div id="bvAEmpty" class="text-center py-10 d-none">
                                    <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                    <p class="text-muted fs-6">{{ __('accounting.no_budget_categories_found') }}</p>
                                    <p class="text-muted fs-7 mb-4">{{ __('accounting.set_up_budgets_message') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TREND CHART --}}
                <div class="row" id="chartCard" style="display:none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_budget_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="budgetTrendsChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TOP PERFORMERS --}}
                <div class="row mt-6" id="topCard" style="display:none;">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-ranking fs-2 me-2 text-primary">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.top_performing_categories') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="topTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.category') }}</th>
                                                <th>{{ __('accounting.budget_utilization') }}</th>
                                                <th>{{ __('accounting.variance') }}</th>
                                                <th>{{ __('accounting.trend') }}</th>
                                                <th>{{ __('accounting.recommendation') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="topBody"></tbody>
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
    window.EXP_BVA_ROUTES = {
        data: '{{ route('reports.expenses.budget-vs-actual-data') }}',
        meta: '{{ route('reports.expenses.budget-vs-actual-meta') }}',
    };
    window.EXP_BVA_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.expenses.partials.budget-vs-actual-scripts')
@endpush