<!-- BEGIN: STATISTICS CARDS -->
<div class="row g-5 g-xl-8 mb-6">
    <!-- Pending Requests -->
    <div class="col-xl-3">
        <div class="card card-xl-stretch mb-5 mb-xl-8 bg-light-warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-5">
                        <span class="symbol-label bg-white">
                            <i class="ki-duotone ki-time fs-2x text-warning">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="fw-bold fs-2x" id="pending_count">{{ $statistics['pending_count'] }}</span>
                        <span class="opacity-75">{{ __('payments.pending_requests') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Currently on Leave -->
    <div class="col-xl-3">
        <div class="card card-xl-stretch mb-5 mb-xl-8 bg-light-primary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-5">
                        <span class="symbol-label bg-white">
                            <i class="ki-duotone ki-profile-circle fs-2x text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="fw-bold fs-2x" id="ongoing_count">{{ $statistics['ongoing_count'] }}</span>
                        <span class="opacity-75">{{ __('payments.currently_on_leave') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Leave -->
    <div class="col-xl-3">
        <div class="card card-xl-stretch mb-5 mb-xl-8 bg-light-info">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-5">
                        <span class="symbol-label bg-white">
                            <i class="ki-duotone ki-calendar-8 fs-2x text-info">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                                <span class="path6"></span>
                            </i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="fw-bold fs-2x" id="upcoming_count">{{ $statistics['approved_upcoming'] }}</span>
                        <span class="opacity-75">{{ __('payments.upcoming_leave') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Days This Month -->
    <div class="col-xl-3">
        <div class="card card-xl-stretch mb-5 mb-xl-8 bg-light-success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-5">
                        <span class="symbol-label bg-white">
                            <i class="ki-duotone ki-cloud fs-2x text-success">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </span>
                    </div>
                    <div class="d-flex flex-column">
                        <span class="fw-bold fs-2x" id="total_days">{{ number_format($statistics['total_days_this_month'], 3) }}</span>
                        <span class="opacity-75">{{ __('payments.days_this_month') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END: STATISTICS CARDS -->

<!-- BEGIN: FILTER CARD - Compact -->
<div class="card card-flush mb-6">
    <div class="card-header">
        <div class="card-title">
            <i class="ki-duotone ki-filter fs-2 me-2"></i>
            <h3 class="card-label">{{ __('payments.filters') }}</h3>
        </div>
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-light" onclick="resetFilters()">
                <i class="ki-duotone ki-arrow-rotate-left fs-3 me-1"></i>
                {{ __('payments.reset') }}
            </button>
        </div>
    </div>
    <div class="card-body">
        <!-- Row 1: Main Filters -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <select class="form-select" id="status_filter">
                    <option value="">{{ __('payments.all_status') }}</option>
                    <option value="pending">{{ __('payments.pending') }}</option>
                    <option value="approved">{{ __('payments.approved') }}</option>
                    <option value="ongoing">{{ __('payments.ongoing') }}</option>
                    <option value="completed">{{ __('payments.completed') }}</option>
                    <option value="rejected">{{ __('payments.rejected') }}</option>
                    <option value="cancelled">{{ __('payments.cancelled') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="employee_filter" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true" required>
                    <option value="">{{ __('payments.all_employees') }}</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="type_filter" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true" required>
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
            <div class="col-md-3">
                <div class="d-flex gap-2">
                    <input type="date" class="form-control" id="date_from" placeholder="From Date">
                    <input type="date" class="form-control" id="date_to" placeholder="To Date">
                </div>
            </div>
        </div>
        
        <!-- Row 2: Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1 flex-md-grow-0" onclick="applyFilters()">
                        <i class="ki-duotone ki-search fs-3 me-1"></i>
                        {{ __('payments.apply_filters') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END: FILTER CARD -->

<!-- BEGIN: CALENDAR VIEW -->
<div id="calendarView" class="card card-flush">
    <div class="card-body">
        <div id="kt_calendar"></div>
    </div>
</div>
<!-- END: CALENDAR VIEW -->

<!-- BEGIN: LIST VIEW (Initially Hidden) -->
<div id="listView" class="card card-flush" style="display: none;">
    <div class="card-header">
        <div class="card-title">
            <h3 class="card-label">{{ __('payments.leave_requests') }}</h3>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3" id="leave_table">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">{{ __('payments.employee') }}</th>
                        <th>{{ __('payments.type') }}</th>
                        <th>{{ __('payments.duration') }}</th>
                        <th>{{ __('payments.days') }}</th>
                        <th>{{ __('payments.status') }}</th>
                        <th>{{ __('payments.applied_on') }}</th>
                        <th class="text-end">{{ __('payments.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $leave)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-35px symbol-circle me-3">
                                    <div class="symbol-label bg-light-primary text-primary fw-bold">
                                        {{ strtoupper(substr($leave->employee->first_name, 0, 1)) }}{{ strtoupper(substr($leave->employee->last_name, 0, 1)) }}
                                    </div>
                                </div>
                                <div>
                                    <span class="fw-bold d-block">{{ $leave->employee->first_name }} {{ $leave->employee->last_name }}</span>
                                    <span class="text-muted fs-7">{{ $leave->employee->department->name ?? '' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>{!! $leave->type_badge !!}</td>
                        <td>
                            {{ $leave->start_date->format('d M') }} - {{ $leave->end_date->format('d M Y') }}
                        </td>
                        <td>
                            <span class="badge badge-light-info">{{ $leave->total_days }} days</span>
                        </td>
                        <td>{!! $leave->status_badge !!}</td>
                        <td>{{ $leave->applied_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-2 justify-content-end">
                                <!-- View Button -->
                                <button class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewLeaveModal{{$leave->id}}"
                                        title="{{ __('payments.view') }}">
                                    <i class="bi bi-eye me-1 fs-5"></i> 
                                    <span class="d-none d-sm-inline">{{ __('payments.view') }}</span>
                                </button>
                                
                                @if($leave->status === 'pending')
                                    @can('approve leave')
                                    <!-- Approve Button -->
                                    <button class="btn btn-sm btn-light btn-active-color-success d-flex align-items-center px-3 py-2" 
                                            onclick="approveLeave({{ $leave->id }})"
                                            title="{{ __('payments.approve') }}">
                                        <i class="bi bi-check-circle me-1 fs-5"></i> 
                                        <span class="d-none d-sm-inline">{{ __('payments.approve') }}</span>
                                    </button>
                                    
                                    <!-- Reject Button -->
                                    <button class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            onclick="showRejectModal({{ $leave->id }})"
                                            title="{{ __('payments.reject') }}">
                                        <i class="bi bi-x-circle me-1 fs-5"></i> 
                                        <span class="d-none d-sm-inline">{{ __('payments.reject') }}</span>
                                    </button>
                                    @endcan
                                    
                                    {{--
                                    @can('edit leave')
                                    <!-- Edit Button -->
                                    <button class="btn btn-sm btn-light btn-active-color-warning d-flex align-items-center px-3 py-2" 
                                            onclick="editLeave({{ $leave->id }})"
                                            title="{{ __('payments.edit') }}">
                                        <i class="bi bi-pencil-square me-1 fs-5"></i> 
                                        <span class="d-none d-sm-inline">{{ __('payments.edit') }}</span>
                                    </button>
                                    @endcan
                                    --}}
                                    
                                    @can('delete leave')
                                    <!-- Delete Button -->
                                    <button class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            onclick="deleteLeave({{ $leave->id }})"
                                            title="{{ __('payments.delete') }}">
                                        <i class="bi bi-trash me-1 fs-5"></i> 
                                        <span class="d-none d-sm-inline">{{ __('payments.delete') }}</span>
                                    </button>
                                    @endcan
                                @endif
                                
                                @if(in_array($leave->status, ['approved', 'ongoing']) && $leave->start_date->gt(now()))
                                    @can('cancel leave')
                                    <!-- Cancel Button -->
                                    <button class="btn btn-sm btn-light btn-active-color-dark d-flex align-items-center px-3 py-2" 
                                            onclick="cancelLeave({{ $leave->id }})"
                                            title="{{ __('payments.cancel') }}">
                                        <i class="bi bi-x-octagon me-1 fs-5"></i> 
                                        <span class="d-none d-sm-inline">{{ __('payments.cancel') }}</span>
                                    </button>
                                    @endcan
                                @endif

                                @include('department.leave.view')
                                @include('department.leave.reject')
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-10">
                            <i class="ki-duotone ki-calendar fs-3x text-muted mb-5 d-block"></i>
                            <span class="text-muted">{{ __('payments.no_leave_requests') }}</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- END: LIST VIEW -->
