{{-- resources/views/reports/purchasing/supplier-performance.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.supplier_performance'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- Toolbar --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('pagination.supplier_performance') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.supplier_performance') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="spExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-funnel fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="spFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">{{ __('pagination.start_date') }}</label>
                                        <input type="date" class="form-control" id="spStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">{{ __('pagination.end_date') }}</label>
                                        <input type="date" class="form-control" id="spEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                        <select class="form-select" id="spSupplier" data-control="select2" data-placeholder="{{ __('passwords.all_suppliers') }}">
                                            <option value=""></option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}" @selected((int)$supplierId === $sup->id)>{{ $sup->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-primary" id="spApplyBtn">
                                            <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="spClearBtn">
                                            <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SKELETON --}}
                <div id="spSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="sp-sk sp-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="sp-sk sp-sk-text mb-2" style="width:60%;"></div>
                                    <div class="sp-sk sp-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="sp-sk sp-sk-chart"></div></div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="sp-sk sp-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="sp-sk sp-sk-text mb-2" style="width:35%;"></div>
                                <div class="sp-sk sp-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="sp-sk sp-sk-badge"></div>
                            <div class="sp-sk sp-sk-badge"></div>
                            <div class="sp-sk sp-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="spContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-people fs-2tx text-primary me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_suppliers') }}</div>
                                        <div class="fs-2 fw-bold text-primary" id="sumTotalSuppliers">—</div>
                                        <div class="text-muted fs-8">{{ __('passwords.active_suppliers') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-currency-dollar fs-2tx text-success me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_spent') }}</div>
                                        <div class="fs-2 fw-bold text-success" id="sumTotalSpent">—</div>
                                        <div class="text-muted fs-8">{{ __('passwords.total_purchases') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-info border border-info border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-graph-up fs-2tx text-info me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.avg_order_value') }}</div>
                                        <div class="fs-2 fw-bold text-info" id="sumAvgOrder">—</div>
                                        <div class="text-muted fs-8">{{ __('passwords.per_order_average') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-trophy fs-2tx text-warning me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.top_supplier') }}</div>
                                        <div class="fs-4 fw-bold text-warning" id="sumTopSupplier">—</div>
                                        <div class="text-muted fs-8" id="sumTopSupplierSpent"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Charts --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-pie-chart fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.abc_classification_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="spAbcChart" style="height: 320px;"></div>
                                <div id="spAbcEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-bar-chart fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.top_10_suppliers_by_spend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="spTopChart" style="height: 320px;"></div>
                                <div id="spTopEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-truck fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.delivery_performance') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="spDeliveryChart" style="height: 320px;"></div>
                                <div id="spDeliveryEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-pie-chart-fill fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.order_value_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="spValueChart" style="height: 320px;"></div>
                                <div id="spValueEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.supplier_performance_metrics') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="spBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="spTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">#</th>
                                    <th>{{ __('passwords.supplier') }}</th>
                                    <th>{{ __('passwords.abc_classification') }}</th>
                                    <th>{{ __('passwords.total_orders') }}</th>
                                    <th>{{ __('passwords.total_spent') }}</th>
                                    <th>{{ __('passwords.spend_percentage') }}</th>
                                    <th>{{ __('passwords.avg_order_value') }}</th>
                                    <th>{{ __('passwords.on_time_delivery_rate') }}</th>
                                    <th>{{ __('passwords.avg_delivery_days') }}</th>
                                    <th>{{ __('passwords.performance_score') }}</th>
                                </tr></thead>
                                <tbody id="spTableBody"></tbody>
                            </table></div>
                            <div id="spEmpty" class="text-center py-10 d-none">
                                <i class="bi bi-people fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_suppliers') }}</h4>
                                <p class="text-muted fs-6">{{ __('passwords.no_supplier_performance_data') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="spPaginationWrap">
                                <span class="text-muted fs-7" id="spPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="spPagination"></ul></nav>
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
    .sp-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: sp-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .sp-sk-text   { height: 12px; border-radius: 4px; }
    .sp-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .sp-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .sp-sk-chart  { width: 100%; height: 320px; border-radius: 8px; }
    @keyframes sp-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #spSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.SP_ROUTES = {
        data: '{{ route("reports.purchasing.supplier-performance-data") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.SP_BOOTSTRAP = {
        startDate:  @json($startDate),
        endDate:    @json($endDate),
        supplierId: @json($supplierId),
        perPage:    @json($perPage),
    };
</script>
@include('reports.purchasing.supplier-performance-scripts')
@endpush