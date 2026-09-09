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

                    @php
                        // Sending is purely a notification/status step now.
                        // Money only moves later, at receiving, against the
                        // actual quantities and actual costs invoiced.
                        $isSent = $order->status === 'sent';
                        $isReceived = in_array($order->status, ['received', 'partially_received']);
                        $canSend = $order->status === 'approved';
                    @endphp

                    <div class="alert alert-info d-flex align-items-center mb-5">
                        <i class="bi bi-info-circle fs-2 me-3"></i>
                        <div>
                            @if($isReceived)
                                {{ __('passwords.po_already_received') }}
                            @elseif($isSent)
                                {{ __('passwords.send_supplier_instruction_sent') }}
                            @else
                                {{ __('passwords.send_supplier_instruction') }}
                            @endif
                        </div>
                    </div>

                    {{-- ═══════════════════════════════════════════════════════════
                        🟢 ORDER SUMMARY
                        ═══════════════════════════════════════════════════════════ --}}
                    <div class="card card-flush bg-light-primary mb-6">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ __('passwords.po_number') }}:</span>
                                        <span class="fw-bold">{{ $order->po_number }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span class="text-muted">{{ __('passwords.supplier') }}:</span>
                                        <span class="fw-bold">{{ $order->supplier->name ?? '—' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span class="text-muted">{{ __('passwords.status') }}:</span>
                                        <span class="fw-bold">
                                            <span class="badge badge-{{ $order->status_badge }}">
                                                {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                            </span>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ __('passwords.total_amount') }}:</span>
                                        <span class="fw-bold text-primary fs-5">{{ number_format($order->total ?? 0, 2) }} {{ currency_symbol() }}</span>
                                    </div>
                                    <div class="form-text text-muted mt-2">
                                        {{ __('passwords.estimated_total_note') }}
                                    </div>
                                </div>
                            </div>
                            @if($isSent)
                                <div class="mt-3 alert alert-success">
                                    <i class="bi bi-check-circle me-2"></i>
                                    {{ __('passwords.po_already_sent_message') }}
                                </div>
                            @endif
                            @if($isReceived)
                                <div class="mt-3 alert alert-info">
                                    <i class="bi bi-box-seam me-2"></i>
                                    {{ __('passwords.po_already_received_message') }}
                                </div>
                            @endif
                        </div>
                    </div>
                    {{-- ════════════════ END OF ORDER SUMMARY ════════════════ --}}

                    <!-- Channel Selection -->
                    <div class="mb-4">
                        <label class="form-label required d-block">{{ __('passwords.send_via') }}</label>
                        <div class="d-flex gap-6">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="channel"
                                       id="sendChannelEmail{{ $order->id }}" value="email" checked
                                       onchange="toggleSendToSupplierChannel({{ $order->id }})"
                                       {{ $canSend ? '' : 'disabled' }}>
                                <label class="form-check-label" for="sendChannelEmail{{ $order->id }}">
                                    <i class="bi bi-envelope me-1"></i>{{ __('passwords.email') }}
                                </label>
                            </div>
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="channel"
                                       id="sendChannelWhatsapp{{ $order->id }}" value="whatsapp"
                                       onchange="toggleSendToSupplierChannel({{ $order->id }})"
                                       {{ $canSend ? '' : 'disabled' }}>
                                <label class="form-check-label" for="sendChannelWhatsapp{{ $order->id }}">
                                    <i class="bi bi-whatsapp me-1"></i>{{ __('passwords.whatsapp') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Supplier Email -->
                    <div class="mb-4" id="send-email-field-wrap{{ $order->id }}">
                        <label class="form-label required">{{ __('passwords.supplier_email') }}</label>
                        <input type="email"
                               name="supplier_email"
                               class="form-control"
                               value="{{ $order->supplier->email ?? '' }}"
                               placeholder="{{ __('passwords.enter_supplier_email') }}"
                               {{ $canSend ? '' : 'disabled' }}
                               required>
                        <div class="form-text text-muted">{{ __('passwords.supplier_email_hint') }}</div>
                    </div>

                    <!-- Supplier Phone (WhatsApp) -->
                    <div class="mb-4 d-none" id="send-phone-field-wrap{{ $order->id }}">
                        <label class="form-label required">{{ __('passwords.supplier_phone') }}</label>
                        <input type="text"
                               name="supplier_phone"
                               class="form-control"
                               value="{{ $order->supplier->phone ?? '' }}"
                               placeholder="+256700000000"
                               {{ $canSend ? '' : 'disabled' }}>
                        <div class="form-text text-muted">{{ __('passwords.supplier_phone_hint') }}</div>
                        <div class="invalid-feedback d-block d-none" id="send-phone-error{{ $order->id }}"></div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="form-label">{{ __('passwords.notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('passwords.optional_notes') }}" {{ $canSend ? '' : 'disabled' }}></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center border-top pt-6">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i>{{ __('auth._cancel') }}
                        </button>
                        <button type="button" class="btn btn-primary" id="sendToSupplierButton{{ $order->id }}"
                                onclick="sendToSupplier({{ $order->id }})"
                                {{ $canSend ? '' : 'disabled' }}>
                            <i class="bi bi-send me-2"></i>
                            <span class="indicator-label">
                                @if($isSent)
                                    {{ __('passwords.already_sent') }}
                                @elseif($isReceived)
                                    {{ __('passwords.po_already_received') }}
                                @else
                                    {{ __('passwords.send_to_supplier') }}
                                @endif
                            </span>
                            <span class="indicator-progress">{{ __('passwords.please_wait') }}
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>