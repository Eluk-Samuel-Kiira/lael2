{{-- ── SEND RECEIPT MODAL ─────────────────────────────────── --}}
<div class="modal fade" id="sendOrderModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-envelope me-2"></i>{{ __('passwords.send_receipt') }} — {{ $order->order_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="sendOrderForm{{ $order->id }}">
                <div class="modal-body">

                    <div class="mb-4">
                        <label class="form-label fw-semibold">{{ __('payments.send_via') }}</label>
                        <div class="d-flex gap-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="channel" value="email"
                                       id="order-channel-email{{ $order->id }}" checked
                                       onchange="toggleOrderSendChannel({{ $order->id }})">
                                <label class="form-check-label fw-semibold" for="order-channel-email{{ $order->id }}">
                                    <i class="bi bi-envelope-at me-1"></i>{{ __('payments.email') }}
                                </label>
                            </div>
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="channel" value="whatsapp"
                                       id="order-channel-whatsapp{{ $order->id }}"
                                       onchange="toggleOrderSendChannel({{ $order->id }})">
                                <label class="form-check-label fw-semibold" for="order-channel-whatsapp{{ $order->id }}">
                                    <i class="bi bi-whatsapp me-1"></i>{{ __('payments.whatsapp') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="order-email-field-wrap{{ $order->id }}">
                        <label class="form-label fw-semibold required">{{ __('payments.customer_email') }}</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ $order->customer_email }}"
                               placeholder="{{ __('payments.enter_customer_email') }}"
                               required>
                    </div>

                    <div id="order-phone-field-wrap{{ $order->id }}" class="d-none">
                        <label class="form-label fw-semibold required">{{ __('payments.customer_phone') }}</label>
                        <input type="tel" name="phone" class="form-control"
                               value="{{ $order->customer_phone }}"
                               placeholder="{{ __('payments.enter_customer_phone') }}">
                        <div id="order-phone-error{{ $order->id }}" class="form-text text-danger d-none"></div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.cancel') }}</button>
                    <button type="button" id="sendOrderButton{{ $order->id }}" class="btn btn-primary"
                            onclick="sendOrderReceipt({{ $order->id }})">
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

<script>
    function toggleOrderSendChannel(orderId) {
        const form = document.getElementById('sendOrderForm' + orderId);
        const channel = form.querySelector('input[name="channel"]:checked').value;

        const isEmail = channel === 'email';
        const isPhone = channel === 'whatsapp';

        const emailWrap = document.getElementById('order-email-field-wrap' + orderId);
        const phoneWrap = document.getElementById('order-phone-field-wrap' + orderId);

        emailWrap.classList.toggle('d-none', !isEmail);
        phoneWrap.classList.toggle('d-none', !isPhone);

        emailWrap.querySelector('input[name="email"]').required = isEmail;
        phoneWrap.querySelector('input[name="phone"]').required = isPhone;
    }

    function sendOrderReceipt(orderId) {
        const submitButton = document.getElementById('sendOrderButton' + orderId);
        if (submitButton.disabled) return;

        const form = document.getElementById('sendOrderForm' + orderId);
        const payload = Object.fromEntries(new FormData(form).entries());

        if (payload.channel === 'whatsapp') {
            const result = validateE164Phone(payload.phone);
            const phoneInput = form.querySelector('input[name="phone"]');
            const errorEl = document.getElementById('order-phone-error' + orderId);

            if (!result.valid) {
                errorEl.textContent = result.error;
                errorEl.classList.remove('d-none');
                phoneInput.focus();
                return;
            }
            errorEl.classList.add('d-none');
            payload.phone = result.formatted;
        }

        if (payload.channel === 'email' && !payload.email) {
            form.querySelector('input[name="email"]').classList.add('is-invalid');
            return;
        }

        LiveBlade.toggleButtonLoading(submitButton, true);
        submitButton.disabled = true;

        fetch('/orders/' + orderId + '/send', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        })
        .then(r => r.json().then(body => ({ ok: r.ok, body })))
        .then(({ ok, body }) => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            submitButton.disabled = false;
            if (ok && body.success) {
                toastr.success(body.message);
                bootstrap.Modal.getInstance(document.getElementById('sendOrderModal' + orderId))?.hide();
            } else {
                toastr.error(body.message || '{{ __("passwords.receipt_send_failed") }}');
            }
        })
        .catch(() => {
            LiveBlade.toggleButtonLoading(submitButton, false);
            submitButton.disabled = false;
            toastr.error('{{ __("passwords.receipt_send_failed") }}');
        });
    }
</script>