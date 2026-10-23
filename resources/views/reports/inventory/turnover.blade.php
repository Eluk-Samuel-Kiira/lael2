{{-- resources/views/reports/inventory/turnover.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.inventory_turnover'))

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
                                {{ __('pagination.inventory_turnover') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_turnover') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="tovExportBtn">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
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
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="tovFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('accounting.date_range') }}</label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="tovStartDate" value="{{ $startDate }}">
                                            <span class="input-group-text">→</span>
                                            <input type="date" class="form-control" id="tovEndDate" value="{{ $endDate }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                        <select class="form-select" id="tovLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                            <option value=""></option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('accounting.department') }}</label>
                                        <select class="form-select" id="tovDepartment" data-control="select2" data-placeholder="{{ __('pagination.all_departments') }}">
                                            <option value=""></option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}" @selected((int)$departmentId === $dept->id)>{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                        <select class="form-select" id="tovVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                            <option value=""></option>
                                            @foreach($variants as $v)
                                                <option value="{{ $v->id }}" @selected((int)$variantId === $v->id)>
                                                    {{ $v->name }}{{ $v->sku ? ' (' . $v->sku . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-primary" id="tovApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                                            {{ __('accounting.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="tovClearBtn">
                                            <i class="ki-duotone ki-cross fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                                            {{ __('accounting.clear') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="tovSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 7; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="tov-sk tov-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="tov-sk tov-sk-text" style="width:55%;height:28px;"></div></div>
                            <div class="d-flex justify-content-center"><div class="tov-sk tov-sk-text" style="width:70%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="tov-sk tov-sk-chart"></div></div></div></div>
                        <div class="col-lg-4"><div class="card"><div class="card-body"><div class="tov-sk tov-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="tov-sk tov-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="tov-sk tov-sk-text mb-2" style="width:35%;"></div>
                                <div class="tov-sk tov-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="tov-sk tov-sk-badge"></div>
                            <div class="tov-sk tov-sk-badge"></div>
                            <div class="tov-sk tov-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="tovContent" class="d-none">

                   {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        @php
                            $statDefs = [
                                ['id' => 'sumAvgRate',   'icon' => 'bi-arrow-repeat',      'color' => 'primary',   'label' => 'avg_turnover_rate'],
                                ['id' => 'sumAvgDays',   'icon' => 'bi-clock-history',     'color' => 'success',   'label' => 'avg_days_held'],
                                ['id' => 'sumTotalMove', 'icon' => 'bi-arrow-left-right',  'color' => 'info',      'label' => 'total_movement'],
                                ['id' => 'sumTotalTx',   'icon' => 'bi-arrow-repeat',      'color' => 'warning',   'label' => 'total_transactions'],
                                ['id' => 'sumFast',      'icon' => 'bi-rocket-takeoff',    'color' => 'danger',    'label' => 'fast_moving_items'],
                                ['id' => 'sumSlow',      'icon' => 'bi-speedometer2',      'color' => 'secondary', 'label' => 'slow_moving_items'],
                                ['id' => 'sumNon',       'icon' => 'bi-pause-circle',      'color' => 'dark',      'label' => 'non_moving_items'],
                            ];
                        @endphp
                        @foreach($statDefs as $stat)
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3">
                                        <i class="bi {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}"></i>
                                    </div>
                                    <div class="mb-1">
                                        <span class="fs-2 fw-bold text-gray-800" id="{{ $stat['id'] }}">—</span>
                                    </div>
                                    <div class="text-gray-600 fw-semibold fs-7">
                                        {{ __('pagination.' . $stat['label']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    {{-- Charts --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.turnover_rate_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="tovRateChart" style="height: 350px;"></div>
                                <div id="tovRateEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-4"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.movement_category_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="tovCategoryChart" style="height: 350px;"></div>
                                <div id="tovCategoryEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Table --}}
                    <div class="row"><div class="col-12"><div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.inventory_turnover_analysis') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="tovBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="tovTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('pagination.sku') }}</th>
                                    <th>{{ __('pagination.product') }}</th>
                                    <th class="text-center">{{ __('pagination.total_movement') }}</th>
                                    <th class="text-center">{{ __('pagination.transaction_count') }}</th>
                                    <th class="text-center">{{ __('pagination.avg_stock_level') }}</th>
                                    <th class="text-center">{{ __('pagination.turnover_rate') }}</th>
                                    <th class="text-center">{{ __('pagination.days_inventory_held') }}</th>
                                    <th class="text-center">{{ __('pagination.movement_category') }}</th>
                                </tr></thead>
                                <tbody id="tovTableBody"></tbody>
                            </table></div>
                            <div id="tovEmpty" class="text-center py-10 d-none">
                                <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.no_turnover_data_found') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="tovPaginationWrap">
                                <span class="text-muted fs-7" id="tovPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="tovPagination"></ul></nav>
                            </div>
                        </div>
                    </div></div></div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .tov-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: tov-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .tov-sk-text   { height: 12px; border-radius: 4px; }
    .tov-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .tov-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .tov-sk-chart  { width: 100%; height: 350px; border-radius: 8px; }
    @keyframes tov-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #tovSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.TOV_ROUTES = {
        data: '{{ route("reports.inventory.turnover-data") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.TOV_BOOTSTRAP = {
        startDate:    @json($startDate),
        endDate:      @json($endDate),
        locationId:   @json($locationId),
        departmentId: @json($departmentId),
        variantId:    @json($variantId),
        perPage:      @json($perPage),
    };
</script>
@include('reports.inventory.turnover-scripts')
@endpush