<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $invoice->invoice_number }}</title>
<style>
    @page { 
        margin: 25px 35px; 
        header: html_header;
        footer: html_footer;
    }
    
    body { 
        font-family: 'Helvetica', 'DejaVu Sans', Arial, sans-serif; 
        font-size: 11px; 
        color: #1e2129; 
        line-height: 1.5;
    }

    /* ── HEADER ─────────────────────────────────────────── */
    .header-table {
        width: 100%;
        margin-bottom: 25px;
        border-bottom: 3px solid #3699ff;
        padding-bottom: 15px;
    }
    .header-table td {
        vertical-align: middle;
    }
    
    .logo-wrapper {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .logo-img {
        max-height: 50px;
        width: auto;
        object-fit: contain;
    }
    .company-details {
        display: flex;
        flex-direction: column;
    }
    .company-name {
        font-size: 20px;
        font-weight: 700;
        color: #1a1a2e;
        letter-spacing: -0.5px;
    }
    .company-tagline {
        font-size: 9px;
        color: #7e8299;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 1px;
    }
    .company-contact {
        font-size: 9px;
        color: #7e8299;
        margin-top: 3px;
    }
    .company-contact span {
        margin: 0 6px;
    }

    .invoice-badge {
        text-align: right;
        padding-right: 5px;
    }
    .invoice-badge .label {
        font-size: 24px;
        font-weight: 700;
        letter-spacing: 2px;
        color: #3699ff;
    }
    .invoice-badge .number {
        font-size: 13px;
        font-weight: 600;
        color: #3f4254;
        margin-top: 2px;
    }
    .invoice-badge .number span {
        color: #7e8299;
        font-weight: 400;
    }

    .status-pill {
        display: inline-block;
        padding: 3px 14px;
        border-radius: 20px;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }
    .status-draft       { background: #fff4de; color: #ffa800; }
    .status-sent        { background: #e1f0ff; color: #3699ff; }
    .status-viewed      { background: #e1f0ff; color: #3699ff; }
    .status-paid        { background: #e8fff3; color: #50cd89; }
    .status-partially_paid { background: #eef3ff; color: #7239ea; }
    .status-overdue     { background: #fff5f8; color: #f1416c; }
    .status-void        { background: #f5f8fa; color: #a1a5b7; }
    .status-cancelled   { background: #f5f8fa; color: #a1a5b7; }

    /* ── META TABLE ──────────────────────────────────────── */
    .meta-table {
        width: 100%;
        margin-bottom: 20px;
        background: #f8f9fb;
        border-radius: 6px;
        padding: 12px 16px;
    }
    .meta-table td {
        padding: 6px 0;
        vertical-align: top;
    }
    .meta-label {
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #a1a5b7;
        font-weight: 600;
    }
    .meta-value {
        font-size: 12px;
        font-weight: 600;
        color: #1e2129;
    }

    .bill-to-title {
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #a1a5b7;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .bill-to-name {
        font-size: 14px;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 2px;
    }
    .bill-to-detail {
        font-size: 10px;
        color: #5e6278;
        line-height: 1.6;
    }

    /* ── ITEMS TABLE ──────────────────────────────────────── */
    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    table.items th {
        background: #f8f9fb;
        font-size: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #7e8299;
        text-align: left;
        padding: 8px 10px;
        border-bottom: 2px solid #eef0f8;
    }
    table.items th:last-child {
        text-align: right;
    }
    table.items td {
        padding: 8px 10px;
        border-bottom: 1px solid #f4f5f8;
        font-size: 11px;
        color: #3f4254;
    }
    table.items td:last-child {
        text-align: right;
        font-weight: 600;
    }
    table.items .item-name {
        font-weight: 600;
        color: #1e2129;
    }
    table.items .item-sku {
        font-size: 9px;
        color: #a1a5b7;
    }

    /* ── TOTALS ───────────────────────────────────────────── */
    .totals-table {
        width: 55%;
        margin-left: 45%;
        margin-top: 8px;
        border-collapse: collapse;
    }
    .totals-table td {
        padding: 5px 0;
        font-size: 11px;
    }
    .totals-table .t-label {
        color: #7e8299;
        text-align: left;
    }
    .totals-table .t-value {
        text-align: right;
        font-weight: 600;
        color: #1e2129;
    }
    .totals-table .grand-row td {
        border-top: 2px solid #1a1a2e;
        padding-top: 10px;
        font-size: 15px;
    }
    .totals-table .grand-row .t-label {
        color: #1a1a2e;
        font-weight: 700;
    }
    .totals-table .grand-row .t-value {
        color: #3699ff;
        font-weight: 700;
    }
    .totals-table .paid-row .t-value {
        color: #50cd89;
    }
    .totals-table .balance-row .t-value {
        color: #f1416c;
    }

    /* ── FOOTER ───────────────────────────────────────────── */
    .footer-notes {
        margin-top: 25px;
        padding-top: 12px;
        border-top: 1px solid #eef0f8;
        font-size: 10px;
        color: #7e8299;
        line-height: 1.6;
    }
    .footer-notes .footer-title {
        font-size: 9px;
        font-weight: 700;
        color: #3f4254;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 3px;
    }

    .thank-you {
        text-align: center;
        margin-top: 25px;
        padding-top: 15px;
        border-top: 1px solid #eef0f8;
        font-size: 12px;
        color: #a1a5b7;
        font-weight: 500;
        letter-spacing: 0.5px;
    }
    .thank-you span {
        color: #3699ff;
    }

    /* ── PRINT ────────────────────────────────────────────── */
    @media print {
        body { background: #fff; }
        .meta-table { background: #f8f9fb; }
    }
</style>
</head>
<body>

    {{-- ── HEADER: Logo + Company Name on Left, Invoice Badge on Right ── --}}
    <table class="header-table">
        <tr>
            <td style="width: 65%;">
                <div class="logo-wrapper">
                    <img 
                        src="{{ getLogoBase64() }}" 
                        class="logo-img"
                        alt="{{ getUIOptions('app_name') ?? config('app.name') }}"
                        style="max-height: 50px; width: auto;"
                    />
                    <div class="company-details">
                        <div class="company-name">{{ getUIOptions('app_name') ?? config('app.name') }}</div>
                        <div class="company-tagline">{{ __('pagination.receipt_tagline') ?? 'YOUR SATISFACTION IS OUR PRIORITY' }}</div>
                        <div class="company-contact">
                            {{ getUIOptions('app_email') ?? '' }}
                            <span>•</span>
                            {{ getUIOptions('app_contact') ?? '' }}
                            @if(getUIOptions('app_address'))
                            <span>•</span>
                            {{ getUIOptions('app_address') }}
                            @endif
                        </div>
                    </div>
                </div>
            </td>
            <td style="width: 35%;" class="invoice-badge">
                <div class="label">{{ __('payments.invoice') }}</div>
                <div class="number">
                    <span>{{ __('payments.number') }}:</span> {{ $invoice->invoice_number }}
                </div>
                <div><span class="status-pill status-{{ $invoice->status }}">{{ str_replace('_', ' ', $invoice->status) }}</span></div>
            </td>
        </tr>
    </table>

    {{-- ── META INFO ─────────────────────────────────────────── --}}
    <table class="meta-table">
        <tr>
            <td style="width: 35%;">
                <div class="bill-to-title">{{ __('payments.bill_to') }}</div>
                <div class="bill-to-name">{{ $invoice->billing_name }}</div>
                <div class="bill-to-detail">
                    @if($invoice->billing_email){{ $invoice->billing_email }}<br>@endif
                    @if($invoice->billing_phone){{ $invoice->billing_phone }}<br>@endif
                    @if($invoice->billing_address){{ $invoice->billing_address }}@endif
                </div>
            </td>
            <td style="width: 22%;">
                <div class="meta-label">{{ __('payments.issue_date') }}</div>
                <div class="meta-value">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('d M, Y') }}</div>
            </td>
            <td style="width: 22%;">
                <div class="meta-label">{{ __('payments.due_date') }}</div>
                <div class="meta-value">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M, Y') : '—' }}</div>
            </td>
            <td style="width: 21%;">
                <div class="meta-label">{{ __('payments.order_reference') }}</div>
                <div class="meta-value">#{{ $invoice->order->order_number ?? '—' }}</div>
            </td>
        </tr>
    </table>

    {{-- ── ITEMS ─────────────────────────────────────────────── --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width: 45%;">{{ __('payments.item') }}</th>
                <th style="width: 10%; text-align: center;">{{ __('payments.qty') }}</th>
                <th style="width: 15%; text-align: right;">{{ __('payments.unit_price') }}</th>
                <th style="width: 15%; text-align: right;">{{ __('payments.tax') }}</th>
                <th style="width: 15%; text-align: right;">{{ __('payments.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->order->orderItems as $item)
            <tr>
                <td>
                    <div class="item-name">{{ $item->item_name }}</div>
                    @if($item->sku)<div class="item-sku">{{ __('payments.sku') }}: {{ $item->sku }}</div>@endif
                </td>
                <td style="text-align: center; font-weight: 600;">{{ $item->quantity }}</td>
                <td style="text-align: right;">{{ currency_symbol() }} {{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right;">{{ currency_symbol() }} {{ number_format($item->tax_amount, 2) }}</td>
                <td style="text-align: right; font-weight: 700;">{{ currency_symbol() }} {{ number_format($item->total_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── TOTALS ────────────────────────────────────────────── --}}
    <table class="totals-table">
        <tr>
            <td class="t-label">{{ __('payments.subtotal') }}</td>
            <td class="t-value">{{ currency_symbol() }} {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        @if($invoice->discount_total > 0)
        <tr>
            <td class="t-label">{{ __('payments.discount') }}</td>
            <td class="t-value" style="color: #f1416c;">-{{ currency_symbol() }} {{ number_format($invoice->discount_total, 2) }}</td>
        </tr>
        @endif
        @if($invoice->tax_total > 0)
        <tr>
            <td class="t-label">{{ __('payments.tax') }}</td>
            <td class="t-value">{{ currency_symbol() }} {{ number_format($invoice->tax_total, 2) }}</td>
        </tr>
        @endif
        <tr class="grand-row">
            <td class="t-label">{{ __('payments.total_due') }}</td>
            <td class="t-value">{{ currency_symbol() }} {{ number_format($invoice->total, 2) }}</td>
        </tr>
        @if($invoice->amount_paid > 0)
        <tr class="paid-row">
            <td class="t-label">{{ __('payments.amount_paid') }}</td>
            <td class="t-value">{{ currency_symbol() }} {{ number_format($invoice->amount_paid, 2) }}</td>
        </tr>
        <tr class="balance-row">
            <td class="t-label">{{ __('payments.balance_due') }}</td>
            <td class="t-value">{{ currency_symbol() }} {{ number_format($invoice->balance_due, 2) }}</td>
        </tr>
        @endif
    </table>

    {{-- ── NOTES ─────────────────────────────────────────────── --}}
    @if($invoice->terms || $invoice->notes)
    <div class="footer-notes">
        @if($invoice->terms)
        <div class="footer-title">{{ __('payments.payment_terms') }}</div>
        <div>{{ $invoice->terms }}</div>
        @endif
        @if($invoice->notes)
        <div class="footer-title" style="margin-top: 10px;">{{ __('payments.notes') }}</div>
        <div>{{ $invoice->notes }}</div>
        @endif
    </div>
    @endif

    {{-- ── THANK YOU ─────────────────────────────────────────── --}}
    <div class="thank-you">
        {{ __('payments.thank_you_business') }}
    </div>

</body>
</html>