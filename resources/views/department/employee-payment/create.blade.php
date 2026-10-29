{{-- resources/views/department/employee-payment/create.blade.php --}}
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
                        {{-- Employee --}}
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.employee') }} *</label>
                            @php
                                $formattedEmployees = [];
                                foreach (($active_employees ?? collect()) as $employee) {
                                    $formattedEmployees[] = (object)[
                                        'id'   => $employee->id,
                                        'name' => $employee->first_name . ' ' . $employee->last_name,
                                    ];
                                }
                            @endphp
                            <x-typable-select
                                name="employee_id"
                                :options="$formattedEmployees"
                                placeholder="Type or select employee..."
                                required="true"
                            />
                            <div id="employee_id"></div>
                        </div>

                        {{-- Payment Date --}}
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.payment_date') }} *</label>
                            <input type="date" name="payment_date" class="form-control" required value="{{ date('Y-m-d') }}">
                            <div id="payment_date"></div>
                        </div>

                        {{-- Payment Type --}}
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.payment_type') }} *</label>
                            <select name="payment_type" class="form-select" required>
                                <option value="">{{ __('payments.select_payment_type') }}</option>
                                <option value="salary">{{ __('payments.salary') }}</option>
                                <option value="allowance">{{ __('payments.allowance') }}</option>
                                <option value="bonus">{{ __('payments.bonus') }}</option>
                                <option value="overtime">{{ __('payments.overtime') }}</option>
                                <option value="other">{{ __('payments.other') }}</option>
                            </select>
                            <div id="payment_type"></div>
                        </div>

                        {{-- Payment Method --}}
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.payment_method') }} *</label>
                            <select name="payment_method_id" class="form-select" required>
                                <option value="">{{ __('payments.select_payment_method') }}</option>
                                @foreach (($active_payment_methods ?? collect()) as $method)
                                    <option value="{{ $method->id }}" {{ $method->is_default ? 'selected' : '' }}>
                                        {{ $method->name }}@if ($method->is_default) ({{ __('payments._default') }})@endif
                                    </option>
                                @endforeach
                            </select>
                            <div id="payment_method_id"></div>
                        </div>

                        {{-- Gross Amount --}}
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.gross_amount') }} *</label>
                            <input type="number" name="gross_amount" class="form-control" step="0.01" min="0.01" required>
                            <div id="gross_amount"></div>
                            <span class="text-muted fs-7">{{ __('payments.gross_amount_help') }}</span>
                        </div>

                        {{-- Status --}}
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.status') }} *</label>
                            <select name="status" class="form-select" required>
                                <option value="pending">{{ __('payments.pending') }}</option>
                                <option value="failed">{{ __('payments.failed') }}</option>
                                <option value="cancelled">{{ __('payments.cancelled') }}</option>
                            </select>
                            <div id="status"></div>
                        </div>

                        {{-- Hours / Rate (overtime only) --}}
                        <div class="col-md-6 overtime-fields" style="display: none;">
                            <label class="form-label">{{ __('payments.hours_worked') }}</label>
                            <input type="number" name="hours_worked" class="form-control" step="0.01" min="0">
                        </div>
                        <div class="col-md-6 overtime-fields" style="display: none;">
                            <label class="form-label">{{ __('payments.hourly_rate') }}</label>
                            <input type="number" name="hourly_rate" class="form-control" step="0.01" min="0">
                        </div>

                        {{-- TAXES --}}
                        <div class="col-12">
                            <div class="card card-flush bg-light">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-calculator text-primary me-2"></i>
                                        {{ __('payments.applicable_taxes') }}
                                    </h3>
                                    <div class="card-toolbar">
                                        <button type="button" class="btn btn-sm btn-primary" id="btnPreviewTaxes">
                                            <i class="fas fa-chart-pie me-2"></i>
                                            {{ __('payments.preview_calculation') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    @if (isset($taxes) && is_countable($taxes) && count($taxes) > 0)
                                        <div class="row">
                                            @foreach ($taxes as $tax)
                                                <div class="col-md-4 mb-3">
                                                    <div class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input tax-checkbox"
                                                               type="checkbox"
                                                               name="selected_taxes[]"
                                                               value="{{ $tax['id'] }}"
                                                               id="tax_{{ $tax['id'] }}">
                                                        <label class="form-check-label" for="tax_{{ $tax['id'] }}">
                                                            <span class="fw-bold">{{ $tax['name'] }}</span><br>
                                                            <span class="badge badge-light-info">{{ $tax['display_rate'] }}</span>
                                                            <small class="text-muted d-block">{{ $tax['code'] }}</small>
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div id="tax_preview" class="mt-4 p-4 bg-white rounded d-none">
                                            <h4 class="mb-3">
                                                <i class="fas fa-file-invoice text-primary me-2"></i>
                                                {{ __('payments.calculation_summary') }}
                                            </h4>
                                            <div class="row g-4 mb-4">
                                                <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-4">
                                                    <span class="text-muted fw-bold d-block">{{ __('payments.gross_amount') }}</span>
                                                    <span class="text-dark fw-bolder fs-2" id="preview_gross">0.00</span>
                                                </div></div></div>
                                                <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-4">
                                                    <span class="text-muted fw-bold d-block">{{ __('payments.total_tax') }}</span>
                                                    <span class="text-danger fw-bolder fs-2" id="preview_tax">0.00</span>
                                                </div></div></div>
                                                <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-4">
                                                    <span class="text-muted fw-bold d-block">{{ __('payments.net_amount') }}</span>
                                                    <span class="text-success fw-bolder fs-2" id="preview_net">0.00</span>
                                                </div></div></div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                                    <thead>
                                                        <tr class="fw-bold text-muted">
                                                            <th>{{ __('payments.tax_name') }}</th>
                                                            <th>{{ __('payments.rate') }}</th>
                                                            <th class="text-end">{{ __('payments.amount') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tax_breakdown_body"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @else
                                        <div class="alert alert-warning d-flex align-items-center">
                                            <i class="fas fa-exclamation-triangle fs-3 me-3"></i>
                                            <div>{{ __('payments.no_active_taxes') }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="net_amount" value="0">
                        <input type="hidden" name="total_tax_amount" value="0">

                        {{-- ADVANCES --}}
                        <div class="col-12 mt-5">
                            <div class="card card-flush bg-light-warning">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-hand-holding-usd text-warning me-2"></i>
                                        {{ __('payments.advance_deductions') }}
                                    </h3>
                                    <div class="card-toolbar">
                                        <button type="button" class="btn btn-sm btn-warning" id="btnLoadAdvances">
                                            <i class="fas fa-sync me-2"></i>
                                            {{ __('payments.load_advances') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="advance_deductions_container" class="mb-3">
                                        <div class="text-center py-5 text-muted">
                                            <i class="fas fa-arrow-up fs-2 mb-3 d-block"></i>
                                            <span>{{ __('payments.select_employee_to_view_advances') }}</span>
                                        </div>
                                    </div>

                                    <div id="advance_deduction_preview" class="mt-4 p-4 bg-white rounded d-none">
                                        <h5 class="mb-3">{{ __('payments.advance_deduction_summary') }}</h5>
                                        <div class="row g-3">
                                            <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-3">
                                                <span class="text-muted fw-bold d-block">{{ __('payments.selected_advances') }}</span>
                                                <span class="text-warning fw-bolder fs-3" data-preview-advance-count>0</span>
                                            </div></div></div>
                                            <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-3">
                                                <span class="text-muted fw-bold d-block">{{ __('payments.total_deduction') }}</span>
                                                <span class="text-danger fw-bolder fs-3" data-preview-advance-total>0.00</span>
                                            </div></div></div>
                                            <div class="col-md-4"><div class="card card-dashed"><div class="card-body p-3">
                                                <span class="text-muted fw-bold d-block">{{ __('payments.amount_after_deduction') }}</span>
                                                <span class="text-success fw-bolder fs-3" data-preview-advance-after>0.00</span>
                                            </div></div></div>
                                        </div>
                                    </div>

                                    <input type="hidden" name="advance_deductions" value='[]'>
                                    <input type="hidden" name="total_advance_deduction" value="0">
                                </div>
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.description') }} *</label>
                            <textarea name="description" class="form-control" rows="2" required></textarea>
                            <div id="description"></div>
                        </div>

                        {{-- Pay period --}}
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.pay_period_start') }}</label>
                            <input type="date" name="pay_period_start" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.pay_period_end') }}</label>
                            <input type="date" name="pay_period_end" class="form-control">
                        </div>

                        {{-- Reference --}}
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payments.reference_number') }}</label>
                            <input type="text" name="reference_number" class="form-control">
                        </div>

                        {{-- Notes --}}
                        <div class="col-12">
                            <label class="form-label">{{ __('payments.notes') }}</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._cancel') }}</button>
                    <button id="submitEmplPaymentButton" type="button" class="btn btn-primary">
                        <span class="indicator-label"><i class="fas fa-check me-2"></i>{{ __('payments.create_payment') }}</span>
                        <span class="indicator-progress" style="display: none;">{{ __('auth.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

