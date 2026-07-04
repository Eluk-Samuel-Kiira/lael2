{{-- ═══════════════════════════════════════════════════════
     Per-invoice Send + Record Payment modals.
     Include once per invoice, AFTER the </table> — not inside
     table rows, since bootstrap modals sitting inside <tbody>
     can get clipped by table overflow/stacking contexts.

     Usage (add right before <x-liveblade-pagination> in the
     component, inside a fresh @foreach over the same $invoices):

        @foreach ($invoices as $invoice)
            @include('orders.invoice.modals', ['invoice' => $invoice])
        @endforeach
═══════════════════════════════════════════════════════ --}}

{{-- ── SEND INVOICE MODAL ─────────────────────────────────── --}}
<div class="modal fade" id="sendInvoiceModal{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-envelope me-2"></i>{{ __('payments.send_invoice') }} — {{ $invoice->invoice_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="sendInvoiceForm{{ $invoice->id }}">
                <div class="modal-body">

                    @if($invoice->status === 'draft')
                    <div class="alert alert-warning d-flex align-items-center mb-4">
                        <i class="bi bi-exclamation-triangle fs-3 me-3"></i>
                        <div class="fs-7">{{ __('payments.first_send_reduces_stock_warning') }}</div>
                    </div>
                    @endif

                    <div class="mb-4">
                        <label class="form-label fw-semibold">{{ __('payments.send_via') }}</label>
                        <div class="d-flex gap-4">
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="channel" value="email"
                                       id="channel-email{{ $invoice->id }}" checked
                                       onchange="toggleSendChannel({{ $invoice->id }})">
                                <label class="form-check-label fw-semibold" for="channel-email{{ $invoice->id }}">
                                    <i class="bi bi-envelope-at me-1"></i>{{ __('payments.email') }}
                                </label>
                            </div>
                            <div class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="channel" value="download"
                                       id="channel-download{{ $invoice->id }}"
                                       onchange="toggleSendChannel({{ $invoice->id }})">
                                <label class="form-check-label fw-semibold" for="channel-download{{ $invoice->id }}">
                                    <i class="bi bi-file-earmark-arrow-down me-1"></i>{{ __('payments.download_pdf_send_manually') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="email-field-wrap{{ $invoice->id }}">
                        <label class="form-label fw-semibold required">{{ __('payments.customer_email') }}</label>
                        <input type="email" name="email" class="form-control"
                               value="{{ $invoice->billing_email }}"
                               placeholder="{{ __('payments.enter_customer_email') }}"
                               required>
                        @if(! $invoice->billing_email)
                        <div class="form-text text-danger">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ __('payments.no_email_on_file_please_add') }}
                        </div>
                        @endif
                    </div>

                    <div id="download-note{{ $invoice->id }}" class="d-none">
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            {{ __('payments.download_note_will_mark_sent') }}
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.cancel') }}</button>
                    <button type="button" id="sendInvoiceButton{{ $invoice->id }}" class="btn btn-primary"
                            onclick="sendInvoice({{ $invoice->id }})">
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

{{-- ── RECORD PAYMENT MODAL ───────────────────────────────── --}}
<div class="modal fade" id="recordPaymentModal{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-cash-coin me-2"></i>{{ __('payments.record_payment') }} — {{ $invoice->invoice_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="recordPaymentForm{{ $invoice->id }}">
                <div class="modal-body">

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="bg-light-primary rounded-3 p-3 text-center">
                                <div class="fs-7 text-muted fw-semibold">{{ __('payments.total_amount') }}</div>
                                <div class="fs-4 fw-bold text-primary">{{ currency_symbol() }} {{ number_format($invoice->total, 2) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light-danger rounded-3 p-3 text-center">
                                <div class="fs-7 text-muted fw-semibold">{{ __('payments.balance_due') }}</div>
                                <div class="fs-4 fw-bold text-danger">{{ currency_symbol() }} {{ number_format($invoice->balance_due, 2) }}</div>
                            </div>
                        </div>
                        @if($invoice->amount_paid > 0)
                        <div class="col-12">
                            <div class="bg-light-success rounded-3 p-2 text-center">
                                <span class="fs-7 fw-semibold text-success">
                                    <i class="bi bi-check-circle me-1"></i>
                                    {{ __('payments.already_paid') }}: {{ currency_symbol() }} {{ number_format($invoice->amount_paid, 2) }}
                                </span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold required">{{ __('payments.amount_received') }}</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ currency_symbol() }}</span>
                            <input type="number" step="0.01" min="0.01" max="{{ $invoice->balance_due }}"
                                   name="amount" class="form-control"
                                   value="{{ number_format($invoice->balance_due, 2, '.', '') }}"
                                   required>
                        </div>
                        <div class="form-text">{{ __('payments.leave_full_amount_or_edit_for_partial') }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold required">{{ __('payments.received_into') }}</label>
                        <select name="payment_method_id" class="form-select" required>
                            <option value="">{{ __('payments.select_payment_method') }}</option>
                            @foreach(getUniquePaymentTypes() as $type)
                                <optgroup label="{{ ucfirst(str_replace('_', ' ', $type)) }}">
                                    @foreach(getPaymentMethodsByType($type) as $method)
                                        <option value="{{ $method->id }}">
                                            {{ $method->name }}@if($method->account_number) — {{ $method->account_number }}@endif
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            {{ __('payments.transaction_reference') }}
                            <span class="text-muted fw-normal">({{ __('payments.optional') }})</span>
                        </label>
                        <input type="text" name="transaction_id" class="form-control"
                               placeholder="{{ __('payments.enter_transaction_id') }}">
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">
                            {{ __('payments.notes') }}
                            <span class="text-muted fw-normal">({{ __('payments.optional') }})</span>
                        </label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="{{ __('payments.eg_cash_received_at_counter') }}"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.cancel') }}</button>
                    <button type="button" id="recordPaymentButton{{ $invoice->id }}" class="btn btn-success"
                            onclick="submitInvoicePayment({{ $invoice->id }})">
                        <span class="indicator-label"><i class="bi bi-check-circle me-1"></i>{{ __('payments.confirm_payment') }}</span>
                        <span class="indicator-progress">
                            {{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



{{-- ── VIEW INVOICE MODAL ─────────────────────────────────── --}}
<div class="modal fade" id="viewInvoiceModal{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>
                    {{ __('payments.invoice_details') }} — {{ $invoice->invoice_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                {{-- Invoice Header --}}
                <div class="row g-4 mb-4">
                    <div class="col-md-7">
                        <div class="d-flex align-items-start gap-3">
                            {{-- Customer Initial - Smaller and cleaner --}}
                            <div class="symbol symbol-40px flex-shrink-0">
                                <div class="symbol-label bg-light-primary text-primary fw-bold fs-3">
                                    {{ strtoupper(substr($invoice->billing_name, 0, 1)) }}
                                </div>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <h4 class="fw-bold mb-1 text-truncate">{{ $invoice->billing_name }}</h4>
                                <div class="text-muted fs-7">
                                    @if($invoice->billing_email)
                                        <i class="bi bi-envelope me-1"></i>{{ $invoice->billing_email }}
                                    @endif
                                    @if($invoice->billing_phone)
                                        <span class="ms-2"><i class="bi bi-phone me-1"></i>{{ $invoice->billing_phone }}</span>
                                    @endif
                                </div>
                                @if($invoice->billing_address)
                                    <div class="text-muted fs-7 mt-1">
                                        <i class="bi bi-geo-alt me-1"></i>{{ $invoice->billing_address }}
                                    </div>
                                @endif
                                @if($invoice->tax_id)
                                    <div class="text-muted fs-7 mt-1">
                                        <span class="badge badge-light-secondary">TIN: {{ $invoice->tax_id }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="bg-light rounded-3 p-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="text-muted fs-7">{{ __('payments.invoice_number') }}</div>
                                    <div class="fw-bold fs-6">{{ $invoice->invoice_number }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted fs-7">{{ __('payments.status') }}</div>
                                    <span class="badge badge-{{ $invoice->status_color }} fw-bold px-3 py-2">
                                        <i class="bi {{ $invoice->status_icon }} me-1"></i>
                                        {{ $invoice->status_label }}
                                    </span>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted fs-7">{{ __('payments.issue_date') }}</div>
                                    <div class="fw-semibold">{{ $invoice->issue_date->format('d M Y, h:i A') }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted fs-7">{{ __('payments.due_date') }}</div>
                                    <div class="fw-semibold {{ $invoice->isOverdue() ? 'text-danger' : '' }}">
                                        {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}
                                        @if($invoice->isOverdue())
                                            <span class="badge badge-danger ms-1">{{ __('payments.overdue') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Order Reference --}}
                @if($invoice->order)
                <div class="bg-light-info rounded-3 p-3 mb-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-3">
                            <span class="text-muted fs-7">{{ __('payments.order_reference') }}</span>
                            <div class="fw-bold fs-6">
                                <a href="{{ route('orders.show', $invoice->order->id) }}" class="text-primary">
                                    #{{ $invoice->order->order_number }}
                                </a>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted fs-7">{{ __('payments.order_status') }}</span>
                            <div>
                                <span class="badge badge-{{ $invoice->order->status == 'completed' ? 'success' : ($invoice->order->status == 'cancelled' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($invoice->order->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted fs-7">{{ __('payments.location') }}</span>
                            <div class="fw-semibold">{{ $invoice->order->location->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted fs-7">{{ __('payments.department') }}</span>
                            <div class="fw-semibold">{{ $invoice->order->department->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Order Items Table - Fixed right alignment --}}
                @if($invoice->order && $invoice->order->orderItems->count() > 0)
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th class="text-start" style="min-width: 180px;">{{ __('payments.item') }}</th>
                                <th class="text-center" style="width: 100px;">{{ __('payments.sku') }}</th>
                                <th class="text-end" style="width: 120px;">{{ __('payments.unit_price') }}</th>
                                <th class="text-center" style="width: 70px;">{{ __('payments.qty') }}</th>
                                <th class="text-end" style="width: 120px;">{{ __('payments.discount') }}</th>
                                <th class="text-end" style="width: 120px;">{{ __('payments.tax') }}</th>
                                <th class="text-end" style="width: 140px; min-width: 140px;">{{ __('payments.total') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->order->orderItems as $index => $item)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="text-start">
                                    <div class="fw-semibold">{{ $item->item_name }}</div>
                                    @if($item->variant)
                                        <span class="text-muted fs-7">Variant: {{ $item->variant->name ?? 'N/A' }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light-secondary">{{ $item->sku ?? '—' }}</span>
                                </td>
                                <td class="text-end fw-semibold">{{ currency_symbol() }} {{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge badge-light-primary">{{ $item->quantity }}</span>
                                </td>
                                <td class="text-end">
                                    @if($item->discount > 0)
                                        <span class="text-danger">{{ currency_symbol() }} {{ number_format($item->discount, 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($item->tax_amount > 0)
                                        <span class="text-warning">{{ currency_symbol() }} {{ number_format($item->tax_amount, 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">{{ currency_symbol() }} {{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td colspan="7" class="text-end pe-3">{{ __('payments.subtotal') }}</td>
                                <td class="text-end pe-3">{{ currency_symbol() }} {{ number_format($invoice->subtotal, 2) }}</td>
                            </tr>
                            @if($invoice->discount_total > 0)
                            <tr>
                                <td colspan="7" class="text-end pe-3 text-danger">{{ __('payments.discount') }}</td>
                                <td class="text-end pe-3 text-danger">-{{ currency_symbol() }} {{ number_format($invoice->discount_total, 2) }}</td>
                            </tr>
                            @endif
                            @if($invoice->tax_total > 0)
                            <tr>
                                <td colspan="7" class="text-end pe-3 text-warning">{{ __('payments.tax') }}</td>
                                <td class="text-end pe-3 text-warning">{{ currency_symbol() }} {{ number_format($invoice->tax_total, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="fs-5">
                                <td colspan="7" class="text-end pe-3 text-primary">{{ __('payments.grand_total') }}</td>
                                <td class="text-end pe-3 text-primary fw-bold">{{ currency_symbol() }} {{ number_format($invoice->total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif

                {{-- Payment Summary --}}
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="bg-light-primary rounded-3 p-3 text-center">
                            <div class="text-muted fs-7">{{ __('payments.total_amount') }}</div>
                            <div class="fs-4 fw-bold text-primary">{{ currency_symbol() }} {{ number_format($invoice->total, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="bg-light-success rounded-3 p-3 text-center">
                            <div class="text-muted fs-7">{{ __('payments.amount_paid') }}</div>
                            <div class="fs-4 fw-bold text-success">{{ currency_symbol() }} {{ number_format($invoice->amount_paid, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="bg-light-danger rounded-3 p-3 text-center">
                            <div class="text-muted fs-7">{{ __('payments.balance_due') }}</div>
                            <div class="fs-4 fw-bold text-danger">{{ currency_symbol() }} {{ number_format($invoice->balance_due, 2) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="bg-light-info rounded-3 p-3 text-center">
                            <div class="text-muted fs-7">{{ __('payments.payment_status') }}</div>
                            <div class="fs-6 fw-bold">
                                @if($invoice->isPaid())
                                    <span class="text-success"><i class="bi bi-check-circle me-1"></i>{{ __('payments.paid') }}</span>
                                @elseif($invoice->isPartiallyPaid())
                                    <span class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>{{ __('payments.partial') }}</span>
                                @else
                                    <span class="text-danger"><i class="bi bi-x-circle me-1"></i>{{ __('payments.unpaid') }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment History --}}
                @if($invoice->payments && $invoice->payments->count() > 0)
                <div class="mb-4">
                    <h6 class="fw-bold mb-3">{{ __('payments.payment_history') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-start">{{ __('payments.date') }}</th>
                                    <th class="text-end">{{ __('payments.amount') }}</th>
                                    <th class="text-start">{{ __('payments.method') }}</th>
                                    <th class="text-start">{{ __('payments.transaction_id') }}</th>
                                    <th class="text-center">{{ __('payments.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoice->payments as $payment)
                                <tr>
                                    <td class="text-start">{{ $payment->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="text-end fw-bold">{{ currency_symbol() }} {{ number_format($payment->amount, 2) }}</td>
                                    <td class="text-start">{{ $payment->paymentMethod->name ?? 'N/A' }}</td>
                                    <td class="text-start"><span class="badge badge-light-secondary">{{ $payment->transaction_id ?? '—' }}</span></td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $payment->status == 'completed' ? 'success' : ($payment->status == 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Notes & Terms --}}
                @if($invoice->notes || $invoice->terms)
                <div class="row g-3">
                    @if($invoice->notes)
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3">
                            <h6 class="fw-bold mb-2"><i class="bi bi-sticky me-1"></i>{{ __('payments.notes') }}</h6>
                            <p class="mb-0 text-muted">{{ $invoice->notes }}</p>
                        </div>
                    </div>
                    @endif
                    @if($invoice->terms)
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3">
                            <h6 class="fw-bold mb-2"><i class="bi bi-file-text me-1"></i>{{ __('payments.terms_conditions') }}</h6>
                            <p class="mb-0 text-muted">{{ $invoice->terms }}</p>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
            <div class="modal-footer">
                @can('download invoice')
                <a href="{{ route('invoices.pdf', $invoice->id) }}" target="_blank" class="btn btn-secondary">
                    <i class="bi bi-file-pdf me-1"></i>{{ __('payments.download_pdf') }}
                </a>
                @endcan
                @can('send invoice')
                @if($invoice->status !== 'void' && $invoice->status !== 'paid')
                <button type="button" class="btn btn-success" data-bs-dismiss="modal" 
                        data-bs-toggle="modal" data-bs-target="#sendInvoiceModal{{ $invoice->id }}">
                    <i class="bi bi-envelope me-1"></i>{{ __('payments.send_invoice') }}
                </button>
                @endif
                @endcan
                @can('record invoice payment')
                @if(!in_array($invoice->status, ['void', 'paid', 'cancelled']))
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal"
                        data-bs-toggle="modal" data-bs-target="#recordPaymentModal{{ $invoice->id }}">
                    <i class="bi bi-cash-coin me-1"></i>{{ __('payments.record_payment') }}
                </button>
                @endif
                @endcan
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.close') }}</button>
            </div>
        </div>
    </div>
</div>


<!-- Delete User Modal -->
<div class="modal fade" id="deleteInvoiceModal{{$invoice->id}}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('auth.are_you_sure') }}</p>
                <p>{{ __('auth.action_cannot') }}</p>
            </div>
            <div class="modal-footer">
                <!-- Discard Button -->
                <button type="button" id="closeDeleteModal{{$invoice->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                <!-- Confirm Button -->
                <button type="button" id="deleteButton{{$invoice->id}}" class="btn btn-danger" 
                    data-item-url="{{ route('invoices.destroy', $invoice->id) }}" 
                    data-item-id="{{ $invoice->id }}"
                    onclick="deleteItem(this)">
                    <span class="indicator-label">{{ __('auth._confirm') }}</span>
                    <span class="indicator-progress" style="display: none;">
                        {{__('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── VOID INVOICE MODAL ─────────────────────────────────── --}}
<div class="modal fade" id="voidInvoiceModal{{ $invoice->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark">
                <h5 class="modal-title text-white fw-bold mb-0">
                    <i class="bi bi-ban me-2"></i>{{ __('payments.void_invoice') }} — {{ $invoice->invoice_number }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="voidInvoiceForm{{ $invoice->id }}">
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-exclamation-diamond fs-1 text-warning mb-3 d-block"></i>
                        <h5 class="fw-bold">{{ __('payments.are_you_sure_void') }}</h5>
                        <p class="text-muted">{{ __('payments.void_warning') }}</p>
                        <div class="bg-light p-3 rounded-3 mt-3">
                            <div class="row">
                                <div class="col-6">
                                    <span class="text-muted">{{ __('payments.invoice_number') }}</span>
                                    <div class="fw-bold">{{ $invoice->invoice_number }}</div>
                                </div>
                                <div class="col-6">
                                    <span class="text-muted">{{ __('payments.status') }}</span>
                                    <div class="fw-bold">
                                        <span class="badge badge-{{ $invoice->status_color }}">
                                            {{ $invoice->status_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @if($invoice->amount_paid > 0)
                            <div class="mt-3">
                                <span class="text-muted">{{ __('payments.amount_paid') }}</span>
                                <div class="fw-bold text-danger">{{ currency_symbol() }} {{ number_format($invoice->amount_paid, 2) }}</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('payments.void_reason') }}</label>
                        <textarea name="reason" class="form-control" rows="2" 
                                  placeholder="{{ __('payments.enter_void_reason') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('payments.cancel') }}</button>
                    <button type="button" id="voidInvoiceButton{{ $invoice->id }}" class="btn btn-dark"
                            onclick="voidInvoice({{ $invoice->id }})">
                        <span class="indicator-label"><i class="bi bi-check-circle me-1"></i>{{ __('payments.confirm_void') }}</span>
                        <span class="indicator-progress">
                            {{ __('payments.processing') }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>