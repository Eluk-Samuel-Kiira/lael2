<x-app-layout>
    @section('title', __('payments.employee_advance'))
    @section('content')

    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">{{ __('payments.advance_table') }}</h1>
            </div>
            <div class="d-flex align-items-stretch gap-3 w-100 w-md-auto">
                <div class="flex-grow-1 flex-sm-grow-0" style="min-width: 240px; max-width: 320px;">
                    <div class="d-flex align-items-center position-relative w-100">
                        <input type="text" id="advanceSearchInput"
                               class="form-control form-control-solid w-100 pe-10"
                               placeholder="{{ __('auth._search') }} {{ __('payments.employee_advance') }}">
                        <i class="bi bi-search fs-4 position-absolute end-0 me-3 top-50 translate-middle-y text-muted"></i>
                    </div>
                </div>
                @can('create employee advance')
                    <button type="button" class="btn btn-warning flex-shrink-0"
                            data-bs-toggle="modal" data-bs-target="#kt_modal_add_advance">
                        <i class="ki-duotone ki-hand-holding-usd fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                        <span class="d-none d-sm-inline">{{ __('payments.new_advance') }}</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            {{-- Stat cards --}}
            <div class="row g-4 mb-6">
                <div class="col-6 col-md-3">
                    <div class="card bg-light-warning"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.total_outstanding') }}</span>
                        <span class="fs-2x fw-bold text-warning" id="statOutstanding">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-primary"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.pending_approval') }}</span>
                        <span class="fs-2x fw-bold text-primary" id="statPending">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-success"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.recovery_rate') }}</span>
                        <span class="fs-2x fw-bold text-success" id="statRecovery">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-info"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.active_advances') }}</span>
                        <span class="fs-2x fw-bold text-info" id="statActive">—</span>
                    </div></div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-6">
                <div class="card-body py-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.status') }}</label>
                            <select id="advanceStatusFilter" class="form-select form-select-solid">
                                <option value="">{{ __('payments.all_status') }}</option>
                                <option value="pending">{{ __('payments.pending') }}</option>
                                <option value="approved">{{ __('payments.approved') }}</option>
                                <option value="partially_paid">{{ __('payments.partially_paid') }}</option>
                                <option value="fully_paid">{{ __('payments.fully_paid') }}</option>
                                <option value="rejected">{{ __('payments.rejected') }}</option>
                                <option value="cancelled">{{ __('payments.cancelled') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.date_from') }}</label>
                            <input type="date" id="advanceDateFrom" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.date_to') }}</label>
                            <input type="date" id="advanceDateTo" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold fs-7">Per page</label>
                            <select id="advancePerPage" class="form-select form-select-solid">
                                <option value="15">15 / page</option>
                                <option value="25">25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="card card-flush">
                <div class="card-body pt-0">
                    <div id="advLoading" class="text-center py-10 d-none"><div class="spinner-border text-primary"></div></div>

                    <div id="advWrapper" class="d-none">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                        <th class="min-w-200px">{{ __('payments.employee') }}</th>
                                        <th class="min-w-120px text-end">{{ __('payments.advance_amount') }}</th>
                                        <th class="min-w-120px text-end">{{ __('payments.remaining') }}</th>
                                        <th class="min-w-120px">{{ __('payments.advance_date') }}</th>
                                        <th class="min-w-120px">{{ __('payments.deduction_frequency') }}</th>
                                        <th class="min-w-120px text-center">{{ __('payments.installments') }}</th>
                                        <th class="min-w-100px text-center">{{ __('payments.status') }}</th>
                                        <th class="min-w-40px text-end"></th>
                                    </tr>
                                </thead>
                                <tbody id="advTableBody"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted fs-7" id="advPageInfo"></div>
                            <ul class="pagination m-0" id="advPagination"></ul>
                        </div>
                    </div>

                    <div id="advEmpty" class="text-center py-15 d-none">
                        <i class="bi bi-cash-stack fs-5tx text-muted mb-4 d-block"></i>
                        <h4 class="fw-bold text-gray-800 mb-2">{{ __('payments.no_advances_found') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('department.employee-advance.create')
    @include('department.employee-advance.advance-drawer')
    @include('department.employee-advance.advance-edit-modal')
    @endsection

    @push('scripts')
    <script>
        window.ADVANCE_ROUTES = {
            data:         '{{ route('employee-advance.data') }}',
            drawer_data:  '{{ route('employee-advance.drawer-data', ['id' => '__ID__']) }}',
            update:       '{{ route('employee-advance.update', ['employee_advance' => '__ID__']) }}',
            destroy:      '{{ route('employee-advance.destroy', ['employee_advance' => '__ID__']) }}',
            approve:      '{{ route('employee-advance.approve', ['id' => '__ID__']) }}',
            reject:       '{{ route('employee-advance.reject', ['id' => '__ID__']) }}',
            cancel:       '{{ route('employee-advance.cancel', ['id' => '__ID__']) }}',
            store:        '{{ route('employee-advance.store') }}',
        };
        window.ADVANCE_BOOTSTRAP = {
            search:  @json($initialFilters['search'] ?? ''),
            perPage: @json($initialFilters['per_page'] ?? 15),
        };
    </script>
    @include('department.employee-advance.advance-list-scripts')
    @include('department.employee-advance.advance-drawer-scripts')
    @include('department.employee-advance.advance-reject-modal')
    @endpush
</x-app-layout>