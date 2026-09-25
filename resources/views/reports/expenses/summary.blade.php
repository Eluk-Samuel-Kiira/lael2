@extends('layouts.app')

@section('title', __('accounting.expense_report'))

@section('content')
@if (tenant_can('advanced_reports'))
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- ═══════════════════════════════════════════════════════════
                     TOOLBAR
                ═══════════════════════════════════════════════════════════ --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container"
                         class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">

                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('accounting.expense_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('accounting.expense_summary') }}</li>
                            </ul>
                        </div>

                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($topExpenses->count() > 0)
                                <div class="dropdown w-100 w-sm-auto">
                                    <button class="btn btn-sm btn-primary w-100 dropdown-toggle"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                        <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                        <span>{{ __('accounting.export') }}</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)"
                                               onclick="exportCurrentPage({tableId: 'expensesTable', filename: 'expenses_report_{{ date('Y_m_d') }}', sheetName: 'Expenses Report'})">
                                                <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                                {{ __('accounting.export_to_excel') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="javascript:void(0)"
                                               onclick="exportCurrentPage({tableId: 'expensesTable', filename: 'expenses_report_{{ date('Y_m_d') }}', format: 'csv'})">
                                                <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                                {{ __('accounting.export_to_csv') }}
                                            </a>
                                        </li>
                                        @if($dailyBreakdown->count() > 0)
                                            <li>
                                                <a class="dropdown-item" href="javascript:void(0)"
                                                   onclick="exportCurrentPage({tableId: 'dailyTable', filename: 'daily_expenses_{{ date('Y_m_d') }}', sheetName: 'Daily Breakdown'})">
                                                    <i class="ki-duotone ki-file-sheet fs-2 me-2 text-warning"></i>
                                                    {{ __('accounting.export_daily_data') }}
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════
                     FILTERS
                ═══════════════════════════════════════════════════════════ --}}
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
                                <div class="card-toolbar">
                                    <span class="badge badge-light-info fs-7">
                                        <i class="ki-duotone ki-calendar-8 fs-4 me-1"></i>
                                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                                        &mdash;
                                        {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                                    </span>
                                </div>
                            </div>

                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.expenses.summary') }}" id="filterForm">

                                    {{-- Row 1: Date range + Category + Payment method --}}
                                    <div class="row g-4 mb-4">
                                        <div class="col-xl-4">
                                            <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                            <div class="d-flex flex-column flex-sm-row gap-2">
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control"
                                                           name="start_date" value="{{ $startDate }}" required>
                                                </div>
                                                <span class="d-none d-sm-flex align-items-center text-gray-500 px-1">
                                                    {{ __('accounting.to') }}
                                                </span>
                                                <div class="input-group">
                                                    <span class="input-group-text">
                                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                    </span>
                                                    <input type="date" class="form-control"
                                                           name="end_date" value="{{ $endDate }}" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-4">
                                            <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="category_id" data-control="select2">
                                                    <option value="">{{ __('accounting.all_categories') }}</option>
                                                    @foreach($categories as $category)
                                                        <option value="{{ $category->id }}"
                                                                {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}
                                                                data-requires-receipt="{{ $category->requires_receipt }}">
                                                            {{ $category->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-4">
                                            <label class="form-label fw-semibold">{{ __('accounting.payment_method') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="payment_method_id" data-control="select2">
                                                    <option value="">{{ __('accounting.all_payment_methods') }}</option>
                                                    @foreach($paymentMethods as $method)
                                                        <option value="{{ $method->id }}"
                                                                {{ (string) $paymentMethodId === (string) $method->id ? 'selected' : '' }}
                                                                data-type="{{ $method->type }}">
                                                            {{ $method->name }}@if($method->is_default) ({{ __('accounting.default') }})@endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Row 2: Status + Vendor + Employee --}}
                                    <div class="row g-4 mb-4">
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.payment_status') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="payment_status" data-control="select2">
                                                    <option value="">{{ __('accounting.all_statuses') }}</option>
                                                    <option value="pending"    {{ $paymentStatus === 'pending'    ? 'selected' : '' }}>{{ __('accounting.pending') }}</option>
                                                    <option value="paid"       {{ $paymentStatus === 'paid'       ? 'selected' : '' }}>{{ __('accounting.paid') }}</option>
                                                    <option value="reimbursed" {{ $paymentStatus === 'reimbursed' ? 'selected' : '' }}>{{ __('accounting.reimbursed') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.vendor_name') }}</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control"
                                                       name="vendor_name"
                                                       value="{{ $vendorName }}"
                                                       placeholder="{{ __('accounting.enter_vendor_name') }}"
                                                       maxlength="200">
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.location') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="location_id" data-control="select2" >
                                                    <option value="">{{ __('pagination.all_locations') }}</option>
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}"
                                                                {{ (string) $locationId === (string) $location->id ? 'selected' : '' }}>
                                                            {{ $location->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.employee') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="employee_id" data-control="select2">
                                                    <option value="">{{ __('accounting.all_employees') }}</option>
                                                    @foreach($employees as $employee)
                                                        <option value="{{ $employee->id }}"
                                                                {{ (string) $employeeId === (string) $employee->id ? 'selected' : '' }}>
                                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                                            @if($employee->employee_id)
                                                                ({{ $employee->employee_id }})
                                                            @endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Row 3: Boolean filters + Actions --}}
                                    <div class="row g-4">
                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.requires_receipt_filter') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="requires_receipt" data-control="select2">
                                                    <option value=""  {{ ($requiresReceipt === null || $requiresReceipt === '') ? 'selected' : '' }}>{{ __('accounting.all_statuses') }}</option>
                                                    <option value="1" {{ $requiresReceipt === '1' ? 'selected' : '' }}>{{ __('accounting.yes') }}</option>
                                                    <option value="0" {{ $requiresReceipt === '0' ? 'selected' : '' }}>{{ __('accounting.no') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.recurring_filter') }}</label>
                                            <div class="input-group">
                                                <select class="form-select" name="is_recurring" data-control="select2">
                                                    <option value=""  {{ ($isRecurring === null || $isRecurring === '') ? 'selected' : '' }}>{{ __('accounting.all_statuses') }}</option>
                                                    <option value="1" {{ $isRecurring === '1' ? 'selected' : '' }}>{{ __('accounting.yes') }}</option>
                                                    <option value="0" {{ $isRecurring === '0' ? 'selected' : '' }}>{{ __('accounting.no') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-xl-6 d-flex align-items-end">
                                            <div class="d-flex flex-column flex-sm-row gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-grow-1">
                                                    <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.apply_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.apply') }}</span>
                                                </button>
                                                <a href="{{ route('reports.expenses.summary') }}"
                                                   class="btn btn-light btn-active-light-primary flex-grow-1">
                                                    <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                    <span class="d-none d-sm-inline">{{ __('accounting.clear_filters') }}</span>
                                                    <span class="d-inline d-sm-none">{{ __('accounting.clear') }}</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                {{-- Active filter chips --}}
                                @php
                                    $activeFilters = array_filter([
                                        $categoryId
                                            ? ['label' => __('accounting.category'),       'value' => $categories->firstWhere('id', $categoryId)?->name]
                                            : null,
                                        $paymentMethodId
                                            ? ['label' => __('accounting.payment_method'), 'value' => $paymentMethods->firstWhere('id', $paymentMethodId)?->name]
                                            : null,
                                        $paymentStatus
                                            ? ['label' => __('accounting.payment_status'), 'value' => __('accounting.' . $paymentStatus)]
                                            : null,
                                        $employeeId
                                            ? ['label' => __('accounting.employee'),       'value' => trim(
                                                ($employees->firstWhere('id', $employeeId)?->first_name ?? '') . ' ' .
                                                ($employees->firstWhere('id', $employeeId)?->last_name ?? '')
                                              )]
                                            : null,
                                        $vendorName
                                            ? ['label' => __('accounting.vendor_name'),    'value' => $vendorName]
                                            : null,
                                        ($requiresReceipt !== null && $requiresReceipt !== '')
                                            ? ['label' => __('accounting.requires_receipt_filter'), 'value' => $requiresReceipt === '1' ? __('accounting.yes') : __('accounting.no')]
                                            : null,
                                        ($isRecurring !== null && $isRecurring !== '')
                                            ? ['label' => __('accounting.recurring_filter'),        'value' => $isRecurring === '1' ? __('accounting.yes') : __('accounting.no')]
                                            : null,
                                    ]);
                                @endphp

                                @if(count($activeFilters) > 0)
                                    <div class="separator separator-dashed my-4"></div>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span class="text-muted fw-semibold me-2">
                                            <i class="ki-duotone ki-filter fs-5 me-1"></i>
                                            {{ __('accounting.active_filters') }}:
                                        </span>
                                        @foreach($activeFilters as $filter)
                                            <span class="badge badge-light-primary fs-7">
                                                <strong>{{ $filter['label'] }}:</strong>&nbsp;{{ $filter['value'] }}
                                            </span>
                                        @endforeach
                                        <a href="{{ route('reports.expenses.summary') }}"
                                           class="text-danger fs-7 text-hover-primary ms-2 d-inline-flex align-items-center gap-1">
                                            <i class="ki-duotone ki-cross fs-5"></i>
                                            {{ __('accounting.clear_all') }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════════════════════════════════════════════════
                     SUMMARY STATISTICS
                ═══════════════════════════════════════════════════════════ --}}
                @if($summary['total_expenses'] > 0)
                    <div class="row mb-6">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('accounting.summary_statistics') }}</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="row g-6">
                                        @foreach([
                                            ['key' => 'total_expenses', 'color' => 'primary',   'icon' => 'ki-receipt',      'label' => 'total_expenses',    'money' => false],
                                            ['key' => 'total_amount',   'color' => 'success',   'icon' => 'ki-dollar',       'label' => 'total_amount',      'money' => true],
                                            ['key' => 'total_tax',      'color' => 'info',      'icon' => 'ki-chart-simple', 'label' => 'total_tax',         'money' => true],
                                            ['key' => 'avg_expense',    'color' => 'warning',   'icon' => 'ki-calculator',   'label' => 'average_expense',   'money' => true],
                                            ['key' => 'max_expense',    'color' => 'danger',    'icon' => 'ki-arrow-up',     'label' => 'largest_expense',   'money' => true],
                                            ['key' => 'min_expense',    'color' => 'secondary', 'icon' => 'ki-arrow-down',   'label' => 'smallest_expense',  'money' => true],
                                        ] as $stat)
                                            <div class="col-md-6 col-lg-2">
                                                <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                                    <div class="card-body d-flex flex-column justify-content-center text-center">
                                                        <div class="mb-4">
                                                            <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </div>
                                                        <div class="mb-1">
                                                            <span class="fs-2 fw-bold text-gray-800">
                                                                @if($stat['money'])
                                                                    {{ currency_symbol() }} {{ number_format($summary[$stat['key']], 2) }}
                                                                @else
                                                                    {{ number_format($summary[$stat['key']]) }}
                                                                @endif
                                                            </span>
                                                        </div>
                                                        <div class="text-gray-600 fw-semibold fs-7">
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

                {{-- ═══════════════════════════════════════════════════════════
                     TOP EXPENSES
                ═══════════════════════════════════════════════════════════ --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.top_expenses') }}</h3>
                                </div>
                                @if($topExpenses->count() > 0)
                                    <div class="card-toolbar">
                                        <span class="badge badge-light-primary fs-7">
                                            {{ __('accounting.total') }}: {{ $summary['total_expenses'] }} {{ __('accounting.expenses') }}
                                        </span>
                                    </div>
                                @endif
                            </div>

                            @if($topExpenses->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="expensesTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-100px ps-4">{{ __('accounting.date') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.expense_number') }}</th>
                                                    <th class="min-w-200px">{{ __('accounting.description') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.vendor') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.category') }}</th>
                                                    <th class="min-w-100px text-end">{{ __('accounting.amount') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.status') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.payment_method_name') }}</th>
                                                    <th class="min-w-120px pe-4">{{ __('accounting.employee') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($topExpenses as $expense)
                                                    @php
                                                        // Build a compact payload for the detail modal — avoids dumping the whole model
                                                        $expensePayload = [
                                                            'id'             => $expense->id,
                                                            'expense_number' => $expense->expense_number,
                                                            'date'           => $expense->date?->format('Y-m-d'),
                                                            'description'    => $expense->description,
                                                            'vendor_name'    => $expense->vendor_name,
                                                            'category'       => $expense->category?->name,
                                                            'amount'         => (float) $expense->total_amount,
                                                            'gross_amount'   => (float) $expense->gross_amount,
                                                            'tax_amount'     => (float) $expense->tax_amount,
                                                            'net_amount'     => (float) $expense->net_amount,
                                                            'status'         => $expense->payment_status,
                                                            'paid_date'      => $expense->paid_date?->format('Y-m-d'),
                                                            'payment_method' => $expense->paymentMethod?->name,
                                                            'employee'       => $expense->employee
                                                                ? trim($expense->employee->first_name . ' ' . $expense->employee->last_name)
                                                                : null,
                                                            'is_recurring'   => (bool) $expense->is_recurring,
                                                            'recurring_frequency' => $expense->recurring_frequency,
                                                            'receipt_url'    => $expense->receipt_url,
                                                        ];
                                                    @endphp
                                                    <tr>
                                                        <td class="ps-4">
                                                            <span class="fw-semibold text-gray-800">
                                                                {{ $expense->date?->format('Y-m-d') }}
                                                            </span>
                                                            @if($expense->is_recurring)
                                                                <div class="mt-1">
                                                                    <span class="badge badge-light-warning badge-sm">
                                                                        <i class="ki-duotone ki-repeat fs-4 me-1"></i>
                                                                        {{ __('accounting.recurring') }}
                                                                    </span>
                                                                </div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <a href="javascript:void(0)"
                                                               onclick='showExpenseDetail({!! json_encode($expensePayload, JSON_HEX_APOS | JSON_HEX_QUOT) !!})'
                                                               class="text-primary fw-bold text-hover-primary">
                                                                {{ $expense->expense_number }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            <span class="text-gray-800 fw-semibold">{{ Str::limit($expense->description, 50) }}</span>
                                                            @if($expense->tax_amount > 0)
                                                                <small class="d-block text-muted">
                                                                    {{ __('accounting.tax') }}: {{ currency_symbol() }} {{ number_format($expense->tax_amount, 2) }}
                                                                </small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span class="text-gray-600">{{ $expense->vendor_name ?? 'N/A' }}</span>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-light-info">
                                                                {{ $expense->category->name ?? 'N/A' }}
                                                            </span>
                                                        </td>
                                                        <td class="text-end">
                                                            <span class="fw-bold text-success">
                                                                {{ currency_symbol() }} {{ number_format($expense->total_amount, 2) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @php
                                                                $statusConfig = [
                                                                    'pending'    => ['color' => 'warning',   'icon' => 'ki-time'],
                                                                    'paid'       => ['color' => 'success',   'icon' => 'ki-check'],
                                                                    'reimbursed' => ['color' => 'info',      'icon' => 'ki-refresh'],
                                                                ];
                                                                $config = $statusConfig[$expense->payment_status] ?? ['color' => 'secondary', 'icon' => 'ki-question'];
                                                            @endphp
                                                            <span class="badge badge-light-{{ $config['color'] }} d-inline-flex align-items-center gap-1">
                                                                <i class="ki-duotone {{ $config['icon'] }} fs-4"></i>
                                                                {{ __('accounting.' . $expense->payment_status) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            @if($expense->paymentMethod)
                                                                @if($expense->paymentMethod->is_default)
                                                                    <span class="badge badge-light-success d-inline-flex align-items-center gap-1">
                                                                        <i class="ki-duotone ki-star fs-4"></i>
                                                                        {{ $expense->paymentMethod->name }}
                                                                    </span>
                                                                @else
                                                                    <span class="badge badge-light-primary">
                                                                        {{ $expense->paymentMethod->name }}
                                                                    </span>
                                                                @endif
                                                            @else
                                                                <span class="badge badge-light-secondary">
                                                                    {{ __('accounting.no_payment_method') }}
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="pe-4">
                                                            @if($expense->employee)
                                                                <div class="d-flex flex-column">
                                                                    <span class="fw-semibold text-gray-800">
                                                                        {{ $expense->employee->first_name }} {{ $expense->employee->last_name }}
                                                                    </span>
                                                                    @if($expense->employee->employee_id)
                                                                        <small class="text-muted">{{ $expense->employee->employee_id }}</small>
                                                                    @endif
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
                            @else
                                <div class="card-body">
                                    <div class="text-center py-10">
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('accounting.no_expenses_found') }}</p>
                                        @if(count($activeFilters ?? []) > 0)
                                            <a href="{{ route('reports.expenses.summary') }}" class="btn btn-light-primary mt-3">
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

                {{-- ═══════════════════════════════════════════════════════════
                     DAILY BREAKDOWN
                ═══════════════════════════════════════════════════════════ --}}
                @if($dailyBreakdown->count() > 0)
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header border-0">
                                    <div class="card-title d-flex align-items-center">
                                        <i class="ki-duotone ki-calendar-8 fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('accounting.daily_breakdown') }}</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <span class="badge badge-light-primary fs-7">
                                            {{ $dailyBreakdown->count() }} {{ __('accounting.days') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="dailyTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="ps-4">{{ __('accounting.date') }}</th>
                                                    <th class="text-center">{{ __('accounting.expense_count') }}</th>
                                                    <th class="text-end">{{ __('accounting.daily_total') }}</th>
                                                    <th class="text-end">{{ __('accounting.daily_tax') }}</th>
                                                    <th class="text-end">{{ __('accounting.daily_average') }}</th>
                                                    <th class="pe-4">{{ __('accounting.daily_percentage') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($dailyBreakdown as $daily)
                                                    @php
                                                        $percentage = $summary['total_amount'] > 0
                                                            ? ($daily->total / $summary['total_amount']) * 100
                                                            : 0;
                                                    @endphp
                                                    <tr>
                                                        <td class="ps-4 fw-semibold">
                                                            {{ \Carbon\Carbon::parse($daily->date)->format('d M Y') }}
                                                            <small class="text-muted d-block">{{ $daily->date }}</small>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge badge-light-primary">{{ $daily->count }}</span>
                                                        </td>
                                                        <td class="text-end fw-bold text-success">
                                                            {{ currency_symbol() }} {{ number_format($daily->total, 2) }}
                                                        </td>
                                                        <td class="text-end text-info">
                                                            {{ currency_symbol() }} {{ number_format($daily->tax, 2) }}
                                                        </td>
                                                        <td class="text-end text-gray-600">
                                                            {{ currency_symbol() }} {{ number_format($daily->average, 2) }}
                                                        </td>
                                                        <td class="pe-4">
                                                            <div class="d-flex align-items-center">
                                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                                    <div class="progress-bar bg-primary"
                                                                         role="progressbar"
                                                                         style="width: {{ min($percentage, 100) }}%;"
                                                                         aria-valuenow="{{ $percentage }}"
                                                                         aria-valuemin="0"
                                                                         aria-valuemax="100"></div>
                                                                </div>
                                                                <span class="fw-semibold text-gray-700" style="min-width: 50px;">
                                                                    {{ number_format($percentage, 1) }}%
                                                                </span>
                                                            </div>
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
{{-- Expense detail modal --}}
<div class="modal fade" id="expenseDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white fw-bold">
                    <i class="ki-duotone ki-receipt fs-2 me-2"></i>
                    {{ __('accounting.expense_detail') }}
                    <span class="opacity-75 ms-1" id="expDetailNumber"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-6">
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.date') }}</div>
                        <div class="fw-bold text-gray-800" id="expDetailDate">—</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.status') }}</div>
                        <div id="expDetailStatus">—</div>
                    </div>
                    <div class="col-md-12">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.description') }}</div>
                        <div class="fw-semibold text-gray-800" id="expDetailDescription">—</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.vendor') }}</div>
                        <div class="fw-bold text-gray-800" id="expDetailVendor">—</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.category') }}</div>
                        <div><span class="badge badge-light-info" id="expDetailCategory">—</span></div>
                    </div>
                </div>

                <div class="bg-light rounded-3 p-5 mb-5">
                    <div class="row g-4">
                        <div class="col-4">
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.gross_amount') }}</div>
                            <div class="fw-bold text-gray-800 fs-5" id="expDetailGross">—</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.tax') }}</div>
                            <div class="fw-bold text-info fs-5" id="expDetailTax">—</div>
                        </div>
                        <div class="col-4">
                            <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.total') }}</div>
                            <div class="fw-bold text-success fs-4" id="expDetailTotal">—</div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.payment_method_name') }}</div>
                        <div class="fw-semibold text-gray-800" id="expDetailPaymentMethod">—</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.employee') }}</div>
                        <div class="fw-semibold text-gray-800" id="expDetailEmployee">—</div>
                    </div>
                    <div class="col-md-6 d-none" id="expDetailRecurringWrap">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.recurring_filter') }}</div>
                        <div>
                            <span class="badge badge-light-warning">
                                <i class="ki-duotone ki-repeat fs-4 me-1"></i>
                                <span id="expDetailRecurringFrequency"></span>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6 d-none" id="expDetailReceiptWrap">
                        <div class="text-muted fs-8 fw-semibold text-uppercase mb-1">{{ __('accounting.receipt') }}</div>
                        <a href="#" id="expDetailReceiptLink" target="_blank" class="btn btn-sm btn-light-primary">
                            <i class="ki-duotone ki-file fs-4 me-1"></i>
                            {{ __('accounting.view_receipt') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('accounting.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

@endif
@endsection

<script>
function showExpenseDetail(expense) {
    const sym = '{{ currency_symbol() }}';

    const money = (v) => sym + ' ' + (Number(v) || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    document.getElementById('expDetailNumber').textContent       = '· ' + (expense.expense_number || '');
    document.getElementById('expDetailDate').textContent         = expense.date || '—';
    document.getElementById('expDetailDescription').textContent  = expense.description || '—';
    document.getElementById('expDetailVendor').textContent       = expense.vendor_name || 'N/A';
    document.getElementById('expDetailCategory').textContent     = expense.category || 'N/A';
    document.getElementById('expDetailGross').textContent        = money(expense.gross_amount);
    document.getElementById('expDetailTax').textContent          = money(expense.tax_amount);
    document.getElementById('expDetailTotal').textContent        = money(expense.amount);
    document.getElementById('expDetailPaymentMethod').textContent= expense.payment_method || '—';
    document.getElementById('expDetailEmployee').textContent     = expense.employee || '—';

    // Status badge
    const statusColors = {
        pending:    'warning',
        paid:       'success',
        reimbursed: 'info',
    };
    const color = statusColors[expense.status] || 'secondary';
    document.getElementById('expDetailStatus').innerHTML =
        `<span class="badge badge-light-${color}">${(expense.status || '').charAt(0).toUpperCase() + (expense.status || '').slice(1)}</span>`;

    // Recurring block
    const recurringWrap = document.getElementById('expDetailRecurringWrap');
    if (expense.is_recurring) {
        document.getElementById('expDetailRecurringFrequency').textContent =
            (expense.recurring_frequency || '').charAt(0).toUpperCase() +
            (expense.recurring_frequency || '').slice(1);
        recurringWrap.classList.remove('d-none');
    } else {
        recurringWrap.classList.add('d-none');
    }

    // Receipt block
    const receiptWrap = document.getElementById('expDetailReceiptWrap');
    const receiptLink = document.getElementById('expDetailReceiptLink');
    if (expense.receipt_url) {
        receiptLink.href = expense.receipt_url;
        receiptWrap.classList.remove('d-none');
    } else {
        receiptWrap.classList.add('d-none');
    }

    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('expenseDetailModal'));
    modal.show();
}
</script>