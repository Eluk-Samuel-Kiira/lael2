{{-- resources/views/department/employee-payment/component.blade.php --}}
@can('view employee payment')
<div class="card-body py-4" id="reloadPaymentComponent">

    <!-- BEGIN: STATISTICS CARDS -->
    <div class="row g-5 g-xl-8 mb-6">
        <!-- Total Payments -->
        <div class="col-xl-3">
            <div class="card card-xl-stretch mb-5 mb-xl-8 bg-light-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-white">
                                <i class="ki-duotone ki-dollar fs-2x text-primary">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-2x" id="total_payments">{{ number_format($statistics['total_payments']) }}</span>
                            <span class="opacity-75">{{ __('payments.total_payments') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Amount -->
        <div class="col-xl-3">
            <div class="card card-xl-stretch mb-5 mb-xl-8 bg-light-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-white">
                                <i class="ki-duotone ki-chart-simple fs-2x text-success">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-2x">{{ number_format($statistics['total_amount'], 2) }}</span>
                            <span class="opacity-75">{{ __('payments.total_amount') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="col-xl-3">
            <div class="card card-xl-stretch mb-5 mb-xl-8 bg-light-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-white">
                                <i class="ki-duotone ki-calendar-8 fs-2x text-warning">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-2x">{{ $statistics['this_month_count'] }}</span>
                            <span class="opacity-75">{{ __('payments.this_month') }}</span>
                            <span class="fw-bold fs-6">{{ number_format($statistics['this_month_amount'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments -->
        <div class="col-xl-3">
            <div class="card card-xl-stretch mb-5 mb-xl-8 bg-light-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-white">
                                <i class="ki-duotone ki-time fs-2x text-danger">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="fw-bold fs-2x">{{ $statistics['pending_count'] }}</span>
                            <span class="opacity-75">{{ __('payments.pending_payments') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Stats Row -->
    <div class="row g-5 g-xl-8 mb-6">
        <div class="col-xl-3">
            <div class="card card-dashed">
                <div class="card-body d-flex align-items-center">
                    <i class="ki-duotone ki-chart-pie fs-2x text-info me-3"></i>
                    <div>
                        <span class="text-gray-600 fw-bold d-block">{{ __('payments.total_gross') }}</span>
                        <span class="fw-bold fs-3">{{ number_format($statistics['total_gross'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-dashed">
                <div class="card-body d-flex align-items-center">
                    <i class="ki-duotone ki-chart-simple fs-2x text-warning me-3"></i>
                    <div>
                        <span class="text-gray-600 fw-bold d-block">{{ __('payments.total_tax') }}</span>
                        <span class="fw-bold fs-3">{{ number_format($statistics['total_tax'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-dashed">
                <div class="card-body d-flex align-items-center">
                    <i class="ki-duotone ki-arrow-rotate-left fs-2x text-success me-3"></i>
                    <div>
                        <span class="text-gray-600 fw-bold d-block">{{ __('payments.total_advance_deductions') }}</span>
                        <span class="fw-bold fs-3">{{ number_format($statistics['total_advance_deductions'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card card-dashed">
                <div class="card-body d-flex align-items-center">
                    <i class="ki-duotone ki-chart-line-up fs-2x text-primary me-3"></i>
                    <div>
                        <span class="text-gray-600 fw-bold d-block">{{ __('payments.average_payment') }}</span>
                        <span class="fw-bold fs-3">{{ number_format($statistics['avg_payment'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BEGIN: FILTER CARD -->
    <div class="card card-flush mb-6">
        <div class="card-header">
            <div class="card-title">
                <h3 class="card-label">{{ __('payments.filter_payments') }}</h3>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-sm btn-light" onclick="resetFilters()">
                    <i class="ki-duotone ki-cross-circle fs-2 me-2"></i>
                    {{ __('payments.reset') }}
                </button>
            </div>
        </div>
        <div class="card-body">
            <form id="paymentFilterForm" method="GET" action="{{ route('payment.index') }}">
                <div class="row g-5">
                    <!-- Status Filter -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('payments.status') }}</label>
                        <select name="status" class="form-select" data-control="select2" data-placeholder="All Status">
                            <option value="">{{ __('payments.all_status') }}</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('payments.pending') }}</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>{{ __('payments.completed') }}</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>{{ __('payments.failed') }}</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>{{ __('payments.cancelled') }}</option>
                        </select>
                    </div>

                    <!-- Payment Type Filter -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('payments.payment_type') }}</label>
                        <select name="payment_type" class="form-select" data-control="select2" data-placeholder="All Types">
                            <option value="">{{ __('payments.all_types') }}</option>
                            <option value="salary" {{ request('payment_type') == 'salary' ? 'selected' : '' }}>{{ __('payments.salary') }}</option>
                            <option value="allowance" {{ request('payment_type') == 'allowance' ? 'selected' : '' }}>{{ __('payments.allowance') }}</option>
                            <option value="bonus" {{ request('payment_type') == 'bonus' ? 'selected' : '' }}>{{ __('payments.bonus') }}</option>
                            <option value="overtime" {{ request('payment_type') == 'overtime' ? 'selected' : '' }}>{{ __('payments.overtime') }}</option>
                            <option value="advance" {{ request('payment_type') == 'advance' ? 'selected' : '' }}>{{ __('payments.advance') }}</option>
                            <option value="other" {{ request('payment_type') == 'other' ? 'selected' : '' }}>{{ __('payments.other') }}</option>
                        </select>
                    </div>

                    <!-- Employee Filter -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('payments.employee') }}</label>
                        <select name="employee_id" class="form-select" data-control="select2" data-placeholder="All Employees">
                            <option value="">{{ __('payments.all_employees') }}</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Payment Method Filter -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('payments.payment_method') }}</label>
                        <select name="payment_method_id" class="form-select" data-control="select2" data-placeholder="All Methods">
                            <option value="">{{ __('payments.all_methods') }}</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method->id }}" {{ request('payment_method_id') == $method->id ? 'selected' : '' }}>
                                    {{ $method->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('payments.date_from') }}</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('payments.date_to') }}</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>

                    <!-- Amount Range -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('payments.min_amount') }}</label>
                        <input type="number" name="min_amount" class="form-control" step="0.01" value="{{ request('min_amount') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">{{ __('payments.max_amount') }}</label>
                        <input type="number" name="max_amount" class="form-control" step="0.01" value="{{ request('max_amount') }}">
                    </div>

                    <!-- Per Page -->
                    <div class="col-md-3">
                        <label class="form-label">{{ __('payments.per_page') }}</label>
                        <select name="per_page" class="form-select">
                            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <div class="col-md-12 mt-4">
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="ki-duotone ki-filter fs-3 me-2"></i>
                                {{ __('payments.apply_filters') }}
                            </button>
                            <a href="{{ route('payment.index') }}" class="btn btn-light">
                                <i class="ki-duotone ki-cross-circle fs-3 me-2"></i>
                                {{ __('payments.clear_filters') }}
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments Table -->
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
                                <div class="badge badge-light fw-bold">{{__('auth._id')}}{{ $payment->id }}</div>
                            </td>
                            <td>{{ $payment->employee->first_name.' '.$payment->employee->last_name ?? 'N/A' }}</td>
                            <td>
                                {!! $payment->payment_type_badge !!}
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
                            <td>{{ number_format($payment->gross_amount, 2) }}</td>
                            <td>
                                <div class="badge badge-light fw-bold">{{ number_format($payment->net_amount, 2) }}</div>
                            </td>
                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                            <td>
                                @if($payment->status === 'completed')
                                    {!! $payment->status_badge !!}
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

                                @include('department.employee-payment.edit')
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="10" class="text-center py-5">
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

    @if(isset($payments) && $payments instanceof \Illuminate\Pagination\LengthAwarePaginator && $payments->hasPages())
        <div class="d-flex flex-stack flex-wrap pt-10">
            <div class="fs-6 fw-semibold text-gray-700">
                {{ __('Showing') }} <span class="fw-bold">{{ $payments->firstItem() }}</span> 
                {{ __('to') }} <span class="fw-bold">{{ $payments->lastItem() }}</span> 
                {{ __('of') }} <span class="fw-bold">{{ $payments->total() }}</span> {{ __('entries') }}
            </div>
            
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if($payments->onFirstPage())
                    <li class="page-item previous disabled">
                        <a href="#" class="page-link"><i class="previous"></i></a>
                    </li>
                @else
                    <li class="page-item previous">
                        <a href="{{ $payments->previousPageUrl() }}" class="page-link"><i class="previous"></i></a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach($payments->getUrlRange(max(1, $payments->currentPage() - 2), min($payments->lastPage(), $payments->currentPage() + 2)) as $page => $url)
                    @if($page == $payments->currentPage())
                        <li class="page-item active">
                            <a href="#" class="page-link">{{ $page }}</a>
                        </li>
                    @else
                        <li class="page-item">
                            <a href="{{ $url }}" class="page-link">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if($payments->hasMorePages())
                    <li class="page-item next">
                        <a href="{{ $payments->nextPageUrl() }}" class="page-link"><i class="next"></i></a>
                    </li>
                @else
                    <li class="page-item next disabled">
                        <a href="#" class="page-link"><i class="next"></i></a>
                    </li>
                @endif
            </ul>
        </div>
    @elseif(isset($payments) && $payments instanceof \Illuminate\Pagination\LengthAwarePaginator && $payments->total() > 0)
        <div class="d-flex flex-stack flex-wrap pt-10">
            <div class="fs-6 fw-semibold text-gray-700">
                {{ __('Showing') }} <span class="fw-bold">{{ $payments->firstItem() }}</span> 
                {{ __('to') }} <span class="fw-bold">{{ $payments->lastItem() }}</span> 
                {{ __('of') }} <span class="fw-bold">{{ $payments->total() }}</span> {{ __('entries') }}
            </div>
        </div>
    @endif

</div>
@endcan

<script>
function resetFilters() {
    window.location.href = '{{ route("payment.index") }}';
}

