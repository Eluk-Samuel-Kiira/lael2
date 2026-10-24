<x-app-layout>
    @section('title', __('pagination._category'))
    @section('content')

    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container"
             class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0 w-100">
            <div class="page-title d-flex flex-column align-items-start text-start me-auto">
                <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                    {{ __('pagination._category_table') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ url()->previous() }}" class="text-muted text-hover-primary">
                            {{ __('auth._back') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('pagination._category_table') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto flex-wrap ms-auto">
                {{-- Search --}}
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 240px; max-width: 320px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text"
                               id="categorySearchInput"
                               class="form-control form-control-solid w-100 pe-12"
                               placeholder="{{ __('auth._search') }} {{ __('pagination._category') }}"
                               value="{{ $initialFilters['search'] }}" />
                        <i class="bi bi-search fs-3 position-absolute end-0 me-4 top-50 translate-middle-y text-muted"></i>
                    </div>
                </div>

                @can('create category')
                <button type="button" class="btn btn-primary flex-shrink-0" data-bs-toggle="modal" data-bs-target="#kt_modal_add_category">
                    <i class="bi bi-plus-lg fs-2 me-2 me-sm-3"></i>
                    <span class="d-none d-sm-inline">{{ __('pagination._category_new') }}</span>
                    <span class="d-inline d-sm-none">{{ __('auth._add') }}</span>
                </button>
                @endcan
            </div>
        </div>
    </div>

    @if (tenant_can('product_catalog'))
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">

                {{-- Summary cards --}}
                <div class="row g-5 g-xl-8 mb-5">
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-flush bg-light-primary">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('pagination.total') }}</span>
                                <span class="fs-1 fw-bold text-primary" id="categorySummaryTotal">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-flush bg-light-success">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('pagination.active') }}</span>
                                <span class="fs-1 fw-bold text-success" id="categorySummaryActive">—</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-flush bg-light-warning">
                            <div class="card-body py-5">
                                <span class="text-muted fw-semibold d-block">{{ __('pagination.inactive') }}</span>
                                <span class="fs-1 fw-bold text-warning" id="categorySummaryInactive">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="card card-flush">
                    <div class="card-header align-items-center py-5">
                        <h3 class="card-title fw-bold">
                            <span class="text-gray-800">{{ __('pagination._category_table') }}</span>
                        </h3>
                        <div class="card-toolbar">
                            <select id="categoryPerPage" class="form-select form-select-solid form-select-sm w-75px">
                                @foreach([15, 25, 50, 100] as $pp)
                                    <option value="{{ $pp }}" @selected($initialFilters['per_page'] === $pp)>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="card-body pt-0">
                        {{-- Skeleton --}}
                        <div id="categoryLoadingSpinner" class="d-none">
                            @for($i = 0; $i < 6; $i++)
                                <div class="d-flex align-items-center gap-4 py-4 border-bottom">
                                    <div class="cat-sk cat-sk-text" style="width:18px;"></div>
                                    <div class="cat-sk cat-sk-text" style="width:140px;"></div>
                                    <div class="flex-grow-1">
                                        <div class="cat-sk cat-sk-text" style="width:180px;"></div>
                                        <div class="cat-sk cat-sk-text" style="width:120px;"></div>
                                    </div>
                                    <div class="cat-sk cat-sk-text" style="width:100px;"></div>
                                    <div class="cat-sk cat-sk-text" style="width:80px;"></div>
                                </div>
                            @endfor
                        </div>

                        {{-- Table --}}
                        <div id="categoryTableContainer" class="d-none">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed table-row-gray-300 fs-6 gy-5 mb-0">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th class="min-w-125px">{{ __('pagination.category_id') }}</th>
                                            <th class="min-w-150px">{{ __('auth._name') }}</th>
                                            <th class="min-w-150px">{{ __('auth._creater') }}</th>
                                            <th class="min-w-140px">{{ __('auth.created_at') }}</th>
                                            <th class="min-w-140px">{{ __('auth._status') }}</th>
                                            <!-- <th class="min-w-100px text-end">{{ __('auth._actions') }}</th> -->
                                        </tr>
                                    </thead>
                                    <tbody id="categoryTableBody"></tbody>
                                </table>
                            </div>

                            <div id="categoryPaginationContainer"
                                 class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5 d-none">
                                <div id="categoryPaginationInfo" class="text-muted fs-7"></div>
                                <nav><ul class="pagination pagination-outline m-0" id="categoryPagination"></ul></nav>
                            </div>
                        </div>

                        {{-- Empty --}}
                        <div id="categoryNoDataMessage" class="text-center py-15 d-none">
                            <i class="bi bi-file-earmark fs-5tx text-muted mb-4 d-block"></i>
                            <h4 class="fw-bold text-gray-800 mb-2">{{ __('pagination.no_categories_found') }}</h4>
                            <p class="text-muted mb-5">{{ __('pagination.no_categories_message') }}</p>
                            <button type="button" class="btn btn-sm btn-primary"
                                    onclick="window.CategoryList?.resetFilters()">
                                {{ __('pagination.reset_filters') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('inventory.category.create-category')  
    @include('inventory.category.edit-category')  
    @include('inventory.category.drawer')           

    @endsection

    @push('styles')
    <style>
        .cat-sk {
            display: inline-block;
            background: linear-gradient(90deg, #f1f1f4 0%, #e9e9ed 50%, #f1f1f4 100%);
            background-size: 200% 100%;
            animation: cat-shimmer 1.4s ease-in-out infinite;
            border-radius: 6px;
        }
        .cat-sk-text { height: 12px; margin: 4px 0; display: block; }
        @keyframes cat-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .category-name-link { cursor: pointer; }
        .category-name-link:hover { text-decoration: underline; }
    </style>
    @endpush

    @push('scripts')
        <script>
            window.CATEGORY_ROUTES = {
                data:   '{{ route('category.data') }}',
                show:   '{{ route('category.show',    ['category' => '__ID__']) }}',
                status: '{{ route('category.status',  ['id'       => '__ID__']) }}',  
                update: '{{ route('category.update',  ['category' => '__ID__']) }}',
                destroy:'{{ route('category.destroy', ['category' => '__ID__']) }}',
            };
            window.CATEGORY_BOOTSTRAP = {
                search:  @json($initialFilters['search']),
                perPage: @json($initialFilters['per_page']),
            };
            window.REPORT_CURRENCY = '{{ currency_symbol() }}';
        </script>
        @include('inventory.category.list-scripts')
        @include('inventory.category.drawer-scripts')
    @endpush
</x-app-layout>