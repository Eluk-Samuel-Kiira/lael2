<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{{ __('passwords.purchase_order') }}</title></head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9; padding: 30px 0;">
        <tr><td align="center">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#fff; border-radius: 10px; overflow: hidden;">
                <tr>
                    <td style="background: #009ef7; padding: 30px; text-align: center;">
                        <img src="{{ getLogoImage() }}" alt="{{ getUIOptions('app_name') }}" style="max-height: 40px; filter: brightness(0) invert(1);" />
                    </td>
                </tr>
                <tr>
                    <td style="padding: 35px 30px;">
                        <p style="font-size: 15px; color: #181c32; margin: 0 0 16px 0;">
                            {{ __('passwords.hello') }} {{ $order->supplier->name ?? __('passwords.supplier') }},
                        </p>
                        <p style="font-size: 14px; color: #5e6278; line-height: 1.6; margin: 0 0 20px 0;">
                            {{ __('passwords.purchase_order_intro', ['number' => $order->po_number]) }}
                        </p>
                        @if($customMessage)
                        <table width="100%" style="background: #f9f9f9; border-left: 4px solid #009ef7; border-radius: 6px; margin-bottom: 20px;">
                            <tr><td style="padding: 14px 18px; font-size: 13px; color: #5e6278;">{{ $customMessage }}</td></tr>
                        </table>
                        @endif
                        <table width="100%" style="background: #f9f9f9; border-radius: 8px; margin-bottom: 24px;">
                            <tr><td style="padding: 18px 20px;">
                                <table width="100%">
                                    <tr><td style="font-size: 13px; color: #5e6278;">{{ __('passwords.po_number') }}</td><td style="font-weight: 700; text-align: right;">{{ $order->po_number }}</td></tr>
                                    <tr><td style="font-size: 13px; color: #5e6278;">{{ __('passwords.total') }}</td><td style="font-weight: 700; text-align: right;">{{ number_format($order->total, 2) }} {{ currency_symbol() }}</td></tr>
                                </table>
                            </td></tr>
                        </table>
                        <p style="font-size: 13px; color: #7e8299;">{{ __('passwords.purchase_order_attached_note') }}</p>
                    </td>
                </tr>
                <tr><td style="background: #f9f9f9; padding: 20px 30px; text-align: center;">
                    <p style="font-size: 12px; color: #7e8299; margin: 0;">{{ getUIOptions('app_name') }}</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>