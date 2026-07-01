<x-app-layout>
    @section('title', __('accounting.payment_method_analysis'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('accounting.payment_method_analysis')}}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.payment-method-analysis') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('accounting.payment_method_analysis')}}</li>
                </ul>
            </div>

            <!-- Right side - Actions -->
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <!-- Date Filter -->
                <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 w-100">
                    <input type="date" id="startDate" class="form-control form-control-solid w-100 w-sm-150px" 
                        value="{{ $startDate }}" onchange="updateFilters()">
                    <span class="d-none d-sm-inline text-gray-500 align-self-center">to</span>
                    <span class="d-inline d-sm-none text-gray-500 text-center">{{ __('accounting.to') }}</span>
                    <input type="date" id="endDate" class="form-control form-control-solid w-100 w-sm-150px" 
                        value="{{ $endDate }}" onchange="updateFilters()">
                </div>
                
                <!-- Apply Filters Button -->
                <button class="btn btn-sm btn-primary w-100 w-sm-auto flex-shrink-0" onclick="applyFilters()">
                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                </button>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Summary Stats -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Balance -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_balance') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">${{ number_format($stats['total_balance'], 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.overall_balance') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.period') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">
                                        {{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Transactions -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_transactions') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">{{ number_format($stats['total_transactions']) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.transactions') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_amount') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">${{ number_format($stats['total_transaction_amount'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Most Active Method -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.most_active_method') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-primary me-2 lh-1">{{ $stats['most_active_method']->name ?? 'N/A' }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.highest_activity') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.transactions') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">{{ $stats['most_active_method']->transaction_stats['total_transactions'] ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Methods Analysis Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.payment_methods_analysis') }}</h3>
                        <div class="card-toolbar">
                            <div class="d-flex align-items-center position-relative">
                                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <input type="text" id="searchMethods" class="form-control form-control-solid w-250px ps-10" placeholder="{{ __('accounting.search_methods') }}">
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="methodsTable">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.current_balance') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.total_transactions') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.deposits') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.withdrawals') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_flow') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.average_transaction') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.activity_rate') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentMethods as $method)
                                    @php
                                        $stats = $method->transaction_stats;
                                        $netFlow = $stats['deposit_total'] - $stats['withdrawal_total'];
                                        $activityRate = $stats['total_transactions'] > 0 ? ($stats['total_transactions'] / max($stats['total_transactions'], 1)) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-3">
                                                    <span class="symbol-label bg-light-{{ $method->is_active ? 'primary' : 'danger' }}">
                                                        <i class="ki-duotone ki-wallet fs-2x text-{{ $method->is_active ? 'primary' : 'danger' }}">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $method->name }}</span>
                                                    <span class="fs-7 text-gray-500">{{ $method->code }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $method->type === 'cash' ? 'warning' : ($method->type === 'bank_account' ? 'info' : 'primary') }}">
                                                {{ $method->getTypeLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $method->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $method->currency->symbol ?? '$' }}{{ number_format($method->current_balance, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($stats['total_transactions']) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-success">
                                                ${{ number_format($stats['deposit_total'], 2) }}
                                            </span>
                                            <span class="fs-8 text-gray-500 d-block">{{ number_format($stats['deposit_count']) }} {{ __('accounting.txns') }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">
                                                ${{ number_format($stats['withdrawal_total'], 2) }}
                                            </span>
                                            <span class="fs-8 text-gray-500 d-block">{{ number_format($stats['withdrawal_count']) }} {{ __('accounting.txns') }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold {{ $netFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($netFlow, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($stats['total_transactions'] > 0)
                                                <span class="fs-6 text-gray-700">
                                                    ${{ number_format($stats['total_amount'] / $stats['total_transactions'], 2) }}
                                                </span>
                                            @else
                                                <span class="fs-7 text-gray-500">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-primary" role="progressbar" 
                                                         style="width: {{ min($activityRate, 100) }}%">
                                                    </div>
                                                </div>
                                                <span class="fs-7 fw-bold">{{ number_format($activityRate, 1) }}%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $method->is_active ? 'success' : 'danger' }}">
                                                {{ $method->is_active ? __('accounting.active') : __('accounting.inactive') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Performance Comparison -->
                <div class="card mt-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.performance_comparison') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.comparison_chart') }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($paymentMethods->take(4) as $method)
                            <div class="col-md-3 mb-5">
                                <div class="d-flex flex-column">
                                    <span class="fs-5 fw-bold text-gray-800">{{ $method->name }}</span>
                                    <span class="fs-7 text-gray-500 mb-3">{{ $method->getTypeLabel() }}</span>
                                    
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="text-gray-500 fs-7 me-auto">{{ __('accounting.balance') }}</span>
                                        <span class="fs-6 fw-bold {{ $method->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format($method->current_balance, 2) }}
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="text-gray-500 fs-7 me-auto">{{ __('accounting.transactions') }}</span>
                                        <span class="fs-6 fw-bold text-gray-700">
                                            {{ number_format($method->transaction_stats['total_transactions']) }}
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="text-gray-500 fs-7 me-auto">{{ __('accounting.deposits') }}</span>
                                        <span class="fs-6 fw-bold text-success">
                                            ${{ number_format($method->transaction_stats['deposit_total'], 2) }}
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="text-gray-500 fs-7 me-auto">{{ __('accounting.withdrawals') }}</span>
                                        <span class="fs-6 fw-bold text-danger">
                                            ${{ number_format($method->transaction_stats['withdrawal_total'], 2) }}
                                        </span>
                                    </div>
                                    
                                    <div class="d-flex align-items-center">
                                        <span class="text-gray-500 fs-7 me-auto">{{ __('accounting.net_flow') }}</span>
                                        @php
                                            $netFlow = $method->transaction_stats['deposit_total'] - $method->transaction_stats['withdrawal_total'];
                                        @endphp
                                        <span class="fs-6 fw-bold {{ $netFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                            ${{ number_format($netFlow, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <!-- Chart Placeholder -->
                        <div class="d-flex justify-content-center mt-8">
                            <div class="w-100" style="max-width: 800px; height: 300px;">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        function updateFilters() {
            // This function updates the filter values
        }
        
        function applyFilters() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            // Redirect with new filter parameters
            window.location.href = '{{ route("accounting.payment-method-analysis") }}' + 
                '?start_date=' + startDate + '&end_date=' + endDate;
        }
        
        // Search functionality
        document.getElementById('searchMethods').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#methodsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        // Initialize performance chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('performanceChart').getContext('2d');
            if (ctx) {
                const methods = @json($paymentMethods->take(5)->values());
                const labels = methods.map(m => m.name);
                const depositData = methods.map(m => m.transaction_stats.deposit_total);
                const withdrawalData = methods.map(m => m.transaction_stats.withdrawal_total);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: '{{ __('accounting.deposits') }}',
                                data: depositData,
                                backgroundColor: 'rgba(40, 199, 111, 0.8)',
                                borderColor: 'rgb(40, 199, 111)',
                                borderWidth: 1
                            },
                            {
                                label: '{{ __('accounting.withdrawals') }}',
                                data: withdrawalData,
                                backgroundColor: 'rgba(245, 101, 101, 0.8)',
                                borderColor: 'rgb(245, 101, 101)',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '$' + context.parsed.y.toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>