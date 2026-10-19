<x-app-layout>
    @section('title', __('passwords.order_index'))
    @section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container"
             class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('passwords.order_table') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url()->previous() }}" class="text-muted text-hover-primary">{{ __('auth._back') }}</a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('passwords.orders') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto flex-wrap">
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 220px; max-width: 280px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text" id="orderSearchInput"
                               class="form-control form-control-solid w-100 pe-12"
                               placeholder="{{ __('auth._search') }} {{ __('passwords.orders') }}"
                               value="{{ $initialFilters['search'] }}" />
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute end-0 me-4 top-50 translate-middle-y">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                    </div>
                </div>

                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 160px; max-width: 200px;">
                    <select id="orderStatusFilter" class="form-select form-select-solid w-100">
                        <option value="">{{ __('pagination.all_statuses') }}</option>
                        @foreach(['pending','confirmed','processing','completed','cancelled','refunded','draft'] as $st)
                            <option value="{{ $st }}" @selected($initialFilters['status'] === $st)>{{ ucwords($st) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 150px; max-width: 190px;">
                    <select id="orderSourceFilter" class="form-select form-select-solid w-100">
                        <option value="">{{ __('passwords.all_sources') ?? 'All sources' }}</option>
                        @foreach(['pos','manual','online','mobile','walk_in'] as $src)
                            <option value="{{ $src }}" @selected($initialFilters['source'] === $src)>{{ ucwords(str_replace('_',' ',$src)) }}</option>
                        @endforeach
                    </select>
                </div>

                @if(isset($locations) && $locations->count() > 0)
                    <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 180px; max-width: 220px;">
                        <select id="orderLocationFilter" class="form-select form-select-solid w-100">
                            <option value="">{{ __('passwords.all_locations') }}</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" @selected((int) $initialFilters['location_id'] === (int) $loc->id)>
                                    {{ $loc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="d-flex align-items-center gap-2 flex-grow-1 flex-sm-grow-0">
                    <input type="date" id="orderDateFrom" class="form-control form-control-solid"
                           value="{{ $initialFilters['date_from'] }}" title="{{ __('passwords.from') ?? 'From' }}" />
                    <span class="text-muted fs-8">—</span>
                    <input type="date" id="orderDateTo" class="form-control form-control-solid"
                           value="{{ $initialFilters['date_to'] }}" title="{{ __('passwords.to') ?? 'To' }}" />
                </div>
            </div>
        </div>
    </div>

    @if (tenant_can('pos'))
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">

                {{-- Summary cards --}}
                <div class="row g-5 g-xl-8 mb-5">
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-primary">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('passwords.total') ?? 'Total orders' }}</span>
                                <span class="fs-1 fw-bold text-primary" id="summaryTotal">—</span>
                                <small class="text-muted d-block mt-1" id="summaryBreakdown"></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-success">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('passwords.total_value') ?? 'Order value' }}</span>
                                <span class="fs-1 fw-bold text-success" id="summaryValue">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-info">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('passwords.paid') }}</span>
                                <span class="fs-1 fw-bold text-info" id="summaryPaid">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-flush bg-light-danger">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('passwords.balance_due') }}</span>
                                <span class="fs-1 fw-bold text-danger" id="summaryBalance">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="card card-flush">
                    <div class="card-header align-items-center py-5">
                        <h3 class="card-title fw-bold">
                            <span class="text-gray-800">{{ __('passwords.order_table') }}</span>
                        </h3>
                        <div class="card-toolbar">
                            <select id="orderPerPage" class="form-select form-select-solid form-select-sm w-75px">
                                @foreach([15, 25, 50, 100] as $pp)
                                    <option value="{{ $pp }}" @selected($initialFilters['per_page'] === $pp)>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body pt-0">
                        <div id="orderLoadingSpinner" class="d-none">
                            @for($i = 0; $i < 6; $i++)
                                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                    <div class="skeleton skeleton-text" style="width:140px;"></div>
                                    <div class="flex-grow-1">
                                        <div class="skeleton skeleton-text" style="width:180px;"></div>
                                        <div class="skeleton skeleton-text" style="width:120px;"></div>
                                    </div>
                                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                                    <div class="skeleton skeleton-text" style="width:100px;"></div>
                                    <div class="skeleton skeleton-text" style="width:80px;"></div>
                                </div>
                            @endfor
                        </div>

                        <div id="orderTableContainer" class="d-none">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed table-row-gray-300 fs-6 gy-5 mb-0">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-125px">{{ __('passwords.order_number') }}</th>
                                            <th class="min-w-150px">{{ __('passwords._customer') }}</th>
                                            <th class="min-w-140px">{{ __('passwords.location') }}</th>
                                            <th class="min-w-120px">{{ __('pagination.created_by') }}</th>
                                            <th class="min-w-120px">{{ __('passwords._status') }}</th>
                                            <th class="min-w-100px">{{ __('passwords._source') }}</th>
                                            <th class="min-w-120px">{{ __('passwords._amount') }}</th>
                                            <th class="min-w-100px text-end">{{ __('auth._actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="orderTableBody"></tbody>
                                </table>
                            </div>
                            <div id="orderPaginationContainer"
                                 class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 d-none">
                                <div id="orderPaginationInfo" class="text-muted fs-7"></div>
                                <nav><ul class="pagination pagination-outline m-0" id="orderPagination"></ul></nav>
                            </div>
                        </div>

                        <div id="orderNoDataMessage" class="text-center py-15 d-none">
                            <i class="ki-duotone ki-search-list fs-5tx text-muted mb-4 d-block">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <h4 class="fw-bold text-gray-800 mb-2">{{ __('passwords.no_orders') }}</h4>
                            <p class="text-muted mb-5">{{ __('passwords.try_adjusting_filters') ?? 'Try adjusting your filters.' }}</p>
                            <button type="button" class="btn btn-sm btn-primary"
                                    onclick="window.OrderList?.resetFilters()">
                                {{ __('passwords.reset_filters') ?? 'Reset filters' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('orders.order.drawer')

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
        .order-number-link { cursor: pointer; }
        .order-number-link:hover { text-decoration: underline; }
        #orderDrawerFooter { background: #fff; box-shadow: 0 -1px 0 rgba(0,0,0,0.05); }
        #orderDrawerActions { overflow-x: auto; min-width: 0; scrollbar-width: thin; }
    </style>
    @endpush

@push('scripts')

    @include('orders.pos.payment-mode')
    @include('orders.order.list-scripts')
    @include('orders.order.drawer-scripts')
@endpush
</x-app-layout>