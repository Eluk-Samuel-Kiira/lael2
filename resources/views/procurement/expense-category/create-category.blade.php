<div class="modal fade" id="kt_modal_add_expense_category" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header" id="kt_modal_add_expense_category">
                <h2 class="fw-bold">{{__('auth._create')}} {{__('passwords.expense-category')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_expense_category_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <!-- Name and Code Fields -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('pagination._category')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="name" placeholder="Enter category name" />
                                <div id="name"></div>
                            </div>
                            
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{__('passwords._code')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="code" placeholder="e.g., EXP-OFFICE" />
                                <div id="code"></div>
                            </div>
                        </div>

                        <!-- Description Field -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('passwords._description')}}</span>
                                </label>
                                <textarea class="form-control form-control-solid" name="description" rows="3" placeholder="Enter category description"></textarea>
                                <div id="description"></div>
                            </div>
                        </div>

                        <!-- GL Account and Status Fields -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('passwords._gl_account')}}</span>
                                </label>
                                <select class="form-select form-select-solid" name="gl_account_id" data-control="select2" data-placeholder="Select GL Account">
                                    <option value="">Select GL Account</option>
                                    @foreach($chartOfAccounts as $account) <!-- CORRECT! This is account definitions -->
                                        <option value="{{ $account->id }}">
                                            {{ $account->account_code }} - {{ $account->account_name }} ({{ $account->account_type }})
                                        </option>
                                    @endforeach
                                </select>
                                <div id="gl_account_id"></div>
                            </div>
                            
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('passwords._status')}}</span>
                                </label>
                                <select class="form-select form-select-solid" name="is_active">
                                    <option value="1" selected>{{__('passwords._active')}}</option>
                                    <option value="0">{{__('passwords._inactive')}}</option>
                                </select>
                                <div id="is_active"></div>
                            </div>
                        </div>

                        <!-- Requirements Fields -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="requires_receipt" value="1" id="requires_receipt" checked>
                                    <label class="form-check-label" for="requires_receipt">
                                        {{__('passwords._requires_receipt')}}
                                    </label>
                                </div>
                                <div id="requires_receipt"></div>
                            </div>
                            
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="requires_approval" value="1" id="requires_approval">
                                    <label class="form-check-label" for="requires_approval">
                                        {{__('passwords._requires_approval')}}
                                    </label>
                                </div>
                                <div id="requires_approval"></div>
                            </div>
                        </div>

                        <!-- Budget Fields -->
                        <div class="row g-9 mb-8">
                            <!-- Monthly Budget -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('passwords._monthly_budget')}}</span>
                                </label>
                                <div class="input-group input-group-solid">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control form-control-solid" name="budget_monthly" placeholder="0.00" step="0.01" min="0" />
                                </div>
                                <div id="budget_monthly"></div>
                            </div>
                            
                            <!-- Annual Budget -->
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{__('passwords._annual_budget')}}</span>
                                </label>
                                <div class="input-group input-group-solid">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control form-control-solid" name="budget_annual" placeholder="0.00" step="0.01" min="0" />
                                </div>
                                <div id="budget_annual"></div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row g-9 mb-8">
                            <div class="col-md-12 text-end">
                                <button type="reset" class="btn btn-light me-3" id="discardExpenseCategoryButton" data-bs-dismiss="modal">
                                    {{__('auth._discard') }}
                                </button>
                                
                                <button 
                                    id="submitExpenseCategoryButton" 
                                    type="button" 
                                    class="btn btn-primary"
                                    onclick="submitExpenseCategoryForm('kt_modal_add_expense_category_form', 'submitExpenseCategoryButton', '{{ route('expense-category.store') }}', 'POST', 'discardExpenseCategoryButton')">
                                    
                                    <span class="indicator-label">{{__('auth.submit')}}</span>
                                    <span class="indicator-progress">{{__('auth.please_wait')}}
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>