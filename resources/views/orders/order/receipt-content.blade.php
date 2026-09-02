@php $payments = $order->orderPayments; @endphp

<table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #009ef7 0%, #0095e8 100%); background-color: #009ef7;">
    <tr>
        <td style="padding: 25px 30px; color: #fff;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="55%" style="vertical-align: middle;">
                        <table cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="background: rgba(255,255,255,0.2); border-radius: 10px; padding: 8px 12px;">
                                    <img alt="Logo" src="{{ getLogoImage() }}" style="max-height: 40px; width: auto;" />
                                </td>
                                <td style="padding-left: 16px; vertical-align: middle;">
                                    <div style="font-size: 20px; font-weight: 700;">{{ getUIOptions('app_name') }}</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td width="45%" style="text-align: right; vertical-align: middle;">
                        <div style="font-size: 26px; font-weight: 700;">{{ __('passwords.order_invoice') }}</div>
                        <div style="font-size: 15px; font-weight: 500; opacity: 0.9; margin-top: 2px;">Order #{{ $order->order_number }}</div>
                        <div style="font-size: 12px; opacity: 0.7; margin-top: 4px;">{{ now()->format('d M Y, h:i A') }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div style="padding: 30px 35px;">

    {{-- ── Info cards ────────────────────────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
        <tr>
            <td width="48%" style="background: #f9f9f9; border-radius: 8px; border-left: 4px solid #009ef7; padding: 16px; vertical-align: top;">
                <div style="color: #7e8299; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 3px;">{{ __('passwords._customer') }}</div>
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">{{ $order->customer_name ?? __('passwords.none') }}</div>
                <div style="color: #7e8299; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 3px;">{{ __('passwords.location') }}</div>
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">{{ $order->location->name ?? '—' }}</div>
                <div style="color: #7e8299; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 3px;">{{ __('passwords.department') }}</div>
                <div style="font-size: 14px; font-weight: 600;">{{ $order->department->name ?? '—' }}</div>
            </td>
            <td width="4%"></td>
            <td width="48%" style="background: #f9f9f9; border-radius: 8px; border-left: 4px solid #009ef7; padding: 16px; vertical-align: top;">
                <div style="color: #7e8299; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 3px;">{{ __('auth._creater') }}</div>
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px;">{{ $order->orderCreater->name ?? __('passwords.none') }}</div>
                <div style="color: #7e8299; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 3px;">{{ __('auth._status') }}</div>
                <div style="font-size: 14px; font-weight: 600; margin-bottom: 10px; text-transform: capitalize;">{{ $order->status }}</div>
                <div style="color: #7e8299; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-bottom: 3px;">{{ __('passwords._source') }}</div>
                <div style="font-size: 14px; font-weight: 600; text-transform: capitalize;">{{ $order->source }}</div>
            </td>
        </tr>
    </table>

    {{-- ── Payments ──────────────────────────────────────────── --}}
    @if($payments->count() > 0)
    <table width="100%" cellpadding="0" cellspacing="0" style="background: #e8fff3; border: 1px solid #cceedd; border-radius: 8px; margin-bottom: 25px;">
        <tr>
            <td style="padding: 18px 20px;">
                <div style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">
                    💳 {{ __('payments.payment_details') }}@if($payments->count() > 1) ({{ $payments->count() }} {{ __('payments.payments') }})@endif
                </div>
                @foreach($payments as $payment)
                <table width="100%" cellpadding="0" cellspacing="0" style="{{ !$loop->first ? 'margin-top:10px;padding-top:10px;border-top:1px dashed #ccc;' : '' }}">
                    <tr>
                        <td width="33%" style="padding: 4px 8px 4px 0; vertical-align: top;">
                            <div style="color: #7e8299; font-size: 10px; font-weight: 600; text-transform: uppercase;">{{ __('payments.payment_method') }}</div>
                            <div style="font-size: 13px; font-weight: 700;">{{ $payment->paymentMethod->name ?? 'N/A' }}</div>
                        </td>
                        <td width="33%" style="padding: 4px 8px; vertical-align: top;">
                            <div style="color: #7e8299; font-size: 10px; font-weight: 600; text-transform: uppercase;">{{ __('payments.amount') }}</div>
                            <div style="font-size: 13px; font-weight: 700; color: #50cd89;">{{ format_currency($payment->amount) }}</div>
                        </td>
                        <td width="33%" style="padding: 4px 0 4px 8px; vertical-align: top;">
                            <div style="color: #7e8299; font-size: 10px; font-weight: 600; text-transform: uppercase;">{{ __('auth._status') }}</div>
                            <div style="font-size: 13px; font-weight: 700; color: #50cd89; text-transform: capitalize;">{{ $payment->status }}</div>
                        </td>
                    </tr>
                </table>
                @endforeach
            </td>
        </tr>
    </table>
    @endif

    {{-- ── Items ─────────────────────────────────────────────── --}}
    <div style="font-size: 16px; font-weight: 700; margin-bottom: 12px;">{{ __('passwords.order_items') }}</div>

    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
        <thead>
            <tr style="background: #f9f9f9;">
                <th style="padding: 10px 8px; text-align: left; font-size: 10px; text-transform: uppercase; color: #5e6278; border-bottom: 2px solid #e4e6ef;">{{ __('passwords.item_name') }}</th>
                <th style="padding: 10px 8px; text-align: center; font-size: 10px; text-transform: uppercase; color: #5e6278; border-bottom: 2px solid #e4e6ef;">{{ __('passwords.quantity') }}</th>
                <th style="padding: 10px 8px; text-align: right; font-size: 10px; text-transform: uppercase; color: #5e6278; border-bottom: 2px solid #e4e6ef;">{{ __('passwords.unit_price') }}</th>
                <th style="padding: 10px 8px; text-align: right; font-size: 10px; text-transform: uppercase; color: #5e6278; border-bottom: 2px solid #e4e6ef;">{{ __('passwords.discount') }}</th>
                <th style="padding: 10px 8px; text-align: right; font-size: 10px; text-transform: uppercase; color: #5e6278; border-bottom: 2px solid #e4e6ef;">{{ __('passwords.tax') }}</th>
                <th style="padding: 10px 8px; text-align: right; font-size: 10px; text-transform: uppercase; color: #5e6278; border-bottom: 2px solid #e4e6ef;">{{ __('passwords.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->orderItems as $item)
            <tr>
                <td style="padding: 10px 8px; font-size: 12px; border-bottom: 1px solid #f1f1f2;">{{ $item->item_name }}</td>
                <td style="padding: 10px 8px; font-size: 12px; text-align: center; border-bottom: 1px solid #f1f1f2;">{{ $item->quantity }}</td>
                <td style="padding: 10px 8px; font-size: 12px; text-align: right; border-bottom: 1px solid #f1f1f2;">{{ format_currency($item->unit_price) }}</td>
                <td style="padding: 10px 8px; font-size: 12px; text-align: right; color: #f1416c; border-bottom: 1px solid #f1f1f2;">-{{ format_currency($item->discount) }}</td>
                <td style="padding: 10px 8px; font-size: 12px; text-align: right; border-bottom: 1px solid #f1f1f2;">{{ format_currency($item->tax_amount) }}</td>
                <td style="padding: 10px 8px; font-size: 12px; text-align: right; font-weight: 700; border-bottom: 1px solid #f1f1f2;">{{ format_currency($item->total_price) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Summary ───────────────────────────────────────────── --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 25px;">
        <tr>
            <td width="55%"></td>
            <td width="45%">
                <table width="100%" cellpadding="0" cellspacing="0" style="background: #f9f9f9; border: 1px solid #e4e6ef; border-radius: 8px;">
                    <tr><td colspan="2" style="padding: 18px 20px 6px 20px;">
                        <table width="100%"><tr>
                            <td style="font-size: 12px; color: #5e6278;">{{ __('passwords.subtotal') }}</td>
                            <td style="font-size: 13px; font-weight: 700; text-align: right;">{{ format_currency($order->subtotal) }}</td>
                        </tr></table>
                    </td></tr>
                    <tr><td colspan="2" style="padding: 6px 20px;">
                        <table width="100%"><tr>
                            <td style="font-size: 12px; color: #5e6278;">{{ __('passwords.discount') }}</td>
                            <td style="font-size: 13px; font-weight: 700; text-align: right; color: #f1416c;">-{{ format_currency($order->discount_total) }}</td>
                        </tr></table>
                    </td></tr>
                    <tr><td colspan="2" style="padding: 6px 20px;">
                        <table width="100%"><tr>
                            <td style="font-size: 12px; color: #5e6278;">{{ __('passwords.tax') }}</td>
                            <td style="font-size: 13px; font-weight: 700; text-align: right;">{{ format_currency($order->tax_total) }}</td>
                        </tr></table>
                    </td></tr>
                    <tr><td colspan="2" style="padding: 12px 20px; border-top: 2px dashed #e4e6ef;">
                        <table width="100%"><tr>
                            <td style="font-size: 15px; font-weight: 700;">{{ __('passwords.total') }}</td>
                            <td style="font-size: 22px; font-weight: 700; text-align: right; color: #009ef7;">{{ format_currency($order->total) }}</td>
                        </tr></table>
                    </td></tr>
                    @if($payments->count() > 0)
                    <tr><td colspan="2" style="padding: 6px 20px;">
                        <table width="100%"><tr>
                            <td style="font-size: 12px; color: #5e6278;">{{ __('passwords.paid') }}</td>
                            <td style="font-size: 13px; font-weight: 700; text-align: right; color: #50cd89;">{{ format_currency($order->paid_amount) }}</td>
                        </tr></table>
                    </td></tr>
                    <tr><td colspan="2" style="padding: 6px 20px 18px 20px;">
                        <table width="100%"><tr>
                            <td style="font-size: 12px; color: #5e6278;">{{ __('passwords.balance_due') }}</td>
                            <td style="font-size: 13px; font-weight: 700; text-align: right; color: #f1416c;">{{ format_currency($order->balance_due) }}</td>
                        </tr></table>
                    </td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

</div>

<table width="100%" cellpadding="0" cellspacing="0" style="background: #f9f9f9; margin-top: 20px;">
    <tr>
        <td style="padding: 20px 35px; text-align: center; color: #7e8299; font-size: 12px;">
            <div style="font-size: 15px; font-weight: 600; color: #181c32; margin-bottom: 6px;">{{ __('passwords.thank_you_business') }}</div>
            <div>{{ __('passwords.print_on') }}: {{ now()->format('M d, Y H:i') }}</div>
        </td>
    </tr>
</table>