<div class="modal fade" id="viewSupplier{{ $supplier->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-850px">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('passwords.supl_details') }} - {{ $supplier->name }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body px-5 my-7">
                <div class="text-center pt-5">

                    <div class="row g-9 mb-8">
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('auth._name') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->name }}</div>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords.contact_person') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->contact_person ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords._email') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->email ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords._phone') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->phone ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords.tax_number') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->tax_number ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords.payment_terms') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->payment_terms ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords.address') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->address ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords._city') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->city ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords._state') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->state ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords.postal_code') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->postal_code ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords.country_code') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->country_code ?? '—' }}</div>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('auth._status') }}:</label>
                            <div class="fw-bold fs-6 {{ $supplier->is_active ? 'text-success' : 'text-danger' }}">
                                {{ $supplier->is_active ? __('auth._active') : __('auth._inactive') }}
                            </div>
                        </div>
                    </div>

                    <div class="row g-9 mb-8">
                        <div class="col-12 text-start">
                            <label class="fw-semibold text-gray-600">{{ __('passwords._notes') }}:</label>
                            <div class="fw-bold fs-6 text-gray-800">{{ $supplier->notes ?? '—' }}</div>
                        </div>
                    </div>

                </div>

                <div class="text-end">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._close') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

