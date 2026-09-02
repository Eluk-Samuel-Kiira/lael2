<x-app-layout>
    @section('title', __('accounting.reconciliation'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{ __('accounting.reconciliation') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.reconciliation') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.reconciliation') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-lg-auto">
                <form method="GET" action="{{ route('accounting.reconciliation') }}" class="w-100" id="filterForm">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <!-- Location Filter -->
                        <div class="d-flex align-items-center bg-light-primary rounded-3 px-4 py-2" style="min-width: 200px;">
                            <i class="ki-duotone ki-geolocation fs-2 text-primary me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0" 
                                    name="location_id" 
                                    data-control="select2" 
                                    id="locationFilter" 
                                    onchange="this.form.submit()"
                                    data-placeholder="{{ __('pagination.all_locations') }}"
                                    style="min-width: 150px; width: 100%;">
                                <option value="">{{ __('pagination.all_locations') }}</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ ($locationId ?? '') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- User Filter -->
                        <div class="d-flex align-items-center bg-light-info rounded-3 px-4 py-2" style="min-width: 200px;">
                            <i class="ki-duotone ki-user fs-2 text-info me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <select class="form-select form-select-sm form-select-solid border-0 bg-transparent ps-0" 
                                    name="user_id" 
                                    data-control="select2" 
                                    id="userFilter" 
                                    onchange="this.form.submit()"
                                    data-placeholder="{{ __('accounting.all_users') }}"
                                    style="min-width: 150px; width: 100%;">
                                <option value="">{{ __('accounting.all_users') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($userId ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Date Selector -->
                        <div class="w-100 w-sm-auto">
                            <input type="date" id="dateSelector" class="form-control form-control-solid w-100 w-sm-180px" 
                                name="date"
                                value="{{ request('date', $date) }}" onchange="this.form.submit()">
                        </div>
                        
                        <!-- Buttons -->
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 20px;">
                            <i class="ki-duotone ki-filter fs-3"></i>
                            <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                            <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                        </button>
                        <a href="{{ route('accounting.reconciliation') }}" class="btn btn-light d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 20px;">
                            <i class="ki-duotone ki-arrow-rotate-left fs-3"></i>
                            <span class="d-none d-sm-inline">{{ __('accounting.reset') }}</span>
                            <span class="d-inline d-sm-none">{{ __('accounting.reset') }}</span>
                        </a>
                        
                        <!-- Reconcile All Button -->
                        <button class="btn btn-sm btn-success flex-grow-1 flex-sm-grow-0" onclick="reconcileAll()">
                            <i class="ki-duotone ki-check fs-2 me-1 me-sm-2"></i>
                            <span class="d-none d-sm-inline">{{ __('accounting.reconcile_all') }}</span>
                            <span class="d-inline d-sm-none">{{ __('accounting.reconcile') }}</span>
                        </button>
                        
                        <!-- Print Button -->
                        <button class="btn btn-sm btn-light flex-shrink-0" onclick="printReport()">
                            <i class="ki-duotone ki-printer fs-2 me-1 me-sm-2"></i>
                            <span class="d-none d-sm-inline">{{ __('accounting.print') }}</span>
                            <span class="d-inline d-sm-none">{{ __('accounting.print') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Summary Stats -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-success">{{ $summary['reconciled_methods'] }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.reconciled_methods') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.of') }} {{ $summary['total_methods'] }} {{ __('accounting.total') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ number_format(($summary['reconciled_methods'] / max($summary['total_methods'], 1)) * 100, 1) }}%</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.unreconciled') }}</span>
                                    <span class="fw-bold text-danger fs-7">{{ $summary['unreconciled_methods'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold {{ $summary['total_discrepancy'] == 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($summary['total_discrepancy'], 2) }} {{ currency_symbol() }}
                                    </span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_discrepancy') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.date') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">
                                        {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-info">{{ number_format($paymentMethods->sum('reconciliation_data.transaction_count')) }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_transactions') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.completed') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ number_format($paymentMethods->sum('reconciliation_data.transaction_count')) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ $summary['total_methods'] }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_methods') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.active') }}</span>
                                    <span class="fw-bold text-success fs-7">{{ $summary['total_methods'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reconciliation Table -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-check-circle fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.account_reconciliation') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.balance_verification') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($paymentMethods->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.starting_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_change') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.expected_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.current_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.discrepancy') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-150px text-center">{{ __('accounting.status') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($paymentMethods as $method)
                                    @php
                                        $data = $method->reconciliation_data;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-3">
                                                    <span class="symbol-label bg-light-{{ $data['is_reconciled'] ? 'success' : 'danger' }}">
                                                        @switch($method->type)
                                                            @case('cash')
                                                                <i class="ki-duotone ki-dollar fs-2x text-{{ $data['is_reconciled'] ? 'success' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                                @break
                                                            @case('bank_account')
                                                                <i class="ki-duotone ki-bank fs-2x text-{{ $data['is_reconciled'] ? 'success' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                                @break
                                                            @default
                                                                <i class="ki-duotone ki-wallet fs-2x text-{{ $data['is_reconciled'] ? 'success' : 'danger' }}">
                                                                    <span class="path1"></span>
                                                                    <span class="path2"></span>
                                                                </i>
                                                        @endswitch
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $method->name }}</span>
                                                    <span class="fs-7 text-gray-500">{{ $method->getTypeLabel() }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($data['starting_balance'], 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $data['net_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $data['net_change'] >= 0 ? '+' : '' }}{{ number_format($data['net_change'], 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">{{ number_format($data['expected_balance'], 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">{{ number_format($data['current_balance'], 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $data['discrepancy'] == 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($data['discrepancy'], 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($data['transaction_count']) }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($data['is_reconciled'])
                                                <span class="badge badge-light-success py-2 px-3">
                                                    <i class="ki-duotone ki-check-circle fs-1 me-1"></i>
                                                    {{ __('accounting.reconciled') }}
                                                </span>
                                            @else
                                                <span class="badge badge-light-danger py-2 px-3">
                                                    <i class="ki-duotone ki-close-circle fs-1 me-1"></i>
                                                    {{ __('accounting.unreconciled') }}
                                                    <span class="d-block fs-8 text-danger">
                                                        {{ number_format($data['discrepancy'], 2) }} {{ currency_symbol() }} {{ __('accounting.off') }}
                                                    </span>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(!$data['is_reconciled'])
                                                <button class="btn btn-sm btn-success" onclick="reconcileMethod({{ $method->id }})">
                                                    <i class="ki-duotone ki-check fs-2"></i> {{ __('accounting.reconcile') }}
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-light btn-active-light-primary" onclick="viewDetails({{ $method->id }})">
                                                    <i class="ki-duotone ki-eye fs-2"></i> {{ __('accounting.view') }}
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_payment_methods_found') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Unreconciled Transactions -->
                @if($unreconciledTransactions->count() > 0)
                <div class="card card-flush mt-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-flag fs-2 me-2 text-warning">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.unreconciled_transactions') }}
                            <span class="badge badge-light-warning ms-2 py-2 px-3">{{ $unreconciledTransactions->count() }}</span>
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.pending_transactions') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-150px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.processed_by') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($unreconciledTransactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ $transaction->transaction_date->format('M d, Y') }}</span>
                                            <span class="fs-7 text-gray-500 d-block">{{ $transaction->transaction_date->format('H:i:s') }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-6 text-gray-800">{{ Str::limit($transaction->description, 50) }}</span>
                                            @if($transaction->notes)
                                                <span class="fs-7 text-gray-500 d-block">{{ Str::limit($transaction->notes, 30) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fs-6 fw-bold text-gray-800">{{ $transaction->paymentMethod->name ?? 'N/A' }}</span>
                                                <span class="fs-7 text-gray-500">{{ $transaction->paymentMethod->type ?? '' }}</span>
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
                                                    'RECONCILIATION' => 'dark'
                                                ];
                                                $color = $typeColors[$transaction->transaction_type] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-light-{{ $color }} py-2 px-3">
                                                {{ $transaction->transaction_type }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-secondary py-2 px-3">{{ $transaction->transaction_category }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND']) ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($transaction->amount, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($transaction->user)
                                                <span class="fw-bold text-gray-700 fs-7">{{ $transaction->user->name }}</span>
                                            @else
                                                <span class="text-gray-500 fs-7">System</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button class="btn btn-sm btn-primary" onclick="completeTransaction({{ $transaction->id }})">
                                                <i class="ki-duotone ki-check-circle fs-2"></i> {{ __('accounting.complete') }}
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
                
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        function printReport() {
            window.print();
        }
        
        function reconcileAll() {
            if (confirm('{{ __("reconciliation.confirm_reconcile_all") }}')) {
                // Implement reconcile all functionality
                alert('{{ __("reconciliation.reconcile_all_message") }}');
            }
        }
        
        function reconcileMethod(methodId) {
            if (confirm('{{ __("reconciliation.confirm_reconcile_method") }}')) {
                // Implement reconcile method functionality
                alert('{{ __("reconciliation.reconcile_method_message") }}');
            }
        }
        
        function viewDetails(methodId) {
            // Implement view details functionality
            alert('{{ __("reconciliation.view_details_message") }}');
        }
        
        function completeTransaction(transactionId) {
            if (confirm('{{ __("reconciliation.confirm_complete_transaction") }}')) {
                // Implement complete transaction functionality
                alert('{{ __("reconciliation.complete_transaction_message") }}');
            }
        }
        
        // Initialize Select2 for filters
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof $ !== 'undefined' && $.fn.select2) {
                $('#locationFilter').select2({
                    placeholder: "{{ __('pagination.all_locations') }}",
                    allowClear: true,
                    width: '180px'
                });
                
                $('#userFilter').select2({
                    placeholder: "{{ __('accounting.all_users') }}",
                    allowClear: true,
                    width: '180px'
                });
            }
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>