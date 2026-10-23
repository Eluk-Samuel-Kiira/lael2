{{-- resources/views/reports/inventory/excess-stock.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.excess_stock_report'))

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
                                {{ __('pagination.excess_stock_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.inventory_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.excess_stock') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-primary" id="esExportBtn">
                                <i class="bi bi-download fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="window.print()">
                                <i class="bi bi-printer fs-2 me-1 me-sm-2"></i>
                                <span class="d-none d-sm-inline">{{ __('pagination.print') }}</span>
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
                        <form id="esFilterForm" class="row g-3" onsubmit="return false;">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('pagination.excess_threshold') }}</label>
                                <select class="form-select" id="esThreshold">
                                    <option value="1.2" @selected($excessThreshold == 1.2)>120% (20% Excess)</option>
                                    <option value="1.3" @selected($excessThreshold == 1.3)>130% (30% Excess)</option>
                                    <option value="1.5" @selected($excessThreshold == 1.5)>150% (50% Excess)</option>
                                    <option value="2.0" @selected($excessThreshold == 2.0)>200% (100% Excess)</option>
                                    <option value="2.5" @selected($excessThreshold == 2.5)>250% (150% Excess)</option>
                                </select>
                                <div class="form-text">{{ __('pagination.items_above_threshold_are_excess') }}</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('pagination.product_variant') }}</label>
                                <select class="form-select" id="esVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                    <option value=""></option>
                                    @foreach($variants as $v)
                                        <option value="{{ $v->id }}" @selected((int)$variantId === $v->id)>
                                            {{ \Illuminate\Support\Str::limit($v->name, 30) }}{{ $v->sku ? ' (' . $v->sku . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                <select class="form-select" id="esLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                    <option value=""></option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" @selected((int)$locationId === $loc->id)>{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">{{ __('accounting.department') }}</label>
                                <select class="form-select" id="esDepartment" data-control="select2" data-placeholder="{{ __('pagination.all_departments') }}">
                                    <option value=""></option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" @selected((int)$departmentId === $dept->id)>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-primary" id="esApplyBtn">
                                    <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                </button>
                                <button type="button" class="btn btn-light" id="esClearBtn">
                                    <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="esSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="es-sk es-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="es-sk es-sk-text" style="width:55%;height:28px;"></div></div>
                            <div class="d-flex justify-content-center mb-1"><div class="es-sk es-sk-text" style="width:70%;"></div></div>
                            <div class="d-flex justify-content-center"><div class="es-sk es-sk-text" style="width:45%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 3; $i++)
                        <div class="col-md-4"><div class="card card-flush"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="es-sk es-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="es-sk es-sk-text" style="width:55%;height:24px;"></div></div>
                            <div class="d-flex justify-content-center"><div class="es-sk es-sk-text" style="width:70%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="es-sk es-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="es-sk es-sk-text mb-2" style="width:35%;"></div>
                                <div class="es-sk es-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="es-sk es-sk-badge"></div>
                            <div class="es-sk es-sk-badge"></div>
                            <div class="es-sk es-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="esContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-box fs-2tx text-warning"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumItems">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Excess Items</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.items_exceeding_threshold') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-upc-scan fs-2tx text-danger"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumQty">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Excess Quantity</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.extra_units_above_threshold') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-currency-dollar fs-2tx text-danger"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumValue">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Excess Value</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.value_of_excess_stock') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card card-flush bg-light-warning border border-warning border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-percent fs-2tx text-warning"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="sumAvg">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Avg Excess %</span>
                                    <span class="fs-8 text-muted mt-1">{{ __('pagination.average_excess_per_item') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Severity categories --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-warning border border-warning border-2 h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-exclamation-triangle fs-2tx text-warning"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="catModerate">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Moderate Excess</span>
                                    <span class="fs-7 text-warning fw-bold mb-1">50–100%</span>
                                    <span class="fs-8 text-muted">{{ __('pagination.moderate_excess_description') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-danger border border-danger border-2 h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-exclamation-octagon fs-2tx text-danger"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="catHigh">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">High Excess</span>
                                    <span class="fs-7 text-danger fw-bold mb-1">100–200%</span>
                                    <span class="fs-8 text-muted">{{ __('pagination.high_excess_description') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-danger border border-danger border-2 h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-3"><i class="bi bi-x-circle fs-2tx text-danger"></i></div>
                                    <span class="fs-1 fw-bold text-gray-800" id="catCritical">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Critical Excess</span>
                                    <span class="fs-7 text-danger fw-bold mb-1">&gt; 200%</span>
                                    <span class="fs-8 text-muted">{{ __('pagination.critical_excess_description') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.excess_stock_details') }}</h3>
                                </div>
                                <span class="badge badge-light-warning fs-7" id="esBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="esTable">
                                <thead><tr class="fw-bold fs-6 text-gray-800 bg-light">
                                    <th class="ps-4" style="width: 10%;">{{ __('pagination.sku') }}</th>
                                    <th style="width: 18%;">{{ __('pagination.product') }}</th>
                                    <th style="width: 10%;">{{ __('pagination.department') }}</th>
                                    <th style="width: 10%;">{{ __('pagination.location') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.current_stock') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.preferred_stock') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.excess_quantity') }}</th>
                                    <th style="width: 8%;" class="text-center">{{ __('pagination.excess_percentage') }}</th>
                                    <th style="width: 10%;" class="text-center">{{ __('pagination.severity') }}</th>
                                    <th style="width: 10%;" class="text-end">{{ __('pagination.excess_value') }}</th>
                                </tr></thead>
                                <tbody id="esTableBody"></tbody>
                                <tfoot class="bg-light d-none" id="esTableFooter">
                                    <tr>
                                        <td colspan="9" class="text-end fw-bold">{{ __('pagination.page_total') }}:</td>
                                        <td class="text-end fw-bold text-danger" id="ftPageValue">—</td>
                                    </tr>
                                </tfoot>
                            </table></div>
                            <div id="esEmpty" class="text-center py-12 d-none">
                                <i class="bi bi-check-circle fs-4tx text-success mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_excess_stock_found') }}</h4>
                                <p class="text-muted fs-6">{{ __('pagination.all_stock_within_limits') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="esPaginationWrap">
                                <span class="text-muted fs-7" id="esPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="esPagination"></ul></nav>
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
    .es-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: es-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .es-sk-text   { height: 12px; border-radius: 4px; }
    .es-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .es-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes es-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #esSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script>
    window.ES_ROUTES = {
        data: '{{ route("reports.inventory.excess-stock-data") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.ES_BOOTSTRAP = {
        departmentId:    @json($departmentId),
        locationId:      @json($locationId),
        variantId:       @json($variantId),
        excessThreshold: @json($excessThreshold),
        perPage:         @json($perPage),
    };
</script>
@include('reports.inventory.excess-stock-scripts')
@endpush