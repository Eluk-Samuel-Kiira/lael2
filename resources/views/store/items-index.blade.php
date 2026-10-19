<x-app-layout>
    @section('title', __('pagination._inventory_item'))
    @section('content')

    @unless(tenant_is_single_shop(auth()->user()->tenant_id))

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('pagination.products_table') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url()->previous() }}" class="text-muted text-hover-primary">
                            {{ __('auth._back') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('pagination.product_index') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-3">
                {{-- Search --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 220px; max-width: 280px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text"
                               id="inventorySearchInput"
                               class="form-control form-control-solid w-100 pe-12"
                               placeholder="{{ __('auth._search') }} {{ __('pagination._products') }}"
                               value="{{ $initialFilters['search'] }}" />
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute end-0 me-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                </div>

                {{-- Location --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 180px; max-width: 220px;">
                    <select id="inventoryLocationFilter" class="form-select form-select-solid w-100">
                        <option value="">{{ __('pagination._location') }} — {{ __('pagination.all') ?? 'All' }}</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}" @selected((int) $initialFilters['location_id'] === (int) $loc->id)>
                                {{ $loc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Department --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 180px; max-width: 220px;">
                    <select id="inventoryDepartmentFilter" class="form-select form-select-solid w-100">
                        <option value="">{{ __('auth._department') }} — {{ __('pagination.all') ?? 'All' }}</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}"
                                    data-location="{{ $dept->location_id }}"
                                    @selected((int) $initialFilters['department_id'] === (int) $dept->id)>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @can('create inventory record')
                <button type="button" class="btn btn-sm btn-light-primary"
                        data-bs-toggle="modal" data-bs-target="#kt_modal_batch_add_inventory">
                    <i class="ki-duotone ki-copy fs-2 me-1"></i>
                    <span class="d-none d-sm-inline">{{ __('pagination.bulk_add_inventory') }}</span>
                    <span class="d-inline d-sm-none">{{ __('auth._bulk') }}</span>
                </button>

                <button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="modal" data-bs-target="#kt_modal_add_inventory">
                    <i class="ki-duotone ki-plus fs-2 me-1"></i>
                    <span class="d-none d-sm-inline">{{ __('auth._create') }} {{ __('pagination.inventory_item') }}</span>
                    <span class="d-inline d-sm-none">{{ __('auth._add') }}</span>
                </button>
                @endcan

                @include('store.inventory-items.create')
                @include('store.inventory-items.batch-create')
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">

                {{-- Summary cards --}}
                <div class="row g-5 g-xl-8 mb-5">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-primary">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('pagination.total') ?? 'Total records' }}</span>
                                <span class="fs-1 fw-bold text-primary" id="summaryTotal">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-success">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('pagination.quantity_allocated') }}</span>
                                <span class="fs-1 fw-bold text-success" id="summaryAllocated">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-info">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('pagination.preferred_stock_level') }}</span>
                                <span class="fs-1 fw-bold text-info" id="summaryPreferred">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-warning">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('pagination.below_preferred') ?? 'Below preferred' }}</span>
                                <span class="fs-1 fw-bold text-warning" id="summaryBelow">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table card --}}
                <div class="card card-flush">
                    <div class="card-header align-items-center py-5">
                        <h3 class="card-title fw-bold">
                            <span class="text-gray-800">{{ __('pagination.products_table') }}</span>
                        </h3>
                        <div class="card-toolbar">
                            <select id="inventoryPerPage" class="form-select form-select-solid form-select-sm w-75px">
                                @foreach ([15, 25, 50, 100] as $pp)
                                    <option value="{{ $pp }}" @selected($initialFilters['per_page'] === $pp)>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body pt-0">
                        {{-- Skeleton --}}
                        <div id="inventoryLoadingSpinner" class="d-none">
                            @for ($i = 0; $i < 6; $i++)
                                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                    <div class="skeleton skeleton-text" style="width:18px;"></div>
                                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                                    <div class="flex-grow-1">
                                        <div class="skeleton skeleton-text" style="width:180px;"></div>
                                        <div class="skeleton skeleton-text" style="width:120px;"></div>
                                    </div>
                                    <div class="skeleton skeleton-text" style="width:80px;"></div>
                                    <div class="skeleton skeleton-text" style="width:80px;"></div>
                                </div>
                            @endfor
                        </div>

                        {{-- Table --}}
                        <div id="inventoryTableContainer" class="d-none">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed table-row-gray-300 fs-6 gy-5 mb-0">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-125px">{{ __('pagination._sku') }}</th>
                                            <th class="min-w-150px">{{ __('auth._name') }}</th>
                                            <th class="min-w-120px">{{ __('auth._department') }}</th>
                                            <th class="min-w-120px">{{ __('pagination._location') }}</th>
                                            <th class="min-w-100px">{{ __('pagination.overall_quantity') }}</th>
                                            <th class="min-w-100px">{{ __('pagination.quantity_allocated') }}</th>
                                            <th class="min-w-100px">{{ __('pagination.preferred_stock_level') }}</th>
                                            <th class="min-w-100px">{{ __('pagination.expiry_date') }}</th>
                                            <th class="min-w-120px">{{ __('auth._creater') }}</th>
                                            <th class="min-w-140px">{{ __('auth.created_at') }}</th>
                                            <th class="min-w-80px text-end">{{ __('auth._actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="inventoryTableBody"></tbody>
                                </table>
                            </div>
                            <div id="inventoryPaginationContainer"
                                 class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 d-none">
                                <div id="inventoryPaginationInfo" class="text-muted fs-7"></div>
                                <nav><ul class="pagination pagination-outline m-0" id="inventoryPagination"></ul></nav>
                            </div>
                        </div>

                        {{-- Empty --}}
                        <div id="inventoryNoDataMessage" class="text-center py-15 d-none">
                            <i class="ki-duotone ki-box-seam fs-5tx text-muted mb-4 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <h4 class="fw-bold text-gray-800 mb-2">{{ __('pagination.no_items_found') }}</h4>
                            <p class="text-muted mb-5">{{ __('passwords.try_adjusting_filters') ?? 'Try adjusting your filters.' }}</p>
                            <button type="button" class="btn btn-sm btn-primary"
                                    onclick="window.InventoryList?.resetFilters()">
                                {{ __('passwords.reset_filters') ?? 'Reset filters' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @include('store.inventory-items.drawer')
            @include('store.inventory-items.create-scripts') 
        </div>
    </div>

    @else
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle me-2"></i>
        <strong>{{ __('auth.single_shop_plan') }}:</strong>
        {{ __('auth.upgrade_for_multiple_shops') }}
        <a href="/" class="btn btn-sm btn-outline-primary ms-2">
            {{ __('auth.upgrade_plan') }}
        </a>
    </div>
    @endunless

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
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Whole row is the click target for the drawer */
        .inventory-row { cursor: pointer; }
        .inventory-row:hover { background-color: #f9f9f9; }

        /* Let clicks on any cell child bubble up to the <tr>.
        Excludes the trailing action cell so its button stays clickable. */
        .inventory-row td:not(:last-child) > * {
            pointer-events: none;
        }

        #submitButton,
        [data-loading-btn] {
            transition: min-width 0.25s ease, opacity 0.15s ease;
            white-space: nowrap;
        }

        /* While loading the button is disabled — keep it visually intact */
        #submitButton:disabled,
        [data-loading-btn]:disabled {
            opacity: 0.85;
            cursor: progress;
        }
    </style>
    @endpush

    @push('scripts')
        @include('store.inventory-items.list-scripts')
        @include('store.inventory-items.drawer-scripts')
    @endpush
</x-app-layout>