@extends('layouts.app')

@section('title', __('auth.by_category'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- TOOLBAR --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">{{ __('auth.by_category') }}</h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('auth._dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.by_category') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" id="bcExport" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span>{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="categoryTable"
                                           data-export-filename="products_by_category"
                                           data-export-sheet="Products by Category">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="categoryTable"
                                           data-export-filename="products_by_category"
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

                {{-- SUMMARY --}}
                <div class="row mb-6" id="bcSummaryRow">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.category_performance_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-category fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="bcStatCategories">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.total_categories') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-package fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="bcStatProducts">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.total_products') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-abstract-44 fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="bcStatVariants">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.total_variants') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-inbox fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="bcStatStock">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.total_stock') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-dollar fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="bcStatValue">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.total_value') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-percentage fs-2tx text-secondary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="bcStatMargin">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.average_margin') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CHARTS --}}
                <div class="row mb-6">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_categories_by_products') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0"><div id="topCategoriesChart" style="height:300px;"></div></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.category_value_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0"><div id="valueDistributionChart" style="height:300px;"></div></div>
                        </div>
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            <h3 class="fw-bold m-0">{{ __('auth.category_performance') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center gap-2">
                                <label class="fs-7 text-muted mb-0">{{ __('accounting.per_page') }}</label>
                                <select class="form-select form-select-sm w-90px" id="bcPerPage">
                                    <option value="15">15</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                                <span class="badge badge-light-primary fs-7" id="bcCountBadge">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="bcLoading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div id="bcWrapper" class="d-none">
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="categoryTable">
                                    <thead class="bg-light">
                                        <tr class="fw-bold fs-6 text-gray-800">
                                            <th class="ps-4 min-w-200px">{{ __('accounting.name') }}</th>
                                            <th class="min-w-100px text-center">{{ __('auth.product_count') }}</th>
                                            <th class="min-w-100px text-center">{{ __('auth.variant_count') }}</th>
                                            <th class="min-w-100px text-center">{{ __('auth.total_stock') }}</th>
                                            <th class="min-w-150px text-end">{{ __('auth.total_cost_value') }}</th>
                                            <th class="min-w-150px text-end">{{ __('auth.total_revenue_value') }}</th>
                                            <th class="min-w-150px text-end">{{ __('auth.total_margin') }}</th>
                                            <th class="min-w-120px text-center">{{ __('auth.margin_percentage') }}</th>
                                            <th class="min-w-120px text-center">{{ __('auth.performance') }}</th>
                                            <th class="min-w-80px text-center">{{ __('auth.ranking') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bcBody"></tbody>
                                    <tfoot class="bg-light" id="bcFoot"></tfoot>
                                </table>
                            </div>
                            <div class="d-flex flex-stack flex-wrap p-6">
                                <div class="fs-6 fw-semibold text-gray-700" id="bcPageInfo">—</div>
                                <ul class="pagination m-0" id="bcPagination"></ul>
                            </div>
                        </div>
                        <div id="bcEmpty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_categories_found') }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Drawer markup — must live in the DOM before scripts try to getInstance() --}}
@include('reports.products.partials.category-drawer')
@include('reports.products.partials.product-drawer')
@include('reports.products.partials.variants-drawer')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.PRODUCTS_ROUTES = Object.assign(window.PRODUCTS_ROUTES || {}, {
        byCategoryData: '{{ route('reports.products.by-category-data') }}',
        byCategoryMeta: '{{ route('reports.products.by-category-meta') }}',
        categoryDrawer: '{{ route('reports.products.category-drawer', ['id' => '__ID__']) }}',
        productDrawer:  '{{ route('reports.products.product-drawer',  ['id' => '__ID__']) }}',
        variantDrawer:  '{{ route('reports.products.variant-drawer',  ['id' => '__ID__']) }}',
    });
    window.BC_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.products.partials.by-category-scripts')
@include('reports.products.partials.category-drawer-scripts')
@include('reports.products.partials.product-drawer-scripts')
@include('reports.products.partials.variants-drawer-scripts')
@endpush