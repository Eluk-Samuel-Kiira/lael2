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
                                            <th class="min-w-100px">{{ __('passwords.unit_cost') }}</th>
                                            <th class="min-w-150px">{{ __('passwords.balance') }}</th>
                                            <th class="min-w-150px text-primary">{{ __('passwords.receiving_now') }}</th>
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
                                                <span class="fw-bold">{{ number_format($item->unit_cost, 2) }}</span>
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
                                                    max="{{ $pending }}"
                                                    value="0"
                                                    data-unit-cost="{{ $item->unit_cost }}"
                                                    data-pending="{{ $pending }}"
                                                    onchange="updateItemsTotal({{ $order->id }})">
                                                <small class="text-muted fs-7">{{ __('passwords.max') }}: {{ $pending }}</small>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Batch Information -->
                    <div class="row g-6 mb-6">
                        <div class="col-md-6">
                            <div class="card card-flush bg-light">
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
                        
                        <!-- Payment Status Summary -->
                        <div class="col-md-6">
                            <div class="card card-flush bg-light-success">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="bi bi-credit-card me-2"></i>
                                        {{ __('payments.payment_status') }}
                                    </h3>
                                </div>
                                <div class="card-body">
                                    @php
                                        $totalAmount = $order->total ?? 0;
                                        $totalPaid = $order->total_paid ?? 0;
                                        $balance = $totalAmount - $totalPaid;
                                        $isFullyPaid = $balance <= 0;
                                    @endphp
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ __('passwords.total_amount') }}:</span>
                                        <span class="fw-bold">{{ number_format($totalAmount, 2) }} {{ currency_symbol() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span class="text-muted">{{ __('passwords.total_paid') }}:</span>
                                        <span class="fw-bold text-success">{{ number_format($totalPaid, 2) }} {{ currency_symbol() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1">
                                        <span class="text-muted">{{ __('passwords.balance') }}:</span>
                                        <span class="fw-bold {{ $isFullyPaid ? 'text-success' : 'text-warning' }}">
                                            {{ number_format($balance, 2) }} {{ currency_symbol() }}
                                        </span>
                                    </div>
                                    @if($isFullyPaid)
                                        <div class="mt-2">
                                            <span class="badge badge-success">✅ {{ __('passwords.fully_paid') }}</span>
                                        </div>
                                    @else
                                        <div class="mt-2">
                                            <span class="badge badge-warning">⚠️ {{ __('passwords.payment_required') }}</span>
                                        </div>
                                    @endif
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
                            <button type="button" class="btn btn-warning" onclick="submitReceiving({{ $order->id }}, 'partially_received')" {{ !$isFullyPaid ? 'disabled' : '' }}>
                                <i class="bi bi-arrow-repeat me-2"></i>{{ __('passwords.mark_partially_received') }}
                            </button>
                            <button type="button" class="btn btn-success" onclick="submitReceiving({{ $order->id }}, 'received')" {{ !$isFullyPaid ? 'disabled' : '' }}>
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
    const updateItemsTotal = debounce(function(orderId) {
        let itemsTotal = 0;
        let totalQuantity = 0;
        const inputs = document.querySelectorAll(`#receiveItemsForm${orderId} .receiving-quantity`);
        
        inputs.forEach(input => {
            const quantity = parseFloat(input.value) || 0;
            const unitCost = parseFloat(input.dataset.unitCost) || 0;
            itemsTotal += quantity * unitCost;
            totalQuantity += quantity;
            
            // Highlight rows with quantity being received
            const row = input.closest('.receive-item-row');
            if (row) {
                row.style.backgroundColor = quantity > 0 ? '#e8f5e9' : '';
                row.style.borderLeft = quantity > 0 ? '3px solid #4caf50' : '';
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
    }, 300);

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
    function submitReceiving(orderId, status) {
        const form = document.getElementById(`receiveItemsForm${orderId}`);
        if (!form) return;
        
        // Check if fully paid before allowing receive
        const balanceEl = document.querySelector(`#receiveItemsModal${orderId} .fw-bold.text-warning, #receiveItemsModal${orderId} .fw-bold.text-success`);
        if (balanceEl) {
            const balanceText = balanceEl.textContent.replace(/[^0-9.]/g, '');
            const balance = parseFloat(balanceText) || 0;
            if (balance > 0.01) {
                Swal.fire({
                    title: '{{ __("passwords.payment_required") }}',
                    text: '{{ __("passwords.cannot_receive_unpaid_items") }}',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }
        }
        
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
        
        const formData = new FormData(form);
        formData.append('status', status);
        
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
                    if (!data.success) {
                        throw new Error(data.message);
                    }
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

    // ── Initialize on modal show ──
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[id^="receiveItemsModal"]').forEach(modal => {
            modal.addEventListener('shown.bs.modal', function() {
                const orderId = this.id.replace('receiveItemsModal', '');
                // Reset all quantities to 0
                document.querySelectorAll(`#receiveItemsForm${orderId} .receiving-quantity`).forEach(input => {
                    input.value = 0;
                });
                updateItemsTotal(orderId);
            });
        });
    });
</script>