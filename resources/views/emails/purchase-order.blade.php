<!DOCTYPE html>
<html>
<head>
    <title>Purchase Order #{{ $purchaseOrder->po_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; }
        .header { background: #f8f9fa; padding: 20px; border-bottom: 2px solid #dee2e6; }
        .content { padding: 20px; }
        .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .table th, .table td { border: 1px solid #dee2e6; padding: 12px; text-align: left; }
        .table th { background: #f8f9fa; }
        .total-row { background: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Purchase Order #{{ $purchaseOrder->po_number }}</h1>
        <p><strong>Date:</strong> {{ $purchaseOrder->created_at->format('F j, Y') }}</p>
        <p><strong>Expected Delivery:</strong> {{ $purchaseOrder->expected_delivery_date?->format('F j, Y') ?? 'Not specified' }}</p>
    </div>
    
    <div class="content">
        <h2>Supplier Details</h2>
        <p>
            <strong>{{ $supplier->name }}</strong><br>
            @if($supplier->contact_person)Contact: {{ $supplier->contact_person }}<br>@endif
            @if($supplier->email)Email: {{ $supplier->email }}<br>@endif
            @if($supplier->phone)Phone: {{ $supplier->phone }}<br>@endif
            @if($supplier->address)
                Address: {{ $supplier->address }}<br>
                {{ $supplier->city }}, {{ $supplier->state }} {{ $supplier->postal_code }}<br>
                {{ $supplier->country_code }}
            @endif
        </p>

        <h2>Order Items</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->sku }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->unit_cost, 2) }}</td>
                    <td>${{ number_format($item->total_cost, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">Subtotal:</td>
                    <td>${{ number_format($purchaseOrder->subtotal, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">Tax:</td>
                    <td>${{ number_format($purchaseOrder->tax_total, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="4" style="text-align: right;">Grand Total:</td>
                    <td>${{ number_format($purchaseOrder->total, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if($purchaseOrder->notes)
        <h2>Notes</h2>
        <p>{{ $purchaseOrder->notes }}</p>
        @endif

        <p>Please process this purchase order and confirm delivery by the expected date.</p>
        
        <p>Best regards,<br>
        {{ config('app.name') }}</p>
    </div>
</body>
</html>