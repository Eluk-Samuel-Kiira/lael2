{{-- resources/views/manufacturing/production-order/component.blade.php --}}
@can('view production_orders')
<div class="card-body py-4" id="reloadProductionComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_production">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('passwords.production_number')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-125px">{{__('passwords.inputs')}}</th>
                    <th class="min-w-125px">{{__('passwords.outputs')}}</th>
                    <th class="min-w-125px">{{__('pagination._total')}}</th>
                    <th class="min-w-125px">{{__('passwords.scheduled_date')}}</th>
                    <th class="min-w-125px">{{__('auth._creater')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @forelse ($productionOrders as $order)
                    <tr>
                        <td>
                            <div class="form-check form-check-sm form-check-custom form-check-solid">
                                <input class="form-check-input row-checkbox" type="checkbox" value="{{ $order->id }}" />
                            </div>
                        </td>
                        <td>
                            <a href="javascript:void(0);" 
                               class="fw-bold text-primary" 
                               data-bs-toggle="collapse" 
                               data-bs-target="#productionItems{{ $order->id }}">
                                {{ $order->production_number }}
                            </a>
                        </td>
                        <td>
                            <div class="badge badge-{{ $order->status_badge }} fw-bold">
                                {{ $order->status_label }}
                            </div>
                        </td>
                        <td>
                            <div class="badge badge-light fw-bold">
                                {{ $order->inputs->count() }} items
                            </div>
                            <small class="d-block text-muted">
                                {{ number_format($order->total_input_quantity, 2) }} units
                            </small>
                        </td>
                        <td>
                            <div class="badge badge-light fw-bold">
                                {{ $order->outputs->count() }} items
                            </div>
                            <small class="d-block text-muted">
                                {{ number_format($order->total_output_quantity, 2) }} units
                            </small>
                        </td>
                        <td>
                            <div class="badge badge-light fw-bold">
                                {{ number_format($order->total_cost ?? 0, 2) }} {{ currency_symbol() }}
                            </div>
                        </td>
                        <td>
                            <div class="badge badge-light fw-bold">
                                {{ $order->scheduled_date ? $order->scheduled_date->format('M d, Y') : __('pagination._none') }}
                            </div>
                        </td>
                        <td>
                            <div class="badge badge-light fw-bold">{{ $order->createdBy->name ?? __('pagination._none')}}</div>
                        </td>
                        <td>
                            <div class="d-flex gap-2 flex-wrap">
                                @if($order->status === 'draft')
                                    @can('start production_orders')
                                        <button class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#startProductionModal{{ $order->id }}">
                                            <i class="bi bi-play-fill me-1"></i>
                                            {{ __('passwords.start_production') }}
                                        </button>
                                    @endcan
                                @endif
                                
                                @if($order->status === 'in_progress')
                                    @can('complete production_orders')
                                        <button type="button" class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#completeProductionModal{{ $order->id }}">
                                            <i class="bi bi-check-circle me-1"></i>
                                            {{ __('passwords.complete_production') }}
                                        </button>
                                    @endcan
                                @endif
                                
                                @if(in_array($order->status, ['draft', 'in_progress']))
                                    @can('cancel production_orders')
                                        <button class="btn btn-sm btn-danger" onclick="cancelProduction({{ $order->id }})">
                                            <i class="bi bi-x-circle me-1"></i>
                                            {{ __('passwords.cancel') }}
                                        </button>
                                    @endcan
                                @endif

                                @can('view production_orders')
                                    <button class="btn btn-sm btn-light btn-active-color-success" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewProduction{{$order->id}}">
                                        <i class="bi bi-eye fs-5"></i>
                                    </button>
                                @endcan
                            </div>

                            {{-- Include Modals --}}
                            @include('manufacturing.production-order.update-modal', ['order' => $order])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">{{ __('passwords.no_records_found') }}</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-liveblade-pagination
        :paginator="$productionOrders"
        id="productionOrderPagination"
        route="{{ route('production-orders.index') }}"
        search-input-id="productionOrderSearchInput"
        :show-info="true"
        :show-per-page="true"
        :per-page-options="[15, 25, 50, 100]"
        data-lb-component="reloadProductionComponent"
    />
</div>
@endcan





