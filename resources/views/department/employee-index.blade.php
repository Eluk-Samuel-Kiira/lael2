<x-app-layout>
    @section('title', __('pagination.employee'))
    @section('content')

    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">{{ __('pagination.employee') }}</h1>
            </div>
            <div class="d-flex align-items-stretch gap-3 w-100 w-md-auto">
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 240px; max-width: 320px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text" id="employeeSearchInput"
                               class="form-control form-control-solid w-100 pe-10"
                               placeholder="{{ __('auth._search') }} {{ __('pagination.employee') }}">
                        <i class="bi bi-search fs-4 position-absolute end-0 me-3 top-50 translate-middle-y text-muted"></i>
                    </div>
                </div>
                @can('edit employee')
                    <button type="button" class="btn btn-light-primary flex-shrink-0"
                            onclick="EmployeeList.syncUsers()">
                        <i class="ki-duotone ki-arrows-loop fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                        <span class="d-none d-sm-inline">{{ __('auth.sync_users') }}</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            <div class="row g-4 mb-6">
                <div class="col-6 col-md-4">
                    <div class="card bg-light-primary"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Total</span>
                        <span class="fs-2x fw-bold text-primary" id="statTotal">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card bg-light-success"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Active</span>
                        <span class="fs-2x fw-bold text-success" id="statActive">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="card bg-light-warning"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">Inactive</span>
                        <span class="fs-2x fw-bold text-warning" id="statInactive">—</span>
                    </div></div>
                </div>
            </div>

            <div class="card mb-6">
                <div class="card-body py-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">{{ __('auth._search') }}</label>
                            <div class="position-relative">
                                <input type="text" id="employeeSearchTop" class="form-control form-control-solid pe-10"
                                       placeholder="{{ __('auth._search') }}…">
                                <i class="bi bi-search fs-4 position-absolute end-0 me-3 top-50 translate-middle-y text-muted"></i>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('pagination.status') }}</label>
                            <select id="employeeStatusFilter" class="form-select form-select-solid">
                                <option value="">All</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">Per page</label>
                            <select id="employeePerPage" class="form-select form-select-solid">
                                <option value="15">15 / page</option>
                                <option value="25">25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush">
                <div class="card-body pt-0">
                    <div id="empLoading" class="text-center py-10 d-none"><div class="spinner-border text-primary"></div></div>
                    <div id="empWrapper" class="d-none">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-7 gy-2 gs-3 mb-0">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('pagination.employee') }}</th>
                                        <th class="min-w-120px">{{ __('pagination.department') }}</th>
                                        <th class="min-w-120px">{{ __('pagination.salary') }}</th>
                                        <th class="min-w-120px">{{ __('pagination.hire_date') }}</th>
                                        <th class="min-w-120px">{{ __('pagination.term_date') }}</th>
                                        <th class="min-w-100px">{{ __('pagination.status') }}</th>
                                        <th class="min-w-40px text-end"></th>
                                    </tr>
                                </thead>
                                <tbody id="empTableBody"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted fs-7" id="empPageInfo"></div>
                            <ul class="pagination m-0" id="empPagination"></ul>
                        </div>
                    </div>
                    <div id="empEmpty" class="text-center py-15 d-none">
                        <i class="bi bi-people fs-5tx text-muted mb-4 d-block"></i>
                        <h4 class="fw-bold text-gray-800 mb-2">{{ __('auth.no_employee_found') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('department.employee.employee-drawer')
    @include('department.employee.employee-edit-modal')
    @endsection

    @push('scripts')
    <script>
        window.EMPLOYEE_ROUTES = {
            data:         '{{ route('employee.data') }}',
            drawer_data:  '{{ route('employee.drawer-data', ['id' => '__ID__']) }}',
            status:       '{{ route('employee.status', ['id' => '__ID__']) }}',
            update:       '{{ route('employee.update', ['employee' => '__ID__']) }}',
            destroy:      '{{ route('employee.destroy', ['employee' => '__ID__']) }}',
            sync:         '{{ route('sync.users.to.employees') }}',
            doc_upload:   '{{ route('employee.documents.upload') }}',
            doc_delete:   '{{ route('employee.documents.delete') }}',
        };
        window.EMPLOYEE_BOOTSTRAP = {
            search:  @json($initialFilters['search'] ?? ''),
            perPage: @json($initialFilters['per_page'] ?? 15),
        };
    </script>
    @include('department.employee.employee-list-scripts')
    @include('department.employee.employee-drawer-scripts')
    @endpush

</x-app-layout>