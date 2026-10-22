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
                                <button type="button" class="btn btn-sm btn-primary" data-tax-preview="{{ $order->id }}">
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
                                        <tbody id="tax_breakdown_body_{{ $order->id }}"></tbody>
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
                                                    data-unit-cost="{{ $item->unit_cost }}">
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
                                                        disabled>
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
                                                value="0">
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
                            <button type="button" class="btn btn-warning" data-receive-submit="{{ $order->id }}" data-status="partially_received">
                                <i class="bi bi-arrow-repeat me-2"></i>{{ __('passwords.mark_partially_received') }}
                            </button>
                            <button type="button" class="btn btn-success" data-receive-submit="{{ $order->id }}" data-status="received">
                                <i class="bi bi-check-circle me-2"></i>{{ __('passwords.mark_fully_received') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>