<div class="modal fade" id="viewSupplier{{ $supplier->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-900px">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('passwords.supplier_details') }} - {{ $supplier->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh;">
                <div class="text-start">

                    <!-- Basic Information -->
                    <div class="card card-dashed mb-7">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('passwords.basic_information') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-9">
                                <div class="col-md-6">
                                    <label class="fw-semibold text-gray-600">{{ __('auth._name') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.trading_name') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->trading_name ?? '—' }}</div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.supplier_type') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">
                                        @php
                                            $typeLabels = [
                                                'company' => __('passwords.company'),
                                                'individual' => __('passwords.individual'),
                                                'government' => __('passwords.government'),
                                                'ngo' => __('passwords.ngo'),
                                                'foreign' => __('passwords.foreign'),
                                            ];
                                        @endphp
                                        {{ $typeLabels[$supplier->supplier_type] ?? $supplier->supplier_type }}
                                    </div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.supplier_code') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->supplier_code ?? '—' }}</div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('auth._status') }}:</label>
                                    <div class="fw-bold fs-6 {{ $supplier->is_active ? 'text-success' : 'text-danger' }}">
                                        {{ $supplier->is_active ? __('auth._active') : __('auth._inactive') }}
                                    </div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.contact_person') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->contact_person ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card card-dashed mb-7">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('passwords.contact_information') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-9">
                                <div class="col-md-6">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords._email') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->email ?? '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords._phone') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->phone ?? '—' }}</div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.phone_secondary') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->phone_secondary ?? '—' }}</div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.website') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">
                                        @if($supplier->website)
                                            <a href="{{ $supplier->website }}" target="_blank">{{ $supplier->website }}</a>
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="card card-dashed mb-7">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('passwords.address_information') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-9">
                                <div class="col-md-12">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.address') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->address ?? '—' }}</div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords._city') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->city ?? '—' }}</div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords._state') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->state ?? '—' }}</div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.postal_code') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->postal_code ?? '—' }}</div>
                                </div>
                                <div class="col-md-4 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.country_code') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->country_code ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tax & Compliance -->
                    <div class="card card-dashed mb-7">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('passwords.tax_compliance') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-9">
                                <div class="col-md-6">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.tax_number') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->tax_number ?? '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.vat_registered') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">
                                        @if($supplier->is_vat_registered)
                                            {{ __('passwords.yes') }} @if($supplier->vat_number) ({{ $supplier->vat_number }}) @endif
                                        @else
                                            {{ __('passwords.no') }}
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.withholding_tax_applicable') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">
                                        @if($supplier->withholding_tax_applicable)
                                            {{ __('passwords.yes') }} @ {{ $supplier->withholding_tax_rate }}%
                                        @elseif($supplier->withholding_tax_exemption_ref)
                                            {{ __('passwords.exempt') }} (Ref: {{ $supplier->withholding_tax_exemption_ref }})
                                            @if($supplier->withholding_tax_exemption_expiry)
                                                <br><small>Expires: {{ $supplier->withholding_tax_exemption_expiry->format('d M Y') }}</small>
                                            @endif
                                        @else
                                            {{ __('passwords.no') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Banking Information -->
                    @if($supplier->bank_name || $supplier->mobile_money_number)
                    <div class="card card-dashed mb-7">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('passwords.banking_information') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-9">
                                @if($supplier->bank_name)
                                <div class="col-md-6">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.bank_name') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->bank_name }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.bank_branch') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->bank_branch ?? '—' }}</div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.bank_account_name') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->bank_account_name ?? '—' }}</div>
                                </div>
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.bank_account_number') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->bank_account_number ?? '—' }}</div>
                                </div>
                                @if($supplier->bank_swift_code)
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.bank_swift_code') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->bank_swift_code }}</div>
                                </div>
                                @endif
                                @endif
                                
                                @if($supplier->mobile_money_number)
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.mobile_money') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">
                                        {{ $supplier->mobile_money_number }}
                                        @if($supplier->mobile_money_provider)
                                            ({{ $supplier->mobile_money_provider }})
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Payment Terms -->
                    <div class="card card-dashed mb-7">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('passwords.payment_terms') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-9">
                                <div class="col-md-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.payment_terms_days') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->payment_terms_days ?? 30 }} {{ __('auth._days') }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.payment_terms_type') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">
                                        @php
                                            $termLabels = [
                                                'net' => __('passwords.net'),
                                                'cod' => __('passwords.cod'),
                                                'prepaid' => __('passwords.prepaid'),
                                                'installment' => __('passwords.installment'),
                                            ];
                                        @endphp
                                        {{ $termLabels[$supplier->payment_terms_type ?? 'net'] ?? 'Net' }}
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.preferred_payment_method') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">
                                        @php
                                            $methodLabels = [
                                                'bank_transfer' => __('passwords.bank_transfer'),
                                                'mobile_money' => __('passwords.mobile_money'),
                                                'cash' => __('passwords.cash'),
                                                'cheque' => __('passwords.cheque'),
                                                'other' => __('passwords.other'),
                                            ];
                                        @endphp
                                        {{ $methodLabels[$supplier->preferred_payment_method] ?? '—' }}
                                    </div>
                                </div>
                                @if($supplier->credit_limit > 0)
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.credit_limit') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ number_format($supplier->credit_limit / 100, 2) }} {{ $supplier->currency_code ?? 'UGX' }}</div>
                                </div>
                                @endif
                                <div class="col-md-6 mt-4">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.currency_code') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->currency_code ?? 'UGX' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Classification -->
                    <div class="card card-dashed mb-7">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('passwords.classification') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-9">
                                <div class="col-md-6">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.category') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">{{ $supplier->category ?? '—' }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="fw-semibold text-gray-600">{{ __('passwords.risk_level') }}:</label>
                                    <div class="fw-bold fs-6 text-gray-800">
                                        @php
                                            $riskColors = [
                                                'low' => 'success',
                                                'medium' => 'warning',
                                                'high' => 'danger',
                                            ];
                                            $color = $riskColors[$supplier->risk_level ?? 'low'] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-light-{{ $color }}">
                                            {{ ucfirst($supplier->risk_level ?? 'low') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    @if($supplier->notes)
                    <div class="card card-dashed mb-7">
                        <div class="card-header">
                            <h3 class="card-title">{{ __('passwords._notes') }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->notes }}</div>
                        </div>
                    </div>
                    @endif

                    <!-- Metadata/Timestamps -->
                    <div class="text-muted fs-7 mt-5">
                        <div>{{ __('passwords.created_at') }}: {{ $supplier->created_at->format('d M Y, h:i A') }}</div>
                        <div>{{ __('passwords.updated_at') }}: {{ $supplier->updated_at->format('d M Y, h:i A') }}</div>
                    </div>

                </div>

                <div class="text-end mt-7">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._close') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>