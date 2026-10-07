{{-- resources/views/procurement/expense/edit.blade.php --}}
<div class="modal fade" id="expenseEditModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('auth.edit_expense') }} — <span id="editExpenseNumber">—</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="expenseEditForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="expense_id" id="editExpenseId">

                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label required">{{ __('pagination.expense_date') }}</label>
                            <input type="date" name="date" id="editDate" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">{{ __('pagination.category') }}</label>
                            <select name="category_id" id="editCategoryId" class="form-select" required>
                                <option value="">{{ __('auth.select_category') }}</option>
                                @foreach($expenseCategories ?? [] as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label required">{{ __('pagination.description') }}</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required">{{ __('passwords.supplier') }}</label>
                            <select name="supplier_id" id="editSupplierId" class="form-select" required>
                                <option value="">{{ __('auth.select_supplier') }}</option>
                                @foreach($suppliers ?? [] as $sup)
                                    <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('pagination.employee') }}</label>
                            <select name="employee_id" id="editEmployeeId" class="form-select">
                                <option value="">{{ __('auth.none') }}</option>
                                @foreach($active_employees ?? [] as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required">{{ __('pagination.gross_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" step="0.01" min="0" required
                                       name="gross_amount" id="editGrossAmount" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('pagination.tax_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" step="0.01" min="0"
                                       name="tax_amount" id="editTaxAmount" class="form-control">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">{{ __('pagination.total_amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="text" readonly name="total_amount_display"
                                       id="editTotalAmount" class="form-control bg-light">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('pagination.payment_method') }}</label>
                            <select name="payment_method_id" id="editPaymentMethodId" class="form-select">
                                <option value="">{{ __('auth.none') }}</option>
                                @foreach($PaymentMethods ?? [] as $pm)
                                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('pagination.payment_status') }}</label>
                            <select name="payment_status" id="editPaymentStatus" class="form-select">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="reimbursed">Reimbursed</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('pagination.paid_date') }}</label>
                            <input type="date" name="paid_date" id="editPaidDate" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.location') }}</label>
                            <select name="location_id" id="editLocationId" class="form-select">
                                <option value="">{{ __('auth.none') }}</option>
                                @foreach($locations ?? [] as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">{{ __('auth.department') }}</label>
                            <select name="department_id" id="editDepartmentId" class="form-select">
                                <option value="">{{ __('auth.none') }}</option>
                                @foreach($departments ?? [] as $dep)
                                    <option value="{{ $dep->id }}" data-location-id="{{ $dep->location_id }}">
                                        {{ $dep->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ __('auth._discard') }}
                    </button>
                    <button type="submit" class="btn btn-primary" id="editExpenseSubmit">
                        <span class="indicator-label">{{ __('auth._update') }}</span>
                        <span class="indicator-progress d-none">
                            {{ __('auth.please_wait') }}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>