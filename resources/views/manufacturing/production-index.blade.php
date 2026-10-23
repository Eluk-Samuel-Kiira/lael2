<x-app-layout>
    @section('title', __('passwords.production_orders'))
    @section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('passwords.production_orders') }}
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
                    <li class="breadcrumb-item text-muted">{{ __('passwords.production_orders') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto flex-wrap">

                {{-- Search --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 240px; max-width: 300px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text"
                            id="productionSearchInput"
                            class="form-control form-control-solid w-100 pe-12"
                            placeholder="{{ __('auth._search') }} {{ __('passwords.production_orders') }}"
                            value="{{ $initialFilters['search'] }}" />
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute end-0 me-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                </div>

                {{-- Location filter --}}
                @if($locations->count() > 0)
                    <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 200px; max-width: 240px;">
                        <select id="productionLocationFilter"
                                class="form-select form-select-solid w-100">
                            <option value="">{{ __('passwords.all_locations') }}</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}"
                                        @selected((int) $initialFilters['location_id'] === (int) $loc->id)>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Status filter --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 180px; max-width: 220px;">
                    <select id="productionStatusFilter" class="form-select form-select-solid w-100">
                        <option value="all" @selected($initialFilters['status'] === 'all')>
                            {{ __('pagination.all_statuses') }}
                        </option>
                        <option value="draft"        @selected($initialFilters['status'] === 'draft')>{{ __('pagination.draft') }}</option>
                        <option value="in_progress"  @selected($initialFilters['status'] === 'in_progress')>{{ __('pagination.in_progress') }}</option>
                        <option value="completed"    @selected($initialFilters['status'] === 'completed')>{{ __('pagination.completed') }}</option>
                        <option value="cancelled"    @selected($initialFilters['status'] === 'cancelled')>{{ __('pagination.cancelled') }}</option>
                    </select>
                </div>

                @can('create production_orders')
                    <button type="button" class="btn btn-primary flex-shrink-0"
                            data-bs-toggle="modal"
                            data-bs-target="#kt_modal_add_production_order">
                        <i class="ki-duotone ki-plus fs-2 me-2 me-sm-3"></i>
                        <span class="d-none d-sm-inline">{{ __('passwords.new_production_order') }}</span>
                        <span class="d-inline d-sm-none">{{ __('auth._add') }}</span>
                    </button>
                @endcan

                @include('manufacturing.production-order.create')
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">

                {{-- ─── SUMMARY CARDS ──────────────────────────────── --}}
                <div class="row g-5 g-xl-8 mb-5">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-primary">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('passwords.total') }}</span>
                                <span class="fs-1 fw-bold text-primary" id="summaryTotal">—</span>
                                <small class="text-muted d-block mt-1" id="summaryBreakdown"></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-success">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('passwords.total_cost') }}</span>
                                <span class="fs-1 fw-bold text-success" id="summaryCost">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-warning">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('passwords.inputs') }}</span>
                                <span class="fs-1 fw-bold text-warning" id="summaryInput">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-info">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('passwords.outputs') }}</span>
                                <span class="fs-1 fw-bold text-info" id="summaryOutput">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ─── TABLE CARD ─────────────────────────────────── --}}
                <div class="card card-flush">
                    <div class="card-header align-items-center py-5">
                        <h3 class="card-title fw-bold">
                            <span class="text-gray-800">{{ __('passwords.production_orders') }}</span>
                        </h3>
                        <div class="card-toolbar">
                            <select id="productionPerPage" class="form-select form-select-solid form-select-sm w-75px">
                                @foreach([15, 25, 50, 100] as $pp)
                                    <option value="{{ $pp }}" @selected($initialFilters['per_page'] === $pp)>
                                        {{ $pp }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body pt-0">

                        {{-- Skeleton --}}
                        <div id="productionLoadingSpinner" class="d-none">
                            @for($i = 0; $i < 6; $i++)
                                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                    <div class="skeleton skeleton-text" style="width:18px;"></div>
                                    <div class="flex-grow-1">
                                        <div class="skeleton skeleton-text" style="width:180px;"></div>
                                        <div class="skeleton skeleton-text" style="width:120px;"></div>
                                    </div>
                                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                                    <div class="skeleton skeleton-text" style="width:80px;"></div>
                                    <div class="skeleton skeleton-text" style="width:60px;"></div>
                                </div>
                            @endfor
                        </div>

                        {{-- Table --}}
                        <div id="productionTableContainer" class="d-none">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed table-row-gray-300 fs-6 gy-5 mb-0">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th class="w-10px pe-2">
                                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                    <input class="form-check-input" type="checkbox" id="productionSelectAll" />
                                                </div>
                                            </th>
                                            <th class="min-w-150px">{{ __('passwords.production_number') }}</th>
                                            <th class="min-w-140px">{{ __('passwords.location') }}</th>
                                            <th class="min-w-120px">{{ __('auth._status') }}</th>
                                            <th class="min-w-140px">{{ __('passwords.inputs') }}</th>
                                            <th class="min-w-140px">{{ __('passwords.outputs') }}</th>
                                            <th class="min-w-120px">{{ __('passwords.total_cost') }}</th>
                                            <th class="min-w-120px">{{ __('passwords.scheduled_date') }}</th>
                                            <th class="min-w-120px">{{ __('auth._creater') }}</th>
                                            <th class="min-w-100px text-end">{{ __('auth._actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productionTableBody"></tbody>
                                </table>
                            </div>

                            <div id="productionPaginationContainer"
                                 class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 d-none">
                                <div id="productionPaginationInfo" class="text-muted fs-7"></div>
                                <nav>
                                    <ul class="pagination pagination-outline m-0" id="productionPagination"></ul>
                                </nav>
                            </div>
                        </div>

                        {{-- Empty --}}
                        <div id="productionNoDataMessage" class="text-center py-15 d-none">
                            <i class="ki-duotone ki-cubes fs-5tx text-muted mb-4 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <h4 class="fw-bold text-gray-800 mb-2">{{ __('passwords.no_records_found') }}</h4>
                            <p class="text-muted mb-5">{{ __('passwords.try_adjusting_filters') }}</p>
                            <button type="button" class="btn btn-sm btn-primary"
                                    onclick="window.ProductionPage.resetFilters()">
                                {{ __('passwords.reset_filters') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
    </style>
    @endpush

    @push('scripts')
        <script>
            window.__VARIANT_OPTIONS__ = `
                @foreach($variants as $variant)
                    <option value="{{ $variant->name }}"
                            data-id="{{ $variant->id }}"
                            data-uom-id="{{ $variant->weight_unit }}"
                            data-cost-price="{{ $variant->supplier_cost_price }}"
                            data-grand-total-cost="{{ $variant->grand_total_cost_price }}"
                            data-inventory-strategy="{{ $variant->product?->resolvedInventoryStrategy() ?? 'quantity' }}">
                    </option>
                @endforeach
            `;

            window.__VARIANT_OUTPUT_OPTIONS__ = `
                @foreach($variants as $variant)
                    <option value="{{ $variant->name }}"
                            data-id="{{ $variant->id }}"
                            data-uom-id="{{ $variant->weight_unit }}"
                            data-selling-price="{{ $variant->selling_price }}"
                            data-grand-total-cost="{{ $variant->grand_total_cost_price }}"
                            data-inventory-strategy="{{ $variant->product?->resolvedInventoryStrategy() ?? 'quantity' }}">
                    </option>
                @endforeach
            `;

            window.__UOM_OPTIONS__ = `{!! $uoms->map(fn($u) => sprintf(
                '<option value="%s">%s</option>',
                $u->id, e($u->name)
            ))->implode('') !!}`;
        </script>

        @include('manufacturing.production-order.list-scripts')
        @include('manufacturing.production-order.edit-modal-shared')
        @include('manufacturing.production-order.view-modal-shared')
        @include('manufacturing.production-order.start-modal-shared')
        @include('manufacturing.production-order.complete-modal-shared') 
        @include('manufacturing.production-order.edit-modal-scripts')
        @include('manufacturing.production-order.view-modal-scripts')  
        @include('manufacturing.production-order.start-modal-scripts')
        @include('manufacturing.production-order.complete-modal-scripts')
        
    @endpush
</x-app-layout>

            