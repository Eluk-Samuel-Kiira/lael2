@can('view invoice')
<div class="card-body py-4" id="reloadInvoiceComponent">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="w-10px pe-2">
                        <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                            <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_table_invoices .form-check-input" value="1" />
                        </div>
                    </th>
                    <th class="min-w-125px">{{__('payments.invoice_number')}}</th>
                    <th class="min-w-150px">{{__('payments.customer')}}</th>
                    <th class="min-w-125px">{{__('payments.issue_date')}}</th>
                    <th class="min-w-125px">{{__('payments.due_date')}}</th>
                    <th class="min-w-125px">{{__('payments.amount')}}</th>
                    <th class="min-w-125px">{{__('payments.status')}}</th>
                    <th class="min-w-125px text-end">{{__('payments.actions')}}</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @if (!empty($invoices) && $invoices->count() > 0)
                    @foreach ($invoices as $invoice)
                        <tr data-invoice="{{ $invoice->invoice_number }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" />
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-gray-800">{{ $invoice->invoice_number }}</span>
                                    <span class="text-muted fs-7">{{ __('payments.order') }}: {{ $invoice->order->order_number ?? 'N/A' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-35px me-3">
                                        <div class="symbol-label bg-light-primary text-primary fw-bold">
                                            {{ strtoupper(substr($invoice->billing_name, 0, 1)) }}
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-gray-800">{{ $invoice->billing_name }}</span>
                                        <span class="text-muted fs-7">{{ $invoice->billing_email ?? '—' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-light-primary fw-bold px-3 py-2">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    {{ $invoice->issue_date->format('d M Y') }}
                                </span>
                            </td>
                            <td>
                                @if($invoice->due_date)
                                    @php
                                        $dueDateClass = 'badge-light-secondary';
                                        $dueIcon = 'bi-calendar-check';
                                        if($invoice->isOverdue()) {
                                            $dueDateClass = 'badge-light-danger';
                                            $dueIcon = 'bi-exclamation-triangle';
                                        } elseif($invoice->due_date->diffInDays(now()) <= 3) {
                                            $dueDateClass = 'badge-light-warning';
                                            $dueIcon = 'bi-clock';
                                        }
                                    @endphp
                                    <span class="badge {{ $dueDateClass }} fw-bold px-3 py-2">
                                        <i class="bi {{ $dueIcon }} me-1"></i>
                                        {{ $invoice->due_date->format('d M Y') }}
                                        @if($invoice->isOverdue())
                                            <span class="badge badge-danger ms-1">{{ __('payments.overdue') }}</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-gray-800 fs-5">{{ number_format($invoice->total, 2) }}</span>
                                    <span class="text-muted fs-7">{{ $invoice->currency }}</span>
                                    @if($invoice->balance_due > 0 && $invoice->balance_due < $invoice->total)
                                        <span class="badge badge-light-warning mt-1">{{ __('payments.balance_due') }}: {{ number_format($invoice->balance_due, 2) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @php
                                    $statusColors = [
                                        'draft' => ['bg' => '#e9ecef', 'text' => '#495057', 'border' => '#ced4da'],
                                        'sent' => ['bg' => '#cce5ff', 'text' => '#004085', 'border' => '#b8daff'],
                                        'viewed' => ['bg' => '#cce5ff', 'text' => '#004085', 'border' => '#b8daff'],
                                        'partially_paid' => ['bg' => '#fff3cd', 'text' => '#856404', 'border' => '#ffeeba'],
                                        'paid' => ['bg' => '#d4edda', 'text' => '#155724', 'border' => '#c3e6cb'],
                                        'overdue' => ['bg' => '#f8d7da', 'text' => '#721c24', 'border' => '#f5c6cb'],
                                        'void' => ['bg' => '#e2e3e5', 'text' => '#383d41', 'border' => '#d6d8db'],
                                        'cancelled' => ['bg' => '#f8d7da', 'text' => '#721c24', 'border' => '#f5c6cb'],
                                    ];
                                    $colors = $statusColors[$invoice->status] ?? $statusColors['draft'];
                                @endphp
                                <select name="status" class="form-select form-select-sm fw-bold"
                                    data-current-status="{{ $invoice->status }}"
                                    style="
                                        background-color: {{ $colors['bg'] }}; 
                                        color: {{ $colors['text'] }}; 
                                        border-color: {{ $colors['border'] }};
                                        font-weight: 600;
                                        padding: 0.375rem 1.75rem 0.375rem 0.75rem;
                                        background-image: url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e\");
                                        background-repeat: no-repeat;
                                        background-position: right 0.75rem center;
                                        background-size: 16px 12px;
                                        -webkit-appearance: none;
                                        -moz-appearance: none;
                                        appearance: none;
                                        cursor: pointer;
                                        transition: all 0.2s ease;
                                        min-width: 130px;
                                    "
                                    onchange="updateInvoiceStatus({{ $invoice->id }}, this.value)"
                                    @cannot('edit invoice') disabled @endcannot>
                                    <option value="draft" {{ $invoice->status == 'draft' ? 'selected' : '' }} style="background-color: #e9ecef; color: #495057; padding: 5px;">
                                        📄 {{__('payments.draft')}}
                                    </option>
                                    <option value="sent" {{ $invoice->status == 'sent' ? 'selected' : '' }} style="background-color: #cce5ff; color: #004085; padding: 5px;">
                                        📨 {{__('payments.sent')}}
                                    </option>
                                    <option value="viewed" {{ $invoice->status == 'viewed' ? 'selected' : '' }} style="background-color: #cce5ff; color: #004085; padding: 5px;">
                                        👁️ {{__('payments.viewed')}}
                                    </option>
                                    <option value="partially_paid" {{ $invoice->status == 'partially_paid' ? 'selected' : '' }} style="background-color: #fff3cd; color: #856404; padding: 5px;">
                                        💳 {{__('payments.partially_paid')}}
                                    </option>
                                    <option value="paid" {{ $invoice->status == 'paid' ? 'selected' : '' }} style="background-color: #d4edda; color: #155724; padding: 5px;">
                                        ✅ {{__('payments.paid')}}
                                    </option>
                                    <option value="overdue" {{ $invoice->status == 'overdue' ? 'selected' : '' }} style="background-color: #f8d7da; color: #721c24; padding: 5px;">
                                        ⚠️ {{__('payments.overdue')}}
                                    </option>
                                    <option value="void" {{ $invoice->status == 'void' ? 'selected' : '' }} style="background-color: #e2e3e5; color: #383d41; padding: 5px;">
                                        🚫 {{__('payments.void')}}
                                    </option>
                                    <option value="cancelled" {{ $invoice->status == 'cancelled' ? 'selected' : '' }} style="background-color: #f8d7da; color: #721c24; padding: 5px;">
                                        ❌ {{__('payments.cancelled')}}
                                    </option>
                                </select>
                            </td>
                            <td>
                                <div class="d-flex gap-2 justify-content-end">
                                    @can('view invoice')
                                        <button type="button" 
                                            class="btn btn-sm btn-icon btn-light-info" 
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewInvoiceModal{{ $invoice->id }}"
                                            data-bs-toggle="tooltip" title="{{ __('payments.view_invoice') }}">
                                            <i class="bi bi-eye fs-5"></i>
                                        </button>
                                    @endcan
                                    {{--
                                    @can('edit invoice')
                                        @if($invoice->status === 'draft')
                                            <button 
                                                class="btn btn-sm btn-icon btn-light-primary" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#editInvoiceModal{{ $invoice->id }}"
                                                data-bs-toggle="tooltip" title="{{ __('payments.edit_invoice') }}">
                                                <i class="bi bi-pencil-square fs-5"></i>
                                            </button>
                                        @endif
                                    @endcan
                                    --}}
                                    @can('send invoice')
                                        @if($invoice->status !== 'void' && $invoice->status !== 'paid')
                                            <button 
                                                class="btn btn-sm btn-icon btn-light-success" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#sendInvoiceModal{{ $invoice->id }}"
                                                data-bs-toggle="tooltip" title="{{ __('payments.send_invoice') }}">
                                                <i class="bi bi-envelope fs-5"></i>
                                            </button>
                                        @endif
                                    @endcan
                                    @can('record invoice payment')
                                        @if(!in_array($invoice->status, ['void', 'paid', 'cancelled', 'draft']))
                                            <button type="button"
                                                class="btn btn-sm btn-icon btn-light-success"
                                                data-bs-toggle="modal"
                                                data-bs-target="#recordPaymentModal{{ $invoice->id }}"
                                                data-bs-toggle="tooltip" title="{{ __('payments.record_payment') }}">
                                                <i class="bi bi-cash-coin fs-5"></i>
                                            </button>
                                        @endif
                                    @endcan
                                    @can('delete invoice')
                                        @if(in_array($invoice->status, ['draft', 'void']))
                                            <button type="button" 
                                                class="btn btn-sm btn-icon btn-light-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteInvoiceModal{{ $invoice->id }}"
                                                data-bs-toggle="tooltip" title="{{ __('payments.delete_invoice') }}">
                                                <i class="bi bi-trash fs-5"></i>
                                            </button>
                                        @endif
                                    @endcan
                                    @can('void invoice')
                                        @if(!in_array($invoice->status, ['void', 'paid', 'cancelled']))
                                            <button type="button" 
                                                class="btn btn-sm btn-icon btn-light-dark" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#voidInvoiceModal{{ $invoice->id }}"
                                                data-bs-toggle="tooltip" title="{{ __('payments.void_invoice') }}">
                                                <i class="bi bi-ban fs-5"></i>
                                            </button>
                                        @endif
                                    @endcan
                                    @can('download invoice')
                                        <a href="{{ route('invoices.pdf', $invoice->id) }}" 
                                            class="btn btn-sm btn-icon btn-light-secondary" 
                                            target="_blank"
                                            data-bs-toggle="tooltip" title="{{ __('payments.download_pdf') }}">
                                            <i class="bi bi-file-pdf fs-5"></i>
                                        </a>
                                    @endcan
                                </div>
                                @include('orders.invoice.modals')
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="8" class="text-center py-10">
                            <div class="text-muted">
                                <i class="bi bi-inbox fs-3x mb-3 d-block text-gray-300"></i>
                                <span class="fw-semibold fs-4">{{ __('payments.no_invoices_found') }}</span>
                                <p class="text-gray-400 fs-6">{{ __('payments.no_invoices_message') }}</p>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
    <x-liveblade-pagination
        :paginator="$invoices"
        id="invoicePagination"
        route="{{ route('invoices.index') }}"
        search-input-id="invoiceSearchInput"
        :show-info="true"
        :show-per-page="true"
        :per-page-options="[15, 25, 50, 100]"
        data-lb-component="reloadInvoiceComponent"
    />
</div>
@endcan