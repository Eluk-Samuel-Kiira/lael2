<table width="100%" cellpadding="0" cellspacing="0" style="background: #009ef7;">
    <tr>
        <td style="padding: 25px 30px; color: #fff;">
            <table width="100%"><tr>
                <td width="55%" style="font-size: 22px; font-weight: 700;">{{ getUIOptions('app_name') }}</td>
                <td width="45%" style="text-align: right;">
                    <div style="font-size: 24px; font-weight: 700;">{{ __('passwords.purchase_order') }}</div>
                    <div style="font-size: 14px; opacity: 0.9;">{{ $order->po_number }}</div>
                </td>
            </tr></table>
        </td>
    </tr>
</table>

<div style="padding: 30px 35px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 25px;">
        <tr>
            <td width="48%" style="background: #f9f9f9; border-radius: 8px; border-left: 4px solid #009ef7; padding: 16px; vertical-align: top;">
                <div style="color: #7e8299; font-size: 11px; text-transform: uppercase;">{{ __('passwords.supplier') }}</div>
                <div style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">{{ $order->supplier->name ?? '—' }}</div>
                <div style="font-size: 12px; color: #5e6278;">{{ $order->supplier->email ?? '' }}</div>
                <div style="font-size: 12px; color: #5e6278;">{{ $order->supplier->phone ?? '' }}</div>
            </td>
            <td width="4%"></td>
            <td width="48%" style="background: #f9f9f9; border-radius: 8px; border-left: 4px solid #009ef7; padding: 16px; vertical-align: top;">
                <div style="color: #7e8299; font-size: 11px; text-transform: uppercase;">{{ __('passwords.expected_delivery') }}</div>
                <div style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">{{ $order->expected_delivery_date?->format('M d, Y') ?? '—' }}</div>
                <div style="color: #7e8299; font-size: 11px; text-transform: uppercase;">{{ __('passwords.status') }}</div>
                <div style="font-size: 14px; font-weight: 700; text-transform: capitalize;">{{ str_replace('_', ' ', $order->status) }}</div>
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
        <thead>
            <tr style="background: #f9f9f9;">
                <th style="padding: 10px 8px; text-align: left; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #e4e6ef;">{{ __('passwords.product') }}</th>
                <th style="padding: 10px 8px; text-align: center; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #e4e6ef;">{{ __('passwords.ordered_qty') }}</th>
                <th style="padding: 10px 8px; text-align: right; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #e4e6ef;">{{ __('passwords.unit_cost') }}</th>
                <th style="padding: 10px 8px; text-align: right; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid #e4e6ef;">{{ __('passwords.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
            <tr>
                <td style="padding: 10px 8px; font-size: 12px; border-bottom: 1px solid #f1f1f2;">{{ $item->product_name }} <span style="color:#7e8299; font-size:10px;">({{ $item->sku }})</span></td>
                <td style="padding: 10px 8px; font-size: 12px; text-align: center; border-bottom: 1px solid #f1f1f2;">{{ $item->quantity }}</td>
                <td style="padding: 10px 8px; font-size: 12px; text-align: right; border-bottom: 1px solid #f1f1f2;">{{ number_format($item->unit_cost, 2) }}</td>
                <td style="padding: 10px 8px; font-size: 12px; text-align: right; font-weight: 700; border-bottom: 1px solid #f1f1f2;">{{ number_format($item->quantity * $item->unit_cost, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table width="100%" style="margin-top: 20px;">
        <tr>
            <td width="60%"></td>
            <td width="40%" style="background: #f9f9f9; border-radius: 8px; padding: 16px;">
                <table width="100%">
                    <tr><td style="font-size: 13px; color: #5e6278;">{{ __('passwords.subtotal') }}</td><td style="text-align: right; font-weight: 700;">{{ number_format($order->subtotal, 2) }} {{ currency_symbol() }}</td></tr>
                    <tr><td style="font-size: 15px; font-weight: 700; padding-top: 8px; border-top: 2px dashed #e4e6ef;">{{ __('passwords.total') }}</td><td style="text-align: right; font-size: 20px; font-weight: 700; color: #009ef7; padding-top: 8px; border-top: 2px dashed #e4e6ef;">{{ number_format($order->total, 2) }} {{ currency_symbol() }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    @if($order->notes)
    <div style="margin-top: 25px; font-size: 12px; color: #5e6278;">
        <strong>{{ __('passwords._notes') }}:</strong> {{ $order->notes }}
    </div>
    @endif
</div>