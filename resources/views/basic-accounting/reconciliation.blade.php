<x-app-layout>
    @section('title', __('accounting.reconciliation'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                    {{__('accounting.reconciliation')}}
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
                    <li class="breadcrumb-item text-muted">{{__('accounting.reconciliation')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-lg-auto">
                <!-- Date Selector -->
                <div class="w-100 w-sm-auto">
                    <input type="date" id="dateSelector" class="form-control form-control-solid w-100 w-sm-200px" 
                        value="{{ $date }}" onchange="changeDate()">
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex flex-row gap-2 w-100 w-sm-auto">
                    <button class="btn btn-sm btn-success flex-grow-1 flex-sm-grow-0" onclick="reconcileAll()">
                        <i class="ki-duotone ki-check fs-2 me-1 me-sm-2"></i>
                        <span class="d-none d-sm-inline">{{ __('accounting.reconcile_all') }}</span>
                        <span class="d-inline d-sm-none">{{ __('accounting.reconcile') }}</span>
                    </button>
                    <button class="btn btn-sm btn-light flex-shrink-0" onclick="printReport()">
                        <i class="ki-duotone ki-printer fs-2 me-1 me-sm-2"></i>
                        <span class="d-none d-sm-inline">{{ __('accounting.print') }}</span>
                        <span class="d-inline d-sm-none">{{ __('accounting.print') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Summary Stats -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Reconciled Methods -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.reconciled_methods') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-success me-2 lh-1">{{ $summary['reconciled_methods'] }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.of') }} {{ $summary['total_methods'] }} {{ __('accounting.total') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.unreconciled') }}</span>
                                    <span class="fs-6 fw-bold text-danger">{{ $summary['unreconciled_methods'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Discrepancy -->
                    <div class="col-xl-3">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_discrepancy') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold {{ $summary['total_discrepancy'] == 0 ? 'text-success' : 'text-danger' }} me-2 lh-1">
                                        ${{ number_format($summary['total_discrepancy'], 2) }}
                                    </span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.overall_difference') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.date') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">
                                        {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reconciliation Status -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.reconciliation_status') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-wrap">
                                    @foreach($paymentMethods as $method)
                                    @if($method->reconciliation_data['is_reconciled'])
                                    <div class="d-flex flex-column me-7 mb-5">
                                        <span class="fs-4 fw-bold text-success">{{ $method->name }}</span>
                                        <span class="fs-6 fw-semibold text-gray-500">{{ __('accounting.reconciled') }}</span>
                                        <div class="d-flex align-items-center mt-2">
                                            <div class="progress h-6px w-100 me-3">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                            </div>
                                            <span class="fs-7 fw-bold">100%</span>
                                        </div>
                                    </div>
                                    @else
                                    <div class="d-flex flex-column me-7 mb-5">
                                        <span class="fs-4 fw-bold text-danger">{{ $method->name }}</span>
                                        <span class="fs-6 fw-semibold text-gray-500">${{ number_format($method->reconciliation_data['discrepancy'], 2) }}</span>
                                        <div class="d-flex align-items-center mt-2">
                                            <div class="progress h-6px w-100 me-3">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                                            </div>
                                            <span class="fs-7 fw-bold">{{ __('accounting.unreconciled') }}</span>
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reconciliation Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.account_reconciliation') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.balance_verification') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.current_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.expected_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_change') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.discrepancy') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.status') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentMethods as $method)
                                    @php
                                        $data = $method->reconciliation_data;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-3">
                                                    <span class="symbol-label bg-light-{{ $data['is_reconciled'] ? 'success' : 'danger' }}">
                                                        <i class="ki-duotone ki-wallet fs-2x text-{{ $data['is_reconciled'] ? 'success' : 'danger' }}">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $method->name }}</span>
                                                    <span class="fs-7 text-gray-500">{{ $method->getTypeLabel() }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">${{ number_format($data['current_balance'], 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">${{ number_format($data['expected_balance'], 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $data['net_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($data['net_change'], 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $data['discrepancy'] == 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($data['discrepancy'], 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($data['transaction_count']) }}</span>
                                        </td>
                                        <td>
                                            @if($data['is_reconciled'])
                                                <span class="badge badge-light-success">{{ __('accounting.reconciled') }}</span>
                                            @else
                                                <span class="badge badge-light-danger">{{ __('accounting.unreconciled') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if(!$data['is_reconciled'])
                                                <button class="btn btn-sm btn-success" onclick="reconcileMethod({{ $method->id }})">
                                                    <i class="ki-duotone ki-check fs-2"></i> {{ __('accounting.reconcile') }}
                                                </button>
                                            @else
                                                <button class="btn btn-sm btn-light" onclick="viewDetails({{ $method->id }})">
                                                    <i class="ki-duotone ki-eye fs-2"></i> {{ __('accounting.view') }}
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Unreconciled Transactions -->
                @if($unreconciledTransactions->count() > 0)
                <div class="card mt-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.unreconciled_transactions') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.pending_transactions') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-150px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unreconciledTransactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="fs-7 fw-bold text-gray-800">{{ $transaction->transaction_date->format('M d, Y') }}</span>
                                            <span class="fs-8 text-gray-500 d-block">{{ $transaction->transaction_date->format('H:i') }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-800">{{ Str::limit($transaction->description, 50) }}</span>
                                            @if($transaction->notes)
                                                <span class="fs-8 text-gray-500 d-block">{{ Str::limit($transaction->notes, 30) }}</span>
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
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND']) ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($transaction->amount, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-warning">{{ $transaction->status }}</span>
                                        </td>
                                        <td class="text-end">
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
        function changeDate() {
            const date = document.getElementById('dateSelector').value;
            window.location.href = '{{ route("accounting.reconciliation") }}?date=' + date;
        }
        
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
        
        // Initialize date selector with today's date if empty
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.getElementById('dateSelector').value) {
                document.getElementById('dateSelector').value = new Date().toISOString().split('T')[0];
            }
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>