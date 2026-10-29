@extends('layouts.app')

@section('title', __('auth.product_margin'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- TOOLBAR --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">{{ __('auth.product_margin') }}</h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('auth._dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_margin') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" id="mgExport" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span>{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="marginTable"
                                           data-export-filename="product_margin"
                                           data-export-sheet="Product Margin">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="marginTable"
                                           data-export-filename="product_margin"
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
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form id="mgFilterForm">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                    <select class="form-select" name="category_id">
                                        <option value="">{{ __('accounting.all_categories') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('auth.min_margin') }} (%)</label>
                                    <input type="number" class="form-control" name="min_margin" min="0" max="100" step="1" placeholder="0">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('auth.max_margin') }} (%)</label>
                                    <input type="number" class="form-control" name="max_margin" min="0" max="100" step="1" placeholder="100">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('accounting.per_page') }}</label>
                                    <select class="form-select" name="per_page" id="mgPerPage">
                                        <option value="15">15 / page</option>
                                        <option value="25">25 / page</option>
                                        <option value="50">50 / page</option>
                                        <option value="100">100 / page</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                    </button>
                                    <button type="button" id="mgClear" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- SUMMARY --}}
                <div class="row mb-6" id="mgSummaryRow">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.margin_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-abstract-44 fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="mgStatTotal">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.total_variants') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-secondary border border-secondary border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-chart-line fs-2tx text-secondary"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="mgStatAvg">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.average_margin') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-arrow-up fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="mgStatHigh">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.high_margin') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-arrow-right fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="mgStatMedium">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.medium_margin') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-arrow-down fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="mgStatLow">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.low_margin') }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                            <div class="card-body text-center">
                                                <div class="mb-4"><i class="ki-duotone ki-dollar fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                                <div class="fs-2 fw-bold text-gray-800" id="mgStatValue">—</div>
                                                <div class="text-gray-600 fw-semibold fs-7">{{ __('auth.total_margin_value') }}</div>
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
                    <div class="col-lg-5">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.margin_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0"><div id="marginDistributionChart" style="height:300px;"></div></div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_10_by_margin') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0"><div id="topMarginChart" style="height:300px;"></div></div>
                        </div>
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            <h3 class="fw-bold m-0">{{ __('auth.margin_analysis') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7" id="mgCountBadge">—</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="mgLoading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div id="mgWrapper" class="d-none">
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="marginTable">
                                    <thead class="bg-light">
                                        <tr class="fw-bold fs-6 text-gray-800">
                                            <th class="ps-4 min-w-100px">{{ __('auth.sku') }}</th>
                                            <th class="min-w-220px">{{ __('accounting.name') }}</th>
                                            <th class="min-w-150px">{{ __('accounting.category') }}</th>
                                            <th class="min-w-90px text-center">{{ __('auth.quantity') }}</th>
                                            <th class="min-w-120px text-end">{{ __('auth.cost_price') }}</th>
                                            <th class="min-w-120px text-end">{{ __('auth.selling_price') }}</th>
                                            <th class="min-w-120px text-end">{{ __('auth.margin_amount') }}</th>
                                            <th class="min-w-100px text-center">{{ __('auth.margin_percentage') }}</th>
                                            <th class="min-w-140px text-end">{{ __('auth.total_margin_value') }}</th>
                                            <th class="min-w-40px text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="mgBody"></tbody>
                                    <tfoot class="bg-light" id="mgFoot"></tfoot>
                                </table>
                            </div>
                            <div class="d-flex flex-stack flex-wrap p-6">
                                <div class="fs-6 fw-semibold text-gray-700" id="mgPageInfo">—</div>
                                <ul class="pagination m-0" id="mgPagination"></ul>
                            </div>
                        </div>
                        <div id="mgEmpty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_products_found') }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@include('reports.products.partials.variants-drawer')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.PRODUCTS_ROUTES = Object.assign(window.PRODUCTS_ROUTES || {}, {
        marginData:    '{{ route('reports.products.margin-data') }}',
        marginMeta:    '{{ route('reports.products.margin-meta') }}',
        variantDrawer: '{{ route('reports.products.variant-drawer', ['id' => '__ID__']) }}',
    });
    window.MG_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.products.partials.margin-scripts')
@include('reports.products.partials.variants-drawer-scripts')
@endpush