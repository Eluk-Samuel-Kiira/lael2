{{-- resources/views/reports/inventory/stock-aging.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.stock_aging_report'))

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
                                {{ __('pagination.stock_aging_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.stock_aging') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="saExportBtn">
                                <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
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
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="saFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                        <select class="form-select" id="saLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                            <option value=""></option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('accounting.department') }}</label>
                                        <select class="form-select" id="saDepartment" data-control="select2" data-placeholder="{{ __('pagination.all_departments') }}">
                                            <option value=""></option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}" @selected((int)$departmentId === $dept->id)>{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                        <select class="form-select" id="saVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                            <option value=""></option>
                                            @foreach($variants as $v)
                                                <option value="{{ $v->id }}" @selected((int)$variantId === $v->id)>
                                                    {{ $v->name }}{{ $v->sku ? ' (' . $v->sku . ')' : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('pagination.aging_category') }}</label>
                                        <select class="form-select" id="saCategory">
                                            <option value="">{{ __('pagination.all_categories') }}</option>
                                            <option value="expired"       @selected($category === 'expired')>{{ __('pagination.expired') }}</option>
                                            <option value="1_week"        @selected($category === '1_week')>{{ __('pagination.within_1_week') }}</option>
                                            <option value="1_month"       @selected($category === '1_month')>{{ __('pagination.within_1_month') }}</option>
                                            <option value="3_months"      @selected($category === '3_months')>{{ __('pagination.within_3_months') }}</option>
                                            <option value="6_months"      @selected($category === '6_months')>{{ __('pagination.within_6_months') }}</option>
                                            <option value="over_6_months" @selected($category === 'over_6_months')>{{ __('pagination.over_6_months') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-primary" id="saApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                                            {{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="saClearBtn">
                                            <i class="ki-duotone ki-cross fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                                            {{ __('pagination.clear_filters') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="saSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 6; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="sa-sk sa-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="sa-sk sa-sk-text" style="width:55%;height:24px;"></div></div>
                            <div class="d-flex justify-content-center"><div class="sa-sk sa-sk-text" style="width:70%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-3"><div class="card card-flush"><div class="card-body d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <div class="sa-sk sa-sk-text mb-2" style="width:50%;"></div>
                                <div class="sa-sk sa-sk-text" style="width:35%;height:24px;"></div>
                            </div>
                            <div class="sa-sk sa-sk-circle"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="sa-sk sa-sk-chart"></div></div></div></div>
                        <div class="col-lg-4"><div class="card"><div class="card-body"><div class="sa-sk sa-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="sa-sk sa-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="sa-sk sa-sk-text mb-2" style="width:35%;"></div>
                                <div class="sa-sk sa-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="sa-sk sa-sk-badge"></div>
                            <div class="sa-sk sa-sk-badge"></div>
                            <div class="sa-sk sa-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="saContent" class="d-none">

                    {{-- Aging categories (6 cards) --}}
                    <div class="row g-6 mb-6">
                        @php
                            $agingDefs = [
                                ['id' => 'sumExpired',  'icon' => 'ki-cross',         'color' => 'danger',  'label' => 'within_1_week',  'subtitle' => 'immediate_action_required'],
                                ['id' => 'sumWeek',     'icon' => 'ki-clock',         'color' => 'warning', 'label' => 'within_1_week',  'subtitle' => 'urgent_attention'],
                                ['id' => 'sumMonth',    'icon' => 'ki-time',          'color' => 'warning', 'label' => 'within_1_month', 'subtitle' => 'prioritize_usage'],
                                ['id' => 'sum3Months',  'icon' => 'ki-calendar-8',    'color' => 'info',    'label' => 'within_3_months','subtitle' => 'monitor_closely'],
                                ['id' => 'sum6Months',  'icon' => 'ki-calendar-tick', 'color' => 'success', 'label' => 'within_6_months','subtitle' => 'good_stock'],
                                ['id' => 'sumOver6',    'icon' => 'ki-shield-tick',   'color' => 'primary', 'label' => 'over_6_months',  'subtitle' => 'long_term_stock'],
                            ];
                        @endphp
                        @foreach($agingDefs as $def)
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-{{ $def['color'] }} border border-{{ $def['color'] }} border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3">
                                        <i class="ki-duotone {{ $def['icon'] }} fs-2tx text-{{ $def['color'] }}">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </div>
                                    <div class="mb-1"><span class="fs-1 fw-bold text-gray-800" id="{{ $def['id'] }}">—</span></div>
                                    <div class="text-gray-600 fw-semibold mb-2">{{ __('pagination.' . $def['subtitle']) }}</div>
                                    <div class="text-muted fs-8">{{ __('pagination.' . $def['label']) }}</div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Additional summary --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-3">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.total_items_analyzed') }}</span>
                                        <div class="fs-2 fw-bold" id="sumTotalItems">—</div>
                                    </div>
                                    <i class="ki-duotone ki-box fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.total_value_at_risk') }}</span>
                                        <div class="fs-2 fw-bold text-danger" id="sumValueAtRisk">—</div>
                                    </div>
                                    <i class="ki-duotone ki-dollar fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-flush bg-light-success border border-success border-dashed">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.total_inventory_value') }}</span>
                                        <div class="fs-2 fw-bold text-success" id="sumTotalInventory">—</div>
                                    </div>
                                    <i class="ki-duotone ki-dollar fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card card-flush bg-light-info border border-info border-dashed">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">{{ __('pagination.batch_tracked_items') }}</span>
                                        <div class="fs-2 fw-bold text-info" id="sumBatchTracked">—</div>
                                    </div>
                                    <i class="ki-duotone ki-barcode fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Charts --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.stock_aging_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="saDistChart" style="height: 350px;"></div>
                                <div id="saDistEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-4"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.aging_category_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="saCatChart" style="height: 350px;"></div>
                                <div id="saCatEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Table --}}
                    <div class="row"><div class="col-12"><div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.stock_aging_details') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="saBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="saTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4 min-w-120px">{{ __('pagination.sku') }}</th>
                                    <th class="min-w-180px">{{ __('pagination.product') }}</th>
                                    <th class="min-w-120px">{{ __('pagination.department') }}</th>
                                    <th class="min-w-120px">{{ __('pagination.location') }}</th>
                                    <th class="min-w-150px">{{ __('pagination.batch_info') }}</th>
                                    <th class="min-w-120px">{{ __('pagination.expiry_date') }}</th>
                                    <th class="min-w-100px text-center">{{ __('pagination.days_to_expiry') }}</th>
                                    <th class="min-w-100px text-center">{{ __('pagination.quantity') }}</th>
                                    <th class="min-w-150px">{{ __('pagination.pricing') }}</th>
                                    <th class="min-w-120px text-center">{{ __('pagination.status') }}</th>
                                </tr></thead>
                                <tbody id="saTableBody"></tbody>
                                <tfoot class="bg-light d-none" id="saTableFooter">
                                    <tr>
                                        <td colspan="7" class="text-end fw-bold">{{ __('pagination.total') }}:</td>
                                        <td class="text-center fw-bold" id="ftQuantity">—</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table></div>
                            <div id="saEmpty" class="text-center py-10 d-none">
                                <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.no_stock_aging_data_found') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="saPaginationWrap">
                                <span class="text-muted fs-7" id="saPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="saPagination"></ul></nav>
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
    .sa-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: sa-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .sa-sk-text   { height: 12px; border-radius: 4px; }
    .sa-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .sa-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .sa-sk-chart  { width: 100%; height: 350px; border-radius: 8px; }
    @keyframes sa-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #saSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.SA_ROUTES = {
        data: '{{ route("reports.inventory.stock-aging-data") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.SA_BOOTSTRAP = {
        departmentId: @json($departmentId),
        locationId:   @json($locationId),
        variantId:    @json($variantId),
        category:     @json($category),
        perPage:      @json($perPage),
    };
</script>
@include('reports.inventory.stock-aging-scripts')
@endpush