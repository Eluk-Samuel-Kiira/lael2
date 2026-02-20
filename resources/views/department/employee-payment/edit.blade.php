<!-- resources/views/procurement/employee-payment/edit.blade.php -->
@can('edit employee payment')
@php
    // Decode applied_taxes if it's a string (JSON from database)
    $appliedTaxesArray = null;
    if ($payment->applied_taxes) {
        if (is_string($payment->applied_taxes)) {
            $appliedTaxesArray = json_decode($payment->applied_taxes, true);
        } elseif (is_array($payment->applied_taxes)) {
            $appliedTaxesArray = $payment->applied_taxes;
        }
    }
    
    // Ensure it's an array
    if (!is_array($appliedTaxesArray)) {
        $appliedTaxesArray = [];
    }
    
    $hasAppliedTaxes = !empty($appliedTaxesArray);
    
    // Get applied tax IDs for checkboxes
    $appliedTaxIds = collect($appliedTaxesArray)->pluck('tax_id')->filter()->toArray();
@endphp

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
                            <select name="employee_id" class="form-select" required id="edit_employee_select_{{ $payment->id }}"
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
                                <input type="text" 
                                    class="form-control" 
                                    value="{{ __('payments.' . $payment->payment_type) }}" 
                                    readonly
                                    style="background-color: #f8f9fa; cursor: not-allowed;">
                                <input type="hidden" name="payment_type" value="{{ $payment->payment_type }}">
                            @else
                                <select name="payment_type" class="form-select" required id="edit_payment_type_{{ $payment->id }}">
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
                                <input type="text" 
                                    class="form-control" 
                                    value="{{ $payment->paymentMethod->name ?? 'N/A' }}" 
                                    readonly
                                    style="background-color: #f8f9fa; cursor: not-allowed;">
                                <input type="hidden" name="payment_method_id" value="{{ $payment->payment_method_id }}">
                            @else
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

                        <!-- Gross Amount -->
                        <div class="col-md-4">
                            <label class="form-label">{{ __('payments.gross_amount') }} *</label>
                            <input type="number" name="gross_amount" id="edit_gross_amount_{{ $payment->id }}" 
                                class="form-control" step="0.01" min="0.01" required
                                value="{{ old('gross_amount', $payment->gross_amount ?? $payment->amount) }}"
                                onchange="editCalculateTaxPreview({{ $payment->id }})"
                                @if($payment->status === 'completed') readonly @endif>
                            <div id="gross_amount{{ $payment->id }}"></div>
                            @if($payment->status !== 'completed')
                                <span class="text-muted fs-7">{{ __('payments.gross_amount_help') }}</span>
                            @endif
                        </div>

                        <!-- Total Tax Amount (Display Only) -->
                        <div class="col-md-4">
                            <label class="form-label">{{ __('payments.total_tax_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" 
                                    class="form-control bg-light" 
                                    id="edit_display_total_tax_{{ $payment->id }}"
                                    value="{{ number_format($payment->total_tax_amount ?? 0, 2) }}"
                                    readonly 
                                    style="background-color: #f8f9fa; font-weight: bold; color: #dc3545;">
                            </div>
                            <!-- This hidden input will be removed by JavaScript so it doesn't override server calculation -->
                            <input type="hidden" name="total_tax_amount" id="edit_total_tax_{{ $payment->id }}" value="{{ $payment->total_tax_amount ?? 0 }}">
                        </div>

                        <!-- Net Amount (Display Only) -->
                        <div class="col-md-4">
                            <label class="form-label">{{ __('payments.net_amount_paid') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="text" 
                                    class="form-control bg-light" 
                                    id="edit_display_net_amount_{{ $payment->id }}"
                                    value="{{ number_format($payment->net_amount ?? $payment->amount, 2) }}"
                                    readonly 
                                    style="background-color: #f8f9fa; font-weight: bold; color: #198754;">
                            </div>
                            <!-- This hidden input will be removed by JavaScript so it doesn't override server calculation -->
                            <input type="hidden" name="net_amount" id="edit_net_amount_{{ $payment->id }}" value="{{ $payment->net_amount ?? $payment->amount }}">
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.status') }} *</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" {{ $payment->status == 'pending' ? 'selected' : '' }}>
                                    {{ __('payments.pending') }}
                                </option>
                                {{--<option value="completed" {{ $payment->status == 'completed' ? 'selected' : '' }}>
                                    {{ __('payments.completed') }}
                                </option>--}}
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
                        <div class="col-md-6 edit-overtime-fields-{{ $payment->id }}" style="display: {{ $isOvertime ? 'block' : 'none' }};">
                            <label class="form-label">{{ __('payments.hours_worked') }}</label>
                            <input type="number" name="hours_worked" class="form-control" step="0.01" min="0"
                                   id="edit_hours_worked_{{ $payment->id }}"
                                   value="{{ $payment->hours_worked ?? 0 }}"
                                   onchange="editCalculateTaxPreview({{ $payment->id }})"
                                   @if($payment->status === 'completed') readonly @endif>
                            <div id="hours_worked{{ $payment->id }}"></div>
                        </div>
                        <div class="col-md-6 edit-overtime-fields-{{ $payment->id }}" style="display: {{ $isOvertime ? 'block' : 'none' }};">
                            <label class="form-label">{{ __('payments.hourly_rate') }}</label>
                            <input type="number" name="hourly_rate" class="form-control" step="0.01" min="0"
                                   id="edit_hourly_rate_{{ $payment->id }}"
                                   value="{{ $payment->hourly_rate ?? 0 }}"
                                   onchange="editCalculateTaxPreview({{ $payment->id }})"
                                   @if($payment->status === 'completed') readonly @endif>
                            <div id="hourly_rate{{ $payment->id }}"></div>
                        </div>

                        <!-- TAX SECTION -->
                        <div class="col-12">
                            <div class="card card-flush {{ $payment->status === 'completed' ? 'bg-light' : 'bg-light' }}">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-calculator text-primary me-2"></i>
                                        {{ __('payments.applicable_taxes') }}
                                    </h3>
                                    @if($payment->status !== 'completed' && isset($taxes) && is_countable($taxes) && count($taxes) > 0)
                                        <div class="card-toolbar">
                                            <button type="button" class="btn btn-sm btn-primary" onclick="editCalculateTaxPreview({{ $payment->id }})">
                                                <i class="fas fa-chart-pie me-2"></i>
                                                {{ __('payments.preview_calculation') }}
                                            </button>
                                        </div>
                                    @endif
                                </div>
                                <div class="card-body">
                                    @if($payment->status === 'completed')
                                        <!-- COMPLETED PAYMENT: Show applied taxes as read-only list -->
                                        @if($hasAppliedTaxes)
                                            <div class="row">
                                                @foreach($appliedTaxesArray as $tax)
                                                    @php
                                                        $taxName = $tax['tax_name'] ?? $tax['name'] ?? 'Tax';
                                                        $taxRate = $tax['rate'] ?? 0;
                                                        $taxType = $tax['type'] ?? 'percentage';
                                                        $taxAmount = $tax['amount'] ?? 0;
                                                        $taxCode = $tax['tax_code'] ?? $tax['code'] ?? '';
                                                    @endphp
                                                    <div class="col-md-4 mb-3">
                                                        <div class="d-flex align-items-center p-3 border rounded bg-white">
                                                            <div class="symbol symbol-40px symbol-circle bg-light-primary me-3">
                                                                <i class="fas fa-receipt text-primary fs-3"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <span class="fw-bold d-block">{{ $taxName }}</span>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="badge badge-light-info">
                                                        @if($taxType === 'percentage')
                                                                            {{ number_format($taxRate, 2) }}%
                                                        @else
                                                                            ${{ number_format($taxRate, 2) }}
                                                        @endif
                                                                    </span>
                                                                    <span class="fw-bold text-danger">${{ number_format($taxAmount, 2) }}</span>
                                                                </div>
                                                        @if($taxCode)
                                                                    <small class="text-muted d-block">{{ $taxCode }}</small>
                                                        @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="alert alert-info d-flex align-items-center mb-0">
                                                <i class="fas fa-info-circle fs-3 me-3"></i>
                                                <div>
                                                    {{ __('payments.no_taxes_applied_for_this_payment') }}
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        <!-- EDITABLE PAYMENT: Show tax checkboxes -->
                                        @if(isset($taxes) && is_countable($taxes) && count($taxes) > 0)
                                            <div class="row">
                                                @foreach($taxes as $tax)
                                                    <div class="col-md-4 mb-3">
                                                        <div class="form-check form-check-custom form-check-solid">
                                                            <input class="form-check-input edit-tax-checkbox-{{ $payment->id }}" 
                                                                type="checkbox" 
                                                                name="selected_taxes[]" 
                                                                value="{{ $tax['id'] }}"
                                                                id="edit_tax_{{ $payment->id }}_{{ $tax['id'] }}"
                                                                data-rate="{{ $tax['rate'] }}"
                                                                data-type="{{ $tax['type'] }}"
                                                                data-name="{{ $tax['name'] }}"
                                                                data-code="{{ $tax['code'] }}"
                                                                {{ in_array($tax['id'], $appliedTaxIds) ? 'checked' : '' }}
                                                                onchange="editCalculateTaxPreview({{ $payment->id }})">
                                                            <label class="form-check-label" for="edit_tax_{{ $payment->id }}_{{ $tax['id'] }}">
                                                                <span class="fw-bold">{{ $tax['name'] }}</span>
                                                                <br>
                                                                <span class="badge badge-light-info">{{ $tax['display_rate'] }}</span>
                                                                <small class="text-muted d-block">{{ $tax['code'] }}</small>
                                                            </label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="alert alert-warning d-flex align-items-center mb-0">
                                                <i class="fas fa-exclamation-triangle fs-3 me-3"></i>
                                                <div>
                                                    {{ __('payments.no_active_taxes') }}
                                                    <a href="{{ route('tax.index') }}" class="alert-link">{{ __('payments.configure_taxes') }}</a>
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Tax Calculation Preview -->
                        <div id="edit_tax_preview_{{ $payment->id }}" class="col-12 mt-4 p-4 bg-white rounded {{ !$hasAppliedTaxes ? 'd-none' : '' }}">
                            
                            <h4 class="mb-3">
                                <i class="fas fa-file-invoice text-primary me-2"></i>
                                {{ __('payments.calculation_summary') }}
                                @if($payment->status === 'completed')
                                    <span class="badge badge-secondary ms-2">{{ __('payments.completed') }}</span>
                                @endif
                            </h4>
                            
                            <!-- Summary Cards -->
                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <div class="card card-dashed">
                                        <div class="card-body p-4">
                                            <span class="text-muted fw-bold d-block">{{ __('payments.gross_amount') }}</span>
                                            <span class="text-dark fw-bolder fs-2" id="edit_preview_gross_{{ $payment->id }}">
                                                ${{ number_format($payment->gross_amount ?? $payment->amount, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card card-dashed">
                                        <div class="card-body p-4">
                                            <span class="text-muted fw-bold d-block">{{ __('payments.total_tax') }}</span>
                                            <span class="text-danger fw-bolder fs-2" id="edit_preview_tax_{{ $payment->id }}">
                                                ${{ number_format($payment->total_tax_amount ?? 0, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card card-dashed">
                                        <div class="card-body p-4">
                                            <span class="text-muted fw-bold d-block">{{ __('payments.net_amount') }}</span>
                                            <span class="text-success fw-bolder fs-2" id="edit_preview_net_{{ $payment->id }}">
                                                ${{ number_format($payment->net_amount ?? $payment->amount, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tax Breakdown Table -->
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                    <thead>
                                        <tr class="fw-bold text-muted">
                                            <th>{{ __('payments.tax_name') }}</th>
                                            <th>{{ __('payments.rate') }}</th>
                                            <th class="text-end">{{ __('payments.amount') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="edit_tax_breakdown_body_{{ $payment->id }}">
                                        @if($hasAppliedTaxes)
                                            @foreach($appliedTaxesArray as $tax)
                                                @php
                                                    $taxName = $tax['tax_name'] ?? $tax['name'] ?? 'Tax';
                                                    $taxRate = $tax['rate'] ?? 0;
                                                    $taxType = $tax['type'] ?? 'percentage';
                                                    $taxAmount = $tax['amount'] ?? 0;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <span class="fw-bold">{{ $taxName }}</span>
                                                        @if($payment->status === 'completed')
                                                            <i class="fas fa-lock text-muted ms-2 fs-7" title="{{ __('payments.completed_payment_tax') }}"></i>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-info">
                                                            @if($taxType === 'percentage')
                                                                {{ number_format($taxRate, 2) }}%
                                                            @else
                                                                ${{ number_format($taxRate, 2) }}
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td class="text-end fw-bold">${{ number_format($taxAmount, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">
                                                    {{ __('payments.no_taxes_applied') }}
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                    @if($hasAppliedTaxes)
                                        <tfoot>
                                            <tr class="fw-bold bg-light">
                                                <td colspan="2" class="text-end">{{ __('payments.total_tax') }}:</td>
                                                <td class="text-end text-danger" id="edit_footer_total_tax_{{ $payment->id }}">
                                                    ${{ number_format($payment->total_tax_amount ?? 0, 2) }}
                                                </td>
                                            </tr>
                                            <tr class="fw-bold bg-light">
                                                <td colspan="2" class="text-end">{{ __('payments.net_amount') }}:</td>
                                                <td class="text-end text-success" id="edit_footer_net_amount_{{ $payment->id }}">
                                                    ${{ number_format($payment->net_amount ?? $payment->amount, 2) }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                            
                            @if($payment->status === 'completed')
                                <div class="alert alert-info mt-3 mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    {{ __('payments.completed_payment_tax_note') }}
                                </div>
                            @endif
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