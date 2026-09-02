<x-app-layout>
    @section('title', __('accounting.cash_flow'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('accounting.cash_flow') }}
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
                    <li class="breadcrumb-item text-muted">{{ __('accounting.cash_flow') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <form method="GET" action="{{ route('accounting.cash-flow') }}" class="w-100" id="filterForm">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <!-- Location Filter -->
                        <div class="d-flex align-items-center bg-light-primary rounded-3 px-4 py-2" style="min-width: 220px;">
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
                                    style="min-width: 160px; width: 100%;">
                                <option value="">{{ __('pagination.all_locations') }}</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ ($locationId ?? '') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- User Filter -->
                        <div class="d-flex align-items-center bg-light-info rounded-3 px-4 py-2" style="min-width: 220px;">
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
                                    style="min-width: 160px; width: 100%;">
                                <option value="">{{ __('accounting.all_users') }}</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ ($userId ?? '') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Date Range -->
                        <div class="d-flex align-items-center gap-2">
                            <input type="date" name="start_date" class="form-control form-control-solid" 
                                style="min-width: 150px; height: 42px;"
                                value="{{ request('start_date', $startDate) }}">
                            <span class="text-gray-500 px-1">{{ __('accounting.to') }}</span>
                            <input type="date" name="end_date" class="form-control form-control-solid" 
                                style="min-width: 150px; height: 42px;"
                                value="{{ request('end_date', $endDate) }}">
                        </div>
                        
                        <!-- Buttons -->
                        <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 24px;">
                            <i class="ki-duotone ki-filter fs-3"></i>
                            <span>{{ __('accounting.apply_filters') }}</span>
                        </button>
                        <button type="button" class="btn btn-light d-flex align-items-center justify-content-center gap-2" onclick="printReport()"
                            style="height: 42px; padding: 0 24px;">
                            <i class="ki-duotone ki-printer fs-3"></i>
                            <span>{{ __('accounting.print') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Cash In -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-success">{{ number_format($summary['total_cash_in'], 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_cash_in') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">
                                        {{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                                    </span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.transactions') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ number_format($summary['total_transactions']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cash Out -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-danger">{{ number_format($summary['total_cash_out'], 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_cash_out') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.period') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">
                                        {{ \Carbon\Carbon::parse($startDate)->format('M d') }} - {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                                    </span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.average_daily') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">
                                        @php
                                            $days = max(1, \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1);
                                        @endphp
                                        {{ number_format($summary['total_cash_out'] / $days, 2) }} {{ currency_symbol() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Net Cash Flow -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold {{ $summary['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($summary['net_cash_flow'], 2) }} {{ currency_symbol() }}
                                    </span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.net_cash_flow') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.net_movement') }}</span>
                                    <span class="fw-bold fs-7 {{ $summary['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $summary['net_cash_flow'] >= 0 ? '+' : '' }}{{ number_format($summary['net_cash_flow'], 2) }} {{ currency_symbol() }}
                                    </span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.cash_in_out_ratio') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">
                                        @if($summary['total_cash_out'] > 0)
                                            {{ number_format($summary['total_cash_in'] / $summary['total_cash_out'], 2) }}:1
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Daily Cash Flow Table -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                            {{ __('accounting.daily_cash_flow') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.daily_breakdown') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($dailyCashFlow->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-120px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.cash_in') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.cash_out') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_flow') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.trend') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.daily_balance') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @php $runningBalance = 0; @endphp
                                    @foreach($dailyCashFlow as $day)
                                    <tr>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ \Carbon\Carbon::parse($day->date)->format('M d, Y') }}</span>
                                            <span class="fs-7 text-gray-500 d-block">{{ \Carbon\Carbon::parse($day->date)->format('l') }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-success">{{ number_format($day->cash_in, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">{{ number_format($day->cash_out, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            @php $netFlow = $day->cash_in - $day->cash_out; $runningBalance += $netFlow; @endphp
                                            <span class="fs-6 fw-bold {{ $netFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($netFlow, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($day->transaction_count) }}</span>
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
                                            <span class="fs-6 fw-bold text-gray-800">{{ number_format($runningBalance, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold text-gray-700 fs-6">
                                        <td>{{ __('accounting.total') }}</td>
                                        <td class="text-end text-success">{{ number_format($dailyCashFlow->sum('cash_in'), 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end text-danger">{{ number_format($dailyCashFlow->sum('cash_out'), 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end {{ $summary['net_cash_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($summary['net_cash_flow'], 2) }} {{ currency_symbol() }}
                                        </td>
                                        <td class="text-end">{{ number_format($dailyCashFlow->sum('transaction_count')) }}</td>
                                        <td></td>
                                        <td class="text-end text-gray-800">{{ number_format($runningBalance, 2) }} {{ currency_symbol() }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_cash_flow_data') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Cash Flow by Payment Method -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-wallet fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.cash_flow_by_payment_method') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.method_breakdown') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($cashFlowByMethod->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.cash_in') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.cash_out') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.net_flow') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.in_out_ratio') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.average_transaction') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
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
                                            <span class="fs-6 fw-bold text-success">{{ number_format($flow->cash_in, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">{{ number_format($flow->cash_out, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            @php $netFlow = $flow->cash_in - $flow->cash_out; @endphp
                                            <span class="fs-6 fw-bold {{ $netFlow >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format($netFlow, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($flow->transaction_count) }}</span>
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
                                                    {{ number_format(($flow->cash_in + $flow->cash_out) / $flow->transaction_count, 2) }} {{ currency_symbol() }}
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
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_payment_method_data') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        function printReport() {
            window.print();
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