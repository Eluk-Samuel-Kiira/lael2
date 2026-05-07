<!-- resources/views/procurement/expense/edit-expense.blade.php -->
<div class="modal fade" id="editExpenseModal{{$expense->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('auth.edit_expense') }} - {{ $expense->expense_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editExpenseForm{{$expense->id}}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-4">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('pagination.expense_date') }}</label>
                            <input type="date" name="date" class="form-control" required value="{{ old('date', $expense->date->format('Y-m-d')) }}">
                            <div id="date{{ $expense->id }}"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('pagination.category') }}</label>
                            <x-typable-select 
                                name="category_id"
                                :options="$expenseCategories"
                                selected="{{ $expense->category_id }}"
                                placeholder="Type or select category..."
                            />
                            <div id="category_id{{ $expense->id }}"></div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label required">{{ __('pagination.description') }}</label>
                            <textarea name="description" class="form-control" rows="2" required>{{ old('description', $expense->description) }}</textarea>
                            <div id="description{{ $expense->id }}"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{ __('passwords.supplier') }}</span>
                            </label>
                            <x-typable-select 
                                name="supplier_id"
                                :options="$suppliers"
                                selected="{{ $expense->supplier_id }}"
                                placeholder="Type or select supplier..."
                            />
                            <div id="supplier_id{{ $expense->id }}"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">{{ __('pagination.employee') }}</label>
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
                                selected="{{ $expense->employee_id }}"
                                placeholder="Type or select employee..."
                            />
                            <div id="employee_id{{ $expense->id }}"></div>
                        </div>
                        
                        <!-- Amount Information with Auto-calculation -->
                        <div class="col-md-4">
                            <label class="form-label required">{{ __('pagination.gross_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="gross_amount" class="form-control" step="0.01" min="0" required 
                                       value="{{ old('gross_amount', $expense->gross_amount) }}" 
                                       id="editGrossAmount{{$expense->id}}"
                                       oninput="calculateEditTotal({{$expense->id}})">
                                <div id="gross_amount{{ $expense->id }}"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">{{ __('pagination.tax_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" name="tax_amount" class="form-control" step="0.01" min="0" 
                                       value="{{ old('tax_amount', $expense->tax_amount) }}"
                                       id="editTaxAmount{{$expense->id}}"
                                       oninput="calculateEditTotal({{$expense->id}})">
                                <div id="tax_amount{{ $expense->id }}"></div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">{{ __('pagination.total_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="text" class="form-control bg-light" id="editTotalAmount{{$expense->id}}" readonly 
                                       value="{{ number_format($expense->total_amount, 2) }}">
                                <div id="editTotal{{ $expense->id }}"></div>
                            </div>
                        </div>
                        
                        <!-- Payment Information -->
                        <div class="col-md-6">
                            <label class="form-label">{{ __('pagination.payment_method') }}</label>
                            @if(isset($expense) && $expense->payment_status === 'paid' && $expense->payment_method_id)
                                <input type="text" 
                                    class="form-control" 
                                    value="{{ $expense->paymentMethod->name ?? 'N/A' }}" 
                                    readonly
                                    style="background-color: #f8f9fa; cursor: not-allowed;">
                                <input type="hidden" name="payment_method_id" value="{{ $expense->payment_method_id }}">
                            @else
                                <x-typable-select 
                                    name="payment_method_id"
                                    :options="$active_payment_methods"
                                    selected="{{ $expense->payment_method_id }}"
                                    placeholder="Type or select payment method..."
                                />
                            @endif
                            <div id="payment_method_id{{ $expense->id }}"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">{{ __('pagination.payment_status') }}</label>
                            <select name="payment_status" class="form-select form-select-solid">
                                <option value="pending" {{ $expense->payment_status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ $expense->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="reimbursed" {{ $expense->payment_status == 'reimbursed' ? 'selected' : '' }}>Reimbursed</option>
                            </select>
                            <div id="payment_status{{ $expense->id }}"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">{{ __('pagination.paid_date') }}</label>
                            <input type="date" name="paid_date" class="form-control" 
                                   value="{{ old('paid_date', $expense->paid_date ? $expense->paid_date->format('Y-m-d') : '') }}">
                            <div id="paid_date{{ $expense->id }}"></div>
                        </div>
                        
                        <!-- Location & Department - Using Dependent Dropdown -->
                        <div class="col-12">
                            <div class="row g-4">
                                <x-liveblade-dependent-dropdown 
                                    id="location_department_{{ $expense->id }}"
                                    parentName="location_id"
                                    childName="department_id"
                                    parentLabel="pagination._location"
                                    childLabel="auth._department"
                                    :parentOptions="$locations"
                                    :childOptions="$departments"
                                    route="{{ route('get.departments') }}"
                                    selectedParent="{{ $expense->location_id }}"
                                    selectedChild="{{ $expense->department_id }}"
                                    skipAjax="true"
                                />
                                <div id="location_id{{ $expense->id }}"></div>
                                <div id="department_id{{ $expense->id }}"></div>
                            </div>
                        </div>
                        
                        <!-- Recurring Expenses -->
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-4">
                                <input type="hidden" name="is_recurring" value="0">
                                <input class="form-check-input" type="checkbox" name="is_recurring" 
                                    value="1" id="editRecurringSwitch{{$expense->id}}" 
                                    {{ $expense->is_recurring ? 'checked' : '' }}
                                    onchange="toggleRecurringFields{{$expense->id}}()">
                                <label class="form-check-label" for="editRecurringSwitch{{$expense->id}}">
                                    {{ __('pagination.recurring_expense') }}
                                </label>
                            </div>                            
                            <div id="is_recurring{{ $expense->id }}"></div>
                        </div>

                        <div class="col-md-6 edit-recurring-fields{{$expense->id}}" 
                            id="recurringFields{{$expense->id}}" 
                            style="display: {{ $expense->is_recurring ? 'block' : 'none' }};">
                            <label class="form-label">{{ __('pagination.recurring_frequency') }}</label>
                            <select name="recurring_frequency" class="form-select">
                                <option value="">{{ __('pagination.select_frequency') }}</option>
                                <option value="weekly" {{ $expense->recurring_frequency == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                <option value="monthly" {{ $expense->recurring_frequency == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="quarterly" {{ $expense->recurring_frequency == 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                <option value="annually" {{ $expense->recurring_frequency == 'annually' ? 'selected' : '' }}>Annually</option>
                            </select>
                            <div id="recurring_frequency{{ $expense->id }}"></div>
                        </div>

                        <div class="col-md-6 edit-recurring-fields{{$expense->id}}" style="display: {{ $expense->is_recurring ? 'block' : 'none' }};">
                            <label class="form-label">{{ __('pagination.next_recurring_date') }}</label>
                            <input type="date" name="next_recurring_date" class="form-control" 
                                   value="{{ old('next_recurring_date', $expense->next_recurring_date ? $expense->next_recurring_date->format('Y-m-d') : '') }}">
                        </div>
                        <div id="next_recurring_date{{ $expense->id }}"></div>
                        
                        <!-- Approval Information -->
                        @if($expense->approved_at)
                            <div class="col-12">
                                <div class="alert alert-success">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                                        <div>
                                            <strong>Approved</strong>
                                            <div class="small">By: {{ $expense->approver->name ?? 'N/A' }}</div>
                                            <div class="small">On: {{ $expense->approved_at->format('d M Y, h:i A') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="closeModalEditButton{{$expense->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">
                        {{ __('auth._discard') }}
                    </button>
                    <button onclick="updateExpense({{$expense->id}})" id="editExpenseButton{{ $expense->id }}" type="button" class="btn btn-primary"
                    @if($expense->payment_status === 'paid') disabled @endif>
                        <span class="indicator-label">{{__('auth._update')}}</span>
                        <span class="indicator-progress">
                            {{__('auth.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>