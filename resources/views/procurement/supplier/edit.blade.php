<div class="modal fade" id="editSupplier{{ $supplier->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
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

            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh;">
                <form id="kt_modal_supplier_form{{ $supplier->id }}" class="form">
                    @csrf
                    @method('PUT')

                    <!-- Basic Information Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.basic_information') }}</h3>
                    </div>
                    
                    <div class="row g-9 mb-8">
                        <!-- Supplier Name -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('auth._name') }}</label>
                            <input type="text" value="{{ $supplier->name }}" class="form-control form-control-solid" name="name" />
                            <div id="name{{ $supplier->id }}"></div>
                        </div>

                        <!-- Trading Name -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.trading_name') }}</label>
                            <input type="text" value="{{ $supplier->trading_name }}" class="form-control form-control-solid" name="trading_name" />
                            <div id="trading_name{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Supplier Type -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('passwords.supplier_type') }}</label>
                            <select name="supplier_type" class="form-select form-select-solid">
                                <option value="company" {{ $supplier->supplier_type == 'company' ? 'selected' : '' }}>{{ __('passwords.company') }}</option>
                                <option value="individual" {{ $supplier->supplier_type == 'individual' ? 'selected' : '' }}>{{ __('passwords.individual') }}</option>
                                <option value="government" {{ $supplier->supplier_type == 'government' ? 'selected' : '' }}>{{ __('passwords.government') }}</option>
                                <option value="ngo" {{ $supplier->supplier_type == 'ngo' ? 'selected' : '' }}>{{ __('passwords.ngo') }}</option>
                                <option value="foreign" {{ $supplier->supplier_type == 'foreign' ? 'selected' : '' }}>{{ __('passwords.foreign') }}</option>
                            </select>
                            <div id="supplier_type{{ $supplier->id }}"></div>
                        </div>

                        <!-- Supplier Code -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.supplier_code') }}</label>
                            <input type="text" value="{{ $supplier->supplier_code }}" class="form-control form-control-solid" name="supplier_code" />
                            <div id="supplier_code{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Contact Person -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.contact_person') }}</label>
                            <input type="text" value="{{ $supplier->contact_person }}" class="form-control form-control-solid" name="contact_person" />
                            <div id="contact_person{{ $supplier->id }}"></div>
                        </div>

                        <!-- Status -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-5">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active_{{ $supplier->id }}" value="1" {{ $supplier->is_active ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active_{{ $supplier->id }}">
                                    {{ __('auth._active') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.contact_information') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Email -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._email') }}</label>
                            <input type="email" value="{{ $supplier->email }}" class="form-control form-control-solid" name="email" />
                            <div id="email{{ $supplier->id }}"></div>
                        </div>

                        <!-- Phone -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._phone') }}</label>
                            <input type="text" value="{{ $supplier->phone }}" class="form-control form-control-solid" name="phone" />
                            <div id="phone{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Secondary Phone -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.phone_secondary') }}</label>
                            <input type="text" value="{{ $supplier->phone_secondary }}" class="form-control form-control-solid" name="phone_secondary" />
                            <div id="phone_secondary{{ $supplier->id }}"></div>
                        </div>

                        <!-- Website -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.website') }}</label>
                            <input type="url" value="{{ $supplier->website }}" class="form-control form-control-solid" name="website" />
                            <div id="website{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <!-- Address Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.address_information') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Address -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-12">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.address') }}</label>
                            <input type="text" value="{{ $supplier->address }}" class="form-control form-control-solid" name="address" />
                            <div id="address{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- City -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._city') }}</label>
                            <input type="text" value="{{ $supplier->city }}" class="form-control form-control-solid" name="city" />
                            <div id="city{{ $supplier->id }}"></div>
                        </div>

                        <!-- State -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._state') }}</label>
                            <input type="text" value="{{ $supplier->state }}" class="form-control form-control-solid" name="state" />
                            <div id="state{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Postal Code -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.postal_code') }}</label>
                            <input type="text" value="{{ $supplier->postal_code }}" class="form-control form-control-solid" name="postal_code" />
                            <div id="postal_code{{ $supplier->id }}"></div>
                        </div>

                        <!-- Country Code -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.country') }}</label>
                            @php
                                $countryOptions = [];
                                foreach(getCountries(true, true) as $code => $name) {
                                    $countryOptions[] = (object)['id' => $code, 'name' => $name];
                                }
                            @endphp
                            <x-typable-select 
                                name="country_code"
                                :options="$countryOptions"
                                selected="{{ $supplier->country_code ?? 'UG' }}"
                                placeholder="Type or select country..."
                            />
                            <div id="country_code{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <!-- Tax & Compliance Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.tax_compliance') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Tax Number (TIN) -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.tax_number') }}</label>
                            <input type="text" value="{{ $supplier->tax_number }}" class="form-control form-control-solid" name="tax_number" />
                            <div id="tax_number{{ $supplier->id }}"></div>
                        </div>

                        <!-- VAT Registered -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-5">
                                <input class="form-check-input" type="checkbox" name="is_vat_registered" id="is_vat_registered_{{ $supplier->id }}" value="1" {{ $supplier->is_vat_registered ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_vat_registered_{{ $supplier->id }}">
                                    {{ __('passwords.vat_registered') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8" id="vat_number_row_{{ $supplier->id }}">
                        <!-- VAT Number -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.vat_number') }}</label>
                            <input type="text" value="{{ $supplier->vat_number }}" class="form-control form-control-solid" name="vat_number" />
                            <div id="vat_number{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Withholding Tax Applicable -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-5">
                                <input class="form-check-input" type="checkbox" name="withholding_tax_applicable" id="wht_applicable_{{ $supplier->id }}" value="1" {{ $supplier->withholding_tax_applicable ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="wht_applicable_{{ $supplier->id }}">
                                    {{ __('passwords.withholding_tax_applicable') }}
                                </label>
                            </div>
                        </div>

                        <!-- Withholding Tax Rate -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6" id="wht_rate_row_{{ $supplier->id }}">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.withholding_tax_rate') }} (%)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control form-control-solid" name="withholding_tax_rate" value="{{ $supplier->withholding_tax_rate }}" />
                            <div id="withholding_tax_rate{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8" id="wht_exemption_row_{{ $supplier->id }}" style="{{ !$supplier->withholding_tax_applicable && $supplier->withholding_tax_exemption_ref ? 'display: flex' : 'display: none' }}">
                        <!-- WHT Exemption Reference -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.wht_exemption_ref') }}</label>
                            <input type="text" value="{{ $supplier->withholding_tax_exemption_ref }}" class="form-control form-control-solid" name="withholding_tax_exemption_ref" />
                            <div id="withholding_tax_exemption_ref{{ $supplier->id }}"></div>
                        </div>

                        <!-- WHT Exemption Expiry -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.wht_exemption_expiry') }}</label>
                            <input type="date" value="{{ $supplier->withholding_tax_exemption_expiry ? $supplier->withholding_tax_exemption_expiry->format('Y-m-d') : '' }}" class="form-control form-control-solid" name="withholding_tax_exemption_expiry" />
                            <div id="withholding_tax_exemption_expiry{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <!-- Banking Information Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.banking_information') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Bank Name -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.bank_name') }}</label>
                            <input type="text" value="{{ $supplier->bank_name }}" class="form-control form-control-solid" name="bank_name" />
                            <div id="bank_name{{ $supplier->id }}"></div>
                        </div>

                        <!-- Bank Branch -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.bank_branch') }}</label>
                            <input type="text" value="{{ $supplier->bank_branch }}" class="form-control form-control-solid" name="bank_branch" />
                            <div id="bank_branch{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Bank Account Name -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.bank_account_name') }}</label>
                            <input type="text" value="{{ $supplier->bank_account_name }}" class="form-control form-control-solid" name="bank_account_name" />
                            <div id="bank_account_name{{ $supplier->id }}"></div>
                        </div>

                        <!-- Bank Account Number -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.bank_account_number') }}</label>
                            <input type="text" value="{{ $supplier->bank_account_number }}" class="form-control form-control-solid" name="bank_account_number" />
                            <div id="bank_account_number{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Bank SWIFT Code -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.bank_swift_code') }}</label>
                            <input type="text" value="{{ $supplier->bank_swift_code }}" class="form-control form-control-solid" name="bank_swift_code" />
                            <div id="bank_swift_code{{ $supplier->id }}"></div>
                        </div>

                        <!-- Mobile Money Number -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.mobile_money_number') }}</label>
                            <input type="text" value="{{ $supplier->mobile_money_number }}" class="form-control form-control-solid" name="mobile_money_number" />
                            <div id="mobile_money_number{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Mobile Money Provider -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.mobile_money_provider') }}</label>
                            <select name="mobile_money_provider" class="form-select form-select-solid">
                                <option value="">{{ __('auth._select') }}</option>
                                <option value="MTN" {{ $supplier->mobile_money_provider == 'MTN' ? 'selected' : '' }}>MTN</option>
                                <option value="Airtel" {{ $supplier->mobile_money_provider == 'Airtel' ? 'selected' : '' }}>Airtel</option>
                            </select>
                            <div id="mobile_money_provider{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <!-- Payment Terms Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.payment_terms') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Payment Terms Days -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-4">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('passwords.payment_terms_days') }}</label>
                            <input type="number" class="form-control form-control-solid" name="payment_terms_days" min="0" value="{{ $supplier->payment_terms_days ?? 30 }}" />
                            <div id="payment_terms_days{{ $supplier->id }}"></div>
                        </div>

                        <!-- Payment Terms Type -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-4">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('passwords.payment_terms_type') }}</label>
                            <select name="payment_terms_type" class="form-select form-select-solid">
                                <option value="net" {{ ($supplier->payment_terms_type ?? 'net') == 'net' ? 'selected' : '' }}>{{ __('passwords.net') }}</option>
                                <option value="cod" {{ ($supplier->payment_terms_type ?? '') == 'cod' ? 'selected' : '' }}>{{ __('passwords.cod') }}</option>
                                <option value="prepaid" {{ ($supplier->payment_terms_type ?? '') == 'prepaid' ? 'selected' : '' }}>{{ __('passwords.prepaid') }}</option>
                                <option value="installment" {{ ($supplier->payment_terms_type ?? '') == 'installment' ? 'selected' : '' }}>{{ __('passwords.installment') }}</option>
                            </select>
                            <div id="payment_terms_type{{ $supplier->id }}"></div>
                        </div>

                        <!-- Preferred Payment Method -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-4">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.preferred_payment_method') }}</label>
                            <select name="preferred_payment_method" class="form-select form-select-solid">
                                <option value="">{{ __('auth._select') }}</option>
                                <option value="bank_transfer" {{ $supplier->preferred_payment_method == 'bank_transfer' ? 'selected' : '' }}>{{ __('passwords.bank_transfer') }}</option>
                                <option value="mobile_money" {{ $supplier->preferred_payment_method == 'mobile_money' ? 'selected' : '' }}>{{ __('passwords.mobile_money') }}</option>
                                <option value="cash" {{ $supplier->preferred_payment_method == 'cash' ? 'selected' : '' }}>{{ __('passwords.cash') }}</option>
                                <option value="cheque" {{ $supplier->preferred_payment_method == 'cheque' ? 'selected' : '' }}>{{ __('passwords.cheque') }}</option>
                                <option value="other" {{ $supplier->preferred_payment_method == 'other' ? 'selected' : '' }}>{{ __('passwords.other') }}</option>
                            </select>
                            <div id="preferred_payment_method{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Credit Limit -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.credit_limit') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" class="form-control form-control-solid" name="credit_limit" min="0" value="{{ $supplier->credit_limit / 100 ?? 0 }}" />
                            </div>
                            <div id="credit_limit{{ $supplier->id }}"></div>
                        </div>

                        <!-- Currency Code -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.currency_code') }}</label>
                            @php
                                $currencyOptions = [];
                                foreach(config('currencies.currencies') as $code => $details) {
                                    $currencyOptions[] = (object)[
                                        'id' => $code, 
                                        'name' => $code . ' - ' . $details['name'] . ' (' . $details['symbol'] . ')'
                                    ];
                                }
                            @endphp
                            <x-typable-select 
                                name="currency_code"
                                :options="$currencyOptions"
                                selected="{{ $supplier->currency_code ?? 'UGX' }}"
                                placeholder="Type or select currency..."
                            />
                            <div id="currency_code{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <!-- Classification Section -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.classification') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <!-- Category -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.category') }}</label>
                            <input type="text" value="{{ $supplier->category }}" class="form-control form-control-solid" name="category" />
                            <div id="category{{ $supplier->id }}"></div>
                        </div>

                        <!-- Risk Level -->
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.risk_level') }}</label>
                            <select name="risk_level" class="form-select form-select-solid">
                                <option value="low" {{ ($supplier->risk_level ?? 'low') == 'low' ? 'selected' : '' }}>{{ __('passwords.low') }}</option>
                                <option value="medium" {{ ($supplier->risk_level ?? '') == 'medium' ? 'selected' : '' }}>{{ __('passwords.medium') }}</option>
                                <option value="high" {{ ($supplier->risk_level ?? '') == 'high' ? 'selected' : '' }}>{{ __('passwords.high') }}</option>
                            </select>
                            <div id="risk_level{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.additional_information') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-12">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._notes') }}</label>
                            <textarea name="notes" class="form-control form-control-solid" rows="3">{{ $supplier->notes }}</textarea>
                            <div id="notes{{ $supplier->id }}"></div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="text-center pt-15">
                        <button type="button" id="closeModalEditButton{{$supplier->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                        <button onclick="updateSupplierInstance({{ $supplier->id }})" id="editSupplierButton{{ $supplier->id }}" type="button" class="btn btn-primary">
                            <span class="indicator-label">{{ __('auth._update') }}</span>
                            <span class="indicator-progress" style="display: none;">{{ __('auth.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

