@extends('layouts.app')

@section('title', __('auth.inventory_valuation'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- TOOLBAR --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">{{ __('auth.inventory_valuation') }}</h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('auth._dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.product_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.inventory_valuation') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" id="invExport" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span>{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="inventoryTable"
                                           data-export-filename="inventory_valuation"
                                           data-export-sheet="Inventory Valuation">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)"
                                           data-export-table="inventoryTable"
                                           data-export-filename="inventory_valuation"
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
                            <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form id="invFilterForm">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                    <select class="form-select" name="category_id">
                                        <option value="">{{ __('accounting.all_categories') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('auth.stock_status') }}</label>
                                    <select class="form-select" name="stock_status">
                                        <option value="">{{ __('accounting.all_statuses') }}</option>
                                        <option value="low">{{ __('auth.low_stock') }}</option>
                                        <option value="out">{{ __('auth.out_of_stock') }}</option>
                                        <option value="overstock">{{ __('auth.overstock') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('accounting.per_page') }}</label>
                                    <select class="form-select" name="per_page" id="invPerPage">
                                        <option value="15">15 / page</option>
                                        <option value="25">25 / page</option>
                                        <option value="50">50 / page</option>
                                        <option value="100">100 / page</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                    </button>
                                    <button type="button" id="invClear" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- SUMMARY CARDS --}}
                <div class="row g-6 mb-6" id="invSummaryRow">
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-abstract-44 fs-2tx text-primary mb-3"></i>
                                <div class="fs-2 fw-bold text-gray-800" id="invStatItems">—</div>
                                <div class="text-muted fs-7">{{ __('auth.total_items') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-success border border-success border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-inbox fs-2tx text-success mb-3"></i>
                                <div class="fs-2 fw-bold text-gray-800" id="invStatQty">—</div>
                                <div class="text-muted fs-7">{{ __('auth.total_quantity') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-money fs-2tx text-warning mb-3"></i>
                                <div class="fs-2 fw-bold text-gray-800" id="invStatCost">—</div>
                                <div class="text-muted fs-7">{{ __('auth.total_cost_value') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-info border border-info border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-dollar fs-2tx text-info mb-3"></i>
                                <div class="fs-2 fw-bold text-gray-800" id="invStatRevenue">—</div>
                                <div class="text-muted fs-7">{{ __('auth.total_revenue_value') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-danger border border-danger border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-growth fs-2tx text-danger mb-3"></i>
                                <div class="fs-2 fw-bold text-gray-800" id="invStatProfit">—</div>
                                <div class="text-muted fs-7">{{ __('auth.total_potential_profit') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-secondary border border-secondary border-dashed h-100">
                            <div class="card-body text-center">
                                <i class="ki-duotone ki-chart-line fs-2tx text-secondary mb-3"></i>
                                <div class="fs-2 fw-bold text-gray-800" id="invStatMargin">—</div>
                                <div class="text-muted fs-7">{{ __('auth.avg_margin') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CHARTS --}}
                <div class="row g-6 mb-6">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i> {{ __('auth.stock_health_distribution') }}</h3>
                            </div>
                            <div class="card-body"><div id="stockHealthChart" style="height:300px;"></div></div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i> {{ __('auth.value_by_stock_status') }}</h3>
                            </div>
                            <div class="card-body"><div id="valueDistributionChart" style="height:300px;"></div></div>
                        </div>
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.inventory_valuation') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7" id="invCountBadge">—</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="invLoading" class="text-center py-10 d-none">
                            <div class="spinner-border text-primary"></div>
                        </div>
                        <div id="invWrapper" class="d-none">
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="inventoryTable">
                                    <thead class="bg-light">
                                        <tr class="fw-bold fs-6 text-gray-800">
                                            <th class="ps-4 min-w-100px">{{ __('auth.sku') }}</th>
                                            <th class="min-w-200px">{{ __('accounting.name') }}</th>
                                            <th class="min-w-150px">{{ __('accounting.category') }}</th>
                                            <th class="min-w-100px text-center">{{ __('auth.quantity') }}</th>
                                            <th class="min-w-120px text-end">{{ __('auth.selling_price') }}</th>
                                            <th class="min-w-120px text-end">{{ __('auth.cost_price') }}</th>
                                            <th class="min-w-120px text-end">{{ __('auth.potential_profit') }}</th>
                                            <th class="min-w-100px text-center">{{ __('auth.margin_percentage') }}</th>
                                            <th class="min-w-120px text-center">{{ __('auth.stock_health') }}</th>
                                            <th class="min-w-40px text-end"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="invBody"></tbody>
                                    <tfoot class="bg-light" id="invFoot"></tfoot>
                                </table>
                            </div>
                            <div class="d-flex flex-stack flex-wrap p-6">
                                <div class="fs-6 fw-semibold text-gray-700" id="invPageInfo">—</div>
                                <ul class="pagination m-0" id="invPagination"></ul>
                            </div>
                        </div>
                        <div id="invEmpty" class="text-center py-10 d-none">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted">{{ __('auth.no_products_found') }}</p>
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
        inventoryData: '{{ route('reports.products.inventory-data') }}',
        inventoryMeta: '{{ route('reports.products.inventory-meta') }}',
        variantDrawer: '{{ route('reports.products.variant-drawer', ['id' => '__ID__']) }}',
    });
    window.INV_BOOTSTRAP = @json($initialFilters ?? []);
</script>
@include('reports.products.partials.inventory-scripts')
@include('reports.products.partials.variants-drawer-scripts')
@endpush