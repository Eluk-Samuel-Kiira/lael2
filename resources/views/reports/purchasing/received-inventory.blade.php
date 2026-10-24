@extends('layouts.app')

@section('title', __('pagination.received_inventory_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- Toolbar --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container"
                        class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0 w-100">

                        {{-- Title block — extreme left --}}
                        <div class="page-title d-flex flex-column align-items-start text-start me-auto">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('pagination.received_inventory_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted"><a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('pagination.dashboard') }}</a></li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.received_inventory_report') }}</li>
                            </ul>
                        </div>

                        {{-- Export — extreme right --}}
                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                            <button type="button" class="btn btn-sm btn-primary" id="riExportBtn">
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
                                <form id="riFilterForm" class="row g-3" onsubmit="return false;">
                                    <div class="col-md-3">
                                        <label class="form-label required fw-semibold">{{ __('pagination.start_date') }}</label>
                                        <input type="date" class="form-control" id="riStartDate" value="{{ $startDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label required fw-semibold">{{ __('pagination.end_date') }}</label>
                                        <input type="date" class="form-control" id="riEndDate" value="{{ $endDate }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                        <select class="form-select" id="riSupplier" data-control="select2" data-placeholder="{{ __('passwords.all_suppliers') }}">
                                            <option value=""></option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}" @selected((int)$supplierId === $sup->id)>{{ $sup->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('passwords.product_variant') }}</label>
                                        <select class="form-select" id="riVariant" data-control="select2" data-placeholder="{{ __('passwords.all_products') }}">
                                            <option value=""></option>
                                            @foreach($variants as $v)
                                                <option value="{{ $v->id }}" @selected((int)$productVariantId === $v->id)>
                                                    {{ $v->name }}@if($v->product) ({{ $v->product->name }})@endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">{{ __('passwords.batch_number') }}</label>
                                        <select class="form-select" id="riBatch" data-control="select2" data-placeholder="{{ __('passwords.select_batch') }}">
                                            <option value=""></option>
                                            @foreach($batchNumbers as $b)
                                                <option value="{{ $b }}" @selected($batchNumber === $b)>{{ $b }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input" type="checkbox" id="riExpiring" value="1" {{ $includeExpiring ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="riExpiring">
                                                {{ __('passwords.show_only_expiring_soon') }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-primary" id="riApplyBtn">
                                            <i class="bi bi-funnel me-1"></i>{{ __('pagination.apply_filters') }}
                                        </button>
                                        <button type="button" class="btn btn-light" id="riClearBtn">
                                            <i class="bi bi-x-lg me-1"></i>{{ __('pagination.clear_filters') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SKELETON --}}
                <div id="riSkeleton">
                    <div class="row g-6 mb-6">
                        @for($i = 0; $i < 4; $i++)
                        <div class="col-md-6 col-lg-3"><div class="card card-flush h-100"><div class="card-body">
                            <div class="d-flex align-items-center gap-3">
                                <div class="ri-sk ri-sk-circle"></div>
                                <div class="flex-grow-1">
                                    <div class="ri-sk ri-sk-text mb-2" style="width:60%;"></div>
                                    <div class="ri-sk ri-sk-text" style="width:40%;height:24px;"></div>
                                </div>
                            </div>
                        </div></div></div>
                        @endfor
                    </div>
                    <div class="row"><div class="col-12"><div class="card"><div class="card-body">
                        @for($i = 0; $i < 10; $i++)
                        <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                            <div class="ri-sk ri-sk-text" style="width:40px;"></div>
                            <div class="flex-grow-1">
                                <div class="ri-sk ri-sk-text mb-2" style="width:35%;"></div>
                                <div class="ri-sk ri-sk-text" style="width:55%;height:8px;"></div>
                            </div>
                            <div class="ri-sk ri-sk-badge"></div>
                            <div class="ri-sk ri-sk-text" style="width:100px;"></div>
                        </div>
                        @endfor
                    </div></div></div></div>
                </div>

                {{-- CONTENT --}}
                <div id="riContent" class="d-none">

                    {{-- Summary cards --}}
                    <div class="row g-6 mb-6" id="riSummaryCards"></div>

                    {{-- Batch analysis --}}
                    <div class="row g-6 mb-6" id="riBatchWrap">
                        <div class="col-12"><div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-layers fs-2 me-2 text-primary"></i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.batch_analysis') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7" id="riBatchBadge"></span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                    <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="ps-4">{{ __('passwords.batch_number') }}</th>
                                        <th>{{ __('passwords.expiry_date') }}</th>
                                        <th>{{ __('passwords.days_to_expiry') }}</th>
                                        <th>{{ __('passwords.total_quantity') }}</th>
                                        <th>{{ __('passwords.total_value') }}</th>
                                        <th class="pe-4">{{ __('passwords.status') }}</th>
                                    </tr></thead>
                                    <tbody id="riBatchBody"></tbody>
                                </table></div>
                            </div>
                        </div></div>
                    </div>

                    {{-- Items table --}}
                    <div class="card">
                        <div class="card-header border-0">
                            <div class="card-title d-flex align-items-center justify-content-between w-100">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-table fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('passwords.received_inventory') }}</h3>
                                </div>
                                <span class="badge badge-light-primary fs-7" id="riBadge"></span>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive"><table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead><tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">#</th>
                                    <th>{{ __('passwords.po_number') }}</th>
                                    <th>{{ __('passwords.product') }}</th>
                                    <th>{{ __('passwords.supplier') }}</th>
                                    <th class="text-end">{{ __('passwords.quantity_received') }}</th>
                                    <th class="text-end">{{ __('passwords.unit_cost') }}</th>
                                    <th class="text-end">{{ __('passwords.total_cost') }}</th>
                                    <th>{{ __('passwords.batch_number') }}</th>
                                    <th>{{ __('passwords.expiry_date') }}</th>
                                    <th>{{ __('passwords.received_by') }}</th>
                                    <th>{{ __('passwords.received_date') }}</th>
                                    <th class="text-end pe-4">{{ __('passwords.expiry_status') }}</th>
                                </tr></thead>
                                <tbody id="riTableBody"></tbody>
                            </table></div>
                            <div id="riEmpty" class="text-center py-10 d-none">
                                <i class="bi bi-inbox fs-4tx text-gray-400 mb-4"></i>
                                <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_received_items') }}</h4>
                                <p class="text-muted fs-6">{{ __('passwords.no_received_inventory_for_period') }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center d-none" id="riPaginationWrap">
                                <span class="text-muted fs-7" id="riPaginationInfo"></span>
                                <nav><ul class="pagination m-0" id="riPagination"></ul></nav>
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
    .ri-sk {
        background: linear-gradient(90deg, #e4e6eb 0%, #f0f2f5 50%, #e4e6eb 100%);
        background-size: 200% 100%;
        animation: ri-shimmer 1.4s ease-in-out infinite;
        border-radius: 6px;
    }
    .ri-sk-text   { height: 12px; border-radius: 4px; }
    .ri-sk-circle { width: 44px; height: 44px; border-radius: 50%; }
    .ri-sk-badge  { width: 56px; height: 24px; border-radius: 12px; }
    @keyframes ri-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    #riSkeleton .card { border: none; box-shadow: 0 0 0 1px #f0f2f5; }
</style>
@endpush

@push('scripts')
<script>
    window.RI_ROUTES = { data: '{{ route("reports.purchasing.received-inventory-data") }}' };
    window.REPORT_CURRENCY = '{{ currency_symbol() }}';
    window.RI_BOOTSTRAP = {
        startDate:        @json($startDate),
        endDate:          @json($endDate),
        supplierId:       @json($supplierId),
        variantId:        @json($productVariantId),
        batchNumber:      @json($batchNumber),
        includeExpiring:  @json($includeExpiring),
        perPage:          @json($perPage),
    };
</script>
@include('reports.purchasing.received-inventory-scripts')
@endpush