<x-app-layout>
    @section('title', __('passwords.purchase_order'))
    @section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('passwords.po_table') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url()->previous() }}" class="text-muted text-hover-primary">{{ __('auth._back') }}</a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('passwords.purchase_order') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3">
                {{-- Search --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 220px; max-width: 280px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text" id="poSearchInput" class="form-control form-control-solid w-100 pe-12"
                               placeholder="{{ __('auth._search') }} PO..." value="{{ $initialFilters['search'] ?? '' }}" />
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute end-0 me-4 top-50 translate-middle-y"></i>
                    </div>
                </div>

                {{-- Status Filter --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 160px; max-width: 200px;">
                    <select id="poStatusFilter" class="form-select form-select-solid w-100" data-control="select2" data-placeholder="{{ __('auth._status') }} — {{ __('pagination.all') ?? 'All' }}" data-hide-search="true">
                        <option value=""></option>
                        <option value="draft">Draft</option>
                        <option value="pending_approval">Pending Approval</option>
                        <option value="approved">Approved</option>
                        <option value="sent">Sent</option>
                        <option value="partially_received">Partially Received</option>
                        <option value="received">Received</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                {{-- Supplier Filter --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 180px; max-width: 220px;">
                    <select id="poSupplierFilter" class="form-select form-select-solid w-100" data-control="select2" data-placeholder="{{ __('passwords.supplier') }} — {{ __('pagination.all') ?? 'All' }}">
                        <option value=""></option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Location Filter --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 180px; max-width: 220px;">
                    <select id="poLocationFilter" class="form-select form-select-solid w-100" data-control="select2" data-placeholder="{{ __('passwords.location') }} — {{ __('pagination.all') ?? 'All' }}">
                        <option value=""></option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear Filters Button --}}
                <button type="button" class="btn btn-sm btn-light" id="poClearFiltersBtn">
                    <i class="ki-duotone ki-arrow-rotate-left fs-2 me-1"></i>
                    <span class="d-none d-sm-inline">{{ __('accounting.reset') ?? 'Clear' }}</span>
                </button>

                @can('create purchase_orders')
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_purchase_order">
                    <i class="ki-duotone ki-plus fs-2 me-1"></i>
                    <span class="d-none d-sm-inline">{{ __('passwords.new_purchase_order') }}</span>
                </button>
                @endcan

                @include('procurement.purchase-order.create')
            </div>
            
        </div>
    </div>

    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">

                {{-- Summary Cards --}}
                <div class="row g-5 g-xl-8 mb-5">
                    <div class="col-xl-6 col-md-6">
                        <div class="card card-flush bg-light-primary">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('pagination.total') ?? 'Total Orders' }}</span>
                                <span class="fs-1 fw-bold text-primary" id="summaryTotal">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-6 col-md-6">
                        <div class="card card-flush bg-light-success">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('pagination.total_value') ?? 'Total Value' }}</span>
                                <span class="fs-1 fw-bold text-success" id="summaryValue">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table Card --}}
                <div class="card card-flush">
                    <div class="card-header align-items-center py-5">
                        <h3 class="card-title fw-bold"><span class="text-gray-800">{{ __('passwords.po_table') }}</span></h3>
                        <div class="card-toolbar">
                            <select id="poPerPage" class="form-select form-select-solid form-select-sm w-75px">
                                @foreach ([15, 25, 50, 100] as $pp)
                                    <option value="{{ $pp }}" @selected(($initialFilters['per_page'] ?? 15) === $pp)>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body pt-0">
                        <div id="poLoadingSpinner" class="d-none">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                                    <div class="flex-grow-1"><div class="skeleton skeleton-text" style="width:180px;"></div></div>
                                    <div class="skeleton skeleton-text" style="width:80px;"></div>
                                </div>
                            @endfor
                        </div>

                        <div id="poTableContainer" class="d-none">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed table-row-gray-300 fs-6 gy-5 mb-0">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-125px">{{ __('passwords.po_number') }}</th>
                                            <th class="min-w-120px">{{ __('passwords.supplier') }}</th>
                                            <th class="min-w-120px">{{ __('passwords.location') }}</th>
                                            <th class="min-w-100px">{{ __('auth._status') }}</th>
                                            <th class="min-w-100px">{{ __('pagination._total') }}</th>
                                            <th class="min-w-120px">{{ __('passwords.expected_delivery') }}</th>
                                            <th class="min-w-120px">{{ __('auth._creater') }}</th>
                                            <th class="min-w-80px text-end">{{ __('auth._actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="poTableBody"></tbody>
                                </table>
                            </div>
                            <div id="poPaginationContainer" class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 d-none">
                                <div id="poPaginationInfo" class="text-muted fs-7"></div>
                                <nav><ul class="pagination pagination-outline m-0" id="poPagination"></ul></nav>
                            </div>
                        </div>

                        <div id="poNoDataMessage" class="text-center py-15 d-none">
                            <i class="ki-duotone ki-box-seam fs-5tx text-muted mb-4 d-block"></i>
                            <h4 class="fw-bold text-gray-800 mb-2">{{ __('pagination.no_items_found') }}</h4>
                            <button type="button" class="btn btn-sm btn-primary" onclick="window.POList?.resetFilters()">{{ __('passwords.reset_filters') ?? 'Reset filters' }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="poModalsContainer" style="display:none;"></div>
    @include('procurement.purchase-order.drawer')

    @endsection

    @push('styles')
    <style>
        .skeleton { display: inline-block; background: linear-gradient(90deg, #f1f1f4 0%, #e9e9ed 50%, #f1f1f4 100%); background-size: 200% 100%; animation: skeleton-shimmer 1.4s ease-in-out infinite; border-radius: 6px; }
        .skeleton-text { height: 12px; margin: 4px 0; display: block; }
        @keyframes skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .po-row { cursor: pointer; }
        .po-row:hover { background-color: #f9f9f9; }
        .po-row td:not(:last-child) > * { pointer-events: none; }
    </style>
    @endpush

    @push('scripts')
        @include('procurement.purchase-order.list-scripts')
        @include('procurement.purchase-order.drawer-scripts')
        @include('procurement.purchase-order.create-scripts')
        @include('procurement.purchase-order.modal-scripts')
    @endpush
</x-app-layout>