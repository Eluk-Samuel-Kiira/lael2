@extends('layouts.app')

@section('title', __('auth.product_summary'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- TOOLBAR --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('auth.product_summary') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('auth._dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.summary') }}</li>
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
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="productSummaryFilterForm">
                                    <div class="row g-4">
                                        <div class="col-xl-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <select class="form-select" name="category_id">
                                                <option value="">{{ __('accounting.all_categories') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-2">
                                            <label class="form-label fw-semibold">{{ __('auth.product_type') }}</label>
                                            <select class="form-select" name="product_type">
                                                <option value="all">{{ __('auth.all_types') }}</option>
                                                <option value="physical">{{ __('auth.physical') }}</option>
                                                <option value="digital">{{ __('auth.digital') }}</option>
                                                <option value="service">{{ __('auth.service') }}</option>
                                                <option value="composite">{{ __('auth.composite') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-2">
                                            <label class="form-label fw-semibold">{{ __('passwords.inventory_strategy') }}</label>
                                            <select class="form-select" name="inventory_strategy">
                                                <option value="all">{{ __('passwords.all_strategies') }}</option>
                                                <option value="quantity">{{ __('passwords.quantity_tracking') }}</option>
                                                <option value="batch">{{ __('passwords.batch_tracking') }}</option>
                                                <option value="serial">{{ __('passwords.serial_tracking') }}</option>
                                                <option value="recipe">{{ __('passwords.recipe_product') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-2">
                                            <label class="form-label fw-semibold">{{ __('auth.status') }}</label>
                                            <select class="form-select" name="is_active">
                                                <option value="">{{ __('auth.all_statuses') }}</option>
                                                <option value="1">{{ __('auth.active') }}</option>
                                                <option value="0">{{ __('auth.inactive') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-2">
                                            <label class="form-label fw-semibold">{{ __('auth.tax_status') }}</label>
                                            <select class="form-select" name="is_taxable">
                                                <option value="">{{ __('auth.all_tax_statuses') }}</option>
                                                <option value="1">{{ __('auth.taxable') }}</option>
                                                <option value="0">{{ __('auth.non_taxable') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.per_page') }}</label>
                                            <select class="form-select" name="per_page" id="productSummaryPerPage">
                                                <option value="15">15 / page</option>
                                                <option value="25">25 / page</option>
                                                <option value="50">50 / page</option>
                                                <option value="100">100 / page</option>
                                            </select>
                                        </div>
                                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                            </button>
                                            <button type="button" id="productSummaryClear" class="btn btn-light">
                                                <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
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
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.summary_statistics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    <div class="col-6 col-md-4 col-lg-2">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-package fs-2tx text-primary"></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="statTotalProducts">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.total_products') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-2">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-abstract-44 fs-2tx text-success"></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="statTotalVariants">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.total_variants') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-2">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-inbox fs-2tx text-info"></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="statTotalStock">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.total_stock') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-2">
                                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-dollar fs-2tx text-warning"></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="statAvgSell">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.avg_selling_price') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-2">
                                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-money fs-2tx text-danger"></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="statAvgCost">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.avg_cost_price') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg-2">
                                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-chart-line fs-2tx text-secondary"></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="statAvgMargin">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.avg_margin') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CHARTS (single card each, same grid) --}}
                <div class="row mb-6">
                    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title"><i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i>{{ __('auth.category_distribution') }}</h3></div><div class="card-body"><div id="categoryChart" style="height:280px;"></div></div></div></div>
                    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title"><i class="ki-duotone ki-chart-bar fs-2 me-2 text-info"></i>{{ __('auth.type_distribution') }}</h3></div><div class="card-body"><div id="typeChart" style="height:280px;"></div></div></div></div>
                </div>
                <div class="row mb-6">
                    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title"><i class="ki-duotone ki-boxes fs-2 me-2 text-warning"></i>{{ __('passwords.inventory_strategy') }}</h3></div><div class="card-body"><div id="strategyChart" style="height:280px;"></div></div></div></div>
                    <div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title"><i class="ki-duotone ki-status fs-2 me-2 text-success"></i>{{ __('auth.status_distribution') }}</h3></div><div class="card-body"><div id="statusChart" style="height:280px;"></div></div></div></div>
                </div>

                {{-- TABLE --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="card-title">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.products') }}</h3>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div id="psLoading" class="text-center py-10 d-none">
                                    <div class="spinner-border text-primary"></div>
                                </div>
                                <div id="psWrapper" class="d-none">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="productSummaryTable">
                                            <thead class="bg-light">
                                                <tr class="fw-bold fs-6 text-gray-800">
                                                    <th class="ps-4">{{ __('auth.sku') }}</th>
                                                    <th>{{ __('accounting.name') }}</th>
                                                    <th>{{ __('accounting.category') }}</th>
                                                    <th>{{ __('accounting.type') }}</th>
                                                    <th>{{ __('passwords.inventory_strategy') }}</th>
                                                    <th>{{ __('auth.status') }}</th>
                                                    <th>{{ __('auth.tax_status') }}</th>
                                                    <th class="text-center">{{ __('auth.variant_count') }}</th>
                                                    <th>{{ __('auth.stock') }}</th>
                                                    <th>{{ __('auth.created_at') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="psBody"></tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex flex-stack flex-wrap p-6">
                                        <div class="fs-6 fw-semibold text-gray-700" id="psPageInfo">—</div>
                                        <ul class="pagination m-0" id="psPagination"></ul>
                                    </div>
                                </div>
                                <div id="psEmpty" class="text-center py-10 d-none">
                                    <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                    <p class="text-muted fs-6">{{ __('auth.no_products_found') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@include('reports.products.partials.product-drawer')
@include('reports.products.partials.variants-drawer')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.PRODUCTS_ROUTES = {
        summaryData:   '{{ route('reports.products.summary-data') }}',
        summaryMeta:   '{{ route('reports.products.summary-meta') }}',
        productDrawer: '{{ route('reports.products.product-drawer', ['id' => '__ID__']) }}',
        variantDrawer: '{{ route('reports.products.variant-drawer', ['id' => '__ID__']) }}',
    };
    window.PRODUCTS_SUMMARY_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.products.partials.summary-scripts')
@include('reports.products.partials.product-drawer-scripts')
@include('reports.products.partials.variants-drawer-scripts')
@endpush