<div class="modal fade" id="kt_modal_add_supplier" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header" id="kt_modal_add_supplier_header">
                <h2 class="fw-bold">{{ __('auth._create') }} {{ __('passwords._supplier') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_add_supplier_form" class="form">
                    @csrf
                    <div class="text-center pt-10">
                        <!-- Row 1 -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="required fs-6 fw-semibold mb-2">{{ __('auth._name') }}</label>
                                <input type="text" class="form-control form-control-solid" name="name" />
                                <div id="name"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="fs-6 fw-semibold mb-2">{{ __('passwords.contact_person') }}</label>
                                <input type="text" class="form-control form-control-solid" name="contact_person" />
                                <div id="contact_person"></div>
                            </div>
                        </div>

                        <!-- Row 2 -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="fs-6 fw-semibold mb-2">{{ __('passwords._email') }}</label>
                                <input type="email" class="form-control form-control-solid" name="email" />
                                <div id="email"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="fs-6 fw-semibold mb-2">{{ __('passwords._phone') }}</label>
                                <input type="text" class="form-control form-control-solid" name="phone" />
                                <div id="phone"></div>
                            </div>
                        </div>

                        <!-- Row 3 -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="fs-6 fw-semibold mb-2">{{ __('passwords.tax_number') }}</label>
                                <input type="text" class="form-control form-control-solid" name="tax_number" />
                                <div id="tax_number"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="fs-6 fw-semibold mb-2">{{ __('passwords.payment_terms') }} ({{ __('auth._days') }})</label>
                                <input type="number" class="form-control form-control-solid" name="payment_terms" min="0" />
                                <div id="payment_terms"></div>
                            </div>
                        </div>

                        <!-- Row 4 -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="fs-6 fw-semibold mb-2">{{ __('passwords.address') }}</label>
                                <input type="text" class="form-control form-control-solid" name="address" />
                                <div id="address"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="fs-6 fw-semibold mb-2">{{ __('passwords._city') }}</label>
                                <input type="text" class="form-control form-control-solid" name="city" />
                                <div id="city"></div>
                            </div>
                        </div>

                        <!-- Row 5 -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="fs-6 fw-semibold mb-2">{{ __('passwords._state') }}</label>
                                <input type="text" class="form-control form-control-solid" name="state" />
                                <div id="state"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="fs-6 fw-semibold mb-2">{{ __('passwords.postal_code') }}</label>
                                <input type="text" class="form-control form-control-solid" name="postal_code" />
                                <div id="postal_code"></div>
                            </div>
                        </div>

                        <!-- Row 6 -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="fs-6 fw-semibold mb-2">{{ __('passwords.country_code') }}</label>
                                <input type="text" class="form-control form-control-solid" name="country_code" maxlength="2" placeholder="UG" />
                                <div id="country_code"></div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="d-flex flex-column mb-8 fv-row">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._notes') }}</label>
                            <textarea class="form-control form-control-solid" name="notes" rows="3"></textarea>
                            <div id="notes"></div>
                        </div>

                        <!-- Buttons -->
                        <button type="reset" class="btn btn-light me-3" id="discardSupplierButton" data-bs-dismiss="modal">
                            {{ __('auth._discard') }}
                        </button>

                        <button 
                            id="submitSupplierButton" 
                            type="button" 
                            class="btn btn-primary"
                            onclick="submitSupplierForm('kt_modal_add_supplier_form', 'submitSupplierButton', '{{ route('suppliers.store') }}', 'POST', 'discardSupplierButton')">
                            
                            <span class="indicator-label">{{ __('auth.submit') }}</span>
                            <span class="indicator-progress">
                                {{ __('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
