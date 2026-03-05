{{--
    complete-payment.blade.php
    ─────────────────────────
    Drop-in replacement. The old 3-step single-payment modal is gone.
    Clicking "Complete" now calls openPaymentModal() — the same split-payment
    system used by the POS — and the controller processSplitPayment() handles
    everything from there.

    Required: payment-modal.blade.php (the shared split-payment modal) must be
    included ONCE somewhere on the page (e.g. in the layout or order-index view).
--}}

@php
    $orderItemsForModal = $order->orderItems->map(fn($item) => [
        'variant_id'         => $item->variant_id ?? $item->product_variant_id,
        'product_variant_id' => $item->product_variant_id ?? $item->variant_id,
        'product_id'         => $item->product_id ?? null,
        'name'               => $item->item_name,
        'product_name'       => $item->item_name,
        'sku'                => $item->sku ?? '',
        'quantity'           => $item->quantity,
        'price'              => $item->unit_price,
        'unit_price'         => $item->unit_price,
        'total'              => $item->total_price,
        'subtotal'           => $item->total_price,
        'note'               => $item->notes ?? null,
    ]);
@endphp
@include('orders.pos.payment-mode')

{{-- Inline script: builds the cartData object and hands it to openPaymentModal() --}}
<script>
(function () {
    /**
     * openCompletePayment(orderId)
     * Called from the "Complete" button in the orders table.
     * Assembles an order payload identical to what the POS produces,
     * then delegates to the shared openPaymentModal() split-payment system.
     */
    window.openCompletePayment_{{ $order->id }} = function () {
        var cartData = {
            // ── identifiers ────────────────────────────────────────────
            order_id:      {{ $order->id }},
            id:            {{ $order->id }},
            ref:           '{{ $order->order_number }}',
            order_number:  '{{ $order->order_number }}',
            source:        '{{ $order->source }}',

            // ── customer ───────────────────────────────────────────────
            customer_name: '{{ addslashes($order->customer_name ?? __("pagination.walk_in_customer")) }}',
            customer_id:   {{ $order->customer_id ?? 'null' }},

            // ── financials ─────────────────────────────────────────────
            subtotal: {{ $order->subtotal }},
            discount: {{ $order->discount_total }},
            tax:      {{ $order->tax_total }},
            total:    {{ $order->total }},

            // ── items (for receipt) ────────────────────────────────────
            items: @json($orderItemsForModal),

            // ── flag so processSplitPayments knows this is a
            //    "complete existing order" call, not a fresh POS sale ───
            completing_existing_order: true,
        };

        if (typeof window.openPaymentModal === 'function') {
            window.openPaymentModal(cartData);
        } else {
            console.error('[CompletePayment] openPaymentModal() not found. ' +
                'Make sure payment-modal.blade.php is included on the page.');
            toastr.error('Payment modal not ready. Please refresh the page.');
        }
    };
})();
</script>