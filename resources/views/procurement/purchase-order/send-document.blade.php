<div class="modal fade" id="sendPODocumentModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-envelope me-2"></i>{{ __('passwords.send_to_supplier') }} — {{ $order->po_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="sendPODocumentForm{{ $order->id }}">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">{{ __('payments.send_via') }}</label>
                        <div class="d-flex gap-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="channel" value="email"
                                       id="po-channel-email{{ $order->id }}" checked
                                       onchange="togglePOSendChannel({{ $order->id }})">
                                <label class="form-check-label fw-semibold" for="po-channel-email{{ $order->id }}">
                                    <i class="bi bi-envelope-at me-1"></i>{{ __('payments.email') }}
                                </label>
                            </div>
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="channel" value="whatsapp"
                                       id="po-channel-whatsapp{{ $order->id }}"
                                       onchange="togglePOSendChannel({{ $order->id }})">
                                <label class="form-check-label fw-semibold" for="po-channel-whatsapp{{ $order->id }}">
                                    <i class="bi bi-whatsapp me-1"></i>{{ __('payments.whatsapp') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="po-email-field-wrap{{ $order->id }}">
                        <label class="form-label fw-semibold required">{{ __('passwords.supplier_email') }}</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ $order->supplier->email ?? '' }}" required>
                    </div>

                    <div id="po-phone-field-wrap{{ $order->id }}" class="d-none">
                        <label class="form-label fw-semibold required">{{ __('passwords.supplier_phone') }}</label>
                        <input type="tel" name="phone" class="form-control"
                               value="{{ $order->supplier->phone ?? '' }}"
                               placeholder="{{ __('payments.enter_customer_phone') }}">
                        <div id="po-phone-error{{ $order->id }}" class="form-text text-danger d-none"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.cancel') }}</button>
                    <button type="button" id="sendPODocumentButton{{ $order->id }}" class="btn btn-primary"
                            onclick="sendPODocument({{ $order->id }})">
                        <span class="indicator-label"><i class="bi bi-send me-1"></i>{{ __('payments.confirm_send') }}</span>
                        <span class="indicator-progress">
                            {{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>