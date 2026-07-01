<div class="modal fade" id="editPaymentMethod{{ $paymentMethod->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{__('payments.edit_payment_method')}} - {{ $paymentMethod->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_payment_method_form{{ $paymentMethod->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="row g-9 mb-8">
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth._name')}}</span>
                            </label>
                            <input type="text" value="{{ $paymentMethod->name }}" class="form-control form-control-solid" name="name" />
                            <div id="name{{ $paymentMethod->id }}"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('auth._code')}}</span>
                            </label>
                            <input type="text" value="{{ $paymentMethod->code }}" class="form-control form-control-solid" name="code" />
                            <div id="code{{ $paymentMethod->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                <span class="required">{{__('payments._type')}}</span>
                            </label>
                            <select class="form-select form-select-solid" name="type" id="paymentTypeSelect{{ $paymentMethod->id }}" onchange="toggleEditPaymentMethodFields({{ $paymentMethod->id }})" data-control="select2" data-close-on-select="false" data-placeholder="{{__('auth._select')}}" data-allow-clear="true">
                                <option value="bank_account" {{ $paymentMethod->type == 'bank_account' ? 'selected' : '' }}>{{__('payments.bank_account')}}</option>
                                <option value="digital_wallet" {{ $paymentMethod->type == 'digital_wallet' ? 'selected' : '' }}>{{__('payments.digital_wallet')}}</option>
                                <option value="card" {{ $paymentMethod->type == 'card' ? 'selected' : '' }}>{{__('payments.card')}}</option>
                                <option value="cash" {{ $paymentMethod->type == 'cash' ? 'selected' : '' }}>{{__('payments.cash')}}</option>
                                <option value="check" {{ $paymentMethod->type == 'check' ? 'selected' : '' }}>{{__('payments.check')}}</option>
                                <option value="mobile_money" {{ $paymentMethod->type == 'mobile_money' ? 'selected' : '' }}>{{__('payments.mobile_money')}}</option>
                                <option value="credit" {{ $paymentMethod->type == 'credit' ? 'selected' : '' }}>{{__('payments.credit')}}</option>
                                <option value="other" {{ $paymentMethod->type == 'other' ? 'selected' : '' }}>{{__('payments.other')}}</option>
                            </select>
                            <div id="type{{ $paymentMethod->id }}"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments._provider')}}
                            </label>
                            <input type="text" value="{{ $paymentMethod->provider }}" class="form-control form-control-solid" name="provider" id="providerField{{ $paymentMethod->id }}" />
                            <div id="provider{{ $paymentMethod->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8" id="bankFields{{ $paymentMethod->id }}" style="{{ in_array($paymentMethod->type, ['bank_account', 'mobile_money']) ? '' : 'display: none;' }}">
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.account_name')}}
                            </label>
                            <input type="text" value="{{ $paymentMethod->account_name }}" class="form-control form-control-solid" name="account_name" />
                            <div id="account_name{{ $paymentMethod->id }}"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.account_number')}}
                            </label>
                            <input type="text" value="{{ $paymentMethod->account_number }}" class="form-control form-control-solid" name="account_number" />
                            <div id="account_number{{ $paymentMethod->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('accounting.current_balance')}}
                            </label>
                            <input type="number" value="{{ $paymentMethod->current_balance }}" class="form-control form-control-solid" name="current_balance" @cannot('update current balance') readonly @endcannot/>
                            <div id="current_balance{{ $paymentMethod->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-12">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.description')}}
                            </label>
                            <textarea class="form-control form-control-solid" name="description" rows="3">{{ $paymentMethod->description }}</textarea>
                            <div id="description{{ $paymentMethod->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.transaction_fee_percentage')}} (%)
                            </label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control form-control-solid" name="transaction_fee_percentage" value="{{ $paymentMethod->transaction_fee_percentage }}" />
                            <div id="transaction_fee_percentage{{ $paymentMethod->id }}"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.transaction_fee_fixed')}}
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="transaction_fee_fixed" value="{{ $paymentMethod->transaction_fee_fixed }}" />
                            <div id="transaction_fee_fixed{{ $paymentMethod->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.min_transaction_amount')}}
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="min_transaction_amount" value="{{ $paymentMethod->min_transaction_amount }}" />
                            <div id="min_transaction_amount{{ $paymentMethod->id }}"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                {{__('payments.max_transaction_amount')}}
                            </label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-solid" name="max_transaction_amount" value="{{ $paymentMethod->max_transaction_amount }}" />
                            <div id="max_transaction_amount{{ $paymentMethod->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $paymentMethod->is_active ? 'checked' : '' }} />
                                <label class="form-check-label">{{__('auth._active')}}</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" {{ $paymentMethod->is_default ? 'checked' : '' }} />
                                <label class="form-check-label">{{__('payments.set_as_default')}}</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="is_online" value="1" {{ $paymentMethod->is_online ? 'checked' : '' }} />
                                <label class="form-check-label">{{__('payments.is_online')}}</label>
                            </div>
                        </div>
                    </div>

                    <div class="text-center pt-10">
                        <button type="reset" class="btn btn-light me-3" id="closeModalEditButton{{ $paymentMethod->id }}" data-bs-dismiss="modal">{{__('auth._discard')}}</button>
                        <button onclick="editPaymentMethodInstance({{$paymentMethod->id }})" id="editPaymentMethodButton{{ $paymentMethod->id }}" type="button" class="btn btn-primary">
                            <span class="indicator-label">{{__('auth._update')}}</span>
                            <span class="indicator-progress">{{__('auth.please_wait')}}
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

