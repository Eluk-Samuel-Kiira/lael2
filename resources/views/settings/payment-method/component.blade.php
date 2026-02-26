<div class="card-body py-4" id="paymentMethodIndexTable">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_payment_methods">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_payment_methods .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('payments.payment_method_id')}}</th>
                    <th class="min-w-125px">{{__('auth._name')}}</th>
                    <th class="min-w-125px">{{__('payments._type')}}</th>
                    <th class="min-w-125px">{{__('payments._provider')}}</th>
                    <th class="min-w-125px">{{__('payments.account_number')}}</th>
                    <th class="min-w-125px">{{__('accounting.current_balance')}}</th>
                    <th class="min-w-125px">{{__('auth._creater')}}</th>
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('auth._status')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($all_payment_methods) && $all_payment_methods->count() > 0)
                    @foreach ($all_payment_methods as $paymentMethod)
                        <tr data-role="{{ strtolower($paymentMethod->name) }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('payments._id')}}{{ $paymentMethod->id }}</div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    {{ $paymentMethod->name }}
                                    @if($paymentMethod->is_default)
                                        <span class="badge badge-success ms-2">{{__('payments._default')}}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-light-info">{{ ucwords(str_replace('_', ' ', $paymentMethod->type)) }}</span>
                            </td>
                            <td>{{ $paymentMethod->provider ?? 'N/A' }}</td>
                            <td>
                                @if($paymentMethod->account_number)
                                    <span class="text-muted">****{{ substr($paymentMethod->account_number, -4) }}</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $paymentMethod->current_balance }}</td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ $paymentMethod->creator->name ?? 'N/A' }}</div>
                            </td>
                            <td>{{ $paymentMethod->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <select name="status" class="form-select form-select-solid form-select-sm" 
                                            onchange="updatePaymentMethodStatus({{ $paymentMethod->id }}, this.value)" @cannot('update payment method') disabled @endcan>
                                        <option value="1" {{ $paymentMethod->is_active == 1 ? 'selected' : '' }}>{{__('auth._active')}}</option>
                                        <option value="0" {{ $paymentMethod->is_active == 0 ? 'selected' : '' }}>{{__('auth._inactive')}}</option>
                                    </select>
                                    
                                    {{-- @if(!$paymentMethod->is_default && $paymentMethod->is_active)
                                        <button type="button" class="btn btn-sm btn-light btn-active-color-primary"
                                                onclick="setDefaultPaymentMethod({{ $paymentMethod->id }})"
                                                title="{{__('payments.set_as_default')}}">
                                            <i class="ki-duotone ki-star"></i>
                                        </button>
                                    @endif --}}
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <!-- Edit Button -->
                                    @can('create payment method')
                                    <button class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editPaymentMethod{{$paymentMethod->id}}">
                                        <i class="bi bi-pencil-square me-1 fs-5"></i> 
                                        <span>{{ __('auth._edit') }}</span>
                                    </button>
                                    @endcan
                                    
                                    <!-- Delete Button -->
                                    @can('delete payment method')
                                    <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deletePaymentMethodModal{{$paymentMethod->id}}">
                                        <i class="bi bi-trash me-1 fs-5"></i> 
                                        <span>{{ __('auth._delete') }}</span>
                                    </button>
                                    @endcan
                                </div>

                                <!-- Delete Modal -->
                                <div class="modal fade" id="deletePaymentMethodModal{{$paymentMethod->id}}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>{{ __('payments.are_you_sure_delete_payment_method') }}</p>
                                                <p>{{ __('auth.action_cannot') }}</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" id="closeDeleteModal{{$paymentMethod->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <button type="button" id="deleteButton{{$paymentMethod->id}}" class="btn btn-danger" 
                                                        data-item-url="{{ route('paymentmethod.destroy', $paymentMethod->id) }}" 
                                                        data-item-id="{{ $paymentMethod->id }}"
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
                                @include('settings.payment-method.edit')
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="10" class="text-center py-10">
                            <div class="text-muted">{{__('payments.no_payment_methods_found')}}</div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>