<div class="modal fade" id="viewPurchase{{ $order->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('passwords.purchase_order_details') }} - {{ $order->po_number }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body px-5 py-7">
                <!-- Action Buttons -->
                <div class="row mb-6">
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-primary me-2" onclick="printPurchaseOrder('viewPurchase{{ $order->id }}')">
                            <i class="bi bi-printer me-2"></i>{{ __('passwords.print') }}
                        </button>
                        <button type="button" class="btn btn-success" onclick="downloadPurchaseOrder('viewPurchase{{ $order->id }}')">
                            <i class="bi bi-download me-2"></i>{{ __('passwords.download_pdf') }}
                        </button>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="row mb-8">
                    <div class="col-md-4 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('passwords.supplier') }}:</label>
                        <div class="fw-bold fs-6 text-gray-800">{{ $order->supplier->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-4 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('passwords.expected_delivery') }}:</label>
                        <div class="fw-bold fs-6 text-gray-800">{{ optional($order->expected_delivery_date)->format('M d, Y') ?? '—' }}</div>
                    </div>
                    <div class="col-md-4 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('pagination._location') }}:</label>
                        <div class="fw-bold fs-6 text-gray-800">{{ $order->location->name ?? '—' }}</div>
                    </div>
                </div>

                <div class="row mb-8">
                    <div class="col-md-4 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('auth._status') }}:</label>
                        <div class="fw-bold fs-6 {{ $order->status_badge ? 'text-'.$order->status_badge : 'text-gray-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </div>
                    </div>
                    <div class="col-md-4 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('auth._creater') }}:</label>
                        <div class="fw-bold fs-6 text-gray-800">{{ $order->creator->name ?? '—' }}</div>
                        <small class="text-muted">{{ optional($order->created_at)->format('M d, Y H:i') }}</small>
                    </div>
                    <div class="col-md-4 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('auth.updated_at') }}:</label>
                        <div class="fw-bold fs-6 text-gray-800">{{ optional($order->updated_at)->format('M d, Y H:i') }}</div>
                    </div>
                </div>

                <!-- Status Action History -->
                <div class="row mb-8">
                    @if($order->submitted_at)
                    <div class="col-md-6 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('passwords.submitted_for_approval') }}:</label>
                        <div class="fw-bold fs-6 text-gray-800">{{ $order->submittedBy->name ?? '—' }}</div>
                        <small class="text-muted">{{ optional($order->submitted_at)->format('M d, Y H:i') }}</small>
                    </div>
                    @endif

                    @if($order->approved_at)
                    <div class="col-md-6 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('passwords.approved_by') }}:</label>
                        <div class="fw-bold fs-6 text-gray-800">{{ $order->approvedBy->name ?? '—' }}</div>
                        <small class="text-muted">{{ optional($order->approved_at)->format('M d, Y H:i') }}</small>
                    </div>
                    @endif

                    @if($order->sent_at)
                    <div class="col-md-6 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('passwords.sent_to_supplier_by') }}:</label>
                        <div class="fw-bold fs-6 text-gray-800">{{ $order->sentBy->name ?? '—' }}</div>
                        <small class="text-muted">{{ optional($order->sent_at)->format('M d, Y H:i') }}</small>
                    </div>
                    @endif

                    @if($order->received_at)
                    <div class="col-md-6 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('passwords.received_by') }}:</label>
                        <div class="fw-bold fs-6 text-gray-800">{{ $order->receivedBy->name ?? '—' }}</div>
                        <small class="text-muted">{{ optional($order->received_at)->format('M d, Y H:i') }}</small>
                    </div>
                    @endif

                    @if($order->cancelled_at)
                    <div class="col-md-6 text-start">
                        <label class="fw-semibold text-gray-600">{{ __('passwords.cancelled_by') }}:</label>
                        <div class="fw-bold fs-6 text-gray-800">{{ $order->cancelledBy->name ?? '—' }}</div>
                        <small class="text-muted">{{ optional($order->cancelled_at)->format('M d, Y H:i') }}</small>
                    </div>
                    @endif
                </div>

                <hr class="my-4">

                @php
                    // Get items - using your original method
                    $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
                    
                    // Also get grouped items for payment method display
                    $groupedItems = [];
                    if (!empty($items) && count($items) > 0) {
                        // Manually group items by payment method and date
                        foreach ($items as $item) {
                            $paymentMethodId = $item['payment_method_id'] ?? 'none';
                            $paymentDate = $item['payment_date'] ?? 'no-date';
                            $key = $paymentMethodId . '_' . $paymentDate;
                            
                            if (!isset($groupedItems[$key])) {
                                $groupedItems[$key] = [
                                    'method' => isset($item['payment_method_id']) ? 
                                        \App\Models\PaymentMethod::find($item['payment_method_id']) : null,
                                    'payment_date' => $item['payment_date'] ?? null,
                                    'items' => [],
                                    'subtotal' => 0,
                                    'tax' => 0,
                                    'total' => 0,
                                ];
                            }
                            
                            $quantity = $item['quantity'] ?? 0;
                            $unitCost = $item['unit_cost'] ?? 0;
                            $taxAmount = $item['tax_amount'] ?? 0;
                            $totalCost = $item['total_cost'] ?? 0;
                            
                            $groupedItems[$key]['items'][] = $item;
                            $groupedItems[$key]['subtotal'] += ($quantity * $unitCost);
                            $groupedItems[$key]['tax'] += $taxAmount;
                            $groupedItems[$key]['total'] += $totalCost;
                        }
                    }
                @endphp

                @if(!empty($items) && count($items) > 0)
                    <!-- Tabs for different views -->
                    <div class="mb-6">
                        <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6" id="itemsTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="grouped-tab" data-bs-toggle="tab" data-bs-target="#grouped-items" type="button" role="tab">
                                    <i class="bi bi-credit-card me-2"></i>Grouped by Payment Method
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-items" type="button" role="tab">
                                    <i class="bi bi-list-ul me-2"></i>All Items List
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="itemsTabContent">
                            <!-- Tab 1: Grouped by Payment Method -->
                            <div class="tab-pane fade show active" id="grouped-items" role="tabpanel">
                                <!-- Group items by payment method and payment date -->
                                @if(!empty($groupedItems))
                                    @foreach($groupedItems as $paymentMethodId => $paymentGroup)
                                        @php
                                            $paymentMethod = $paymentGroup['method'] ?? null;
                                            $groupItems = $paymentGroup['items'] ?? [];
                                            $paymentDate = $paymentGroup['payment_date'] ?? null;
                                            $groupSubtotal = $paymentGroup['subtotal'] ?? 0;
                                            $groupTax = $paymentGroup['tax'] ?? 0;
                                            $groupTotal = $paymentGroup['total'] ?? 0;
                                        @endphp

                                        <!-- Payment Method Group Card -->
                                        <div class="card card-flush mb-6">
                                            <div class="card-header border-bottom">
                                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                                    <div>
                                                        <h5 class="fw-bold text-gray-800 mb-1">
                                                            @if($paymentMethod)
                                                                <i class="bi bi-credit-card-2-front me-2"></i>
                                                                {{ $paymentMethod->name ?? 'Unknown' }}
                                                                @if($paymentMethod->account_number ?? false)
                                                                    <span class="text-muted fs-7">({{ $paymentMethod->account_number }})</span>
                                                                @endif
                                                            @else
                                                                <i class="bi bi-question-circle me-2"></i>
                                                                No Payment Method
                                                            @endif
                                                        </h5>
                                                        <div class="d-flex align-items-center gap-4 mt-2">
                                                            @if($paymentDate && $paymentDate !== 'no-date')
                                                                <div class="text-muted fs-7">
                                                                    <i class="bi bi-calendar3 me-1"></i>
                                                                    Payment Date: 
                                                                    <span class="fw-semibold">{{ \Carbon\Carbon::parse($paymentDate)->format('M d, Y') }}</span>
                                                                </div>
                                                            @endif
                                                            <div class="text-primary fs-6 fw-bold">
                                                                <i class="bi bi-cash-coin me-1"></i>
                                                                Group Total: {{ number_format($groupTotal, 2) }} {{ currencySymbol() }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="badge badge-light-primary fs-7 py-2 px-3">
                                                        {{ count($groupItems) }} Items
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="card-body pt-0">
                                                <!-- Items in this payment method group -->
                                                <ul class="list-group list-group-flush">
                                                    @foreach($groupItems as $item)
                                                        <li class="list-group-item px-4 py-3 border-bottom">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <!-- Left side: Product details -->
                                                                <div class="me-4 flex-grow-1">
                                                                    <h6 class="fw-bold text-gray-800 mb-1">{{ $item['product_name'] ?? '—' }}</h6>
                                                                    <div class="row">
                                                                        <div class="col-md-6">
                                                                            <div class="text-muted fs-7 mb-1">SKU: {{ $item['sku'] ?? 'N/A' }}</div>
                                                                            <div class="text-muted fs-7">
                                                                                Quantity: 
                                                                                <span class="fw-semibold">{{ $item['quantity'] ?? 0 }}</span> | 
                                                                                Received: 
                                                                                <span class="badge bg-success">{{ $item['received_quantity'] ?? 0 }}</span> | 
                                                                                Pending: 
                                                                                <span class="badge bg-danger">{{ (($item['quantity'] ?? 0) - ($item['received_quantity'] ?? 0)) }}</span>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-6">
                                                                            <div class="fs-7">
                                                                                <span class="text-muted">Payment Status:</span>
                                                                                @php
                                                                                    $statusClass = [
                                                                                        'pending' => 'warning',
                                                                                        'partial' => 'info',
                                                                                        'paid' => 'success',
                                                                                        'overdue' => 'danger'
                                                                                    ][$item['payment_status'] ?? 'pending'] ?? 'secondary';
                                                                                @endphp
                                                                                <span class="badge bg-{{ $statusClass }} ms-2">
                                                                                    {{ ucfirst($item['payment_status'] ?? 'pending') }}
                                                                                </span>
                                                                            </div>
                                                                            @if($item['payment_date'] && $item['payment_date'] !== 'no-date')
                                                                            <div class="fs-7 text-muted">
                                                                                <i class="bi bi-calendar-check me-1"></i>
                                                                                {{ \Carbon\Carbon::parse($item['payment_date'])->format('M d, Y') }}
                                                                            </div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <!-- Right side: Costs -->
                                                                <div class="text-end ms-4" style="min-width: 150px;">
                                                                    <div class="fs-7">
                                                                        <strong>Unit Cost:</strong> 
                                                                        {{ number_format($item['unit_cost'] ?? 0, 2) }} {{ currencySymbol() }}
                                                                    </div>
                                                                    <div class="fs-7">
                                                                        <strong>Tax:</strong> 
                                                                        {{ number_format($item['tax_amount'] ?? 0, 2) }} {{ currencySymbol() }}
                                                                    </div>
                                                                    <div class="fw-bold text-primary fs-6 mt-1">
                                                                        <strong>Total:</strong> 
                                                                        {{ number_format($item['total_cost'] ?? 0, 2) }} {{ currencySymbol() }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- Payment Method Summary Table -->
                                    <div class="card card-flush mb-8">
                                        <div class="card-header">
                                            <h5 class="card-title text-gray-800">
                                                <i class="bi bi-credit-card-2-front me-2"></i>
                                                Payment Method Summary
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-row-bordered gy-3">
                                                    <thead>
                                                        <tr class="fw-bold text-gray-700">
                                                            <th>Payment Method</th>
                                                            <th>Payment Date</th>
                                                            <th>Items Count</th>
                                                            <th>Subtotal</th>
                                                            <th>Tax</th>
                                                            <th>Total</th>
                                                            <th>Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($groupedItems as $paymentMethodId => $paymentGroup)
                                                            @php
                                                                $paymentMethod = $paymentGroup['method'] ?? null;
                                                                $groupItems = $paymentGroup['items'] ?? [];
                                                                $groupTotal = $paymentGroup['total'] ?? 0;
                                                                $groupSubtotal = $paymentGroup['subtotal'] ?? 0;
                                                                $groupTax = $paymentGroup['tax'] ?? 0;
                                                                $paymentDate = $paymentGroup['payment_date'] ?? null;
                                                                
                                                                // Get overall status for this payment method group
                                                                $statuses = collect($groupItems)->pluck('payment_status')->unique();
                                                                $hasOverdue = $statuses->contains('overdue');
                                                                $hasPending = $statuses->contains('pending');
                                                                $hasPartial = $statuses->contains('partial');
                                                                
                                                                if ($hasOverdue) {
                                                                    $overallStatus = 'overdue';
                                                                    $statusClass = 'danger';
                                                                } elseif ($hasPending) {
                                                                    $overallStatus = 'pending';
                                                                    $statusClass = 'warning';
                                                                } elseif ($hasPartial) {
                                                                    $overallStatus = 'partial';
                                                                    $statusClass = 'info';
                                                                } else {
                                                                    $overallStatus = 'paid';
                                                                    $statusClass = 'success';
                                                                }
                                                            @endphp
                                                            <tr>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        @if($paymentMethod && ($paymentMethod->icon ?? false))
                                                                            <i class="{{ $paymentMethod->icon }} fs-3 me-3 text-primary"></i>
                                                                        @elseif($paymentMethod)
                                                                            <i class="bi bi-credit-card fs-3 me-3 text-primary"></i>
                                                                        @else
                                                                            <i class="bi bi-question-circle fs-3 me-3 text-muted"></i>
                                                                        @endif
                                                                        <div>
                                                                            <div class="fw-bold">{{ $paymentMethod->name ?? 'No Payment Method' }}</div>
                                                                            @if($paymentMethod && $paymentMethod->account_number)
                                                                                <small class="text-muted">{{ $paymentMethod->account_number }}</small>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    @if($paymentDate && $paymentDate !== 'no-date')
                                                                        <span class="badge badge-light-primary">
                                                                            {{ \Carbon\Carbon::parse($paymentDate)->format('M d, Y') }}
                                                                        </span>
                                                                    @else
                                                                        <span class="text-muted">—</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-light-info">{{ count($groupItems) }}</span>
                                                                </td>
                                                                <td class="fw-bold">{{ number_format($groupSubtotal, 2) }} {{ currencySymbol() }}</td>
                                                                <td class="fw-bold">{{ number_format($groupTax, 2) }} {{ currencySymbol() }}</td>
                                                                <td class="fw-bold text-primary">{{ number_format($groupTotal, 2) }} {{ currencySymbol() }}</td>
                                                                <td>
                                                                    <span class="badge bg-{{ $statusClass }}">
                                                                        {{ ucfirst($overallStatus) }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                        <!-- Total Row -->
                                                        <tr class="fw-bold text-gray-800 border-top">
                                                            <td colspan="3" class="text-end">Grand Total:</td>
                                                            <td class="fw-bold">{{ displayFormatedCurrency($order->subtotal) }} {{ currencySymbol() }}</td>
                                                            <td class="fw-bold">{{ displayFormatedCurrency($order->tax_total) }} {{ currencySymbol() }}</td>
                                                            <td class="fw-bold text-primary">{{ displayFormatedCurrency($order->total) }} {{ currencySymbol() }}</td>
                                                            <td></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Tab 2: All Items List (Original View) -->
                            <div class="tab-pane fade" id="all-items" role="tabpanel">
                                <ul class="list-group list-group-flush mb-5">
                                    @foreach($items as $item)
                                        <li class="list-group-item px-4 py-3 border-bottom">
                                            <div class="d-flex justify-content-between align-items-start">
                                                
                                                <!-- Left side: Product details -->
                                                <div class="me-4">
                                                    <h6 class="fw-bold text-gray-800 mb-1">{{ $item['product_name'] ?? '—' }}</h6>
                                                    <div class="text-muted fs-7 mb-1">SKU: {{ $item['sku'] ?? 'N/A' }}</div>
                                                    <div class="text-muted fs-7">
                                                        Quantity: <span class="fw-semibold">{{ $item['quantity'] ?? 0 }}</span> | 
                                                        Received: <span class="badge bg-success">{{ $item['received_quantity'] ?? 0 }}</span> | 
                                                        Pending: <span class="badge bg-danger">{{ (($item['quantity'] ?? 0) - ($item['received_quantity'] ?? 0)) }}</span>
                                                    </div>
                                                    @if($item['payment_status'] ?? false)
                                                    <div class="fs-7 mt-1">
                                                        <span class="text-muted">Payment:</span>
                                                        @php
                                                            $statusClass = [
                                                                'pending' => 'warning',
                                                                'partial' => 'info',
                                                                'paid' => 'success',
                                                                'overdue' => 'danger'
                                                            ][$item['payment_status']] ?? 'secondary';
                                                        @endphp
                                                        <span class="badge bg-{{ $statusClass }} ms-2">
                                                            {{ ucfirst($item['payment_status']) }}
                                                        </span>
                                                        @if($item['payment_date'] ?? false)
                                                            <span class="text-muted ms-2">
                                                                <i class="bi bi-calendar-check me-1"></i>
                                                                {{ \Carbon\Carbon::parse($item['payment_date'])->format('M d, Y') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    @endif
                                                </div>

                                                <!-- Right side: Costs -->
                                                <div class="text-end">
                                                    <div class="fs-7"><strong>Unit Cost:</strong> {{ number_format($item['unit_cost'] ?? 0, 2) }} {{ currencySymbol() }}</div>
                                                    <div class="fs-7"><strong>Tax:</strong> {{ number_format($item['tax_amount'] ?? 0, 2) }} {{ currencySymbol() }}</div>
                                                    <div class="fw-bold text-primary fs-6 mt-1"><strong>Total:</strong> {{ number_format($item['total_cost'] ?? 0, 2) }} {{ currencySymbol() }}</div>
                                                </div>

                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                @else
                    <div class="alert alert-secondary text-center">{{ __('passwords.no_items_found') }}</div>
                @endif

                <!-- Order Summary -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="fw-bold">{{ __('pagination.order_summary') }}</h6>
                        <table class="table table-sm table-borderless w-auto">
                            <tr>
                                <td>{{ __('pagination.subtotal') }}:</td>
                                <td class="text-end">{{ displayFormatedCurrency($order->subtotal) }} {{ currencySymbol() }}</td>
                            </tr>
                            <tr>
                                <td>{{ __('passwords.tax_total') }}:</td>
                                <td class="text-end">{{ displayFormatedCurrency($order->tax_total) }} {{ currencySymbol() }}</td>
                            </tr>
                            <tr class="fw-bold text-primary">
                                <td>{{ __('passwords.grand_total') }}:</td>
                                <td class="text-end">{{ displayFormatedCurrency($order->total) }} {{ currencySymbol() }}</td>
                            </tr>
                        </table>
                    </div>

                    <div class="col-md-6 mb-3">
                        <h6 class="fw-bold">{{ __('passwords.status_history') }}</h6>
                        <table class="table table-sm table-borderless w-auto">
                            <tr>
                                <td>{{ __('auth.created_at') }}:</td>
                                <td class="text-end">{{ optional($order->created_at)->format('M d, Y H:i') }}</td>
                            </tr>
                            @if($order->submitted_at)
                            <tr>
                                <td>{{ __('passwords.submitted_at') }}:</td>
                                <td class="text-end">{{ optional($order->submitted_at)->format('M d, Y H:i') }}</td>
                            </tr>
                            @endif
                            @if($order->approved_at)
                            <tr>
                                <td>{{ __('passwords.approved_at') }}:</td>
                                <td class="text-end">{{ optional($order->approved_at)->format('M d, Y H:i') }}</td>
                            </tr>
                            @endif
                            @if($order->sent_at)
                            <tr>
                                <td>{{ __('passwords.sent_at') }}:</td>
                                <td class="text-end">{{ optional($order->sent_at)->format('M d, Y H:i') }}</td>
                            </tr>
                            @endif
                            @if($order->received_at)
                            <tr>
                                <td>{{ __('passwords.received_at') }}:</td>
                                <td class="text-end">{{ optional($order->received_at)->format('M d, Y H:i') }}</td>
                            </tr>
                            @endif
                            @if($order->cancelled_at)
                            <tr>
                                <td>{{ __('passwords.cancelled_at') }}:</td>
                                <td class="text-end">{{ optional($order->cancelled_at)->format('M d, Y H:i') }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </div>

                @if($order->notes)
                    <div class="mt-4 mb-0">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-sticky text-muted me-2 mt-1"></i>
                            <div>
                                <small class="text-muted d-block">{{ __('passwords._notes') }}</small>
                                <p class="mb-0 text-dark">{{ $order->notes }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="text-end mt-6">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('auth._close') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    // Function to print purchase order
    function printPurchaseOrder(modalId) {
        const modalElement = document.getElementById(modalId);
        if (!modalElement) {
            console.error('Modal not found:', modalId);
            return;
        }

        // Clone the modal content
        const modalContent = modalElement.querySelector('.modal-content').cloneNode(true);
        
        // Remove action buttons from the print version
        const actionButtons = modalContent.querySelector('.row.mb-6');
        if (actionButtons) {
            actionButtons.remove();
        }

        // Remove the close button from print version
        const closeButton = modalContent.querySelector('.text-end.mt-6');
        if (closeButton) {
            closeButton.remove();
        }

        // Remove tabs and show only grouped view for printing
        const tabContainer = modalContent.querySelector('#itemsTab');
        const tabContent = modalContent.querySelector('#itemsTabContent');
        
        if (tabContainer && tabContent) {
            // Show only the grouped items (first tab)
            tabContainer.remove();
            const allItemsTab = modalContent.querySelector('#all-items');
            if (allItemsTab) {
                allItemsTab.remove();
            }
            // Keep only grouped items
            const groupedItemsTab = modalContent.querySelector('#grouped-items');
            if (groupedItemsTab) {
                groupedItemsTab.classList.remove('tab-pane', 'fade');
                groupedItemsTab.classList.add('show', 'active');
            }
            tabContent.classList.remove('tab-content');
        }

        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=1000,height=700');
        
        // Create print-friendly HTML
        const printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Purchase Order Details</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
                <style>
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 20px; 
                        background: white;
                        color: black;
                    }
                    .print-header { 
                        text-align: center; 
                        margin-bottom: 30px; 
                        border-bottom: 2px solid #333; 
                        padding-bottom: 20px; 
                    }
                    .modal-header { 
                        border-bottom: 2px solid #dee2e6; 
                        margin-bottom: 20px;
                    }
                    .modal-body {
                        padding: 0 !important;
                    }
                    .text-primary { color: #007bff !important; }
                    .text-success { color: #28a745 !important; }
                    .text-danger { color: #dc3545 !important; }
                    .badge { 
                        padding: 4px 8px; 
                        border-radius: 4px; 
                        font-size: 12px; 
                    }
                    .bg-success { background-color: #28a745 !important; color: white; }
                    .bg-danger { background-color: #dc3545 !important; color: white; }
                    .bg-warning { background-color: #ffc107 !important; color: black; }
                    .bg-primary { background-color: #007bff !important; color: white; }
                    .bg-info { background-color: #17a2b8 !important; color: white; }
                    .bg-secondary { background-color: #6c757d !important; color: white; }
                    .bg-light { background-color: #f8f9fa !important; color: black; }
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none !important; }
                    }
                </style>
            </head>
            <body>
                <div class="container-fluid">
                    ${modalContent.innerHTML}
                </div>
                
                <div class="text-center mt-4 no-print">
                    <button onclick="window.print()" class="btn btn-primary me-2">Print</button>
                    <button onclick="window.close()" class="btn btn-secondary">Close</button>
                </div>
                
                <script>
                    window.onload = function() {
                        // Auto-print after a short delay
                        setTimeout(() => {
                            window.print();
                        }, 1000);
                    };
                <\/script>
            </body>
            </html>
        `;

        printWindow.document.write(printContent);
        printWindow.document.close();
    }

    // Function to download as PDF
    function downloadPurchaseOrder(modalId) {
        // For PDF download, we can use the same print function but suggest saving as PDF
        printPurchaseOrder(modalId);
        
        // Alternatively, show a message about using browser's "Save as PDF" option
        setTimeout(() => {
            alert('To download as PDF, use the print dialog and choose "Save as PDF" as your printer destination.');
        }, 1000);
    }

</script>