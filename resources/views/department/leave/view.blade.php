@if(isset($leave))
<div class="modal fade" id="viewLeaveModal{{ $leave->id }}" tabindex="-1" aria-hidden="true" dir="ltr">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-calendar-check me-2"></i>
                    {{ __('payments.leave_details') }} #{{ $leave->id }}
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh; text-align: left;">
                
                <!-- Employee Header Card -->
                <div class="d-flex flex-stack mb-7" style="direction: ltr;">
                    <div class="d-flex align-items-center">
                        <!-- Avatar -->
                        <div class="symbol symbol-60px symbol-circle me-5">
                            @if($leave->employee->user && $leave->employee->user->profile_image)
                                <img src="{{ asset('storage/' . $leave->employee->user->profile_image) }}" alt="{{ $leave->employee->first_name }}" class="symbol-label" />
                            @else
                                <div class="symbol-label fs-2x fw-bold bg-light-primary text-primary">
                                    {{ strtoupper(substr($leave->employee->first_name, 0, 1)) }}{{ strtoupper(substr($leave->employee->last_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        
                        <!-- Employee Info -->
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-3 text-gray-800">{{ $leave->employee->first_name }} {{ $leave->employee->last_name }}</span>
                            <span class="fw-semibold text-gray-500">{{ $leave->employee->department->name ?? 'No Department' }}</span>
                            <span class="fw-semibold text-gray-500">{{ $leave->employee->email }}</span>
                        </div>
                    </div>
                    
                    <!-- Status Badges -->
                    <div class="d-flex flex-column align-items-end">
                        <div class="mb-2">
                            {!! $leave->status_badge !!}
                            {!! $leave->type_badge !!}
                        </div>
                        @if($leave->is_paid)
                            <span class="badge badge-light-success py-2 px-3 fs-7">
                                <i class="bi bi-cash me-1"></i> {{ __('payments.paid_leave') }}
                            </span>
                        @else
                            <span class="badge badge-light-secondary py-2 px-3 fs-7">
                                <i class="bi bi-cash-stack me-1"></i> {{ __('payments.unpaid_leave') }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Progress Bar (for ongoing leave) -->
                @if($leave->status === 'ongoing')
                <div class="card card-dashed bg-light-primary mb-7">
                    <div class="card-body">
                        <div class="d-flex flex-column">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-semibold text-gray-600">{{ __('payments.leave_progress') }}</span>
                                <span class="fw-bold text-primary">{{ $leave->progress }}%</span>
                            </div>
                            <div class="progress h-10px">
                                <div class="progress-bar bg-primary" role="progressbar" 
                                     style="width: {{ $leave->progress }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between mt-2 text-muted fs-7">
                                <span>{{ __('payments.started') }}: {{ $leave->start_date->format('d M Y') }}</span>
                                <span>{{ __('payments.ends') }}: {{ $leave->end_date->format('d M Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Timeline/Audit Trail -->
                <div class="card card-dashed mb-7">
                    <div class="card-body">
                        <h5 class="card-title text-muted mb-4">
                            <i class="bi bi-clock-history me-2"></i>
                            {{ __('payments.timeline') }}
                        </h5>
                        <div class="d-flex flex-wrap gap-5">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar-plus text-primary me-2 fs-5"></i>
                                <div>
                                    <span class="text-muted d-block fs-7">{{ __('payments.applied_on') }}</span>
                                    <span class="fw-bold">{{ $leave->applied_at->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                            
                            @if($leave->approved_at)
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle text-success me-2 fs-5"></i>
                                <div>
                                    <span class="text-muted d-block fs-7">{{ __('payments.approved_on') }}</span>
                                    <span class="fw-bold">{{ $leave->approved_at->format('d M Y, h:i A') }}</span>
                                    @if($leave->approver)
                                        <span class="text-muted fs-7">by {{ $leave->approver->name }}</span>
                                    @endif
                                </div>
                            </div>
                            @endif
                            
                            @if($leave->rejected_at)
                            <div class="d-flex align-items-center">
                                <i class="bi bi-x-circle text-danger me-2 fs-5"></i>
                                <div>
                                    <span class="text-muted d-block fs-7">{{ __('payments.rejected_on') }}</span>
                                    <span class="fw-bold">{{ $leave->rejected_at->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                            @endif
                            
                            @if($leave->cancelled_at)
                            <div class="d-flex align-items-center">
                                <i class="bi bi-x-octagon text-secondary me-2 fs-5"></i>
                                <div>
                                    <span class="text-muted d-block fs-7">{{ __('payments.cancelled_on') }}</span>
                                    <span class="fw-bold">{{ $leave->cancelled_at->format('d M Y, h:i A') }}</span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Main Content Tabs -->
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-4" role="tablist" style="direction: ltr;">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" href="#leave_details_tab_{{ $leave->id }}" role="tab">
                            <i class="bi bi-info-circle me-2"></i>
                            {{ __('payments.leave_details') }}
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#leave_handover_tab_{{ $leave->id }}" role="tab">
                            <i class="bi bi-arrow-left-right me-2"></i>
                            {{ __('payments.handover_info') }}
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#leave_attachments_tab_{{ $leave->id }}" role="tab">
                            <i class="bi bi-paperclip me-2"></i>
                            {{ __('payments.attachments') }}
                            @php
                                $attachmentsCount = 0;
                                if ($leave->attachments) {
                                    if (is_array($leave->attachments)) {
                                        $attachmentsCount = count($leave->attachments);
                                    } elseif (is_string($leave->attachments)) {
                                        $decoded = json_decode($leave->attachments, true);
                                        $attachmentsCount = is_array($decoded) ? count($decoded) : 0;
                                    }
                                }
                            @endphp
                            @if($attachmentsCount > 0)
                                <span class="badge badge-circle badge-light-primary ms-2">{{ $attachmentsCount }}</span>
                            @endif
                        </a>
                    </li>
                </ul>

                <div class="tab-content" style="direction: ltr;">
                    <!-- Details Tab -->
                    <div class="tab-pane fade show active" id="leave_details_tab_{{ $leave->id }}" role="tabpanel">
                        <div class="row g-7">
                            <!-- Leave Period Card -->
                            <div class="col-md-6">
                                <div class="card card-dashed bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-4">
                                            <i class="bi bi-calendar-range me-2"></i>
                                            {{ __('payments.leave_period') }}
                                        </h6>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-gray-600">{{ __('payments.start_date') }}:</span>
                                            <span class="fw-bold">{{ $leave->start_date->format('l, d F Y') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-gray-600">{{ __('payments.end_date') }}:</span>
                                            <span class="fw-bold">{{ $leave->end_date->format('l, d F Y') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-gray-600">{{ __('payments.total_days') }}:</span>
                                            <span class="fw-bold fs-3 text-primary">{{ $leave->total_days }} days</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Leave Type Card -->
                            <div class="col-md-6">
                                <div class="card card-dashed bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-4">
                                            <i class="bi bi-tag me-2"></i>
                                            {{ __('payments.leave_type') }}
                                        </h6>
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-gray-600">{{ __('payments.type') }}:</span>
                                            <span class="fw-bold">{{ $leave->leave_type_label }}</span>
                                        </div>
                                        @if($leave->leave_type === 'other' && $leave->custom_type)
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-gray-600">{{ __('payments.custom_type') }}:</span>
                                            <span class="fw-bold">{{ $leave->custom_type }}</span>
                                        </div>
                                        @endif
                                        <div class="d-flex justify-content-between">
                                            <span class="text-gray-600">{{ __('payments.payment') }}:</span>
                                            @if($leave->is_paid)
                                                <span class="badge badge-light-success">Paid Leave</span>
                                            @else
                                                <span class="badge badge-light-secondary">Unpaid Leave</span>
                                            @endif
                                        </div>
                                        @if(!$leave->is_paid && $leave->deduction_amount)
                                        <div class="d-flex justify-content-between mt-3">
                                            <span class="text-gray-600">{{ __('payments.deduction_amount') }}:</span>
                                            <span class="fw-bold text-danger">{{ number_format($leave->deduction_amount, 2) }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Reason Card -->
                            <div class="col-12">
                                <div class="card card-dashed bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-4">
                                            <i class="bi bi-chat-dots me-2"></i>
                                            {{ __('payments.reason') }}
                                        </h6>
                                        <div class="p-4 bg-white rounded border">
                                            {{ $leave->reason ?? 'No reason provided' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rejection Reason (if rejected) -->
                            @if($leave->status === 'rejected' && $leave->rejection_reason)
                            <div class="col-12">
                                <div class="card card-dashed bg-light-danger">
                                    <div class="card-body">
                                        <h6 class="card-title text-danger mb-4">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            {{ __('payments.rejection_reason') }}
                                        </h6>
                                        <div class="p-4 bg-white rounded border border-danger">
                                            {{ $leave->rejection_reason }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Additional Notes -->
                            @if($leave->notes)
                            <div class="col-12">
                                <div class="card card-dashed bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-4">
                                            <i class="bi bi-journal-text me-2"></i>
                                            {{ __('payments.additional_notes') }}
                                        </h6>
                                        <div class="p-4 bg-white rounded border">
                                            {{ $leave->notes }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Handover Tab -->
                    <div class="tab-pane fade" id="leave_handover_tab_{{ $leave->id }}" role="tabpanel">
                        <div class="row g-7">
                            <!-- Contact Information -->
                            <div class="col-md-6">
                                <div class="card card-dashed bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-4">
                                            <i class="bi bi-telephone me-2"></i>
                                            {{ __('payments.contact_during_leave') }}
                                        </h6>
                                        @if($leave->alternate_contact)
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="text-gray-600">{{ __('payments.alternate_contact') }}:</span>
                                            <span class="fw-bold">{{ $leave->alternate_contact }}</span>
                                        </div>
                                        @endif
                                        @if($leave->emergency_contact)
                                        <div class="d-flex justify-content-between">
                                            <span class="text-gray-600">{{ __('payments.emergency_contact') }}:</span>
                                            <span class="fw-bold">{{ $leave->emergency_contact }}</span>
                                        </div>
                                        @endif
                                        @if(!$leave->alternate_contact && !$leave->emergency_contact)
                                        <div class="text-muted text-center py-4">
                                            <i class="bi bi-info-circle fs-2 mb-2 d-block"></i>
                                            {{ __('payments.no_contact_info') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Handover Notes -->
                            <div class="col-md-6">
                                <div class="card card-dashed bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-4">
                                            <i class="bi bi-arrow-left-right me-2"></i>
                                            {{ __('payments.handover_notes') }}
                                        </h6>
                                        @if($leave->handover_notes)
                                        <div class="p-3 bg-white rounded border">
                                            {{ $leave->handover_notes }}
                                        </div>
                                        @else
                                        <div class="text-muted text-center py-4">
                                            <i class="bi bi-info-circle fs-2 mb-2 d-block"></i>
                                            {{ __('payments.no_handover_notes') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Handover To -->
                            <div class="col-12">
                                <div class="card card-dashed bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-4">
                                            <i class="bi bi-people me-2"></i>
                                            {{ __('payments.handover_to') }}
                                        </h6>
                                        @php
                                            $handoverTo = is_string($leave->handover_to) ? json_decode($leave->handover_to, true) : $leave->handover_to;
                                        @endphp
                                        @if(is_array($handoverTo) && count($handoverTo) > 0)
                                        <div class="row g-4">
                                            @foreach($handoverTo as $employeeId)
                                                @php
                                                    $handoverEmployee = \App\Models\Employee::find($employeeId);
                                                @endphp
                                                @if($handoverEmployee)
                                                <div class="col-md-4">
                                                    <div class="d-flex align-items-center p-3 bg-white rounded border">
                                                        <div class="symbol symbol-35px symbol-circle me-3">
                                                            <div class="symbol-label bg-light-info text-info fw-bold">
                                                                {{ strtoupper(substr($handoverEmployee->first_name, 0, 1)) }}{{ strtoupper(substr($handoverEmployee->last_name, 0, 1)) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <span class="fw-bold d-block">{{ $handoverEmployee->first_name }} {{ $handoverEmployee->last_name }}</span>
                                                            <span class="text-muted fs-7">{{ $handoverEmployee->job_title ?? 'Employee' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        @else
                                        <div class="text-muted text-center py-4">
                                            <i class="bi bi-info-circle fs-2 mb-2 d-block"></i>
                                            {{ __('payments.no_handover_assigned') }}
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attachments Tab -->
                    <div class="tab-pane fade" id="leave_attachments_tab_{{ $leave->id }}" role="tabpanel">
                        @php
                            $attachments = null;
                            if ($leave->attachments) {
                                if (is_string($leave->attachments)) {
                                    $attachments = json_decode($leave->attachments, true);
                                } elseif (is_array($leave->attachments)) {
                                    $attachments = $leave->attachments;
                                }
                            }
                            $hasAttachments = is_array($attachments) && count($attachments) > 0;
                        @endphp
                        
                        @if($hasAttachments)
                            <div class="row g-4">
                                @foreach($attachments as $index => $attachment)
                                <div class="col-md-4">
                                    <div class="card card-dashed">
                                        <div class="card-body text-center">
                                            @php
                                                $fileType = strtolower(pathinfo($attachment['name'] ?? '', PATHINFO_EXTENSION));
                                                $icon = match($fileType) {
                                                    'pdf' => 'bi-file-pdf text-danger',
                                                    'doc', 'docx' => 'bi-file-word text-primary',
                                                    'xls', 'xlsx' => 'bi-file-excel text-success',
                                                    'jpg', 'jpeg', 'png', 'gif' => 'bi-file-image text-info',
                                                    default => 'bi-file-text text-secondary',
                                                };
                                            @endphp
                                            <i class="bi {{ $icon }} fs-3x mb-3 d-block"></i>
                                            <span class="fw-bold d-block text-truncate">{{ $attachment['name'] ?? 'Unnamed file' }}</span>
                                            @if(isset($attachment['size']))
                                            <span class="text-muted fs-7 d-block mb-3">
                                                {{ round($attachment['size'] / 1024, 2) }} KB
                                            </span>
                                            @endif
                                            <div class="d-flex justify-content-center gap-2">
                                                @if(isset($attachment['path']))
                                                <a href="{{ asset('storage/' . $attachment['path']) }}" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-light btn-active-light-primary">
                                                    <i class="bi bi-eye me-1"></i> {{ __('payments.view') }}
                                                </a>
                                                <a href="{{ asset('storage/' . $attachment['path']) }}" 
                                                   download 
                                                   class="btn btn-sm btn-light btn-active-light-success">
                                                    <i class="bi bi-download me-1"></i> {{ __('payments.download') }}
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-10">
                                <i class="bi bi-paperclip fs-3x text-muted mb-5 d-block"></i>
                                <span class="text-muted fw-bold">{{ __('payments.no_attachments') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <!-- Action Buttons (if pending) -->
                @if($leave->status === 'pending')
                    <div class="d-flex gap-2">
                        @can('approve leave')
                        <button type="button" class="btn btn-success" onclick="approveLeave({{ $leave->id }})">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ __('payments.approve') }}
                        </button>
                        <button type="button" class="btn btn-danger" onclick="showRejectModal({{ $leave->id }})">
                            <i class="bi bi-x-circle me-2"></i>
                            {{ __('payments.reject') }}
                        </button>
                        @endcan
                    </div>
                @endif
                
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>
                    {{ __('payments.close') }}
                </button>
            </div>
        </div>
    </div>
</div>
@endif