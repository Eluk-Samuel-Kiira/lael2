{{-- resources/views/reports/production/index.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_order_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            {{-- ─── TOOLBAR ──────────────────────────────────────────────── --}}
            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                    <div class="page-title d-flex flex-column">
                        <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                            {{ __('pagination.production_order_report') }}
                        </h1>
                        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                            <li class="breadcrumb-item text-muted">
                                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                    {{ __('pagination.dashboard') }}
                                </a>
                            </li>
                            <li class="breadcrumb-item">
                                <span class="bullet bg-gray-500 w-5px h-2px"></span>
                            </li>
                            <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                            <li class="breadcrumb-item">
                                <span class="bullet bg-gray-500 w-5px h-2px"></span>
                            </li>
                            <li class="breadcrumb-item text-muted">{{ __('pagination.production_orders') }}</li>
                        </ul>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-success" id="exportReportBtn">
                            <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                            <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                        </button>
                        <a href="{{ route('production-orders.index') }}" class="btn btn-sm btn-primary">
                            <i class="ki-duotone ki-plus fs-2 me-1 me-sm-2"></i>
                            <span class="d-none d-sm-inline">{{ __('pagination.new_order') }}</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- ─── FILTERS CARD ─────────────────────────────────────────── --}}
            <div class="row mb-6">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div id="filterForm">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                        <input type="date" class="form-control" id="filterStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                        <input type="date" class="form-control" id="filterEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.status') }}</label>
                                        <select class="form-select" id="filterStatus">
                                            @foreach($statuses as $s)
                                                <option value="{{ $s['value'] }}" {{ $status == $s['value'] ? 'selected' : '' }}>
                                                    {{ $s['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                        <select class="form-select" id="filterLocation">
                                            <option value="">{{ __('pagination.all_locations') }}</option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}" {{ (int) $locationId === $location->id ? 'selected' : '' }}>
                                                    {{ $location->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                        <select class="form-select" id="filterVariant">
                                            <option value="">{{ __('pagination.all_variants') }}</option>
                                            @foreach($variants as $variant)
                                                <option value="{{ $variant->id }}" {{ (int) $variantId === $variant->id ? 'selected' : '' }}>
                                                    {{ $variant->name }} ({{ $variant->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.category') }}</label>
                                        <select class="form-select" id="filterCategory">
                                            <option value="">{{ __('pagination.all_categories') }}</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ (int) $categoryId === $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.payment_status') }}</label>
                                        <select class="form-select" id="filterPayment">
                                            <option value="all" {{ $hasPayment === 'all' ? 'selected' : '' }}>{{ __('pagination.all') }}</option>
                                            <option value="with_payment" {{ $hasPayment === 'with_payment' ? 'selected' : '' }}>{{ __('pagination.with_payment') }}</option>
                                            <option value="without_payment" {{ $hasPayment === 'without_payment' ? 'selected' : '' }}>{{ __('pagination.without_payment') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.search') }}</label>
                                        <input type="text" class="form-control" id="filterSearch" value="{{ $search }}" placeholder="{{ __('pagination.search_orders') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.min_cost') }}</label>
                                        <input type="number" step="0.01" class="form-control" id="filterMinCost" value="{{ $minCost }}" placeholder="0.00">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.max_cost') }}</label>
                                        <input type="number" step="0.01" class="form-control" id="filterMaxCost" value="{{ $maxCost }}" placeholder="10000.00">
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end gap-2">
                                        <button type="button" class="btn btn-primary flex-grow-1" id="applyFiltersBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                            {{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="clearFiltersBtn">
                                            <i class="ki-duotone ki-cross fs-2"></i>
                                            {{ __('pagination.clear') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── SUMMARY CARDS ────────────────────────────────────────── --}}
            <div class="row mb-6">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                <h3 class="fw-bold m-0">{{ __('pagination.summary_statistics') }}</h3>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row g-6">
                                <div class="col-md-6 col-lg-2">
                                    <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                        <div class="card-body d-flex flex-column justify-content-center text-center">
                                            <div class="mb-2">
                                                <i class="ki-duotone ki-box fs-2tx text-primary">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                            </div>
                                            <span class="fs-1 fw-bold text-gray-800" id="summaryTotalOrders">—</span>
                                            <span class="text-gray-600 fw-semibold">{{ __('pagination.total_orders') }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card card-flush h-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <div class="text-gray-600 fw-semibold mb-2">{{ __('pagination.by_status') }}</div>
                                            <div class="d-flex flex-wrap gap-2" id="summaryStatusBreakdown"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <div class="card card-flush h-100">
                                        <div class="card-body d-flex flex-column justify-content-center">
                                            <div class="text-gray-600 fw-semibold mb-2">{{ __('pagination.cost_summary') }}</div>
                                            <div class="d-flex flex-wrap gap-2" id="summaryCosts"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <div class="card card-flush h-100" id="summaryProfitCard">
                                        <div class="card-body d-flex flex-column justify-content-center text-center">
                                            <div class="mb-2">
                                                <i class="ki-duotone ki-dollar fs-2tx">
                                                    <span class="path1"></span><span class="path2"></span>
                                                </i>
                                            </div>
                                            <span class="fs-1 fw-bold text-gray-800" id="summaryTotalProfit">—</span>
                                            <span class="text-gray-600 fw-semibold">{{ __('pagination.total_profit') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── TABLE CARD ──────────────────────────────────────────── --}}
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                <h3 class="fw-bold m-0">{{ __('pagination.production_orders') }}</h3>
                            </div>
                            <div class="card-toolbar">
                                <select id="reportsPerPage" class="form-select form-select-solid form-select-sm w-75px">
                                    @foreach([15, 25, 50, 100] as $pp)
                                        <option value="{{ $pp }}" @selected($perPage === $pp)>{{ $pp }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="card-body pt-0">
                            {{-- Skeleton --}}
                            <div id="reportLoadingSpinner" class="d-none py-5">
                                @for($i = 0; $i < 6; $i++)
                                    <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                        <div class="skeleton skeleton-text" style="width:120px;"></div>
                                        <div class="flex-grow-1">
                                            <div class="skeleton skeleton-text" style="width:180px;"></div>
                                            <div class="skeleton skeleton-text" style="width:120px;"></div>
                                        </div>
                                        <div class="skeleton skeleton-text" style="width:100px;"></div>
                                        <div class="skeleton skeleton-text" style="width:80px;"></div>
                                    </div>
                                @endfor
                            </div>

                            {{-- Table --}}
                            <div id="reportTableContainer" class="d-none">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4 min-w-140px">{{ __('pagination.order_number') }}</th>
                                                <th class="min-w-120px">{{ __('pagination.status') }}</th>
                                                <th class="min-w-150px">{{ __('pagination.location') }}</th>
                                                <th class="min-w-120px text-center">{{ __('pagination.input_qty') }}</th>
                                                <th class="min-w-120px text-center">{{ __('pagination.output_qty') }}</th>
                                                <th class="min-w-150px text-center">{{ __('pagination.input_cost') }}</th>
                                                <th class="min-w-150px text-center">{{ __('pagination.output_cost') }}</th>
                                                <th class="min-w-120px text-center">{{ __('pagination.profit') }}</th>
                                                <th class="min-w-150px">{{ __('pagination.created_by') }}</th>
                                                <th class="min-w-100px text-center">{{ __('pagination.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody"></tbody>
                                    </table>
                                </div>
                                <div id="reportPaginationContainer" class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 d-none">
                                    <div id="reportPaginationInfo" class="text-muted fs-7"></div>
                                    <nav><ul class="pagination pagination-outline m-0" id="reportPagination"></ul></nav>
                                </div>
                            </div>

                            {{-- Empty --}}
                            <div id="reportNoDataMessage" class="text-center py-15 d-none">
                                <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.no_production_orders_found') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Drawer shell --}}
@include('reports.production.drawer')

@endsection

@push('styles')
<style>
    .skeleton {
        display: inline-block;
        background: linear-gradient(90deg, #f1f1f4 0%, #e9e9ed 50%, #f1f1f4 100%);
        background-size: 200% 100%;
        animation: skeleton-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .skeleton-text { height: 12px; margin: 4px 0; display: block; }
    @keyframes skeleton-shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .report-row { cursor: pointer; }
    .report-row:hover { background-color: #f9f9f9; }
    .report-row td:not(:last-child) > * { pointer-events: none; }
</style>
@endpush

@push('scripts')
    <script>
        window.REPORT_ROUTES = {
            data:   '{{ route("reports.production.data") }}',
            detail: '{{ route("reports.production.detail", ["orderId" => "__ID__"]) }}',
        };
        window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    </script>
    @include('reports.production.list-scripts')
    @include('reports.production.drawer-scripts')
@endpush