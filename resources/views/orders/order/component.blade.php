<div class="card-body py-4" id="ordersIndexTable">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('passwords.order_number')}}</th>
                    <th class="min-w-125px">{{__('passwords._customer')}}</th>
                    <th class="min-w-125px">{{__('passwords._type')}}</th>
                    <th class="min-w-125px">{{__('passwords._status')}}</th>
                    <th class="min-w-125px">{{__('passwords._source')}}</th>
                    <th class="min-w-125px">{{__('passwords._amount')}}</th>
                    <th class="min-w-125px">{{__('payments.payment_method')}}</th>
                    <th class="min-w-125px">{{__('auth._creater')}}</th>
                    <th class="min-w-100px">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($orders) && $orders->count() > 0)
                    @foreach ($orders as $order)
                        <tr data-role="{{ strtolower($order->id) }}" class="order-row">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <a href="javascript:void(0);" 
                                   class="toggle-items d-flex align-items-center text-gray-800 text-hover-primary fw-bold" 
                                   data-bs-toggle="collapse" 
                                   data-bs-target="#orderItems{{ $order->id }}"
                                   aria-expanded="false"
                                   aria-controls="orderItems{{ $order->id }}">
                                    <span class="toggle-icon svg-icon svg-icon-3 me-3 rotate-180">
                                        <i class="ki-duotone ki-down fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </span>
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td>
                                <span class="text-gray-800 fw-bold">{{ $order->customer_name ?? __('passwords.none') }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light-primary fw-bold fs-8 px-3 py-2">{{ ucwords($order->type) }}</span>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'primary',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        'refunded' => 'dark'
                                    ];
                                    $statusColor = $statusColors[strtolower($order->status)] ?? 'secondary';
                                @endphp
                                <span class="badge badge-light-{{ $statusColor }} fw-bold fs-8 px-3 py-2">
                                    <span class="bullet bullet-{{ $statusColor }} bullet-sm me-2"></span>
                                    {{ ucwords($order->status) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-gray-800 fw-bold">{{ ucwords($order->source) }}</span>
                            </td>
                            <td>
                                <span class="text-gray-800 fw-bold">{{ currencySymbol() }} {{ $order->total }}</span>
                            </td>
                            <td>
                                @if($order->payments && $order->payments->paymentMethod)
                                    <div class="d-flex flex-column">
                                        <span class="text-gray-800 fw-bold fs-7 mb-1">{{ $order->payments->paymentMethod->name }}</span>
                                        @if($order->payments->paymentMethod->account_number)
                                            <small class="text-muted fs-8">{{ $order->payments->paymentMethod->account_number }}</small>
                                        @endif
                                        @if($order->payments->paymentMethod->type)
                                            <small class="badge badge-light-info fs-8 mt-1">{{ $order->payments->paymentMethod->type }}</small>
                                        @endif
                                    </div>
                                @elseif($order->payments)
                                    <span class="text-muted fs-8">No payment method</span>
                                @else
                                    <span class="text-muted fs-8">No payment</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-35px symbol-circle me-3">
                                        <div class="symbol-label bg-light-primary">
                                            <span class="fs-7 fw-bold text-primary">{{ substr($order->orderCreater->name ?? 'U', 0, 1) }}</span>
                                        </div>
                                    </div>
                                    <div class="text-gray-800 fw-bold fs-7">{{ $order->orderCreater->name ?? __('passwords.none') }}</div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end gap-2">
                                    @if ($order->status == 'confirmed' && $order->source == 'pos')
                                        <button 
                                            class="btn btn-sm btn-light-primary btn-active-light-primary d-flex align-items-center px-4 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#completePayment{{$order->id}}">
                                            <i class="ki-duotone ki-dollar fs-4 me-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                            <span class="fw-bold">{{__('pagination._complete')}}</span>
                                        </button>
                                    @endif
                                    @if ($order->status == 'confirmed')
                                        <button class="btn btn-sm btn-danger" onclick="cancelPOSOrder({{ $order->id }})">
                                            {{ __('passwords.cancel') }}
                                        </button>
                                    @endif
                                    <button 
                                        class="btn btn-sm btn-icon btn-light btn-active-light-primary"
                                        onclick="printOrder({{ $order->id }})"
                                        data-bs-toggle="tooltip"
                                        data-bs-title="{{ __('passwords._print') }}">
                                        <i class="ki-duotone ki-printer fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </button>
                                </div>
                                @include('orders.order.complete-payment')
                            </td>
                        </tr>

                        <!-- Expanded row -->
                        <tr class="collapse" id="orderItems{{ $order->id }}" data-bs-parent="#ordersIndexTable">
                            <td colspan="10" class="p-0 border-0">
                                <div class="order-details-container bg-light">
                                    <div class="order-details-content p-6 border border-dashed border-gray-300 border-top-0">
                                        
                                        <!-- Payment Information (if exists) -->
                                        @if($order->payments)
                                        <div class="card card-flush card-dashed mb-6">
                                            <div class="card-header border-0">
                                                <div class="card-title">
                                                    <h3 class="fw-bold text-gray-800">
                                                        <i class="ki-duotone ki-credit-card fs-2 me-2 text-primary">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                            <span class="path3"></span>
                                                        </i>
                                                        {{ __('payments.payment_details') }}
                                                    </h3>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="row g-5">
                                                    <div class="col-md-6">
                                                        <div class="d-flex flex-column gap-4">
                                                            <div>
                                                                <div class="text-muted fs-7 fw-semibold mb-1">{{ __('payments.payment_method') }}</div>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="symbol symbol-40px symbol-circle me-3">
                                                                        <div class="symbol-label bg-light-success">
                                                                            <i class="ki-duotone ki-credit-card fs-2 text-success">
                                                                                <span class="path1"></span>
                                                                                <span class="path2"></span>
                                                                                <span class="path3"></span>
                                                                            </i>
                                                                        </div>
                                                                    </div>
                                                                    <div>
                                                                        <div class="fw-bold text-gray-800 fs-6">{{ $order->payments->paymentMethod->name ?? 'Not specified' }}</div>
                                                                        @if($order->payments->paymentMethod->account_number ?? false)
                                                                            <div class="text-muted fs-7">{{ $order->payments->paymentMethod->account_number }}</div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="text-muted fs-7 fw-semibold mb-1">{{ __('payments.transaction_id') }}</div>
                                                                <div class="fw-bold text-gray-800 fs-6">{{ $order->payments->transaction_id ?? 'N/A' }}</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex flex-column gap-4">
                                                            <div>
                                                                <div class="text-muted fs-7 fw-semibold mb-1">{{ __('payments.amount') }}</div>
                                                                <div class="fw-bold text-primary fs-2">{{ currencySymbol() }} {{ $order->payments->amount }}</div>
                                                            </div>
                                                            <div class="row g-3">
                                                                <div class="col-6">
                                                                    <div class="text-muted fs-7 fw-semibold mb-1">{{ __('auth._status') }}</div>
                                                                    <span class="badge badge-light-{{ $order->payments->status_color }} fw-bold px-3 py-2">
                                                                        <span class="bullet bullet-{{ $order->payments->status_color }} bullet-sm me-2"></span>
                                                                        {{ ucfirst($order->payments->status) }}
                                                                    </span>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="text-muted fs-7 fw-semibold mb-1">{{ __('auth.processed_at') }}</div>
                                                                    <div class="fw-bold text-gray-800 fs-7">{{ $order->payments->formatted_processed_at }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Items table -->
                                        <div class="card card-flush card-dashed mb-6">
                                            <div class="card-header border-0">
                                                <div class="card-title">
                                                    <h3 class="fw-bold text-gray-800">
                                                        <i class="ki-duotone ki-basket fs-2 me-2 text-primary">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        {{ __('passwords.order_items') }}
                                                    </h3>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table table-row-dashed align-middle fs-6 gy-5">
                                                        <thead>
                                                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                                                <th class="ps-0">{{ __('passwords.item_name') }}</th>
                                                                <th>{{ __('passwords.sku') }}</th>
                                                                <th class="text-center">{{ __('passwords.quantity') }}</th>
                                                                <th class="text-end">{{ __('passwords.unit_price') }}</th>
                                                                <th class="text-end">{{ __('passwords.discount') }}</th>
                                                                <th class="text-end">{{ __('passwords.tax') }}</th>
                                                                <th class="text-end pe-0">{{ __('passwords.total') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="fw-semibold text-gray-600">
                                                            @foreach ($order->orderItems as $item)
                                                                <tr>
                                                                    <td class="ps-0">
                                                                        <div class="d-flex align-items-center">
                                                                            <div class="symbol symbol-45px me-3">
                                                                                <div class="symbol-label bg-light">
                                                                                    <i class="ki-duotone ki-abstract-41 fs-2 text-gray-600">
                                                                                        <span class="path1"></span>
                                                                                        <span class="path2"></span>
                                                                                    </i>
                                                                                </div>
                                                                            </div>
                                                                            <div>
                                                                                <div class="fw-bold text-gray-800">{{ $item->item_name }}</div>
                                                                            </div>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge badge-light fw-bold">{{ $item->sku }}</span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="fw-bold">{{ $item->quantity }}</span>
                                                                    </td>
                                                                    <td class="text-end">{{ displayFormatedCurrency($item->unit_price) }} {{ currencySymbol() }}</td>
                                                                    <td class="text-end text-danger">-{{ displayFormatedCurrency($item->discount) }} {{ currencySymbol() }}</td>
                                                                    <td class="text-end">{{ displayFormatedCurrency($item->tax_amount) }} {{ currencySymbol() }}</td>
                                                                    <td class="text-end pe-0 fw-bold text-primary">{{ displayFormatedCurrency($item->total_price) }} {{ currencySymbol() }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Financial summary -->
                                        <div class="card card-flush card-dashed">
                                            <div class="card-header border-0">
                                                <div class="card-title">
                                                    <h3 class="fw-bold text-gray-800">
                                                        <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                        {{ __('passwords.summary') }}
                                                    </h3>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="row">
                                                    <div class="col-md-6 col-lg-4 offset-md-6 offset-lg-8">
                                                        <div class="bg-light rounded p-4">
                                                            <div class="d-flex flex-column gap-3">
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-muted fs-7">{{ __('passwords.subtotal') }}</span>
                                                                    <span class="fw-bold fs-6">{{ displayFormatedCurrency($order->subtotal) }} {{ currencySymbol() }}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-muted fs-7">{{ __('passwords.discount') }}</span>
                                                                    <span class="fw-bold fs-6 text-danger">-{{ displayFormatedCurrency($order->discount_total) }} {{ currencySymbol() }}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-muted fs-7">{{ __('passwords.tax') }}</span>
                                                                    <span class="fw-bold fs-6">{{ displayFormatedCurrency($order->tax_total) }} {{ currencySymbol() }}</span>
                                                                </div>
                                                                <div class="separator separator-dashed my-3"></div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="fw-bold text-gray-800 fs-5">{{ __('passwords.total') }}</span>
                                                                    <span class="fw-bold text-primary fs-3">{{ displayFormatedCurrency($order->total) }} {{ currencySymbol() }}</span>
                                                                </div>
                                                                @if($order->payments)
                                                                <div class="separator separator-dashed my-3"></div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-muted fs-7">{{ __('passwords.paid') }}</span>
                                                                    <span class="fw-bold fs-6 text-success">{{ displayFormatedCurrency($order->paid_amount) }} {{ currencySymbol() }}</span>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span class="text-muted fs-7">{{ __('passwords.balance_due') }}</span>
                                                                    <span class="fw-bold fs-6 text-danger">{{ displayFormatedCurrency($order->balance_due) }} {{ currencySymbol() }}</span>
                                                                </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>

{{-- Information to print --}}
@foreach ($orders as $order)
    <div id="printableOrder{{ $order->id }}" style="display:none;">
        <!-- Header -->
        <div class="invoice-header">
            <div class="header-content">
                <div class="invoice-title">{{ __('passwords.order_invoice') }}</div>
                <div class="invoice-subtitle">Order #{{ $order->order_number }}</div>
            </div>
        </div>
        
        <div class="invoice-body">
            <!-- Info Grid -->
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">{{ __('passwords._customer') }}</div>
                    <div class="info-value">{{ $order->customer_name ?? __('passwords.none') }}</div>
                    
                    <div class="info-label">{{ __('passwords.location') }}</div>
                    <div class="info-value">{{ $order->location->name ?? __('passwords.none') }}</div>
                    
                    <div class="info-label">{{ __('passwords.department') }}</div>
                    <div class="info-value">{{ $order->department->name ?? __('passwords.none') }}</div>
                </div>
                
                <div class="info-card">
                    <div class="info-label">{{ __('auth._creater') }}</div>
                    <div class="info-value">{{ $order->orderCreater->name ?? __('passwords.none') }}</div>
                    
                    <div class="info-label">{{ __('auth._status') }}</div>
                    <div class="info-value" style="text-transform: capitalize;">{{ $order->status }}</div>
                    
                    <div class="info-label">{{ __('passwords._source') }}</div>
                    <div class="info-value" style="text-transform: capitalize;">{{ $order->source }}</div>
                </div>
            </div>

            <!-- Payment Information -->
            @if($order->payments)
            <div class="payment-section">
                <div class="payment-header">
                    <div class="payment-icon">💳</div>
                    <div class="payment-title">{{ __('payments.payment_details') }}</div>
                </div>
                
                <div class="payment-grid">
                    <div class="payment-item">
                        <div class="payment-label">{{ __('payments.payment_method') }}</div>
                        <div class="payment-value">{{ $order->payments->paymentMethod->name ?? 'Not specified' }}</div>
                        @if($order->payments->paymentMethod->account_number ?? false)
                            <div style="color: var(--kt-gray-500); font-size: 11px; margin-top: 2px;">
                                {{ $order->payments->paymentMethod->account_number }}
                            </div>
                        @endif
                    </div>
                    
                    <div class="payment-item">
                        <div class="payment-label">{{ __('payments.payment_type') }}</div>
                        <div class="payment-value">
                            <span class="payment-type-badge">{{ ucwords(str_replace('_', ' ', $order->payments->paymentMethod->type ?? 'N/A')) }}</span>
                        </div>
                    </div>
                    
                    <div class="payment-item">
                        <div class="payment-label">{{ __('pagination._currency') }}</div>
                        <div class="payment-value">{{ getMailOptions('currency') }} - {{ currencySymbol() }}</div>
                    </div>
                    
                    <div class="payment-item">
                        <div class="payment-label">{{ __('payments.amount') }}</div>
                        <div class="payment-value text-success">{{ currencySymbol() }} {{ number_format($order->payments->amount, 2) }}</div>
                    </div>
                    
                    <div class="payment-item">
                        <div class="payment-label">{{ __('payments.transaction_id') }}</div>
                        <div class="payment-value" style="font-size: 11px; word-break: break-all;">{{ $order->payments->transaction_id ?? 'N/A' }}</div>
                    </div>
                    
                    <div class="payment-item">
                        <div class="payment-label">{{ __('auth._status') }}</div>
                        <div class="payment-value text-success" style="text-transform: capitalize;">{{ $order->payments->status }}</div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Items Table -->
            <div class="items-section">
                <div class="section-title">{{ __('passwords.order_items') }}</div>
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('passwords.item_name') }}</th>
                            <th>{{ __('passwords.sku') }}</th>
                            <th class="text-center">{{ __('passwords.quantity') }}</th>
                            <th class="text-right">{{ __('passwords.unit_price') }}</th>
                            <th class="text-right">{{ __('passwords.discount') }}</th>
                            <th class="text-right">{{ __('passwords.tax') }}</th>
                            <th class="text-right">{{ __('passwords.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->orderItems as $item)
                            <tr>
                                <td><span class="item-name">{{ $item->item_name }}</span></td>
                                <td><span class="item-sku">{{ $item->sku }}</span></td>
                                <td class="text-center"><strong>{{ $item->quantity }}</strong></td>
                                <td class="text-right">{{ currencySymbol() }} {{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right text-danger">-{{ currencySymbol() }} {{ number_format($item->discount, 2) }}</td>
                                <td class="text-right">{{ currencySymbol() }} {{ number_format($item->tax_amount, 2) }}</td>
                                <td class="text-right"><strong>{{ currencySymbol() }} {{ number_format($item->total_price, 2) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="summary-section">
                <div class="summary-box">
                    <div class="summary-row">
                        <span class="summary-label">{{ __('passwords.subtotal') }}</span>
                        <span class="summary-value">{{ currencySymbol() }} {{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">{{ __('passwords.discount') }}</span>
                        <span class="summary-value text-danger">-{{ currencySymbol() }} {{ number_format($order->discount_total, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">{{ __('passwords.tax') }}</span>
                        <span class="summary-value">{{ currencySymbol() }} {{ number_format($order->tax_total, 2) }}</span>
                    </div>
                    <div class="summary-row total">
                        <span class="summary-label">{{ __('passwords.total') }}</span>
                        <span class="summary-value">{{ currencySymbol() }} {{ number_format($order->total, 2) }}</span>
                    </div>
                    @if($order->payments)
                    <div class="summary-row">
                        <span class="summary-label">{{ __('passwords.paid') }}</span>
                        <span class="summary-value text-success">{{ currencySymbol() }} {{ number_format($order->paid_amount, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">{{ __('passwords.balance_due') }}</span>
                        <span class="summary-value text-danger">{{ currencySymbol() }} {{ number_format($order->balance_due, 2) }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="invoice-footer">
            <div class="footer-thank-you">Thank you for your business!</div>
            <div>Printed on: {{ now()->format('M d, Y H:i') }}</div>
        </div>
    </div>
@endforeach

<style>
    .account-card {
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .account-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .account-card.border-primary {
        border-width: 2px !important;
    }

    .cursor-pointer {
        cursor: pointer;
    }

    .payment-type-btn {
        transition: all 0.3s ease;
    }

    .payment-type-btn:hover .btn-check:not(:checked) {
        border-color: #009ef7;
    }

    .payment-type-btn.active {
        border-color: #009ef7 !important;
        background-color: #f1faff !important;
    }
</style>
<style>
    /* ============================================
    METRONIC UI CONSISTENT STYLING
    ============================================ */

    /* Global Variables */
    :root {
        --kt-primary: #009ef7;
        --kt-primary-light: #f1faff;
        --kt-success: #50cd89;
        --kt-success-light: #e8fff3;
        --kt-info: #00b2ff;
        --kt-info-light: #e8f7ff;
        --kt-warning: #ffc700;
        --kt-warning-light: #fff8dd;
        --kt-danger: #f1416c;
        --kt-danger-light: #fff5f8;
        --kt-dark: #181c32;
        --kt-light: #f8f9fa;
        --kt-gray-100: #f5f8fa;
        --kt-gray-200: #eff2f5;
        --kt-gray-300: #e4e6ef;
        --kt-gray-400: #b5b5c3;
        --kt-gray-500: #a1a5b7;
        --kt-gray-600: #7e8299;
        --kt-gray-700: #5e6278;
        --kt-gray-800: #3f4254;
        --kt-gray-900: #181c32;
    }

    /* Order Row Styling */
    .order-row {
        transition: all 0.2s ease;
        border-bottom: 1px solid var(--kt-gray-200);
    }

    .order-row:hover {
        background-color: var(--kt-gray-100);
    }

    /* Toggle Button */
    .toggle-items {
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .toggle-items:hover {
        color: var(--kt-primary) !important;
    }

    .toggle-icon {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .toggle-items[aria-expanded="true"] .toggle-icon {
        transform: rotate(90deg);
    }

    .toggle-items[aria-expanded="true"] .toggle-icon i {
        color: var(--kt-primary);
    }

    /* Expanded Row Container */
    .order-details-container {
        background-color: var(--kt-gray-100);
    }

    .order-details-content {
        animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Card Styling */
    .card-flush {
        border: 0;
        background-color: transparent;
    }

    .card-dashed {
        border: 1px dashed var(--kt-gray-300) !important;
        border-radius: 0.65rem;
    }

    .card-dashed .card-header {
        border-bottom: 1px dashed var(--kt-gray-300);
        background-color: transparent;
    }

    /* Badge Styling - Consistent with Metronic */
    .badge {
        border-radius: 0.425rem;
        padding: 0.35rem 0.75rem;
        font-weight: 600;
        line-height: 1.5;
    }

    .badge-light-primary {
        background-color: var(--kt-primary-light);
        color: var(--kt-primary);
        border: 1px solid rgba(0, 158, 247, 0.2);
    }

    .badge-light-success {
        background-color: var(--kt-success-light);
        color: var(--kt-success);
        border: 1px solid rgba(80, 205, 137, 0.2);
    }

    .badge-light-info {
        background-color: var(--kt-info-light);
        color: var(--kt-info);
        border: 1px solid rgba(0, 178, 255, 0.2);
    }

    .badge-light-warning {
        background-color: var(--kt-warning-light);
        color: var(--kt-warning);
        border: 1px solid rgba(255, 199, 0, 0.2);
    }

    .badge-light-danger {
        background-color: var(--kt-danger-light);
        color: var(--kt-danger);
        border: 1px solid rgba(241, 65, 108, 0.2);
    }

    .badge-light-dark {
        background-color: #f5f8fa;
        color: var(--kt-dark);
        border: 1px solid rgba(24, 28, 50, 0.1);
    }

    /* Bullet Styling */
    .bullet {
        display: inline-block;
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 50%;
    }

    .bullet-primary { background-color: var(--kt-primary); }
    .bullet-success { background-color: var(--kt-success); }
    .bullet-info { background-color: var(--kt-info); }
    .bullet-warning { background-color: var(--kt-warning); }
    .bullet-danger { background-color: var(--kt-danger); }
    .bullet-dark { background-color: var(--kt-dark); }

    /* Symbol Styling */
    .symbol {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .symbol-circle .symbol-label {
        border-radius: 50%;
    }

    .symbol-label {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Button Styling */
    .btn-light-primary {
        background-color: var(--kt-primary-light);
        border-color: var(--kt-primary-light);
        color: var(--kt-primary);
    }

    .btn-light-primary:hover,
    .btn-active-light-primary:hover {
        background-color: var(--kt-primary);
        border-color: var(--kt-primary);
        color: #fff;
    }

    .btn-light {
        border-color: var(--kt-gray-300);
        background-color: #fff;
        color: var(--kt-gray-700);
    }

    .btn-light:hover {
        border-color: var(--kt-primary);
        background-color: var(--kt-primary-light);
        color: var(--kt-primary);
    }

    /* Table Enhancements */
    #kt_table_users th {
        background-color: var(--kt-gray-100);
        position: sticky;
        top: 0;
        z-index: 10;
    }

    #kt_table_users tbody tr:last-child {
        border-bottom: 1px solid var(--kt-gray-200);
    }

    /* Separator */
    .separator {
        display: block;
        height: 0;
        overflow: visible;
        border-top: 1px dashed var(--kt-gray-300);
    }

    .separator-dashed {
        border-top-style: dashed;
    }

    /* Border Colors */
    .border-gray-300 {
        border-color: var(--kt-gray-300) !important;
    }

    .border-dashed {
        border-style: dashed !important;
    }

    /* Text Colors */
    .text-gray-800 {
        color: var(--kt-gray-800) !important;
    }

    .text-gray-600 {
        color: var(--kt-gray-600) !important;
    }

    .text-muted {
        color: var(--kt-gray-500) !important;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .order-details-content {
            padding: 1rem !important;
        }
        
        .table-responsive {
            margin: 0 -0.75rem;
            padding: 0 0.75rem;
        }
        
        .btn span:not(.svg-icon) {
            display: none;
        }
        
        .btn {
            padding: 0.375rem 0.75rem;
        }
    }

    /* Hover Effects */
    .text-hover-primary:hover {
        color: var(--kt-primary) !important;
    }

    /* Print Styles */
    @media print {
        .order-details-container,
        .toggle-items,
        .btn,
        .badge-light-primary {
            display: none !important;
        }
        
        .card-dashed {
            border: 1px solid #ddd !important;
        }
    }

    /* Animation Classes */
    .rotate-180 {
        transition: transform 0.3s ease;
    }

    .collapsed .rotate-180 {
        transform: rotate(0deg);
    }

    :not(.collapsed) .rotate-180 {
        transform: rotate(180deg);
    }

    /* Custom Scrollbar for Table */
    .table-responsive::-webkit-scrollbar {
        height: 6px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: var(--kt-gray-100);
        border-radius: 3px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: var(--kt-gray-300);
        border-radius: 3px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: var(--kt-gray-400);
    }
</style>


<script>

    function printOrder(orderId) {
        console.log('Printing order:', orderId);
        
        const printElement = document.getElementById('printableOrder' + orderId);
        
        if (!printElement) {
            console.error('Print element not found for order ID:', orderId);
            toastr.error('Print content not found for this order.');
            return;
        }
        
        const printContent = printElement.innerHTML;
        const printWindow = window.open('', '_blank', 'width=1000,height=800,scrollbars=yes');
        
        if (!printWindow) {
            toastr.warning('Please allow popups to print this order.');
            return;
        }
        
        // Create an enhanced print document with Metronic-inspired styling
        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <title>Invoice - Order #${orderId}</title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    /* Metronic Color Palette */
                    :root {
                        --kt-primary: #009ef7;
                        --kt-primary-light: #f1faff;
                        --kt-success: #50cd89;
                        --kt-success-light: #e8fff3;
                        --kt-danger: #f1416c;
                        --kt-danger-light: #fff5f8;
                        --kt-warning: #ffc700;
                        --kt-info: #7239ea;
                        --kt-dark: #181c32;
                        --kt-gray-100: #f9f9f9;
                        --kt-gray-200: #f1f1f2;
                        --kt-gray-300: #e4e6ef;
                        --kt-gray-400: #b5b5c3;
                        --kt-gray-500: #a1a5b7;
                        --kt-gray-600: #7e8299;
                        --kt-gray-700: #5e6278;
                        --kt-gray-800: #3f4254;
                    }
                    
                    * {
                        margin: 0;
                        padding: 0;
                        box-sizing: border-box;
                    }
                    
                    body {
                        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                        line-height: 1.6;
                        color: var(--kt-dark);
                        background: #ffffff;
                        padding: 30px 20px;
                    }
                    
                    .invoice-container {
                        max-width: 900px;
                        margin: 0 auto;
                        background: white;
                        box-shadow: 0 0 40px rgba(0, 0, 0, 0.08);
                        border-radius: 12px;
                        overflow: hidden;
                    }
                    
                    /* Header Section */
                    .invoice-header {
                        background: linear-gradient(135deg, var(--kt-primary) 0%, #0095e8 100%);
                        color: white;
                        padding: 40px;
                        position: relative;
                        overflow: hidden;
                    }
                    
                    .invoice-header::before {
                        content: '';
                        position: absolute;
                        top: -50%;
                        right: -10%;
                        width: 400px;
                        height: 400px;
                        background: rgba(255, 255, 255, 0.1);
                        border-radius: 50%;
                    }
                    
                    .header-content {
                        position: relative;
                        z-index: 1;
                    }
                    
                    .invoice-title {
                        font-size: 32px;
                        font-weight: 700;
                        margin-bottom: 8px;
                        letter-spacing: -0.5px;
                    }
                    
                    .invoice-subtitle {
                        font-size: 18px;
                        font-weight: 500;
                        opacity: 0.95;
                    }
                    
                    /* Main Content */
                    .invoice-body {
                        padding: 40px;
                    }
                    
                    /* Info Grid */
                    .info-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 30px;
                        margin-bottom: 35px;
                        padding-bottom: 30px;
                        border-bottom: 2px dashed var(--kt-gray-300);
                    }
                    
                    .info-card {
                        background: var(--kt-gray-100);
                        border-radius: 8px;
                        padding: 20px;
                        border-left: 4px solid var(--kt-primary);
                    }
                    
                    .info-label {
                        color: var(--kt-gray-600);
                        font-size: 12px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        margin-bottom: 6px;
                    }
                    
                    .info-value {
                        color: var(--kt-dark);
                        font-size: 15px;
                        font-weight: 600;
                        margin-bottom: 12px;
                    }
                    
                    .info-value:last-child {
                        margin-bottom: 0;
                    }
                    
                    /* Payment Section */
                    .payment-section {
                        background: linear-gradient(135deg, var(--kt-success-light) 0%, #d4f8e8 100%);
                        border: 1px solid rgba(80, 205, 137, 0.3);
                        border-radius: 10px;
                        padding: 25px;
                        margin-bottom: 35px;
                    }
                    
                    .payment-header {
                        display: flex;
                        align-items: center;
                        margin-bottom: 20px;
                        padding-bottom: 15px;
                        border-bottom: 1px dashed rgba(80, 205, 137, 0.3);
                    }
                    
                    .payment-icon {
                        width: 40px;
                        height: 40px;
                        background: var(--kt-success);
                        border-radius: 8px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-right: 12px;
                        color: white;
                        font-weight: 700;
                        font-size: 18px;
                    }
                    
                    .payment-title {
                        color: var(--kt-dark);
                        font-size: 18px;
                        font-weight: 700;
                    }
                    
                    .payment-grid {
                        display: grid;
                        grid-template-columns: repeat(3, 1fr);
                        gap: 20px;
                    }
                    
                    .payment-item {
                        background: white;
                        border-radius: 6px;
                        padding: 15px;
                    }
                    
                    .payment-label {
                        color: var(--kt-gray-600);
                        font-size: 11px;
                        font-weight: 600;
                        text-transform: uppercase;
                        margin-bottom: 4px;
                    }
                    
                    .payment-value {
                        color: var(--kt-dark);
                        font-size: 14px;
                        font-weight: 700;
                    }
                    
                    .payment-type-badge {
                        display: inline-block;
                        background: var(--kt-success);
                        color: white;
                        padding: 4px 10px;
                        border-radius: 4px;
                        font-size: 11px;
                        font-weight: 600;
                        text-transform: uppercase;
                    }
                    
                    /* Items Table */
                    .items-section {
                        margin-bottom: 35px;
                    }
                    
                    .section-title {
                        color: var(--kt-dark);
                        font-size: 18px;
                        font-weight: 700;
                        margin-bottom: 20px;
                        display: flex;
                        align-items: center;
                    }
                    
                    .section-title::before {
                        content: '';
                        width: 4px;
                        height: 24px;
                        background: var(--kt-primary);
                        border-radius: 2px;
                        margin-right: 12px;
                    }
                    
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        background: white;
                        border-radius: 8px;
                        overflow: hidden;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                    }
                    
                    thead {
                        background: var(--kt-gray-100);
                    }
                    
                    th {
                        padding: 14px 12px;
                        text-align: left;
                        color: var(--kt-gray-700);
                        font-weight: 700;
                        font-size: 11px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        border-bottom: 2px solid var(--kt-gray-300);
                    }
                    
                    td {
                        padding: 14px 12px;
                        color: var(--kt-dark);
                        font-size: 13px;
                        border-bottom: 1px solid var(--kt-gray-200);
                    }
                    
                    tbody tr:last-child td {
                        border-bottom: none;
                    }
                    
                    tbody tr:hover {
                        background: var(--kt-gray-100);
                    }
                    
                    .text-center {
                        text-align: center;
                    }
                    
                    .text-right {
                        text-align: right;
                    }
                    
                    .item-name {
                        font-weight: 600;
                        color: var(--kt-dark);
                    }
                    
                    .item-sku {
                        background: var(--kt-primary-light);
                        color: var(--kt-primary);
                        padding: 3px 8px;
                        border-radius: 4px;
                        font-size: 11px;
                        font-weight: 600;
                        display: inline-block;
                    }
                    
                    /* Summary Section */
                    .summary-section {
                        display: flex;
                        justify-content: flex-end;
                    }
                    
                    .summary-box {
                        width: 380px;
                        background: var(--kt-gray-100);
                        border-radius: 10px;
                        padding: 25px;
                        border: 1px solid var(--kt-gray-300);
                    }
                    
                    .summary-row {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 10px 0;
                    }
                    
                    .summary-row.total {
                        border-top: 2px dashed var(--kt-gray-400);
                        margin-top: 10px;
                        padding-top: 15px;
                    }
                    
                    .summary-label {
                        color: var(--kt-gray-700);
                        font-size: 13px;
                        font-weight: 500;
                    }
                    
                    .summary-value {
                        font-weight: 700;
                        font-size: 14px;
                        color: var(--kt-dark);
                    }
                    
                    .total .summary-label {
                        font-size: 16px;
                        font-weight: 700;
                        color: var(--kt-dark);
                    }
                    
                    .total .summary-value {
                        font-size: 24px;
                        color: var(--kt-primary);
                    }
                    
                    .text-danger {
                        color: var(--kt-danger) !important;
                    }
                    
                    .text-success {
                        color: var(--kt-success) !important;
                    }
                    
                    /* Footer */
                    .invoice-footer {
                        background: var(--kt-gray-100);
                        padding: 25px 40px;
                        margin-top: 40px;
                        text-align: center;
                        color: var(--kt-gray-600);
                        font-size: 13px;
                    }
                    
                    .footer-thank-you {
                        font-size: 16px;
                        font-weight: 600;
                        color: var(--kt-dark);
                        margin-bottom: 8px;
                    }
                    
                    /* Print Buttons */
                    .print-actions {
                        text-align: center;
                        margin: 30px 0;
                        padding: 0 40px;
                    }
                    
                    .btn {
                        display: inline-block;
                        padding: 12px 28px;
                        border: none;
                        border-radius: 8px;
                        font-weight: 600;
                        font-size: 14px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        text-decoration: none;
                        margin: 0 6px;
                    }
                    
                    .btn-primary {
                        background: var(--kt-primary);
                        color: white;
                        box-shadow: 0 4px 12px rgba(0, 158, 247, 0.3);
                    }
                    
                    .btn-primary:hover {
                        background: #0095e8;
                        transform: translateY(-2px);
                        box-shadow: 0 6px 16px rgba(0, 158, 247, 0.4);
                    }
                    
                    .btn-secondary {
                        background: var(--kt-gray-200);
                        color: var(--kt-gray-700);
                    }
                    
                    .btn-secondary:hover {
                        background: var(--kt-gray-300);
                        color: var(--kt-dark);
                    }
                    
                    /* Print Styles */
                    @media print {
                        body {
                            padding: 0;
                            background: white;
                        }
                        
                        .invoice-container {
                            box-shadow: none;
                            max-width: 100%;
                        }
                        
                        .no-print {
                            display: none !important;
                        }
                        
                        .invoice-header {
                            print-color-adjust: exact;
                            -webkit-print-color-adjust: exact;
                        }
                        
                        .payment-section {
                            print-color-adjust: exact;
                            -webkit-print-color-adjust: exact;
                        }
                        
                        tbody tr:hover {
                            background: transparent;
                        }
                    }
                    
                    @page {
                        size: A4;
                        margin: 15mm;
                    }
                </style>
            </head>
            <body>
                <div class="invoice-container">
                    ${printContent}
                </div>
                
                <div class="print-actions no-print">
                    <button class="btn btn-primary" onclick="window.print()">
                        🖨️ Print Invoice
                    </button>
                    <button class="btn btn-secondary" onclick="window.close()">
                        ✕ Close Window
                    </button>
                </div>
                
                <script>
                    window.onload = function() {
                        window.focus();
                        // Uncomment to auto-print
                        // setTimeout(() => window.print(), 300);
                    };
                <\/script>
            </body>
            </html>
        `);
        
        printWindow.document.close();
    }

    // Initialize tooltips and toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Handle toggle animation
        const toggleButtons = document.querySelectorAll('.toggle-items');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                if (target) {
                    this.classList.toggle('collapsed');
                }
            });
        });
    });
</script>