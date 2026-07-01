{{-- resources/views/reports/expenses/recurring.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.recurring_expenses_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <!-- Left side - Title and Breadcrumb -->
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('accounting.recurring_expenses_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('accounting.recurring_analysis') }}</li>
                            </ul>
                        </div>

                        <!-- Right side - Actions -->
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($recurringExpenses->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('accounting.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('accounting.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'recurringTable', filename: 'recurring_expenses_{{ date('Y_m_d') }}', sheetName: 'Recurring Expenses'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'recurringTable', filename: 'recurring_expenses_{{ date('Y_m_d') }}', format: 'csv'})">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('accounting.export_to_csv') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Filter Section --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.expenses.recurring') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6">
                                        {{-- Frequency --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.frequency') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-repeat fs-2"></i>
                                                </span>
                                                <select class="form-select" name="frequency">
                                                    <option value="">{{ __('accounting.all_frequencies') }}</option>
                                                    @foreach($frequencies as $key => $name)
                                                        <option value="{{ $key }}" {{ $frequency == $key ? 'selected' : '' }}>
                                                            {{ __($name) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Category --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-category fs-2"></i>
                                                </span>
                                                <select class="form-select" name="category_id">
                                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Status --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('accounting.status') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-status fs-2"></i>
                                                </span>
                                                <select class="form-select" name="status">
                                                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>
                                                        {{ __('accounting.active') }}
                                                    </option>
                                                    <option value="upcoming" {{ $status == 'upcoming' ? 'selected' : '' }}>
                                                        {{ __('accounting.upcoming') }} (7 days)
                                                    </option>
                                                    <option value="overdue" {{ $status == 'overdue' ? 'selected' : '' }}>
                                                        {{ __('accounting.overdue') }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex flex-column justify-content-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <button type="submit" class="btn btn-primary flex-grow-1" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.expenses.recurring') }}" class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.clear') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary Statistics --}}
                @if($recurringExpenses->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.recurring_summary') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $totalRecurring = $recurringExpenses->count();
                                        $totalMonthly = $recurringExpenses->sum('total_amount');
                                        $totalUpcoming = $upcomingNext30Days->count();
                                        $totalUpcomingAmount = $upcomingNext30Days->sum('total_amount');
                                    @endphp
                                    @foreach([
                                        ['key' => 'total_recurring', 'color' => 'primary', 'icon' => 'ki-repeat', 'label' => 'total_recurring_expenses', 'value' => $totalRecurring],
                                        ['key' => 'total_monthly', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'total_monthly_recurring', 'value' => '$' . number_format($totalMonthly, 2)],
                                        ['key' => 'annual_projection', 'color' => 'info', 'icon' => 'ki-chart-simple', 'label' => 'annual_projection', 'value' => '$' . number_format($annualProjection, 2)],
                                        ['key' => 'upcoming_30_days', 'color' => 'warning', 'icon' => 'ki-calendar-8', 'label' => 'upcoming_30_days', 'value' => $totalUpcoming],
                                        ['key' => 'upcoming_amount', 'color' => 'danger', 'icon' => 'ki-dollar', 'label' => 'upcoming_amount', 'value' => '$' . number_format($totalUpcomingAmount, 2)],
                                        ['key' => 'avg_recurring', 'color' => 'secondary', 'icon' => 'ki-calculator', 'label' => 'average_recurring', 'value' => '$' . number_format($totalRecurring > 0 ? $totalMonthly / $totalRecurring : 0, 2)]
                                    ] as $stat)
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}">
                                                        @for($i = 1; $i <= 2; $i++)
                                                        <span class="path{{ $i }}"></span>
                                                        @endfor
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $stat['value'] }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('accounting.' . $stat['label']) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Recurring Expenses Table --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('accounting.recurring_expenses') }}</h3>
                                    </div>
                                    @if($recurringExpenses->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $recurringExpenses->count() }} {{ __('accounting.expenses') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($recurringExpenses->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="recurringTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-200px ps-4">{{ __('accounting.description') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.category') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.frequency') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.next_date') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.amount') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.annual_total') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.payment_method') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.days_until') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recurringExpenses as $expense)
                                                @php
                                                    $nextDate = $expense->next_recurring_date ? \Carbon\Carbon::parse($expense->next_recurring_date) : null;
                                                    $today = \Carbon\Carbon::today();
                                                    
                                                    // Calculate days until next payment
                                                    $daysUntil = $nextDate ? $today->diffInDays($nextDate, false) : null;
                                                    
                                                    // Determine status
                                                    if (!$nextDate) {
                                                        $statusColor = 'secondary';
                                                        $statusText = __('accounting.no_date');
                                                    } elseif ($daysUntil < 0) {
                                                        $statusColor = 'danger';
                                                        $statusText = __('accounting.overdue');
                                                    } elseif ($daysUntil <= 7) {
                                                        $statusColor = 'warning';
                                                        $statusText = __('accounting.upcoming');
                                                    } else {
                                                        $statusColor = 'success';
                                                        $statusText = __('accounting.active');
                                                    }
                                                    
                                                    // Calculate annual total
                                                    $annualMultiplier = match($expense->recurring_frequency) {
                                                        'weekly' => 52,
                                                        'monthly' => 12,
                                                        'quarterly' => 4,
                                                        'annually' => 1,
                                                        default => 12
                                                    };
                                                    $annualTotal = $expense->total_amount * $annualMultiplier;
                                                @endphp
                                                <tr>
                                                    <td class="ps-4">
                                                        <div class="d-flex align-items-center">
                                                            <div class="symbol symbol-40px symbol-circle me-3">
                                                                <div class="symbol-label bg-light-primary">
                                                                    <i class="ki-duotone ki-repeat fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $expense->description }}</span>
                                                                @if($expense->vendor_name)
                                                                <small class="text-muted">
                                                                    {{ __('accounting.vendor') }}: {{ $expense->vendor_name }}
                                                                </small>
                                                                @endif
                                                                <small class="text-muted">
                                                                    {{ __('accounting.expense_number') }}: {{ $expense->expense_number }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-info">
                                                            {{ $expense->category->name ?? 'N/A' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php
                                                            $frequencyColors = [
                                                                'weekly' => 'primary',
                                                                'monthly' => 'success',
                                                                'quarterly' => 'info',
                                                                'annually' => 'warning'
                                                            ];
                                                            $frequencyColor = $frequencyColors[$expense->recurring_frequency] ?? 'secondary';
                                                        @endphp
                                                        <span class="badge badge-light-{{ $frequencyColor }}">
                                                            {{ __($expense->recurring_frequency) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($nextDate)
                                                        <span class="fw-semibold text-gray-800">{{ $nextDate->format('Y-m-d') }}</span>
                                                        <div class="mt-1">
                                                            <small class="text-muted">
                                                                {{ $nextDate->format('D, M j') }}
                                                            </small>
                                                        </div>
                                                        @else
                                                        <span class="text-muted">{{ __('accounting.not_set') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($expense->total_amount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-info">${{ number_format($annualTotal, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        @if($expense->paymentMethod)
                                                            <span class="badge badge-light-primary">
                                                                {{ $expense->paymentMethod->name }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-light-secondary">
                                                                {{ __('accounting.no_payment_method') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $statusColor }}">
                                                            {{ $statusText }}
                                                            @if($daysUntil !== null && $daysUntil < 0)
                                                            <i class="ki-duotone ki-warning fs-4 ms-1"></i>
                                                            @elseif($daysUntil !== null && $daysUntil <= 7)
                                                            <i class="ki-duotone ki-time fs-4 ms-1"></i>
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($daysUntil !== null)
                                                            @if($daysUntil < 0)
                                                            <span class="text-danger fw-bold">
                                                                {{ abs($daysUntil) }} {{ __('accounting.days_overdue') }}
                                                            </span>
                                                            @elseif($daysUntil == 0)
                                                            <span class="text-warning fw-bold">
                                                                {{ __('accounting.due_today') }}
                                                            </span>
                                                            @else
                                                            <span class="text-gray-700 fw-semibold">
                                                                {{ $daysUntil }} {{ __('accounting.days') }}
                                                            </span>
                                                            @endif
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <div class="card-body">
                                    <div class="text-center py-10">
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('accounting.no_recurring_expenses_found') }}</p>
                                        @if(request()->hasAny(['frequency', 'category_id', 'status']))
                                        <a href="{{ route('reports.expenses.recurring') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('accounting.clear_filters_view_all') }}
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- END Recurring Expenses Table --}}
                
                {{-- Monthly Projection Chart --}}
                @if($recurringExpenses->count() > 0 && count($monthlyProjection) > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_projection') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="monthlyProjectionChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                {{-- Breakdown by Frequency --}}
                @if($byFrequency->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.breakdown_by_frequency') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach($byFrequency as $freq => $data)
                                    @php
                                        $frequencyColors = [
                                            'weekly' => 'primary',
                                            'monthly' => 'success', 
                                            'quarterly' => 'info',
                                            'annually' => 'warning'
                                        ];
                                        $color = $frequencyColors[$freq] ?? 'secondary';
                                    @endphp
                                    <div class="col-md-6 col-lg-3">
                                        <div class="card card-flush bg-light-{{ $color }} border border-{{ $color }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-3">
                                                    <i class="ki-duotone ki-repeat fs-2tx text-{{ $color }}">
                                                        @for($i = 1; $i <= 2; $i++)
                                                        <span class="path{{ $i }}"></span>
                                                        @endfor
                                                    </i>
                                                </div>
                                                <div class="mb-2">
                                                    <span class="fs-2 fw-bold text-gray-800 d-block">
                                                        {{ $data['count'] }}
                                                    </span>
                                                    <small class="text-gray-600 fw-semibold d-block mt-1">
                                                        {{ __($freq) }} {{ __('accounting.expenses') }}
                                                    </small>
                                                </div>
                                                <div class="border-top border-gray-300 pt-2 mt-2">
                                                    <span class="fs-4 fw-bold text-gray-700 d-block">
                                                        ${{ number_format($data['total_monthly'], 2) }}
                                                    </span>
                                                    <small class="text-gray-600 fw-semibold d-block mt-1">
                                                        {{ __('accounting.monthly_total') }}
                                                    </small>
                                                </div>
                                                <div class="border-top border-gray-300 pt-2 mt-2">
                                                    <span class="fs-4 fw-bold text-success d-block">
                                                        ${{ number_format($data['total_annual'], 2) }}
                                                    </span>
                                                    <small class="text-gray-600 fw-semibold d-block mt-1">
                                                        {{ __('accounting.annual_projection') }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                {{-- Upcoming Expenses in Next 30 Days --}}
                @if($upcomingNext30Days->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.upcoming_next_30_days') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.date') }}</th>
                                                <th>{{ __('accounting.description') }}</th>
                                                <th>{{ __('accounting.amount') }}</th>
                                                <th>{{ __('accounting.frequency') }}</th>
                                                <th>{{ __('accounting.payment_method') }}</th>
                                                <th>{{ __('accounting.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($upcomingNext30Days as $expense)
                                            @php
                                                $nextDate = \Carbon\Carbon::parse($expense->next_recurring_date);
                                                $today = \Carbon\Carbon::today();
                                                $daysUntil = $today->diffInDays($nextDate, false);
                                                
                                                if ($daysUntil < 0) {
                                                    $statusColor = 'danger';
                                                    $statusText = __('accounting.overdue');
                                                } elseif ($daysUntil == 0) {
                                                    $statusColor = 'warning';
                                                    $statusText = __('accounting.due_today');
                                                } elseif ($daysUntil <= 3) {
                                                    $statusColor = 'warning';
                                                    $statusText = __('accounting.due_soon');
                                                } else {
                                                    $statusColor = 'success';
                                                    $statusText = __('accounting.scheduled');
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4 fw-semibold">
                                                    {{ $nextDate->format('Y-m-d') }}
                                                    <div class="mt-1">
                                                        <small class="text-muted">
                                                            {{ $nextDate->format('D') }}
                                                            @if($daysUntil == 0)
                                                            <span class="badge badge-light-warning badge-sm ms-1">
                                                                {{ __('accounting.today') }}
                                                            </span>
                                                            @endif
                                                        </small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-gray-800 fw-bold">{{ Str::limit($expense->description, 40) }}</span>
                                                    @if($expense->vendor_name)
                                                    <div class="mt-1">
                                                        <small class="text-muted">{{ $expense->vendor_name }}</small>
                                                    </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">${{ number_format($expense->total_amount, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-primary">
                                                        {{ __($expense->recurring_frequency) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($expense->paymentMethod)
                                                        <span class="badge badge-light-info">
                                                            {{ $expense->paymentMethod->name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $statusColor }}">
                                                        {{ $statusText }}
                                                        @if($daysUntil <= 3)
                                                        <i class="ki-duotone ki-warning fs-4 ms-1"></i>
                                                        @endif
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
                @endif
                
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($recurringExpenses->count() > 0 && count($monthlyProjection) > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Prepare monthly projection data for chart
        const monthlyProjection = @json($monthlyProjection);
        
        // Extract data arrays for chart
        const months = [];
        const projectedTotals = [];
        const expenseCounts = [];
        
        // Sort months chronologically
        const sortedMonths = Object.keys(monthlyProjection).sort();
        
        sortedMonths.forEach(monthKey => {
            const data = monthlyProjection[monthKey];
            months.push(data.month);
            projectedTotals.push(parseFloat(data.projected_total));
            expenseCounts.push(parseInt(data.expense_count));
        });
        
        // Initialize chart
        const chartOptions = {
            series: [{
                name: '{{ __("accounting.projected_amount") }}',
                data: projectedTotals,
                type: 'column',
                color: '#3E97FF'
            }, {
                name: '{{ __("accounting.expense_count") }}',
                data: expenseCounts,
                type: 'line',
                color: '#50CD89'
            }],
            chart: {
                type: 'line',
                height: 350,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                }
            },
            stroke: {
                width: [0, 3],
                curve: 'smooth'
            },
            plotOptions: {
                bar: {
                    columnWidth: '50%'
                }
            },
            xaxis: {
                categories: months,
                labels: {
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            yaxis: [
                {
                    seriesName: '{{ __("accounting.projected_amount") }}',
                    title: {
                        text: 'Amount ($)'
                    },
                    labels: {
                        formatter: function(val) {
                            return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 0});
                        }
                    }
                },
                {
                    seriesName: '{{ __("accounting.expense_count") }}',
                    opposite: true,
                    title: {
                        text: '{{ __("accounting.expense_count") }}'
                    },
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0);
                        }
                    }
                }
            ],
            tooltip: {
                y: [
                    {
                        formatter: function(val) {
                            return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                        }
                    },
                    {
                        formatter: function(val) {
                            return val + ' {{ __("accounting.expenses") }}';
                        }
                    }
                ]
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center'
            },
            dataLabels: {
                enabled: false
            }
        };
        
        // Render the chart
        const chart = new ApexCharts(document.querySelector("#monthlyProjectionChart"), chartOptions);
        chart.render();
    });
</script>
@endif
@endpush

@endsection