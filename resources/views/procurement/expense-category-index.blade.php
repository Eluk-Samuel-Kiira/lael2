<x-app-layout>
    @section('title', __('passwords.expense_cat_index'))
    @section('content')

    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 w-100">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">
                    {{ __('passwords.expense_category_table') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                            {{ __('pagination.dashboard') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-muted">{{ __('passwords.expense_category_table') }}</li>
                </ul>
            </div>

            <div class="d-flex align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                @can('create category')
                    <button type="button" class="btn btn-primary flex-shrink-0"
                            data-bs-toggle="modal" data-bs-target="#kt_modal_add_expense_category">
                        <i class="ki-duotone ki-plus fs-2 me-2"></i>
                        <span class="d-none d-sm-inline">{{ __('passwords.expense_category_new') }}</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    @if (tenant_can('expenses'))
    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            {{-- Stat cards --}}
            <div class="row g-4 mb-6">
                <div class="col-6 col-md-4">
                    <div class="card bg-light-primary">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Total Categories</span>
                            <span class="fs-2x fw-bold text-primary" id="statTotal">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card bg-light-success">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Active</span>
                            <span class="fs-2x fw-bold text-success" id="statActive">—</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card bg-light-warning">
                        <div class="card-body py-4">
                            <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Inactive</span>
                            <span class="fs-2x fw-bold text-warning" id="statInactive">—</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-6">
                <div class="card-body py-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold fs-7">{{ __('auth._search') }}</label>
                            <div class="position-relative">
                                <input type="text" id="expCatSearchTop" class="form-control form-control-solid pe-10"
                                       placeholder="{{ __('auth._search') }}…">
                                <i class="bi bi-search fs-4 position-absolute end-0 me-3 top-50 translate-middle-y text-muted"></i>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold fs-7">{{ __('auth._status') }}</label>
                            <select id="expCatStatusFilter" class="form-select form-select-solid">
                                <option value="">All statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold fs-7">Per page</label>
                            <select id="expCatPerPage" class="form-select form-select-solid">
                                <option value="15">15 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card card-flush">
                <div class="card-body pt-0">
                    <div id="expCatLoading" class="text-center py-10 d-none">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <div id="expCatWrapper" class="d-none">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                        <th class="min-w-40px">ID</th>
                                        <th class="min-w-150px">Name</th>
                                        <th class="min-w-100px">Code</th>
                                        <th class="min-w-120px">Created</th>
                                        <th class="min-w-100px">Status</th>
                                        <th class="min-w-40px text-end"></th>
                                    </tr>
                                </thead>
                                <tbody id="expCatTableBody"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted fs-7" id="expCatPageInfo"></div>
                            <ul class="pagination m-0" id="expCatPagination"></ul>
                        </div>
                    </div>

                    <div id="expCatEmpty" class="text-center py-15 d-none">
                        <i class="bi bi-file-earmark fs-5tx text-muted mb-4 d-block"></i>
                        <h4 class="fw-bold text-gray-800 mb-2">No categories found</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @include('procurement.expense-category.create-category')
    @include('procurement.expense-category.edit-category')
    @include('procurement.expense-category.drawer')
    @endsection

    @push('scripts')
    <script>
        window.EXP_CAT_ROUTES = {
            data:        '{{ route('expense-category.data') }}',
            drawer_data: '{{ route('expense-category.drawer-data', ['id' => '__ID__']) }}',
            status:      '{{ route('expense-category.status', ['id' => '__ID__']) }}',
            update:      '{{ route('expense-category.update', ['expense_category' => '__ID__']) }}',
            destroy:     '{{ route('expense-category.destroy', ['expense_category' => '__ID__']) }}',
        };
        window.EXP_CAT_BOOTSTRAP = {
            search:  @json($initialFilters['search'] ?? ''),
            perPage: @json($initialFilters['per_page'] ?? 15),
        };
    </script>
    @include('procurement.expense-category.list-scripts')
    @include('procurement.expense-category.drawer-scripts')
    @endpush
</x-app-layout>