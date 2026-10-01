<div class="modal fade" id="kt_modal_add_customer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth._create') }} {{ __('passwords.customer') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_customer_form" class="form">
                    @csrf
                    <div class="text-center pt-10">

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('passwords.first_name') }}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="first_name" />
                                <div id="first_name"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('passwords.last_name') }}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="last_name" />
                                <div id="last_name"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.email') }}</span>
                                </label>
                                <input type="email" class="form-control form-control-solid" name="email" />
                                <div id="email"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.phone') }} - {{__('+256....')}}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="phone" />
                                <div id="phone"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.customer_group') }}</span>
                                </label>
                                <select name="group_id" class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('passwords.select_group') }}">
                                    <option value="">{{ __('pagination._none') }}</option>
                                    @foreach($customerGroups ?? [] as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                                <div id="group_id"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords._tax') }}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="tax_number" />
                                <div id="tax_number"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.birth_date') }}</span>
                                </label>
                                <input type="date" class="form-control form-control-solid" name="birth_date" />
                                <div id="birth_date"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.country_code') }}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid"
                                       name="country_code" maxlength="2"
                                       placeholder="e.g. UG" />
                                <div id="country_code"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.address') }}</span>
                                </label>
                                <textarea class="form-control form-control-solid" name="address" rows="2"></textarea>
                                <div id="address"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.city') }}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="city" />
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.state') }}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="state" />
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.postal_code') }}</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" name="postal_code" />
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="accepts_marketing" value="1" id="accepts_marketing">
                                    <label class="form-check-label" for="accepts_marketing">
                                        {{ __('passwords.accepts_marketing') }}
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords._status') }}</span>
                                </label>
                                <select class="form-select form-select-solid" name="is_active">
                                    <option value="1" selected>{{ __('payments.active') }}</option>
                                    <option value="0">{{ __('payments.inactive') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.notes') }}</span>
                                </label>
                                <textarea class="form-control form-control-solid" name="notes" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="col-md-12 text-end">
                                <button type="reset" class="btn btn-light me-3" id="discardCustomerButton" data-bs-dismiss="modal">
                                    {{ __('auth._discard') }}
                                </button>

                                <button id="submitCustomerButton"
                                        type="button"
                                        class="btn btn-primary"
                                        onclick="submitCustomerForm('kt_modal_add_customer_form', 'submitCustomerButton', '{{ route('customer.store') }}', 'POST', 'discardCustomerButton')">
                                    <span class="indicator-label">{{ __('auth.submit') }}</span>
                                    <span class="indicator-progress">{{ __('auth.please_wait') }}
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