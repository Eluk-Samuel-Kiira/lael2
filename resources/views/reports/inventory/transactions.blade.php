{{-- resources/views/reports/inventory/transactions.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_transactions'))

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
                                {{ __('pagination.inventory_transactions') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_transactions') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="txExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title d-flex align-items-center">
                            <i class="bi bi-funnel fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form id="txFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">{{ __('pagination.date_range') }}</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="txStartDate" value="{{ $startDate }}">
                                    <span class="input-group-text">→</span>
                                    <input type="date" class="form-control" id="txEndDate" value="{{ $endDate }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('pagination.transaction_type') }}</label>
                                <select class="form-select" id="txType">
                                    <option value="all" @selected($type === 'all')>{{ __('pagination.all_types') }}</option>
                                    <option value="purchase"    @selected($type === 'purchase')>{{ __('pagination.purchase') }}</option>
                                    <option value="sale"        @selected($type === 'sale')>{{ __('pagination.sale') }}</option>
                                    <option value="return"      @selected($type === 'return')>{{ __('pagination.return') }}</option>
                                    <option value="adjustment"  @selected($type === 'adjustment')>{{ __('pagination.adjustment') }}</option>
                                    <option value="transfer_in" @selected($type === 'transfer_in')>{{ __('pagination.transfer_in') }}</option>
                                    <option value="transfer_out" @selected($type === 'transfer_out')>{{ __('pagination.transfer_out') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                <select class="form-select" id="txVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                    <option value=""></option>
                                    @foreach($variants as $v)
                                        <option value="{{ $v->id }}" @selected((int)$variantId === $v->id)>
                                            {{ \Illuminate\Support\Str::limit($v->name, 30) }}{{ $v->sku ? ' (' . $v->sku . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                <select class="form-select" id="txLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="txApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="txClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="txSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 6; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="tx-sk tx-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="tx-sk tx-sk-text" style="width:55%;height:24px;"></div></div>
                            <div class="d-flex justify-content-center"><div class="tx-sk tx-sk-text" style="width:70%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="tx-sk tx-sk-chart"></div></div></div></div>
                        <div class="col-lg-6"><div class="card"><div class="card-body"><div class="tx-sk tx-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="tx-sk tx-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="tx-sk tx-sk-text mb-2" style="width:35%;"></div>
                                <div class="tx-sk tx-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="tx-sk tx-sk-badge"></div>
                            <div class="tx-sk tx-sk-badge"></div>
                            <div class="tx-sk tx-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="txContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        @php
                            $sumDefs = [
                                ['id'=>'sumTotalTx',   'icon'=>'bi-arrow-left-right', 'color'=>'primary',   'label'=>'total_transactions'],
                                ['id'=>'sumTotalQty',  'icon'=>'bi-boxes',            'color'=>'success',   'label'=>'total_quantity_moved'],
                                ['id'=>'sumPositive',  'icon'=>'bi-arrow-up-circle',  'color'=>'info',      'label'=>'positive_movements'],
                                ['id'=>'sumNegative',  'icon'=>'bi-arrow-down-circle','color'=>'warning',   'label'=>'negative_movements'],
                                ['id'=>'sumNet',       'icon'=>'bi-calculator',       'color'=>'danger',    'label'=>'net_change'],
                                ['id'=>'sumAvgDaily',  'icon'=>'bi-clock-history',    'color'=>'secondary', 'label'=>'avg_daily_transactions'],
                            ];
                        @endphp
                        @foreach($sumDefs as $def)
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-{{ $def['color'] }} border border-{{ $def['color'] }} border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi {{ $def['icon'] }} fs-2tx text-{{ $def['color'] }}"></i></div>
                                    <span class="fs-2 fw-bold text-gray-800" id="{{ $def['id'] }}">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">{{ __('pagination.' . $def['label']) }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Charts --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-pie-chart fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.transaction_type_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="txTypeChart" style="height: 320px;"></div>
                                <div id="txTypeEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-graph-up fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.daily_transaction_trend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="txDailyChart" style="height: 320px;"></div>
                                <div id="txDailyEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Type breakdown --}}
                    <div class="row mb-6" id="txTypeTableCard">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="bi bi-list-check fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.transaction_type_breakdown') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="min-w-150px">{{ __('pagination.type') }}</th>
                                        <th class="min-w-120px text-center">{{ __('pagination.transaction_count') }}</th>
                                        <th class="min-w-150px text-end">{{ __('pagination.total_quantity') }}</th>
                                        <th class="min-w-120px text-center">{{ __('pagination.avg_quantity') }}</th>
                                        <th class="min-w-200px">{{ __('pagination.percentage') }}</th>
                                    </tr></thead>
                                    <tbody id="txTypeBody"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Main table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.transaction_details') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="txBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="txTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4 min-w-150px">{{ __('pagination.date_time') }}</th>
                                    <th class="min-w-120px">{{ __('pagination.sku') }}</th>
                                    <th class="min-w-200px">{{ __('pagination.product') }}</th>
                                    <th class="min-w-100px text-center">{{ __('pagination.type') }}</th>
                                    <th class="min-w-100px text-center">{{ __('pagination.quantity') }}</th>
                                    <th class="min-w-150px">{{ __('pagination.department') }}</th>
                                    <th class="min-w-150px">{{ __('pagination.location') }}</th>
                                    <th class="min-w-150px">{{ __('pagination.reference') }}</th>
                                    <th class="min-w-200px">{{ __('pagination.notes') }}</th>
                                    <th style="width:2%;"></th>
                                </tr></thead>
                                <tbody id="txTableBody"></tbody>
                                <tfoot class="bg-light d-none" id="txTableFooter">
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">{{ __('pagination.current_page') }}:</td>
                                        <td class="text-center fw-bold" id="ftPageQty">—</td>
                                        <td colspan="5"></td>
                                    </tr>
                                </tfoot>
                            </table></div>
                            <div id="txEmpty" class="text-center py-12 d-none">
                                <i class="bi bi-file-earmark-text fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.no_transactions_found') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="txPaginationWrap">
                                <span class="text-muted fs-7" id="txPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="txPagination"></ul></nav>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Drawer --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="txDrawer" style="width: 620px; max-width: 100vw;">
    <div class="offcanvas-header bg-primary py-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-info-circle fs-2 text-white"></i>
            <div>
                <h4 class="offcanvas-title text-white fw-bold mb-0" id="txDrawerLabel">Transaction Detail</h4>
                <span class="text-white opacity-75 fs-7" id="txDrawerSubtitle"></span>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body px-5 py-6">
        <div id="txDrawerSkeleton">
            <div class="tx-sk tx-sk-text mb-3" style="width:60%;"></div>
            <div class="tx-sk tx-sk-text mb-3" style="width:40%;"></div>
            <div class="tx-sk tx-sk-text mb-3" style="width:80%;"></div>
        </div>
        <div id="txDrawerContent" class="d-none"></div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tx-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: tx-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .tx-sk-text   { height: 12px; border-radius: 4px; }
    .tx-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .tx-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .tx-sk-chart  { width: 100%; height: 320px; border-radius: 8px; }
    @keyframes tx-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #txSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
    .tx-row { cursor: pointer; }
    .tx-row:hover { background-color: #f9f9f9; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.TX_ROUTES = {
        data:   '{{ route("reports.inventory.transactions-data") }}',
        detail: '{{ route("reports.inventory.transactions-detail", ["id" => "__ID__"]) }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.TX_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        type:         @json($type),
        departmentId: @json($departmentId),
        locationId:   @json($locationId),
        variantId:    @json($variantId),
        perPage:      @json($perPage),
    };
</script>
@include('reports.inventory.transactions-scripts')
@endpush