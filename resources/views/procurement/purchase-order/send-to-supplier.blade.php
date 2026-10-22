<!-- Send to Supplier Modal Content -->
<div class="modal fade" id="sendToSupplierModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-700px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-send me-2"></i>
                    {{ __('passwords.send_to_supplier') }} - {{ $order->po_number }}
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body px-5 my-7">
                <form id="sendToSupplierForm{{ $order->id }}">
                    @csrf
                    <div class="alert alert-info d-flex align-items-center mb-5">
                        <i class="bi bi-info-circle fs-2 me-3"></i>
                        <div>
                            {{ __('passwords.send_supplier_instruction') }}
                        </div>
                    </div>

                    <!-- Channel Selection -->
                    <div class="mb-4">
                        <label class="form-label required d-block">{{ __('passwords.send_via') }}</label>
                        <div class="d-flex gap-6">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="channel"
                                       id="sendChannelEmail{{ $order->id }}" value="email" checked>
                                <label class="form-check-label" for="sendChannelEmail{{ $order->id }}">
                                    <i class="bi bi-envelope me-1"></i>{{ __('passwords.email') }}
                                </label>
                            </div>
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="channel"
                                       id="sendChannelWhatsapp{{ $order->id }}" value="whatsapp">
                                <label class="form-check-label" for="sendChannelWhatsapp{{ $order->id }}">
                                    <i class="bi bi-whatsapp me-1"></i>{{ __('passwords.whatsapp') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Supplier Email -->
                    <div class="mb-4" id="send-email-field-wrap{{ $order->id }}">
                        <label class="form-label required">{{ __('passwords.supplier_email') }}</label>
                        <input type="email" name="supplier_email" class="form-control"
                               value="{{ $order->supplier->email ?? '' }}"
                               placeholder="{{ __('passwords.enter_supplier_email') }}" required>
                        <div class="form-text text-muted">{{ __('passwords.supplier_email_hint') }}</div>
                    </div>

                    <!-- Supplier Phone (WhatsApp) -->
                    <div class="mb-4 d-none" id="send-phone-field-wrap{{ $order->id }}">
                        <label class="form-label required">{{ __('passwords.supplier_phone') }}</label>
                        <input type="text" name="supplier_phone" class="form-control"
                               value="{{ $order->supplier->phone ?? '' }}"
                               placeholder="+256700000000">
                        <div class="form-text text-muted">{{ __('passwords.supplier_phone_hint') }}</div>
                        <div class="invalid-feedback d-block d-none" id="send-phone-error{{ $order->id }}"></div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="form-label">{{ __('passwords.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('passwords.optional_notes') }}"></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center border-top pt-6">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i>{{ __('auth._cancel') }}
                        </button>
                        <button type="button" class="btn btn-primary" id="sendToSupplierButton{{ $order->id }}" data-send-action="{{ $order->id }}">
                            <i class="bi bi-send me-2"></i>
                            <span class="indicator-label">{{ __('passwords.send_to_supplier') }}</span>
                            <span class="indicator-progress d-none">{{ __('passwords.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Delete Modal -->
<div class="modal fade" id="deletePurchaseModal{{$order->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-flex align-items-center mb-4">
                    <i class="bi bi-exclamation-triangle fs-2 me-3"></i>
                    <div>
                        <p class="mb-1 fw-bold">{{ __('auth.are_you_sure') }}</p>
                        <p class="mb-0 fs-7">{{ __('auth.action_cannot') }}</p>
                    </div>
                </div>
                <p class="text-muted fs-7">
                    You are about to delete Purchase Order <strong>{{ $order->po_number }}</strong>.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">
                    {{ __('auth._discard') }}
                </button>
                <button type="button" 
                        class="btn btn-danger" 
                        data-delete-action="{{ $order->id }}"
                        data-url="{{ route('purchase-orders.destroy', $order->id) }}">
                    <span class="indicator-label"><i class="bi bi-trash me-1"></i>{{ __('auth._confirm') }}</span>
                    <span class="indicator-progress d-none">
                        {{__('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>