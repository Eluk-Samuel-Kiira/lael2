@extends('layouts.app')

@section('title', __('accounting.tax_report'))

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
                                {{ __('accounting.tax_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('accounting.tax_analysis') }}</li>
                            </ul>
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
                                    <span class="badge badge-light-info fs-7" id="taxDateBadge">
                                        <i class="ki-duotone ki-calendar-8 fs-4 me-1"></i>
                                        <span id="taxDateBadgeText">—</span>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="taxFilterForm">
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

                                    <div class="row g-4">
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <select class="form-select" name="category_id">
                                                <option value="">{{ __('accounting.all_categories') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.tax_type') }}</label>
                                            <select class="form-select" name="tax_type">
                                                <option value="all">{{ __('accounting.all_tax_types') }}</option>
                                                <option value="taxable">{{ __('accounting.taxable_only') }}</option>
                                                <option value="non-taxable">{{ __('accounting.non_taxable_only') }}</option>
                                            </select>
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
                                                <button type="button" id="taxClear" class="btn btn-light btn-active-light-primary flex-grow-1">
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
                <div class="row g-6 mb-6" id="summaryCardRow">
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4"><i class="ki-duotone ki-receipt fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statTotalExpenses">—</span></div>
                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_expenses') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4"><i class="ki-duotone ki-dollar fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statGross">—</span></div>
                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.gross_amount') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4"><i class="ki-duotone ki-receipt-tax fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i></div>
                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statTax">—</span></div>
                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.total_tax') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                <div class="mb-4"><i class="ki-duotone ki-percentage fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i></div>
                                <div class="mb-1"><span class="fs-2 fw-bold text-gray-800" id="statAvgTax">—</span></div>
                                <div class="text-gray-600 fw-semibold fs-7">{{ __('accounting.average_tax_rate') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CATEGORY TABLE --}}
                <div class="card mb-6" id="catCard" style="display:none;">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.tax_by_category') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="catTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.category') }}</th>
                                        <th class="text-center">{{ __('accounting.expense_count') }}</th>
                                        <th class="text-end">{{ __('accounting.gross_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.tax_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.net_amount') }}</th>
                                        <th class="text-center">{{ __('accounting.avg_tax_rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="catBody"></tbody>
                                <tfoot class="table-light fw-bold" id="catFoot"></tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- MONTHLY TABLE --}}
                <div class="card mb-6" id="monthlyCard" style="display:none;">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.monthly_tax_breakdown') }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="monthlyTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.period') }}</th>
                                        <th class="text-center">{{ __('accounting.expense_count') }}</th>
                                        <th class="text-end">{{ __('accounting.gross_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.tax_amount') }}</th>
                                        <th class="text-end">{{ __('accounting.net_amount') }}</th>
                                        <th class="text-center">{{ __('accounting.avg_tax_rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="monthlyBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- DISTRIBUTION TABLE --}}
                <div class="card" id="distCard" style="display:none;">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.tax_rate_distribution') }}</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="distTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">{{ __('accounting.tax_rate') }}</th>
                                        <th class="text-center">{{ __('accounting.expense_count') }}</th>
                                        <th class="text-end">{{ __('accounting.total_tax') }}</th>
                                        <th class="text-center">{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="distBody"></tbody>
                                <tfoot class="table-light fw-bold" id="distFoot"></tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- EMPTY --}}
                <div class="card" id="emptyCard" style="display:none;">
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('accounting.no_tax_data_found') }}</p>
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
    window.EXP_TAX_ROUTES = {
        data: '{{ route('reports.expenses.tax-report-data') }}',
        meta: '{{ route('reports.expenses.tax-report-meta') }}',
    };
    window.EXP_TAX_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.expenses.partials.tax-report-scripts')
@endpush