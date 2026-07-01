<!-- View Modal - Metronic Styled -->
<div class="modal fade" id="viewAdvanceModal{{ $advance->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.view_advance') }} #{{ $advance->id }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh;">
                <!-- Employee Header Card -->
                <div class="d-flex flex-stack mb-7">
                    <!--begin::Wrapper-->
                    <div class="d-flex align-items-center me-3">
                        <!--begin::Avatar-->
                        <div class="symbol symbol-60px symbol-circle me-5">
                            @if($advance->employee->user && $advance->employee->user->profile_image)
                                <img src="{{ asset('storage/' . $advance->employee->user->profile_image) }}" alt="{{ $advance->employee->first_name }}" class="symbol-label" />
                            @else
                                <div class="symbol-label fs-2x fw-bold bg-light-primary text-primary">
                                    {{ strtoupper(substr($advance->employee->first_name, 0, 1)) }}{{ strtoupper(substr($advance->employee->last_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <!--end::Avatar-->

                        <!--begin::Info-->
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-4 text-gray-800">{{ $advance->employee->first_name }} {{ $advance->employee->last_name }}</span>
                            <span class="fw-semibold text-gray-500">{{ $advance->employee->department->name ?? '' }}</span>
                            <span class="fw-semibold text-gray-500">{{ $advance->employee->email }}</span>
                        </div>
                        <!--end::Info-->
                    </div>
                    <!--end::Wrapper-->

                    <!--begin::Status-->
                    <div class="d-flex flex-column align-items-end">
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
                        <span class="badge badge-lg badge-light-{{ $color }} py-3 px-4 fs-6 mb-2">
                            {{ __('payments.' . $advance->status) }}
                        </span>
                        @if($advance->approved_at)
                            <span class="text-muted fs-7">
                                <i class="ki-duotone ki-calendar me-1 fs-6"></i>
                                {{ $advance->approved_at->format('d M Y') }}
                            </span>
                        @endif
                    </div>
                    <!--end::Status-->
                </div>

                <!-- Stats Cards Row -->
                <div class="row g-5 g-xl-8 mb-5">
                    <div class="col-md-4">
                        <div class="card card-dashed bg-light-warning">
                            <div class="card-body">
                                <span class="fw-semibold text-gray-600 d-block mb-2">{{ __('payments.advance_amount') }}</span>
                                <span class="fw-bold fs-2x text-warning">{{ number_format($advance->advance_amount, 2) }}</span>
                                <span class="fw-semibold text-gray-600">{{ currency_symbol() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-dashed bg-light">
                            <div class="card-body">
                                <span class="fw-semibold text-gray-600 d-block mb-2">{{ __('payments.remaining_amount') }}</span>
                                <span class="fw-bold fs-2x">{{ number_format($advance->remaining_amount, 2) }}</span>
                                <span class="fw-semibold text-gray-600">{{ currency_symbol() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-dashed bg-light-success">
                            <div class="card-body">
                                <span class="fw-semibold text-gray-600 d-block mb-2">{{ __('payments.recovered_amount') }}</span>
                                <span class="fw-bold fs-2x text-success">{{ number_format($advance->advance_amount - $advance->remaining_amount, 2) }}</span>
                                <span class="fw-semibold text-gray-600">{{ currency_symbol() }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Tabs -->
                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link active" data-bs-toggle="tab" href="#details_tab_{{ $advance->id }}" role="tab">
                            <i class="ki-duotone ki-information me-2 fs-3"></i>
                            {{ __('payments.advance_details') }}
                        </a>
                    </li>
                    @if($advance->deduction_history && count($advance->deduction_history) > 0)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#history_tab_{{ $advance->id }}" role="tab">
                            <i class="ki-duotone ki-clock me-2 fs-3"></i>
                            {{ __('payments.deduction_history') }}
                            <span class="badge badge-circle badge-light-primary ms-2">{{ count($advance->deduction_history) }}</span>
                        </a>
                    </li>
                    @endif
                    @if($advance->deduction_schedule && count($advance->deduction_schedule) > 0)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-bs-toggle="tab" href="#schedule_tab_{{ $advance->id }}" role="tab">
                            <i class="ki-duotone ki-calendar me-2 fs-3"></i>
                            {{ __('payments.deduction_schedule') }}
                        </a>
                    </li>
                    @endif
                </ul>

                <div class="tab-content">
                    <!-- Details Tab -->
                    <div class="tab-pane fade show active" id="details_tab_{{ $advance->id }}" role="tabpanel">
                        <div class="row g-9">
                            <!-- Dates -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-5">
                                    <i class="ki-duotone ki-calendar fs-2x me-3 text-primary"></i>
                                    <div>
                                        <span class="fw-semibold text-gray-600 d-block">{{ __('payments.advance_date') }}</span>
                                        <span class="fw-bold fs-4">{{ $advance->advance_date->format('d F Y') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-5">
                                    <i class="ki-duotone ki-calendar-tick fs-2x me-3 text-primary"></i>
                                    <div>
                                        <span class="fw-semibold text-gray-600 d-block">{{ __('payments.request_date') }}</span>
                                        <span class="fw-bold fs-4">{{ $advance->request_date->format('d F Y') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Deduction Info -->
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-5">
                                    <i class="ki-duotone ki-arrow-repeat fs-2x me-3 text-primary"></i>
                                    <div>
                                        <span class="fw-semibold text-gray-600 d-block">{{ __('payments.deduction_frequency') }}</span>
                                        <span class="fw-bold fs-4">{{ $advance->deduction_frequency_label }}</span>
                                        @if($advance->deduction_day)
                                            <span class="badge badge-light-primary ms-2">{{ __('payments.day') }}: {{ $advance->deduction_day }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($advance->installments > 1)
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-5">
                                    <i class="ki-duotone ki-chart-pie fs-2x me-3 text-primary"></i>
                                    <div class="w-100">
                                        <span class="fw-semibold text-gray-600 d-block">{{ __('payments.installments') }}</span>
                                        <div class="d-flex align-items-center">
                                            <span class="fw-bold fs-4 me-3">{{ $advance->installments_paid }}/{{ $advance->installments }}</span>
                                            <div class="progress h-10px w-100">
                                                <div class="progress-bar bg-primary" role="progressbar" 
                                                     style="width: {{ $advance->progress_percentage }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Purpose -->
                            @if($advance->purpose)
                            <div class="col-12">
                                <div class="separator separator-dashed my-3"></div>
                                <div class="d-flex mb-5">
                                    <i class="ki-duotone ki-message-text fs-2x me-3 text-primary"></i>
                                    <div>
                                        <span class="fw-semibold text-gray-600 d-block">{{ __('payments.purpose') }}</span>
                                        <span class="fw-bold fs-4">{{ $advance->purpose }}</span>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Reason -->
                            <div class="col-12">
                                <div class="d-flex mb-5">
                                    <i class="ki-duotone ki-note fs-2x me-3 text-primary"></i>
                                    <div>
                                        <span class="fw-semibold text-gray-600 d-block">{{ __('payments.reason') }}</span>
                                        <div class="bg-light p-5 rounded">
                                            <span class="fw-bold fs-5">{{ $advance->reason }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notes -->
                            @if($advance->notes)
                            <div class="col-12">
                                <div class="d-flex mb-5">
                                    <i class="ki-duotone ki-notepad fs-2x me-3 text-primary"></i>
                                    <div>
                                        <span class="fw-semibold text-gray-600 d-block">{{ __('payments.notes') }}</span>
                                        <div class="bg-light p-5 rounded">
                                            <span class="fw-bold fs-5">{{ $advance->notes }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- History Tab -->
                    @if($advance->deduction_history && count($advance->deduction_history) > 0)
                    <div class="tab-pane fade" id="history_tab_{{ $advance->id }}" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                <thead>
                                    <tr class="fw-bold text-muted bg-light">
                                        <th class="ps-4 min-w-150px">{{ __('payments.date') }}</th>
                                        <th class="text-end min-w-120px">{{ __('payments.amount') }}</th>
                                        <th class="min-w-120px">{{ __('payments.payment_reference') }}</th>
                                        <th class="text-end min-w-150px">{{ __('payments.remaining_after') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($advance->deduction_history as $history)
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold">{{ \Carbon\Carbon::parse($history['deducted_at'])->format('d M Y') }}</span>
                                            <span class="text-muted d-block fs-7">{{ \Carbon\Carbon::parse($history['deducted_at'])->diffForHumans() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-danger">{{ number_format($history['deduction_amount'], 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary">#{{ $history['payment_id'] }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-success">{{ number_format($history['remaining_after'], 2) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Schedule Tab -->
                    @if($advance->deduction_schedule && count($advance->deduction_schedule) > 0)
                    <div class="tab-pane fade" id="schedule_tab_{{ $advance->id }}" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                <thead>
                                    <tr class="fw-bold text-muted bg-light">
                                        <th class="ps-4">{{ __('payments.installment') }}</th>
                                        <th>{{ __('payments.scheduled_date') }}</th>
                                        <th class="text-end">{{ __('payments.amount') }}</th>
                                        <th>{{ __('payments.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($advance->deduction_schedule as $schedule)
                                    <tr>
                                        <td class="ps-4">#{{ $schedule['installment'] }}</td>
                                        <td>{{ \Carbon\Carbon::parse($schedule['scheduled_date'])->format('d M Y') }}</td>
                                        <td class="text-end fw-bold">{{ number_format($schedule['amount'], 2) }}</td>
                                        <td>
                                            @if($schedule['status'] == 'completed')
                                                <span class="badge badge-light-success">{{ __('payments.completed') }}</span>
                                            @else
                                                <span class="badge badge-light-warning">{{ __('payments.pending') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2 me-2"></i>
                    {{ __('auth.close') }}
                </button>
                @if($advance->status === 'pending' && auth()->user()->can('edit employee advance'))
                <button class="btn btn-warning" 
                        data-bs-toggle="modal" 
                        data-bs-target="#editAdvanceModal{{ $advance->id }}"
                        title="{{ __('payments.edit') }}">
                    <i class="bi bi-pencil-square me-1 fs-5"></i> 
                    <span class="d-none d-sm-inline">{{ __('payments.edit') }}</span>
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal (only for pending) -->
@if($advance->status === 'pending' && auth()->user()->can('edit employee advance'))
<div class="modal fade" id="editAdvanceModal{{ $advance->id }}" tabindex="-1" aria-hidden="true" dir="ltr">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.edit_advance') }} #{{ $advance->id }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh; text-align: left;">
                <form id="editAdvanceForm{{ $advance->id }}" class="form" style="direction: ltr;">
                    @csrf
                    @method('PUT')
                    
                    <div class="row g-9 mb-8" style="text-align: left;">
                        <!-- Employee (readonly with Metronic styling) -->
                        <div class="col-md-6">
                            <label class="required fs-6 fw-semibold mb-2 text-start">{{ __('payments.employee') }}</label>
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-40px symbol-circle me-3">
                                    <div class="symbol-label bg-light-primary text-primary fw-bold">
                                        {{ strtoupper(substr($advance->employee->first_name, 0, 1)) }}{{ strtoupper(substr($advance->employee->last_name, 0, 1)) }}
                                    </div>
                                </div>
                                <input type="text" class="form-control form-control-solid bg-light" 
                                       value="{{ $advance->employee->first_name }} {{ $advance->employee->last_name }}" readonly>
                            </div>
                            <input type="hidden" name="employee_id" value="{{ $advance->employee_id }}">
                        </div>

                        <!-- Advance Amount -->
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2 text-start">{{ __('payments.advance_amount') }}</label>
                            <div class="input-group input-group-solid">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="advance_amount" class="form-control form-control-solid" 
                                       step="0.01" min="0.01" value="{{ $advance->advance_amount }}" required>
                            </div>
                            <div id="advance_amount{{ $advance->id }}" class="fv-plugins-message-container text-start"></div>
                        </div>

                        <!-- Advance Date -->
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2 text-start">{{ __('payments.advance_date') }}</label>
                            <input type="date" name="advance_date" class="form-control form-control-solid" 
                                   value="{{ $advance->advance_date->format('Y-m-d') }}" required>
                            <div id="advance_date{{ $advance->id }}" class="fv-plugins-message-container text-start"></div>
                        </div>

                        <!-- Deduction Frequency -->
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2 text-start">{{ __('payments.deduction_frequency') }}</label>
                            <select name="deduction_frequency" class="form-select form-select-solid" 
                                    id="deduction_frequency{{ $advance->id }}" 
                                    style="direction: ltr; text-align: left;"
                                    required>
                                <option value="one_time" {{ $advance->deduction_frequency == 'one_time' ? 'selected' : '' }}>{{ __('payments.one_time') }}</option>
                                <option value="weekly" {{ $advance->deduction_frequency == 'weekly' ? 'selected' : '' }}>{{ __('payments.weekly') }}</option>
                                <option value="monthly" {{ $advance->deduction_frequency == 'monthly' ? 'selected' : '' }}>{{ __('payments.monthly') }}</option>
                                <option value="yearly" {{ $advance->deduction_frequency == 'yearly' ? 'selected' : '' }}>{{ __('payments.yearly') }}</option>
                            </select>
                            <div id="deduction_frequency{{ $advance->id }}" class="fv-plugins-message-container text-start"></div>
                        </div>

                        <!-- Installments (for recurring) -->
                        <div class="col-md-6 fv-row installments-field-{{ $advance->id }}" 
                             style="{{ in_array($advance->deduction_frequency, ['weekly', 'monthly', 'yearly']) ? '' : 'display: none;' }}">
                            <label class="required fs-6 fw-semibold mb-2 text-start">{{ __('payments.installments') }}</label>
                            <input type="number" name="installments" class="form-control form-control-solid" 
                                   min="2" max="12" value="{{ $advance->installments ?? 2 }}">
                            <div id="installments{{ $advance->id }}" class="fv-plugins-message-container text-start"></div>
                        </div>

                        <!-- Deduction Day -->
                        <div class="col-md-6 fv-row deduction-day-field-{{ $advance->id }}" 
                            style="{{ in_array($advance->deduction_frequency, ['weekly', 'monthly', 'yearly']) ? '' : 'display: none;' }}">
                            <label class="fs-6 fw-semibold mb-2 text-start">{{ __('payments.deduction_day') }}</label>
                            <select name="deduction_day" class="form-select form-select-solid" 
                                    style="direction: ltr; text-align: left; background-position: right 0.75rem center !important;">
                                <option value="">{{ __('payments.select_day') }}</option>
                                @for($i = 1; $i <= 31; $i++)
                                    <option value="{{ $i }}" {{ $advance->deduction_day == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            <div class="text-muted fs-7 mt-1 text-start">{{ __('payments.deduction_day_help') }}</div>
                        </div>

                        <!-- Deduction Start Date -->
                        <div class="col-md-6 fv-row">
                            <label class="fs-6 fw-semibold mb-2 text-start">{{ __('payments.deduction_start_date') }}</label>
                            <input type="date" name="deduction_start_date" class="form-control form-control-solid" 
                                   value="{{ $advance->deduction_start_date ? $advance->deduction_start_date->format('Y-m-d') : '' }}">
                            <div class="text-muted fs-7 mt-1 text-start">{{ __('payments.deduction_start_help') }}</div>
                        </div>

                        <!-- Applicable Salary Types -->
                        <div class="col-12">
                            <label class="fs-6 fw-semibold mb-3 text-start">{{ __('payments.applicable_to_salary_types') }}</label>
                            <div class="row text-start">
                                @foreach(['salary', 'allowance', 'bonus', 'overtime'] as $type)
                                <div class="col-md-3">
                                    <div class="form-check form-check-custom form-check-solid mb-3">
                                        <input class="form-check-input" type="checkbox" 
                                               name="applicable_salary_types[]" value="{{ $type }}" 
                                               id="edit_salary_type_{{ $advance->id }}_{{ $type }}"
                                               {{ in_array($type, $advance->applicable_salary_types ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="edit_salary_type_{{ $advance->id }}_{{ $type }}">
                                            {{ __('payments.' . $type) }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <div class="text-muted fs-7 text-start">{{ __('payments.applicable_salary_help') }}</div>
                        </div>

                        <!-- Purpose -->
                        <div class="col-md-12">
                            <label class="fs-6 fw-semibold mb-2 text-start">{{ __('payments.purpose') }}</label>
                            <input type="text" name="purpose" class="form-control form-control-solid" 
                                   value="{{ $advance->purpose }}" placeholder="{{ __('payments.purpose_placeholder') }}">
                        </div>

                        <!-- Reason -->
                        <div class="col-md-12 fv-row">
                            <label class="required fs-6 fw-semibold mb-2 text-start">{{ __('payments.reason') }}</label>
                            <textarea name="reason" class="form-control form-control-solid" rows="3" required>{{ $advance->reason }}</textarea>
                            <div id="reason{{ $advance->id }}" class="fv-plugins-message-container text-start"></div>
                        </div>

                        <!-- Notes -->
                        <div class="col-md-12">
                            <label class="fs-6 fw-semibold mb-2 text-start">{{ __('payments.notes') }}</label>
                            <textarea name="notes" class="form-control form-control-solid" rows="2">{{ $advance->notes }}</textarea>
                        </div>
                    </div>

                    <div class="text-center pt-15">
                        <button type="reset" id="closeModalEditButton{{ $advance->id }}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="editInstanceLoopAdvance({{$advance->id }})" id="editAdvanceButton{{ $advance->id }}" type="button" class="btn btn-primary">
                            <span class="indicator-label">{{__('auth._update')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#deduction_frequency').on('change', function() {
            var frequency = $(this).val();
            
            if (frequency === 'one_time') {
                $('#installments_field').hide();
                $('#deduction_day_field').hide();
                $('input[name="installments"]').removeAttr('required');
            } else {
                $('#installments_field').show();
                $('#deduction_day_field').show();
                $('input[name="installments"]').attr('required', 'required');
            }
        });
    });
</script>
@endif

<!-- Approve Modal -->
@if($advance->status === 'pending' && auth()->user()->can('approve employee advance'))
<div class="modal fade" id="approveAdvanceModal{{ $advance->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.approve_advance') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <div class="text-center">
                    <!-- Icon -->
                    <i class="ki-duotone ki-check-circle fs-3x text-success mb-5"></i>
                    
                    <!-- Title -->
                    <h3 class="fw-bold mb-3">{{ __('payments.confirm_approve') }}</h3>
                    
                    <!-- Message -->
                    <p class="text-muted fw-semibold mb-7">{{ __('payments.confirm_approve_message') }}</p>
                    
                    <!-- Employee Summary -->
                    <div class="d-flex align-items-center justify-content-center mb-7">
                        <div class="symbol symbol-40px symbol-circle me-3">
                            <div class="symbol-label bg-light-primary text-primary fw-bold">
                                {{ strtoupper(substr($advance->employee->first_name, 0, 1)) }}{{ strtoupper(substr($advance->employee->last_name, 0, 1)) }}
                            </div>
                        </div>
                        <div class="text-start">
                            <span class="fw-bold d-block">{{ $advance->employee->first_name }} {{ $advance->employee->last_name }}</span>
                            <span class="text-muted">{{ number_format($advance->advance_amount, 2) }} {{ currency_symbol() }}</span>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="mb-7 text-start">
                        <label class="required fw-semibold fs-6 mb-3">{{ __('payments.disbursement_method') }}</label>
                        <select name="payment_method_id" id="approve_payment_method_{{ $advance->id }}" 
                                class="form-select form-select-solid" data-control="select2" data-hide-search="true" required>
                            <option value="">{{ __('payments.select_payment_method') }}</option>
                            @foreach($active_payment_methods as $method)
                                <option value="{{ $method->id }}" {{ $method->is_default ? 'selected' : '' }}>
                                    {{ $method->name }} 
                                    @if($method->is_default)
                                        ({{ __('payments._default') }})
                                    @endif
                                    @if($method->type === 'bank_account')
                                        - {{ $method->account_number ?? '' }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div id="payment_method_error_{{ $advance->id }}" class="text-danger mt-2 d-none"></div>
                    </div>

                    <!-- Advance Details Summary -->
                    <div class="bg-light-warning rounded p-4 mb-7 text-start">
                        <div class="row">
                            <div class="col-6">
                                <span class="text-muted d-block">{{ __('payments.advance_amount') }}</span>
                                <span class="fw-bold fs-5">{{ number_format($advance->advance_amount, 2) }} {{ currency_symbol() }}</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">{{ __('payments.deduction_frequency') }}</span>
                                <span class="fw-bold fs-5">{{ $advance->deduction_frequency_label }}</span>
                            </div>
                            @if($advance->purpose)
                            <div class="col-12 mt-3">
                                <span class="text-muted d-block">{{ __('payments.purpose') }}</span>
                                <span class="fw-bold">{{ $advance->purpose }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                        <button onclick="confirmApproveAdvance({{ $advance->id }})" 
                                id="approveAdvanceButton{{ $advance->id }}" 
                                class="btn btn-success">
                            <span class="indicator-label">{{ __('payments.approve_and_disburse') }}</span>
                            <span class="indicator-progress" style="display: none;">
                                {{ __('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Reject Modal -->
@if($advance->status === 'pending' && auth()->user()->can('reject employee advance'))
<div class="modal fade" id="rejectAdvanceModal{{ $advance->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered mw-500px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.reject_advance') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <div class="text-center mb-7">
                    <i class="ki-duotone ki-cross-circle fs-3x text-danger mb-5"></i>
                    <h3 class="fw-bold mb-3">{{ __('payments.confirm_reject') }}</h3>
                    <p class="text-muted">{{ __('payments.confirm_reject_message') }}</p>
                </div>
                
                <!-- Rejection Reason -->
                <div class="mb-7">
                    <label class="required fs-6 fw-semibold mb-3">{{ __('payments.rejection_reason') }}</label>
                    <textarea id="rejection_reason_{{ $advance->id }}" class="form-control form-control-solid" 
                              rows="4" placeholder="{{ __('payments.rejection_reason') }}"></textarea>
                    <div id="rejection_reason_error_{{ $advance->id }}" class="text-danger mt-2 d-none"></div>
                </div>
                
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                    <button onclick="confirmRejectAdvance({{ $advance->id }})" 
                            id="rejectAdvanceButton{{ $advance->id }}" 
                            class="btn btn-danger">
                        <span class="indicator-label">{{ __('payments.reject') }}</span>
                        <span class="indicator-progress" style="display: none;">
                            {{ __('auth.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Cancel Modal -->
@if(in_array($advance->status, ['approved', 'partially_paid']) && auth()->user()->can('cancel employee advance'))
<div class="modal fade" id="cancelAdvanceModal{{ $advance->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-450px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.cancel_advance') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <div class="text-center">
                    <i class="ki-duotone ki-close-circle fs-3x text-warning mb-5"></i>
                    <h3 class="fw-bold mb-3">{{ __('payments.confirm_cancel') }}</h3>
                    <p class="text-muted fw-semibold mb-7">{{ __('payments.confirm_cancel_message') }}</p>
                    
                    <!-- Advance Summary -->
                    <div class="bg-light-warning rounded p-5 mb-7">
                        <div class="row">
                            <div class="col-6 text-start">
                                <span class="text-muted">{{ __('payments.advance_amount') }}</span>
                                <span class="fw-bold d-block">{{ number_format($advance->advance_amount, 2) }}</span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-muted">{{ __('payments.remaining') }}</span>
                                <span class="fw-bold d-block text-warning">{{ number_format($advance->remaining_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                        <button onclick="confirmCancelAdvance({{ $advance->id }})" 
                                id="cancelAdvanceButton{{ $advance->id }}" 
                                class="btn btn-warning">
                            <span class="indicator-label">{{ __('payments.cancel') }}</span>
                            <span class="indicator-progress" style="display: none;">
                                {{ __('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Delete Modal (only for pending) -->
@if($advance->status === 'pending' && auth()->user()->can('delete employee advance'))
<div class="modal fade" id="deleteAdvanceModal{{ $advance->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-450px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('payments.delete_advance') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <div class="text-center">
                    <i class="ki-duotone ki-trash fs-3x text-danger mb-5"></i>
                    <h3 class="fw-bold mb-3">{{ __('payments.confirm_delete') }}</h3>
                    <p class="text-muted fw-semibold mb-7">{{ __('payments.confirm_delete_message') }}</p>
                    
                    <!-- Warning -->
                    <div class="bg-light-danger rounded p-5 mb-7">
                        <span class="text-danger fw-bold">
                            <i class="ki-duotone ki-information fs-2 me-2"></i>
                            {{ __('payments.delete_warning') }}
                        </span>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                        <button onclick="confirmDeleteAdvance({{ $advance->id }})" 
                                id="deleteAdvanceButton{{ $advance->id }}" 
                                class="btn btn-danger">
                            <span class="indicator-label">{{ __('payments.delete') }}</span>
                            <span class="indicator-progress" style="display: none;">
                                {{ __('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif