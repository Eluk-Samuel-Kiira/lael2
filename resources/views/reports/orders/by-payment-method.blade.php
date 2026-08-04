{{-- resources/views/reports/orders/by-payment-method.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.payment_method_analysis'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                
                {{-- ============================================================ --}}
                {{-- TOOLBAR SECTION --}}
                {{-- ============================================================ --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('auth.payment_method_analysis') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.payment_method_analysis') }}</li>
                            </ul>
                        </div>
                        @if(isset($summary) && $summary->total_methods > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('paymentMethodTable', 'payment_method_analysis')">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                                <i class="ki-duotone ki-printer fs-2"></i> {{ __('accounting.print') }}
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- FILTER SECTION --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-filter-square fs-2 me-2"></i>
                            {{ __('accounting.filter_by') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.orders.by-payment-method') }}" id="filterForm">
                            {{-- Row 1: Date Range & Location --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('accounting.start_date') }}</label>
                                    <input type="date" class="form-control" name="start_date" value="{{ $startDate ?? now()->startOfMonth()->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('accounting.end_date') }}</label>
                                    <input type="date" class="form-control" name="end_date" value="{{ $endDate ?? now()->endOfMonth()->format('Y-m-d') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('auth.location') }}</label>
                                    <select class="form-select" name="location_id" data-control="select2">
                                        <option value="">{{ __('auth.all_locations') }}</option>
                                        @foreach($locations ?? [] as $location)
                                            <option value="{{ $location->id }}" {{ ($locationId ?? '') == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">{{ __('auth.department') }}</label>
                                    <select class="form-select" name="department_id" data-control="select2">
                                        <option value="">{{ __('auth.all_departments') }}</option>
                                        @foreach($departments ?? [] as $department)
                                            <option value="{{ $department->id }}" {{ ($departmentId ?? '') == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Row 2: Payment Filters --}}
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('auth.payment_type') }}</label>
                                    <select class="form-select" name="payment_type" data-control="select2">
                                        <option value="all">{{ __('auth.all_types') }}</option>
                                        @foreach($paymentTypes ?? [] as $type)
                                            <option value="{{ $type }}" {{ ($paymentType ?? '') == $type ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_', ' ', $type)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('auth.payment_status') }}</label>
                                    <select class="form-select" name="payment_status">
                                        <option value="all">{{ __('auth.all_statuses') }}</option>
                                        <option value="completed" {{ ($paymentStatus ?? '') == 'completed' ? 'selected' : '' }}>✅ {{ __('auth.completed') }}</option>
                                        <option value="failed" {{ ($paymentStatus ?? '') == 'failed' ? 'selected' : '' }}>❌ {{ __('auth.failed') }}</option>
                                        <option value="pending" {{ ($paymentStatus ?? '') == 'pending' ? 'selected' : '' }}>⏳ {{ __('auth.pending') }}</option>
                                        <option value="refunded" {{ ($paymentStatus ?? '') == 'refunded' ? 'selected' : '' }}>🔄 {{ __('auth.refunded') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('auth.method_status') }}</label>
                                    <select class="form-select" name="payment_method_status">
                                        <option value="all">{{ __('auth.all_methods') }}</option>
                                        <option value="active" {{ ($paymentMethodStatus ?? '') == 'active' ? 'selected' : '' }}>🟢 {{ __('auth.active') }}</option>
                                        <option value="inactive" {{ ($paymentMethodStatus ?? '') == 'inactive' ? 'selected' : '' }}>🔴 {{ __('auth.inactive') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">{{ __('auth.min_amount') }}</label>
                                    <input type="number" class="form-control" name="min_amount" value="{{ $minAmount ?? '' }}" placeholder="0.00" step="0.01" min="0">
                                </div>
                            </div>

                            {{-- Row 3: Actions --}}
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.orders.by-payment-method') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-arrows-circle fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </a>
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('accounting.showing') }} <strong>{{ $paymentMethodAnalysis->count() ?? 0 }}</strong> {{ __('auth.payment_methods') }}
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA MESSAGE --}}
                {{-- ============================================================ --}}
                @if(!isset($summary) || $summary->total_methods == 0)
                <div class="card">
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_payment_methods_found_for_period') }}</p>
                            <p class="text-muted fs-7">{{ __('auth.try_adjusting_date_range_or_filters') }}</p>
                        </div>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- KPI CARDS - Key Performance Indicators --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    {{-- Total Methods --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-primary h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-primary d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-credit-card fs-2x text-white"></i>
                                </div>
                                <div class="fs-1 fw-bold text-primary">{{ number_format($summary->total_methods) }}</div>
                                <div class="text-muted">{{ __('auth.payment_methods') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Total Transactions --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-info h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-info d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-receipt fs-2x text-white"></i>
                                </div>
                                <div class="fs-1 fw-bold text-info">{{ number_format($summary->total_transactions) }}</div>
                                <div class="text-muted">{{ __('auth.total_transactions') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Total Amount --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-success h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-success d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-dollar fs-2x text-white"></i>
                                </div>
                                <div class="fs-1 fw-bold text-success">{{ currency_symbol() }}{{ number_format($summary->total_amount, 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_processed') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Average Transaction --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-warning h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-warning d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-calculator fs-2x text-white"></i>
                                </div>
                                <div class="fs-1 fw-bold text-warning">{{ currency_symbol() }}{{ number_format($summary->avg_transaction, 2) }}</div>
                                <div class="text-muted">{{ __('auth.avg_transaction') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Success Rate --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-{{ ($summary->success_rate ?? 0) >= 95 ? 'success' : (($summary->success_rate ?? 0) >= 80 ? 'warning' : 'danger') }} h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-{{ ($summary->success_rate ?? 0) >= 95 ? 'success' : (($summary->success_rate ?? 0) >= 80 ? 'warning' : 'danger') }} d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-check-circle fs-2x text-white"></i>
                                </div>
                                <div class="fs-1 fw-bold">{{ number_format($summary->success_rate ?? 0, 1) }}%</div>
                                <div class="text-muted">{{ __('auth.success_rate') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Top Method --}}
                    <div class="col-md-6 col-lg-2">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body text-center">
                                <div class="symbol symbol-50px bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3 rounded-circle">
                                    <i class="ki-duotone ki-crown fs-2x text-white"></i>
                                </div>
                                <div class="fs-6 fw-bold text-gray-800">{{ Str::limit($summary->most_used_method ?? 'N/A', 20) }}</div>
                                <div class="text-muted">{{ currency_symbol() }}{{ number_format($summary->most_used_amount ?? 0, 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- PAYMENT METHOD DISTRIBUTION CHART --}}
                {{-- ============================================================ --}}
                @if(isset($paymentMethodAnalysis) && $paymentMethodAnalysis->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2"></i>
                                    {{ __('auth.payment_method_distribution') }}
                                </h3>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-success fs-7">
                                        {{ $paymentMethodAnalysis->count() }} {{ __('auth.methods') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="paymentMethodChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- PAYMENT TRENDS CHART --}}
                {{-- ============================================================ --}}
                @if(isset($paymentTrends) && $paymentTrends->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-chart-line fs-2 me-2"></i>
                                    {{ __('auth.payment_method_trends') }}
                                </h3>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-info fs-7">
                                        {{ __('auth.daily_breakdown') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="paymentTrendsChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- FAILED TRANSACTIONS SECTION --}}
                {{-- ============================================================ --}}
                @if(isset($failedTransactions) && $failedTransactions->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card border border-danger">
                            <div class="card-header bg-light-danger">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-cross-circle fs-2 me-2 text-danger"></i>
                                    {{ __('auth.failed_transactions_analysis') }}
                                </h3>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-danger fs-7">
                                        {{ $failedTransactions->sum('failed_count') }} {{ __('auth.failed') }}
                                    </span>
                                    <span class="badge badge-light-secondary ms-2 fs-7">
                                        {{ currency_symbol() }}{{ number_format($failedTransactions->sum('failed_amount'), 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light-danger">
                                                <th class="ps-4" style="width: 20%;">{{ __('auth.payment_method') }}</th>
                                                <th style="width: 15%;" class="text-center">{{ __('accounting.type') }}</th>
                                                <th style="width: 15%;" class="text-center">{{ __('auth.failed_count') }}</th>
                                                <th style="width: 20%;" class="text-end">{{ __('auth.failed_amount') }}</th>
                                                <th style="width: 30%;" class="text-center">{{ __('auth.failure_rate') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($failedTransactions as $failed)
                                            @php
                                                $methodTransactions = $paymentMethodAnalysis->firstWhere('id', $failed->id);
                                                $totalForMethod = $methodTransactions ? $methodTransactions->transaction_count + $failed->failed_count : $failed->failed_count;
                                                $failureRate = $totalForMethod > 0 ? ($failed->failed_count / $totalForMethod) * 100 : 100;
                                                $typeColors = [
                                                    'cash' => 'success',
                                                    'card' => 'primary',
                                                    'bank_transfer' => 'info',
                                                    'digital_wallet' => 'warning',
                                                    'credit' => 'danger',
                                                    'other' => 'secondary'
                                                ];
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $failed->name }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-{{ $typeColors[$failed->type ?? 'other'] ?? 'secondary' }}">
                                                        {{ ucfirst(str_replace('_', ' ', $failed->type ?? 'unknown')) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light-danger fs-6">{{ number_format($failed->failed_count) }}</span>
                                                </td>
                                                <td class="text-end text-danger fw-bold">
                                                    {{ currency_symbol() }}{{ number_format($failed->failed_amount, 2) }}
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 8px;">
                                                            <div class="progress-bar bg-{{ $failureRate > 10 ? 'danger' : ($failureRate > 5 ? 'warning' : 'success') }}" 
                                                                 style="width: {{ min($failureRate, 100) }}%;"
                                                                 role="progressbar"
                                                                 aria-valuenow="{{ $failureRate }}" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <span class="fw-bold min-w-60px text-end">{{ number_format($failureRate, 1) }}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="2" class="text-end fw-bold">{{ __('accounting.total') }}</th>
                                                <th class="text-center fw-bold text-danger">{{ number_format($failedTransactions->sum('failed_count')) }}</th>
                                                <th class="text-end fw-bold text-danger">{{ currency_symbol() }}{{ number_format($failedTransactions->sum('failed_amount'), 2) }}</th>
                                                <th class="text-center fw-bold">{{ number_format($summary->overall_failure_rate ?? 0, 1) }}%</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- PAYMENT METHOD ANALYSIS TABLE --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2"></i>
                            {{ __('auth.payment_method_analysis_details') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-6">
                                {{ $paymentMethodAnalysis->count() }} {{ __('auth.methods') }}
                            </span>
                            <span class="badge badge-light-secondary ms-2 fs-6">
                                {{ $startDate ?? 'N/A' }} {{ __('accounting.to') }} {{ $endDate ?? 'N/A' }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="paymentMethodTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 4%;" class="text-center">{{ __('accounting.rank') }}</th>
                                        <th style="width: 14%;">{{ __('auth.payment_method') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('accounting.type') }}</th>
                                        <th style="width: 7%;" class="text-center">{{ __('accounting.status') }}</th>
                                        <th style="width: 7%;" class="text-center">{{ __('auth.txn_count') }}</th>
                                        <th style="width: 10%;" class="text-end">{{ __('accounting.total_amount') }}</th>
                                        <th style="width: 8%;" class="text-end">{{ __('auth.avg_txn') }}</th>
                                        <th style="width: 8%;" class="text-end">{{ __('auth.largest') }}</th>
                                        <th style="width: 7%;" class="text-center">{{ __('auth.failed_txn') }}</th>
                                        <th style="width: 7%;" class="text-center">{{ __('auth.success_rate') }}</th>
                                        <th style="width: 10%;">{{ __('auth.last_transaction') }}</th>
                                        <th style="width: 8%;" class="text-center">{{ __('auth.share') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentMethodAnalysisPaginated ?? $paymentMethodAnalysis as $index => $method)
                                    @php
                                        $rank = $index + 1;
                                        $rowClass = '';
                                        if ($rank <= 3) $rowClass = 'table-success';
                                        $totalAmountAll = $paymentMethodAnalysis->sum('total_amount');
                                        $percentage = $totalAmountAll > 0 ? ($method->total_amount / $totalAmountAll) * 100 : 0;
                                        $typeColors = [
                                            'cash' => 'success',
                                            'card' => 'primary',
                                            'bank_account' => 'info',
                                            'bank_transfer' => 'info',
                                            'digital_wallet' => 'warning',
                                            'mobile_money' => 'warning',
                                            'credit' => 'danger',
                                            'check' => 'secondary',
                                            'crypto' => 'dark',
                                            'gift_card' => 'purple',
                                            'other' => 'secondary'
                                        ];
                                        $successColor = $method->success_rate >= 95 ? 'success' : ($method->success_rate >= 80 ? 'warning' : 'danger');
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td class="text-center">
                                            <span class="fw-bold">{{ $rank }}</span>
                                            @if($rank <= 3)
                                                <span class="badge badge-light-{{ $rank == 1 ? 'danger' : ($rank == 2 ? 'warning' : 'info') }} ms-1">
                                                    {{ $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-40px symbol-circle me-3">
                                                    <div class="symbol-label bg-light-{{ $typeColors[$method->method_type] ?? 'secondary' }}">
                                                        <i class="ki-duotone ki-credit-card fs-2"></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold">{{ $method->method_name }}</span>
                                                    <span class="badge badge-light-{{ $typeColors[$method->method_type] ?? 'secondary' }} badge-sm mt-1" style="width: fit-content;">
                                                        {{ ucfirst(str_replace('_', ' ', $method->method_type)) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $typeColors[$method->method_type] ?? 'secondary' }}">
                                                {{ ucfirst(str_replace('_', ' ', $method->method_type)) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-{{ $method->is_active ? 'success' : 'danger' }}">
                                                {{ $method->is_active ? __('auth.active') : __('auth.inactive') }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary">{{ number_format($method->transaction_count) }}</span>
                                        </td>
                                        <td class="text-end fw-bold text-success">
                                            {{ currency_symbol() }}{{ number_format($method->total_amount, 2) }}
                                        </td>
                                        <td class="text-end">
                                            {{ currency_symbol() }}{{ number_format($method->average_transaction, 2) }}
                                        </td>
                                        <td class="text-end text-danger">
                                            {{ currency_symbol() }}{{ number_format($method->largest_transaction, 2) }}
                                        </td>
                                        <td class="text-center">
                                            @if($method->failed_count > 0)
                                                <span class="badge badge-light-danger">{{ number_format($method->failed_count) }}</span>
                                            @else
                                                <span class="badge badge-light-success">0</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge badge-light-{{ $successColor }} fs-7">
                                                    {{ number_format($method->success_rate, 1) }}%
                                                </span>
                                                <div class="progress w-100 mt-1" style="height: 4px;">
                                                    <div class="progress-bar bg-{{ $successColor }}" 
                                                         style="width: {{ $method->success_rate }}%;"
                                                         role="progressbar"
                                                         aria-valuenow="{{ $method->success_rate }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @if($method->last_transaction_date)
                                                <span class="text-gray-700">{{ optional($method->last_transaction_date)->format('M d, Y H:i') ?? '-' }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-2" style="height: 6px;">
                                                    <div class="progress-bar bg-{{ $typeColors[$method->method_type] ?? 'secondary' }}" 
                                                         style="width: {{ min($percentage, 100) }}%;"
                                                         role="progressbar"
                                                         aria-valuenow="{{ $percentage }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="fw-bold min-w-45px text-end">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5" class="text-end fw-bold">{{ __('accounting.totals') }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($paymentMethodAnalysis->sum('total_amount'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($paymentMethodAnalysis->avg('average_transaction') ?? 0, 2) }}</th>
                                        <th colspan="2"></th>
                                        <th class="text-center fw-bold">{{ number_format($summary->success_rate ?? 0, 1) }}%</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    {{-- ✅ PAGINATION SECTION --}}
                    @if(isset($paymentMethodAnalysisPaginated) && $paymentMethodAnalysisPaginated->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $paymentMethodAnalysisPaginated,
                            'pageName' => 'page',
                            'perPageName' => 'per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif
                    
                    {{-- LEGEND SECTION --}}
                    <div class="card-footer text-muted fs-7">
                        <i class="ki-duotone ki-information-4 fs-2 me-1"></i>
                        <strong>{{ __('auth.legend') }}:</strong>
                        <span class="badge badge-light-success mx-1">✅ {{ __('auth.high') }}</span> ≥95% {{ __('auth.success_rate') }}
                        <span class="badge badge-light-warning mx-1">⚠️ {{ __('auth.medium') }}</span> 80-94% {{ __('auth.success_rate') }}
                        <span class="badge badge-light-danger mx-1">❌ {{ __('auth.low') }}</span> &lt;80% {{ __('auth.success_rate') }}
                        <span class="badge badge-light-secondary mx-1">📊 {{ __('auth.share') }}</span> {{ __('auth.market_share_percentage') }}
                        <span class="badge badge-light-primary mx-1">💰 {{ __('auth.amount') }}</span> {{ __('auth.total_processed') }}
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- REPORT METADATA FOOTER --}}
                {{-- ============================================================ --}}
                <div class="mt-6 text-muted text-center fs-7">
                    <hr>
                    <p>
                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                        {{ __('accounting.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }} 
                        | {{ __('accounting.period') }}: {{ $startDate ?? 'N/A' }} {{ __('accounting.to') }} {{ $endDate ?? 'N/A' }}
                        @if(isset($locationId) && $locationId)
                            | {{ __('auth.location') }}: {{ $locations->where('id', $locationId)->first()->name ?? 'N/A' }}
                        @endif
                        @if(isset($departmentId) && $departmentId)
                            | {{ __('auth.department') }}: {{ $departments->where('id', $departmentId)->first()->name ?? 'N/A' }}
                        @endif
                        | {{ $summary->total_methods ?? 0 }} {{ __('auth.methods') }} | 
                        {{ $summary->total_transactions ?? 0 }} {{ __('auth.transactions') }}
                        @if(isset($summary->success_rate))
                            | {{ __('auth.success_rate') }}: {{ number_format($summary->success_rate, 1) }}%
                        @endif
                    </p>
                </div>

                @endif {{-- End of data check --}}
                
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- JAVASCRIPT - Charts & Export --}}
{{-- ============================================================ --}}
@push('scripts')
@if(isset($paymentMethodAnalysis) && $paymentMethodAnalysis->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Payment Method Distribution Chart ──────────────────────────
    const paymentData = @json($paymentMethodAnalysis);
    const methodNames = paymentData.map(method => method.method_name);
    const methodAmounts = paymentData.map(method => parseFloat(method.total_amount));
    
    const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A1A5B7', '#17C653', '#FE7A0A', '#4E7B8A', '#9D4EDD'];
    
    const paymentMethodChart = new ApexCharts(document.querySelector("#paymentMethodChart"), {
        series: methodAmounts,
        chart: {
            type: 'donut',
            height: 400,
            toolbar: {
                show: true
            }
        },
        labels: methodNames,
        colors: colors,
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            fontSize: '12px',
            formatter: function(seriesName, opts) {
                const total = opts.w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                const percentage = total > 0 ? ((opts.w.globals.series[opts.seriesIndex] / total) * 100).toFixed(1) : 0;
                return seriesName + ' - ' + percentage + '%';
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return '{{ currency_symbol() }}' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                }
            }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: '{{ __("auth.total_amount") }}',
                            fontSize: '14px',
                            fontWeight: 600,
                            formatter: function(w) {
                                const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                return '{{ currency_symbol() }}' + total.toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                        },
                        value: {
                            formatter: function(val) {
                                return '{{ currency_symbol() }}' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                            }
                        }
                    }
                }
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    height: 300
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    });
    paymentMethodChart.render();
    
    // ─── Payment Trends Chart ────────────────────────────────────────
    @if(isset($paymentTrends) && $paymentTrends->count() > 0)
    const paymentTrendsData = @json($paymentTrends);
    let trendSeries = [];
    let trendCategories = [];
    let isFirstType = true;
    
    Object.keys(paymentTrendsData).forEach((methodType) => {
        const methodData = paymentTrendsData[methodType];
        
        if (methodData && Array.isArray(methodData) && methodData.length > 0) {
            const trendValues = methodData.map(item => parseFloat(item.daily_total || 0));
            
            if (isFirstType && methodData.length > 0) {
                trendCategories = methodData.map(item => {
                    try {
                        const date = new Date(item.date);
                        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                    } catch(e) {
                        return item.date;
                    }
                });
                isFirstType = false;
            }
            
            const displayName = methodType.replace(/_/g, ' ').toUpperCase();
            trendSeries.push({
                name: displayName,
                data: trendValues,
                type: 'line'
            });
        }
    });
    
    if (trendSeries.length > 0 && trendCategories.length > 0) {
        const trendColors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A1A5B7'];
        
        const paymentTrendsChart = new ApexCharts(document.querySelector("#paymentTrendsChart"), {
            series: trendSeries,
            chart: {
                type: 'line',
                height: 400,
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
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            xaxis: {
                categories: trendCategories,
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: '{{ __("auth.daily_amount") }} ({{ currency_symbol() }})',
                    style: {
                        fontSize: '12px'
                    }
                },
                labels: {
                    formatter: function(val) {
                        return '{{ currency_symbol() }}' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            },
            colors: trendColors,
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val) {
                        return '{{ currency_symbol() }}' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center',
                fontSize: '12px'
            },
            grid: {
                borderColor: '#e7e7e7',
                row: {
                    colors: ['#f8f9fa', 'transparent'],
                    opacity: 0.5
                }
            },
            markers: {
                size: 4,
                hover: {
                    size: 6
                }
            }
        });
        paymentTrendsChart.render();
    } else {
        document.querySelector("#paymentTrendsChart").innerHTML = 
            '<div class="text-center text-muted py-10">{{ __("auth.no_trend_data_available") }}</div>';
    }
    @endif
});
</script>
@endif

<script>
// ─── Form Validation ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const startDate = new Date(document.querySelector('[name="start_date"]').value);
            const endDate = new Date(document.querySelector('[name="end_date"]').value);
            const minAmount = parseFloat(document.querySelector('[name="min_amount"]').value) || 0;
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
                return false;
            }
            
            if (minAmount < 0) {
                e.preventDefault();
                alert('{{ __("auth.min_amount_cannot_be_negative") }}');
                return false;
            }
            
            return true;
        });
    }
    
    // ─── Export Functions ──────────────────────────────────────────────
    window.exportTableToExcel = function(tableId, filename) {
        const table = document.getElementById(tableId);
        if (!table) {
            alert('{{ __("accounting.table_not_found") }}');
            return;
        }
        
        try {
            if (typeof XLSX === 'undefined') {
                alert('{{ __("accounting.export_library_missing") }}');
                return;
            }
            
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(table);
            XLSX.utils.book_append_sheet(wb, ws, 'Payment Methods');
            XLSX.writeFile(wb, filename + '.xlsx');
        } catch (e) {
            console.error('Export error:', e);
            alert('{{ __("accounting.export_error") }}: ' + e.message);
        }
    };
    
    // ─── Print Styles ──────────────────────────────────────────────────
    const style = document.createElement('style');
    style.innerHTML = `
        @media print {
            .card-header:has(.card-title:contains("Filter Report")),
            .app-toolbar .dropdown,
            #filterForm,
            .btn,
            .kt_app_toolbar .d-flex.gap-2 {
                display: none !important;
            }
            .card {
                border: 1px solid #ddd !important;
                break-inside: avoid;
            }
            .table {
                font-size: 9px !important;
            }
            .badge {
                border: 1px solid #ddd !important;
                font-size: 8px !important;
            }
            .progress {
                display: none !important;
            }
            .symbol {
                display: none !important;
            }
            .card-header {
                background: #f8f9fa !important;
            }
            #paymentMethodChart,
            #paymentTrendsChart {
                height: 250px !important;
            }
        }
    `;
    document.head.appendChild(style);
});
</script>
@endpush

@endsection