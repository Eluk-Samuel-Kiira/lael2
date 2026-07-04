{{-- resources/views/public/invoice/show.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('payments.invoice') }} {{ $invoice->invoice_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .invoice-box {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            line-height: 24px;
            font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
            color: #555;
        }
        .invoice-box table {
            width: 100%;
            line-height: inherit;
            text-align: left;
        }
        .invoice-box table td {
            padding: 5px;
            vertical-align: top;
        }
        .invoice-box table tr td:nth-child(2) {
            text-align: right;
        }
        .invoice-box table tr.top table td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.top table td.title {
            font-size: 45px;
            line-height: 45px;
            color: #333;
        }
        .invoice-box table tr.information table td {
            padding-bottom: 40px;
        }
        .invoice-box table tr.heading td {
            background: #eee;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }
        .invoice-box table tr.details td {
            padding-bottom: 20px;
        }
        .invoice-box table tr.item td {
            border-bottom: 1px solid #eee;
        }
        .invoice-box table tr.item.last td {
            border-bottom: none;
        }
        .invoice-box table tr.total td:nth-child(2) {
            border-top: 2px solid #eee;
            font-weight: bold;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-paid { background: #d4edda; color: #155724; }
        .status-overdue { background: #f8d7da; color: #721c24; }
        .status-sent { background: #cce5ff; color: #004085; }
        .status-draft { background: #fff3cd; color: #856404; }
        .status-void { background: #e2e3e5; color: #383d41; }
        .btn-pay {
            display: inline-block;
            padding: 12px 30px;
            background: #3699ff;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s;
        }
        .btn-pay:hover {
            background: #187de4;
            color: #fff;
        }
        @media only screen and (max-width: 600px) {
            .invoice-box table tr.top table td {
                width: 100%;
                display: block;
                text-align: center;
            }
            .invoice-box table tr.information table td {
                width: 100%;
                display: block;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="invoice-box">
            <table cellpadding="0" cellspacing="0">
                <tr class="top">
                    <td colspan="2">
                        <table>
                            <tr>
                                <td class="title">
                                    <h2>{{ config('app.name') }}</h2>
                                </td>
                                <td>
                                    <strong>{{ __('payments.invoice') }} #{{ $invoice->invoice_number }}</strong><br>
                                    {{ __('payments.issue_date') }}: {{ $invoice->issue_date->format('d M, Y') }}<br>
                                    {{ __('payments.due_date') }}: {{ $invoice->due_date ? $invoice->due_date->format('d M, Y') : '—' }}<br>
                                    <span class="status-badge status-{{ $invoice->status }}">
                                        {{ str_replace('_', ' ', $invoice->status) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <tr class="information">
                    <td colspan="2">
                        <table>
                            <tr>
                                <td>
                                    <strong>{{ __('payments.bill_to') }}</strong><br>
                                    {{ $invoice->billing_name }}<br>
                                    @if($invoice->billing_email){{ $invoice->billing_email }}<br>@endif
                                    @if($invoice->billing_phone){{ $invoice->billing_phone }}<br>@endif
                                    @if($invoice->billing_address){{ $invoice->billing_address }}@endif
                                </td>
                                <td>
                                    <strong>{{ __('payments.balance_due') }}</strong><br>
                                    <span style="font-size: 28px; color: #3699ff;">
                                        {{ currency_symbol() }} {{ number_format($invoice->balance_due, 2) }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <tr class="heading">
                    <td>{{ __('payments.item') }}</td>
                    <td>{{ __('payments.total') }}</td>
                </tr>
                
                @foreach($invoice->order->orderItems as $item)
                <tr class="item">
                    <td>{{ $item->item_name }}</td>
                    <td>{{ currency_symbol() }} {{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
                
                <tr class="total">
                    <td></td>
                    <td>
                        <table>
                            <tr>
                                <td><strong>{{ __('payments.subtotal') }}</strong></td>
                                <td>{{ currency_symbol() }} {{ number_format($invoice->subtotal, 2) }}</td>
                            </tr>
                            @if($invoice->discount_total > 0)
                            <tr>
                                <td>{{ __('payments.discount') }}</td>
                                <td>-{{ currency_symbol() }} {{ number_format($invoice->discount_total, 2) }}</td>
                            </tr>
                            @endif
                            @if($invoice->tax_total > 0)
                            <tr>
                                <td>{{ __('payments.tax') }}</td>
                                <td>{{ currency_symbol() }} {{ number_format($invoice->tax_total, 2) }}</td>
                            </tr>
                            @endif
                            <tr style="font-size: 20px;">
                                <td><strong>{{ __('payments.grand_total') }}</strong></td>
                                <td><strong>{{ currency_symbol() }} {{ number_format($invoice->total, 2) }}</strong></td>
                            </tr>
                            @if($invoice->amount_paid > 0)
                            <tr>
                                <td>{{ __('payments.amount_paid') }}</td>
                                <td style="color: #50cd89;">{{ currency_symbol() }} {{ number_format($invoice->amount_paid, 2) }}</td>
                            </tr>
                            <tr style="font-size: 18px; color: #f1416c;">
                                <td><strong>{{ __('payments.balance_due') }}</strong></td>
                                <td><strong>{{ currency_symbol() }} {{ number_format($invoice->balance_due, 2) }}</strong></td>
                            </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>
            
            @if(!$invoice->isPaid() && !$invoice->isVoid())
            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                <a href="{{ route('public.invoice.pay', $invoice->public_token) }}" class="btn-pay">
                    <i class="bi bi-credit-card me-2"></i>{{ __('payments.pay_now') }}
                </a>
                <p style="margin-top: 10px; color: #999; font-size: 14px;">
                    {{ __('payments.secure_payment') }}
                </p>
            </div>
            @endif
            
            @if($invoice->notes || $invoice->terms)
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 14px; color: #777;">
                @if($invoice->terms)
                <p><strong>{{ __('payments.terms_conditions') }}:</strong><br>{{ $invoice->terms }}</p>
                @endif
                @if($invoice->notes)
                <p><strong>{{ __('payments.notes') }}:</strong><br>{{ $invoice->notes }}</p>
                @endif
            </div>
            @endif
            
            <div style="text-align: center; margin-top: 20px; color: #999; font-size: 12px;">
                {{ __('payments.thank_you_business') }}
            </div>
        </div>
    </div>
</body>
</html>