<div class="modal fade" id="kt_modal_add_expense" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
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
            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh;">
                <form id="kt_modal_add_expense_form" class="form">
                    @csrf
                    
                    <!-- Basic Information Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.basic_information') }}</h3>
                    </div>
                    
                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">{{__('pagination.description')}}</label>
                            <input type="text" class="form-control form-control-solid" name="description" placeholder="{{__('pagination.enter_description')}}" />
                            <div id="description"></div>
                        </div>
                        
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">{{__('pagination.gross_amount')}}</label>
                            <input type="number" step="0.01" class="form-control form-control-solid" name="gross_amount" id="expense_amount" placeholder="0.00" />
                            <div id="gross_amount"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{ __('passwords.supplier') }}</span>
                            </label>
                            <x-typable-select 
                                name="supplier_id"
                                :options="$suppliers"
                                selected="{{ old('supplier_id', $expense->supplier_id ?? '') }}"
                                placeholder="Type or select supplier..."
                            />
                            <div id="supplier_id"></div>
                        </div>
                        
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">{{__('pagination.category')}}</label>
                            <x-typable-select 
                                name="category_id"
                                :options="$expenseCategories"
                                selected="{{ old('category_id', $expense->category_id ?? '') }}"
                                placeholder="Type or select category..."
                            />
                            <div id="category_id"></div>
                        </div>
                    </div>

                    <!-- Tax Information Section -->
                    <div class="card card-flush bg-light-warning mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-receipt text-warning me-2"></i>
                                {{ __('passwords.tax_information') }}
                            </h3>
                            <div class="card-toolbar">
                                <button type="button" class="btn btn-sm btn-primary" onclick="calculateExpenseTaxPreview()">
                                    <i class="bi bi-calculator me-2"></i>
                                    {{ __('passwords.preview_calculation') }}
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold mb-3">{{ __('passwords.select_applicable_taxes') }}</label>
                                <div class="row g-4">
                                    @foreach($taxes as $tax)
                                    <div class="col-lg-6">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input expense-tax-checkbox" 
                                                type="checkbox" 
                                                name="selected_taxes[]" 
                                                value="{{ $tax->id }}"
                                                id="expense_tax_{{ $tax->id }}"
                                                data-rate="{{ $tax->rate }}"
                                                data-type="{{ $tax->type }}"
                                                data-name="{{ $tax->name }}"
                                                data-is_withholding="{{ $tax->is_withholding_tax ? 'true' : 'false' }}">
                                            <label class="form-check-label" for="expense_tax_{{ $tax->id }}">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="fw-bold">{{ $tax->name }}</span>
                                                    @if($tax->is_withholding_tax)
                                                        <span class="badge badge-light-danger">Withholding</span>
                                                    @else
                                                        <span class="badge badge-light-primary">Additive</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="badge badge-light-info">{{ $tax->formatted_rate }}</span>
                                                    <small class="text-muted ms-2">{{ $tax->code }}</small>
                                                </div>
                                                @if($tax->is_withholding_tax)
                                                    <small class="text-danger d-block mt-1">
                                                        <i class="bi bi-arrow-down-short me-1"></i>
                                                        {{ __('passwords.withholding_tax_note') }}
                                                    </small>
                                                @else
                                                    <small class="text-primary d-block mt-1">
                                                        <i class="bi bi-arrow-up-short me-1"></i>
                                                        {{ __('passwords.additive_tax_note') }}
                                                    </small>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Tax Preview Result -->
                            <div id="expense_tax_preview" class="mt-4 p-4 bg-white rounded d-none">
                                <h5 class="mb-3">{{ __('passwords.tax_calculation_summary') }}</h5>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <div class="card card-dashed bg-light">
                                            <div class="card-body p-3">
                                                <span class="text-muted fw-bold d-block">{{ __('passwords.taxable_amount') }}</span>
                                                <span class="fw-bold fs-3" id="expense_preview_taxable">0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card card-dashed bg-light-warning">
                                            <div class="card-body p-3">
                                                <span class="text-muted fw-bold d-block">{{ __('passwords.total_tax') }}</span>
                                                <span class="fw-bold fs-3 text-warning" id="expense_preview_tax">0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card card-dashed bg-light-success">
                                            <div class="card-body p-3">
                                                <span class="text-muted fw-bold d-block">{{ __('passwords.total_amount') }}</span>
                                                <span class="fw-bold fs-3 text-success" id="expense_preview_total">0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-gray-100 align-middle">
                                        <thead>
                                            <tr class="fw-bold text-muted bg-light">
                                                <th>{{ __('passwords.tax_name') }}</th>
                                                <th>{{ __('passwords.rate') }}</th>
                                                <th>{{ __('passwords.type') }}</th>
                                                <th class="text-end">{{ __('passwords.amount') }}</th>
                                                <th class="text-center">{{ __('passwords.effect') }}</th>
                                             </tr>
                                        </thead>
                                        <tbody id="expense_tax_breakdown_body">
                                        </tbody>
                                     </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Information Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.payment_information') }}</h3>
                    </div>
                    
                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{__('pagination.payment_method')}}</label>
                            <select name="payment_method_id" class="form-select">
                                <option value="">{{ __('payments.select_payment_method') }}</option>
                                @foreach($active_payment_methods as $method)
                                    <option value="{{ $method->id }}" {{ $method->is_default ? 'selected' : '' }}>
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
                            <label class="fs-6 fw-semibold mb-2">{{__('pagination.payment_status')}}</label>
                            <select class="form-select form-select-solid" name="payment_status">
                                <option value="pending" selected>{{__('pagination.pending')}}</option>
                                <!-- <option value="paid">{{__('pagination.paid')}}</option>
                                <option value="reimbursed">{{__('pagination.reimbursed')}}</option> -->
                            </select>
                            <div id="payment_status"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{__('pagination.date')}}</label>
                            <input type="date" class="form-control form-control-solid" name="date" value="{{ date('Y-m-d') }}" />
                            <div id="date"></div>
                        </div>
                        
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{__('pagination.paid_date')}}</label>
                            <input type="date" class="form-control form-control-solid" name="paid_date" />
                            <div id="paid_date"></div>
                        </div>
                    </div>

                    <!-- Location & Department Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.location_department') }}</h3>
                    </div>
                    
                    <div class="row g-9 mb-8">
                        <x-liveblade-dependent-dropdown 
                            id="location_department"
                            parentName="location_id"
                            childName="department_id"
                            parentLabel="pagination._location"
                            childLabel="auth._department"
                            :parentOptions="$locations"
                            :childOptions="$departments"
                            route="{{ route('get.departments') }}"
                        />
                        <div id="location_id"></div>
                        <div id="department_id"></div>
                    </div>

                    <div class="row g-9 mb-8">
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
                        />
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" name="total_tax_amount" id="expense_total_tax_amount" value="0">
                    <input type="hidden" name="net_amount" id="expense_net_amount" value="0">

                    <div class="text-center pt-15">
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
