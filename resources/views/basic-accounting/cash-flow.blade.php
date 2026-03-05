<x-app-layout>
    @section('title', __('accounting.cash_flow'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('accounting.cash_flow')}}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.cash-flow') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{__('accounting.cash_flow')}}</li>
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
                
                <!-- Action Buttons -->
                <div class="d-flex flex-row gap-2">
                    <button class="btn btn-sm btn-primary flex-grow-1 flex-sm-grow-0" onclick="applyFilters()">
                        <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                        <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                        <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
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
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Cash In -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_cash_in') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-success me-2 lh-1">${{ number_format($summary['total_cash_in'], 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.money_received') }}</span>
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
                    
                    <!-- Cash Out -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_cash_out') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-danger me-2 lh-1">${{ number_format($summary['total_cash_out'], 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.money_paid') }}</span>
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
                    
                    <!-- Net Cash Flow -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.net_cash_flow') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold {{ $summary['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }} me-2 lh-1">
                                        ${{ number_format($summary['net_cash_flow'], 2) }}
                                    </span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.net_movement') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.transactions') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">{{ number_format($summary['total_transactions']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Daily Cash Flow Table -->
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.daily_cash_flow') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.daily_breakdown') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-120px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.cash_in') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.cash_out') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_flow') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.trend') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.daily_balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $runningBalance = 0;
                                    @endphp
                                    @foreach($dailyCashFlow as $day)
                                    <tr>
                                        <td>
                                            <span class="fs-7 fw-bold text-gray-800">{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</span>
                                            <span class="fs-8 text-gray-500 d-block">{{ \Carbon\Carbon::parse($day->date)->format('D') }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-success">${{ number_format($day->cash_in, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">${{ number_format($day->cash_out, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            @php
                                                $netFlow = $day->cash_in - $day->cash_out;
                                                $runningBalance += $netFlow;
                                            @endphp
                                            <span class="fs-6 fw-bold {{ $netFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($netFlow, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($day->transaction_count) }}</span>
                                        </td>
                                        <td>
                                            @if($day->cash_in > 0 || $day->cash_out > 0)
                                                <div class="d-flex align-items-center">
                                                    <div class="progress h-8px w-100 me-3">
                                                        @if($day->cash_in > 0 && $day->cash_out > 0)
                                                            @php
                                                                $inPercentage = ($day->cash_in / ($day->cash_in + $day->cash_out)) * 100;
                                                                $outPercentage = ($day->cash_out / ($day->cash_in + $day->cash_out)) * 100;
                                                            @endphp
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $inPercentage }}%"></div>
                                                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $outPercentage }}%"></div>
                                                        @elseif($day->cash_in > 0)
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                                        @else
                                                            <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                                                        @endif
                                                    </div>
                                                    <span class="fs-7 fw-bold">{{ $day->cash_in > 0 ? number_format(($day->cash_in / ($day->cash_in + $day->cash_out)) * 100, 0) : 0 }}%</span>
                                                </div>
                                            @else
                                                <span class="fs-7 text-gray-500">{{ __('accounting.no_activity') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">${{ number_format($runningBalance, 2) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold text-gray-700">
                                        <td>{{ __('accounting.total') }}</td>
                                        <td class="text-end">${{ number_format($dailyCashFlow->sum('cash_in'), 2) }}</td>
                                        <td class="text-end">${{ number_format($dailyCashFlow->sum('cash_out'), 2) }}</td>
                                        <td class="text-end">${{ number_format($summary['net_cash_flow'], 2) }}</td>
                                        <td class="text-end">{{ number_format($dailyCashFlow->sum('transaction_count')) }}</td>
                                        <td></td>
                                        <td class="text-end">${{ number_format($runningBalance, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Cash Flow by Payment Method -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.cash_flow_by_payment_method') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.method_breakdown') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.cash_in') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.cash_out') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_flow') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.in_out_ratio') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.average_transaction') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cashFlowByMethod as $flow)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-3">
                                                    <span class="symbol-label bg-light-primary">
                                                        <i class="ki-duotone ki-wallet fs-2x text-primary">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $flow->paymentMethod->name ?? 'N/A' }}</span>
                                                    <span class="fs-7 text-gray-500">{{ $flow->paymentMethod->type ?? '' }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-success">${{ number_format($flow->cash_in, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">${{ number_format($flow->cash_out, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            @php
                                                $netFlow = $flow->cash_in - $flow->cash_out;
                                            @endphp
                                            <span class="fs-6 fw-bold {{ $netFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($netFlow, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($flow->transaction_count) }}</span>
                                        </td>
                                        <td>
                                            @if($flow->cash_in > 0 || $flow->cash_out > 0)
                                                <div class="d-flex align-items-center">
                                                    <div class="progress h-8px w-100 me-3">
                                                        @if($flow->cash_in > 0 && $flow->cash_out > 0)
                                                            @php
                                                                $inPercentage = ($flow->cash_in / ($flow->cash_in + $flow->cash_out)) * 100;
                                                                $outPercentage = ($flow->cash_out / ($flow->cash_in + $flow->cash_out)) * 100;
                                                            @endphp
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $inPercentage }}%"></div>
                                                            <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $outPercentage }}%"></div>
                                                        @elseif($flow->cash_in > 0)
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                                                        @else
                                                            <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                                                        @endif
                                                    </div>
                                                    <span class="fs-7 fw-bold">{{ $flow->cash_in > 0 ? number_format(($flow->cash_in / ($flow->cash_in + $flow->cash_out)) * 100, 0) : 0 }}%</span>
                                                </div>
                                            @else
                                                <span class="fs-7 text-gray-500">{{ __('accounting.no_activity') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($flow->transaction_count > 0)
                                                <span class="fs-6 text-gray-700">
                                                    ${{ number_format(($flow->cash_in + $flow->cash_out) / $flow->transaction_count, 2) }}
                                                </span>
                                            @else
                                                <span class="fs-7 text-gray-500">-</span>
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
            window.location.href = '{{ route("accounting.cash-flow") }}' + 
                '?start_date=' + startDate + '&end_date=' + endDate;
        }
        
        function printReport() {
            window.print();
        }
        
        // Initialize date inputs with default values
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