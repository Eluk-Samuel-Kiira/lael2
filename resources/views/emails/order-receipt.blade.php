<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('passwords.order_invoice') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family: 'Helvetica', 'Arial', sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9; padding: 30px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background:#fff; border-radius: 10px; overflow: hidden;">

                    {{-- Header --}}
                    <tr>
                        <td style="background: linear-gradient(135deg, #009ef7 0%, #0095e8 100%); background-color: #009ef7; padding: 30px; text-align: center;">
                            <img src="{{ getLogoImage() }}" alt="{{ getUIOptions('app_name') }}" style="max-height: 40px; filter: brightness(0) invert(1);" />
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding: 35px 30px;">
                            <p style="font-size: 15px; color: #181c32; margin: 0 0 16px 0;">
                                {{ __('passwords.hello') }} {{ $order->customer_name ?? __('passwords.customer') }},
                            </p>

                            <p style="font-size: 14px; color: #5e6278; line-height: 1.6; margin: 0 0 20px 0;">
                                {{ __('passwords.order_receipt_intro', ['number' => $order->order_number]) }}
                            </p>

                            @if($customMessage)
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f9f9f9; border-left: 4px solid #009ef7; border-radius: 6px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding: 14px 18px; font-size: 13px; color: #5e6278; line-height: 1.6;">
                                        {{ $customMessage }}
                                    </td>
                                </tr>
                            </table>
                            @endif

                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f9f9f9; border-radius: 8px; margin-bottom: 24px;">
                                <tr>
                                    <td style="padding: 18px 20px;">
                                        <table width="100%">
                                            <tr>
                                                <td style="font-size: 13px; color: #5e6278; padding: 4px 0;">{{ __('passwords.order_number') }}</td>
                                                <td style="font-size: 13px; font-weight: 700; text-align: right; padding: 4px 0;">{{ $order->order_number }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 13px; color: #5e6278; padding: 4px 0;">{{ __('passwords.total') }}</td>
                                                <td style="font-size: 13px; font-weight: 700; text-align: right; padding: 4px 0;">{{ format_currency($order->total) }}</td>
                                            </tr>
                                            <tr>
                                                <td style="font-size: 13px; color: #5e6278; padding: 4px 0;">{{ __('auth._status') }}</td>
                                                <td style="font-size: 13px; font-weight: 700; text-align: right; text-transform: capitalize; padding: 4px 0;">{{ $order->status }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 13px; color: #7e8299; line-height: 1.6; margin: 0;">
                                {{ __('passwords.order_receipt_attached_note') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="background: #f9f9f9; padding: 20px 30px; text-align: center;">
                            <p style="font-size: 12px; color: #7e8299; margin: 0;">
                                {{ __('passwords.thank_you_business') }} — {{ getUIOptions('app_name') }}
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>