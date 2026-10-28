{{-- procurement/supplier/edit.blade.php — shared edit modal, populated by JS --}}
<div class="modal fade" id="supplierEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    {{ __('auth._edit') }} {{ __('passwords._supplier') }} —
                    <span id="supplierEditHeaderName">—</span>
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh;">
                <form id="supplierEditForm" class="form">
                    @csrf
                    <input type="hidden" name="supplier_id" id="supplierEditId">

                    {{-- ─── Basic Information ─────────────────────── --}}
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.basic_information') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('auth._name') }}</label>
                            <input type="text" class="form-control form-control-solid" name="name">
                            <div id="name"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.trading_name') }}</label>
                            <input type="text" class="form-control form-control-solid" name="trading_name">
                            <div id="trading_name"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('passwords.supplier_type') }}</label>
                            <select name="supplier_type" class="form-select form-select-solid">
                                <option value="company">{{ __('passwords.company') }}</option>
                                <option value="individual">{{ __('passwords.individual') }}</option>
                                <option value="government">{{ __('passwords.government') }}</option>
                                <option value="ngo">{{ __('passwords.ngo') }}</option>
                                <option value="foreign">{{ __('passwords.foreign') }}</option>
                            </select>
                            <div id="supplier_type"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.supplier_code') }}</label>
                            <input type="text" class="form-control form-control-solid" name="supplier_code">
                            <div id="supplier_code"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.contact_person') }}</label>
                            <input type="text" class="form-control form-control-solid" name="contact_person">
                            <div id="contact_person"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-5">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active_edit" value="1">
                                <label class="form-check-label fw-semibold" for="is_active_edit">
                                    {{ __('auth._active') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- ─── Contact Information ───────────────────── --}}
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.contact_information') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._email') }}</label>
                            <input type="email" class="form-control form-control-solid" name="email">
                            <div id="email"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._phone') }}</label>
                            <input type="text" class="form-control form-control-solid" name="phone">
                            <div id="phone"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.phone_secondary') }}</label>
                            <input type="text" class="form-control form-control-solid" name="phone_secondary">
                            <div id="phone_secondary"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.website') }}</label>
                            <input type="url" class="form-control form-control-solid" name="website">
                            <div id="website"></div>
                        </div>
                    </div>

                    {{-- ─── Address ───────────────────────────────── --}}
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.address_information') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-12">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.address') }}</label>
                            <input type="text" class="form-control form-control-solid" name="address">
                            <div id="address"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._city') }}</label>
                            <input type="text" class="form-control form-control-solid" name="city">
                            <div id="city"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._state') }}</label>
                            <input type="text" class="form-control form-control-solid" name="state">
                            <div id="state"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.postal_code') }}</label>
                            <input type="text" class="form-control form-control-solid" name="postal_code">
                            <div id="postal_code"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.country') }}</label>
                            @php
                                $countryOptions = [];
                                foreach (getCountries(true, true) as $code => $name) {
                                    $countryOptions[] = (object)['id' => $code, 'name' => $name];
                                }
                            @endphp
                            <x-typable-select
                                name="country_code"
                                :options="$countryOptions"
                                selected=""
                                placeholder="Type or select country..."
                            />
                            <div id="country_code"></div>
                        </div>
                    </div>

                    {{-- ─── Tax & Compliance ──────────────────────── --}}
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.tax_compliance') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.tax_number') }}</label>
                            <input type="text" class="form-control form-control-solid" name="tax_number">
                            <div id="tax_number"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-5">
                                <input class="form-check-input" type="checkbox" name="is_vat_registered" id="is_vat_registered_edit" value="1">
                                <label class="form-check-label fw-semibold" for="is_vat_registered_edit">
                                    {{ __('passwords.vat_registered') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.vat_number') }}</label>
                            <input type="text" class="form-control form-control-solid" name="vat_number">
                            <div id="vat_number"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <div class="form-check form-switch form-check-custom form-check-solid mt-5">
                                <input class="form-check-input" type="checkbox" name="withholding_tax_applicable" id="wht_applicable_edit" value="1">
                                <label class="form-check-label fw-semibold" for="wht_applicable_edit">
                                    {{ __('passwords.withholding_tax_applicable') }}
                                </label>
                            </div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.withholding_tax_rate') }} (%)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control form-control-solid" name="withholding_tax_rate">
                            <div id="withholding_tax_rate"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.wht_exemption_ref') }}</label>
                            <input type="text" class="form-control form-control-solid" name="withholding_tax_exemption_ref">
                            <div id="withholding_tax_exemption_ref"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.wht_exemption_expiry') }}</label>
                            <input type="date" class="form-control form-control-solid" name="withholding_tax_exemption_expiry">
                            <div id="withholding_tax_exemption_expiry"></div>
                        </div>
                    </div>

                    {{-- ─── Banking ───────────────────────────────── --}}
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.banking_information') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.bank_name') }}</label>
                            <input type="text" class="form-control form-control-solid" name="bank_name">
                            <div id="bank_name"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.bank_branch') }}</label>
                            <input type="text" class="form-control form-control-solid" name="bank_branch">
                            <div id="bank_branch"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.bank_account_name') }}</label>
                            <input type="text" class="form-control form-control-solid" name="bank_account_name">
                            <div id="bank_account_name"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.bank_account_number') }}</label>
                            <input type="text" class="form-control form-control-solid" name="bank_account_number">
                            <div id="bank_account_number"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.bank_swift_code') }}</label>
                            <input type="text" class="form-control form-control-solid" name="bank_swift_code">
                            <div id="bank_swift_code"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.mobile_money_number') }}</label>
                            <input type="text" class="form-control form-control-solid" name="mobile_money_number">
                            <div id="mobile_money_number"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.mobile_money_provider') }}</label>
                            <select name="mobile_money_provider" class="form-select form-select-solid">
                                <option value="">{{ __('auth._select') }}</option>
                                <option value="MTN">MTN</option>
                                <option value="Airtel">Airtel</option>
                            </select>
                            <div id="mobile_money_provider"></div>
                        </div>
                    </div>

                    {{-- ─── Payment Terms ─────────────────────────── --}}
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.payment_terms') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-4">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('passwords.payment_terms_days') }}</label>
                            <input type="number" class="form-control form-control-solid" name="payment_terms_days" min="0">
                            <div id="payment_terms_days"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-4">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('passwords.payment_terms_type') }}</label>
                            <select name="payment_terms_type" class="form-select form-select-solid">
                                <option value="net">{{ __('passwords.net') }}</option>
                                <option value="cod">{{ __('passwords.cod') }}</option>
                                <option value="prepaid">{{ __('passwords.prepaid') }}</option>
                                <option value="installment">{{ __('passwords.installment') }}</option>
                            </select>
                            <div id="payment_terms_type"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-4">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.preferred_payment_method') }}</label>
                            <select name="preferred_payment_method" class="form-select form-select-solid">
                                <option value="">{{ __('auth._select') }}</option>
                                <option value="bank_transfer">{{ __('passwords.bank_transfer') }}</option>
                                <option value="mobile_money">{{ __('passwords.mobile_money') }}</option>
                                <option value="cash">{{ __('passwords.cash') }}</option>
                                <option value="cheque">{{ __('passwords.cheque') }}</option>
                                <option value="other">{{ __('passwords.other') }}</option>
                            </select>
                            <div id="preferred_payment_method"></div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.credit_limit') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">UGX</span>
                                <input type="number" class="form-control form-control-solid" name="credit_limit" min="0">
                            </div>
                            <div id="credit_limit"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.currency_code') }}</label>
                            @php
                                $currencyOptions = [];
                                foreach (config('currencies.currencies') as $code => $details) {
                                    $currencyOptions[] = (object)[
                                        'id'   => $code,
                                        'name' => $code . ' - ' . $details['name'] . ' (' . $details['symbol'] . ')',
                                    ];
                                }
                            @endphp
                            <x-typable-select
                                name="currency_code"
                                :options="$currencyOptions"
                                selected=""
                                placeholder="Type or select currency..."
                            />
                            <div id="currency_code"></div>
                        </div>
                    </div>

                    {{-- ─── Classification ────────────────────────── --}}
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.classification') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.category') }}</label>
                            <input type="text" class="form-control form-control-solid" name="category">
                            <div id="category"></div>
                        </div>
                        <div class="d-flex flex-column mb-8 fv-row col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords.risk_level') }}</label>
                            <select name="risk_level" class="form-select form-select-solid">
                                <option value="low">{{ __('passwords.low') }}</option>
                                <option value="medium">{{ __('passwords.medium') }}</option>
                                <option value="high">{{ __('passwords.high') }}</option>
                            </select>
                            <div id="risk_level"></div>
                        </div>
                    </div>

                    {{-- ─── Notes ─────────────────────────────────── --}}
                    <div class="separator separator-dashed my-10">
                        <h3 class="text-dark">{{ __('passwords.additional_information') }}</h3>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="d-flex flex-column mb-8 fv-row col-12">
                            <label class="fs-6 fw-semibold mb-2">{{ __('passwords._notes') }}</label>
                            <textarea class="form-control form-control-solid" name="notes" rows="3"></textarea>
                            <div id="notes"></div>
                        </div>
                    </div>

                    {{-- ─── Actions ───────────────────────────────── --}}
                    <div class="text-center pt-15">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">
                            {{ __('auth._discard') }}
                        </button>
                        <button type="button" id="supplierEditSubmit" class="btn btn-primary">
                            <span class="indicator-label">{{ __('auth._update') }}</span>
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



