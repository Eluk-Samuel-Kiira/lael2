<x-app-layout>
    @section('title', __('accounting.expense_analysis'))
    @section('content')
    
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-4 gap-md-0">
            <!-- Left side - Title and Breadcrumb -->
            <div class="page-title d-flex flex-column">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-md-1 flex-column my-0">
                    {{__('accounting.expense_analysis')}}
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
                    <li class="breadcrumb-item text-muted">{{__('accounting.expense_analysis')}}</li>
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
                
                <!-- Summary Cards -->
                <div class="row g-5 g-xl-8 mb-8">
                    <!-- Total Expenses -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.total_expenses') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-danger me-2 lh-1">${{ number_format($summary['total_expenses'], 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.total_spent') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.expense_count') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">{{ number_format($summary['expense_count']) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Average Expense -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.average_expense') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-gray-800 me-2 lh-1">${{ number_format($summary['average_expense'], 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.per_transaction') }}</span>
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
                    
                    <!-- Largest Expense -->
                    <div class="col-xl-4">
                        <div class="card card-flush h-md-100">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ __('accounting.largest_expense') }}</h2>
                                </div>
                            </div>
                            <div class="card-body pt-1">
                                <div class="d-flex flex-column text-center my-7">
                                    <span class="fs-2hx fw-bold text-danger me-2 lh-1">${{ number_format($summary['largest_expense'], 2) }}</span>
                                    <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ __('accounting.single_expense') }}</span>
                                </div>
                                <div class="d-flex flex-stack">
                                    <span class="text-gray-500 fw-semibold fs-6">{{ __('accounting.date_range') }}</span>
                                    <span class="fs-6 fw-bold text-gray-700">
                                        {{ \Carbon\Carbon::parse($startDate)->diffInDays($endDate) + 1 }} {{ __('accounting.days') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Expenses by Category -->
                <div class="card mb-8">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.expenses_by_category') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.category_breakdown') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        <th class="min-w-200px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.expense_count') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.total_amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.average_amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.max_amount') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.percentage') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.trend') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
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
                                            <span class="fs-6 text-gray-700">{{ number_format($category->count) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-danger">${{ number_format($category->total_amount, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">${{ number_format($category->average_amount, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 fw-bold text-gray-800">${{ number_format($category->max_amount, 2) }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress h-8px w-100 me-3">
                                                    <div class="progress-bar bg-danger" role="progressbar" 
                                                         style="width: {{ ($category->total_amount / $summary['total_expenses']) * 100 }}%">
                                                    </div>
                                                </div>
                                                <span class="fs-7 fw-bold">{{ number_format(($category->total_amount / $summary['total_expenses']) * 100, 1) }}%</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if($category->average_amount > 0)
                                                <span class="badge badge-light-{{ $category->average_amount > 100 ? 'danger' : ($category->average_amount > 50 ? 'warning' : 'success') }}">
                                                    @if($category->average_amount > 100)
                                                        {{ __('accounting.high') }}
                                                    @elseif($category->average_amount > 50)
                                                        {{ __('accounting.medium') }}
                                                    @else
                                                        {{ __('accounting.low') }}
                                                    @endif
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold text-gray-700">
                                        <td>{{ __('accounting.total') }}</td>
                                        <td class="text-end">{{ number_format($expensesByCategory->sum('count')) }}</td>
                                        <td class="text-end">${{ number_format($expensesByCategory->sum('total_amount'), 2) }}</td>
                                        <td class="text-end">${{ number_format($expensesByCategory->avg('average_amount'), 2) }}</td>
                                        <td class="text-end">${{ number_format($expensesByCategory->max('max_amount'), 2) }}</td>
                                        <td>100%</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Top Expenses -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.top_expenses') }}</h3>
                        <div class="card-toolbar">
                            <span class="text-gray-500 fs-7">{{ __('accounting.largest_expenses') }}</span>
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
                                        <th class="min-w-100px">{{ __('accounting.category') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                        <th class="min-w-100px text-end">{{ __('accounting.balance_after') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topExpenses as $index => $expense)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <span class="fs-7 fw-bold text-gray-800">{{ $expense->transaction_date->format('M d, Y') }}</span>
                                            <span class="fs-8 text-gray-500 d-block">{{ $expense->transaction_date->format('H:i') }}</span>
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-800">{{ Str::limit($expense->description, 50) }}</span>
                                            @if($expense->notes)
                                                <span class="fs-8 text-gray-500 d-block">{{ Str::limit($expense->notes, 30) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fs-7 text-gray-800">{{ $expense->paymentMethod->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-danger">{{ $expense->transaction_category }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-5 fw-bold text-danger">${{ number_format($expense->amount, 2) }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="fs-6 text-gray-700">${{ number_format($expense->balance_after, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $expense->status === 'COMPLETED' ? 'success' : ($expense->status === 'PENDING' ? 'warning' : 'danger') }}">
                                                {{ $expense->status }}
                                            </span>
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
            window.location.href = '{{ route("accounting.expense-analysis") }}' + 
                '?start_date=' + startDate + '&end_date=' + endDate;
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