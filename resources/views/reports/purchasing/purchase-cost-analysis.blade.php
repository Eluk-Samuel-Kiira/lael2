@extends('layouts.app')

@section('title', __('pagination.purchase_cost_analysis'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- Toolbar --}}
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0 w-100">

                        {{-- Title block — extreme left --}}
                        <div class="page-title d-flex flex-column align-items-start text-start me-auto">
                            <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                                {{ __('pagination.purchase_cost_analysis') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchase_cost_analysis') }}</li>
                            </ul>
                        </div>

                        {{-- Export button — extreme right --}}
                        <div class="d-flex align-items-stretch w-100 w-lg-auto ms-auto">
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0)" id="pcaExportExcel">
                                        <i class="bi bi-file-earmark-excel fs-2 me-2 text-success"></i>{{ __('pagination.export_to_excel') }}
                                    </a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0)" id="pcaExportPdf">
                                        <i class="bi bi-file-earmark-pdf fs-2 me-2 text-danger"></i>{{ __('pagination.export_to_pdf') }}
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="row mb-6">
                    <div class="col-12"><div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="bi bi-funnel fs-2 me-2 text-primary"></i>
                                <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <form id="pcaFilterForm" class="row g-3" onsubmit="return false;">
                                <div class="col-md-3">
                                    <label class="form-label required fw-semibold">{{ __('pagination.start_date') }}</label>
                                    <input type="date" class="form-control" id="pcaStartDate" value="{{ $startDate }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label required fw-semibold">{{ __('pagination.end_date') }}</label>
                                    <input type="date" class="form-control" id="pcaEndDate" value="{{ $endDate }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('passwords.product_variant') }}</label>
                                    <select class="form-select" id="pcaVariant" data-control="select2" data-placeholder="{{ __('passwords.all_products') }}">
                                        <option value=""></option>
                                        @foreach($variants as $v)
                                            <option value="{{ $v->id }}" @selected((int)$productVariantId === $v->id)>
                                                {{ $v->name }}@if($v->product) ({{ $v->product->name }})@endif
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                    <select class="form-select" id="pcaSupplier" data-control="select2" data-placeholder="{{ __('passwords.all_suppliers') }}">
                                        <option value=""></option>
                                        @foreach($suppliers as $s)
                                            <option value="{{ $s->id }}" @selected((int)$supplierId === $s->id)>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-primary" id="pcaApplyBtn">
                                        <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                    </button>
                                    <button type="button" class="btn btn-light" id="pcaClearBtn">
                                        <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div></div>
                </div>

                {{-- SKELETON --}}
                <div id="pcaSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="pca-sk pca-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="pca-sk pca-sk-text mb-2" style="width:60%;"></div>
                                    <div class="pca-sk pca-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body"><div class="pca-sk pca-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="pca-sk pca-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="pca-sk pca-sk-text mb-2" style="width:35%;"></div>
                                <div class="pca-sk pca-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="pca-sk pca-sk-badge"></div>
                            <div class="pca-sk pca-sk-text" style="width:100px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="pcaContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6" id="pcaSummaryCards"></div>

                    {{-- Price movement cards --}}
                    <div class="row g-6 mb-6" id="pcaMovementCards"></div>

                    {{-- Price trend chart --}}
                    <div class="row g-6 mb-6" id="pcaTrendWrap">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.price_trend_analysis') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7" id="pcaPeriodBadge"></span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="pcaChart" style="height:400px;"></div>
                                <div id="pcaChartEmpty" class="text-center text-muted py-10 d-none">{{ __('passwords.no_data') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Top products --}}
                    <div class="row g-6 mb-6 d-none" id="pcaTopWrap">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-trophy fs-2 me-2 text-warning"></i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.top_products_by_spend') }}</h3>
                                    </div>
                                    <span class="badge badge-light-warning fs-7" id="pcaTopBadge"></span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="ps-4">{{ __('passwords.product') }}</th>
                                        <th>{{ __('passwords.total_quantity') }}</th>
                                        <th>{{ __('passwords.avg_unit_cost') }}</th>
                                        <th>{{ __('passwords.total_cost') }}</th>
                                        <th>{{ __('passwords.price_change') }}</th>
                                        <th>{{ __('passwords.price_change_percentage') }}</th>
                                        <th>{{ __('passwords.purchase_count') }}</th>
                                        <th class="text-end pe-4">{{ __('passwords.trend') }}</th>
                                    </tr></thead>
                                    <tbody id="pcaTopBody"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Full table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.complete_cost_analysis') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="pcaBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4 text-center">#</th>
                                    <th>{{ __('passwords.product') }}</th>
                                    <th>{{ __('passwords.category') }}</th>
                                    <th class="text-end">{{ __('passwords.total_quantity') }}</th>
                                    <th class="text-end">{{ __('passwords.avg_unit_cost') }}</th>
                                    <th class="text-end">{{ __('passwords.total_cost') }}</th>
                                    <th class="text-end">{{ __('passwords.price_change') }}</th>
                                    <th class="text-center">{{ __('passwords.price_change_percentage') }}</th>
                                    <th class="text-center">{{ __('passwords.purchase_frequency') }}</th>
                                    <th class="text-center pe-4">{{ __('passwords.cost_efficiency') }}</th>
                                </tr></thead>
                                <tbody id="pcaTableBody"></tbody>
                                <tfoot class="bg-light" id="pcaTableFoot"></tfoot>
                            </table></div>
                            <div id="pcaEmpty" class="text-center py-10 d-none">
                                <i class="bi bi-inbox fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_data') }}</h4>
                                <p class="text-muted fs-6">{{ __('passwords.no_purchase_cost_data_for_filters') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="pcaPaginationWrap">
                                <span class="text-muted fs-7" id="pcaPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="pcaPagination"></ul></nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .pca-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: pca-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .pca-sk-text   { height: 12px; border-radius: 4px; }
    .pca-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .pca-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .pca-sk-chart  { width: 100%; height: 400px; border-radius: 8px; }
    @keyframes pca-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #pcaSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.PCA_ROUTES = { data: '{{ route("reports.purchasing.purchase-cost-analysis-data") }}' };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.PCA_BOOTSTRAP = {
        startDate:  @json($startDate),
        endDate:    @json($endDate),
        variantId:  @json($productVariantId),
        supplierId: @json($supplierId),
        perPage:    @json($perPage),
    };
    window.PCA_I18N = {
        // summary
        totalProductsAnalyzed: @json(__('passwords.total_products_analyzed')),
        uniqueProducts:        @json(__('passwords.unique_products')),
        totalQuantity:         @json(__('passwords.total_quantity')),
        totalUnitsPurchased:   @json(__('passwords.total_units_purchased')),
        totalCost:             @json(__('passwords.total_cost')),
        totalSpend:            @json(__('passwords.total_spend')),
        avgPriceChange:        @json(__('passwords.avg_price_change')),
        averagePriceMovement:  @json(__('passwords.average_price_movement')),

        // movement cards
        productsWithPriceIncrease: @json(__('passwords.products_with_price_increase')),
        priceIncreased:            @json(__('passwords.price_increased')),
        productsWithPriceDecrease: @json(__('passwords.products_with_price_decrease')),
        priceDecreased:            @json(__('passwords.price_decreased')),
        priceStableProducts:       @json(__('passwords.price_stable_products')),
        priceRemainedStable:       @json(__('passwords.price_remained_stable')),

        // trends
        significant_increase: @json(__('passwords.significant_increase')),
        moderate_increase:    @json(__('passwords.moderate_increase')),
        significant_decrease: @json(__('passwords.significant_decrease')),
        moderate_decrease:    @json(__('passwords.moderate_decrease')),
        stable:               @json(__('passwords.stable')),

        // efficiency
        low_efficiency:    @json(__('passwords.low_efficiency')),
        medium_efficiency: @json(__('passwords.medium_efficiency')),
        high_efficiency:   @json(__('passwords.high_efficiency')),

        // frequency
        very_high_frequency: @json(__('passwords.very_high_frequency')),
        high_frequency:      @json(__('passwords.high_frequency')),
        moderate_frequency:  @json(__('passwords.moderate_frequency')),
        low_frequency:       @json(__('passwords.low_frequency')),
        very_low_frequency:  @json(__('passwords.very_low_frequency')),

        // labels
        product:               @json(__('passwords.product')),
        category:              @json(__('passwords.category')),
        units:                 @json(__('passwords.units')),
        purchases:             @json(__('passwords.purchases')),
        orders:                @json(__('passwords.orders')),
        avgUnitCost:           @json(__('passwords.avg_unit_cost')),
        priceChange:           @json(__('passwords.price_change')),
        priceChangePercentage: @json(__('passwords.price_change_percentage')),
        purchaseCount:         @json(__('passwords.purchase_count')),
        purchaseFrequency:     @json(__('passwords.purchase_frequency')),
        costEfficiency:        @json(__('passwords.cost_efficiency')),
        trend:                 @json(__('passwords.trend')),
        currentPage:           @json(__('pagination.current_page')),
        grandTotal:            @json(__('pagination.grand_total')),
        productNotFound:       @json(__('passwords.product_not_found')),
    };
</script>
@include('reports.purchasing.purchase-cost-analysis-scripts')
@endpush