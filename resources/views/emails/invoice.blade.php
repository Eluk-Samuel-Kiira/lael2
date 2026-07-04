{{-- resources/views/emails/invoice.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('payments.invoice_email_subject') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #3699ff;
            padding: 20px 30px;
            border-radius: 8px 8px 0 0;
            color: white;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .invoice-details {
            background-color: #f8f9fb;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-details td {
            padding: 6px 10px;
        }
        .invoice-details .label {
            color: #7e8299;
            font-weight: 600;
        }
        .invoice-details .value {
            font-weight: 600;
            text-align: right;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table th {
            background-color: #f8f9fb;
            padding: 10px 12px;
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            color: #7e8299;
            border-bottom: 2px solid #eef0f8;
        }
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eef0f8;
            font-size: 14px;
        }
        .items-table .text-end {
            text-align: right;
        }
        .total-row {
            font-weight: 700;
            font-size: 16px;
        }
        .total-row td {
            border-top: 2px solid #1a1a2e;
            padding-top: 12px;
        }
        .total-row .label {
            color: #1a1a2e;
        }
        .total-row .value {
            color: #3699ff;
        }
        .payment-box {
            text-align: center;
            margin: 25px 0;
            background-color: #f8f9fb;
            padding: 20px;
            border-radius: 8px;
        }
        .payment-box .amount {
            font-size: 24px;
            font-weight: 700;
            color: #3699ff;
        }
        .payment-box .balance {
            font-size: 18px;
            font-weight: 600;
            color: #f1416c;
        }
        .footer {
            text-align: center;
            padding: 20px 30px;
            color: #7e8299;
            font-size: 12px;
            border-top: 1px solid #eef0f8;
            margin-top: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-paid { background: #e8fff3; color: #50cd89; }
        .status-overdue { background: #fff5f8; color: #f1416c; }
        .status-sent { background: #e1f0ff; color: #3699ff; }
        .status-draft { background: #fff4de; color: #ffa800; }
        .status-void { background: #f5f8fa; color: #a1a5b7; }
        .status-partially_paid { background: #eef3ff; color: #7239ea; }
        .status-cancelled { background: #f5f8fa; color: #a1a5b7; }
        
        @media only screen and (max-width: 480px) {
            .container { padding: 10px; }
            .content { padding: 15px; }
            .items-table td, .items-table th { padding: 6px 8px; font-size: 12px; }
            .header h1 { font-size: 20px; }
            .payment-box .amount { font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
            <p>{{ __('payments.invoice') }} #{{ $invoice->invoice_number }}</p>
        </div>

        {{-- Content --}}
        <div class="content">
            <p style="font-size: 16px; margin-bottom: 5px;">
                {{ __('payments.dear') }} <strong>{{ $invoice->billing_name }}</strong>,
            </p>
            <p>{{ $customMessage ?? __('payments.invoice_email_body', [
                'number' => $invoice->invoice_number,
                'total' => number_format($invoice->total, 2)
            ]) }}</p>

            {{-- Invoice Summary --}}
            <div class="invoice-details">
                <table>
                    <tr>
                        <td class="label">{{ __('payments.invoice_number') }}</td>
                        <td class="value">{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="label">{{ __('payments.issue_date') }}</td>
                        <td class="value">{{ $invoice->issue_date->format('d M, Y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">{{ __('payments.due_date') }}</td>
                        <td class="value">{{ $invoice->due_date ? $invoice->due_date->format('d M, Y') : '—' }}</td>
                    </tr>
                    <tr>
                        <td class="label">{{ __('payments.status') }}</td>
                        <td class="value">
                            <span class="status-badge status-{{ $invoice->status }}">
                                {{ str_replace('_', ' ', $invoice->status) }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">{{ __('payments.total_amount') }}</td>
                        <td class="value" style="font-size: 18px; color: #3699ff;">
                            {{ currency_symbol() }} {{ number_format($invoice->total, 2) }}
                        </td>
                    </tr>
                    @if($invoice->amount_paid > 0)
                    <tr>
                        <td class="label">{{ __('payments.amount_paid') }}</td>
                        <td class="value" style="color: #50cd89;">
                            {{ currency_symbol() }} {{ number_format($invoice->amount_paid, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">{{ __('payments.balance_due') }}</td>
                        <td class="value" style="color: #f1416c;">
                            {{ currency_symbol() }} {{ number_format($invoice->balance_due, 2) }}
                        </td>
                    </tr>
                    @endif
                </table>
            </div>

            {{-- Items Table --}}
            @if($invoice->order && $invoice->order->orderItems->count() > 0)
            <table class="items-table">
                <thead>
                    <tr>
                        <th>{{ __('payments.item') }}</th>
                        <th class="text-end">{{ __('payments.qty') }}</th>
                        <th class="text-end">{{ __('payments.unit_price') }}</th>
                        <th class="text-end">{{ __('payments.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->order->orderItems as $item)
                    <tr>
                        <td>
                            {{ $item->item_name }}
                            @if($item->sku)
                                <br><small style="color: #7e8299;">SKU: {{ $item->sku }}</small>
                            @endif
                        </td>
                        <td class="text-end">{{ $item->quantity }}</td>
                        <td class="text-end">{{ currency_symbol() }} {{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end">{{ currency_symbol() }} {{ number_format($item->total_price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif

            {{-- Payment Box --}}
            @if(!$invoice->isPaid() && !$invoice->isVoid())
            <div style="text-align: center; margin: 25px 0; background-color: #f8f9fb; padding: 25px; border-radius: 8px;">
                <p style="margin-bottom: 10px; color: #5e6278; font-size: 16px;">
                    <strong>{{ __('payments.total_amount') }}:</strong> 
                    <span style="font-size: 22px; color: #3699ff; font-weight: 700;">
                        {{ currency_symbol() }} {{ number_format($invoice->total, 2) }}
                    </span>
                </p>
                @if($invoice->balance_due > 0)
                <p style="margin-bottom: 15px; color: #f1416c; font-size: 16px;">
                    <strong>{{ __('payments.balance_due') }}:</strong> 
                    <span style="font-size: 20px; font-weight: 700;">
                        {{ currency_symbol() }} {{ number_format($invoice->balance_due, 2) }}
                    </span>
                </p>
                @endif
                
                {{-- PAYMENT BUTTON WITH PUBLIC LINK --}}
                <a href="{{ route('public.invoice.pay', $invoice->public_token) }}" 
                style="display: inline-block; padding: 14px 40px; background-color: #3699ff; 
                        color: #ffffff !important; text-decoration: none; border-radius: 8px; 
                        font-weight: 700; font-size: 16px; margin: 10px 0;
                        box-shadow: 0 4px 12px rgba(54, 153, 255, 0.4);">
                    <i class="bi bi-credit-card" style="margin-right: 8px;"></i>
                    {{ __('payments.pay_now') }}
                </a>
                
                <p style="margin-top: 10px; color: #7e8299; font-size: 13px;">
                    {{ __('payments.secure_payment_link') }}
                </p>
                <p style="font-size: 12px; color: #a1a5b7;">
                    {{ __('payments.you_can_also_copy_link') }}
                </p>
            </div>
            @endif

            {{-- Footer Notes --}}
            @if($invoice->terms || $invoice->notes)
            <div style="background-color: #f8f9fb; padding: 15px 20px; border-radius: 8px; margin-top: 20px;">
                @if($invoice->terms)
                    <p style="margin-bottom: 5px;"><strong>{{ __('payments.terms_conditions') }}:</strong></p>
                    <p style="margin-top: 0; color: #5e6278;">{{ $invoice->terms }}</p>
                @endif
                @if($invoice->notes)
                    <p style="margin-bottom: 5px;"><strong>{{ __('payments.notes') }}:</strong></p>
                    <p style="margin-top: 0; color: #5e6278;">{{ $invoice->notes }}</p>
                @endif
            </div>
            @endif

            <p style="margin-top: 25px; color: #7e8299; font-size: 14px;">
                {{ __('payments.thank_you_business') }}
            </p>
            <p style="color: #7e8299; font-size: 12px;">
                {{ __('payments.this_is_auto_generated') }}
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                {{ config('app.name') }} &bull;
                {{ getUIOptions('app_address') ?? '' }} &bull;
                {{ getUIOptions('app_email') ?? '' }}
            </p>
            <p style="margin-top: 5px;">
                {{ __('payments.if_you_have_questions') }}
            </p>
        </div>
    </div>
</body>
</html>