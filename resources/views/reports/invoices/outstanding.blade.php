@extends('layouts.app')

@section('title', __('auth.outstanding_aging_report'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">

                {{-- ============================================================ --}}
                {{-- TOOLBAR --}}
                {{-- ============================================================ --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('auth.outstanding_aging_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.invoice_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.outstanding') }}</li>
                            </ul>
                        </div>
                        @if(isset($summary) && $summary->total_invoices > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('outstandingTable', 'outstanding_aging')">
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
                {{-- FILTER --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">{{ __('accounting.filter_by') }}</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('reports.invoices.outstanding') }}" class="row g-3">
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
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.aging_bucket') }}</label>
                                <select class="form-select" name="aging_bucket">
                                    @foreach($agingBucketOptions as $opt)
                                        <option value="{{ $opt['value'] }}" {{ $agingBucket == $opt['value'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label d-block">&nbsp;</label>
                                <div class="form-check form-check-custom form-check-solid mt-3">
                                    <input class="form-check-input" type="checkbox" name="only_overdue" value="1"
                                           id="onlyOverdue" {{ $onlyOverdue ? 'checked' : '' }}>
                                    <label class="form-check-label" for="onlyOverdue">
                                        {{ __('auth.show_only_overdue') }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-9 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">{{ __('accounting.apply_filters') }}</button>
                                <a href="{{ route('reports.invoices.outstanding') }}" class="btn btn-light">{{ __('accounting.clear_filters') }}</a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA --}}
                {{-- ============================================================ --}}
                @if(!isset($summary) || $summary->total_invoices == 0)
                <div class="card">
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-check-circle fs-4tx text-success mb-4">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('auth.nothing_outstanding') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_outstanding_invoices_for_period') }}</p>
                        </div>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- KPI CARDS --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-danger h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-danger">
                                            <i class="ki-duotone ki-dollar fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold text-danger">{{ currency_symbol() }}{{ number_format($summary->total_outstanding, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_outstanding') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ $summary->total_invoices }} {{ __('auth.invoices') }}
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
                                        <div class="fs-3 fw-bold text-warning">{{ currency_symbol() }}{{ number_format($summary->overdue_amount, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.overdue_amount') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ $summary->overdue_invoices }} {{ __('auth.invoices') }} · {{ __('auth.avg') }}
                                    {{ number_format($summary->average_days_overdue, 1) }} {{ __('auth.days') }}
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
                                            <i class="ki-duotone ki-check-circle fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold text-success">{{ currency_symbol() }}{{ number_format($summary->current_amount, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.current_amount') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ $summary->current_invoices }} {{ __('auth.invoices') }} {{ __('auth.not_yet_due') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-primary h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-primary">
                                            <i class="ki-duotone ki-chart fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold text-primary">{{ number_format($summary->collection_rate, 1) }}%</div>
                                        <div class="text-muted fs-6">{{ __('auth.collection_rate') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ __('auth.collected') }}: {{ currency_symbol() }}{{ number_format($summary->total_collected, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

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
                                'current' => ['label' => __('auth.current'),        'color' => 'success', 'icon' => 'ki-check-circle',    'sub' => __('auth.not_yet_due')],
                                '1_30'    => ['label' => __('auth.days_1_30'),      'color' => 'info',    'icon' => 'ki-information-4',   'sub' => __('auth.days_overdue')],
                                '31_60'   => ['label' => __('auth.days_31_60'),     'color' => 'warning', 'icon' => 'ki-warning-2',       'sub' => __('auth.days_overdue')],
                                '61_90'   => ['label' => __('auth.days_61_90'),     'color' => 'danger',  'icon' => 'ki-information-5',   'sub' => __('auth.days_overdue')],
                                '90_plus' => ['label' => __('auth.days_90_plus'),   'color' => 'dark',    'icon' => 'ki-cross-circle',    'sub' => __('auth.days_overdue')],
                            ] as $key => $meta)
                                @php $bucket = $agingSummary->$key; @endphp
                                <div class="col-md-6 col-lg">
                                    <div class="card bg-light-{{ $meta['color'] }} h-100">
                                        <div class="card-body text-center">
                                            <i class="ki-duotone {{ $meta['icon'] }} fs-2tx text-{{ $meta['color'] }} mb-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            <div class="fs-6 text-muted">{{ $meta['label'] }}</div>
                                            <div class="fs-2 fw-bold text-{{ $meta['color'] }}">{{ number_format($bucket['count']) }}</div>
                                            <div class="fs-7 fw-semibold">{{ currency_symbol() }}{{ number_format($bucket['amount'], 2) }}</div>
                                            <div class="fs-8 text-muted">{{ $meta['sub'] }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- TOP DEBTORS --}}
                {{-- ============================================================ --}}
                @if($topDebtors->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-people fs-2 me-2"></i>
                            {{ __('auth.top_debtors') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('auth.customer') }}</th>
                                        <th class="text-center">{{ __('auth.invoices') }}</th>
                                        <th class="text-end">{{ __('auth.outstanding') }}</th>
                                        <th class="text-center">{{ __('auth.overdue') }}</th>
                                        <th class="text-end">{{ __('auth.overdue_amount') }}</th>
                                        <th class="text-center">{{ __('auth.oldest_days') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topDebtors as $debtor)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $debtor->customer_name }}</span>
                                            @if(!$debtor->customer_id)
                                                <span class="badge badge-light-secondary ms-1">{{ __('auth.guest') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ number_format($debtor->invoice_count) }}</td>
                                        <td class="text-end fw-bold text-danger">{{ currency_symbol() }}{{ number_format($debtor->total_outstanding, 2) }}</td>
                                        <td class="text-center">
                                            @if($debtor->overdue_count > 0)
                                                <span class="badge badge-light-danger">{{ $debtor->overdue_count }}</span>
                                            @else
                                                <span class="badge badge-light-secondary">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end text-warning">{{ currency_symbol() }}{{ number_format($debtor->overdue_amount, 2) }}</td>
                                        <td class="text-center">
                                            @if($debtor->oldest_days_overdue > 0)
                                                <span class="badge badge-light-{{ $debtor->oldest_days_overdue > 90 ? 'dark' : ($debtor->oldest_days_overdue > 60 ? 'danger' : ($debtor->oldest_days_overdue > 30 ? 'warning' : 'info')) }}">
                                                    {{ $debtor->oldest_days_overdue }} {{ __('auth.days') }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
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
                {{-- BY LOCATION --}}
                {{-- ============================================================ --}}
                @if($byLocation->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-geolocation fs-2 me-2"></i>
                            {{ __('auth.outstanding_by_location') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('auth.location') }}</th>
                                        <th class="text-center">{{ __('auth.invoices') }}</th>
                                        <th class="text-end">{{ __('auth.outstanding') }}</th>
                                        <th class="text-end">{{ __('auth.overdue_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byLocation as $loc)
                                    <tr>
                                        <td><span class="fw-bold">{{ $loc->location }}</span></td>
                                        <td class="text-center">{{ number_format($loc->invoice_count) }}</td>
                                        <td class="text-end fw-bold text-danger">{{ currency_symbol() }}{{ number_format($loc->outstanding, 2) }}</td>
                                        <td class="text-end text-warning">{{ currency_symbol() }}{{ number_format($loc->overdue_amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($byLocation->sum('invoice_count')) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($byLocation->sum('outstanding'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($byLocation->sum('overdue_amount'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- DETAIL TABLE — ALL OUTSTANDING INVOICES --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2"></i>
                            {{ __('auth.outstanding_invoices') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-6">
                                {{ $invoicesPaginated->total() }} {{ __('auth.invoices') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="outstandingTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 130px;">{{ __('auth.invoice_number') }}</th>
                                        <th style="min-width: 140px;">{{ __('auth.customer') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.issue_date') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.due_date') }}</th>
                                        <th class="text-center" style="min-width: 90px;">{{ __('auth.days_overdue') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.aging_bucket') }}</th>
                                        <th class="text-end" style="min-width: 110px;">{{ __('auth.total') }}</th>
                                        <th class="text-end" style="min-width: 110px;">{{ __('auth.paid') }}</th>
                                        <th class="text-end" style="min-width: 110px;">{{ __('auth.balance_due') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.location') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoicesPaginated as $invoice)
                                    @php
                                        $bucketMeta = [
                                            'current' => ['label' => __('auth.current'),     'color' => 'success'],
                                            '1_30'    => ['label' => __('auth.days_1_30'),   'color' => 'info'],
                                            '31_60'   => ['label' => __('auth.days_31_60'),  'color' => 'warning'],
                                            '61_90'   => ['label' => __('auth.days_61_90'),  'color' => 'danger'],
                                            '90_plus' => ['label' => __('auth.days_90_plus'),'color' => 'dark'],
                                        ][$invoice->aging_bucket] ?? ['label' => '—', 'color' => 'secondary'];
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-primary">{{ $invoice->invoice_number }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $invoice->billing_name }}</span>
                                            @if($invoice->billing_phone)
                                                <br><small class="text-muted">{{ $invoice->billing_phone }}</small>
                                            @endif
                                        </td>
                                        <td><span class="text-muted">{{ $invoice->issue_date->format('M d, Y') }}</span></td>
                                        <td>
                                            @if($invoice->due_date)
                                                <span class="{{ $invoice->due_date->isPast() ? 'text-danger fw-bold' : 'text-muted' }}">
                                                    {{ $invoice->due_date->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($invoice->days_past_due > 0)
                                                <span class="badge badge-light-{{ $invoice->days_past_due > 90 ? 'dark' : ($invoice->days_past_due > 60 ? 'danger' : ($invoice->days_past_due > 30 ? 'warning' : 'info')) }}">
                                                    {{ $invoice->days_past_due }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $bucketMeta['color'] }}">{{ $bucketMeta['label'] }}</span>
                                        </td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($invoice->total, 2) }}</td>
                                        <td class="text-end text-success">{{ currency_symbol() }}{{ number_format($invoice->amount_paid, 2) }}</td>
                                        <td class="text-end fw-bold text-danger">{{ currency_symbol() }}{{ number_format($invoice->balance_due, 2) }}</td>
                                        <td>
                                            <span class="text-muted fs-7">{{ $invoice->order->location->name ?? 'N/A' }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="6">{{ __('accounting.total_average') }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($invoicesPaginated->sum('total'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($invoicesPaginated->sum('amount_paid'), 2) }}</th>
                                        <th class="text-end fw-bold text-danger">{{ currency_symbol() }}{{ number_format($invoicesPaginated->sum('balance_due'), 2) }}</th>
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
                {{-- METADATA FOOTER --}}
                {{-- ============================================================ --}}
                <div class="mt-6 text-muted text-center fs-7">
                    <hr>
                    <p>
                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                        {{ __('accounting.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }}
                        | {{ __('accounting.period') }}: {{ $startDate ?? 'N/A' }} {{ __('accounting.to') }} {{ $endDate ?? 'N/A' }}
                        @if($onlyOverdue)
                            | {{ __('auth.filter') }}: {{ __('auth.only_overdue') }}
                        @endif
                        @if($agingBucket && $agingBucket !== 'all')
                            | {{ __('auth.aging_bucket') }}: {{ collect($agingBucketOptions)->firstWhere('value', $agingBucket)['label'] ?? $agingBucket }}
                        @endif
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