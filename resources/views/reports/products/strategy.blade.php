@extends('layouts.app')

@section('title', __('pagination.inventory_strategy_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- TOOLBAR --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">{{ __('pagination.inventory_strategy_report') }}</h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.product_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_strategy') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" id="stExport" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span>{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="strategyTable"
                                           data-export-filename="inventory_strategy"
                                           data-export-sheet="Inventory Strategy">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="strategyTable"
                                           data-export-filename="inventory_strategy"
                                           data-export-format="csv">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('pagination.export_to_csv') }}
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
                            <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form id="stFilterForm">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('pagination.inventory_strategy') }}</label>
                                    <select class="form-select" name="strategy">
                                        <option value="all">{{ __('pagination.all_strategies') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('pagination.category') }}</label>
                                    <select class="form-select" name="category_id">
                                        <option value="">{{ __('pagination.all_categories') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">{{ __('pagination.search') }}</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="ki-duotone ki-magnifier fs-2"><span class="path1"></span><span class="path2"></span></i></span>
                                        <input type="text" class="form-control" name="search" placeholder="{{ __('pagination.search_products') }}">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('accounting.per_page') }}</label>
                                    <select class="form-select" name="per_page" id="stPerPage">
                                        <option value="15">15 / page</option>
                                        <option value="25">25 / page</option>
                                        <option value="50">50 / page</option>
                                        <option value="100">100 / page</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                    <select class="form-select" name="location_id">
                                        <option value="">{{ __('pagination.all_locations') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('pagination.department') }}</label>
                                    <select class="form-select" name="department_id">
                                        <option value="">{{ __('pagination.all_departments') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('pagination.apply') }}
                                    </button>
                                    <button type="button" id="stClear" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- SUMMARY --}}
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.summary_statistics') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-6">
                            <div class="col-md-6 col-lg-2">
                                <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-2"><i class="ki-duotone ki-box fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                        <span class="fs-2 fw-bold text-gray-800" id="stStatProducts">—</span>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('pagination.total_products') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="card card-flush h-100">
                                    <div class="card-body d-flex flex-column justify-content-center">
                                        <div class="text-gray-600 fw-semibold mb-2">{{ __('pagination.by_strategy') }}</div>
                                        <div class="d-flex flex-wrap gap-2" id="stStrategyBadges"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="card card-flush h-100">
                                    <div class="card-body d-flex flex-column justify-content-center">
                                        <div class="text-gray-600 fw-semibold mb-2">{{ __('pagination.stock_status') }}</div>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge badge-light-success fs-6 py-2 px-3">{{ __('pagination.in_stock') }}: <span id="stStatInStock">0</span></span>
                                            <span class="badge badge-light-warning fs-6 py-2 px-3">{{ __('pagination.low_stock') }}: <span id="stStatLowStock">0</span></span>
                                            <span class="badge badge-light-danger fs-6 py-2 px-3">{{ __('pagination.out_of_stock') }}: <span id="stStatOutStock">0</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                    <div class="card-body text-center">
                                        <div class="mb-2"><i class="ki-duotone ki-dollar fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                        <span class="fs-2 fw-bold text-gray-800" id="stStatValue">—</span>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('pagination.total_inventory_value') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.products_by_strategy') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7" id="stCountBadge">—</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="stLoading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div id="stWrapper" class="d-none">
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="strategyTable">
                                    <thead class="bg-light">
                                        <tr class="fw-bold fs-6 text-gray-800">
                                            <th class="ps-4 min-w-50px">#</th>
                                            <th class="min-w-200px">{{ __('pagination.product') }}</th>
                                            <th class="min-w-150px">{{ __('pagination.category') }}</th>
                                            <th class="min-w-120px">{{ __('pagination.strategy') }}</th>
                                            <th class="min-w-100px text-center">{{ __('pagination.variants') }}</th>
                                            <th class="min-w-120px text-center">{{ __('pagination.total_stock') }}</th>
                                            <th class="min-w-150px text-center">{{ __('pagination.total_value') }}</th>
                                            <th class="min-w-120px text-center">{{ __('pagination.status') }}</th>
                                            <th class="min-w-40px text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="stBody"></tbody>
                                </table>
                            </div>
                            <div class="d-flex flex-stack flex-wrap p-6">
                                <div class="fs-6 fw-semibold text-gray-700" id="stPageInfo">—</div>
                                <ul class="pagination m-0" id="stPagination"></ul>
                            </div>
                        </div>
                        <div id="stEmpty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('pagination.no_products_found') }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@include('reports.products.partials.strategy-drawer')
@endsection

@push('scripts')
<script>
    window.PRODUCTS_ROUTES = Object.assign(window.PRODUCTS_ROUTES || {}, {
        strategyData:    '{{ route('reports.products.strategy.data') }}',
        strategyMeta:    '{{ route('reports.products.strategy.meta') }}',
        strategyDetail:  '{{ route('reports.products.strategy.detail', ['productId' => '__ID__']) }}',
    });
    window.ST_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.products.partials.strategy-scripts')
@include('reports.products.partials.strategy-drawer-scripts')
@endpush