@extends('layouts.app')

@section('title', __('auth.invoice_payments_report'))

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
                                {{ __('auth.invoice_payments_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.invoice_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.payments') }}</li>
                            </ul>
                        </div>
                        @if(isset($summary) && $summary->total_payments > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('invoicePaymentsTable', 'invoice_payments')">
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
                        <form method="GET" action="{{ route('reports.invoices.payments') }}" class="row g-3">
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
                                <label class="form-label">{{ __('auth.payment_method') }}</label>
                                <select class="form-select" name="payment_method_id" data-control="select2">
                                    <option value="">{{ __('auth.all_methods') }}</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method->id }}" {{ $paymentMethod == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.recorded_via') }}</label>
                                <select class="form-select" name="recorded_via">
                                    @foreach($channels as $c)
                                        <option value="{{ $c['value'] }}" {{ $recordedVia == $c['value'] ? 'selected' : '' }}>{{ $c['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('auth.status') }}</label>
                                <select class="form-select" name="status">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s['value'] }}" {{ $statusFilter == $s['value'] ? 'selected' : '' }}>{{ $s['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-9 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">{{ __('accounting.apply_filters') }}</button>
                                <a href="{{ route('reports.invoices.payments') }}" class="btn btn-light">{{ __('accounting.clear_filters') }}</a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA --}}
                {{-- ============================================================ --}}
                @if(!isset($summary) || $summary->total_payments == 0)
                <div class="card">
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_invoice_payments_for_period') }}</p>
                        </div>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- KPI CARDS --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
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
                                        <div class="fs-3 fw-bold text-success">{{ currency_symbol() }}{{ number_format($summary->completed_amount, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_collected') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ $summary->completed_count }} {{ __('auth.completed_payments') }}
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
                                            <i class="ki-duotone ki-basket fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold text-primary">{{ number_format($summary->total_payments) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_payments') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ __('auth.avg') }}: {{ currency_symbol() }}{{ number_format($summary->average_payment, 2) }}
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
                                            <i class="ki-duotone ki-arrow-up fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold text-warning">{{ currency_symbol() }}{{ number_format($summary->largest_payment, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.largest_payment') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ __('auth.avg') }}: {{ currency_symbol() }}{{ number_format($summary->average_payment, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-danger h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-danger">
                                            <i class="ki-duotone ki-cross-circle fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold text-danger">{{ number_format($summary->failed_count + $summary->refunded_count) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.failed_refunded') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ currency_symbol() }}{{ number_format($summary->failed_amount + $summary->refunded_amount, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- BREAKDOWNS: METHOD + CHANNEL --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-wallet fs-2 me-2"></i>
                                    {{ __('auth.payments_by_method') }}
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('auth.payment_method') }}</th>
                                                <th class="text-center">{{ __('auth.count') }}</th>
                                                <th class="text-end">{{ __('auth.total_amount') }}</th>
                                                <th class="text-end">{{ __('auth.avg') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($byMethod as $row)
                                            <tr>
                                                <td>
                                                    <span class="fw-bold">{{ $row->method }}</span>
                                                    <br><small class="text-muted text-capitalize">{{ $row->type }}</small>
                                                </td>
                                                <td class="text-center">{{ number_format($row->count) }}</td>
                                                <td class="text-end fw-bold text-success">{{ currency_symbol() }}{{ number_format($row->total_amount, 2) }}</td>
                                                <td class="text-end">{{ currency_symbol() }}{{ number_format($row->average_amount, 2) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th>{{ __('accounting.total_average') }}</th>
                                                <th class="text-center">{{ number_format($byMethod->sum('count')) }}</th>
                                                <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($byMethod->sum('total_amount'), 2) }}</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="ki-duotone ki-route fs-2 me-2"></i>
                                    {{ __('auth.payments_by_channel') }}
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>{{ __('auth.channel') }}</th>
                                                <th class="text-center">{{ __('auth.count') }}</th>
                                                <th class="text-end">{{ __('auth.total_amount') }}</th>
                                                <th class="text-end">{{ __('auth.share') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $channelTotal = $byChannel->sum('total_amount'); @endphp
                                            @foreach($byChannel as $row)
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light-{{
                                                        $row->channel === 'pos' ? 'primary' :
                                                        ($row->channel === 'manual' ? 'warning' :
                                                        ($row->channel === 'webhook' ? 'info' : 'secondary'))
                                                    }} fs-7 py-2 px-3">
                                                        {{ ucfirst($row->channel) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">{{ number_format($row->count) }}</td>
                                                <td class="text-end fw-bold text-success">{{ currency_symbol() }}{{ number_format($row->total_amount, 2) }}</td>
                                                <td class="text-end">
                                                    <span class="badge badge-light-secondary">
                                                        {{ $channelTotal > 0 ? number_format(($row->total_amount / $channelTotal) * 100, 1) : 0 }}%
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th>{{ __('accounting.total_average') }}</th>
                                                <th class="text-center">{{ number_format($byChannel->sum('count')) }}</th>
                                                <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($channelTotal, 2) }}</th>
                                                <th class="text-end">100%</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- DAILY TREND --}}
                {{-- ============================================================ --}}
                @if($dailyTrend->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-chart-line fs-2 me-2"></i>
                            {{ __('auth.daily_payment_trend') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.date') }}</th>
                                        <th class="text-center">{{ __('auth.payments') }}</th>
                                        <th class="text-end">{{ __('auth.total_amount') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyTrend as $row)
                                    <tr>
                                        <td><span class="fw-bold">{{ \Carbon\Carbon::parse($row->date)->format('M d, Y') }}</span></td>
                                        <td class="text-center">{{ number_format($row->payment_count) }}</td>
                                        <td class="text-end text-success fw-semibold">{{ currency_symbol() }}{{ number_format($row->total_amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($dailyTrend->sum('payment_count')) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($dailyTrend->sum('total_amount'), 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- DETAIL TABLE — ALL PAYMENTS --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2"></i>
                            {{ __('auth.payment_transactions') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-6">
                                {{ $paymentsPaginated->total() }} {{ __('auth.payments') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="invoicePaymentsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 130px;">{{ __('auth.invoice_number') }}</th>
                                        <th style="min-width: 140px;">{{ __('auth.customer') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.date') }}</th>
                                        <th style="min-width: 120px;">{{ __('auth.payment_method') }}</th>
                                        <th style="min-width: 90px;">{{ __('auth.channel') }}</th>
                                        <th style="min-width: 120px;">{{ __('auth.reference') }}</th>
                                        <th class="text-end" style="min-width: 120px;">{{ __('auth.amount') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.status') }}</th>
                                        <th style="min-width: 120px;">{{ __('auth.processed_by') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentsPaginated as $payment)
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-primary">{{ $payment->invoice_number }}</span>
                                            @if($payment->order_number !== 'N/A')
                                                <br><small class="text-muted">{{ $payment->order_number }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $payment->customer_name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $payment->processed_at->format('M d, Y') }}</span>
                                            <br><small class="text-muted">{{ $payment->processed_at->format('H:i A') }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $payment->payment_method }}</span>
                                            <br><small class="text-muted text-capitalize">{{ $payment->payment_method_type }}</small>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{
                                                $payment->recorded_via === 'pos' ? 'primary' :
                                                ($payment->recorded_via === 'manual' ? 'warning' :
                                                ($payment->recorded_via === 'webhook' ? 'info' : 'secondary'))
                                            }} fs-8">
                                                {{ ucfirst($payment->recorded_via) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted fs-7">{{ $payment->reference }}</span>
                                        </td>
                                        <td class="text-end fw-bold text-success">{{ currency_symbol() }}{{ number_format($payment->amount, 2) }}</td>
                                        <td>
                                            <span class="badge badge-light-{{
                                                $payment->status === 'completed' ? 'success' :
                                                ($payment->status === 'failed' ? 'danger' :
                                                ($payment->status === 'refunded' ? 'secondary' :
                                                ($payment->status === 'pending' ? 'warning' : 'info')))
                                            }} fs-7 py-2 px-3">
                                                {{ ucfirst($payment->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted fs-7">{{ $payment->processed_by }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="6">{{ __('accounting.total_average') }}</th>
                                        <th class="text-end fw-bold text-success">{{ currency_symbol() }}{{ number_format($paymentsPaginated->sum('amount'), 2) }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        {{ $paymentsPaginated->links() }}
                        <small class="text-muted float-end">
                            {{ __('accounting.showing') }} {{ $paymentsPaginated->firstItem() ?? 0 }} - {{ $paymentsPaginated->lastItem() ?? 0 }} {{ __('accounting.of') }} {{ $paymentsPaginated->total() }} {{ __('auth.payments') }}
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