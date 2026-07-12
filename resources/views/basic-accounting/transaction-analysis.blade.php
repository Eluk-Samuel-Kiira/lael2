<x-app-layout>
    @section('title', __('accounting.transaction_analysis'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('accounting.transaction_analysis')}}
                </h1>
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

            <!-- Right side - Actions -->
            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <form method="GET" action="{{ route('accounting.transaction-analysis') }}" class="w-100">
                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 w-100">
                        <div class="w-100 w-sm-auto" style="min-width: 140px;">
                            <input type="date" name="start_date" class="form-control form-control-solid w-100" 
                                value="{{ request('start_date', $startDate) }}"
                                style="cursor: pointer; height: 42px;">
                        </div>
                        <div class="d-flex align-items-center justify-content-center">
                            <span class="text-gray-500 px-2">{{ __('accounting.to') }}</span>
                        </div>
                        <div class="w-100 w-sm-auto" style="min-width: 140px;">
                            <input type="date" name="end_date" class="form-control form-control-solid w-100" 
                                value="{{ request('end_date', $endDate) }}"
                                style="cursor: pointer; height: 42px;">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 w-sm-auto d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 24px;">
                            <i class="ki-duotone ki-filter fs-3"></i>
                            <span>{{ __('accounting.apply_filters') }}</span>
                        </button>
                        <a href="{{ route('accounting.transaction-analysis') }}" class="btn btn-light w-100 w-sm-auto d-flex align-items-center justify-content-center gap-2"
                            style="height: 42px; padding: 0 24px;">
                            <i class="ki-duotone ki-arrow-rotate-left fs-3"></i>
                            <span>{{ __('accounting.reset') }}</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column flex-column-fluid">
        <div id="kt_app_content" class="app-content flex-column-fluid">
            <div id="kt_app_content_container" class="app-container container-xxl">
                
                @if($volumeByType->isEmpty() && $volumeByCategory->isEmpty())
                <div class="alert alert-info d-flex align-items-center">
                    <i class="ki-duotone ki-information-5 fs-2 me-2"></i>
                    <span>{{ __('No transactions found for the selected date range.') }}</span>
                </div>
                @else
                
                <!-- Transaction Volume by Type & Category Row -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Transaction Volume by Type -->
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
                                                $totalCount = $volumeByType->sum('count') ?: 1;
                                                $totalAmount = $volumeByType->sum('total_amount');
                                            @endphp
                                            @foreach($volumeByType as $item)
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light-{{ $item->transaction_type === 'DEPOSIT' ? 'success' : ($item->transaction_type === 'WITHDRAWAL' ? 'danger' : 'info') }}">
                                                        {{ $item->transaction_type }}
                                                    </span>
                                                </tr>
                                                <td class="text-end">{{ number_format($item->count) }}</td>
                                                <td class="text-end">
                                                    <span class="fs-6 fw-bold text-gray-800">
                                                        {{ number_format($item->total_amount, 2) }} {{ currency_symbol() }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($item->count > 0)
                                                        {{ number_format($item->average_amount, 2) }} {{ currency_symbol() }}
                                                    @else
                                                        0.00
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress h-6px w-100 me-3">
                                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                                 style="width: {{ ($item->count / $totalCount) * 100 }}%">
                                                            </div>
                                                        </div>
                                                        <span class="fs-7 fw-bold">{{ number_format(($item->count / $totalCount) * 100, 1) }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold text-gray-700">
                                                <td>{{ __('accounting.total') }}<tr>
                                                <td class="text-end">{{ number_format($totalCount) }}</td>
                                                <td class="text-end">{{ number_format($totalAmount, 2) }} {{ currency_symbol() }}</td>
                                                <td class="text-end">
                                                    @if($totalCount > 0)
                                                        {{ number_format($totalAmount / $totalCount, 2) }} {{ currency_symbol() }}
                                                    @else
                                                        0.00
                                                    @endif
                                                </td>
                                                <td>100%</td>
                                            </tr>
                                        </tfoot>
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
                                <div class="card-toolbar">
                                    <span class="text-gray-500 fs-7">{{ __('accounting.category_breakdown') }}</span>
                                </div>
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
                                                $categoryTotalAmount = $volumeByCategory->sum('total_amount') ?: 1;
                                            @endphp
                                            @foreach($volumeByCategory as $item)
                                            <tr>
                                                <td>
                                                    <span class="fs-6 fw-bold text-gray-800">{{ $item->transaction_category }}</span>
                                                </td>
                                                <td class="text-end">{{ number_format($item->count) }}</td>
                                                <td class="text-end">
                                                    <span class="fs-6 fw-bold text-gray-800">
                                                        {{ number_format($item->total_amount, 2) }} {{ currency_symbol() }}
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    @if($item->count > 0)
                                                        {{ number_format($item->average_amount, 2) }} {{ currency_symbol() }}
                                                    @else
                                                        0.00
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress h-6px w-100 me-3">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                 style="width: {{ ($item->total_amount / $categoryTotalAmount) * 100 }}%">
                                                            </div>
                                                        </div>
                                                        <span class="fs-7 fw-bold">{{ number_format(($item->total_amount / $categoryTotalAmount) * 100, 1) }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold text-gray-700">
                                                <td>{{ __('accounting.total') }}</td>
                                                <td class="text-end">{{ $volumeByCategory->sum('count') }}</td>
                                                <td class="text-end">{{ number_format($categoryTotalAmount, 2) }} {{ currency_symbol() }}</td>
                                                <td class="text-end">{{ number_format($volumeByCategory->avg('average_amount'), 2) }} {{ currency_symbol() }}</td>
                                                <td>100%</td>
                                            </tr>
                                        </tfoot>
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
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.daily_analysis') }}</span>
                        </div>
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
                                    @php $previousTotal = null; @endphp
                                    @foreach($dailyTrends as $trend)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($trend->date)->format('M d, Y') }}</td>
                                        <td class="text-end">{{ number_format($trend->transaction_count) }}</td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">
                                                {{ number_format($trend->daily_total, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($previousTotal !== null)
                                                @php
                                                    $change = $trend->daily_total - $previousTotal;
                                                    $percentage = $previousTotal > 0 ? ($change / $previousTotal) * 100 : 0;
                                                @endphp
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fs-7 fw-bold {{ $change >= 0 ? 'text-success' : 'text-danger' }}">
                                                        {{ $change >= 0 ? '+' : '' }}{{ number_format(abs($change), 2) }} {{ currency_symbol() }}
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
                                                {{ number_format($trend->daily_total / $trend->transaction_count, 2) }} {{ currency_symbol() }}
                                            @else
                                                0.00
                                            @endif
                                        </td>
                                    </tr>
                                    @php $previousTotal = $trend->daily_total; @endphp
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Top Transactions with Pagination -->
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
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.type') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance_after') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topTransactions as $index => $transaction)
                                    <tr>
                                        <td>{{ $index + 1 + (($topTransactions->currentPage() - 1) * $topTransactions->perPage()) }}</td>
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
                                                {{ number_format($transaction->amount, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">
                                                {{ number_format($transaction->balance_after, 2) }} {{ currency_symbol() }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="ki-duotone ki-information-5 fs-2x"></i>
                                                <p class="mt-2">{{ __('accounting.no_transactions_found') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer">
                            @include('partials.pagination', [
                                'paginator' => $topTransactions,
                                'pageName' => 'page',
                                'perPageName' => 'per_page',
                                'showPerPage' => true
                            ])
                        </div>
                    </div>
                </div>
                
                @endif
                
            </div>
        </div>
    </div>
    
    @endsection
</x-app-layout>