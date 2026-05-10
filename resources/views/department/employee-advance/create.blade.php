<!-- Advance Request/Creation Modal -->
<div class="modal fade" id="kt_modal_add_advance" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('payments.request_advance') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="kt_modal_add_advance_form" class="form" method="POST" action="{{ route('employee-advance.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Employee Selection -->
                         <div class="col-md-6">
                            <label class="form-label">{{ __('payments.employee') }} *</label>
                            @php
                                $formattedEmployees = [];
                                foreach($active_employees as $employee) {
                                    $formattedEmployees[] = (object)[
                                        'id' => $employee->id,
                                        'name' => $employee->first_name . ' ' . $employee->last_name
                                    ];
                                }
                            @endphp
                            <x-typable-select 
                                name="employee_id"
                                :options="$formattedEmployees"
                                selected="{{ old('employee_id', $item->employee_id ?? '') }}"
                                placeholder="Type or select employee..."
                                required="true"
                            />
                            <div id="employee_id"></div>
                        </div>

                        <!-- Advance Amount -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.advance_amount') }} *</label>
                            <div class="input-group">
                                <input type="number" name="advance_amount" class="form-control" step="0.01" min="0.01" required>
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                            </div>
                        </div>

                        <!-- Advance Date -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.advance_date') }} *</label>
                            <input type="date" name="advance_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <!-- Deduction Frequency -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.deduction_frequency') }} *</label>
                            <select name="deduction_frequency" class="form-select" required id="deduction_frequency">
                                <option value="one_time">{{ __('payments.one_time') }}</option>
                                <option value="monthly">{{ __('payments.monthly') }}</option>
                                <option value="weekly">{{ __('payments.weekly') }}</option>
                                <option value="yearly">{{ __('payments.yearly') }}</option>
                            </select>
                        </div>

                        <!-- Installments (for recurring) -->
                        <div class="col-md-6" id="installments_field" style="display: none;">
                            <label class="form-label">{{ __('payments.number_of_installments') }} *</label>
                            <input type="number" name="installments" class="form-control" min="2" max="12" value="2">
                        </div>

                        <!-- Deduction Day (for recurring) -->
                        <div class="col-md-6" id="deduction_day_field" style="display: none;">
                            <label class="form-label">{{ __('payments.deduction_day') }}</label>
                            <select name="deduction_day" class="form-select">
                                <option value="">{{ __('payments.select_day') }}</option>
                                @for($i = 1; $i <= 31; $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                            <span class="text-muted fs-7">{{ __('payments.deduction_day_help') }}</span>
                        </div>

                        <!-- Deduction Start Date -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.deduction_start_date') }}</label>
                            <input type="date" name="deduction_start_date" class="form-control">
                            <span class="text-muted fs-7">{{ __('payments.deduction_start_help') }}</span>
                        </div>

                        <!-- Applicable Salary Types -->
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.applicable_to_salary_types') }}</label>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="applicable_salary_types[]" value="salary" id="apply_salary">
                                        <label class="form-check-label" for="apply_salary">{{ __('payments.salary') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="applicable_salary_types[]" value="allowance" id="apply_allowance">
                                        <label class="form-check-label" for="apply_allowance">{{ __('payments.allowance') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="applicable_salary_types[]" value="bonus" id="apply_bonus">
                                        <label class="form-check-label" for="apply_bonus">{{ __('payments.bonus') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" name="applicable_salary_types[]" value="overtime" id="apply_overtime">
                                        <label class="form-check-label" for="apply_overtime">{{ __('payments.overtime') }}</label>
                                    </div>
                                </div>
                            </div>
                            <span class="text-muted fs-7">{{ __('payments.applicable_salary_help') }}</span>
                        </div>

                        <!-- Purpose/Reason -->
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.purpose') }}</label>
                            <input type="text" name="purpose" class="form-control" placeholder="{{ __('payments.purpose_placeholder') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">{{ __('payments.reason') }} *</label>
                            <textarea name="reason" class="form-control" rows="3" required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-hand-holding-usd me-2"></i>
                        {{ __('payments.submit_advance_request') }}
                    </button>
                </div>
            </form>
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