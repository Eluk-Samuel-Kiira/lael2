<div class="modal fade" id="kt_modal_add_payment_method" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('payments.new_payment_method')}}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_payment_method_form" class="form">
                    @csrf
                    <div class="row g-9 mb-8">
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth._name')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="name" />
                            <div id="name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth._code')}}</span>
                            </label>
                            <input type="text" class="form-control form-control-solid" name="code" />
                            <div id="code"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('payments._type')}}</span>
                            </label>
                            <select class="form-select form-select-solid" name="type" id="paymentTypeSelect" onchange="togglePaymentMethodFields()">
                                <option value="">{{__('payments.select_type')}}</option>
                                <option value="bank_account">{{__('payments.bank_account')}}</option>
                                <option value="digital_wallet">{{__('payments.digital_wallet')}}</option>
                                <option value="card">{{__('payments.card')}}</option>
                                <option value="cash">{{__('payments.cash')}}</option>
                                <option value="check">{{__('payments.check')}}</option>
                                <option value="mobile_money">{{__('payments.mobile_money')}}</option>
                                <option value="credit">{{__('payments.credit')}}</option>
                                <option value="other">{{__('payments.other')}}</option>
                            </select>
                            <div id="type"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments._provider')}}
                            </label>
                            <input type="text" class="form-control form-control-solid" name="provider" id="providerField" />
                            <div id="provider"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8" id="bankFields" style="display: none;">
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.account_name')}}
                            </label>
                            <input type="text" class="form-control form-control-solid" name="account_name" />
                            <div id="account_name"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.account_number')}}
                            </label>
                            <input type="text" class="form-control form-control-solid" name="account_number" />
                            <div id="account_number"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('accounting.current_balance')}}
                            </label>
                            <input type="number" class="form-control form-control-solid" name="current_balance" value="0"/>
                            <div id="current_balance"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.description')}}
                            </label>
                            <textarea class="form-control form-control-solid" name="description" rows="3"></textarea>
                            <div id="description"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.transaction_fee_percentage')}} (%)
                            </label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control form-control-solid" name="transaction_fee_percentage" value="0" />
                            <div id="transaction_fee_percentage"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.transaction_fee_fixed')}}
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="transaction_fee_fixed" value="0" />
                            <div id="transaction_fee_fixed"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.min_transaction_amount')}}
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="min_transaction_amount" value="0" />
                            <div id="min_transaction_amount"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.max_transaction_amount')}}
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="max_transaction_amount" />
                            <div id="max_transaction_amount"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked />
                                <label class="form-check-label">{{__('auth._active')}}</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" />
                                <label class="form-check-label">{{__('payments.set_as_default')}}</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_online" value="1" checked />
                                <label class="form-check-label">{{__('payments.is_online')}}</label>
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" id="discardButton" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                        <button 
                            id="submitPaymentMethodButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitPaymentMethodForm('kt_modal_add_payment_method_form', 'submitPaymentMethodButton', '{{ route('paymentmethod.store') }}', 'POST', 'discardButton')">
                            <span class="indicator-label">{{__('auth.submit')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
