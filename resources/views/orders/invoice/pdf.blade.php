<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $invoice->invoice_number }}</title>
<style>
    @page { margin: 30px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e2129; }

    .header-table { width: 100%; margin-bottom: 30px; }
    .header-table td { vertical-align: top; }
    .company-name { font-size: 20px; font-weight: bold; color: #1a1a2e; }
    .company-meta { font-size: 10px; color: #7e8299; line-height: 1.5; margin-top: 4px; }

    .invoice-badge {
        text-align: right;
    }
    .invoice-badge .label {
        font-size: 22px; font-weight: bold; letter-spacing: 1px; color: #3699ff;
    }
    .invoice-badge .number {
        font-size: 13px; color: #3f4254; margin-top: 4px;
    }

    .status-pill {
        display: inline-block; padding: 3px 12px; border-radius: 12px;
        font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px;
        margin-top: 6px;
    }
    .status-draft   { background: #fff4de; color: #ffa800; }
    .status-sent, .status-viewed { background: #e1f0ff; color: #3699ff; }
    .status-paid    { background: #e8fff3; color: #50cd89; }
    .status-partially_paid { background: #eef3ff; color: #7239ea; }
    .status-overdue { background: #fff5f8; color: #f1416c; }
    .status-void, .status-cancelled { background: #f5f8fa; color: #a1a5b7; }

    .meta-table { width: 100%; margin-bottom: 25px; border-top: 1px solid #eef0f8; border-bottom: 1px solid #eef0f8; padding: 12px 0; }
    .meta-table td { padding: 10px 0; vertical-align: top; }
    .meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #a1a5b7; margin-bottom: 3px; }
    .meta-value { font-size: 12px; font-weight: bold; color: #1e2129; }

    .bill-to-title { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #a1a5b7; margin-bottom: 6px; }
    .bill-to-name { font-size: 14px; font-weight: bold; color: #1a1a2e; margin-bottom: 3px; }
    .bill-to-detail { font-size: 11px; color: #5e6278; line-height: 1.6; }

    table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.items th {
        background: #f8f9fb; font-size: 9px; text-transform: uppercase; letter-spacing: .5px;
        color: #7e8299; text-align: left; padding: 10px 12px; border-bottom: 2px solid #eef0f8;
    }
    table.items th.num { text-align: right; }
    table.items td {
        padding: 10px 12px; border-bottom: 1px solid #f4f5f8; font-size: 11px; color: #3f4254;
    }
    table.items td.num { text-align: right; font-weight: bold; }
    table.items .item-name { font-weight: bold; color: #1e2129; }
    table.items .item-sku { font-size: 9px; color: #a1a5b7; }

    .totals-table { width: 60%; margin-left: 40%; margin-top: 10px; }
    .totals-table td { padding: 6px 0; font-size: 12px; }
    .totals-table .t-label { color: #7e8299; }
    .totals-table .t-value { text-align: right; font-weight: bold; color: #1e2129; }
    .totals-table .grand-row td { border-top: 2px solid #1a1a2e; padding-top: 12px; font-size: 16px; }
    .totals-table .grand-row .t-label { color: #1a1a2e; font-weight: bold; }
    .totals-table .grand-row .t-value { color: #3699ff; }
    .totals-table .balance-row .t-value { color: #f1416c; }
    .totals-table .paid-row .t-value { color: #50cd89; }

    .footer-notes { margin-top: 35px; padding-top: 15px; border-top: 1px solid #eef0f8; font-size: 10px; color: #7e8299; line-height: 1.6; }
    .footer-notes .footer-title { font-size: 10px; font-weight: bold; color: #3f4254; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }

    .thank-you { text-align: center; margin-top: 30px; font-size: 12px; color: #a1a5b7; }
</style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="company-name">{{ getUIOptions('app_name') ?? config('app.name') }}</div>
                <div class="company-meta">
                    {{ getUIOptions('app_address') ?? '' }}<br>
                    {{ getUIOptions('app_email') ?? '' }} &nbsp;•&nbsp; {{ getUIOptions('app_contact') ?? '' }}
                </div>
            </td>
            <td style="width: 40%;" class="invoice-badge">
                <div class="label">INVOICE</div>
                <div class="number">{{ $invoice->invoice_number }}</div>
                <div><span class="status-pill status-{{ $invoice->status }}">{{ str_replace('_', ' ', $invoice->status) }}</span></div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="width: 40%;">
                <div class="bill-to-title">Bill To</div>
                <div class="bill-to-name">{{ $invoice->billing_name }}</div>
                <div class="bill-to-detail">
                    @if($invoice->billing_email){{ $invoice->billing_email }}<br>@endif
                    @if($invoice->billing_phone){{ $invoice->billing_phone }}<br>@endif
                    @if($invoice->billing_address){{ $invoice->billing_address }}@endif
                </div>
            </td>
            <td style="width: 20%;">
                <div class="meta-label">Issue Date</div>
                <div class="meta-value">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d M, Y') }}</div>
            </td>
            <td style="width: 20%;">
                <div class="meta-label">Due Date</div>
                <div class="meta-value">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M, Y') : '—' }}</div>
            </td>
            <td style="width: 20%;">
                <div class="meta-label">Order Ref</div>
                <div class="meta-value">{{ $invoice->order->order_number ?? '—' }}</div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 45%;">Item</th>
                <th class="num" style="width: 10%;">Qty</th>
                <th class="num" style="width: 15%;">Unit Price</th>
                <th class="num" style="width: 15%;">Tax</th>
                <th class="num" style="width: 15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->order->orderItems as $item)
            <tr>
                <td>
                    <div class="item-name">{{ $item->item_name }}</div>
                    @if($item->sku)<div class="item-sku">SKU: {{ $item->sku }}</div>@endif
                </td>
                <td class="num">{{ $item->quantity }}</td>
                <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                <td class="num">{{ number_format($item->tax_amount, 2) }}</td>
                <td class="num">{{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td class="t-label">Subtotal</td>
            <td class="t-value">{{ currency_symbol() }} {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->discount_total > 0)
        <tr>
            <td class="t-label">Discount</td>
            <td class="t-value">-{{ currency_symbol() }} {{ number_format($invoice->discount_total, 2) }}</td>
        </tr>
        @endif
        @if($invoice->tax_total > 0)
        <tr>
            <td class="t-label">Tax</td>
            <td class="t-value">{{ currency_symbol() }} {{ number_format($invoice->tax_total, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-row">
            <td class="t-label">Total Due</td>
            <td class="t-value">{{ currency_symbol() }} {{ number_format($invoice->total, 2) }}</td>
        </tr>
        @if($invoice->amount_paid > 0)
        <tr class="paid-row">
            <td class="t-label">Amount Paid</td>
            <td class="t-value">{{ currency_symbol() }} {{ number_format($invoice->amount_paid, 2) }}</td>
        </tr>
        <tr class="balance-row">
            <td class="t-label">Balance Due</td>
            <td class="t-value">{{ currency_symbol() }} {{ number_format($invoice->balance_due, 2) }}</td>
        </tr>
        @endif
    </table>

    @if($invoice->terms || $invoice->notes)
    <div class="footer-notes">
        @if($invoice->terms)
        <div class="footer-title">Payment Terms</div>
        <div>{{ $invoice->terms }}</div>
        @endif
        @if($invoice->notes)
        <div class="footer-title" style="margin-top: 10px;">Notes</div>
        <div>{{ $invoice->notes }}</div>
        @endif
    </div>
    @endif

    <div class="thank-you">Thank you for your business.</div>

</body>
</html>