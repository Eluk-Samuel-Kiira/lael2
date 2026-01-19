{{-- resources/views/reports/expenses/audit.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.expense_audit_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
                        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                                {{ __('accounting.expense_audit_report') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('accounting.audit_and_compliance') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($auditStats['total_items'] > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'auditItemsTable', filename: 'expense_audit_{{ date('Y_m_d') }}', sheetName: 'Audit Items'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'auditItemsTable', filename: 'expense_audit_{{ date('Y_m_d') }}', format: 'csv'})">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('accounting.export_to_csv') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" onclick="generateAuditPDF()">
                                            <i class="ki-duotone ki-file-pdf fs-2 me-2 text-danger"></i>
                                            {{ __('accounting.export_to_pdf') }}
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
                                <form method="GET" action="{{ route('reports.expenses.audit') }}" id="filterForm">
                                    <div class="row g-6">
                                        {{-- Date Range --}}
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.start_date') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label fw-semibold">{{ __('accounting.end_date') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                            </div>
                                        </div>
                                        
                                        {{-- Audit Type --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.audit_type') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-search fs-2"></i>
                                                </span>
                                                <select class="form-select" name="audit_type" id="auditTypeSelect">
                                                    @foreach($auditTypes as $key => $label)
                                                        <option value="{{ $key }}" {{ $auditType == $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- High Value Threshold (visible only when high_value selected) --}}
                                        <div class="col-md-6 col-lg-2" id="thresholdField" style="{{ $auditType != 'high_value' ? 'display: none;' : '' }}">
                                            <label class="form-label fw-semibold">{{ __('accounting.threshold_amount') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-dollar fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="threshold" 
                                                    value="{{ request()->get('threshold', 1000) }}" min="0" step="100">
                                            </div>
                                        </div>
                                        
                                        {{-- Employee --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('accounting.employee') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-user fs-2"></i>
                                                </span>
                                                <select class="form-select" name="employee_id">
                                                    <option value="">{{ __('accounting.all_employees') }}</option>
                                                    @foreach($employees as $employee)
                                                        <option value="{{ $employee->id }}" {{ $employeeId == $employee->id ? 'selected' : '' }}>
                                                            {{ $employee->first_name }} {{ $employee->last_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="col-md-12 col-lg-2 d-flex align-items-end">
                                            <div class="d-flex gap-2 w-100">
                                                <button type="submit" class="btn btn-primary flex-fill" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('accounting.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.expenses.audit') }}" class="btn btn-light btn-active-light-primary">
                                                    <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                                    {{ __('accounting.clear_filters') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Audit Statistics --}}
                @if($auditStats['total_items'] > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.audit_statistics') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @foreach([
                                        ['key' => 'total_items', 'color' => 'primary', 'icon' => 'ki-receipt', 'label' => 'total_audit_items', 'value' => $auditStats['total_items']],
                                        ['key' => 'total_amount', 'color' => 'success', 'icon' => 'ki-dollar', 'label' => 'total_amount_audited', 'value' => '$' . number_format($auditStats['total_amount'], 2)],
                                        ['key' => 'missing_receipts', 'color' => 'danger', 'icon' => 'ki-document', 'label' => 'missing_receipts', 'value' => $auditStats['missing_receipts']],
                                        ['key' => 'unapproved', 'color' => 'warning', 'icon' => 'ki-shield-check', 'label' => 'unapproved_expenses', 'value' => $auditStats['unapproved']],
                                        ['key' => 'high_value', 'color' => 'info', 'icon' => 'ki-chart-line-up', 'label' => 'high_value_items', 'value' => $auditStats['high_value']],
                                        ['key' => 'average_age_days', 'color' => 'secondary', 'icon' => 'ki-clock', 'label' => 'average_age_days', 'value' => number_format($auditStats['average_age_days'] ?? 0, 1)],
                                        ['key' => 'compliance_rate', 'color' => 'dark', 'icon' => 'ki-check', 'label' => 'compliance_rate', 'value' => $auditStats['total_items'] > 0 ? number_format((($auditStats['total_items'] - $auditStats['missing_receipts'] - $auditStats['unapproved']) / $auditStats['total_items']) * 100, 1) . '%' : '100%'],
                                        ['key' => 'avg_item_amount', 'color' => 'primary', 'icon' => 'ki-calculator', 'label' => 'average_item_amount', 'value' => '$' . number_format($auditStats['total_items'] > 0 ? $auditStats['total_amount'] / $auditStats['total_items'] : 0, 2)]
                                    ] as $stat)
                                    <div class="col-md-6 col-lg-3">
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

                {{-- Audit Items Table --}}
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
                                        <h3 class="fw-bold m-0">
                                            {{ $auditTypes[$auditType] ?? __('accounting.all_audit_items') }}
                                        </h3>
                                    </div>
                                    @if($auditStats['total_items'] > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $auditStats['total_items'] }} {{ __('accounting.items') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($auditStats['total_items'] > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="auditItemsTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-100px ps-4">{{ __('accounting.date') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.description') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.category') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.employee') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.amount') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.status') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.receipt') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.approval') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.age_days') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.audit_flags') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($auditItems as $expense)
                                                @php
                                                    $ageDays = Carbon\Carbon::parse($expense->created_at)->diffInDays(Carbon\Carbon::today());
                                                    
                                                    // Determine status flags
                                                    $hasReceipt = !empty($expense->receipt_url);
                                                    $isApproved = !empty($expense->approved_at);
                                                    $requiresReceipt = $expense->category && $expense->category->requires_receipt;
                                                    $requiresApproval = $expense->category && $expense->category->requires_approval;
                                                    
                                                    // Audit flags
                                                    $flags = [];
                                                    if ($requiresReceipt && !$hasReceipt) {
                                                        $flags[] = ['text' => __('accounting.missing_receipt'), 'color' => 'danger'];
                                                    }
                                                    if ($requiresApproval && !$isApproved) {
                                                        $flags[] = ['text' => __('accounting.pending_approval'), 'color' => 'warning'];
                                                    }
                                                    if ($ageDays > 30) {
                                                        $flags[] = ['text' => __('accounting.old_expense'), 'color' => 'secondary'];
                                                    }
                                                    if ($expense->total_amount >= 1000) {
                                                        $flags[] = ['text' => __('accounting.high_value'), 'color' => 'info'];
                                                    }
                                                @endphp
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="fw-semibold">{{ $expense->date->format('Y-m-d') }}</span>
                                                        <div class="mt-1">
                                                            <small class="text-muted">{{ $expense->date->format('D') }}</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex justify-content-start flex-column">
                                                            <span class="text-gray-800 fw-bold">{{ Str::limit($expense->description, 40) }}</span>
                                                            @if($expense->vendor_name)
                                                            <small class="text-muted">
                                                                {{ __('accounting.vendor') }}: {{ $expense->vendor_name }}
                                                            </small>
                                                            @endif
                                                            <small class="text-muted">
                                                                {{ __('accounting.expense_number') }}: {{ $expense->expense_number }}
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">
                                                            {{ $expense->category->name ?? 'N/A' }}
                                                        </span>
                                                        <div class="mt-1">
                                                            @if($expense->category)
                                                                @if($expense->category->requires_receipt)
                                                                    <small class="badge badge-light-success badge-sm">{{ __('accounting.requires_receipt') }}</small>
                                                                @endif
                                                                @if($expense->category->requires_approval)
                                                                    <small class="badge badge-light-warning badge-sm">{{ __('accounting.requires_approval') }}</small>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($expense->employee)
                                                            <div class="d-flex align-items-center">
                                                                <div class="symbol symbol-30px symbol-circle me-3">
                                                                    <div class="symbol-label bg-light-primary">
                                                                        <span class="fw-bold">{{ substr($expense->employee->first_name, 0, 1) }}</span>
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex justify-content-start flex-column">
                                                                    <span class="fw-semibold">{{ $expense->employee->first_name }} {{ $expense->employee->last_name }}</span>
                                                                    <small class="text-muted">ID: {{ $expense->employee->id }}</small>
                                                                </div>
                                                            </div>
                                                        @else
                                                            <span class="text-muted">{{ __('accounting.not_assigned') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($expense->total_amount, 2) }}</span>
                                                        <div class="mt-1">
                                                            <small class="text-muted">
                                                                @if($expense->tax_amount > 0)
                                                                    {{ __('accounting.tax') }}: ${{ number_format($expense->tax_amount, 2) }}
                                                                @endif
                                                            </small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($expense->payment_status == 'paid')
                                                            <span class="badge badge-light-success">{{ __('accounting.paid') }}</span>
                                                        @elseif($expense->payment_status == 'pending')
                                                            <span class="badge badge-light-warning">{{ __('accounting.pending') }}</span>
                                                        @else
                                                            <span class="badge badge-light-info">{{ $expense->payment_status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($hasReceipt)
                                                            <a href="{{ $expense->receipt_url }}" target="_blank" class="btn btn-sm btn-light-success">
                                                                <i class="ki-duotone ki-eye fs-2 me-1"></i>
                                                                {{ __('accounting.view_receipt') }}
                                                            </a>
                                                        @elseif($requiresReceipt)
                                                            <span class="badge badge-light-danger">
                                                                <i class="ki-duotone ki-cross fs-2 me-1"></i>
                                                                {{ __('accounting.missing') }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-light-secondary">{{ __('accounting.not_required') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($isApproved)
                                                            <span class="badge badge-light-success">
                                                                <i class="ki-duotone ki-check fs-2 me-1"></i>
                                                                {{ __('accounting.approved') }}
                                                            </span>
                                                            @if($expense->approver)
                                                            <div class="mt-1">
                                                                <small class="text-muted">
                                                                    {{ $expense->approver->name }}
                                                                </small>
                                                            </div>
                                                            @endif
                                                        @elseif($requiresApproval)
                                                            <span class="badge badge-light-warning">
                                                                <i class="ki-duotone ki-clock fs-2 me-1"></i>
                                                                {{ __('accounting.pending') }}
                                                            </span>
                                                        @else
                                                            <span class="badge badge-light-secondary">{{ __('accounting.not_required') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $ageColor = $ageDays > 30 ? 'danger' : ($ageDays > 7 ? 'warning' : 'success');
                                                        @endphp
                                                        <span class="fw-bold text-{{ $ageColor }}">
                                                            {{ $ageDays }} {{ __('accounting.days') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @foreach($flags as $flag)
                                                            <span class="badge badge-light-{{ $flag['color'] }} mb-1 d-block">
                                                                {{ $flag['text'] }}
                                                            </span>
                                                        @endforeach
                                                        @if(empty($flags))
                                                            <span class="badge badge-light-success">{{ __('accounting.compliant') }}</span>
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
                                        <i class="ki-duotone ki-shield-check fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                                        <p class="text-muted fs-6">{{ __('accounting.no_audit_items_found') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'audit_type', 'employee_id']))
                                        <a href="{{ route('reports.expenses.audit') }}" class="btn btn-light-primary">
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

                {{-- Category Analysis --}}
                @if($byCategory->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.audit_by_category') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.category') }}</th>
                                                <th>{{ __('accounting.expense_count') }}</th>
                                                <th>{{ __('accounting.total_amount') }}</th>
                                                <th>{{ __('accounting.average_amount') }}</th>
                                                <th>{{ __('accounting.missing_receipts') }}</th>
                                                <th>{{ __('accounting.unapproved') }}</th>
                                                <th>{{ __('accounting.compliance_rate') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($byCategory as $category)
                                            @php
                                                $complianceRate = $category['count'] > 0 ? 
                                                    (($category['count'] - $category['missing_receipts'] - $category['unapproved']) / $category['count']) * 100 : 100;
                                                $complianceColor = $complianceRate >= 90 ? 'success' : ($complianceRate >= 70 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
                                                <td class="ps-4 fw-semibold">
                                                    <span class="badge badge-light-primary">{{ $category['category'] }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $category['count'] }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">${{ number_format($category['total_amount'], 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-gray-700">${{ number_format($category['avg_amount'], 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $category['missing_receipts'] > 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ $category['missing_receipts'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $category['unapproved'] > 0 ? 'text-warning' : 'text-success' }}">
                                                        {{ $category['unapproved'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 20px;">
                                                            <div class="progress-bar bg-{{ $complianceColor }}" role="progressbar" 
                                                                style="width: {{ min($complianceRate, 100) }}%" 
                                                                aria-valuenow="{{ $complianceRate }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <span class="fw-bold text-{{ $complianceColor }}">{{ number_format($complianceRate, 1) }}%</span>
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

                {{-- Employee Compliance --}}
                @if($employeeCompliance->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-user-tick fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.employee_compliance') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.employee') }}</th>
                                                <th>{{ __('accounting.employee_id') }}</th>
                                                <th>{{ __('accounting.total_expenses') }}</th>
                                                <th>{{ __('accounting.average_expense') }}</th>
                                                <th>{{ __('accounting.missing_receipts') }}</th>
                                                <th>{{ __('accounting.unapproved') }}</th>
                                                <th>{{ __('accounting.compliance_rate') }}</th>
                                                <th>{{ __('accounting.risk_level') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($employeeCompliance as $employee)
                                            @php
                                                $missingRate = $employee->total_expenses > 0 ? ($employee->missing_receipts / $employee->total_expenses) * 100 : 0;
                                                $unapprovedRate = $employee->total_expenses > 0 ? ($employee->unapproved / $employee->total_expenses) * 100 : 0;
                                                $complianceRate = 100 - $missingRate - $unapprovedRate;
                                                
                                                if ($complianceRate >= 90) {
                                                    $riskColor = 'success';
                                                    $riskText = __('accounting.low_risk');
                                                } elseif ($complianceRate >= 70) {
                                                    $riskColor = 'warning';
                                                    $riskText = __('accounting.medium_risk');
                                                } else {
                                                    $riskColor = 'danger';
                                                    $riskText = __('accounting.high_risk');
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4 fw-semibold">
                                                    {{ $employee->employee_name }}
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">ID: {{ $employee->id }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold">{{ $employee->total_expenses }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold text-gray-700">${{ number_format($employee->avg_expense, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $employee->missing_receipts > 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ $employee->missing_receipts }}
                                                    </span>
                                                    <small class="text-muted d-block">({{ number_format($missingRate, 1) }}%)</small>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $employee->unapproved > 0 ? 'text-warning' : 'text-success' }}">
                                                        {{ $employee->unapproved }}
                                                    </span>
                                                    <small class="text-muted d-block">({{ number_format($unapprovedRate, 1) }}%)</small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 20px;">
                                                            <div class="progress-bar bg-{{ $riskColor }}" role="progressbar" 
                                                                style="width: {{ min($complianceRate, 100) }}%" 
                                                                aria-valuenow="{{ $complianceRate }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <span class="fw-bold text-{{ $riskColor }}">{{ number_format($complianceRate, 1) }}%</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $riskColor }}">{{ $riskText }}</span>
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

                {{-- Monthly Audit Trend --}}
                @if($monthlyAudit->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('accounting.monthly_audit_trend') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('accounting.period') }}</th>
                                                <th>{{ __('accounting.total_expenses') }}</th>
                                                <th>{{ __('accounting.total_amount') }}</th>
                                                <th>{{ __('accounting.missing_receipts') }}</th>
                                                <th>{{ __('accounting.unapproved') }}</th>
                                                <th>{{ __('accounting.missing_receipt_rate') }}</th>
                                                <th>{{ __('accounting.unapproved_rate') }}</th>
                                                <th>{{ __('accounting.compliance_trend') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($monthlyAudit as $month)
                                            @php
                                                $monthName = \Carbon\Carbon::create($month->year, $month->month, 1)->format('M Y');
                                                $missingRate = $month->total_expenses > 0 ? ($month->missing_receipts / $month->total_expenses) * 100 : 0;
                                                $unapprovedRate = $month->total_expenses > 0 ? ($month->unapproved / $month->total_expenses) * 100 : 0;
                                                $complianceRate = 100 - $missingRate - $unapprovedRate;
                                                $complianceColor = $complianceRate >= 90 ? 'success' : ($complianceRate >= 70 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
                                                <td class="ps-4 fw-semibold">
                                                    {{ $monthName }}
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $month->total_expenses }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">${{ number_format($month->monthly_total, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $month->missing_receipts > 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ $month->missing_receipts }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $month->unapproved > 0 ? 'text-warning' : 'text-success' }}">
                                                        {{ $month->unapproved }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $missingRate > 10 ? 'text-danger' : ($missingRate > 5 ? 'text-warning' : 'text-success') }}">
                                                        {{ number_format($missingRate, 1) }}%
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold {{ $unapprovedRate > 10 ? 'text-danger' : ($unapprovedRate > 5 ? 'text-warning' : 'text-success') }}">
                                                        {{ number_format($unapprovedRate, 1) }}%
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 15px;">
                                                            <div class="progress-bar bg-{{ $complianceColor }}" role="progressbar" 
                                                                style="width: {{ min($complianceRate, 100) }}%" 
                                                                aria-valuenow="{{ $complianceRate }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <span class="fw-bold text-{{ $complianceColor }}">{{ number_format($complianceRate, 1) }}%</span>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle threshold field based on audit type
        const auditTypeSelect = document.getElementById('auditTypeSelect');
        const thresholdField = document.getElementById('thresholdField');
        
        function toggleThresholdField() {
            if (auditTypeSelect.value === 'high_value') {
                thresholdField.style.display = 'block';
            } else {
                thresholdField.style.display = 'none';
            }
        }
        
        auditTypeSelect.addEventListener('change', toggleThresholdField);
        toggleThresholdField(); // Initial call
        
        // PDF Export Function
        function generateAuditPDF() {
            alert('PDF export feature would be implemented here. This could use jsPDF or a server-side library.');
        }
    });
</script>
@endpush

@endsection