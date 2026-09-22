@extends('layouts.app')

@section('title', __('auth.invoice_delivery_report'))

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
                                {{ __('auth.invoice_delivery_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.invoice_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.delivery') }}</li>
                            </ul>
                        </div>
                        @if(isset($summary) && $summary->total_sends > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('deliveryTable', 'invoice_delivery')">
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
                        <form method="GET" action="{{ route('reports.invoices.delivery') }}" class="row g-3">
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
                                <label class="form-label">{{ __('auth.channel') }}</label>
                                <select class="form-select" name="channel">
                                    @foreach($channels as $c)
                                        <option value="{{ $c['value'] }}" {{ $channel == $c['value'] ? 'selected' : '' }}>{{ $c['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.delivery_status') }}</label>
                                <select class="form-select" name="send_status">
                                    @foreach($sendStatuses as $s)
                                        <option value="{{ $s['value'] }}" {{ $sendStatus == $s['value'] ? 'selected' : '' }}>{{ $s['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">{{ __('accounting.apply_filters') }}</button>
                                <a href="{{ route('reports.invoices.delivery') }}" class="btn btn-light">{{ __('accounting.clear_filters') }}</a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA --}}
                {{-- ============================================================ --}}
                @if(!isset($summary) || $summary->total_sends == 0)
                <div class="card">
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-send fs-4tx text-gray-400 mb-4">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_delivery_records_for_period') }}</p>
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
                                            <i class="ki-duotone ki-send fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold text-primary">{{ number_format($summary->total_sends) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_send_attempts') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ $summary->unique_invoices }} {{ __('auth.unique_invoices') }}
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
                                        <div class="fs-3 fw-bold text-success">{{ number_format($summary->delivery_rate, 1) }}%</div>
                                        <div class="text-muted fs-6">{{ __('auth.delivery_rate') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ $summary->delivered_count }} {{ __('auth.delivered') }} · {{ $summary->sent_count }} {{ __('auth.sent') }}
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
                                        <div class="fs-3 fw-bold text-danger">{{ number_format($summary->failure_rate, 1) }}%</div>
                                        <div class="text-muted fs-6">{{ __('auth.failure_rate') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ $summary->failed_count }} {{ __('auth.failed') }} · {{ $summary->bounced_count }} {{ __('auth.bounced') }}
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
                                        <div class="fs-3 fw-bold text-warning">{{ number_format($summary->pending_count) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.pending_delivery') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ __('auth.awaiting_provider') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- BREAKDOWN BY CHANNEL --}}
                {{-- ============================================================ --}}
                @if($byChannel->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-route fs-2 me-2"></i>
                            {{ __('auth.delivery_by_channel') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('auth.channel') }}</th>
                                        <th class="text-center">{{ __('auth.total') }}</th>
                                        <th class="text-center">{{ __('auth.sent') }}</th>
                                        <th class="text-center">{{ __('auth.delivered') }}</th>
                                        <th class="text-center">{{ __('auth.failed') }}</th>
                                        <th class="text-center">{{ __('auth.bounced') }}</th>
                                        <th class="text-center">{{ __('auth.pending') }}</th>
                                        <th class="text-end">{{ __('auth.success_rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($byChannel as $row)
                                    @php
                                        $channelColor = [
                                            'email'       => 'primary',
                                            'sms'         => 'info',
                                            'whatsapp'    => 'success',
                                            'portal_link' => 'warning',
                                            'print'       => 'secondary',
                                        ][$row->channel] ?? 'secondary';
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-{{ $channelColor }} fs-7 py-2 px-3">
                                                {{ ucfirst(str_replace('_', ' ', $row->channel)) }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">{{ number_format($row->total) }}</td>
                                        <td class="text-center">{{ number_format($row->sent) }}</td>
                                        <td class="text-center text-success">{{ number_format($row->delivered) }}</td>
                                        <td class="text-center text-danger">{{ number_format($row->failed) }}</td>
                                        <td class="text-center text-warning">{{ number_format($row->bounced) }}</td>
                                        <td class="text-center text-muted">{{ number_format($row->pending) }}</td>
                                        <td class="text-end">
                                            <span class="badge badge-light-{{ $row->success_rate >= 90 ? 'success' : ($row->success_rate >= 70 ? 'warning' : 'danger') }}">
                                                {{ number_format($row->success_rate, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($byChannel->sum('total')) }}</th>
                                        <th class="text-center">{{ number_format($byChannel->sum('sent')) }}</th>
                                        <th class="text-center text-success">{{ number_format($byChannel->sum('delivered')) }}</th>
                                        <th class="text-center text-danger">{{ number_format($byChannel->sum('failed')) }}</th>
                                        <th class="text-center text-warning">{{ number_format($byChannel->sum('bounced')) }}</th>
                                        <th class="text-center">{{ number_format($byChannel->sum('pending')) }}</th>
                                        <th class="text-end">—</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- FAILURE REASONS --}}
                {{-- ============================================================ --}}
                @if($failureReasons->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-warning-2 fs-2 text-danger me-2"></i>
                            {{ __('auth.top_failure_reasons') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th>{{ __('auth.reason') }}</th>
                                        <th class="text-center" style="width: 120px;">{{ __('auth.occurrences') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($failureReasons as $idx => $row)
                                    <tr>
                                        <td class="text-muted">{{ $idx + 1 }}</td>
                                        <td><span class="text-muted">{{ $row->reason }}</span></td>
                                        <td class="text-center">
                                            <span class="badge badge-light-danger">{{ number_format($row->count) }}</span>
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
                {{-- DAILY TREND --}}
                {{-- ============================================================ --}}
                @if($dailyTrend->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-chart-line fs-2 me-2"></i>
                            {{ __('auth.daily_delivery_trend') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.date') }}</th>
                                        <th class="text-center">{{ __('auth.sent') }}</th>
                                        <th class="text-center">{{ __('auth.delivered') }}</th>
                                        <th class="text-center">{{ __('auth.failed') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyTrend as $row)
                                    <tr>
                                        <td><span class="fw-bold">{{ \Carbon\Carbon::parse($row->date)->format('M d, Y') }}</span></td>
                                        <td class="text-center text-primary">{{ number_format($row->sent) }}</td>
                                        <td class="text-center text-success">{{ number_format($row->delivered) }}</td>
                                        <td class="text-center text-danger">{{ number_format($row->failed) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($dailyTrend->sum('sent')) }}</th>
                                        <th class="text-center fw-bold">{{ number_format($dailyTrend->sum('delivered')) }}</th>
                                        <th class="text-center fw-bold">{{ number_format($dailyTrend->sum('failed')) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- DETAIL TABLE — ALL SEND ATTEMPTS --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2"></i>
                            {{ __('auth.send_attempts') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-6">
                                {{ $sendsPaginated->total() }} {{ __('auth.attempts') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="deliveryTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 130px;">{{ __('auth.invoice_number') }}</th>
                                        <th style="min-width: 140px;">{{ __('auth.recipient') }}</th>
                                        <th style="min-width: 90px;">{{ __('auth.channel') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.status') }}</th>
                                        <th style="min-width: 110px;">{{ __('auth.sent_at') }}</th>
                                        <th style="min-width: 110px;">{{ __('auth.delivered_at') }}</th>
                                        <th style="min-width: 120px;">{{ __('auth.provider') }}</th>
                                        <th style="min-width: 120px;">{{ __('auth.sent_by') }}</th>
                                        <th style="min-width: 140px;">{{ __('auth.error_message') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sendsPaginated as $send)
                                    @php
                                        $statusColor = [
                                            'pending'   => 'warning',
                                            'sent'      => 'info',
                                            'delivered' => 'success',
                                            'failed'    => 'danger',
                                            'bounced'   => 'dark',
                                        ][$send->status] ?? 'secondary';

                                        $channelColor = [
                                            'email'       => 'primary',
                                            'sms'         => 'info',
                                            'whatsapp'    => 'success',
                                            'portal_link' => 'warning',
                                            'print'       => 'secondary',
                                        ][$send->channel] ?? 'secondary';
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-primary">{{ $send->invoice->invoice_number ?? 'N/A' }}</span>
                                            @if($send->invoice && $send->invoice->billing_name)
                                                <br><small class="text-muted">{{ $send->invoice->billing_name }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $send->recipient ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $channelColor }} fs-8">
                                                {{ ucfirst(str_replace('_', ' ', $send->channel)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-{{ $statusColor }} fs-7 py-2 px-3">
                                                {{ ucfirst($send->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($send->sent_at)
                                                <span class="text-muted">{{ $send->sent_at->format('M d, Y') }}</span>
                                                <br><small class="text-muted">{{ $send->sent_at->format('H:i A') }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($send->delivered_at)
                                                <span class="text-success">{{ $send->delivered_at->format('M d, Y') }}</span>
                                                <br><small class="text-muted">{{ $send->delivered_at->format('H:i A') }}</small>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted fs-7">{{ $send->provider ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted fs-7">{{ $send->sentBy->name ?? 'System' }}</span>
                                        </td>
                                        <td>
                                            @if($send->error_message)
                                                <span class="text-danger fs-7" title="{{ $send->error_message }}">
                                                    {{ \Illuminate\Support\Str::limit($send->error_message, 60) }}
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
                    <div class="card-footer">
                        {{ $sendsPaginated->links() }}
                        <small class="text-muted float-end">
                            {{ __('accounting.showing') }} {{ $sendsPaginated->firstItem() ?? 0 }} - {{ $sendsPaginated->lastItem() ?? 0 }} {{ __('accounting.of') }} {{ $sendsPaginated->total() }} {{ __('auth.attempts') }}
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