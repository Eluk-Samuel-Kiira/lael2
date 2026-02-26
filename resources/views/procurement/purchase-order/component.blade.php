@can('view purchase_orders')
<div class="card-body py-4" id="reloadPurchasesComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input"
                                type="checkbox"
                                data-kt-check="true"
                                data-kt-check-target="#kt_table_users .row-checkbox"
                                value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('pagination._id')}}</th>
                    <th class="min-w-125px">{{__('passwords.po_number')}}</th>
                    <th class="min-w-125px">{{__('passwords.supplier')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-125px">{{__('pagination._total')}}</th>
                    <th class="min-w-125px">{{__('passwords.expected_delivery')}}</th>
                    <th class="min-w-125px">{{__('auth._creater')}}</th>
                    <th class="min-w-125px">{{__('auth.updated_at')}}</th>
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($purchaseOrders) && $purchaseOrders->count() > 0)
                    @foreach ($purchaseOrders as $order)
                        <!-- Main Row -->
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input row-checkbox" type="checkbox" value="{{ $order->id }}" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('payments._id')}}{{ $order->id }}</div>
                            </td>
                            
                            <td>
                                <a href="javascript:void(0);" 
                                class="toggle-items fw-bold text-primary" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#orderItems{{ $order->id }}">
                                {{ $order->po_number }}
                                </a>
                            </td>

                            <td>{{ $order->supplier->name ?? __('pagination._none') }}</td>

                            <td>
                                <div class="badge badge-{{ $order->status_badge }} fw-bold">
                                    {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                </div>
                            </td>

                            <td>
                                <div class="badge badge-light fw-bold">
                                    {{ $order->total }} {{ currency_symbol() }}
                                </div>
                            </td>

                            <td>
                                <div class="badge badge-light fw-bold">
                                    {{ $order->expected_delivery_date ? $order->expected_delivery_date->format('M d, Y') : __('pagination._none') }}
                                </div>
                            </td>

                            <td>
                                <div class="badge badge-light fw-bold">{{ $order->creator->name ?? __('pagination._none')}}</div>
                            </td>
                            
                            <td>{{ $order->updated_at->format('d M Y, h:i a') }}</td>
                            <td>{{ $order->created_at->format('d M Y, h:i a') }}</td>
                            
                            <td>
                                <div class="d-flex gap-2">
                                    <!-- Status Action Buttons -->
                                    @if($order->status === 'draft')
                                        @can('submit purchase_orders')
                                            <button class="btn btn-sm btn-warning" onclick="submitForApproval({{ $order->id }})">
                                                {{ __('passwords.submit_approval') }}
                                            </button>
                                        @endcan
                                    @endif
                                    
                                    @if($order->status === 'pending_approval')
                                        @can('approve purchase_orders')
                                            <button class="btn btn-sm btn-success" onclick="approvePurchaseOrder({{ $order->id }})">
                                                {{ __('passwords.approve') }}
                                            </button>
                                        @endcan
                                    @endif
                                    
                                    @if($order->status === 'approved')
                                        @can('send purchase_orders')
                                            <button class="btn btn-sm btn-primary" onclick="sendToSupplier({{ $order->id }})">
                                                {{ __('passwords.send_supplier') }}
                                            </button>
                                        @endcan
                                    @endif
                                    
                                    @if(in_array($order->status, ['sent', 'partially_received']))
                                        @can('receive purchase_orders')
                                            <button class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#receiveItemsModal{{ $order->id }}"
                                                    data-total-pending="{{ $order->items->sum('quantity') - $order->items->sum('received_quantity') }}">
                                                {{ __('passwords.receive_items') }}
                                            </button>
                                        @endcan
                                    @endif
                                    
                                    @if(in_array($order->status, ['draft', 'pending_approval', 'approved']))
                                        @can('cancel purchase_orders')
                                            <button class="btn btn-sm btn-danger" onclick="cancelPurchaseOrder({{ $order->id }})">
                                                {{ __('passwords.cancel') }}
                                            </button>
                                        @endcan
                                    @endif

                                    <!-- View/Edit/Delete Buttons -->
                                    @can('view purchase_orders')
                                        <button class="btn btn-sm btn-light btn-active-color-success" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewPurchase{{$order->id}}">
                                            <i class="bi bi-eye fs-5"></i>
                                        </button>
                                    @endcan
                                    
                                    {{--
                                    @can('edit purchase_orders')
                                        <!-- Only show edit for draft and pending_approval status -->
                                        @if(in_array($order->status, ['draft', 'pending_approval']))
                                            <button class="btn btn-sm btn-light btn-active-color-primary" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editPurchase{{$order->id}}">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </button>
                                        @endif
                                    @endcan
                                    --}}

                                    @can('delete purchase_orders')
                                        <!-- Only show delete for draft status (before submission) and cancelled orders -->
                                        @if(in_array($order->status, ['draft', 'cancelled']))
                                            <button type="button" 
                                                    class="btn btn-sm btn-light btn-active-color-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deletePurchaseModal{{$order->id}}">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        @endif
                                    @endcan
                                </div>

                                <!-- Delete Modal -->
                                <div class="modal fade" id="deletePurchaseModal{{$order->id}}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>{{ __('auth.are_you_sure') }}</p>
                                                <p>{{ __('auth.action_cannot') }}</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" id="closeDeleteModal{{$order->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <button type="button" id="deleteButton{{$order->id}}" class="btn btn-danger" 
                                                        data-item-url="{{ route('purchase-orders.destroy', $order->id) }}"
                                                        data-item-id="{{ $order->id }}"
                                                        onclick="deleteItem(this)">
                                                    <span class="indicator-label">{{ __('auth._confirm') }}</span>
                                                    <span class="indicator-progress" style="display: none;">
                                                        {{__('auth.please_wait') }}
                                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>  

                                @include('procurement.purchase-order.view') 
                                @include('procurement.purchase-order.receive') 
                            </td>
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </table>
    </div>
</div>
@endcan