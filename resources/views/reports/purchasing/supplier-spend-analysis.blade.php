{{-- resources/views/reports/purchasing/supplier-spend-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.supplier_spend_analysis'))

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
                                {{ __('pagination.supplier_spend_analysis') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.supplier_spend_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="ssExportBtn">
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
                                <form id="ssFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.start_date') }}</label>
                                        <input type="date" class="form-control" id="ssStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.end_date') }}</label>
                                        <input type="date" class="form-control" id="ssEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.period') }}</label>
                                        <select class="form-select" id="ssPeriod">
                                            <option value="monthly"   @selected($period === 'monthly')>{{ __('pagination.monthly') }}</option>
                                            <option value="quarterly" @selected($period === 'quarterly')>{{ __('pagination.quarterly') }}</option>
                                            <option value="yearly"    @selected($period === 'yearly')>{{ __('pagination.yearly') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                        <select class="form-select" id="ssSupplier" data-control="select2" data-placeholder="{{ __('passwords.all_suppliers') }}">
                                            <option value=""></option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}" @selected((int)$supplierId === $sup->id)>{{ $sup->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-primary" id="ssApplyBtn">
                                            <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="ssClearBtn">
                                            <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SKELETON --}}
                <div id="ssSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="ss-sk ss-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="ss-sk ss-sk-text mb-2" style="width:60%;"></div>
                                    <div class="ss-sk ss-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card"><div class="card-body"><div class="ss-sk ss-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="ss-sk ss-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="ss-sk ss-sk-text mb-2" style="width:35%;"></div>
                                <div class="ss-sk ss-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="ss-sk ss-sk-badge"></div>
                            <div class="ss-sk ss-sk-text" style="width:100px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="ssContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex align-items-center">
                                    <i class="bi bi-people fs-2tx text-primary me-3"></i>
                                    <div>
                                        <div class="text-gray-600 fw-semibold fs-7">{{ __('passwords.total_suppliers') }}</div>
                                        <div class="fs-2 fw-bold text-primary" id="sumSuppliers">—</div>
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
                                        <div class="fs-2 fw-bold text-success" id="sumSpent">—</div>
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
                                        <div class="text-muted fs-8" id="sumOrderCount"></div>
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
                                        <div class="fs-4 fw-bold text-warning" id="sumTopName">—</div>
                                        <div class="text-muted fs-8" id="sumTopSpent"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Spend trend chart --}}
                    <div class="row g-6 mb-6">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-graph-up-arrow fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.spend_trend') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7" id="ssPeriodBadge"></span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="ssTrendChart" style="height: 400px;"></div>
                                <div id="ssTrendEmpty" class="text-center py-10 d-none">
                                    <i class="bi bi-bar-chart fs-4tx text-gray-400 mb-3"></i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_trend_data') }}</h4>
                                    <p class="text-muted fs-6">{{ __('passwords.no_spend_data_for_selected_period') }}</p>
                                </div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.supplier_spend_analysis') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="ssBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="ssTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4 min-w-50px">#</th>
                                    <th style="min-width: 150px;">{{ __('passwords.supplier') }}</th>
                                    <th class="min-w-100px text-center">{{ __('passwords.total_orders') }}</th>
                                    <th class="min-w-150px text-end">{{ __('passwords.total_spent') }}</th>
                                    <th class="min-w-120px text-center">{{ __('passwords.spend_percentage') }}</th>
                                    <th class="min-w-150px text-end">{{ __('passwords.avg_order_value') }}</th>
                                    <th class="min-w-150px text-end">{{ __('passwords.min_order') }}</th>
                                    <th class="min-w-150px text-end">{{ __('passwords.max_order') }}</th>
                                    <th class="min-w-150px">{{ __('passwords.last_order') }}</th>
                                    <th class="min-w-80px text-center">{{ __('passwords.actions') }}</th>
                                </tr></thead>
                                <tbody id="ssTableBody"></tbody>
                                <tfoot class="bg-light d-none" id="ssTableFooter">
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">{{ __('pagination.current_page') }}:</td>
                                        <td class="text-center fw-bold" id="ftPageOrders">—</td>
                                        <td class="text-end fw-bold text-primary" id="ftPageSpent">—</td>
                                        <td colspan="6"></td>
                                    </tr>
                                </tfoot>
                            </table></div>
                            <div id="ssEmpty" class="text-center py-10 d-none">
                                <i class="bi bi-people fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_supplier_spend_data') }}</h4>
                                <p class="text-muted fs-6">{{ __('passwords.no_spend_data_for_selected_period') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="ssPaginationWrap">
                                <span class="text-muted fs-7" id="ssPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="ssPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Drawer --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="ssDrawer" style="width: 820px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-shop fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="ssDrawerLabel">{{ __('passwords.supplier_spend_details') }}</h4>
                <span class="text-white opacity-75 fs-7" id="ssDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="ssDrawerSkeleton">
            <div class="ss-sk ss-sk-text mb-3" style="width:60%;"></div>
            <div class="ss-sk ss-sk-text mb-3" style="width:40%;"></div>
            <div class="ss-sk ss-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="ssDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .ss-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: ss-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .ss-sk-text   { height: 12px; border-radius: 4px; }
    .ss-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .ss-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .ss-sk-chart  { width: 100%; height: 400px; border-radius: 8px; }
    @keyframes ss-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #ssSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .ss-row { cursor: pointer; }
    .ss-row:hover { background-color: #f9f9f9; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.SS_ROUTES = {
        data:   '{{ route("reports.purchasing.supplier-spend-analysis-data") }}',
        detail: '{{ route("reports.purchasing.supplier-spend-analysis-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.SS_BOOTSTRAP = {
        startDate:  @json($startDate),
        endDate:    @json($endDate),
        supplierId: @json($supplierId),
        period:     @json($period),
        perPage:    @json($perPage),
    };
</script>
@include('reports.purchasing.supplier-spend-analysis-scripts')
@endpush