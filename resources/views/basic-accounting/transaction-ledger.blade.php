<x-app-layout>
    @section('title', __('accounting.transaction_ledger'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('accounting.transaction_ledger')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.transaction-ledger') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('accounting.transaction_ledger')}}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
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
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                            onclick="exportCurrentPage({tableId: 'transactionsTable', filename: 'transaction_ledger', sheetName: 'Transactions', excludeColumns: [9], includeHidden: true})">
                                <i class="ki-duotone ki-file-table fs-2 me-2 text-warning"></i>
                                {{ __('accounting.export_all_including_filtered') }}
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
                
                <!-- Filters -->
                <div class="card mb-8">
                    <div class="card-body">
                        <form id="filterForm" method="GET" class="row g-5">
                            <!-- Date Range -->
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] }}">
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.payment_method') }}</label>
                                <select name="payment_method_id" class="form-select">
                                    <option value="">{{ __('accounting.all_methods') }}</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}" {{ $filters['payment_method_id'] == $method->id ? 'selected' : '' }}>
                                            {{ $method->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Transaction Type -->
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.transaction_type') }}</label>
                                <select name="transaction_type" class="form-select">
                                    <option value="">{{ __('accounting.all_types') }}</option>
                                    @foreach($transactionTypes as $type)
                                        <option value="{{ $type }}" {{ $filters['transaction_type'] == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Status -->
                            <div class="col-md-3">
                                <label class="form-label">{{ __('accounting.status') }}</label>
                                <select name="status" class="form-select">
                                    <option value="">{{ __('accounting.all_statuses') }}</option>
                                    <option value="COMPLETED" {{ $filters['status'] == 'COMPLETED' ? 'selected' : '' }}>COMPLETED</option>
                                    <option value="PENDING" {{ $filters['status'] == 'PENDING' ? 'selected' : '' }}>PENDING</option>
                                    <option value="FAILED" {{ $filters['status'] == 'FAILED' ? 'selected' : '' }}>FAILED</option>
                                    <option value="CANCELLED" {{ $filters['status'] == 'CANCELLED' ? 'selected' : '' }}>CANCELLED</option>
                                </select>
                            </div>
                            
                            <!-- Buttons -->
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="ki-duotone ki-filter fs-2"></i> {{ __('accounting.apply_filters') }}
                                </button>
                                <a href="{{ route('accounting.transaction-ledger') }}" class="btn btn-secondary">
                                    {{ __('accounting.reset') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Summary Stats -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Transactions -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_transactions') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">{{ $transactions->total() }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.transactions') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Amount -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_amount') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    @php
                                        $totalAmount = $transactions->sum('amount');
                                    @endphp
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">${{ number_format($totalAmount, 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.total_value') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Average Transaction -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.average_transaction') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    @php
                                        $averageAmount = $transactions->count() > 0 ? $totalAmount / $transactions->count() : 0;
                                    @endphp
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">${{ number_format($averageAmount, 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.per_transaction') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Date Range -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.date_range') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">
                                        {{ \Carbon\Carbon::parse($filters['start_date'])->format('M d') }} - {{ \Carbon\Carbon::parse($filters['end_date'])->format('M d') }}
                                    </span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.selected_period') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.transaction_details') }}</h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" id="searchTransactions" class="form-control form-control-solid w-250px ps-10" placeholder="{{ __('accounting.search_transactions') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="transactionsTable">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-100px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.transaction_ref') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance_after') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="fs-7 fw-bold text-gray-800">{{ $transaction->transaction_date->format('M d, Y') }}</span>
                                            <span class="fs-8 text-gray-500 d-block">{{ $transaction->transaction_date->format('H:i') }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-600">{{ $transaction->transaction_ref }}</span>
                                            @if($transaction->receipt_number)
                                                <span class="fs-8 text-gray-500 d-block">{{ $transaction->receipt_number }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-800">{{ $transaction->paymentMethod->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $transaction->transaction_type === 'DEPOSIT' ? 'success' : ($transaction->transaction_type === 'WITHDRAWAL' ? 'danger' : 'info') }}">
                                                {{ $transaction->transaction_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-600">{{ $transaction->transaction_category }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-800">{{ Str::limit($transaction->description, 40) }}</span>
                                            @if($transaction->notes)
                                                <span class="fs-8 text-gray-500 d-block">{{ Str::limit($transaction->notes, 30) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND']) ? 'text-success' : 'text-danger' }}">
                                                {{ $transaction->paymentMethod->currency->symbol ?? '$' }}{{ number_format($transaction->amount, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-7 text-gray-800 fw-bold">
                                                {{ $transaction->paymentMethod->currency->symbol ?? '$' }}{{ number_format($transaction->balance_after, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $transaction->status === 'COMPLETED' ? 'success' : ($transaction->status === 'PENDING' ? 'warning' : 'danger') }}">
                                                {{ $transaction->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-light btn-active-light-primary view-transaction-btn" 
                                                    data-transaction-id="{{ $transaction->id }}">
                                                {{ __('accounting.view') }}
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-5">
                            <div class="d-flex align-items-center">
                                <span class="text-gray-700 fs-7">
                                    {{ __('accounting.showing') }} {{ $transactions->firstItem() ?? 0 }} {{ __('accounting.to') }} {{ $transactions->lastItem() ?? 0 }} {{ __('accounting.of') }} {{ $transactions->total() }} {{ __('accounting.entries') }}
                                </span>
                            </div>
                            <div>
                                {{ $transactions->links() }}
                            </div>
                        </div>
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
                    <h2 class="modal-title">{{ __('accounting.transaction_details') }}</h2>
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </div>
                </div>
                <div class="modal-body">
                    <!-- Basic Transaction Details -->
                    <div class="row mb-8">
                        <div class="col-md-6">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('accounting.transaction_ref') }}</span>
                                <span class="fs-5 fw-bold text-gray-800" id="modalTransactionRef"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('accounting.receipt_number') }}</span>
                                <span class="fs-5 fw-bold text-gray-800" id="modalReceiptNumber"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Amount Details -->
                    <div class="row mb-8">
                        <div class="col-md-4">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('accounting.amount') }}</span>
                                <span class="fs-4 fw-bold" id="modalAmount"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('accounting.balance_before') }}</span>
                                <span class="fs-4 fw-bold text-gray-800" id="modalBalanceBefore"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('accounting.balance_after') }}</span>
                                <span class="fs-4 fw-bold text-gray-800" id="modalBalanceAfter"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transaction Info -->
                    <div class="row mb-8">
                        <div class="col-md-4">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('accounting.payment_method') }}</span>
                                <span class="fs-6 fw-bold text-gray-800" id="modalPaymentMethod"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('accounting.type') }}</span>
                                <span class="badge fs-6" id="modalTransactionType"></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('accounting.category') }}</span>
                                <span class="fs-6 fw-bold text-gray-800" id="modalCategory"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="row mb-8">
                        <div class="col-12">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('accounting.description') }}</span>
                                <span class="fs-6 text-gray-800" id="modalDescription"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="row mb-8" id="modalNotesSection" style="display: none;">
                        <div class="col-12">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('accounting.notes') }}</span>
                                <span class="fs-6 text-gray-800" id="modalNotes"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Information -->
                    <div class="row mb-8" id="customerSection" style="display: none;">
                        <div class="col-12">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('Customer') }}</span>
                                <span class="fs-6 fw-bold text-gray-800" id="modalCustomer"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Processed By Information -->
                    <div class="row mb-8">
                        <div class="col-12">
                            <div class="d-flex flex-column">
                                <span class="fs-6 text-gray-600 mb-2">{{ __('Processed By') }}</span>
                                <span class="fs-6 fw-bold text-gray-800" id="modalProcessedBy"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Metadata Section -->
                    <div class="separator separator-dashed my-10"></div>
                    
                    <h4 class="mb-6">{{ __('Metadata') }}</h4>
                    
                    <!-- Tabs for different metadata views -->
                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" id="metadataTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#metadataTab1">{{ __('Formatted View') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#metadataTab2">{{ __('Raw JSON') }}</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#metadataTab3">{{ __('Reference Details') }}</a>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="metadataContent">
                        <!-- Formatted Metadata Tab -->
                        <div class="tab-pane fade show active" id="metadataTab1" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="formattedMetadataTable">
                                    <thead>
                                        <tr>
                                            <th class="w-50">{{ __('Field') }}</th>
                                            <th class="w-50">{{ __('Value') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Metadata rows will be inserted here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Raw JSON Tab -->
                        <div class="tab-pane fade" id="metadataTab2" role="tabpanel">
                            <div class="card">
                                <div class="card-body">
                                    <pre class="m-0" id="rawMetadata" style="max-height: 300px; overflow: auto;"></pre>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Reference Details Tab -->
                        <div class="tab-pane fade" id="metadataTab3" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="referenceTable">
                                    <tbody>
                                        <tr>
                                            <th class="w-50">{{ __('Reference Table') }}</th>
                                            <td class="w-50" id="modalReferenceTable"></td>
                                        </tr>
                                        <tr>
                                            <th class="w-50">{{ __('Reference ID') }}</th>
                                            <td class="w-50" id="modalReferenceId"></td>
                                        </tr>
                                        <tr id="externalReferenceRow" style="display: none;">
                                            <th class="w-50">{{ __('External Reference') }}</th>
                                            <td class="w-50" id="modalExternalReference"></td>
                                        </tr>
                                        <tr id="bankReferenceRow" style="display: none;">
                                            <th class="w-50">{{ __('Bank Reference') }}</th>
                                            <td class="w-50" id="modalBankReference"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" onclick="printTransactionDetails()">
                        <i class="ki-duotone ki-printer fs-2 me-2"></i>{{ __('Print') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        
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
        });
        
        // Load transaction details via AJAX
        function loadTransactionDetails(transactionId) {
            // Show loading state
            clearModalData();
            document.getElementById('modalTransactionRef').textContent = 'Loading...';
            
            // Make AJAX request
            fetch(`/accounting/transaction-ledger/details/${transactionId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
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
        
        // Clear modal data
        function clearModalData() {
            // Clear all modal fields
            document.getElementById('modalTransactionRef').textContent = '';
            document.getElementById('modalReceiptNumber').textContent = '';
            document.getElementById('modalAmount').textContent = '';
            document.getElementById('modalBalanceBefore').textContent = '';
            document.getElementById('modalBalanceAfter').textContent = '';
            document.getElementById('modalPaymentMethod').textContent = '';
            document.getElementById('modalTransactionType').innerHTML = '';
            document.getElementById('modalCategory').textContent = '';
            document.getElementById('modalDescription').textContent = '';
            document.getElementById('modalNotes').textContent = '';
            document.getElementById('modalCustomer').textContent = '';
            document.getElementById('modalProcessedBy').textContent = '';
            document.getElementById('modalReferenceTable').textContent = '';
            document.getElementById('modalReferenceId').textContent = '';
            document.getElementById('modalExternalReference').textContent = '';
            document.getElementById('modalBankReference').textContent = '';
            document.getElementById('rawMetadata').textContent = '';
            
            // Clear formatted metadata table
            const formattedTable = document.getElementById('formattedMetadataTable').getElementsByTagName('tbody')[0];
            formattedTable.innerHTML = '';
            
            // Hide sections
            document.getElementById('modalNotesSection').style.display = 'none';
            document.getElementById('customerSection').style.display = 'none';
            document.getElementById('externalReferenceRow').style.display = 'none';
            document.getElementById('bankReferenceRow').style.display = 'none';
        }
        
        // Populate modal with data
        function populateModal(data) {
            const transaction = data.transaction;
            const customer = data.customer;
            const paymentMethod = data.payment_method;
            const currency = data.currency;
            
            // Basic transaction details
            document.getElementById('modalTransactionRef').textContent = transaction.transaction_ref;
            document.getElementById('modalReceiptNumber').textContent = transaction.receipt_number || 'N/A';
            
            // Format amounts with proper currency
            const currencySymbol = currency?.symbol || '$';
            
            // Amount styling based on transaction type
            const isPositive = ['DEPOSIT', 'TRANSFER_IN', 'REFUND'].includes(transaction.transaction_type);
            const amountClass = isPositive ? 'text-success' : 'text-danger';
            const amountSign = isPositive ? '+' : '-';
            
            document.getElementById('modalAmount').innerHTML = `
                <span class="${amountClass}">
                    ${amountSign}${currencySymbol}${parseFloat(transaction.amount).toFixed(2)}
                </span>
            `;
            
            document.getElementById('modalBalanceBefore').textContent = 
                `${currencySymbol}${parseFloat(transaction.balance_before).toFixed(2)}`;
            
            document.getElementById('modalBalanceAfter').textContent = 
                `${currencySymbol}${parseFloat(transaction.balance_after).toFixed(2)}`;
            
            // Transaction info
            document.getElementById('modalPaymentMethod').textContent = 
                paymentMethod?.name || 'N/A';
            
            // Transaction type badge
            const typeBadgeClass = transaction.transaction_type === 'DEPOSIT' ? 'badge-light-success' : 
                                 transaction.transaction_type === 'WITHDRAWAL' ? 'badge-light-danger' : 'badge-light-info';
            document.getElementById('modalTransactionType').innerHTML = `
                <span class="badge ${typeBadgeClass}">${transaction.transaction_type}</span>
            `;
            
            document.getElementById('modalCategory').textContent = transaction.transaction_category;
            document.getElementById('modalDescription').textContent = transaction.description || 'N/A';
            
            // Notes
            if (transaction.notes) {
                document.getElementById('modalNotesSection').style.display = 'block';
                document.getElementById('modalNotes').textContent = transaction.notes;
            }
            
            // Customer information
            if (customer && (customer.first_name || customer.last_name)) {
                const customerName = `${customer.first_name || ''} ${customer.last_name || ''}`.trim();
                document.getElementById('customerSection').style.display = 'block';
                document.getElementById('modalCustomer').textContent = customerName;
            }
            
            // Processed By from metadata
            let processedByName = 'System';
            try {
                if (transaction.metadata) {
                    const metadata = typeof transaction.metadata === 'string' 
                        ? JSON.parse(transaction.metadata) 
                        : transaction.metadata;
                    
                    processedByName = metadata?.processed_by_name || 
                                   metadata?.receiver_name || 
                                   metadata?.cash_handler || 
                                   'System';
                }
            } catch (e) {
                console.error('Error parsing metadata:', e);
            }
            document.getElementById('modalProcessedBy').textContent = processedByName;
            
            // Reference information
            document.getElementById('modalReferenceTable').textContent = transaction.reference_table || 'N/A';
            document.getElementById('modalReferenceId').textContent = transaction.reference_id || 'N/A';
            
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
                console.error('Error parsing metadata:', e);
                metadata = { error: 'Failed to parse metadata' };
            }
            
            // Show raw JSON
            document.getElementById('rawMetadata').textContent = 
                JSON.stringify(metadata, null, 2);
            
            // Clear previous formatted metadata
            const formattedTable = document.getElementById('formattedMetadataTable').getElementsByTagName('tbody')[0];
            formattedTable.innerHTML = '';
            
            // Group metadata by category based on reference_table
            const metadataGroups = {};
            
            if (transaction.reference_table) {
                // Group common fields by reference table
                switch(transaction.reference_table) {
                    case 'employee_payments':
                        metadataGroups['Employee Details'] = [
                            { field: 'employee_id', label: 'Employee ID' },
                            { field: 'employee_name', label: 'Employee Name' },
                            { field: 'payment_type', label: 'Payment Type' },
                            { field: 'payment_date', label: 'Payment Date' },
                            { field: 'reference_number', label: 'Reference Number' }
                        ];
                        
                        if (metadata.hours_worked) {
                            metadataGroups['Payment Details'] = [
                                { field: 'hours_worked', label: 'Hours Worked' },
                                { field: 'hourly_rate', label: 'Hourly Rate' },
                                { field: 'pay_period_start', label: 'Pay Period Start' },
                                { field: 'pay_period_end', label: 'Pay Period End' }
                            ];
                        }
                        break;
                        
                    case 'purchase_orders':
                        metadataGroups['Purchase Order'] = [
                            { field: 'purchase_order_number', label: 'PO Number' },
                            { field: 'purchase_receipt_id', label: 'Receipt ID' },
                            { field: 'total_items_received', label: 'Items Received' },
                            { field: 'total_cost', label: 'Total Cost' },
                            { field: 'payment_status', label: 'Payment Status' }
                        ];
                        break;
                        
                    case 'expenses':
                        metadataGroups['Expense Details'] = [
                            { field: 'expense_number', label: 'Expense Number' },
                            { field: 'expense_description', label: 'Description' },
                            { field: 'vendor_name', label: 'Vendor' },
                            { field: 'amount', label: 'Amount' },
                            { field: 'tax_amount', label: 'Tax Amount' },
                            { field: 'total_amount', label: 'Total Amount' }
                        ];
                        break;
                        
                    case 'orders':
                        metadataGroups['Order Details'] = [
                            { field: 'order_number', label: 'Order Number' },
                            { field: 'customer_id', label: 'Customer ID' },
                            { field: 'customer_name', label: 'Customer Name' },
                            { field: 'items_count', label: 'Items Count' },
                            { field: 'payment_type', label: 'Payment Type' }
                        ];
                        
                        if (metadata.cash_details) {
                            metadataGroups['Cash Details'] = [
                                { field: 'amount_tendered', label: 'Amount Tendered', prefix: '$' },
                                { field: 'change_due', label: 'Change Due', prefix: '$' },
                                { field: 'cash_received', label: 'Cash Received', prefix: '$' }
                            ];
                        }
                        break;
                }
            }
            
            // Add general fields if they exist
            const generalFields = [];
            if (metadata.transaction_nature) generalFields.push({ field: 'transaction_nature', label: 'Transaction Nature' });
            if (metadata.processed_by_id) generalFields.push({ field: 'processed_by_id', label: 'Processed By ID' });
            if (metadata.processed_by_name) generalFields.push({ field: 'processed_by_name', label: 'Processed By Name' });
            
            if (generalFields.length > 0) {
                metadataGroups['General Information'] = generalFields;
            }
            
            // Add all other fields to "Additional Details"
            const otherFields = [];
            for (const key in metadata) {
                let found = false;
                for (const group in metadataGroups) {
                    if (metadataGroups[group].some(item => item.field === key)) {
                        found = true;
                        break;
                    }
                }
                if (!found && key !== 'cash_details' && key !== 'payment_details') {
                    otherFields.push({ field: key, label: key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) });
                }
            }
            
            if (otherFields.length > 0) {
                metadataGroups['Additional Details'] = otherFields;
            }
            
            // If no groups created, show all fields
            if (Object.keys(metadataGroups).length === 0) {
                metadataGroups['All Fields'] = Object.keys(metadata).map(key => ({
                    field: key,
                    label: key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
                }));
            }
            
            // Render grouped metadata
            for (const groupName in metadataGroups) {
                // Add group header
                const headerRow = formattedTable.insertRow();
                const headerCell = headerRow.insertCell();
                headerCell.colSpan = 2;
                headerCell.innerHTML = `<strong class="text-primary">${groupName}</strong>`;
                headerCell.className = 'bg-light';
                
                // Add fields
                metadataGroups[groupName].forEach(item => {
                    if (metadata[item.field] !== undefined && metadata[item.field] !== null) {
                        const row = formattedTable.insertRow();
                        const fieldCell = row.insertCell();
                        const valueCell = row.insertCell();
                        
                        fieldCell.innerHTML = `<strong>${item.label}</strong>`;
                        
                        let value = metadata[item.field];
                        // Format values
                        if (typeof value === 'object' && value !== null) {
                            value = JSON.stringify(value, null, 2);
                        } else if (item.prefix) {
                            value = `${item.prefix}${parseFloat(value).toFixed(2)}`;
                        } else if (typeof value === 'string' && value.match(/^\d{4}-\d{2}-\d{2}/)) {
                            value = new Date(value).toLocaleDateString();
                        }
                        
                        valueCell.textContent = value;
                    }
                });
            }
        }
        
        function printTransactionDetails() {
            // Simple print function
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