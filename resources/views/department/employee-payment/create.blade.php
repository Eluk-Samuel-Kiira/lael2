<!-- resources/views/procurement/employee-payment/create.blade.php -->
<div class="modal fade" id="kt_modal_add_payment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('payments.add_new_payment') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="kt_modal_add_payment_form" class="form">
                @csrf
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Employee -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.employee') }} *</label>
                            <select name="employee_id" class="form-select" required>
                                <option value="">{{ __('payments.select_employee') }}</option>
                                @foreach($active_employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                                @endforeach
                            </select>
                            <div id="employee_id"></div>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.payment_date') }} *</label>
                            <input type="date" name="payment_date" class="form-control" required 
                                   value="{{ date('Y-m-d') }}">
                            <div id="payment_date"></div>
                        </div>

                        <!-- Payment Type -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.payment_type') }} *</label>
                            <select name="payment_type" class="form-select" required>
                                <option value="">{{ __('payments.select_payment_type') }}</option>
                                <option value="salary">{{ __('payments.salary') }}</option>
                                <option value="allowance">{{ __('payments.allowance') }}</option>
                                <option value="bonus">{{ __('payments.bonus') }}</option>
                                <option value="overtime">{{ __('payments.overtime') }}</option>
                                <option value="advance">{{ __('payments.advance') }}</option>
                                <option value="other">{{ __('payments.other') }}</option>
                            </select>
                            <div id="payment_type"></div>
                        </div>

                        <!-- Payment Method with auto-selected default -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.payment_method') }} *</label>
                            <select name="payment_method_id" class="form-select" required>
                                <option value="">{{ __('payments.select_payment_method') }}</option>
                                @foreach($active_payment_methods as $method)
                                    @php
                                        // Check if this is the default payment method
                                        $isDefault = $method->is_default;
                                        // Check if this method was previously selected
                                        $isSelected = old('payment_method_id') == $method->id;
                                        // Auto-select default if nothing else is selected
                                        $shouldSelect = $isSelected || (!$isSelected && $isDefault && !old('payment_method_id'));
                                    @endphp
                                    
                                    <option value="{{ $method->id }}" 
                                        {{ $shouldSelect ? 'selected' : '' }}>
                                        {{ $method->name }}
                                        @if($method->is_default)
                                            ({{ __('payments._default') }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div id="payment_method_id"></div>
                        </div>

                        <!-- Amount -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.amount') }} *</label>
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required>
                            <div id="amount"></div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.status') }} *</label>
                            <select name="status" class="form-select" required>
                                <option value="pending">{{ __('payments.pending') }}</option>
                                <option value="completed">{{ __('payments.completed') }}</option>
                                <option value="failed">{{ __('payments.failed') }}</option>
                                <option value="cancelled">{{ __('payments.cancelled') }}</option>
                            </select>
                            <div id="status"></div>
                        </div>

                        <!-- Hours Worked & Hourly Rate (for overtime) -->
                        <div class="col-md-6 overtime-fields" style="display: none;">
                            <label class="form-label">{{ __('payments.hours_worked') }}</label>
                            <input type="number" name="hours_worked" class="form-control" step="0.01" min="0">
                            <div id="hours_worked"></div>
                        </div>
                        <div class="col-md-6 overtime-fields" style="display: none;">
                            <label class="form-label">{{ __('payments.hourly_rate') }}</label>
                            <input type="number" name="hourly_rate" class="form-control" step="0.01" min="0">
                            <div id="hourly_rate"></div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.description') }} *</label>
                            <textarea name="description" class="form-control" rows="2" required></textarea>
                            <div id="description"></div>
                        </div>

                        <!-- Pay Period -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.pay_period_start') }}</label>
                            <input type="date" name="pay_period_start" class="form-control">
                            <div id="pay_period_start"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.pay_period_end') }}</label>
                            <input type="date" name="pay_period_end" class="form-control">
                            <div id="pay_period_end"></div>
                        </div>

                        <!-- Reference Number -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.reference_number') }}</label>
                            <input type="text" name="reference_number" class="form-control">
                            <div id="reference_number"></div>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                            <div id="notes"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="discardButton" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                    <button 
                        id="submitEmplPaymentButton" 
                        type="button" 
                        class="btn btn-primary"
                        onclick="submitFormEmployeePayment('kt_modal_add_payment_form', 'submitEmplPaymentButton', '{{ route('payment.store') }}', 'POST', 'discardButton')">
                        
                        <span class="indicator-label">{{__('payments.create_payment')}}</span>
                        <span class="indicator-progress">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

