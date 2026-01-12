<!-- resources/views/procurement/employee-payment/edit.blade.php -->
@can('edit employee payment')
<div class="modal fade" id="editPaymentModal{{$payment->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('payments.edit_payment') }} - {{ $payment->id }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPaymentForm{{$payment->id}}" class="form">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Employee -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.employee') }} *</label>
                            <select name="employee_id" class="form-select" required 
                                    @if($payment->status === 'completed') disabled @endif>
                                <option value="">{{ __('payments.select_employee') }}</option>
                                @foreach($active_employees as $employee)
                                    <option value="{{ $employee->id }}" 
                                            {{ $payment->employee_id == $employee->id ? 'selected' : '' }}>
                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="employee_id{{ $payment->id }}"></div>
                            @if($payment->status === 'completed')
                                <input type="hidden" name="employee_id" value="{{ $payment->employee_id }}">
                            @endif
                        </div>

                        <!-- Payment Date -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.payment_date') }} *</label>
                            <input type="date" name="payment_date" class="form-control" required 
                                   value="{{ $payment->payment_date->format('Y-m-d') }}"
                                   @if($payment->status === 'completed') readonly @endif>
                            <div id="payment_date{{ $payment->id }}"></div>
                        </div>

                        <!-- Payment Type -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.payment_type') }} *</label>
                            @if($payment->status === 'completed')
                                <!-- For completed payments, show readonly field with hidden input -->
                                <input type="text" 
                                    class="form-control" 
                                    value="{{ __('payments.' . $payment->payment_type) }}" 
                                    readonly
                                    style="background-color: #f8f9fa; cursor: not-allowed;">
                                <input type="hidden" name="payment_type" value="{{ $payment->payment_type }}">
                            @else
                                <!-- For non-completed payments, show select dropdown -->
                                <select name="payment_type" class="form-select" required>
                                    <option value="">{{ __('payments.select_payment_type') }}</option>
                                    <option value="salary" {{ old('payment_type', $payment->payment_type) == 'salary' ? 'selected' : '' }}>
                                        {{ __('payments.salary') }}
                                    </option>
                                    <option value="allowance" {{ old('payment_type', $payment->payment_type) == 'allowance' ? 'selected' : '' }}>
                                        {{ __('payments.allowance') }}
                                    </option>
                                    <option value="bonus" {{ old('payment_type', $payment->payment_type) == 'bonus' ? 'selected' : '' }}>
                                        {{ __('payments.bonus') }}
                                    </option>
                                    <option value="overtime" {{ old('payment_type', $payment->payment_type) == 'overtime' ? 'selected' : '' }}>
                                        {{ __('payments.overtime') }}
                                    </option>
                                    <option value="advance" {{ old('payment_type', $payment->payment_type) == 'advance' ? 'selected' : '' }}>
                                        {{ __('payments.advance') }}
                                    </option>
                                    <option value="other" {{ old('payment_type', $payment->payment_type) == 'other' ? 'selected' : '' }}>
                                        {{ __('payments.other') }}
                                    </option>
                                </select>
                            @endif
                            <div id="payment_type{{ $payment->id }}"></div>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.payment_method') }} *</label>
                            
                            @if(isset($payment) && $payment->status === 'completed')
                                <!-- For completed payments, show readonly field -->
                                <input type="text" 
                                    class="form-control" 
                                    value="{{ $payment->paymentMethod->name ?? 'N/A' }}" 
                                    readonly
                                    style="background-color: #f8f9fa; cursor: not-allowed;">
                                <input type="hidden" name="payment_method_id" value="{{ $payment->payment_method_id }}">
                            @else
                                <!-- For non-completed payments, show select dropdown with active payment methods -->
                                <select name="payment_method_id" class="form-select" required>
                                    <option value="">{{ __('payments.select_payment_method') }}</option>
                                    @foreach($active_payment_methods as $method)
                                        <option value="{{ $method->id }}" 
                                            {{ old('payment_method_id', isset($payment) ? $payment->payment_method_id : '') == $method->id ? 'selected' : '' }}>
                                            {{ $method->name }}
                                            @if($method->is_default)
                                                ({{ __('payments._default') }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            <div id="payment_method_id{{ $payment->id }}"></div>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.amount') }} *</label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required
                                   value="{{ $payment->amount }}"
                                   @if($payment->status === 'completed') readonly @endif>
                            <div id="amount{{ $payment->id }}"></div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.status') }} *</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" {{ $payment->status == 'pending' ? 'selected' : '' }}>
                                    {{ __('payments.pending') }}
                                </option>
                                <option value="completed" {{ $payment->status == 'completed' ? 'selected' : '' }}>
                                    {{ __('payments.completed') }}
                                </option>
                                <option value="failed" {{ $payment->status == 'failed' ? 'selected' : '' }}>
                                    {{ __('payments.failed') }}
                                </option>
                                <option value="cancelled" {{ $payment->status == 'cancelled' ? 'selected' : '' }}>
                                    {{ __('payments.cancelled') }}
                                </option>
                            </select>
                            <div id="status{{ $payment->id }}"></div>
                        </div>

                        <!-- Hours Worked & Hourly Rate (for overtime) -->
                        @php
                            $isOvertime = $payment->payment_type === 'overtime';
                        @endphp
                        <div class="col-md-6 overtime-fields" style="display: {{ $isOvertime ? 'block' : 'none' }};">
                            <label class="form-label">{{ __('payments.hours_worked') }}</label>
                            <input type="number" name="hours_worked" class="form-control" step="0.01" min="0"
                                   value="{{ $payment->hours_worked ?? 0 }}"
                                   @if($payment->status === 'completed') readonly @endif>
                            <div id="hours_worked{{ $payment->id }}"></div>
                        </div>
                        <div class="col-md-6 overtime-fields" style="display: {{ $isOvertime ? 'block' : 'none' }};">
                            <label class="form-label">{{ __('payments.hourly_rate') }}</label>
                            <input type="number" name="hourly_rate" class="form-control" step="0.01" min="0"
                                   value="{{ $payment->hourly_rate ?? 0 }}"
                                   @if($payment->status === 'completed') readonly @endif>
                            <div id="hourly_rate{{ $payment->id }}"></div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.description') }} *</label>
                            <textarea name="description" class="form-control" rows="2" required
                                      @if($payment->status === 'completed') readonly @endif>{{ $payment->description }}</textarea>
                            <div id="description{{ $payment->id }}"></div>
                        </div>

                        <!-- Pay Period -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.pay_period_start') }}</label>
                            <input type="date" name="pay_period_start" class="form-control"
                                   value="{{ $payment->pay_period_start ? $payment->pay_period_start->format('Y-m-d') : '' }}"
                                   @if($payment->status === 'completed') readonly @endif>
                            <div id="pay_period_start{{ $payment->id }}"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.pay_period_end') }}</label>
                            <input type="date" name="pay_period_end" class="form-control"
                                   value="{{ $payment->pay_period_end ? $payment->pay_period_end->format('Y-m-d') : '' }}"
                                   @if($payment->status === 'completed') readonly @endif>
                            <div id="pay_period_end{{ $payment->id }}"></div>
                        </div>

                        <!-- Reference Number -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.reference_number') }}</label>
                            <input type="text" name="reference_number" class="form-control"
                                   value="{{ $payment->reference_number }}"
                                   @if($payment->status === 'completed') readonly @endif>
                            <div id="reference_number{{ $payment->id }}"></div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"
                                      @if($payment->status === 'completed') readonly @endif>{{ $payment->notes }}</textarea>
                            <div id="notes{{ $payment->id }}"></div>
                        </div>

                        @if($payment->status === 'completed')
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    {{ __('payments.cannot_edit_completed') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $payment->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                    <button onclick="editEmployeePayment({{$payment->id }})" id="editEmployeePaymentButton{{ $payment->id }}" type="button" class="btn btn-primary"
                            @if($payment->status === 'completed') disabled @endif>
                        <span class="indicator-label">{{__('auth._update')}}</span>
                        <span class="indicator-progress">{{__('auth.please_wait')}}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


@endcan