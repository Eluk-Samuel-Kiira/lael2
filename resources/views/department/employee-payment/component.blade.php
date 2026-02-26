@can('view employee payment')
<!-- resources/views/procurement/employee-payment/component.blade.php -->
<div class="card-body py-4" id="reloadPaymentComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_payments">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_payments .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('payments.payment_id')}}</th>
                    <th class="min-w-125px">{{__('payments.employee')}}</th>
                    <th class="min-w-125px">{{__('payments.payment_type')}}</th>
                    <th class="min-w-125px">{{__('payments.payment_method')}}</th>
                    <th class="min-w-125px">{{__('payments.gross_amount')}}</th>
                    <th class="min-w-125px">{{__('payments.net_amount')}}</th>
                    <th class="min-w-125px">{{__('payments.payment_date')}}</th>
                    <th class="min-w-125px">{{__('payments.payment_status')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($payments) && $payments->count() > 0)
                    @foreach ($payments as $payment)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('ID-')}}{{ $payment->id }}</div>
                            </td>
                            <td>{{ $payment->employee->first_name.' '.$payment->employee->last_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge badge-light">{{  $payment->payment_type }}</span>
                            </td>
                            <td>
                                @php
                                    $type = $payment->paymentMethod->type ?? 'other';
                                    $typeColors = [
                                        'cash' => 'warning',
                                        'bank_account' => 'info', 
                                        'digital_wallet' => 'primary',
                                        'card' => 'success',
                                        'check' => 'secondary',
                                        'mobile_money' => 'danger',
                                        'other' => 'dark'
                                    ];
                                    $color = $typeColors[$type] ?? 'dark';
                                    
                                    // Short names for common types
                                    $typeNames = [
                                        'cash' => 'Cash',
                                        'bank_account' => 'Bank',
                                        'digital_wallet' => 'Wallet',
                                        'card' => 'Card',
                                        'check' => 'Check',
                                        'mobile_money' => 'Mobile',
                                        'other' => 'Other'
                                    ];
                                    $typeName = $typeNames[$type] ?? 'Other';
                                @endphp
                                
                                <span class="badge badge-light-{{ $color }} me-2">
                                    <i class="fas fa-@switch($type)
                                        @case('cash') money-bill-wave @break
                                        @case('bank_account') bank @break
                                        @case('digital_wallet') wallet @break
                                        @case('card') credit-card @break
                                        @case('check') file-invoice @break
                                        @case('mobile_money') mobile-alt @break
                                        @default credit-card @break
                                    @endswitch me-1"></i>
                                    {{ $typeName }}
                                </span><br>
                                @if($payment->paymentMethod)
                                    @if($payment->paymentMethod->is_default)
                                        <span class="badge badge-light-success">
                                            <i class="fas fa-star me-1"></i>
                                            {{ $payment->paymentMethod->name }}
                                        </span>
                                    @else
                                        <span class="badge badge-light-primary">
                                            {{ $payment->paymentMethod->name }}
                                        </span>
                                    @endif
                                @else
                                    <span class="badge badge-light-secondary">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        {{__('accounting.no_payment_method')}}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $payment->gross_amount }}</td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ number_format($payment->net_amount, 2) }}</div>
                            </td>
                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                            <td>
                                
                                @if($payment->status === 'completed')
                                    <span class="badge badge-light-success">
                                        {{ __('payments.completed') }}
                                    </span>
                                @else
                                    <select name="status" 
                                            class="form-select form-select-solid form-select-sm" 
                                            onchange="updatePaymentStatus({{ $payment->id }}, this.value)"
                                            @cannot('update employee payment') disabled @endcannot>
                                        <option value="pending" {{ $payment->status == 'pending' ? 'selected' : '' }}>
                                            {{__('payments.pending')}}
                                        </option>
                                        <option value="completed" {{ $payment->status == 'completed' ? 'selected' : '' }}>
                                            {{__('payments.completed')}}
                                        </option>
                                        <option value="failed" {{ $payment->status == 'failed' ? 'selected' : '' }}>
                                            {{__('payments.failed')}}
                                        </option>
                                        <option value="cancelled" {{ $payment->status == 'cancelled' ? 'selected' : '' }}>
                                            {{__('payments.cancelled')}}
                                        </option>
                                    </select>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('edit employee payment')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editPaymentModal{{$payment->id}}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                        </button>
                                    @endcan
                                    @can('delete employee payment')
                                        <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deletePaymentModal{{$payment->id}}"
                                            {{ $payment->status === 'completed' ? 'disabled' : '' }}>
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>
                                        </button>
                                    @endcan
                                </div>

                                <!-- Delete Modal -->
                                <div class="modal fade" id="deletePaymentModal{{$payment->id}}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">{{ __('auth.confirm_deletion') }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>{{ __('auth.are_you_sure') }}</p>
                                                <p>{{ __('auth.action_cannot') }}</p>
                                                @if($payment->status === 'completed')
                                                    <div class="alert alert-warning">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                                        {{ __('payments.cannot_delete_completed_warning') }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" id="closeDeleteModal{{$payment->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                @can('delete employee payment')
                                                @if($payment->status !== 'completed')
                                                    <button type="button" id="deleteButton{{$payment->id}}" class="btn btn-danger" 
                                                        data-item-url="{{ route('payment.destroy', $payment->id) }}" 
                                                        data-item-id="{{ $payment->id }}"
                                                        onclick="deleteItem(this)">
                                                        <span class="indicator-label">{{ __('auth._confirm') }}</span>
                                                        <span class="indicator-progress" style="display: none;">
                                                            {{__('auth.please_wait') }}
                                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                        </span>
                                                    </button>
                                                @endif
                                                @endcan
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                @include('department.employee-payment.edit')
                                
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-cash-coin fs-2"></i>
                                <p class="mt-2">{{ __('payments.no_payments_found') }}</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endcan

