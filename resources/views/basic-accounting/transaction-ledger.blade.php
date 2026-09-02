<x-app-layout>
    @section('title', __('accounting.transaction_ledger'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('accounting.transaction_ledger') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.transaction-ledger') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.transaction_ledger') }}</li>
                </ul>
            </div>
            
            <div class="d-flex align-items-center gap-2 gap-lg-3 flex-wrap">
                <!-- Location Filter -->
                <div class="d-flex align-items-center bg-light-primary rounded-3 px-3 py-2">
                    <i class="ki-duotone ki-geolocation fs-2 text-primary me-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0 w-150px w-md-200px" 
                            name="location_id" 
                            data-control="select2" 
                            id="locationFilter" 
                            onchange="applyFilter()"
                            data-placeholder="{{ __('pagination.all_locations') }}">
                        <option value="">{{ __('pagination.all_locations') }}</option>
                        @foreach($locations ?? [] as $location)
                            <option value="{{ $location->id }}" {{ ($filters['location_id'] ?? '') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Export Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle d-flex align-items-center gap-2" 
                            type="button" 
                            data-bs-toggle="dropdown" 
                            aria-expanded="false">
                        <i class="ki-duotone ki-file-down fs-2"></i>
                        <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'transactionsTable', filename: 'transaction_ledger', sheetName: 'Transactions', excludeColumns: [9]})">
                                <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                {{ __('accounting.export_to_excel') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'transactionsTable', filename: 'transaction_ledger', format: 'csv', excludeColumns: [9]})">
                                <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                {{ __('accounting.export_to_csv') }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Filters Card -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-filter fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.filter_transactions') }}
                        </h3>
                        <div class="card-toolbar">
                            <a href="{{ route('accounting.transaction-ledger') }}" class="btn btn-sm btn-light">
                                <i class="ki-duotone ki-arrow-rotate-right fs-2 me-1"></i>
                                {{ __('accounting.reset') }}
                            </a>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form id="filterForm" method="GET" class="row g-5">
                            <!-- Date Range -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="ki-duotone ki-calendar-8 fs-2 me-1 text-gray-500"></i>
                                    {{ __('accounting.start_date') }}
                                </label>
                                <input type="date" name="start_date" class="form-control form-control-solid" value="{{ $filters['start_date'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="ki-duotone ki-calendar-8 fs-2 me-1 text-gray-500"></i>
                                    {{ __('accounting.end_date') }}
                                </label>
                                <input type="date" name="end_date" class="form-control form-control-solid" value="{{ $filters['end_date'] }}">
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="ki-duotone ki-wallet fs-2 me-1 text-gray-500"></i>
                                    {{ __('accounting.payment_method') }}
                                </label>
                                <select name="payment_method_id" class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('accounting.all_methods') }}">
                                    <option value="">{{ __('accounting.all_methods') }}</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}" {{ ($filters['payment_method_id'] ?? '') == $method->id ? 'selected' : '' }}>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Transaction Type -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="ki-duotone ki-tag fs-2 me-1 text-gray-500"></i>
                                    {{ __('accounting.transaction_type') }}
                                </label>
                                <select name="transaction_type" class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('accounting.all_types') }}">
                                    <option value="">{{ __('accounting.all_types') }}</option>
                                    @foreach($transactionTypes as $type)
                                        <option value="{{ $type }}" {{ ($filters['transaction_type'] ?? '') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Status -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="ki-duotone ki-status fs-2 me-1 text-gray-500"></i>
                                    {{ __('accounting.status') }}
                                </label>
                                <select name="status" class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('accounting.all_statuses') }}">
                                    <option value="">{{ __('accounting.all_statuses') }}</option>
                                    <option value="COMPLETED" {{ ($filters['status'] ?? '') == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                                    <option value="PENDING" {{ ($filters['status'] ?? '') == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                                    <option value="FAILED" {{ ($filters['status'] ?? '') == 'FAILED' ? 'selected' : '' }}>FAILED</option>
                                    <option value="CANCELLED" {{ ($filters['status'] ?? '') == 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                                    <option value="REVERSED" {{ ($filters['status'] ?? '') == 'REVERSED' ? 'selected' : '' }}>REVERSED</option>
                                </select>
                            </div>
                            
                            <!-- NEW: User Filter -->
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">
                                    <i class="ki-duotone ki-user fs-2 me-1 text-gray-500"></i>
                                    {{ __('accounting.processed_by') }}
                                </label>
                                <select name="user_id" class="form-select form-select-solid" data-control="select2" data-placeholder="{{ __('accounting.all_users') }}">
                                    <option value="">{{ __('accounting.all_users') }}</option>
                                    @foreach($users ?? [] as $user)
                                        <option value="{{ $user->id }}" {{ ($filters['user_id'] ?? '') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Buttons -->
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                    {{ __('accounting.apply_filters') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Summary Stats -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Transactions -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $transactions->total() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.total_pages') }}</span>
                                    <span class="fw-bold text-gray-700">{{ $transactions->lastPage() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Amount -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ number_format($totalAmount, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_amount') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.total_value') }}</span>
                                    <span class="fw-bold text-gray-700">{{ number_format($totalAmount, 2) }} {{ currency_symbol() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Average Transaction -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ number_format($averageAmount, 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.average_transaction') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.per_transaction') }}</span>
                                    <span class="fw-bold text-gray-700">{{ number_format($averageAmount, 2) }} {{ currency_symbol() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Date Range -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800 fs-3">
                                        {{ \Carbon\Carbon::parse($filters['start_date'])->format('M d') }} - 
                                        {{ \Carbon\Carbon::parse($filters['end_date'])->format('M d, Y') }}
                                    </span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.date_range') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.selected_period') }}</span>
                                    <span class="fw-bold text-gray-700">{{ $transactions->count() }} {{ __('accounting.transactions') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions Table -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-table fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.transaction_details') }}
                        </h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" id="searchTransactions" 
                                       class="form-control form-control-solid w-250px ps-10" 
                                       placeholder="{{ __('accounting.search_transactions') }}">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="transactionsTable">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-100px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.transaction_ref') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance_after') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.status') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @forelse($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="fs-7 fw-bold text-gray-800">{{ $transaction->transaction_date->format('M d, Y') }}</span>
                                            <span class="fs-8 text-gray-500 d-block">{{ $transaction->transaction_date->format('H:i:s') }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-600 fw-bold">{{ $transaction->transaction_ref }}</span>
                                            @if($transaction->receipt_number)
                                                <span class="fs-8 text-gray-500 d-block">
                                                    <i class="ki-duotone ki-receipt fs-1 me-1"></i>
                                                    {{ $transaction->receipt_number }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fs-7 text-gray-800 fw-bold">{{ $transaction->paymentMethod->name ?? 'N/A' }}</span>
                                                @if($transaction->user)
                                                    <span class="fs-8 text-gray-500">
                                                        <i class="ki-duotone ki-user fs-1 me-1"></i>
                                                        {{ $transaction->user->name }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $typeColors = [
                                                    'DEPOSIT' => 'success',
                                                    'WITHDRAWAL' => 'danger',
                                                    'TRANSFER_IN' => 'info',
                                                    'TRANSFER_OUT' => 'warning',
                                                    'FEE' => 'secondary',
                                                    'REFUND' => 'primary',
                                                    'ADJUSTMENT' => 'info',
                                                    'RECONCILIATION' => 'dark',
                                                ];
                                                $typeColor = $typeColors[$transaction->transaction_type] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-light-{{ $typeColor }} py-2 px-3">
                                                {{ $transaction->transaction_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-secondary py-2 px-3">
                                                {{ $transaction->transaction_category }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-800">{{ Str::limit($transaction->description, 40) }}</span>
                                            @if($transaction->notes)
                                                <span class="fs-8 text-gray-500 d-block">
                                                    <i class="ki-duotone ki-information fs-1 me-1"></i>
                                                    {{ Str::limit($transaction->notes, 30) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @php
                                                $amountClasses = [
                                                    'DEPOSIT' => 'text-success',
                                                    'TRANSFER_IN' => 'text-success',
                                                    'REFUND' => 'text-success',
                                                    'WITHDRAWAL' => 'text-danger',
                                                    'TRANSFER_OUT' => 'text-danger',
                                                    'FEE' => 'text-danger',
                                                    'ADJUSTMENT' => 'text-info',
                                                    'RECONCILIATION' => 'text-dark',
                                                ];
                                                $amountClass = $amountClasses[$transaction->transaction_type] ?? 'text-dark';
                                            @endphp
                                            <span class="fs-6 fw-bold {{ $amountClass }}">
                                                {{ number_format($transaction->amount, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-7 text-gray-800 fw-bold">
                                                {{ number_format($transaction->balance_after, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusColors = [
                                                    'COMPLETED' => 'success',
                                                    'PENDING' => 'warning',
                                                    'FAILED' => 'danger',
                                                    'CANCELLED' => 'secondary',
                                                    'REVERSED' => 'dark',
                                                ];
                                                $statusColor = $statusColors[$transaction->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-light-{{ $statusColor }} py-2 px-3">
                                                {{ $transaction->status }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-light btn-active-light-primary view-transaction-btn" 
                                                    data-transaction-id="{{ $transaction->id }}">
                                                <i class="ki-duotone ki-eye fs-2 me-1"></i>
                                                {{ __('accounting.view') }}
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-10">
                                            <div class="text-muted fs-6">{{ __('accounting.no_transactions_found') }}</div>
                                            <div class="text-gray-500 fs-7 mt-2">{{ __('accounting.try_adjusting_filters') }}</div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        @if($transactions instanceof \Illuminate\Pagination\AbstractPaginator && $transactions->hasPages())
                            <div class="mt-6 d-flex justify-content-center">
                                {{ $transactions->appends(request()->query())->links('pagination::bootstrap-5') }}
                            </div>
                        @endif
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fw-bold">
                        <i class="ki-duotone ki-information fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        {{ __('accounting.transaction_details') }}
                    </h2>
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body">
                    <!-- Transaction Reference -->
                    <div class="d-flex align-items-center bg-light rounded-3 p-5 mb-8">
                        <div class="d-flex flex-column">
                            <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.transaction_ref') }}</span>
                            <span class="fs-4 fw-bold text-gray-800" id="modalTransactionRef">-</span>
                        </div>
                        <div class="d-flex flex-column ms-8">
                            <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.receipt_number') }}</span>
                            <span class="fs-4 fw-bold text-gray-800" id="modalReceiptNumber">-</span>
                        </div>
                        <div class="d-flex flex-column ms-8">
                            <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.date') }}</span>
                            <span class="fs-4 fw-bold text-gray-800" id="modalDate">-</span>
                        </div>
                    </div>
                    
                    <!-- Amount Details -->
                    <div class="row g-5 mb-8">
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-{{ request()->get('status', 'COMPLETED') === 'COMPLETED' ? 'success' : 'primary' }} h-100">
                                <div class="card-body text-center py-5">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.amount') }}</span>
                                    <div class="fs-2hx fw-bold" id="modalAmount">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-info h-100">
                                <div class="card-body text-center py-5">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.balance_before') }}</span>
                                    <div class="fs-2hx fw-bold text-gray-800" id="modalBalanceBefore">-</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-flush bg-light-primary h-100">
                                <div class="card-body text-center py-5">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.balance_after') }}</span>
                                    <div class="fs-2hx fw-bold text-gray-800" id="modalBalanceAfter">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transaction Info -->
                    <div class="row g-5 mb-8">
                        <div class="col-md-3">
                            <div class="d-flex flex-column">
                                <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.payment_method') }}</span>
                                <span class="fs-6 fw-bold text-gray-800" id="modalPaymentMethod">-</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex flex-column">
                                <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.type') }}</span>
                                <span id="modalTransactionType">-</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex flex-column">
                                <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.category') }}</span>
                                <span class="fs-6 fw-bold text-gray-800" id="modalCategory">-</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex flex-column">
                                <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.status') }}</span>
                                <span id="modalStatus">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-8">
                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.description') }}</span>
                        <div class="bg-light rounded-3 p-4 mt-2">
                            <span class="fs-6 text-gray-800" id="modalDescription">-</span>
                        </div>
                    </div>
                    
                    <!-- User Information -->
                    <div class="row g-5 mb-8">
                        <div class="col-md-6">
                            <div class="d-flex flex-column">
                                <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.processed_by') }}</span>
                                <span class="fs-6 fw-bold text-gray-800" id="modalProcessedBy">-</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column">
                                <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.customer') }}</span>
                                <span class="fs-6 fw-bold text-gray-800" id="modalCustomer">-</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reference Information -->
                    <div class="mb-8">
                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.reference_information') }}</span>
                        <div class="table-responsive mt-2">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th class="w-50">{{ __('accounting.reference_table') }}</th>
                                        <td class="w-50" id="modalReferenceTable">-</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('accounting.reference_id') }}</th>
                                        <td id="modalReferenceId">-</td>
                                    </tr>
                                    <tr id="externalReferenceRow" style="display: none;">
                                        <th>{{ __('accounting.external_reference') }}</th>
                                        <td id="modalExternalReference">-</td>
                                    </tr>
                                    <tr id="bankReferenceRow" style="display: none;">
                                        <th>{{ __('accounting.bank_reference') }}</th>
                                        <td id="modalBankReference">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Metadata -->
                    <div class="mb-0">
                        <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.metadata') }}</span>
                        <div class="mt-2">
                            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="metadataTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#metadataTab1">{{ __('accounting.formatted_view') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-bs-toggle="tab" href="#metadataTab2">{{ __('accounting.raw_json') }}</a>
                                </li>
                            </ul>
                            
                            <div class="tab-content" id="metadataContent">
                                <div class="tab-pane fade show active" id="metadataTab1" role="tabpanel">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="formattedMetadataTable">
                                            <thead>
                                                <tr>
                                                    <th class="w-50">{{ __('accounting.field') }}</th>
                                                    <th class="w-50">{{ __('accounting.value') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="metadataTab2" role="tabpanel">
                                    <div class="card">
                                        <div class="card-body">
                                            <pre class="m-0" id="rawMetadata" style="max-height: 300px; overflow: auto;"></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('accounting.close') }}</button>
                    <button type="button" class="btn btn-primary" onclick="printTransactionDetails()">
                        <i class="ki-duotone ki-printer fs-2 me-2"></i>
                        {{ __('accounting.print') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        // Apply filter with location and user
        function applyFilter() {
            const locationId = document.getElementById('locationFilter').value;
            const currentUrl = new URL(window.location.href);
            
            if (locationId) {
                currentUrl.searchParams.set('location_id', locationId);
            } else {
                currentUrl.searchParams.delete('location_id');
            }
            
            window.location.href = currentUrl.toString();
        }
        
        // Search functionality
        document.getElementById('searchTransactions').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#transactionsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Handle view transaction button clicks
        document.addEventListener('DOMContentLoaded', function() {
            const viewButtons = document.querySelectorAll('.view-transaction-btn');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const transactionId = this.getAttribute('data-transaction-id');
                    loadTransactionDetails(transactionId);
                });
            });
            
            // Initialize Select2
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#locationFilter').select2({
                    placeholder: "{{ __('pagination.all_locations') }}",
                    allowClear: true,
                    width: '200px'
                });
            }
        });
        
        // Load transaction details via AJAX
        function loadTransactionDetails(transactionId) {
            clearModalData();
            document.getElementById('modalTransactionRef').textContent = 'Loading...';
            
            fetch(`/accounting/transaction-ledger/details/${transactionId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    populateModal(data);
                    const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error loading transaction details:', error);
                    document.getElementById('modalTransactionRef').textContent = 'Error loading details';
                    alert('Failed to load transaction details. Please try again.');
                });
        }
        
        function clearModalData() {
            // Clear all modal fields
            ['modalTransactionRef', 'modalReceiptNumber', 'modalDate', 'modalAmount', 
             'modalBalanceBefore', 'modalBalanceAfter', 'modalPaymentMethod', 
             'modalCategory', 'modalDescription', 'modalProcessedBy', 'modalCustomer',
             'modalReferenceTable', 'modalReferenceId', 'modalExternalReference', 
             'modalBankReference', 'rawMetadata'].forEach(id => {
                document.getElementById(id).textContent = '-';
            });
            
            document.getElementById('modalTransactionType').innerHTML = '-';
            document.getElementById('modalStatus').innerHTML = '-';
            
            document.getElementById('externalReferenceRow').style.display = 'none';
            document.getElementById('bankReferenceRow').style.display = 'none';
            
            const formattedTable = document.getElementById('formattedMetadataTable').getElementsByTagName('tbody')[0];
            formattedTable.innerHTML = '';
        }
        
        function populateModal(data) {
            const transaction = data.transaction;
            const customer = data.customer;
            const paymentMethod = data.payment_method;
            const currency = data.currency;
            
            document.getElementById('modalTransactionRef').textContent = transaction.transaction_ref || '-';
            document.getElementById('modalReceiptNumber').textContent = transaction.receipt_number || '-';
            document.getElementById('modalDate').textContent = transaction.transaction_date ? new Date(transaction.transaction_date).toLocaleString() : '-';
            
            const currencySymbol = '{{ currency_symbol() }}';
            const isPositive = ['DEPOSIT', 'TRANSFER_IN', 'REFUND'].includes(transaction.transaction_type);
            const amountClass = isPositive ? 'text-success' : 'text-danger';
            const amountSign = isPositive ? '+' : '-';
            
            document.getElementById('modalAmount').innerHTML = `
                <span class="${amountClass}">
                    ${amountSign}${parseFloat(transaction.amount).toFixed(2)} ${currencySymbol}
                </span>
            `;
            
            document.getElementById('modalBalanceBefore').textContent = 
                `${parseFloat(transaction.balance_before).toFixed(2)} ${currencySymbol}`;
            document.getElementById('modalBalanceAfter').textContent = 
                `${parseFloat(transaction.balance_after).toFixed(2)} ${currencySymbol}`;
            
            document.getElementById('modalPaymentMethod').textContent = paymentMethod?.name || '-';
            
            // Transaction type badge
            const typeColors = {
                'DEPOSIT': 'success',
                'WITHDRAWAL': 'danger',
                'TRANSFER_IN': 'info',
                'TRANSFER_OUT': 'warning',
                'FEE': 'secondary',
                'REFUND': 'primary',
                'ADJUSTMENT': 'info',
                'RECONCILIATION': 'dark'
            };
            const typeColor = typeColors[transaction.transaction_type] || 'secondary';
            document.getElementById('modalTransactionType').innerHTML = `
                <span class="badge badge-light-${typeColor} py-2 px-3">${transaction.transaction_type}</span>
            `;
            
            document.getElementById('modalCategory').textContent = transaction.transaction_category || '-';
            
            // Status badge
            const statusColors = {
                'COMPLETED': 'success',
                'PENDING': 'warning',
                'FAILED': 'danger',
                'CANCELLED': 'secondary',
                'REVERSED': 'dark'
            };
            const statusColor = statusColors[transaction.status] || 'secondary';
            document.getElementById('modalStatus').innerHTML = `
                <span class="badge badge-light-${statusColor} py-2 px-3">${transaction.status}</span>
            `;
            
            document.getElementById('modalDescription').textContent = transaction.description || '-';
            document.getElementById('modalProcessedBy').textContent = transaction.user?.name || 'System';
            document.getElementById('modalCustomer').textContent = customer ? `${customer.first_name || ''} ${customer.last_name || ''}`.trim() : '-';
            
            // Reference information
            document.getElementById('modalReferenceTable').textContent = transaction.reference_table || '-';
            document.getElementById('modalReferenceId').textContent = transaction.reference_id || '-';
            
            if (transaction.external_reference) {
                document.getElementById('externalReferenceRow').style.display = '';
                document.getElementById('modalExternalReference').textContent = transaction.external_reference;
            }
            
            if (transaction.bank_reference) {
                document.getElementById('bankReferenceRow').style.display = '';
                document.getElementById('modalBankReference').textContent = transaction.bank_reference;
            }
            
            // Handle metadata
            let metadata = {};
            try {
                metadata = transaction.metadata ? 
                    (typeof transaction.metadata === 'string' ? JSON.parse(transaction.metadata) : transaction.metadata) 
                    : {};
            } catch (e) {
                metadata = { error: 'Failed to parse metadata' };
            }
            
            document.getElementById('rawMetadata').textContent = JSON.stringify(metadata, null, 2);
            
            // Populate formatted metadata
            const formattedTable = document.getElementById('formattedMetadataTable').getElementsByTagName('tbody')[0];
            formattedTable.innerHTML = '';
            
            for (const key in metadata) {
                if (metadata[key] !== null && metadata[key] !== undefined) {
                    const row = formattedTable.insertRow();
                    const fieldCell = row.insertCell();
                    const valueCell = row.insertCell();
                    
                    fieldCell.innerHTML = `<strong>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</strong>`;
                    
                    let value = metadata[key];
                    if (typeof value === 'object' && value !== null) {
                        value = JSON.stringify(value, null, 2);
                    }
                    valueCell.textContent = value;
                }
            }
        }
        
        function printTransactionDetails() {
            const printContent = `
                <div style="font-family: Arial, sans-serif; padding: 20px;">
                    <h2>Transaction Details</h2>
                    <p><strong>Transaction Ref:</strong> ${document.getElementById('modalTransactionRef').textContent}</p>
                    <p><strong>Amount:</strong> ${document.getElementById('modalAmount').textContent}</p>
                    <p><strong>Date:</strong> ${new Date().toLocaleDateString()}</p>
                </div>
            `;
            
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.print();
        }
    </script>
    @endpush
    
    @endsection
</x-app-layout>