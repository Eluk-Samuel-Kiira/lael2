<div class="modal fade" id="kt_modal_add_expense" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('passwords.expense_new')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_expense_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.description')}} </span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="description" placeholder="{{__('pagination.enter_description')}}" />
                                <div id="description"></div>
                            </div>
                            
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.amount')}} </span>
                                </label>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="amount" placeholder="0.00" />
                                <div id="amount"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.vendor')}} </span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="vendor_name" placeholder="{{__('pagination.enter_vendor')}}" />
                                <div id="vendor_name"></div>
                            </div>
                            
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination.category')}} </span>
                                </label>
                                <select class="form-select form-select-solid" name="category_id">
                                    <option value="">{{__('pagination.select_category')}}</option>
                                    @foreach($expenseCategories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div id="category_id"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('pagination.payment_method')}} </span>
                                </label>
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
                            
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('pagination.tax_amount')}} </span>
                                </label>
                                <input type="number" step="0.01" class="form-control form-control-solid" name="tax_amount" placeholder="0.00" />
                                <div id="tax_amount"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('pagination.date')}} </span>
                                </label>
                                <input type="date" class="form-control form-control-solid" name="date" value="{{ date('Y-m-d') }}" />
                                <div id="date"></div>
                            </div>
                            
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('pagination.paid_date')}} </span>
                                </label>
                                <input type="date" class="form-control form-control-solid" name="paid_date" />
                                <div id="paid_date"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('pagination.employee')}} </span>
                                </label>
                                <select class="form-select form-select-solid" name="employee_id">
                                    <option value="">{{__('pagination.select_employee')}}</option>
                                    @foreach($active_employees as $employee)
                                        <option value="{{ $employee->id }}">
                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="employee_id"></div>
                            </div>
                            
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('pagination.payment_status')}} </span>
                                </label>
                                <select class="form-select form-select-solid" name="payment_status">
                                    <option value="pending">{{__('pagination.pending')}}</option>
                                    <!-- <option value="paid">{{__('pagination.paid')}}</option> -->
                                    <!-- <option value="reimbursed">{{__('pagination.reimbursed')}}</option> -->
                                </select>
                                <div id="payment_status"></div>
                            </div>
                        </div>

                        <button type="button" id="closeModalButton" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="createExpense('kt_modal_add_expense_form', 'addExpenseButton', '{{ route('expense.store') }}', 'POST', 'closeModalButton')" 
                            id="addExpenseButton" type="button" class="btn btn-primary">
                            <span class="indicator-label">{{__('auth.submit')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

