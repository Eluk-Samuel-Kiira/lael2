<!-- Receive Items Modal -->
<div class="modal fade" id="receiveItemsModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="bi bi-box-seam me-2"></i>
                    {{ __('passwords.receive_items') }} - {{ $order->po_number }}
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"></i>
                </div>
            </div>

            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh; overflow-y: auto;">
                <form id="receiveItemsForm{{ $order->id }}" class="form">
                    @csrf

                    <div class="alert alert-info d-flex align-items-center mb-5">
                        <i class="bi bi-info-circle fs-2 me-3"></i>
                        <div>{{ __('passwords.receive_items_instruction') }}</div>
                    </div>

                    <div class="alert alert-light-warning d-flex align-items-center mb-5">
                        <i class="bi bi-exclamation-triangle fs-2 me-3 text-warning"></i>
                        <div>{{ __('passwords.actual_cost_may_differ_note') }}</div>
                    </div>

                    <!-- Tax Information Section -->
                    <div class="card card-flush bg-light-warning mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-receipt text-warning me-2"></i>
                                {{ __('passwords.tax_information') }}
                            </h3>
                            <div class="card-toolbar">
                                <button type="button" class="btn btn-sm btn-primary" onclick="calculateTaxPreview({{ $order->id }})">
                                    <i class="bi bi-calculator me-2"></i>
                                    {{ __('passwords.preview_calculation') }}
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold mb-3">{{ __('passwords.select_applicable_taxes') }}</label>
                                <div class="row g-4">
                                    @foreach($taxes as $tax)
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <div class="form-check form-check-custom form-check-solid">
                                            <input class="form-check-input tax-checkbox"
                                                type="checkbox"
                                                name="selected_taxes[]"
                                                value="{{ $tax->id }}"
                                                id="tax_{{ $tax->id }}_{{ $order->id }}">
                                            <label class="form-check-label" for="tax_{{ $tax->id }}_{{ $order->id }}">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="fw-bold">{{ $tax->name }}</span>
                                                    @if($tax->is_withholding_tax)
                                                        <span class="badge badge-light-danger">Withholding</span>
                                                    @else
                                                        <span class="badge badge-light-primary">Additive</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span class="badge badge-light-info">{{ $tax->formatted_rate }}</span>
                                                    <small class="text-muted ms-2">{{ $tax->code }}</small>
                                                </div>
                                                @if($tax->is_withholding_tax)
                                                    <small class="text-danger d-block mt-1">
                                                        <i class="bi bi-arrow-down-short me-1"></i>
                                                        {{ __('passwords.withholding_tax_note') }}
                                                    </small>
                                                @else
                                                    <small class="text-primary d-block mt-1">
                                                        <i class="bi bi-arrow-up-short me-1"></i>
                                                        {{ __('passwords.additive_tax_note') }}
                                                    </small>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Tax Preview Result -->
                            <div id="tax_preview_{{ $order->id }}" class="mt-4 p-4 bg-light rounded d-none">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h5 class="mb-0">{{ __('passwords.tax_calculation_summary') }}</h5>
                                    <span class="badge badge-light-primary">{{ __('passwords.preview') }}</span>
                                </div>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-4">
                                        <div class="card card-dashed bg-white">
                                            <div class="card-body p-3 text-center">
                                                <span class="text-muted fw-bold d-block">{{ __('passwords.taxable_amount') }}</span>
                                                <span class="fw-bold fs-2 text-dark" id="preview_taxable_{{ $order->id }}">0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card card-dashed bg-white">
                                            <div class="card-body p-3 text-center">
                                                <span class="text-muted fw-bold d-block">{{ __('passwords.total_tax') }}</span>
                                                <span class="fw-bold fs-2 text-warning" id="preview_total_tax_{{ $order->id }}">0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card card-dashed bg-white">
                                            <div class="card-body p-3 text-center">
                                                <span class="text-muted fw-bold d-block">{{ __('passwords.net_payable') }}</span>
                                                <span class="fw-bold fs-2 text-success" id="preview_net_payable_{{ $order->id }}">0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-gray-100 align-middle">
                                        <thead>
                                            <tr class="fw-bold text-muted bg-light">
                                                <th>{{ __('passwords.tax_name') }}</th>
                                                <th>{{ __('passwords.rate') }}</th>
                                                <th class="text-end">{{ __('passwords.amount') }}</th>
                                                <th class="text-center">{{ __('passwords.effect') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tax_breakdown_body_{{ $order->id }}">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items to Receive -->
                    <div class="card card-flush mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-box-seam me-2"></i>
                                {{ __('passwords.items_to_receive') }}
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                    <thead>
                                        <tr class="fw-bold text-muted bg-light">
                                            <th class="ps-4 min-w-200px">{{ __('passwords.product') }}</th>
                                            <th class="min-w-90px">{{ __('passwords.po_unit_cost') }}</th>
                                            <th class="min-w-150px">{{ __('passwords.balance') }}</th>
                                            <th class="min-w-130px text-primary">{{ __('passwords.receiving_now') }}</th>
                                            <th class="min-w-150px text-warning">{{ __('passwords.actual_unit_cost') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->items as $item)
                                        @php
                                            $ordered = $item->quantity;
                                            $received = $item->received_quantity;
                                            $pending = $ordered - $received;
                                            $progress = $ordered > 0 ? ($received / $ordered) * 100 : 0;
                                        @endphp
                                        <tr class="receive-item-row">
                                            <td class="ps-4">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold text-gray-800">{{ $item->product_name }}</span>
                                                    <span class="text-muted fs-7">SKU: {{ $item->sku }}</span>
                                                </div>
                                                <input type="hidden" name="items[{{ $item->id }}][purchase_order_item_id]" value="{{ $item->id }}">
                                                <input type="hidden" name="items[{{ $item->id }}][product_variant_id]" value="{{ $item->product_variant_id }}">
                                            </td>
                                            <td>
                                                <span class="fw-bold text-muted">{{ number_format($item->unit_cost, 2) }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-muted">{{ __('passwords.ordered') }}:</span>
                                                        <span class="fw-bold">{{ $ordered }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-1">
                                                        <span class="text-success">{{ __('passwords.received') }}:</span>
                                                        <span class="fw-bold text-success">{{ $received }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span class="text-warning">{{ __('passwords.pending') }}:</span>
                                                        <span class="fw-bold text-warning">{{ $pending }}</span>
                                                    </div>
                                                    <div class="progress" style="height: 4px;">
                                                        <div class="progress-bar bg-success" role="progressbar"
                                                            style="width: {{ $progress }}%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number"
                                                    name="items[{{ $item->id }}][quantity_received]"
                                                    class="form-control receiving-quantity"
                                                    min="0"
                                                    value="0"
                                                    data-pending="{{ $pending }}"
                                                    data-unit-cost="{{ $item->unit_cost }}"
                                                    oninput="updateItemsTotal({{ $order->id }})">
                                                <small class="text-muted fs-7">{{ __('passwords.max') }}: {{ $pending }}</small>
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text">{{ currency_symbol() }}</span>
                                                    <input type="number"
                                                        name="items[{{ $item->id }}][actual_unit_cost]"
                                                        class="form-control receiving-actual-cost"
                                                        min="0"
                                                        step="0.01"
                                                        value="{{ number_format($item->unit_cost, 2, '.', '') }}"
                                                        oninput="updateItemsTotal({{ $order->id }})">
                                                </div>
                                                <small class="text-muted fs-7">{{ __('passwords.defaults_to_po_price') }}</small>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Batch Information & Payment -->
                    <div class="row g-6 mb-6">
                        <div class="col-md-6">
                            <div class="card card-flush bg-light h-100">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="bi bi-upc-scan me-2"></i>
                                        {{ __('passwords.batch_information') }}
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('passwords.batch_number') }}</label>
                                        <input type="text" name="batch_number" class="form-control" placeholder="{{ __('passwords.enter_batch_number') }}" required>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">{{ __('passwords.expiry_date') }}</label>
                                        <input type="date" name="expiry_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment (optional, against the actual receipt total) -->
                        <div class="col-md-6">
                            <div class="card card-flush bg-light-success h-100">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="bi bi-credit-card me-2"></i>
                                        {{ __('payments.payment_information') }}
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <div class="text-muted fs-7 mb-3">
                                        {{ __('passwords.payment_optional_at_receipt_note') }}
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('payments.payment_method') }}</label>
                                        <select name="payment_method_id" id="payment_method_{{ $order->id }}" class="form-select">
                                            <option value="">{{ __('payments.select_payment_method') }}</option>
                                            @foreach($active_payment_methods as $method)
                                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">{{ __('passwords.payment_amount') }}</label>
                                        <div class="input-group">
                                            <span class="input-group-text">{{ currency_symbol() }}</span>
                                            <input type="number"
                                                name="payment_amount"
                                                id="payment_amount_{{ $order->id }}"
                                                class="form-control"
                                                step="0.01"
                                                min="0"
                                                max="0"
                                                value="0"
                                                onblur="if (this.value === '') this.value = '0';"
                                                oninput="updateItemsTotal({{ $order->id }})">
                                        </div>
                                        <div class="form-text text-muted">
                                            {{ __('passwords.max_payment') }}: <span id="payment_max_display_{{ $order->id }}">0.00</span> {{ currency_symbol() }}
                                            &mdash; {{ __('passwords.based_on_this_receipt') }}
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label">{{ __('payments.payment_date') }}</label>
                                        <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Receiving Summary -->
                    <div class="card card-flush bg-light-primary mb-6">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="bi bi-calculator me-2"></i>
                                {{ __('passwords.receiving_summary') }}
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.gross_amount') }}</span>
                                        <span class="fw-bold fs-2 text-dark" id="gross_amount_display_{{ $order->id }}">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.taxable_amount') }}</span>
                                        <span class="fw-bold fs-2 text-dark" id="taxable_amount_display_{{ $order->id }}">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_tax') }}</span>
                                        <span class="fw-bold fs-2 text-danger" id="total_tax_display_{{ $order->id }}">0.00</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <span class="text-muted d-block">{{ __('passwords.total_payable') }}</span>
                                        <span class="fw-bold fs-2 text-primary" id="total_payable_display_{{ $order->id }}">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-6">
                        <label class="form-label">{{ __('passwords.receiving_notes') }}</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="{{ __('passwords.enter_receiving_notes') }}"></textarea>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" name="total_tax_amount" id="total_tax_amount_{{ $order->id }}" value="0">
                    <input type="hidden" name="net_amount" id="net_amount_{{ $order->id }}" value="0">
                    <input type="hidden" name="taxable_amount" id="taxable_amount_hidden_{{ $order->id }}" value="0">

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center border-top pt-6">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="bi bi-x-lg me-2"></i>{{ __('auth._cancel') }}
                        </button>
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-warning" onclick="submitReceiving({{ $order->id }}, 'partially_received')">
                                <i class="bi bi-arrow-repeat me-2"></i>{{ __('passwords.mark_partially_received') }}
                            </button>
                            <button type="button" class="btn btn-success" onclick="submitReceiving({{ $order->id }}, 'received')">
                                <i class="bi bi-check-circle me-2"></i>{{ __('passwords.mark_fully_received') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    // ── Debounce function for performance ──
    function debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ── Update items total (debounced) ──
    // Uses each row's ACTUAL unit cost (editable) rather than the fixed PO price,
    // since that's what will really leave the account.
    const updateItemsTotal = debounce(function(orderId) {
        let itemsTotal = 0;
        let totalQuantity = 0;
        const inputs = document.querySelectorAll(`#receiveItemsForm${orderId} .receiving-quantity`);

        inputs.forEach(input => {
            const quantity = parseFloat(input.value) || 0;
            const row = input.closest('.receive-item-row');
            const actualCostInput = row ? row.querySelector('.receiving-actual-cost') : null;
            const fallbackCost = parseFloat(input.dataset.unitCost) || 0;
            const actualCost = actualCostInput && actualCostInput.value !== ''
                ? (parseFloat(actualCostInput.value) || 0)
                : fallbackCost;

            itemsTotal += quantity * actualCost;
            totalQuantity += quantity;

            // Highlight rows with quantity being received
            if (row) {
                row.style.backgroundColor = quantity > 0 ? '#e8f5e9' : '';
                row.style.borderLeft = quantity > 0 ? '3px solid #4caf50' : '';
            }

            // Actual cost only matters once something is being received
            if (actualCostInput) {
                actualCostInput.disabled = quantity <= 0;
            }
        });

        // Update displays
        const displays = {
            gross: document.getElementById(`gross_amount_display_${orderId}`),
            taxable: document.getElementById(`taxable_amount_display_${orderId}`),
            taxableHidden: document.getElementById(`taxable_amount_hidden_${orderId}`),
            totalTax: document.getElementById(`total_tax_display_${orderId}`),
            totalPayable: document.getElementById(`total_payable_display_${orderId}`)
        };

        if (displays.gross) displays.gross.textContent = itemsTotal.toFixed(2);
        if (displays.taxable) displays.taxable.textContent = itemsTotal.toFixed(2);
        if (displays.taxableHidden) displays.taxableHidden.value = itemsTotal;

        // Reset tax preview when items change
        const taxPreview = document.getElementById(`tax_preview_${orderId}`);
        if (taxPreview) taxPreview.classList.add('d-none');

        if (displays.totalTax) displays.totalTax.textContent = '0.00';
        if (displays.totalPayable) displays.totalPayable.textContent = itemsTotal.toFixed(2);

        document.getElementById(`total_tax_amount_${orderId}`).value = '0';
        document.getElementById(`net_amount_${orderId}`).value = itemsTotal;

        // Keep the payment amount in sync with the current receipt total
        updatePaymentMax(orderId, itemsTotal);
    }, 300);

    // ── Keep payment amount bounded to (and defaulted at) the current receipt payable ──
    function updatePaymentMax(orderId, payableAmount) {
        const maxDisplay = document.getElementById(`payment_max_display_${orderId}`);
        const paymentInput = document.getElementById(`payment_amount_${orderId}`);
        if (!paymentInput) return;

        const rounded = Math.max(0, payableAmount || 0);
        paymentInput.setAttribute('max', rounded.toFixed(2));
        if (maxDisplay) maxDisplay.textContent = rounded.toFixed(2);

        // If the current payment amount exceeds the new max, clamp it down
        const current = parseFloat(paymentInput.value) || 0;
        if (current > rounded) {
            paymentInput.value = rounded.toFixed(2);
        }
    }

    // ── Calculate tax preview ──
    function calculateTaxPreview(orderId) {
        const taxableAmount = parseFloat(document.getElementById(`taxable_amount_hidden_${orderId}`).value) || 0;
        const selectedTaxes = Array.from(
            document.querySelectorAll(`#receiveItemsForm${orderId} .tax-checkbox:checked`)
        ).map(cb => cb.value);

        if (selectedTaxes.length === 0 || taxableAmount === 0) {
            Swal.fire('Info', 'Please select taxes and enter quantities first', 'info');
            return;
        }

        Swal.fire({
            title: 'Calculating...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch('/purchase-orders/calculate-tax-preview', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                taxable_amount: taxableAmount,
                selected_taxes: selectedTaxes
            })
        })
        .then(response => response.json())
        .then(data => {
            Swal.close();

            if (data.success) {
                const preview = document.getElementById(`tax_preview_${orderId}`);
                if (preview) preview.classList.remove('d-none');

                // Update preview values
                document.getElementById(`preview_taxable_${orderId}`).textContent = data.data.taxable_amount.toFixed(2);
                document.getElementById(`preview_total_tax_${orderId}`).textContent = data.data.total_tax.toFixed(2);
                document.getElementById(`preview_net_payable_${orderId}`).textContent = data.data.net_payable.toFixed(2);

                document.getElementById(`total_tax_display_${orderId}`).textContent = data.data.total_tax.toFixed(2);
                document.getElementById(`total_payable_display_${orderId}`).textContent = data.data.net_payable.toFixed(2);

                document.getElementById(`total_tax_amount_${orderId}`).value = data.data.total_tax;
                document.getElementById(`net_amount_${orderId}`).value = data.data.net_payable;

                // Net payable (incl. tax) becomes the real cap for payment against this receipt
                updatePaymentMax(orderId, data.data.net_payable);

                // Build breakdown
                const tbody = document.getElementById(`tax_breakdown_body_${orderId}`);
                if (tbody) {
                    tbody.innerHTML = data.data.tax_breakdown.map(tax => `
                        <tr>
                            <td><strong>${tax.name}</strong><br><small>${tax.code || ''}</small></td>
                            <td>${tax.formatted_rate}</td>
                            <td class="text-end">${tax.amount.toFixed(2)}</td>
                            <td class="text-center">
                                <span class="badge ${tax.is_withholding_tax ? 'badge-light-danger' : 'badge-light-primary'}">
                                    ${tax.is_withholding_tax ? 'Deducted (-)' : 'Added (+)'}
                                </span>
                            </td>
                        </tr>
                    `).join('');
                }

                Swal.fire('Success', 'Tax calculation completed', 'success');
            } else {
                Swal.fire('Error', data.message || 'Calculation failed', 'error');
            }
        })
        .catch(error => {
            Swal.close();
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to calculate taxes', 'error');
        });
    }

    // ── Submit receiving ──
    // Note: receiving no longer requires the PO balance to be settled first —
    // payment here (if any) is recorded against the ACTUAL receipt total,
    // computed from real quantities and real unit costs.
    function submitReceiving(orderId, status) {
        const form = document.getElementById(`receiveItemsForm${orderId}`);
        if (!form) return;

        // Check if any items are being received
        const hasQuantity = Array.from(form.querySelectorAll('.receiving-quantity'))
            .some(input => parseFloat(input.value) > 0);

        if (!hasQuantity) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text: '{{ __("passwords.enter_quantity_for_at_least_one_item") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        // If a payment amount was entered, a payment method is required
        const paymentAmount = parseFloat(document.getElementById(`payment_amount_${orderId}`)?.value) || 0;
        const paymentMethodSelect = document.getElementById(`payment_method_${orderId}`);
        if (paymentAmount > 0 && paymentMethodSelect && !paymentMethodSelect.value) {
            Swal.fire({
                title: '{{ __("passwords.validation_error") }}',
                text: '{{ __("payments.select_payment_method") }}',
                icon: 'warning',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }

        const formData = new FormData(form);
        formData.append('status', status);

        // Always send payment_amount — even when 0 — so the backend can record intent.
        formData.set('payment_amount', String(Math.max(0, paymentAmount)));

        // Only send method/date if there's actually a payment.
        if (paymentAmount <= 0) {
            formData.delete('payment_method_id');
            formData.delete('payment_date');
        }

        Swal.fire({
            title: status === 'received'
                ? '{{ __("passwords.mark_fully_received_title") }}'
                : '{{ __("passwords.mark_partially_received_title") }}',
            text: status === 'received'
                ? '{{ __("passwords.mark_fully_received_confirmation") }}'
                : '{{ __("passwords.mark_partially_received_confirmation") }}',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: status === 'received' ? '#198754' : '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: status === 'received'
                ? '{{ __("passwords.mark_received") }}'
                : '{{ __("passwords.mark_partial") }}',
            cancelButtonText: '{{ __("passwords.cancel") }}',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch(`/purchase-orders/${orderId}/receive-items`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success && data.requires_confirm) {
                        // Show the confirmation dialog with the over-receipt details
                        return Swal.fire({
                            title: '{{ __("passwords.over_receipt_title") }}',
                            html: buildOverReceiptHtml(data.over_receipts),
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: '{{ __("passwords.yes_receive_more") }}',
                            cancelButtonText: '{{ __("passwords.cancel") }}',
                            confirmButtonColor: '#dc3545',
                        }).then(result => {
                            if (!result.isConfirmed) return null;

                            // Resubmit with the confirmation flag
                            formData.append('allow_over_receipt', '1');
                            return fetch(`/purchase-orders/${orderId}/receive-items`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                    'Accept': 'application/json',
                                },
                                body: formData
                            }).then(r => r.json());
                        });
                    }
                    if (!data.success) throw new Error(data.message);
                    return data;
                });
            }
        })
        .then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    title: '{{ __("passwords.success") }}',
                    text: result.value.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            }
        })
        .catch(error => {
            Swal.fire({
                title: '{{ __("passwords.error") }}',
                text: error.message,
                icon: 'error',
                confirmButtonColor: '#0d6efd'
            });
        });
    }

    function buildOverReceiptHtml(overReceipts) {
        if (!Array.isArray(overReceipts) || overReceipts.length === 0) {
            return '<p>No over-receipt details available.</p>';
        }

        const fmt = (n) => Number(n || 0).toLocaleString(undefined, {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });

        const rows = overReceipts.map(r => `
            <tr>
                <td class="text-start">${r.product_name ?? '—'}</td>
                <td class="text-end">${fmt(r.ordered)}</td>
                <td class="text-end">${fmt(r.previously_received)}</td>
                <td class="text-end">${fmt(r.receiving_now)}</td>
                <td class="text-end text-danger fw-bold">+${fmt(r.over_by)}</td>
            </tr>
        `).join('');

        return `
            <div class="text-start mb-3">
                <p class="mb-2">The following items exceed the ordered quantity:</p>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Ordered</th>
                                <th class="text-end">Received</th>
                                <th class="text-end">Now</th>
                                <th class="text-end">Over By</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
                <p class="text-danger mt-3 mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Are you sure you want to proceed?
                </p>
            </div>
        `;
    }

    // ── Initialize on modal show ──
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="receiveItemsModal"]').forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                const orderId = this.id.replace('receiveItemsModal', '');
                // Reset all quantities to 0 and restore actual cost to PO price
                document.querySelectorAll(`#receiveItemsForm${orderId} .receiving-quantity`).forEach(input => {
                    input.value = 0;
                });
                document.querySelectorAll(`#receiveItemsForm${orderId} .receiving-actual-cost`).forEach(input => {
                    input.disabled = true;
                });
                const paymentAmount = document.getElementById(`payment_amount_${orderId}`);
                if (paymentAmount) paymentAmount.value = 0;
                updateItemsTotal(orderId);
            });
        });
    });
</script>