<x-app-layout>
    @section('title', __('payments.leave_management'))
    @section('content')

    <div class="app-toolbar py-3 py-lg-6">
        <div class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading text-gray-900 fw-bold fs-2hx my-0">{{ __('payments.leave_management') }}</h1>
            </div>
            <div class="d-flex align-items-stretch gap-3 w-100 w-md-auto">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-light btn-active-primary active" id="viewCalendarBtn">
                        <i class="ki-duotone ki-calendar fs-2 me-2"><span class="path1"></span><span class="path2"></span></i>
                        <span class="d-none d-sm-inline">{{ __('payments.calendar') }}</span>
                    </button>
                    <button type="button" class="btn btn-light btn-active-primary" id="viewListBtn">
                        <i class="ki-duotone ki-table fs-2 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <span class="d-none d-sm-inline">{{ __('payments.list') }}</span>
                    </button>
                </div>
                @can('create leave')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_add_leave">
                        <i class="ki-duotone ki-plus fs-2 me-2"></i>
                        <span class="d-none d-sm-inline">{{ __('payments.request_leave') }}</span>
                    </button>
                @endcan
            </div>
        </div>
    </div>

    <div class="app-content flex-column-fluid">
        <div class="app-container container-xxl">

            {{-- Stats --}}
            <div class="row g-4 mb-6">
                <div class="col-6 col-md-3">
                    <div class="card bg-light-warning"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.pending_requests') }}</span>
                        <span class="fs-2x fw-bold text-warning" id="statPending">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-primary"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.currently_on_leave') }}</span>
                        <span class="fs-2x fw-bold text-primary" id="statOngoing">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-info"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.upcoming_leave') }}</span>
                        <span class="fs-2x fw-bold text-info" id="statUpcoming">—</span>
                    </div></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card bg-light-success"><div class="card-body py-4">
                        <span class="text-muted fs-8 fw-semibold text-uppercase d-block">{{ __('payments.days_this_month') }}</span>
                        <span class="fs-2x fw-bold text-success" id="statDays">—</span>
                    </div></div>
                </div>
            </div>

            {{-- Filters --}}
            <div class="card mb-6">
                <div class="card-body py-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.status') }}</label>
                            <select id="leaveStatusFilter" class="form-select form-select-solid">
                                <option value="">{{ __('payments.all_status') }}</option>
                                <option value="pending">{{ __('payments.pending') }}</option>
                                <option value="approved">{{ __('payments.approved') }}</option>
                                <option value="ongoing">{{ __('payments.ongoing') }}</option>
                                <option value="completed">{{ __('payments.completed') }}</option>
                                <option value="rejected">{{ __('payments.rejected') }}</option>
                                <option value="cancelled">{{ __('payments.cancelled') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.type') }}</label>
                            <select id="leaveTypeFilter" class="form-select form-select-solid">
                                <option value="">{{ __('payments.all_types') }}</option>
                                <option value="annual">{{ __('payments.annual') }}</option>
                                <option value="sick">{{ __('payments.sick') }}</option>
                                <option value="maternity">{{ __('payments.maternity') }}</option>
                                <option value="paternity">{{ __('payments.paternity') }}</option>
                                <option value="bereavement">{{ __('payments.bereavement') }}</option>
                                <option value="study">{{ __('payments.study') }}</option>
                                <option value="unpaid">{{ __('payments.unpaid') }}</option>
                                <option value="other">{{ __('payments.other') }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.date_from') }}</label>
                            <input type="date" id="leaveDateFrom" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">{{ __('payments.date_to') }}</label>
                            <input type="date" id="leaveDateTo" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold fs-7">Per page</label>
                            <select id="leavePerPage" class="form-select form-select-solid">
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button id="btnResetLeaveFilters" class="btn btn-light w-100" type="button">{{ __('payments.reset') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Calendar view --}}
            <div id="leaveCalendarView" class="card card-flush mb-6">
                <div class="card-body">
                    <div id="kt_calendar"></div>
                </div>
            </div>

            {{-- List view (hidden by default) --}}
            <div id="leaveListView" class="card card-flush d-none">
                <div class="card-body pt-0">
                    <div id="leaveLoading" class="text-center py-10 d-none">
                        <div class="spinner-border text-primary"></div>
                    </div>

                    <div id="leaveWrapper" class="d-none">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5 mb-0">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                        <th class="min-w-200px">{{ __('payments.employee') }}</th>
                                        <th class="min-w-120px">{{ __('payments.type') }}</th>
                                        <th class="min-w-150px">{{ __('payments.duration') }}</th>
                                        <th class="min-w-80px">{{ __('payments.days') }}</th>
                                        <th class="min-w-100px text-center">{{ __('payments.status') }}</th>
                                        <th class="min-w-100px">{{ __('payments.applied_on') }}</th>
                                        <th class="min-w-40px text-end"></th>
                                    </tr>
                                </thead>
                                <tbody id="leaveTableBody"></tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="text-muted fs-7" id="leavePageInfo"></div>
                            <ul class="pagination m-0" id="leavePagination"></ul>
                        </div>
                    </div>

                    <div id="leaveEmpty" class="text-center py-15 d-none">
                        <i class="bi bi-calendar-x fs-5tx text-muted mb-4 d-block"></i>
                        <h4 class="fw-bold text-gray-800 mb-2">{{ __('payments.no_leave_requests') }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('department.leave.create')
    @include('department.leave.leave-drawer')
    @include('department.leave.leave-edit-modal')
    @include('department.leave.leave-reject-modal')
    @endsection

    @push('scripts')
    <script>
        window.LEAVE_ROUTES = {
            data:         '{{ route('leave.data') }}',
            drawer_data:  '{{ route('leave.drawer-data', ['id' => '__ID__']) }}',
            meta:         '{{ route('leave.meta') }}',
            store:        '{{ route('leave.store') }}',
            update:       '{{ route('leave.update', ['leave' => '__ID__']) }}',
            destroy:      '{{ route('leave.destroy', ['leave' => '__ID__']) }}',
            approve:      '{{ route('leave.approve', ['id' => '__ID__']) }}',
            reject:       '{{ route('leave.reject', ['id' => '__ID__']) }}',
            cancel:       '{{ route('leave.cancel', ['id' => '__ID__']) }}',
            calendar:     '{{ route('leave.calendar.events') }}',
        };
        window.LEAVE_BOOTSTRAP = {
            search:  @json($initialFilters['search'] ?? ''),
            perPage: @json($initialFilters['per_page'] ?? 15),
        };
    </script>
    @include('department.leave.leave-list-scripts')
    @include('department.leave.leave-drawer-scripts')
    @include('department.leave.leave-page-scripts')
    @endpush
</x-app-layout>