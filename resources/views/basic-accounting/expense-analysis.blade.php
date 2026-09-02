<x-app-layout>
    @section('title', __('accounting.expense_analysis'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{ __('accounting.expense_analysis') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('accounting.expense-analysis') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.basic_accounting') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.expense_analysis') }}</li>
                </ul>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-3 w-100 w-md-auto">
                <form method="GET" action="{{ route('accounting.expense-analysis') }}" class="w-100" id="filterForm">
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
                        <a href="{{ route('accounting.expense-analysis') }}" class="btn btn-light d-flex align-items-center justify-content-center gap-2"
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
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Expenses -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-danger">{{ number_format($summary['total_expenses'], 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.total_expenses') }}</span>
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
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.expense_count') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ number_format($summary['expense_count']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Average Expense -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-gray-800">{{ number_format($summary['average_expense'], 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.average_expense') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.per_transaction') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ number_format($summary['average_expense'], 2) }} {{ currency_symbol() }}</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.total_categories') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ $expensesByCategory->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Largest Expense -->
                    <div class="col-sm-6 col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header pt-7">
                                <div class="card-title d-flex flex-column">
                                    <span class="fs-2hx fw-bold text-danger">{{ number_format($summary['largest_expense'], 2) }} {{ currency_symbol() }}</span>
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.largest_expense') }}</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.single_expense') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">{{ number_format($summary['largest_expense'], 2) }} {{ currency_symbol() }}</span>
                                </div>
                                <div class="d-flex flex-stack mt-2">
                                    <span class="text-gray-500 fw-semibold fs-7">{{ __('accounting.date_range') }}</span>
                                    <span class="fw-bold text-gray-700 fs-7">
                                        {{ \Carbon\Carbon::parse($startDate)->diffInDays(\Carbon\Carbon::parse($endDate)) + 1 }} {{ __('accounting.days') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Expenses by Category -->
                <div class="card card-flush mb-8">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-category fs-2 me-2 text-danger">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.expenses_by_category') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.category_breakdown') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($expensesByCategory->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.expense_count') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.average_amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.max_amount') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.percentage') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.trend') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($expensesByCategory as $category)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-45px me-3">
                                                    <span class="symbol-label bg-light-danger">
                                                        <i class="ki-duotone ki-arrow-down fs-2x text-danger">
                                                            <span class="path1"></span>
                                                            <span class="path2"></span>
                                                        </i>
                                                    </span>
                                                </div>
                                                <span class="fs-6 fw-bold text-gray-800">{{ $category->transaction_category }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary py-2 px-3">{{ number_format($category->count) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">{{ number_format($category->total_amount, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($category->average_amount, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">{{ number_format($category->max_amount, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-danger" role="progressbar" 
                                                         style="width: {{ ($category->total_amount / max($summary['total_expenses'], 1)) * 100 }}%">
                                                    </div>
                                                </div>
                                                <span class="fs-7 fw-bold">{{ number_format(($category->total_amount / max($summary['total_expenses'], 1)) * 100, 1) }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $avgAmount = $category->average_amount;
                                                $trendColor = $avgAmount > 1000 ? 'danger' : ($avgAmount > 500 ? 'warning' : 'success');
                                                $trendLabel = $avgAmount > 1000 ? __('accounting.high') : ($avgAmount > 500 ? __('accounting.medium') : __('accounting.low'));
                                            @endphp
                                            <span class="badge badge-light-{{ $trendColor }} py-2 px-3">
                                                {{ $trendLabel }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold text-gray-700 fs-6">
                                        <td>{{ __('accounting.total') }}</td>
                                        <td class="text-end">{{ number_format($expensesByCategory->sum('count')) }}</td>
                                        <td class="text-end text-danger">{{ number_format($expensesByCategory->sum('total_amount'), 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end">{{ number_format($expensesByCategory->avg('average_amount'), 2) }} {{ currency_symbol() }}</td>
                                        <td class="text-end">{{ number_format($expensesByCategory->max('max_amount'), 2) }} {{ currency_symbol() }}</td>
                                        <td>100%</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_expense_categories') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
                <!-- Top Expenses -->
                <div class="card card-flush">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-dollar fs-2 me-2 text-danger">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __('accounting.top_expenses') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.largest_expenses') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @if($topExpenses->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                        <th class="min-w-50px">#</th>
                                        <th class="min-w-150px">{{ __('accounting.date') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance_after') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.processed_by') }}</th>
                                        <th class="min-w-100px text-center">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @foreach($topExpenses as $index => $expense)
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-secondary py-2 px-3">
                                                {{ $index + 1 + (($topExpenses->currentPage() - 1) * $topExpenses->perPage()) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fs-6 fw-bold text-gray-800">{{ $expense->transaction_date->format('M d, Y') }}</span>
                                            <span class="fs-7 text-gray-500 d-block">{{ $expense->transaction_date->format('H:i:s') }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-6 text-gray-800">{{ Str::limit($expense->description, 50) }}</span>
                                            @if($expense->notes)
                                                <span class="fs-7 text-gray-500 d-block">{{ Str::limit($expense->notes, 30) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fs-6 fw-bold text-gray-800">{{ $expense->paymentMethod->name ?? 'N/A' }}</span>
                                                @if($expense->paymentMethod)
                                                    <span class="fs-7 text-gray-500">{{ $expense->paymentMethod->type ?? '' }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-danger py-2 px-3">{{ $expense->transaction_category }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-5 fw-bold text-danger">{{ number_format($expense->amount, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">{{ number_format($expense->balance_after, 2) }} {{ currency_symbol() }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($expense->user)
                                                <span class="fw-bold text-gray-700 fs-7">{{ $expense->user->name }}</span>
                                            @else
                                                <span class="text-gray-500 fs-7">System</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusColors = [
                                                    'COMPLETED' => 'success',
                                                    'PENDING' => 'warning',
                                                    'FAILED' => 'danger',
                                                    'CANCELLED' => 'secondary',
                                                    'REVERSED' => 'dark'
                                                ];
                                                $statusColor = $statusColors[$expense->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-light-{{ $statusColor }} py-2 px-3">
                                                {{ $expense->status }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-6 d-flex justify-content-center">
                            {{ $topExpenses->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="text-muted fs-6">{{ __('accounting.no_expenses_found') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
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