<div class="modal fade" id="editCustomer{{ $customer->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('passwords.customer_edit') }} - {{ $customer->first_name }} {{ $customer->last_name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="kt_modal_edit_customer_form{{ $customer->id }}" class="form">
                    @csrf
                    @method('PUT')
                    <div class="text-center pt-10">

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('passwords.first_name') }}</span>
                                </label>
                                <input type="text" value="{{ $customer->first_name }}"
                                       class="form-control form-control-solid" name="first_name" />
                                <div id="first_name{{ $customer->id }}"></div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">{{ __('passwords.last_name') }}</span>
                                </label>
                                <input type="text" value="{{ $customer->last_name }}"
                                       class="form-control form-control-solid" name="last_name" />
                                <div id="last_name{{ $customer->id }}"></div>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.email') }}</span>
                                </label>
                                <input type="email" value="{{ $customer->email }}"
                                       class="form-control form-control-solid" name="email" />
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.phone') }}</span>
                                </label>
                                <input type="text" value="{{ $customer->phone }}"
                                       class="form-control form-control-solid" name="phone" />
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.customer_group') }}</span>
                                </label>
                                <select name="group_id" class="form-select form-select-solid"
                                        data-control="select2"
                                        data-placeholder="{{ __('passwords.select_group') }}">
                                    <option value="">{{ __('pagination._none') }}</option>
                                    @foreach($customerGroups ?? [] as $group)
                                        <option value="{{ $group->id }}"
                                                {{ $customer->group_id == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords._tax') }}</span>
                                </label>
                                <input type="text" value="{{ $customer->tax_number }}"
                                       class="form-control form-control-solid" name="tax_number" />
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.birth_date') }}</span>
                                </label>
                                <input type="date"
                                       value="{{ $customer->birth_date ? $customer->birth_date->format('Y-m-d') : '' }}"
                                       class="form-control form-control-solid" name="birth_date" />
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.country_code') }}</span>
                                </label>
                                <input type="text" value="{{ $customer->country_code }}"
                                       class="form-control form-control-solid"
                                       name="country_code" maxlength="2" />
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.address') }}</span>
                                </label>
                                <textarea class="form-control form-control-solid" name="address" rows="2">{{ $customer->address }}</textarea>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.city') }}</span>
                                </label>
                                <input type="text" value="{{ $customer->city }}"
                                       class="form-control form-control-solid" name="city" />
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.state') }}</span>
                                </label>
                                <input type="text" value="{{ $customer->state }}"
                                       class="form-control form-control-solid" name="state" />
                            </div>
                            <div class="d-flex flex-column mb-8 fv-row col-md-4">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.postal_code') }}</span>
                                </label>
                                <input type="text" value="{{ $customer->postal_code }}"
                                       class="form-control form-control-solid" name="postal_code" />
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox"
                                           name="accepts_marketing" value="1"
                                           id="accepts_marketing{{ $customer->id }}"
                                           {{ $customer->accepts_marketing ? 'checked' : '' }}>
                                    <label class="form-check-label" for="accepts_marketing{{ $customer->id }}">
                                        {{ __('passwords.accepts_marketing') }}
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex flex-column mb-8 fv-row col-md-6">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords._status') }}</span>
                                </label>
                                <select class="form-select form-select-solid" name="is_active">
                                    <option value="1" {{ $customer->is_active ? 'selected' : '' }}>
                                        {{ __('payments.active') }}
                                    </option>
                                    <option value="0" {{ !$customer->is_active ? 'selected' : '' }}>
                                        {{ __('payments.inactive') }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="d-flex flex-column mb-8 fv-row col-md-12">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span>{{ __('passwords.notes') }}</span>
                                </label>
                                <textarea class="form-control form-control-solid" name="notes" rows="2">{{ $customer->notes }}</textarea>
                            </div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="col-md-12 text-end">
                                <button type="button" id="closeModalEditButton{{ $customer->id }}"
                                        class="btn btn-light me-3" data-bs-dismiss="modal">
                                    {{ __('auth._discard') }}
                                </button>
                                <button onclick="updateCustomer({{ $customer->id }})"
                                        id="editCustomerButton{{ $customer->id }}"
                                        type="button"
                                        class="btn btn-primary">
                                    <span class="indicator-label">{{ __('auth._update') }}</span>
                                    <span class="indicator-progress">
                                        {{ __('auth.please_wait') }}
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