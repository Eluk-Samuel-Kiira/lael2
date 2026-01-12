<x-app-layout>
    @section('title', __('accounting.transaction_analysis'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{__('accounting.transaction_analysis')}}</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.transaction-analysis') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('accounting.transaction_analysis')}}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <!-- Date Filter -->
                <div class="d-flex align-items-center">
                    <input type="date" id="startDate" class="form-control form-control-solid w-150px" 
                           value="{{ $startDate }}" onchange="updateFilters()">
                    <span class="mx-2">to</span>
                    <input type="date" id="endDate" class="form-control form-control-solid w-150px" 
                           value="{{ $endDate }}" onchange="updateFilters()">
                </div>
                <button class="btn btn-sm btn-primary" onclick="applyFilters()">
                    <i class="ki-duotone ki-filter fs-2"></i> {{ __('accounting.apply_filters') }}
                </button>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                @if($volumeByType->isEmpty() && $volumeByCategory->isEmpty())
                <div class="alert alert-info">
                    <i class="ki-duotone ki-information fs-2 me-2"></i>
                    {{ __('No transactions found for the selected date range.') }}
                </div>
                @else
                
                <!-- Transaction Volume by Type -->
                <div class="row g-5 g-xl-8 mb-8">
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('accounting.transaction_volume_by_type') }}</h3>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                                <th class="min-w-150px">{{ __('accounting.transaction_type') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.count') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.average_amount') }}</th>
                                                <th class="min-w-100px">{{ __('accounting.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $totalCount = $volumeByType->sum('count') ?: 1; // Prevent division by zero
                                                $totalAmount = $volumeByType->sum('total_amount');
                                            @endphp
                                            @foreach($volumeByType as $item)
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light-{{ $item->transaction_type === 'DEPOSIT' ? 'success' : ($item->transaction_type === 'WITHDRAWAL' ? 'danger' : 'info') }}">
                                                        {{ $item->transaction_type }}
                                                    </span>
                                                </td>
                                                <td class="text-end">{{ number_format($item->count) }}</td>
                                                <td class="text-end">
                                                    <span class="fs-6 fw-bold text-gray-800">
                                                        ${{ number_format($item->total_amount, 2) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($item->count > 0)
                                                        ${{ number_format($item->average_amount, 2) }}
                                                    @else
                                                        $0.00
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($totalCount > 1) {{-- Only show percentage if we have transactions --}}
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress h-6px w-100 me-3">
                                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                                 style="width: {{ ($item->count / $totalCount) * 100 }}%">
                                                            </div>
                                                        </div>
                                                        <span class="fs-7 fw-bold">{{ number_format(($item->count / $totalCount) * 100, 1) }}%</span>
                                                    </div>
                                                    @else
                                                    <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        @if($totalCount > 1)
                                        <tfoot>
                                            <tr class="fw-bold text-gray-700">
                                                <td>{{ __('accounting.total') }}</td>
                                                <td class="text-end">{{ number_format($totalCount) }}</td>
                                                <td class="text-end">${{ number_format($totalAmount, 2) }}</td>
                                                <td class="text-end">
                                                    @if($totalCount > 0)
                                                        ${{ number_format($totalAmount / $totalCount, 2) }}
                                                    @else
                                                        $0.00
                                                    @endif
                                                </td>
                                                <td>100%</td>
                                            </tr>
                                        </tfoot>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transaction Volume by Category -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('accounting.transaction_volume_by_category') }}</h3>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                                <th class="min-w-150px">{{ __('accounting.category') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.count') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                                <th class="min-w-100px text-end">{{ __('accounting.average_amount') }}</th>
                                                <th class="min-w-100px">{{ __('accounting.share') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $categoryTotalAmount = $volumeByCategory->sum('total_amount') ?: 1; // Prevent division by zero
                                            @endphp
                                            @foreach($volumeByCategory as $item)
                                            <tr>
                                                <td>
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $item->transaction_category }}</span>
                                                </td>
                                                <td class="text-end">{{ number_format($item->count) }}</td>
                                                <td class="text-end">
                                                    <span class="fs-6 fw-bold text-gray-800">
                                                        ${{ number_format($item->total_amount, 2) }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($item->count > 0)
                                                        ${{ number_format($item->average_amount, 2) }}
                                                    @else
                                                        $0.00
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($categoryTotalAmount > 1)
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress h-6px w-100 me-3">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                 style="width: {{ ($item->total_amount / $categoryTotalAmount) * 100 }}%">
                                                            </div>
                                                        </div>
                                                        <span class="fs-7 fw-bold">{{ number_format(($item->total_amount / $categoryTotalAmount) * 100, 1) }}%</span>
                                                    </div>
                                                    @else
                                                    <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Daily Trends -->
                @if($dailyTrends->isNotEmpty())
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.daily_transaction_trends') }}</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-120px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transaction_count') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.daily_total') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.trend') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.daily_average') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $previousTotal = null;
                                    @endphp
                                    @foreach($dailyTrends as $trend)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($trend->date)->format('M d, Y') }}</td>
                                        <td class="text-end">{{ number_format($trend->transaction_count) }}</td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">
                                                ${{ number_format($trend->daily_total, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($previousTotal !== null)
                                                @php
                                                    $change = $trend->daily_total - $previousTotal;
                                                    $percentage = $previousTotal > 0 ? ($change / $previousTotal) * 100 : 0;
                                                @endphp
                                                <div class="d-flex align-items-center">
                                                    <span class="fs-7 fw-bold me-2 {{ $change >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $change >= 0 ? '+' : '' }}${{ number_format($change, 2) }}
                                                    </span>
                                                    <span class="badge badge-light-{{ $change >= 0 ? 'success' : 'danger' }}">
                                                        {{ $change >= 0 ? '↑' : '↓' }} {{ number_format(abs($percentage), 1) }}%
                                                    </span>
                                                </div>
                                            @else
                                                <span class="fs-7 text-gray-500">{{ __('accounting.first_day') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($trend->transaction_count > 0)
                                                ${{ number_format($trend->daily_total / $trend->transaction_count, 2) }}
                                            @else
                                                $0.00
                                            @endif
                                        </td>
                                    </tr>
                                    @php
                                        $previousTotal = $trend->daily_total;
                                    @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Top Transactions -->
                @if($topTransactions->isNotEmpty())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.top_transactions') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.largest_transactions') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-50px">#</th>
                                        <th class="min-w-150px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.account_name') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance_after') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topTransactions as $index => $transaction)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $transaction->transaction_date->format('M d, Y H:i') }}</td>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ Str::limit($transaction->description, 50) }}</span>
                                        </td>
                                        <td>{{ $transaction->paymentMethod->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge badge-light-{{ $transaction->transaction_type === 'DEPOSIT' ? 'success' : ($transaction->transaction_type === 'WITHDRAWAL' ? 'danger' : 'info') }}">
                                                {{ $transaction->transaction_type }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction->transaction_category }}</td>
                                        <td class="text-end">
                                            <span class="fs-5 fw-bold {{ in_array($transaction->transaction_type, ['DEPOSIT', 'TRANSFER_IN', 'REFUND']) ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($transaction->amount, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">
                                                ${{ number_format($transaction->balance_after, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
                
                @endif {{-- End of if there are transactions --}}
                
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
            window.location.href = '{{ route("accounting.transaction-analysis") }}' + 
                '?start_date=' + startDate + '&end_date=' + endDate;
        }
        
        // Initialize date inputs with today's date if empty
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.getElementById('startDate').value) {
                document.getElementById('startDate').value = new Date().toISOString().split('T')[0];
            }
            if (!document.getElementById('endDate').value) {
                document.getElementById('endDate').value = new Date().toISOString().split('T')[0];
            }
        });
    </script>
    @endpush
    
    @endsection
</x-app-layout>