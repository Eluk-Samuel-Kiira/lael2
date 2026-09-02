<div class="modal fade" id="viewPurchase{{ $order->id }}" tabindex="-1" aria-hidden="true" dir="ltr">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <!-- Modal Header with Status Badge -->
            <div class="modal-header">
                <div>
                    <h2 class="fw-bold text-gray-900 mb-1">{{ __('passwords.purchase_order_details') }}</h2>
                    <div class="d-flex align-items-center gap-3 mt-1">
                        <span class="badge badge-light-primary py-2 px-3 fs-7">
                            <i class="bi bi-receipt me-1"></i>
                            {{ $order->po_number }}
                        </span>
                        <span class="{{ $order->status_badge ? 'badge badge-light-'.$order->status_badge : 'badge badge-light-secondary' }} py-2 px-3 fs-7">
                            <i class="bi bi-flag me-1"></i>
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </div>
                </div>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body scroll-y mx-5 my-7" style="max-height: 70vh;">
                
                <!-- Action Buttons Card -->
                <div class="card card-dashed bg-light mb-7">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="d-flex gap-3">
                                <button type="button" class="btn btn-sm btn-light-primary" onclick="printPurchaseOrder({{ $order->id }})">
                                    <i class="bi bi-printer me-2"></i>{{ __('passwords.print') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-light-success" onclick="downloadPurchaseOrder({{ $order->id }})">
                                    <i class="bi bi-download me-2"></i>{{ __('passwords.download_pdf') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-light-info" data-bs-toggle="modal" data-bs-target="#sendPODocumentModal{{ $order->id }}">
                                    <i class="bi bi-send me-2"></i>{{ __('passwords.send_to_supplier') }}
                                </button>
                            </div>
                            <div class="text-muted fs-7">
                                <i class="bi bi-clock me-1"></i>
                                Created: {{ optional($order->created_at)->format('M d, Y H:i') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Information Cards Row -->
                <div class="row g-6 mb-7">
                    <!-- Supplier Card -->
                    <div class="col-md-4">
                        <div class="card card-dashed bg-light-primary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="symbol symbol-40px symbol-circle me-3">
                                        <div class="symbol-label bg-primary">
                                            <i class="bi bi-building text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold text-gray-800 mb-0">{{ __('passwords.supplier') }}</h6>
                                </div>
                                <div class="fw-bold fs-5 text-gray-800 mb-1">{{ $order->supplier->name ?? '—' }}</div>
                                @if($order->supplier)
                                    <div class="text-muted fs-7">{{ $order->supplier->email ?? '' }}</div>
                                    <div class="text-muted fs-7">{{ $order->supplier->phone ?? '' }}</div>
                                    @if($order->supplier->tax_number)
                                        <div class="text-muted fs-7 mt-2">
                                            <i class="bi bi-receipt me-1"></i>
                                            TIN: {{ $order->supplier->tax_number }}
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Delivery Card -->
                    <div class="col-md-4">
                        <div class="card card-dashed bg-light-info h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="symbol symbol-40px symbol-circle me-3">
                                        <div class="symbol-label bg-info">
                                            <i class="bi bi-truck text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold text-gray-800 mb-0">{{ __('passwords.expected_delivery') }}</h6>
                                </div>
                                <div class="fw-bold fs-5 text-gray-800">
                                    @if($order->expected_delivery_date)
                                        <i class="bi bi-calendar-check me-2 text-info"></i>
                                        {{ optional($order->expected_delivery_date)->format('M d, Y') }}
                                    @else
                                        —
                                    @endif
                                </div>
                                @if($order->location)
                                    <div class="text-muted fs-7 mt-2">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        {{ $order->location->name }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Received Summary Card -->
                    <div class="col-md-4">
                        <div class="card card-dashed bg-light-success h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="symbol symbol-40px symbol-circle me-3">
                                        <div class="symbol-label bg-success">
                                            <i class="bi bi-box-seam text-white fs-4"></i>
                                        </div>
                                    </div>
                                    <h6 class="fw-bold text-gray-800 mb-0">{{ __('passwords.received_summary') }}</h6>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ __('passwords.received_value') }}:</span>
                                        <span class="fw-bold text-success">{{ number_format($order->received_subtotal ?? 0, 2) }} {{ currency_symbol() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">{{ __('passwords.received_tax') }}:</span>
                                        <span class="fw-bold text-danger">{{ number_format($order->received_tax_total ?? 0, 2) }} {{ currency_symbol() }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                        <span class="fw-bold">{{ __('passwords.received_total') }}:</span>
                                        <span class="fw-bold text-primary fs-6">{{ number_format($order->received_total ?? 0, 2) }} {{ currency_symbol() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Receiving Progress Bar -->
                @php
                    $progressPercent = ($order->received_subtotal ?? 0) / max(($order->subtotal ?? 1), 1) * 100;
                @endphp
                <div class="card card-flush bg-light mb-7">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">{{ __('passwords.receiving_progress') }}</span>
                            <span class="fw-bold text-primary">{{ number_format($progressPercent, 1) }}%</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-primary" role="progressbar" 
                                 style="width: {{ $progressPercent }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <div>
                                <span class="text-muted">{{ __('passwords.received') }}:</span>
                                <span class="fw-bold text-success">{{ number_format($order->received_subtotal ?? 0, 2) }}</span>
                            </div>
                            <div>
                                <span class="text-muted">{{ __('passwords.pending') }}:</span>
                                <span class="fw-bold text-warning">{{ number_format(($order->subtotal ?? 0) - ($order->received_subtotal ?? 0), 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status History Accordion -->
                <div class="card card-flush mb-7">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-clock-history text-primary me-2"></i>
                            {{ __('passwords.status_history') }}
                        </h3>
                        <div class="card-toolbar">
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#statusHistory{{ $order->id }}">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                    <div class="collapse show" id="statusHistory{{ $order->id }}">
                        <div class="card-body">
                            <div class="d-flex flex-column gap-5">
                                @if($order->created_at)
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-40px symbol-circle me-4">
                                        <div class="symbol-label bg-light-primary">
                                            <i class="bi bi-file-text text-primary fs-5"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div>
                                                <span class="fw-bold text-gray-800">{{ __('passwords.order_created') }}</span>
                                                <span class="badge badge-light-primary ms-2">{{ __('passwords.created') }}</span>
                                            </div>
                                            <span class="text-muted fs-7">{{ optional($order->created_at)->format('M d, Y H:i') }}</span>
                                        </div>
                                        <div class="text-muted fs-7 mt-1">
                                            <i class="bi bi-person me-1"></i> {{ $order->creator->name ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                                @endif
                                <!-- ... rest of status history items ... -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items Section with Received Totals -->
                @php
                    $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
                @endphp

                @if(!empty($items) && count($items) > 0)
                <div class="card card-flush">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-box-seam text-primary me-2"></i>
                            {{ __('passwords.order_items') }}
                        </h3>
                        <div class="card-toolbar">
                            <div class="d-flex gap-3">
                                <span class="badge badge-light-primary">{{ __('passwords.total_ordered') }}: {{ number_format($order->subtotal ?? 0, 2) }}</span>
                                <span class="badge badge-light-success">{{ __('passwords.total_received') }}: {{ number_format($order->received_subtotal ?? 0, 2) }}</span>
                                <span class="badge badge-light-warning">{{ __('passwords.total_pending') }}: {{ number_format(($order->subtotal ?? 0) - ($order->received_subtotal ?? 0), 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                <thead>
                                    <tr class="fw-bold text-muted bg-light">
                                        <th class="ps-4 min-w-200px">{{ __('passwords.product') }}</th>
                                        <th class="min-w-80px text-center">{{ __('passwords.ordered_qty') }}</th>
                                        <th class="min-w-80px text-center">{{ __('passwords.unit_cost') }}</th>
                                        <th class="min-w-80px text-center">{{ __('passwords.received_qty') }}</th>
                                        <th class="min-w-80px text-center">{{ __('passwords.pending_qty') }}</th>
                                        <th class="min-w-100px text-end">{{ __('passwords.ordered_value') }}</th>
                                        <th class="min-w-100px text-end">{{ __('passwords.received_value') }}</th>
                                        <th class="min-w-100px text-end">{{ __('passwords.pending_value') }}</th>
                                        <th class="min-w-80px text-end">{{ __('passwords.tax_amount') }}</th>
                                      </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    @php
                                        $orderedQty = $item->quantity;
                                        $receivedQty = $item->received_quantity;
                                        $pendingQty = $orderedQty - $receivedQty;
                                        $unitCost = $item->unit_cost;
                                        $orderedValue = $orderedQty * $unitCost;
                                        $receivedValue = $receivedQty * $unitCost;
                                        $pendingValue = $pendingQty * $unitCost;
                                        $itemTax = $item->tax_amount ?? 0;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-gray-800">{{ $item->product_name }}</span>
                                                <span class="text-muted fs-7">SKU: {{ $item->sku }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center fw-bold">{{ $orderedQty }}</td>
                                        <td class="text-center">{{ number_format($unitCost, 2) }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-light-success">{{ $receivedQty }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-warning">{{ $pendingQty }}</span>
                                        </td>
                                        <td class="text-end text-muted">{{ number_format($orderedValue, 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end text-success">{{ number_format($receivedValue, 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end text-warning">{{ number_format($pendingValue, 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end text-danger">{{ number_format($itemTax, 2) }} {{ currency_symbol() }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr class="fw-bold">
                                        <td colspan="5" class="ps-4 text-end">{{ __('passwords.totals') }}: </td>
                                        <td class="text-end">{{ number_format($order->subtotal ?? 0, 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end text-success">{{ number_format($order->received_subtotal ?? 0, 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end text-warning">{{ number_format(($order->subtotal ?? 0) - ($order->received_subtotal ?? 0), 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end text-danger">{{ number_format($order->tax_total ?? 0, 2) }} {{ currency_symbol() }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tax Liability Information -->
                @php
                    $taxLiabilities = \App\Models\SupplierTaxLiability::where('purchase_order_id', $order->id)->get();
                @endphp

                @if($taxLiabilities->count() > 0)
                <div class="card card-flush mt-7 bg-light-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-receipt text-warning me-2"></i>
                            {{ __('passwords.tax_liabilities') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                <thead>
                                    <tr class="fw-bold text-muted bg-light">
                                        <th class="ps-4 min-w-150px">{{ __('passwords.tax_name') }}</th>
                                        <th class="min-w-100px">{{ __('passwords.tax_type') }}</th>
                                        <th class="min-w-80px">{{ __('passwords.rate') }}</th>
                                        <th class="text-end min-w-120px">{{ __('passwords.taxable_amount') }}</th>
                                        <th class="text-end min-w-100px">{{ __('passwords.tax_amount') }}</th>
                                        <th class="text-end min-w-120px">{{ __('passwords.amount_paid') }}</th>
                                        <th class="min-w-100px">{{ __('passwords.status') }}</th>
                                        <th class="min-w-100px">{{ __('passwords.due_date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($taxLiabilities as $liability)
                                    @php
                                        $amountPaid = $liability->taxable_amount - $liability->tax_amount;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-gray-800">{{ $liability->tax_name }}</span>
                                                <small class="text-muted">{{ $liability->tax_code }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            @if($liability->is_withholding_tax)
                                                <span class="badge badge-light-danger">
                                                    <i class="bi bi-arrow-down-short me-1"></i>
                                                    {{ __('passwords.withholding_tax') }}
                                                </span>
                                            @else
                                                <span class="badge badge-light-primary">
                                                    <i class="bi bi-arrow-up-short me-1"></i>
                                                    {{ __('passwords.additive_tax') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light-info">{{ number_format($liability->tax_rate, 2) }}%</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold">{{ number_format($liability->taxable_amount, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-danger">{{ number_format($liability->tax_amount, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-success">{{ number_format($amountPaid, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td>
                                            {!! $liability->status_badge !!}
                                        </td>
                                        <td>
                                            @if($liability->due_date)
                                                {{ $liability->due_date->format('d M Y') }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @php
                                    $totalTaxable = $taxLiabilities->sum('taxable_amount');
                                    $totalTaxAmount = $taxLiabilities->sum('tax_amount');
                                    $totalAmountPaid = $taxLiabilities->sum(function($liability) {
                                        return $liability->taxable_amount - $liability->tax_amount;
                                    });
                                @endphp
                                <tfoot class="bg-light">
                                    <tr class="fw-bold">
                                        <td colspan="3" class="ps-4 text-end">{{ __('passwords.totals') }}: </td>
                                        <td class="text-end">{{ number_format($totalTaxable, 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end text-danger">{{ number_format($totalTaxAmount, 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end text-success">{{ number_format($totalAmountPaid, 2) }} {{ currency_symbol() }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                @else
                <div class="card card-flush bg-light-warning">
                    <div class="card-body text-center py-10">
                        <i class="bi bi-box-seam fs-3x text-warning mb-3 d-block"></i>
                        <span class="text-muted">{{ __('passwords.no_items_found') }}</span>
                    </div>
                </div>
                @endif

                <!-- Notes -->
                @if($order->notes)
                <div class="card card-flush mt-7 bg-light">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="bi bi-sticky text-muted me-2"></i>
                            {{ __('passwords._notes') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-gray-700">{{ $order->notes }}</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-2"></i>
                    {{ __('auth._close') }}
                </button>
            </div>
        </div>
    </div>
</div>