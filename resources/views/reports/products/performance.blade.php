@extends('layouts.app')

@section('title', __('auth.product_performance'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- TOOLBAR --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">{{ __('auth.product_performance') }}</h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('auth._dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_performance') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" id="perfExport" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span>{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="performanceTable"
                                           data-export-filename="product_performance"
                                           data-export-sheet="Performance">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="performanceTable"
                                           data-export-filename="product_performance"
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
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="perfFilterForm">
                                    <div class="row g-4">
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <select class="form-select" name="category_id">
                                                <option value="">{{ __('accounting.all_categories') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('auth.product_type') }}</label>
                                            <select class="form-select" name="product_type">
                                                <option value="all">{{ __('auth.all_types') }}</option>
                                                <option value="physical">{{ __('auth.physical') }}</option>
                                                <option value="digital">{{ __('auth.digital') }}</option>
                                                <option value="service">{{ __('auth.service') }}</option>
                                                <option value="composite">{{ __('auth.composite') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.per_page') }}</label>
                                            <select class="form-select" name="per_page" id="perfPerPage">
                                                <option value="15">15 / page</option>
                                                <option value="25">25 / page</option>
                                                <option value="50">50 / page</option>
                                                <option value="100">100 / page</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3 d-flex align-items-end">
                                            <div class="d-flex gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                                </button>
                                                <button type="button" id="perfClear" class="btn btn-light">
                                                    <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUMMARY CARDS --}}
                <div class="row mb-6" id="perfSummaryRow">
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-primary border border-primary border-dashed">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-package fs-2tx text-primary mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800" id="perfStatProducts">—</div>
                                <div class="text-gray-600">{{ __('auth.total_products') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-success border border-success border-dashed">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-inbox fs-2tx text-success mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800" id="perfStatStock">—</div>
                                <div class="text-gray-600">{{ __('auth.total_stock') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-info border border-info border-dashed">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-dollar fs-2tx text-info mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800" id="perfStatRevenue">—</div>
                                <div class="text-gray-600">{{ __('auth.total_revenue_value') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-flush bg-light-danger border border-danger border-dashed">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-growth fs-2tx text-danger mb-3"></i>
                                <div class="fs-1 fw-bold text-gray-800" id="perfStatMargin">—</div>
                                <div class="text-gray-600">{{ __('auth.total_margin') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CHARTS --}}
                <div class="row mb-6">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i> {{ __('auth.margin_distribution') }}</h3>
                            </div>
                            <div class="card-body"><div id="marginDistributionChart" style="height:350px;"></div></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i> {{ __('auth.top_performers') }} (Top 10)</h3>
                            </div>
                            <div class="card-body"><div id="topPerformersChart" style="height:350px;"></div></div>
                        </div>
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.product_performance') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div id="perfLoading" class="text-center py-10 d-none">
                                    <div class="spinner-border text-primary"></div>
                                </div>
                                <div id="perfWrapper" class="d-none">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="performanceTable">
                                            <thead class="bg-light">
                                                <tr class="fw-bold fs-6 text-gray-800">
                                                    <th class="ps-4">{{ __('auth.sku') }}</th>
                                                    <th>{{ __('accounting.name') }}</th>
                                                    <th>{{ __('accounting.category') }}</th>
                                                    <th>{{ __('accounting.type') }}</th>
                                                    <th class="text-center">{{ __('auth.total_stock') }}</th>
                                                    <th class="text-end">{{ __('auth.total_cost_value') }}</th>
                                                    <th class="text-end">{{ __('auth.total_revenue_value') }}</th>
                                                    <th class="text-end">{{ __('auth.total_margin') }}</th>
                                                    <th class="text-center">{{ __('auth.margin_percentage') }}</th>
                                                    <th class="text-center">{{ __('accounting.performance') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="perfBody"></tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex flex-stack flex-wrap p-6">
                                        <div class="fs-6 fw-semibold text-gray-700" id="perfPageInfo">—</div>
                                        <ul class="pagination m-0" id="perfPagination"></ul>
                                    </div>
                                </div>
                                <div id="perfEmpty" class="text-center py-10 d-none">
                                    <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                                    <h4 class="text-gray-600">{{ __('accounting.no_data_available') }}</h4>
                                    <p class="text-muted">{{ __('auth.no_products_found') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('reports.products.partials.product-drawer')
@include('reports.products.partials.variants-drawer')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.PRODUCTS_ROUTES = Object.assign(window.PRODUCTS_ROUTES || {}, {
        performanceData: '{{ route('reports.products.performance-data') }}',
        performanceMeta: '{{ route('reports.products.performance-meta') }}',
        productDrawer:   '{{ route('reports.products.product-drawer', ['id' => '__ID__']) }}',
        variantDrawer:   '{{ route('reports.products.variant-drawer', ['id' => '__ID__']) }}',
    });
    window.PERF_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.products.partials.performance-scripts')
@include('reports.products.partials.product-drawer-scripts')
@include('reports.products.partials.variants-drawer-scripts')
@endpush