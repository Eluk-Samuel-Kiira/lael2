<!-- resources/views/procurement/expense/expense-component.blade.php -->
<div class="card-body py-4" id="reloadExpenseComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_users .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('pagination.expense_id')}}</th>
                    <th class="min-w-125px">{{__('pagination.expense_number')}}</th>
                    <th class="min-w-125px">{{__('pagination.description')}}</th> 
                    <th class="min-w-125px">{{__('pagination.amount')}}</th> 
                    <th class="min-w-125px">{{__('payments.payment_method')}}</th>
                    <th class="min-w-125px">{{__('auth.created_at')}}</th>
                    <th class="min-w-125px">{{__('pagination.approve')}}</th>
                    <th class="min-w-125px">{{__('pagination.payment_status')}}</th>
                    <th class="min-w-100px text-end">{{__('auth._actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($expenses) && $expenses->count() > 0)
                    @foreach ($expenses as $expense)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="badge badge-light fw-bold">{{__('ID-')}}{{ $expense->id }}</div>
                            </td>
                            <td>{{ $expense->expense_number }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>
                                <div class="badge badge-light fw-bold">${{ number_format($expense->total_amount, 2) }}</div>
                            </td>
                            <td>
                                @php
                                    $type = $expense->paymentMethod->type ?? 'other';
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
                                
                               @if($expense->paymentMethod)
                                    @if($expense->paymentMethod->is_default)
                                        <span class="badge badge-light-success">
                                            <i class="fas fa-star me-1"></i>
                                            {{ $expense->paymentMethod->name }}
                                        </span>
                                    @else
                                        <span class="badge badge-light-primary">
                                            {{ $expense->paymentMethod->name }}
                                        </span>
                                    @endif
                                @else
                                    <span class="badge badge-light-secondary">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        {{__('accounting.no_payment_method')}}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $expense->created_at->format('d M Y, h:i a') }}</td>
                            <td>
                                @if($expense->approved_at)
                                    <span class="badge badge-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        {{ __('pagination.approved') }}
                                    </span>
                                    <div class="small text-muted mt-1">
                                        {{ $expense->approved_at->format('d M Y, h:i a') }}
                                    </div>
                                @else
                                    <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid">
                                        <input type="checkbox"
                                            class="form-check-input approve-switch"
                                            id="approve-switch-{{ $expense->id }}"
                                            onchange="approveExpense({{ $expense->id }}, this.checked ? 1 : 0)"
                                        >

                                        <span id="approve-label-{{ $expense->id }}"
                                            class="form-check-label ms-2 fw-bold fs-6 text-gray-700">
                                            {{ __('pagination.pending') }}
                                        </span>
                                    </label>
                                @endif
                            </td>
                                <td>
                                    <select name="status" class="form-select form-select-solid form-select-sm" onchange="updateExpenseStatus({{ $expense->id }}, this.value)"
                                        @cannot('update expense') disabled @endcannot>
                                        <option value="pending" {{ $expense->payment_status == 'pending' ? 'selected' : '' }}>{{__('pagination.pending')}}</option>
                                        <option value="paid" {{ $expense->payment_status == 'paid' ? 'selected' : '' }}>{{__('pagination.paid')}}</option>
                                        <option value="reimbursed" {{ $expense->payment_status == 'reimbursed' ? 'selected' : '' }}>{{__('pagination.reimbursed')}}</option>
                                    </select>
                                </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @can('edit expense')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#editExpenseModal{{$expense->id}}">
                                            <i class="bi bi-pencil-square me-1 fs-5"></i> <span>{{ __('auth._edit') }}</span>
                                        </button>
                                    @endcan
                                    @can('upload expense')
                                        <button 
                                            class="btn btn-sm btn-light btn-active-color-primary d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#uploadReceiptModal{{$expense->id}}">
                                            <i class="bi bi-upload me-1 fs-5"></i> <span>{{ __('auth._upload') }}</span>
                                        </button>
                                    @endcan
                                    @can('delete expense')
                                        <button type="button" 
                                            class="btn btn-sm btn-light btn-active-color-danger d-flex align-items-center px-3 py-2" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteExpenseModal{{$expense->id}}">
                                            <i class="bi bi-trash me-1 fs-5"></i> <span>{{ __('auth._delete') }}</span>
                                        </button>
                                    @endcan
                                </div>

                                <!-- Delete Expense Modal -->
                                <div class="modal fade" id="deleteExpenseModal{{$expense->id}}" tabindex="-1" aria-hidden="true">
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
                                                <!-- Discard Button -->
                                                <button type="button" id="closeDeleteModal{{$expense->id}}" class="btn btn-light me-3" data-bs-dismiss="modal">{{ __('auth._discard') }}</button>
                                                <!-- Confirm Button -->
                                                <button type="button" id="deleteButton{{$expense->id}}" class="btn btn-danger" 
                                                    data-item-url="{{ route('expense.destroy', $expense->id) }}" 
                                                    data-item-id="{{ $expense->id }}"
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
                                @include('procurement.expense.edit')
                                @include('procurement.expense.upload-receipt')
                                
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="bi bi-receipt fs-2"></i>
                                <p class="mt-2">{{ __('pagination.no_expenses_found') }}</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>