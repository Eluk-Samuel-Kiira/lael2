@can('view order')
<div class="card-body py-4" id="ordersIndexTable">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox"
                                data-kt-check="true"
                                data-kt-check-target="#kt_table_users .form-check-input"
                                value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('passwords.order_number')}}</th>
                    <th class="min-w-125px">{{__('passwords._customer')}}</th>
                    <th class="min-w-125px">{{__('passwords._type')}}</th>
                    <th class="min-w-125px">{{__('passwords._status')}}</th>
                    <th class="min-w-125px">{{__('passwords._source')}}</th>
                    <th class="min-w-125px">{{__('passwords._amount')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @forelse ($orders as $order)
                    @php
                        $statusColors = [
                            'pending'    => 'warning',
                            'confirmed'  => 'primary',
                            'processing' => 'info',
                            'completed'  => 'success',
                            'cancelled'  => 'danger',
                            'refunded'   => 'dark',
                            'draft'      => 'secondary',
                        ];
                        $statusColor  = $statusColors[strtolower($order->status)] ?? 'secondary';
                        $payments     = $order->orderPayments;
                        $paymentCount = $payments->count();
                    @endphp

                    {{-- ── Main row ──────────────────────────────────────────────────── --}}
                    <tr data-role="{{ $order->id }}" class="order-row">

                        <td>
                            <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="{{ $order->id }}" />
                            </div>
                        </td>

                        {{-- Order # → opens offcanvas --}}
                        <td>
                            <a href="javascript:void(0);"
                               class="d-flex align-items-center text-gray-800 text-hover-primary fw-bold"
                               data-bs-toggle="offcanvas"
                               data-bs-target="#orderDetail{{ $order->id }}"
                               aria-controls="orderDetail{{ $order->id }}">
                                <i class="ki-duotone ki-right fs-5 me-2 text-muted"></i>
                                {{ $order->order_number }}
                            </a>
                        </td>

                        <td><span class="text-gray-800 fw-bold">{{ $order->customer_name ?? __('passwords.none') }}</span></td>

                        <td>
                            <span class="badge badge-light-primary fw-bold fs-8 px-3 py-2">{{ ucwords($order->type) }}</span>
                        </td>

                        <td>
                            <span class="badge badge-light-{{ $statusColor }} fw-bold fs-8 px-3 py-2">
                                <span class="bullet bullet-{{ $statusColor }} bullet-sm me-2"></span>
                                {{ ucwords($order->status) }}
                            </span>
                        </td>

                        <td><span class="text-gray-800 fw-bold">{{ ucwords($order->source) }}</span></td>

                        <td>
                            <span class="fw-bold text-gray-800">{{ format_currency($order->total) }}</span>
                            @if($order->balance_due > 0)
                                <small class="d-block text-danger fs-8">↳ {{ __('passwords.balance_due') }}: {{ format_currency($order->balance_due) }}</small>
                            @endif
                        </td>

                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                @can('complete order')
                                    @if ($order->status === 'confirmed' && $order->source === 'pos')
                                        <button class="btn btn-sm btn-icon btn-light-primary"
                                            onclick="openCompletePayment_{{ $order->id }}()"
                                            data-bs-toggle="tooltip" data-bs-title="{{ __('pagination._complete') }}">
                                            <i class="ki-duotone ki-dollar fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        </button>
                                    @endif
                                @endcan
                                
                                @can('refund order')
                                    @if ($order->status === 'completed')
                                        <button class="btn btn-sm btn-icon btn-light-success"
                                            onclick="openRefund_{{ $order->id }}()"
                                            data-bs-toggle="tooltip" data-bs-title="{{ __('payments.refund') }}">
                                            <i class="ki-duotone ki-arrow-circle-left fs-4">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                        </button>
                                    @endif
                                @endcan

                                @can('cancel order')
                                    @if ($order->status === 'confirmed')
                                        <button class="btn btn-sm btn-icon btn-light-danger"
                                            onclick="cancelPOSOrder({{ $order->id }})"
                                            data-bs-toggle="tooltip" data-bs-title="{{ __('passwords.cancel') }}">
                                            <i class="ki-duotone ki-cross fs-4"><span class="path1"></span><span class="path2"></span></i>
                                        </button>
                                    @endif
                                @endcan

                                @can('print order')
                                    <button class="btn btn-sm btn-icon btn-light btn-active-light-primary"
                                        onclick="printOrder({{ $order->id }})"
                                        data-bs-toggle="tooltip" data-bs-title="{{ __('passwords._print') }}">
                                        <i class="ki-duotone ki-printer fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    </button>
                                @endcan

                                {{-- Detail trigger icon --}}
                                @can('view order')
                                <button class="btn btn-sm btn-icon btn-light btn-active-light-info"
                                    data-bs-toggle="offcanvas"
                                    data-bs-target="#orderDetail{{ $order->id }}"
                                    data-bs-title="{{ __('passwords.view_details') }}"
                                    data-bs-toggle-tt="tooltip">
                                    <i class="ki-duotone ki-information fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </button>
                                @endcan
                            </div>
                            @include('orders.order.complete-payment')
                        </td>
                    </tr>

                @empty
                    <tr>
                        <td colspan="8" class="text-center py-10">
                            <i class="ki-duotone ki-search-list fs-3x text-gray-400 mb-3 d-block"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            <span class="text-gray-500 fw-semibold fs-6">{{ __('passwords.no_orders') }}</span>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


{{-- ══════════════════════════════════════════════════════════════════════════
     OFFCANVAS DETAIL PANELS  (one per order, hidden until triggered)
════════════════════════════════════════════════════════════════════════════ --}}
@foreach ($orders as $order)
    @php
        $payments     = $order->orderPayments;
        $paymentCount = $payments->count();
        $statusColors = ['pending'=>'warning','confirmed'=>'primary','processing'=>'info','completed'=>'success','cancelled'=>'danger','refunded'=>'dark','draft'=>'secondary'];
        $statusColor  = $statusColors[strtolower($order->status)] ?? 'secondary';
    @endphp

    <div class="offcanvas offcanvas-end order-detail-offcanvas"
         tabindex="-1"
         id="orderDetail{{ $order->id }}"
         aria-labelledby="orderDetailLabel{{ $order->id }}"
         style="width: 520px;">

        {{-- Header --}}
        <div class="offcanvas-header border-bottom py-4 px-6"
             style="background: linear-gradient(135deg,#009ef7 0%,#0095e8 100%);">
            <div class="d-flex flex-column">
                <h5 class="offcanvas-title text-white fw-bold mb-1" id="orderDetailLabel{{ $order->id }}">
                    {{ __('passwords.order_invoice') }} · <span class="opacity-75">{{ $order->order_number }}</span>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-white bg-opacity-25 text-white fw-bold fs-8 px-2 py-1">{{ ucwords($order->type) }}</span>
                    <span class="badge bg-white bg-opacity-25 text-white fw-bold fs-8 px-2 py-1">{{ ucwords($order->source) }}</span>
                    <span class="badge bg-white bg-opacity-25 text-white fw-bold fs-8 px-2 py-1">{{ ucwords($order->status) }}</span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>

        {{-- Scrollable body --}}
        <div class="offcanvas-body p-0">
            <div class="px-6 py-5">

                {{-- ── Meta info row ──────────────────────────────────────── --}}
                <div class="row g-3 mb-5">
                    <div class="col-6">
                        <div class="bg-light rounded-2 p-3 h-100">
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{__('passwords._customer')}}</div>
                            <div class="fw-bold text-gray-800 fs-6">{{ $order->customer_name ?? __('passwords.none') }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light rounded-2 p-3 h-100">
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{__('auth._creater')}}</div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="symbol symbol-25px symbol-circle">
                                    <div class="symbol-label bg-light-primary">
                                        <span class="fs-8 fw-bold text-primary">{{ substr($order->orderCreater->name ?? 'U', 0, 1) }}</span>
                                    </div>
                                </div>
                                <span class="fw-bold text-gray-800 fs-7">{{ $order->orderCreater->name ?? __('passwords.none') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light rounded-2 p-3 h-100">
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{__('passwords.location')}}</div>
                            <div class="fw-bold text-gray-800 fs-7">{{ $order->location->name ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light rounded-2 p-3 h-100">
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{__('passwords.department')}}</div>
                            <div class="fw-bold text-gray-800 fs-7">{{ $order->department->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                {{-- ── Payments ────────────────────────────────────────────── --}}
                @if($paymentCount > 0)
                <div class="mb-5">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="ki-duotone ki-credit-card fs-4 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <span class="fw-bold text-gray-800 fs-6">{{ __('payments.payment_details') }}</span>
                        @if($paymentCount > 1)
                            <span class="badge badge-light-primary ms-1">{{ $paymentCount }}</span>
                        @endif
                    </div>

                    <div class="d-flex flex-column gap-2">
                        @foreach($payments as $payment)
                            @php $pColors = ['completed'=>'success','pending'=>'warning','failed'=>'danger']; $pColor = $pColors[$payment->status] ?? 'secondary'; @endphp
                            <div class="d-flex align-items-center justify-content-between border border-dashed border-gray-300 rounded-2 px-4 py-3">
                                {{-- Left: method info --}}
                                <div class="d-flex align-items-center gap-3">
                                    <div class="symbol symbol-35px symbol-circle">
                                        <div class="symbol-label bg-light-success">
                                            <i class="ki-duotone ki-credit-card fs-4 text-success"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-gray-800 fs-7 lh-1 mb-1">
                                            {{ $payment->paymentMethod->name ?? __('passwords.no_payment_meth') }}
                                            @if($payment->paymentMethod->type ?? false)
                                                <span class="badge badge-light-info fs-8 ms-1 px-2 py-1">{{ ucwords(str_replace('_',' ',$payment->paymentMethod->type)) }}</span>
                                            @endif
                                        </div>
                                        @if($payment->transaction_id ?? false)
                                            <div class="text-muted fs-8">{{ $payment->transaction_id }}</div>
                                        @endif
                                        @if($payment->paymentMethod->account_number ?? false)
                                            <div class="text-muted fs-8">{{ $payment->paymentMethod->account_number }}</div>
                                        @endif
                                    </div>
                                </div>
                                {{-- Right: amount + status --}}
                                <div class="text-end">
                                    <div class="fw-bold text-success fs-5 lh-1 mb-1">{{ format_currency($payment->amount) }}</div>
                                    <span class="badge badge-light-{{ $pColor }} fs-8 px-2 py-1">
                                        <span class="bullet bullet-{{ $pColor }} bullet-sm me-1"></span>{{ ucfirst($payment->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- ── Order Items ─────────────────────────────────────────── --}}
                <div class="mb-5">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="ki-duotone ki-basket fs-4 text-primary"><span class="path1"></span><span class="path2"></span></i>
                        <span class="fw-bold text-gray-800 fs-6">{{ __('passwords.order_items') }}</span>
                        <span class="badge badge-light-primary ms-1">{{ $order->orderItems->count() }}</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-row-dashed align-middle fs-8 gy-2 mb-0">
                            <thead>
                                <tr class="text-muted fw-bold text-uppercase fs-9">
                                    <th class="ps-0">{{ __('passwords.item_name') }}</th>
                                    <th class="text-center w-50px">{{ __('passwords.quantity') }}</th>
                                    <th class="text-end">{{ __('passwords.unit_price') }}</th>
                                    <th class="text-end text-danger">{{ __('passwords.discount') }}</th>
                                    <th class="text-end">{{ __('passwords.tax') }}</th>
                                    <th class="text-end pe-0 text-primary">{{ __('passwords.total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600">
                                @foreach ($order->orderItems as $item)
                                <tr>
                                    <td class="ps-0">
                                        <div class="fw-bold text-gray-800 fs-7">{{ $item->item_name }}</div>
                                        @if($item->sku)
                                            <span class="badge badge-light fs-9 px-2 py-1">{{ $item->sku }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-bold">{{ $item->quantity }}</td>
                                    <td class="text-end fs-7">{{ format_currency($item->unit_price) }}</td>
                                    <td class="text-end text-danger fs-7">-{{ format_currency($item->discount) }}</td>
                                    <td class="text-end fs-7">{{ format_currency($item->tax_amount) }}</td>
                                    <td class="text-end pe-0 fw-bold text-primary fs-7">{{ format_currency($item->total_price) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ── Financial Summary ───────────────────────────────────── --}}
                <div class="bg-light rounded-3 p-5 border border-dashed border-gray-300">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between fs-7">
                            <span class="text-muted fw-semibold">{{ __('passwords.subtotal') }}</span>
                            <span class="fw-bold text-gray-800">{{ format_currency($order->subtotal) }}</span>
                        </div>
                        <div class="d-flex justify-content-between fs-7">
                            <span class="text-muted fw-semibold">{{ __('passwords.discount') }}</span>
                            <span class="fw-bold text-danger">-{{ format_currency($order->discount_total) }}</span>
                        </div>
                        <div class="d-flex justify-content-between fs-7">
                            <span class="text-muted fw-semibold">{{ __('passwords.tax') }}</span>
                            <span class="fw-bold text-gray-800">{{ format_currency($order->tax_total) }}</span>
                        </div>
                        <div class="separator separator-dashed my-1"></div>
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold text-gray-800 fs-5">{{ __('passwords.total') }}</span>
                            <span class="fw-bold text-primary fs-3">{{ format_currency($order->total) }}</span>
                        </div>
                        @if($paymentCount > 0)
                            <div class="separator separator-dashed my-1"></div>
                            <div class="d-flex justify-content-between fs-7">
                                <span class="text-muted fw-semibold">{{ __('passwords.paid') }}</span>
                                <span class="fw-bold text-success">{{ format_currency($order->paid_amount) }}</span>
                            </div>
                            <div class="d-flex justify-content-between fs-7">
                                <span class="text-muted fw-semibold">{{ __('passwords.balance_due') }}</span>
                                <span class="fw-bold {{ $order->balance_due > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ format_currency($order->balance_due) }}
                                </span>
                            </div>
                            @if($paymentCount > 1)
                                <div class="separator separator-dashed my-1"></div>
                                <div class="text-muted fs-9 fw-semibold mb-1 text-uppercase">{{ __('payments.payment_breakdown') }}</div>
                                @foreach($payments as $payment)
                                    <div class="d-flex justify-content-between fs-8">
                                        <span class="text-gray-600">↳ {{ $payment->paymentMethod->name ?? '—' }}</span>
                                        <span class="fw-bold text-success">{{ format_currency($payment->amount) }}</span>
                                    </div>
                                @endforeach
                            @endif
                        @endif
                    </div>
                </div>

            </div>{{-- /px-6 py-5 --}}
        </div>{{-- /offcanvas-body --}}

        {{-- Footer actions --}}
        <div class="offcanvas-footer border-top px-6 py-4 d-flex gap-3">
            @can('print order')
                <button class="btn btn-primary btn-sm flex-grow-1" onclick="printOrder({{ $order->id }}); bootstrap.Offcanvas.getInstance(document.getElementById('orderDetail{{ $order->id }}')).hide();">
                    <i class="ki-duotone ki-printer fs-4 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    {{ __('passwords._print') }}
                </button>
            @endcan
            <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="offcanvas">{{ __('passwords.close_window') }}</button>
        </div>
    </div>
@endforeach


{{-- ── Printable invoices (hidden) ─────────────────────────────────────────── --}}
@foreach ($orders as $order)
    @php $payments = $order->orderPayments; @endphp
    <div id="printableOrder{{ $order->id }}" style="display:none;">
        <div class="invoice-header">
            <div class="header-content">
                <div class="invoice-title">{{ __('passwords.order_invoice') }}</div>
                <div class="invoice-subtitle">Order #{{ $order->order_number }}</div>
            </div>
        </div>
        <div class="invoice-body">
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">{{ __('passwords._customer') }}</div>
                    <div class="info-value">{{ $order->customer_name ?? __('passwords.none') }}</div>
                    <div class="info-label">{{ __('passwords.location') }}</div>
                    <div class="info-value">{{ $order->location->name ?? '—' }}</div>
                    <div class="info-label">{{ __('passwords.department') }}</div>
                    <div class="info-value">{{ $order->department->name ?? '—' }}</div>
                </div>
                <div class="info-card">
                    <div class="info-label">{{ __('auth._creater') }}</div>
                    <div class="info-value">{{ $order->orderCreater->name ?? __('passwords.none') }}</div>
                    <div class="info-label">{{ __('auth._status') }}</div>
                    <div class="info-value" style="text-transform:capitalize;">{{ $order->status }}</div>
                    <div class="info-label">{{ __('passwords._source') }}</div>
                    <div class="info-value" style="text-transform:capitalize;">{{ $order->source }}</div>
                </div>
            </div>
            @if($payments->count() > 0)
            <div class="payment-section">
                <div class="payment-header">
                    <div class="payment-icon">💳</div>
                    <div class="payment-title">{{ __('payments.payment_details') }}@if($payments->count()>1) ({{ $payments->count() }} {{ __('payments.payments') }})@endif</div>
                </div>
                @foreach($payments as $payment)
                <div class="payment-grid" style="{{ !$loop->first ? 'margin-top:12px;padding-top:12px;border-top:1px dashed #ccc;' : '' }}">
                    <div class="payment-item"><div class="payment-label">{{ __('payments.payment_method') }}</div><div class="payment-value">{{ $payment->paymentMethod->name ?? 'N/A' }}</div></div>
                    <div class="payment-item"><div class="payment-label">{{ __('payments.payment_type') }}</div><div class="payment-value"><span class="payment-type-badge">{{ ucwords(str_replace('_',' ',$payment->paymentMethod->type ?? 'N/A')) }}</span></div></div>
                    <div class="payment-item"><div class="payment-label">{{ __('payments.amount') }}</div><div class="payment-value text-success">{{ format_currency($payment->amount) }}</div></div>
                    <div class="payment-item"><div class="payment-label">{{ __('payments.transaction_id') }}</div><div class="payment-value" style="font-size:11px;word-break:break-all;">{{ $payment->transaction_id ?? 'N/A' }}</div></div>
                    <div class="payment-item"><div class="payment-label">{{ __('auth._status') }}</div><div class="payment-value text-success" style="text-transform:capitalize;">{{ $payment->status }}</div></div>
                    <div class="payment-item"><div class="payment-label">{{ __('auth.processed_at') }}</div><div class="payment-value" style="font-size:12px;">{{ $payment->formatted_processed_at ?? '—' }}</div></div>
                </div>
                @endforeach
            </div>
            @endif
            <div class="items-section">
                <div class="section-title">{{ __('passwords.order_items') }}</div>
                <table><thead><tr>
                    <th>{{ __('passwords.item_name') }}</th><th>{{ __('passwords.sku') }}</th>
                    <th class="text-center">{{ __('passwords.quantity') }}</th>
                    <th class="text-right">{{ __('passwords.unit_price') }}</th>
                    <th class="text-right">{{ __('passwords.discount') }}</th>
                    <th class="text-right">{{ __('passwords.tax') }}</th>
                    <th class="text-right">{{ __('passwords.total') }}</th>
                </tr></thead><tbody>
                @foreach ($order->orderItems as $item)
                <tr>
                    <td><span class="item-name">{{ $item->item_name }}</span></td>
                    <td><span class="item-sku">{{ $item->sku }}</span></td>
                    <td class="text-center"><strong>{{ $item->quantity }}</strong></td>
                    <td class="text-right">{{ format_currency($item->unit_price) }}</td>
                    <td class="text-right text-danger">-{{ format_currency($item->discount) }}</td>
                    <td class="text-right">{{ format_currency($item->tax_amount) }}</td>
                    <td class="text-right"><strong>{{ format_currency($item->total_price) }}</strong></td>
                </tr>
                @endforeach
                </tbody></table>
            </div>
            <div class="summary-section">
                <div class="summary-box">
                    <div class="summary-row"><span class="summary-label">{{ __('passwords.subtotal') }}</span><span class="summary-value">{{ format_currency($order->subtotal) }}</span></div>
                    <div class="summary-row"><span class="summary-label">{{ __('passwords.discount') }}</span><span class="summary-value text-danger">-{{ format_currency($order->discount_total) }}</span></div>
                    <div class="summary-row"><span class="summary-label">{{ __('passwords.tax') }}</span><span class="summary-value">{{ format_currency($order->tax_total) }}</span></div>
                    <div class="summary-row total"><span class="summary-label">{{ __('passwords.total') }}</span><span class="summary-value">{{ format_currency($order->total) }}</span></div>
                    @if($payments->count() > 0)
                        <div class="summary-row"><span class="summary-label">{{ __('passwords.paid') }}</span><span class="summary-value text-success">{{ format_currency($order->paid_amount) }}</span></div>
                        <div class="summary-row"><span class="summary-label">{{ __('passwords.balance_due') }}</span><span class="summary-value text-danger">{{ format_currency($order->balance_due) }}</span></div>
                        @if($payments->count() > 1)
                            @foreach($payments as $payment)
                            <div class="summary-row" style="font-size:12px;">
                                <span class="summary-label" style="color:#7e8299;">↳ {{ $payment->paymentMethod->name ?? '—' }}</span>
                                <span class="summary-value text-success" style="font-size:13px;">{{ format_currency($payment->amount) }}</span>
                            </div>
                            @endforeach
                        @endif
                    @endif
                </div>
            </div>
        </div>
        <div class="invoice-footer">
            <div class="footer-thank-you">{{ __('passwords.thank_you_business') }}</div>
            <div>{{ __('passwords.print_on') }}: {{ now()->format('M d, Y H:i') }}</div>
        </div>
    </div>
@endforeach


<style>
    /* Offcanvas footer */
    .offcanvas-footer { background: #fff; }

    /* Compact table rows inside offcanvas */
    .order-detail-offcanvas .table td,
    .order-detail-offcanvas .table th { padding-top: 0.4rem; padding-bottom: 0.4rem; }

    /* Main table */
    .order-row { transition: background 0.15s ease; }
    .order-row:hover { background-color: #f9f9f9; }
    .separator { display: block; height: 0; border-top: 1px dashed #e4e6ef; }
    .separator-dashed { border-top-style: dashed; }

    @keyframes slideDown { from{opacity:0;transform:translateY(-6px);}to{opacity:1;transform:translateY(0);} }

    @media (max-width: 768px) {
        .order-detail-offcanvas { width: 100% !important; }
        .btn span.fw-bold { display: none; }
    }
    @media print { .btn,.form-check{display:none!important;} }
</style>


<script>
    function printOrder(orderId) {
        const printElement = document.getElementById('printableOrder' + orderId);
        if (!printElement) { toastr.error('Print content not found.'); return; }
        const printWindow = window.open('', '_blank', 'width=1000,height=800,scrollbars=yes');
        if (!printWindow) { toastr.warning('Please allow popups to print this order.'); return; }
        printWindow.document.write(`<!DOCTYPE html><html lang="en"><head>
            <title>Invoice - Order #${orderId}</title><meta charset="UTF-8">
            <style>
                :root{--kt-primary:#009ef7;--kt-primary-light:#f1faff;--kt-success:#50cd89;--kt-success-light:#e8fff3;--kt-danger:#f1416c;--kt-gray-100:#f9f9f9;--kt-gray-200:#f1f1f2;--kt-gray-300:#e4e6ef;--kt-gray-600:#7e8299;--kt-gray-700:#5e6278;--kt-gray-800:#3f4254;--kt-dark:#181c32;}
                *{margin:0;padding:0;box-sizing:border-box;}
                body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;line-height:1.6;color:var(--kt-dark);background:#fff;padding:30px 20px;}
                .invoice-container{max-width:900px;margin:0 auto;background:#fff;box-shadow:0 0 40px rgba(0,0,0,.08);border-radius:12px;overflow:hidden;}
                .invoice-header{background:linear-gradient(135deg,var(--kt-primary) 0%,#0095e8 100%);color:#fff;padding:40px;position:relative;overflow:hidden;}
                .invoice-header::before{content:'';position:absolute;top:-50%;right:-10%;width:400px;height:400px;background:rgba(255,255,255,.1);border-radius:50%;}
                .header-content{position:relative;z-index:1;}
                .invoice-title{font-size:32px;font-weight:700;margin-bottom:8px;}
                .invoice-subtitle{font-size:18px;font-weight:500;opacity:.95;}
                .invoice-body{padding:40px;}
                .info-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:30px;margin-bottom:35px;padding-bottom:30px;border-bottom:2px dashed var(--kt-gray-300);}
                .info-card{background:var(--kt-gray-100);border-radius:8px;padding:20px;border-left:4px solid var(--kt-primary);}
                .info-label{color:var(--kt-gray-600);font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;}
                .info-value{color:var(--kt-dark);font-size:15px;font-weight:600;margin-bottom:12px;}
                .payment-section{background:linear-gradient(135deg,var(--kt-success-light) 0%,#d4f8e8 100%);border:1px solid rgba(80,205,137,.3);border-radius:10px;padding:25px;margin-bottom:35px;}
                .payment-header{display:flex;align-items:center;margin-bottom:20px;padding-bottom:15px;border-bottom:1px dashed rgba(80,205,137,.3);}
                .payment-icon{width:40px;height:40px;background:var(--kt-success);border-radius:8px;display:flex;align-items:center;justify-content:center;margin-right:12px;color:#fff;font-size:18px;}
                .payment-title{color:var(--kt-dark);font-size:18px;font-weight:700;}
                .payment-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:15px;}
                .payment-item{background:#fff;border-radius:6px;padding:12px;}
                .payment-label{color:var(--kt-gray-600);font-size:11px;font-weight:600;text-transform:uppercase;margin-bottom:4px;}
                .payment-value{color:var(--kt-dark);font-size:14px;font-weight:700;}
                .payment-type-badge{display:inline-block;background:var(--kt-success);color:#fff;padding:4px 10px;border-radius:4px;font-size:11px;font-weight:600;text-transform:uppercase;}
                .items-section{margin-bottom:35px;}
                .section-title{color:var(--kt-dark);font-size:18px;font-weight:700;margin-bottom:20px;display:flex;align-items:center;}
                .section-title::before{content:'';width:4px;height:24px;background:var(--kt-primary);border-radius:2px;margin-right:12px;}
                table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.04);}
                thead{background:var(--kt-gray-100);}
                th{padding:14px 12px;text-align:left;color:var(--kt-gray-700);font-weight:700;font-size:11px;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid var(--kt-gray-300);}
                td{padding:14px 12px;color:var(--kt-dark);font-size:13px;border-bottom:1px solid var(--kt-gray-200);}
                tbody tr:last-child td{border-bottom:none;}
                .text-center{text-align:center;}.text-right{text-align:right;}
                .item-name{font-weight:600;}.item-sku{background:var(--kt-primary-light);color:var(--kt-primary);padding:3px 8px;border-radius:4px;font-size:11px;font-weight:600;}
                .summary-section{display:flex;justify-content:flex-end;margin-top:30px;}
                .summary-box{width:380px;background:var(--kt-gray-100);border-radius:10px;padding:25px;border:1px solid var(--kt-gray-300);}
                .summary-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;}
                .summary-row.total{border-top:2px dashed var(--kt-gray-300);margin-top:8px;padding-top:14px;}
                .summary-label{color:var(--kt-gray-700);font-size:13px;font-weight:500;}.summary-value{font-weight:700;font-size:14px;color:var(--kt-dark);}
                .total .summary-label{font-size:16px;font-weight:700;}.total .summary-value{font-size:24px;color:var(--kt-primary);}
                .text-danger{color:var(--kt-danger)!important;}.text-success{color:#50cd89!important;}
                .invoice-footer{background:var(--kt-gray-100);padding:25px 40px;margin-top:40px;text-align:center;color:var(--kt-gray-600);font-size:13px;}
                .footer-thank-you{font-size:16px;font-weight:600;color:var(--kt-dark);margin-bottom:8px;}
                .print-actions{text-align:center;margin:30px 0;}
                .btn{display:inline-block;padding:12px 28px;border:none;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;margin:0 6px;}
                .btn-primary{background:var(--kt-primary);color:#fff;}.btn-secondary{background:var(--kt-gray-200);color:var(--kt-gray-700);}
                @media print{body{padding:0;}.invoice-container{box-shadow:none;max-width:100%;}.no-print{display:none!important;}.invoice-header,.payment-section{print-color-adjust:exact;-webkit-print-color-adjust:exact;}}
                @page{size:A4;margin:15mm;}
            </style>
        </head><body>
            <div class="invoice-container">${printElement.innerHTML}</div>
            <div class="print-actions no-print">
                <button class="btn btn-primary" onclick="window.print()">🖨️ {{ __('passwords.print_invoice') }}</button>
                <button class="btn btn-secondary" onclick="window.close()">✕ {{ __('passwords.close_window') }}</button>
            </div>
            <script>window.onload=function(){window.focus();};<\/script>
        </body></html>`);
        printWindow.document.close();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    });
</script>
@endcan