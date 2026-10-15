{{-- resources/views/manufacturing/production-order/start-modal-shared.blade.php --}}

<div class="modal fade"
     id="startProductionModalShared"
     tabindex="-1"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-order-id="">

    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"
         style="max-width: 720px;">

        <div class="modal-content">

            {{-- ─── HEADER ────────────────────────────────────────── --}}
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white d-flex align-items-center flex-wrap gap-2">
                    <i class="bi bi-play-circle-fill fs-3"></i>
                    <span>
                        {{ __('passwords.start_production') }}
                        <span class="text-white-50 mx-1">•</span>
                        <span class="fw-bold po-start-number">—</span>
                    </span>
                    <span class="badge badge-light fw-bold po-start-location"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- ─── BODY ──────────────────────────────────────────── --}}
            <div class="modal-body scroll-y" style="max-height: 65vh; overflow-y: auto;">

                {{-- Info banner --}}
                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed mb-6 p-4">
                    <i class="bi bi-info-circle fs-2 text-warning me-4"></i>
                    <div class="d-flex flex-stack flex-grow-1">
                        <div class="fw-semibold">
                            <h6 class="text-gray-900 fw-bold mb-1">
                                {{ __('passwords.start_production_info') }}
                            </h6>
                            <div class="fs-7 text-gray-700">
                                {{ __('passwords.start_production_info_text') }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Input materials preview --}}
                <div class="card card-flush bg-light-danger mb-5">
                    <div class="card-header min-h-50px">
                        <h3 class="card-title fs-6 fw-bold text-gray-800">
                            <i class="bi bi-box-arrow-in-down text-danger me-2 fs-4"></i>
                            {{ __('passwords.input_materials') }}
                            <span class="badge badge-light-danger ms-2 po-start-input-count">0</span>
                        </h3>
                    </div>
                    <div class="card-body pt-3 po-start-inputs-preview">
                        <div class="text-muted fs-7">Loading inputs…</div>
                    </div>
                </div>

                {{-- Payment / Production info --}}
                <div class="card card-flush bg-light-primary mb-5">
                    <div class="card-header min-h-50px">
                        <h3 class="card-title fs-6 fw-bold text-gray-800 po-start-payment-title">
                            <i class="bi bi-credit-card text-primary me-2 fs-4"></i>
                            {{ __('payments.payment_information') }}
                        </h3>
                    </div>
                    <div class="card-body pt-3">

                        {{-- Warning / success banner toggled by JS --}}
                        <div class="po-start-payment-notice"></div>

                        <form class="po-start-form">
                            @csrf

                            {{-- Payment block (hidden if estimated_cost = 0) --}}
                            <div class="po-start-payment-block">
                                <div class="row g-4">
                                    <div class="col-md-7">
                                        <label class="form-label required fw-semibold">
                                            {{ __('payments.payment_method') }}
                                        </label>
                                        <select name="payment_method_id"
                                                class="form-select form-select-solid po-start-payment-method">
                                            <option value="">{{ __('payments.select_payment_method') }}</option>
                                            @foreach($paymentMethods ?? [] as $method)
                                                <option value="{{ $method->id }}">
                                                    {{ $method->name }}
                                                    @if($method->account_number)
                                                        — {{ $method->account_number }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label required fw-semibold">
                                            {{ __('passwords.production_cost') }}
                                        </label>
                                        <div class="input-group input-group-solid">
                                            <span class="input-group-text">{{ currency_symbol() }}</span>
                                            <input type="number"
                                                   name="withdrawal_amount"
                                                   class="form-control po-start-withdrawal"
                                                   min="0.01"
                                                   step="0.01"
                                                   value="0">
                                        </div>
                                        <div class="form-text text-muted">
                                            {{ __('passwords.estimated_cost') }}:
                                            <strong class="po-start-estimated"></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5">
                                <label class="form-label fw-semibold">{{ __('passwords.notes') }}</label>
                                <textarea name="notes"
                                          class="form-control form-control-solid"
                                          rows="2"
                                          placeholder="{{ __('passwords.start_production_notes') }}"></textarea>
                            </div>

                            <input type="hidden" name="estimated_cost" class="po-start-estimated-hidden" value="0">
                        </form>

                    </div>
                </div>

            </div>

            {{-- ─── FOOTER ────────────────────────────────────────── --}}
            <div class="modal-footer d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>{{ __('auth._cancel') }}
                </button>
                <button type="button" class="btn btn-warning po-start-submit">
                    <i class="bi bi-play-fill me-2"></i>
                    <span class="indicator-label">
                        {{ __('passwords.start_production') }}
                        <span class="badge bg-white text-warning ms-2 po-start-submit-cost"></span>
                    </span>
                    <span class="indicator-progress" style="display: none;">
                        {{ __('passwords.processing') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>