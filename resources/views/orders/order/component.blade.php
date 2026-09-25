@can('view order')
<div class="card-body py-4" id="ordersIndexTable">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
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
                    <th class="min-w-125px">{{__('passwords.location')}}</th>
                    <th class="min-w-125px">{{__('pagination.created_by')}}</th>
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
                            <span class="badge badge-light-warning fw-bold px-3 py-2">
                                {{ $order->correct_location->name ?? 'N/A' }}
                            </span>
                        </td>

                        <td>
                            <span class="badge badge-light-primary fw-bold fs-8 px-3 py-2">{{ ucwords($order->orderCreater->name) }}</span>
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
                            <div class="d-flex justify-content-end">
                                <!-- Desktop: Show all buttons -->
                                <div class="d-none d-md-flex gap-2 flex-wrap">
                                    @can('complete order')
                                        @if ($order->status === 'confirmed' && $order->source === 'pos')
                                            <button class="btn btn-sm btn-icon btn-light-primary flex-shrink-0"
                                                onclick="openCompletePayment_{{ $order->id }}()"
                                                data-bs-toggle="tooltip" data-bs-title="{{ __('pagination._complete') }}"
                                                style="width: 32px; height: 32px; min-width: 32px;">
                                                <i class="ki-duotone ki-dollar fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                            </button>
                                        @endif

                                        @if ($order->status === 'completed')
                                            <button class="btn btn-sm btn-icon btn-light btn-active-light-primary flex-shrink-0"
                                                data-bs-toggle="modal" data-bs-target="#sendOrderModal{{ $order->id }}"
                                                data-bs-title="{{ __('passwords.send_receipt') }}"
                                                style="width: 32px; height: 32px; min-width: 32px;">
                                                <i class="ki-duotone ki-sms fs-4"><span class="path1"></span><span class="path2"></span></i>
                                            </button>
                                            <button class="btn btn-sm btn-icon btn-light btn-active-light-primary flex-shrink-0"
                                                onclick="printOrder({{ $order->id }})"
                                                data-bs-toggle="tooltip" data-bs-title="{{ __('passwords._print') }}"
                                                style="width: 32px; height: 32px; min-width: 32px;">
                                                <i class="ki-duotone ki-printer fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                            </button>
                                        @endif
                                    @endcan
                                    
                                    @can('refund order')
                                        @if ($order->status === 'completed')
                                            <button class="btn btn-sm btn-icon btn-light-success flex-shrink-0"
                                                onclick="openRefund_{{ $order->id }}()"
                                                data-bs-toggle="tooltip" data-bs-title="{{ __('payments.refund') }}"
                                                style="width: 32px; height: 32px; min-width: 32px;">
                                                <i class="ki-duotone ki-arrow-circle-left fs-4"><span class="path1"></span><span class="path2"></span></i>
                                            </button>
                                        @endif
                                    @endcan

                                    @can('cancel order')
                                        @if ($order->status === 'confirmed')
                                            <button class="btn btn-sm btn-icon btn-light-danger flex-shrink-0"
                                                onclick="cancelPOSOrder({{ $order->id }})"
                                                data-bs-toggle="tooltip" data-bs-title="{{ __('passwords.cancel') }}"
                                                style="width: 32px; height: 32px; min-width: 32px;">
                                                <i class="ki-duotone ki-cross fs-4"><span class="path1"></span><span class="path2"></span></i>
                                            </button>
                                        @endif
                                    @endcan

                                    @can('view order')
                                    <button class="btn btn-sm btn-icon btn-light btn-active-light-info flex-shrink-0"
                                        data-bs-toggle="offcanvas" data-bs-target="#orderDetail{{ $order->id }}"
                                        data-bs-title="{{ __('passwords.view_details') }}"
                                        style="width: 32px; height: 32px; min-width: 32px;">
                                        <i class="ki-duotone ki-information fs-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    </button>
                                    @endcan
                                </div>

                                <!-- Mobile: Dropdown menu -->
                                <div class="d-md-none">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light btn-active-light-primary dropdown-toggle" 
                                                type="button" 
                                                data-bs-toggle="dropdown" 
                                                aria-expanded="false">
                                            <i class="ki-duotone ki-dots-vertical fs-3"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @can('complete order')
                                                @if ($order->status === 'confirmed' && $order->source === 'pos')
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)" 
                                                        onclick="openCompletePayment_{{ $order->id }}()">
                                                            <i class="ki-duotone ki-dollar fs-4 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                            {{ __('pagination._complete') }}
                                                        </a>
                                                    </li>
                                                @endif

                                                @if ($order->status === 'completed')
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)" 
                                                        data-bs-toggle="modal" data-bs-target="#sendOrderModal{{ $order->id }}">
                                                            <i class="ki-duotone ki-sms fs-4 me-2"><span class="path1"></span><span class="path2"></span></i>
                                                            {{ __('passwords.send_receipt') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)" 
                                                        onclick="printOrder({{ $order->id }})">
                                                            <i class="ki-duotone ki-printer fs-4 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                            {{ __('passwords._print') }}
                                                        </a>
                                                    </li>
                                                @endif
                                            @endcan
                                            
                                            @can('refund order')
                                                @if ($order->status === 'completed')
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:void(0)" 
                                                        onclick="openRefund_{{ $order->id }}()">
                                                            <i class="ki-duotone ki-arrow-circle-left fs-4 me-2"><span class="path1"></span><span class="path2"></span></i>
                                                            {{ __('payments.refund') }}
                                                        </a>
                                                    </li>
                                                @endif
                                            @endcan

                                            @can('cancel order')
                                                @if ($order->status === 'confirmed')
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="javascript:void(0)" 
                                                        onclick="cancelPOSOrder({{ $order->id }})">
                                                            <i class="ki-duotone ki-cross fs-4 me-2"><span class="path1"></span><span class="path2"></span></i>
                                                            {{ __('passwords.cancel') }}
                                                        </a>
                                                    </li>
                                                @endif
                                            @endcan

                                            @can('view order')
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0)" 
                                                data-bs-toggle="offcanvas" data-bs-target="#orderDetail{{ $order->id }}">
                                                    <i class="ki-duotone ki-information fs-4 me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                                    {{ __('passwords.view_details') }}
                                                </a>
                                            </li>
                                            @endcan
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @include('orders.order.complete-payment')
                            @include('orders.order.send-receipt')
                            @include('orders.order.view-order')
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
    <div class="mt-4">
        <x-liveblade-pagination
            :paginator="$orders"
            id="orderPagination"
            route="{{ route('orders.index') }}"
            search-input-id="orderSearchInput"
            :show-info="true"
            :show-per-page="true"
            :per-page-options="[15, 25, 50, 100]"
            data-lb-component="ordersIndexTable"
        />
    </div>
</div>


@endcan