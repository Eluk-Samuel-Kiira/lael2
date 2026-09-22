@extends('layouts.app')

@section('title', __('auth.invoice_summary_report'))

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
                                {{ __('auth.invoice_summary_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.invoice_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.summary') }}</li>
                            </ul>
                        </div>
                        @if(isset($summary) && $summary->total_invoices > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('invoiceSummaryTable', 'invoice_summary')">
                                <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export_to_excel') }}
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
                        <h3 class="card-title">{{ __('accounting.filter_by') }}</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.invoices.summary') }}" class="row g-3">
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.start_date') }}</label>
                                <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('accounting.end_date') }}</label>
                                <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.location') }}</label>
                                <select class="form-select" name="location_id" data-control="select2">
                                    <option value="">{{ __('auth.all_locations') }}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ $locationId == $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.department') }}</label>
                                <select class="form-select" name="department_id" data-control="select2">
                                    <option value="">{{ __('auth.all_departments') }}</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" {{ $departmentId == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.status') }}</label>
                                <select class="form-select" name="status_filter">
                                    <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>{{ __('auth.all_statuses') }}</option>
                                    @foreach($statuses as $s)
                                        <option value="{{ $s['value'] }}" {{ $statusFilter == $s['value'] ? 'selected' : '' }}>{{ $s['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.customer') }}</label>
                                <select class="form-select" name="customer_id" data-control="select2">
                                    <option value="">{{ __('auth.all_customers') }}</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ $customerId == $customer->id ? 'selected' : '' }}>
                                            {{ trim($customer->first_name . ' ' . $customer->last_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ __('accounting.search') }}</label>
                                <input type="text" class="form-control" name="search" value="{{ $search }}"
                                       placeholder="{{ __('auth.invoice_number_or_billing_name') }}">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">{{ __('accounting.apply_filters') }}</button>
                                <a href="{{ route('reports.invoices.summary') }}" class="btn btn-light">{{ __('accounting.clear_filters') }}</a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA MESSAGE --}}
                {{-- ============================================================ --}}
                @if(!isset($summary) || $summary->total_invoices == 0)
                <div class="card">
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_invoices_found_for_period') }}</p>
                        </div>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- KPI CARDS --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-primary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-primary">
                                            <i class="ki-duotone ki-document fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-2 fw-bold">{{ number_format($summary->total_invoices) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_invoices') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    <span class="badge badge-light-success me-1">{{ $summary->paid_count }}</span> {{ __('auth.paid') }}
                                    <span class="badge badge-light-warning ms-2 me-1">{{ $summary->partially_paid_count }}</span> {{ __('auth.partial') }}
                                    <span class="badge badge-light-danger ms-2 me-1">{{ $summary->overdue_count }}</span> {{ __('auth.overdue') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-success h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-success">
                                            <i class="ki-duotone ki-dollar fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-4 fw-bold">{{ currency_symbol() }}{{ number_format($summary->total_invoiced, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_invoiced') }}</div>
                                        <div class="fs-4 fw-bold text-success mt-1">{{ currency_symbol() }}{{ number_format($summary->total_paid, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_collected') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-warning h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-warning">
                                            <i class="ki-duotone ki-time fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-4 fw-bold text-danger">{{ currency_symbol() }}{{ number_format($summary->total_outstanding, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.outstanding') }}</div>
                                        <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($summary->collection_rate, 1) }}%</div>
                                        <div class="text-muted fs-6">{{ __('auth.collection_rate') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-secondary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-secondary">
                                            <i class="ki-duotone ki-chart fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-4 fw-bold">{{ currency_symbol() }}{{ number_format($summary->average_invoice_value, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.average_invoice') }}</div>
                                        <div class="fs-4 fw-bold text-primary mt-1">{{ currency_symbol() }}{{ number_format($summary->largest_invoice, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.largest_invoice') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- SUMMARY STATS ROW --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-3">
                        <div class="card bg-light-info">
                            <div class="card-body text-center">
                                <div class="fs-6 text-muted">{{ __('auth.total_tax') }}</div>
                                <div class="fs-3 fw-bold text-info">{{ currency_symbol() }}{{ number_format($summary->total_tax, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-warning">
                            <div class="card-body text-center">
                                <div class="fs-6 text-muted">{{ __('auth.total_discount') }}</div>
                                <div class="fs-3 fw-bold text-warning">{{ currency_symbol() }}{{ number_format($summary->total_discount, 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-danger">
                            <div class="card-body text-center">
                                <div class="fs-6 text-muted">{{ __('auth.overdue_invoices') }}</div>
                                <div class="fs-3 fw-bold text-danger">{{ number_format($summary->overdue_count) }}</div>
                                <div class="fs-7 text-muted">{{ $summary->outstanding_count }} {{ __('auth.outstanding') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light-success">
                            <div class="card-body text-center">
                                <div class="fs-6 text-muted">{{ __('auth.fully_paid') }}</div>
                                @php
                                    $paidRate = $summary->total_invoices > 0
                                        ? ($summary->paid_count / $summary->total_invoices) * 100
                                        : 0;
                                @endphp
                                <div class="fs-3 fw-bold text-success">{{ number_format($paidRate, 1) }}%</div>
                                <div class="fs-7 text-muted">{{ $summary->paid_count }} / {{ $summary->total_invoices }} {{ __('auth.invoices') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- STATUS BREAKDOWN --}}
                {{-- ============================================================ --}}
                @if($statusBreakdown->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-chart-pie fs-2 me-2"></i>
                            {{ __('auth.status_breakdown') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('auth.status') }}</th>
                                        <th class="text-center">{{ __('auth.count') }}</th>
                                        <th class="text-end">{{ __('auth.total_amount') }}</th>
                                        <th class="text-end">{{ __('auth.balance_due') }}</th>
                                        <th class="text-end">{{ __('auth.share') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statusBreakdown as $row)
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-{{ $row->color }} fs-7 py-2 px-3">
                                                {{ $row->label }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">{{ number_format($row->count) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($row->total_amount, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($row->balance_due, 2) }}</td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary">
                                                {{ number_format($row->percentage, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($statusBreakdown->sum('count')) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($statusBreakdown->sum('total_amount'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($statusBreakdown->sum('balance_due'), 2) }}</th>
                                        <th class="text-end">100%</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- AGING BUCKETS --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-time fs-2 me-2"></i>
                            {{ __('auth.aging_analysis') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            @foreach([
                                'current' => ['label' => __('auth.current'),        'color' => 'success', 'icon' => 'ki-check-circle'],
                                '1_30'    => ['label' => __('auth.days_1_30'),      'color' => 'info',    'icon' => 'ki-information-4'],
                                '31_60'   => ['label' => __('auth.days_31_60'),     'color' => 'warning', 'icon' => 'ki-warning-2'],
                                '61_90'   => ['label' => __('auth.days_61_90'),     'color' => 'danger',  'icon' => 'ki-information-5'],
                                '90_plus' => ['label' => __('auth.days_90_plus'),   'color' => 'dark',    'icon' => 'ki-cross-circle'],
                            ] as $key => $meta)
                                @php $bucket = $agingBuckets->$key; @endphp
                                <div class="col-md-6 col-lg">
                                    <div class="card bg-light-{{ $meta['color'] }} h-100">
                                        <div class="card-body text-center">
                                            <i class="ki-duotone {{ $meta['icon'] }} fs-2tx text-{{ $meta['color'] }} mb-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            <div class="fs-6 text-muted">{{ $meta['label'] }}</div>
                                            <div class="fs-2 fw-bold text-{{ $meta['color'] }}">{{ number_format($bucket['count']) }}</div>
                                            <div class="fs-6 text-muted">{{ currency_symbol() }}{{ number_format($bucket['amount'], 2) }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- MONTHLY TREND --}}
                {{-- ============================================================ --}}
                @if($monthlyTrend->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-chart-line fs-2 me-2"></i>
                            {{ __('auth.monthly_invoice_trend') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('auth.month') }}</th>
                                        <th class="text-center">{{ __('auth.invoices') }}</th>
                                        <th class="text-end">{{ __('auth.total_invoiced') }}</th>
                                        <th class="text-end">{{ __('auth.total_collected') }}</th>
                                        <th class="text-end">{{ __('auth.outstanding') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthlyTrend as $row)
                                    <tr>
                                        <td><span class="fw-bold">{{ $row->month_label }}</span></td>
                                        <td class="text-center">{{ number_format($row->invoice_count) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($row->total_amount, 2) }}</td>
                                        <td class="text-end text-success">{{ currency_symbol() }}{{ number_format($row->paid_amount, 2) }}</td>
                                        <td class="text-end fw-bold {{ $row->outstanding > 0 ? 'text-danger' : 'text-muted' }}">
                                            {{ currency_symbol() }}{{ number_format($row->outstanding, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($monthlyTrend->sum('invoice_count')) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($monthlyTrend->sum('total_amount'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($monthlyTrend->sum('paid_amount'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($monthlyTrend->sum('outstanding'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- TOP CUSTOMERS BY INVOICE VALUE --}}
                {{-- ============================================================ --}}
                @if($topCustomers->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-people fs-2 me-2"></i>
                            {{ __('auth.top_customers_by_invoice_value') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('auth.customer') }}</th>
                                        <th class="text-center">{{ __('auth.invoices') }}</th>
                                        <th class="text-end">{{ __('auth.total_billed') }}</th>
                                        <th class="text-end">{{ __('auth.total_paid') }}</th>
                                        <th class="text-end">{{ __('auth.outstanding') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topCustomers as $customer)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $customer->customer_name }}</span>
                                            @if(!$customer->customer_id)
                                                <span class="badge badge-light-secondary ms-1">{{ __('auth.guest') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($customer->invoice_count) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($customer->total_billed, 2) }}</td>
                                        <td class="text-end text-success">{{ currency_symbol() }}{{ number_format($customer->total_paid, 2) }}</td>
                                        <td class="text-end fw-bold {{ $customer->outstanding > 0 ? 'text-danger' : 'text-muted' }}">
                                            {{ currency_symbol() }}{{ number_format($customer->outstanding, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- INVOICE DETAIL TABLE --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2"></i>
                            {{ __('auth.invoice_breakdown') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-6">{{ $invoices->count() }} {{ __('auth.invoices') }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="invoiceSummaryTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 130px;">{{ __('auth.invoice_number') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.issue_date') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.due_date') }}</th>
                                        <th style="min-width: 140px;">{{ __('auth.customer') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.status') }}</th>
                                        <th class="text-end" style="min-width: 120px;">{{ __('auth.total') }}</th>
                                        <th class="text-end" style="min-width: 120px;">{{ __('auth.paid') }}</th>
                                        <th class="text-end" style="min-width: 120px;">{{ __('auth.balance_due') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.location') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoicesPaginated as $invoice)
                                    <tr>
                                        <td>
                                            <a href="{{ route('invoices.show', $invoice->id) ?? '#' }}"
                                               class="fw-bold text-primary text-hover-primary">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $invoice->issue_date->format('M d, Y') }}</span>
                                        </td>
                                        <td>
                                            @if($invoice->due_date)
                                                <span class="{{ $invoice->due_date->isPast() && $invoice->balance_due > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                                    {{ $invoice->due_date->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $invoice->billing_name }}</span>
                                            @if($invoice->billing_email)
                                                <br><small class="text-muted">{{ $invoice->billing_email }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $invoice->status_color }} fs-7 py-2 px-3">
                                                {{ $invoice->status_label }}
                                            </span>
                                        </td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($invoice->total, 2) }}</td>
                                        <td class="text-end text-success">{{ currency_symbol() }}{{ number_format($invoice->amount_paid, 2) }}</td>
                                        <td class="text-end fw-bold {{ $invoice->balance_due > 0 ? 'text-danger' : 'text-muted' }}">
                                            {{ currency_symbol() }}{{ number_format($invoice->balance_due, 2) }}
                                        </td>
                                        <td>
                                            <span class="text-muted fs-7">{{ $invoice->order->correct_location->name ?? 'N/A' }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5">{{ __('accounting.total_average') }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($invoicesPaginated->sum('total'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($invoicesPaginated->sum('amount_paid'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($invoicesPaginated->sum('balance_due'), 2) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        {{ $invoicesPaginated->links() }}
                        <small class="text-muted float-end">
                            {{ __('accounting.showing') }} {{ $invoicesPaginated->firstItem() ?? 0 }} - {{ $invoicesPaginated->lastItem() ?? 0 }} {{ __('accounting.of') }} {{ $invoicesPaginated->total() }} {{ __('auth.invoices') }}
                        </small>
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
                    </p>
                </div>

                @endif {{-- End of data check --}}

            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- JAVASCRIPT --}}
{{-- ============================================================ --}}
<script>
function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __('accounting.table_not_found') }}');
        return;
    }
    try {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch (e) {
        alert('{{ __('accounting.export_error') }}: ' + e.message);
    }
}

function printReport() {
    window.print();
}
</script>
@endsection