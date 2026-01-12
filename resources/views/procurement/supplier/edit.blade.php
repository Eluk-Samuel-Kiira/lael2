<div class="modal fade" id="editSupplier{{ $supplier->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('auth._edit') }} {{ __('passwords._supplier') }} - {{ $supplier->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="kt_modal_supplier_form{{ $supplier->id }}" class="form">
                    @csrf
                    @method('PUT')

                    <div class="text-center pt-10">
                        <!-- Supplier Name & Contact Person -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('auth._name') }}</span>
                                </label>
                                <input type="text" value="{{ $supplier->name }}" class="form-control form-control-solid" name="name" />
                                <div id="name{{ $supplier->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.contact_person') }}</span>
                                </label>
                                <input type="text" value="{{ $supplier->contact_person }}" class="form-control form-control-solid" name="contact_person" />
                                <div id="contact_person{{ $supplier->id }}"></div>
                            </div>
                        </div>

                        <!-- Email & Phone -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords._email') }}</span>
                                </label>
                                <input type="email" value="{{ $supplier->email }}" class="form-control form-control-solid" name="email" />
                                <div id="email{{ $supplier->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords._phone') }}</span>
                                </label>
                                <input type="text" value="{{ $supplier->phone }}" class="form-control form-control-solid" name="phone" />
                                <div id="phone{{ $supplier->id }}"></div>
                            </div>
                        </div>

                        <!-- Tax Number & Payment Terms -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.tax_number') }}</span>
                                </label>
                                <input type="text" value="{{ $supplier->tax_number }}" class="form-control form-control-solid" name="tax_number" />
                                <div id="tax_number{{ $supplier->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.payment_terms') }}</span>
                                </label>
                                <input type="number" value="{{ $supplier->payment_terms }}" class="form-control form-control-solid" name="payment_terms" />
                                <div id="payment_terms{{ $supplier->id }}"></div>
                            </div>
                        </div>

                        <!-- Address & City -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.address') }}</span>
                                </label>
                                <input type="text" value="{{ $supplier->address }}" class="form-control form-control-solid" name="address" />
                                <div id="address{{ $supplier->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords._city') }}</span>
                                </label>
                                <input type="text" value="{{ $supplier->city }}" class="form-control form-control-solid" name="city" />
                                <div id="city{{ $supplier->id }}"></div>
                            </div>
                        </div>

                        <!-- State & Postal Code -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords._state') }}</span>
                                </label>
                                <input type="text" value="{{ $supplier->state }}" class="form-control form-control-solid" name="state" />
                                <div id="state{{ $supplier->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.postal_code') }}</span>
                                </label>
                                <input type="text" value="{{ $supplier->postal_code }}" class="form-control form-control-solid" name="postal_code" />
                                <div id="postal_code{{ $supplier->id }}"></div>
                            </div>
                        </div>

                        <!-- Country -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.country_code') }}</span>
                                </label>
                                <input type="text" value="{{ $supplier->country_code }}" class="form-control form-control-solid" name="country_code" />
                                <div id="country_code{{ $supplier->id }}"></div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords._notes') }}</span>
                                </label>
                                <textarea name="notes" class="form-control form-control-solid" rows="3">{{ $supplier->notes }}</textarea>
                                <div id="notes{{ $supplier->id }}"></div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <button type="button" id="closeModalEditButton{{$supplier->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="updateSupplierInstance({{ $supplier->id }})" id="editSupplierButton{{ $supplier->id }}" type="button" class="btn btn-primary">
                            <span class="indicator-label">{{ __('auth._update') }}</span>
                            <span class="indicator-progress">{{ __('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
