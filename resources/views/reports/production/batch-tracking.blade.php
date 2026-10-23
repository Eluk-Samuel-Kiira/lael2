{{-- resources/views/reports/production/batch-tracking.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.production_batch_tracking'))

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
                                {{ __('pagination.production_batch_tracking') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.manufacturing') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.production_batch_tracking') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-success" id="btExportBtn">
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
                                <form id="btFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_from') }}</label>
                                        <input type="date" class="form-control" id="btStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.date_to') }}</label>
                                        <input type="date" class="form-control" id="btEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.location') }}</label>
                                        <select class="form-select" id="btLocation" data-control="select2" data-placeholder="{{ __('pagination.all_locations') }}">
                                            <option value=""></option>
                                            @foreach($locations as $location)
                                                <option value="{{ $location->id }}" @selected((int)$locationId === $location->id)>{{ $location->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.variant') }}</label>
                                        <select class="form-select" id="btVariant" data-control="select2" data-placeholder="{{ __('pagination.all_variants') }}">
                                            <option value=""></option>
                                            @foreach($variants as $variant)
                                                <option value="{{ $variant->id }}" @selected((int)$variantId === $variant->id)>{{ $variant->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.batch_type') }}</label>
                                        <select class="form-select" id="btBatchType" data-control="select2">
                                            @foreach($batchTypes as $b)
                                                <option value="{{ $b['value'] }}" @selected($batchType === $b['value'])>{{ $b['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">{{ __('pagination.search') }}</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ki-duotone ki-magnifier fs-2"></i></span>
                                            <input type="text" class="form-control" id="btSearch" value="{{ $search }}" placeholder="{{ __('pagination.search_batches') }}">
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-primary" id="btApplyBtn">
                                            <i class="ki-duotone ki-filter fs-2 me-1"></i>{{ __('pagination.apply') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="btClearBtn">
                                            <i class="ki-duotone ki-cross fs-2 me-1"></i>{{ __('pagination.clear') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═════════ SKELETON ═════════ --}}
                <div id="btSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 6; $i++)
                        <div class="col-md-6 col-lg-2"><div class="card card-flush h-100"><div class="card-body d-flex flex-column justify-content-center text-center">
                            <div class="d-flex justify-content-center mb-3"><div class="bt-sk bt-sk-circle"></div></div>
                            <div class="d-flex justify-content-center mb-2"><div class="bt-sk bt-sk-text" style="width:55%;height:24px;"></div></div>
                            <div class="d-flex justify-content-center mb-1"><div class="bt-sk bt-sk-text" style="width:70%;"></div></div>
                            <div class="d-flex justify-content-center"><div class="bt-sk bt-sk-text" style="width:45%;"></div></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-3"><div class="card card-flush"><div class="card-body d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <div class="bt-sk bt-sk-text mb-2" style="width:50%;"></div>
                                <div class="bt-sk bt-sk-text" style="width:35%;height:24px;"></div>
                            </div>
                            <div class="bt-sk bt-sk-circle"></div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card"><div class="card-body"><div class="bt-sk bt-sk-chart"></div></div></div></div>
                        <div class="col-lg-4"><div class="card"><div class="card-body"><div class="bt-sk bt-sk-chart"></div></div></div></div>
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="bt-sk bt-sk-text" style="width:60px;"></div>
                            <div class="flex-grow-1">
                                <div class="bt-sk bt-sk-text mb-2" style="width:35%;"></div>
                                <div class="bt-sk bt-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="bt-sk bt-sk-badge"></div>
                            <div class="bt-sk bt-sk-badge"></div>
                            <div class="bt-sk bt-sk-badge"></div>
                            <div class="bt-sk bt-sk-text" style="width:80px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- ═════════ CONTENT ═════════ --}}
                <div id="btContent" class="d-none">

                    {{-- 6 summary cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-primary border border-primary border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-bucket fs-2tx text-primary"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-2 fw-bold text-primary" id="btTotalLogs">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">{{ __('pagination.total_logs') }}</span>
                                    <span class="text-muted fs-8" id="btUniqueBatches">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-exit fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-2 fw-bold text-success" id="btProdKg">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Produced (kg)</span>
                                    <span class="text-muted fs-8" id="btProdKgSub">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-enter fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-2 fw-bold text-danger" id="btConsKg">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Consumed (kg)</span>
                                    <span class="text-muted fs-8" id="btConsKgSub">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush h-100" id="btNetCard">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-calculator fs-2tx" id="btNetIcon"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-2 fw-bold" id="btNetKg">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">Net (kg)</span>
                                    <span class="text-muted fs-8">produced − consumed</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-success border border-success border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-dollar fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-2 fw-bold text-success" id="btProdCost">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">{{ __('pagination.produced_cost') }}</span>
                                    <span class="text-muted fs-8" id="btProdCostSub">—</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-danger border border-danger border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-2"><i class="ki-duotone ki-dollar fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i></div>
                                    <span class="fs-2 fw-bold text-danger" id="btConsCost">—</span>
                                    <span class="text-gray-600 fw-semibold fs-7">{{ __('pagination.consumed_cost') }}</span>
                                    <span class="text-muted fs-8" id="btConsCostSub">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 4 status cards --}}
                    <div class="row g-6 mb-6">
                        <div class="col-md-3"><div class="card card-flush bg-light-success"><div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted fs-7">{{ __('pagination.active_batches') }}</span>
                                <div class="fs-3 fw-bold text-success" id="btActive">—</div>
                            </div>
                            <i class="ki-duotone ki-check-circle fs-2tx text-success"><span class="path1"></span><span class="path2"></span></i>
                        </div></div></div>
                        <div class="col-md-3"><div class="card card-flush bg-light-danger"><div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted fs-7">{{ __('pagination.depleted_batches') }}</span>
                                <div class="fs-3 fw-bold text-danger" id="btDepleted">—</div>
                            </div>
                            <i class="ki-duotone ki-cross-circle fs-2tx text-danger"><span class="path1"></span><span class="path2"></span></i>
                        </div></div></div>
                        <div class="col-md-3"><div class="card card-flush bg-light-warning"><div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted fs-7">{{ __('pagination.expired_batches') }}</span>
                                <div class="fs-3 fw-bold text-warning" id="btExpired">—</div>
                            </div>
                            <i class="ki-duotone ki-clock fs-2tx text-warning"><span class="path1"></span><span class="path2"></span></i>
                        </div></div></div>
                        <div class="col-md-3"><div class="card card-flush bg-light-info"><div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted fs-7">{{ __('pagination.suppliers_involved') }}</span>
                                <div class="fs-3 fw-bold text-info" id="btSuppliers">—</div>
                                <span class="text-muted fs-8" id="btSuppliersSub">—</span>
                            </div>
                            <i class="ki-duotone ki-shop fs-2tx text-info"><span class="path1"></span><span class="path2"></span></i>
                        </div></div></div>
                    </div>

                    {{-- Charts --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-8"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.monthly_batch_trends') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="btTrendChart" style="height: 350px;"></div>
                                <div id="btTrendEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-4"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.batch_by_variant') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="btVariantChart" style="height: 350px;"></div>
                                <div id="btVariantEmpty" class="text-center text-muted py-10 d-none">{{ __('pagination.no_data_available') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Top produced / consumed --}}
                    <div class="row g-6 mb-6">
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-exit fs-2 me-2 text-success"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_produced_batches') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                    <thead><tr class="fw-bold fs-7 text-gray-800 bg-light">
                                        <th>#</th><th>{{ __('pagination.batch_number') }}</th><th>{{ __('pagination.product') }}</th>
                                        <th class="text-center">Qty</th><th class="text-center">KG</th><th class="text-center">Cost</th>
                                    </tr></thead>
                                    <tbody id="btTopProdBody"></tbody>
                                </table></div>
                                <div id="btTopProdEmpty" class="text-center text-muted py-5 d-none">{{ __('pagination.no_batches_found') }}</div>
                            </div>
                        </div></div>
                        <div class="col-lg-6"><div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-enter fs-2 me-2 text-danger"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.top_consumed_batches') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                    <thead><tr class="fw-bold fs-7 text-gray-800 bg-light">
                                        <th>#</th><th>{{ __('pagination.batch_number') }}</th><th>{{ __('pagination.product') }}</th>
                                        <th class="text-center">Qty</th><th class="text-center">KG</th><th class="text-center">Cost</th>
                                    </tr></thead>
                                    <tbody id="btTopConsBody"></tbody>
                                </table></div>
                                <div id="btTopConsEmpty" class="text-center text-muted py-5 d-none">{{ __('pagination.no_batches_found') }}</div>
                            </div>
                        </div></div>
                    </div>

                    {{-- By variant --}}
                    <div class="card mb-6 d-none" id="btVariantCard">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="ki-duotone ki-cube-2 fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                <h3 class="fw-bold m-0">{{ __('pagination.batch_by_variant') }}</h3>
                            </div>
                            <div class="card-toolbar"><span class="badge badge-light-primary fs-7" id="btVariantCount"></span></div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                <thead><tr class="fw-bold fs-7 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('pagination.product') }}</th>
                                    <th class="text-center">Weight/Unit</th>
                                    <th class="text-center">{{ __('pagination.unique_batches') }}</th>
                                    <th class="text-center">Produced #</th>
                                    <th class="text-center">Consumed #</th>
                                    <th class="text-end">Produced (kg)</th>
                                    <th class="text-end">Consumed (kg)</th>
                                    <th class="text-end">Net (kg)</th>
                                    <th class="text-end">Cost / kg</th>
                                    <th class="text-end pe-4">{{ __('pagination.net_cost') }}</th>
                                </tr></thead>
                                <tbody id="btVariantBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- By month --}}
                    <div class="card mb-6 d-none" id="btMonthCard">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center">
                                <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                <h3 class="fw-bold m-0">{{ __('pagination.monthly_batch_breakdown') }}</h3>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                <thead><tr class="fw-bold fs-7 text-gray-800 bg-light">
                                    <th class="ps-4">{{ __('pagination.period') }}</th>
                                    <th class="text-center">Produced #</th>
                                    <th class="text-center">Consumed #</th>
                                    <th class="text-end">Produced (kg)</th>
                                    <th class="text-end">Consumed (kg)</th>
                                    <th class="text-end">Net (kg)</th>
                                    <th class="text-end">{{ __('pagination.produced_cost') }}</th>
                                    <th class="text-end pe-4">{{ __('pagination.consumed_cost') }}</th>
                                </tr></thead>
                                <tbody id="btMonthBody"></tbody>
                            </table></div>
                        </div>
                    </div>

                    {{-- Batch logs --}}
                    <div class="row"><div class="col-12"><div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"><span class="path1"></span><span class="path2"></span></i>
                                    <h3 class="fw-bold m-0">{{ __('pagination.batch_logs') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="btLogsBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-3 align-middle">
                                <thead><tr class="fw-bold fs-7 text-gray-800 bg-light">
                                    <th class="ps-4 min-w-60px">#</th>
                                    <th class="min-w-140px">{{ __('pagination.event_date') }}</th>
                                    <th class="min-w-200px">{{ __('pagination.batch_product') }}</th>
                                    <th class="min-w-140px">{{ __('pagination.type') }}</th>
                                    <th class="min-w-180px">{{ __('pagination.source') }}</th>
                                    <th class="text-center min-w-160px">Movement</th>
                                    <th class="text-center min-w-140px">Balance (kg)</th>
                                    <th class="text-end min-w-120px">Cost/kg</th>
                                    <th class="text-end min-w-120px">{{ __('pagination.total_cost') }}</th>
                                    <th class="min-w-120px">{{ __('pagination.expiry') }}</th>
                                    <th class="pe-4 min-w-140px">{{ __('pagination.performed_by') }}</th>
                                </tr></thead>
                                <tbody id="btLogsBody"></tbody>
                                <tfoot class="bg-light fw-bold d-none" id="btLogsFooter">
                                    <tr>
                                        <td colspan="5" class="ps-4 text-end">{{ __('pagination.totals') }}:</td>
                                        <td class="text-center" id="btLogsFooterMovement"></td>
                                        <td></td><td></td>
                                        <td class="text-end" id="btLogsFooterCost"></td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table></div>
                            <div id="btLogsEmpty" class="text-center py-10 d-none">
                                <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"><span class="path1"></span><span class="path2"></span></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('pagination.no_data_available') }}</h4>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="btPaginationWrap">
                                <span class="text-muted fs-7" id="btPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="btPagination"></ul></nav>
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
    .bt-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: bt-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .bt-sk-text   { height: 12px; border-radius: 4px; }
    .bt-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .bt-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    .bt-sk-chart  { width: 100%; height: 350px; border-radius: 8px; }
    @keyframes bt-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #btSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    window.BT_ROUTES = {
        data:   '{{ route("reports.production.batch-tracking-data") }}',
        export: '{{ route("reports.production.batch-tracking") }}',
    };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.BT_BOOTSTRAP = {
        startDate:  @json($startDate),
        endDate:    @json($endDate),
        locationId: @json($locationId),
        variantId:  @json($variantId),
        batchType:  @json($batchType),
        search:     @json($search),
        perPage:    @json($perPage),
    };
</script>
@include('reports.production.batch-tracking-scripts')
@endpush