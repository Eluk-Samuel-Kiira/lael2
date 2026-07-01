@can('view employee advance')
    <!-- BEGIN: STATISTICS CARDS -->
    <div class="row g-5 g-xl-8 mb-6">
        <!-- Total Outstanding - Warning -->
        <div class="col-xl-3">
            <div class="card card-xl-stretch mb-5 mb-xl-8 shadow-sm">
                <div class="card-body bg-warning rounded">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-white">
                                <i class="ki-duotone ki-bank fs-2x text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-2 text-white" id="total_outstanding">{{ number_format($statistics['total_outstanding'] ?? 0, 2) }}</span>
                            <span class="fw-semibold text-white opacity-75">{{ __('payments.total_outstanding') }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-5">
                        <span class="badge badge-light-warning fs-7 fw-bold px-4 py-2">{{ __('payments.to_be_deducted') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Approval - Primary -->
        <div class="col-xl-3">
            <div class="card card-xl-stretch mb-5 mb-xl-8 shadow-sm">
                <div class="card-body bg-primary rounded">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-white">
                                <i class="ki-duotone ki-time fs-2x text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-2 text-white" id="pending_count">{{ $statistics['pending_count'] ?? 0 }}</span>
                            <span class="fw-semibold text-white opacity-75">{{ __('payments.pending_approval') }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-5">
                        <span class="badge badge-light-primary fs-7 fw-bold px-4 py-2">{{ __('payments.awaiting_approval') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recovery Rate - Success -->
        <div class="col-xl-3">
            <div class="card card-xl-stretch mb-5 mb-xl-8 shadow-sm">
                <div class="card-body bg-success rounded">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-white">
                                <i class="ki-duotone ki-chart-line-down fs-2x text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-2 text-white" id="recovery_rate">{{ $statistics['recovery_rate'] ?? 0 }}%</span>
                            <span class="fw-semibold text-white opacity-75">{{ __('payments.recovery_rate') }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-5">
                        <span class="badge badge-light-success fs-7 fw-bold px-4 py-2">{{ __('payments.of_total_advanced') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Advances - Info -->
        <div class="col-xl-3">
            <div class="card card-xl-stretch mb-5 mb-xl-8 shadow-sm">
                <div class="card-body bg-info rounded">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-white">
                                <i class="ki-duotone ki-abstract-26 fs-2x text-info">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-2 text-white" id="active_count">{{ $statistics['active_count'] ?? 0 }}</span>
                            <span class="fw-semibold text-white opacity-75">{{ __('payments.active_advances') }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-5">
                        <span class="badge badge-light-info fs-7 fw-bold px-4 py-2">{{ __('payments.currently_deducting') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END: STATISTICS CARDS -->


    <!-- BEGIN: FILTER CARD -->
    <div class="card card-flush mb-6">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">{{ __('payments.filter_advances') }}</h3>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-light" onclick="resetFilters()">
                    <i class="ki-duotone ki-cross-circle fs-2 me-2"></i>
                    {{ __('payments.reset_filters') }}
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-5">
                <!-- Status Filter -->
                <div class="col-md-3">
                    <label class="form-label">{{ __('payments.status') }}</label>
                    <select class="form-select" id="status_filter" data-control="select2" data-placeholder="{{ __('payments.all_status') }}">
                        <option value="">{{ __('payments.all_status') }}</option>
                        <option value="pending">{{ __('payments.pending') }}</option>
                        <option value="approved">{{ __('payments.approved') }}</option>
                        <option value="partially_paid">{{ __('payments.partially_paid') }}</option>
                        <option value="fully_paid">{{ __('payments.fully_paid') }}</option>
                        <option value="rejected">{{ __('payments.rejected') }}</option>
                        <option value="cancelled">{{ __('payments.cancelled') }}</option>
                    </select>
                </div>

                <!-- Employee Filter -->
                <div class="col-md-3">
                    <label class="form-label">{{ __('payments.employee') }}</label>
                    <select class="form-select" id="employee_filter" data-control="select2" data-placeholder="{{ __('payments.all_employees') }}">
                        <option value="">{{ __('payments.all_employees') }}</option>
                        @foreach($users ?? [] as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range Filter -->
                <div class="col-md-3">
                    <label class="form-label">{{ __('payments.date_from') }}</label>
                    <input type="date" class="form-control" id="date_from" value="{{ request('date_from') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">{{ __('payments.date_to') }}</label>
                    <input type="date" class="form-control" id="date_to" value="{{ request('date_to') }}">
                </div>

                <!-- Search -->
                <div class="col-md-12 mt-4">
                    <div class="input-group">
                        <span class="input-group-text bg-body border-0">
                            <i class="ki-duotone ki-magnifier fs-3 text-gray-500"></i>
                        </span>
                        <input type="text" 
                                class="form-control form-control-solid" 
                                id="search_input"
                                placeholder="{{ __('payments.search_advances') }}"
                                value="{{ request('search') }}">
                        <button class="btn btn-primary" type="button" onclick="applyFilters()">
                            <i class="ki-duotone ki-filter fs-3 me-2"></i>
                            {{ __('payments.apply_filters') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END: FILTER CARD -->

    <!-- BEGIN: ADVANCES TABLE CARD -->
    <div class="card card-flush">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <h3 class="card-label">{{ __('payments.advances_list') }}</h3>
            </div>
            <div class="card-toolbar">
                <!-- Per Page Selector -->
                <select class="form-select form-select-sm w-75px" id="per_page" onchange="changePerPage(this.value)">
                    <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3" id="advances_table">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th class="min-w-50px ps-4">#</th>
                            <th class="min-w-150px" data-sort="employee">{{ __('payments.employee') }}</th>
                            <th class="min-w-100px text-end" data-sort="advance_amount">{{ __('payments.advance_amount') }}</th>
                            <th class="min-w-100px text-end" data-sort="remaining">{{ __('payments.remaining') }}</th>
                            <th class="min-w-100px" data-sort="advance_date">{{ __('payments.advance_date') }}</th>
                            <th class="min-w-120px" data-sort="deduction_frequency">{{ __('payments.deduction_frequency') }}</th>
                            <th class="min-w-80px text-center" data-sort="installments">{{ __('payments.installments') }}</th>
                            <th class="min-w-100px text-center" data-sort="status">{{ __('payments.status') }}</th>
                            <th class="min-w-100px" data-sort="purpose">{{ __('payments.purpose') }}</th>
                            <th class="min-w-150px text-end">{{ __('payments.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($advances as $advance)
                        <tr>
                            <td class="ps-4">
                                <span class="text-gray-800 fw-bold">#{{ $advance->id }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-35px symbol-circle me-3">
                                        <div class="symbol-label bg-light-primary text-primary fw-bold">
                                            {{ strtoupper(substr($advance->employee->first_name, 0, 1)) }}{{ strtoupper(substr($advance->employee->last_name, 0, 1)) }}
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-gray-800 fw-bold">{{ $advance->employee->first_name }} {{ $advance->employee->last_name }}</span>
                                        <span class="text-muted fs-7">{{ $advance->employee->department->name ?? '' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-dark">{{ number_format($advance->advance_amount, 2) }}</span>
                            </td>
                            <td class="text-end">
                                @if($advance->remaining_amount > 0)
                                    <span class="fw-bold text-warning">{{ number_format($advance->remaining_amount, 2) }}</span>
                                @else
                                    <span class="fw-bold text-success">0.00</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-gray-800">{{ $advance->advance_date->format('d M Y') }}</span>
                                <span class="text-muted fs-7 d-block">{{ $advance->advance_date->diffForHumans() }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light-info">{{ $advance->deduction_frequency_label }}</span>
                                @if($advance->deduction_day)
                                    <span class="text-muted fs-7 d-block">{{ __('payments.day') }}: {{ $advance->deduction_day }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($advance->installments > 1)
                                    <span class="fw-bold">{{ $advance->installments_paid }}/{{ $advance->installments }}</span>
                                    <div class="progress h-5px mt-2 w-50 mx-auto">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                                style="width: {{ $advance->progress_percentage }}%"></div>
                                    </div>
                                @else
                                    <span class="badge badge-light-secondary">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'approved' => 'info',
                                        'partially_paid' => 'primary',
                                        'fully_paid' => 'success',
                                        'rejected' => 'danger',
                                        'cancelled' => 'secondary',
                                    ];
                                    $color = $statusColors[$advance->status] ?? 'secondary';
                                @endphp
                                <span class="badge badge-light-{{ $color }} fs-7 fw-bold py-3 px-4">
                                    {{ __('payments.' . $advance->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-gray-800">{{ Str::limit($advance->purpose, 20) }}</span>
                                @if($advance->purpose && strlen($advance->purpose) > 20)
                                    <span class="text-muted fs-7 d-block" data-bs-toggle="tooltip" title="{{ $advance->purpose }}">
                                        {{ __('payments.hover_for_more') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    <!-- View Button -->
                                    <button class="btn btn-sm btn-light btn-active-light-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewAdvanceModal{{ $advance->id }}"
                                            title="{{ __('payments.view') }}">
                                        <i class="bi bi-eye me-1 fs-5"></i> 
                                        <span class="d-none d-sm-inline">{{ __('payments.view') }}</span>
                                    </button>

                                    <!-- Edit Button (only for pending) -->
                                    @if($advance->status === 'pending' && auth()->user()->can('edit employee advance'))
                                    <button class="btn btn-sm btn-light btn-active-light-warning d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editAdvanceModal{{ $advance->id }}"
                                            title="{{ __('payments.edit') }}">
                                        <i class="bi bi-pencil-square me-1 fs-5"></i> 
                                        <span class="d-none d-sm-inline">{{ __('payments.edit') }}</span>
                                    </button>
                                    @endif

                                    <!-- Approve/Reject (for pending) -->
                                    @if($advance->status === 'pending')
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light btn-active-light-success d-flex align-items-center px-3 py-2" 
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                                title="{{ __('payments.approve_reject') }}">
                                            <i class="bi bi-check-circle me-1 fs-5"></i> 
                                            <span class="d-none d-sm-inline">{{ __('payments.actions') }}</span>
                                        </button>
                                        <div class="dropdown-menu">
                                            @can('approve employee advance')
                                            <a class="dropdown-item" href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#approveAdvanceModal{{ $advance->id }}">
                                                <i class="bi bi-check-circle text-success me-2"></i>
                                                {{ __('payments.approve') }}
                                            </a>
                                            @endcan
                                            @can('reject employee advance')
                                            <a class="dropdown-item" href="#" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#rejectAdvanceModal{{ $advance->id }}">
                                                <i class="bi bi-x-circle text-danger me-2"></i>
                                                {{ __('payments.reject') }}
                                            </a>
                                            @endcan
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Cancel Button (for approved) -->
                                    @if(in_array($advance->status, ['approved', 'partially_paid']) && auth()->user()->can('cancel employee advance'))
                                    <button class="btn btn-sm btn-light btn-active-light-secondary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#cancelAdvanceModal{{ $advance->id }}"
                                            title="{{ __('payments.cancel') }}">
                                        <i class="bi bi-x-octagon me-1 fs-5"></i> 
                                        <span class="d-none d-sm-inline">{{ __('payments.cancel') }}</span>
                                    </button>
                                    @endif

                                    <!-- Delete Button (only for pending) -->
                                    @if($advance->status === 'pending' && auth()->user()->can('delete employee advance'))
                                    <button class="btn btn-sm btn-light btn-active-light-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteAdvanceModal{{ $advance->id }}"
                                            title="{{ __('payments.delete') }}">
                                        <i class="bi bi-trash me-1 fs-5"></i> 
                                        <span class="d-none d-sm-inline">{{ __('payments.delete') }}</span>
                                    </button>
                                    @endif
                                </div>
                                @include('department.employee-advance.modals')
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-10">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="ki-duotone ki-hand-holding-usd fs-3x text-muted mb-5"></i>
                                    <span class="text-muted fw-bold">{{ __('payments.no_advances_found') }}</span>
                                    @can('create employee advance')
                                    <button class="btn btn-sm btn-light-primary mt-5" data-bs-toggle="modal" data-bs-target="#kt_modal_add_advance">
                                        <i class="ki-duotone ki-plus fs-2 me-2"></i>
                                        {{ __('payments.create_first_advance') }}
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex flex-stack flex-wrap mt-10">
                <div class="fs-6 fw-semibold text-gray-700">
                    {{ __('payments.showing') }} {{ $advances->firstItem() ?? 0 }} {{ __('payments.to') }} 
                    {{ $advances->lastItem() ?? 0 }} {{ __('payments.of') }} {{ $advances->total() }} {{ __('payments.entries') }}
                </div>
                <div class="pagination">
                    {{ $advances->appends(request()->except('page'))->links() }}
                </div>
            </div>
        </div>
    </div>
    <!-- END: ADVANCES TABLE CARD -->



    <!-- BEGIN: PAGE SCRIPTS -->
    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltips.map(function(el) {
                return new bootstrap.Tooltip(el);
            });
        });

        // Apply filters
        function applyFilters() {
            const params = new URLSearchParams(window.location.search);
            
            // Get filter values
            const status = document.getElementById('status_filter').value;
            const employee = document.getElementById('employee_filter').value;
            const dateFrom = document.getElementById('date_from').value;
            const dateTo = document.getElementById('date_to').value;
            const search = document.getElementById('search_input').value;
            const perPage = document.getElementById('per_page').value;
            
            // Update params
            if (status) params.set('status', status); else params.delete('status');
            if (employee) params.set('employee_id', employee); else params.delete('employee_id');
            if (dateFrom) params.set('date_from', dateFrom); else params.delete('date_from');
            if (dateTo) params.set('date_to', dateTo); else params.delete('date_to');
            if (search) params.set('search', search); else params.delete('search');
            if (perPage) params.set('per_page', perPage); else params.delete('per_page');
            
            // Reset to page 1
            params.delete('page');
            
            window.location.search = params.toString();
        }

        // Reset filters
        function resetFilters() {
            window.location.href = window.location.pathname;
        }

        // Change per page
        function changePerPage(value) {
            const params = new URLSearchParams(window.location.search);
            params.set('per_page', value);
            params.delete('page');
            window.location.search = params.toString();
        }

        // View advance
        function viewAdvance(id) {
            // Load advance details via AJAX
            fetch(`/employee-advances/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate view modal
                        // You'll implement this based on your view modal structure
                        $('#kt_modal_view_advance').modal('show');
                    }
                });
        }

        // Edit advance
        function editAdvance(id) {
            window.location.href = `/employee-advances/${id}/edit`;
        }

        // Approve advance
        function approveAdvance(id) {
            Swal.fire({
                title: '{{ __("payments.confirm_approve") }}',
                text: '{{ __("payments.confirm_approve_message") }}',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __("payments.yes_approve") }}',
                cancelButtonText: '{{ __("auth._cancel") }}',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(`/employee-advances/${id}/approve`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(response.statusText);
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(`{{ __("payments.request_failed") }}: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("payments.success") }}',
                        text: result.value.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }

        // Reject advance
        function rejectAdvance(id) {
            // Show reject reason modal
            $('#reject_advance_id').val(id);
            $('#kt_modal_reject_advance').modal('show');
        }

        // Cancel advance
        function cancelAdvance(id) {
            Swal.fire({
                title: '{{ __("payments.confirm_cancel") }}',
                text: '{{ __("payments.confirm_cancel_message") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '{{ __("payments.yes_cancel") }}',
                cancelButtonText: '{{ __("auth._cancel") }}',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(`/employee-advances/${id}/cancel`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .catch(error => {
                        Swal.showValidationMessage(`{{ __("payments.request_failed") }}: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("payments.success") }}',
                        text: result.value.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }

        // Delete advance
        function deleteAdvance(id) {
            Swal.fire({
                title: '{{ __("payments.confirm_delete") }}',
                text: '{{ __("payments.confirm_delete_message") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '{{ __("payments.yes_delete") }}',
                cancelButtonText: '{{ __("auth._cancel") }}',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(`/employee-advances/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .catch(error => {
                        Swal.showValidationMessage(`{{ __("payments.request_failed") }}: ${error}`);
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("payments.success") }}',
                        text: result.value.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }
            });
        }

        // Export advances
        function exportAdvances() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = `/employee-advances/export?${params.toString()}`;
        }

        // Search on enter key
        document.getElementById('search_input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
    </script>
    <!-- END: PAGE SCRIPTS -->

@endcan