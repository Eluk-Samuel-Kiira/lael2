@extends('layouts.app')

@section('title', __('auth.invoice_status_trends'))

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
                                {{ __('auth.invoice_status_trends') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.invoice_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.status_trends') }}</li>
                            </ul>
                        </div>
                        @if(isset($summary) && $summary->total_invoices > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('statusTrendsTable', 'invoice_status_trends')">
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
                        <form method="GET" action="{{ route('reports.invoices.status-trends') }}" class="row g-3">
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
                                <label class="form-label">{{ __('auth.group_by') }}</label>
                                <select class="form-select" name="group_by">
                                    @foreach($groupOptions as $opt)
                                        <option value="{{ $opt['value'] }}" {{ $groupBy == $opt['value'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2 w-100">{{ __('accounting.apply_filters') }}</button>
                            </div>
                            <div class="col-12">
                                <a href="{{ route('reports.invoices.status-trends') }}" class="btn btn-light">{{ __('accounting.clear_filters') }}</a>
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
                            <i class="ki-duotone ki-chart-simple fs-4tx text-gray-400 mb-4">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_invoices_for_status_trends') }}</p>
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
                                        <div class="fs-3 fw-bold text-primary">{{ number_format($summary->total_invoices) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_invoices') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    <span class="badge badge-light-success me-1">{{ $summary->paid_count }}</span> {{ __('auth.paid') }}
                                    <span class="badge badge-light-warning ms-2 me-1">{{ $summary->unpaid_count }}</span> {{ __('auth.unpaid') }}
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
                                            <i class="ki-duotone ki-time fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold text-success">{{ number_format($summary->avg_days_to_pay, 1) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.avg_days_to_pay') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ __('auth.median') }}: {{ number_format($summary->median_days_to_pay, 1) }} {{ __('auth.days') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-info h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-50px me-3">
                                        <span class="symbol-label bg-info">
                                            <i class="ki-duotone ki-send fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-3 fw-bold text-info">{{ number_format($summary->avg_days_to_send, 1) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.avg_days_to_send') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ __('auth.avg_days_to_view') }}: {{ number_format($summary->avg_days_to_view, 1) }} {{ __('auth.days') }}
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
                                            <i class="ki-duotone ki-check-circle fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        @php
                                            $totalWithTerms = $summary->paid_within_terms + $summary->paid_late;
                                            $onTimeRate = $totalWithTerms > 0
                                                ? ($summary->paid_within_terms / $totalWithTerms) * 100
                                                : 0;
                                        @endphp
                                        <div class="fs-3 fw-bold text-warning">{{ number_format($onTimeRate, 1) }}%</div>
                                        <div class="text-muted fs-6">{{ __('auth.paid_within_terms') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    {{ $summary->paid_late }} {{ __('auth.paid_late') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- PAYMENT SPEED DISTRIBUTION --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-rocket fs-2 me-2"></i>
                            {{ __('auth.payment_speed_distribution') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            @php
                                $totalSpeed = array_sum($paymentSpeedBuckets);
                                $speedBuckets = [
                                    'same_day'   => ['label' => __('auth.same_day'),   'color' => 'success', 'icon' => 'ki-flash-circle'],
                                    '1_7_days'   => ['label' => __('auth.days_1_7'),   'color' => 'info',    'icon' => 'ki-time'],
                                    '8_14_days'  => ['label' => __('auth.days_8_14'),  'color' => 'primary', 'icon' => 'ki-calendar-8'],
                                    '15_30_days' => ['label' => __('auth.days_15_30'), 'color' => 'warning', 'icon' => 'ki-calendar-tick'],
                                    '31_60_days' => ['label' => __('auth.days_31_60'), 'color' => 'danger',  'icon' => 'ki-warning-2'],
                                    '60_plus'    => ['label' => __('auth.days_60_plus'),'color' => 'dark',   'icon' => 'ki-information-5'],
                                ];
                            @endphp
                            @foreach($speedBuckets as $key => $meta)
                                @php
                                    $count = $paymentSpeedBuckets[$key] ?? 0;
                                    $pct = $totalSpeed > 0 ? ($count / $totalSpeed) * 100 : 0;
                                @endphp
                                <div class="col-md-6 col-lg-2">
                                    <div class="card bg-light-{{ $meta['color'] }} h-100">
                                        <div class="card-body text-center">
                                            <i class="ki-duotone {{ $meta['icon'] }} fs-2tx text-{{ $meta['color'] }} mb-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            <div class="fs-6 text-muted">{{ $meta['label'] }}</div>
                                            <div class="fs-2 fw-bold text-{{ $meta['color'] }}">{{ number_format($count) }}</div>
                                            <div class="fs-8 text-muted">{{ number_format($pct, 1) }}%</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- STATUS FUNNEL --}}
                {{-- ============================================================ --}}
                @if($statusFunnel->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-filter-square fs-2 me-2"></i>
                            {{ __('auth.status_funnel') }}
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('auth.status') }}</th>
                                        <th class="text-center">{{ __('auth.count') }}</th>
                                        <th class="text-end">{{ __('auth.share') }}</th>
                                        <th style="width: 45%;">{{ __('auth.distribution') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $funnelTotal = $statusFunnel->sum('count'); @endphp
                                    @foreach($statusFunnel as $row)
                                    @php
                                        $statusColor = \App\Models\Invoice::STATUS_COLORS[$row->status] ?? 'secondary';
                                        $pct = $funnelTotal > 0 ? ($row->count / $funnelTotal) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-{{ $statusColor }} fs-7 py-2 px-3">
                                                {{ $row->label }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">{{ number_format($row->count) }}</td>
                                        <td class="text-end">
                                            <span class="badge badge-light-secondary">{{ number_format($pct, 1) }}%</span>
                                        </td>
                                        <td>
                                            <div class="progress h-8px w-100 bg-light-{{ $statusColor }}">
                                                <div class="progress-bar bg-{{ $statusColor }}"
                                                     role="progressbar"
                                                     style="width: {{ $pct }}%"
                                                     aria-valuenow="{{ $pct }}"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($funnelTotal) }}</th>
                                        <th class="text-end">100%</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- TIME-TO-PAYMENT METRICS --}}
                {{-- ============================================================ --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-timer fs-2 me-2"></i>
                            {{ __('auth.time_to_payment_metrics') }}
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-3">
                                <div class="border border-dashed border-gray-300 rounded p-4 text-center h-100">
                                    <div class="fs-7 text-muted text-uppercase mb-2">{{ __('auth.avg_days_to_send') }}</div>
                                    <div class="fs-1 fw-bold text-info">{{ number_format($summary->avg_days_to_send, 1) }}</div>
                                    <div class="fs-7 text-muted">{{ __('auth.issue_to_send') }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-dashed border-gray-300 rounded p-4 text-center h-100">
                                    <div class="fs-7 text-muted text-uppercase mb-2">{{ __('auth.avg_days_to_view') }}</div>
                                    <div class="fs-1 fw-bold text-primary">{{ number_format($summary->avg_days_to_view, 1) }}</div>
                                    <div class="fs-7 text-muted">{{ __('auth.send_to_view') }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-dashed border-gray-300 rounded p-4 text-center h-100">
                                    <div class="fs-7 text-muted text-uppercase mb-2">{{ __('auth.avg_days_to_pay') }}</div>
                                    <div class="fs-1 fw-bold text-success">{{ number_format($summary->avg_days_to_pay, 1) }}</div>
                                    <div class="fs-7 text-muted">{{ __('auth.issue_to_pay') }}</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-dashed border-gray-300 rounded p-4 text-center h-100">
                                    <div class="fs-7 text-muted text-uppercase mb-2">{{ __('auth.median_days_to_pay') }}</div>
                                    <div class="fs-1 fw-bold text-secondary">{{ number_format($summary->median_days_to_pay, 1) }}</div>
                                    <div class="fs-7 text-muted">{{ __('auth.issue_to_pay') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mt-2">
                            <div class="col-md-6">
                                <div class="alert alert-success d-flex align-items-center">
                                    <i class="ki-duotone ki-rocket fs-2tx text-success me-3">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <div>
                                        <div class="fs-6 fw-bold">{{ __('auth.fastest_payment') }}</div>
                                        <div class="fs-4 fw-bold text-success">{{ $summary->fastest_payment_days }} {{ __('auth.days') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-danger d-flex align-items-center">
                                    <i class="ki-duotone ki-time fs-2tx text-danger me-3">
                                        <span class="path1"></span><span class="path2"></span>
                                    </i>
                                    <div>
                                        <div class="fs-6 fw-bold">{{ __('auth.slowest_payment') }}</div>
                                        <div class="fs-4 fw-bold text-danger">{{ $summary->slowest_payment_days }} {{ __('auth.days') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- TREND TABLE --}}
                {{-- ============================================================ --}}
                @if($trend->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-chart-line fs-2 me-2"></i>
                            {{ __('auth.invoice_trend') }}
                            <span class="badge badge-light-primary ms-2">{{ ucfirst($groupBy) }}</span>
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('auth.period') }}</th>
                                        <th class="text-center">{{ __('auth.invoices') }}</th>
                                        <th class="text-center">{{ __('auth.paid') }}</th>
                                        <th class="text-end">{{ __('auth.total_value') }}</th>
                                        <th class="text-end">{{ __('auth.collected') }}</th>
                                        <th class="text-end">{{ __('auth.outstanding') }}</th>
                                        <th class="text-end">{{ __('auth.avg_days_to_pay') }}</th>
                                        <th class="text-end">{{ __('auth.collection_rate') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($trend as $row)
                                    <tr>
                                        <td><span class="fw-bold">{{ $row->period }}</span></td>
                                        <td class="text-center">{{ number_format($row->invoice_count) }}</td>
                                        <td class="text-center text-success">{{ number_format($row->paid_count) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($row->total_value, 2) }}</td>
                                        <td class="text-end text-success">{{ currency_symbol() }}{{ number_format($row->paid_value, 2) }}</td>
                                        <td class="text-end text-danger">{{ currency_symbol() }}{{ number_format($row->outstanding_value, 2) }}</td>
                                        <td class="text-end">
                                            @if($row->avg_days_to_pay > 0)
                                                <span class="badge badge-light-{{ $row->avg_days_to_pay <= 14 ? 'success' : ($row->avg_days_to_pay <= 30 ? 'warning' : 'danger') }}">
                                                    {{ number_format($row->avg_days_to_pay, 1) }} {{ __('auth.days') }}
                                                </span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <span class="badge badge-light-{{ $row->collection_rate >= 90 ? 'success' : ($row->collection_rate >= 70 ? 'warning' : 'danger') }}">
                                                {{ number_format($row->collection_rate, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($trend->sum('invoice_count')) }}</th>
                                        <th class="text-center fw-bold">{{ number_format($trend->sum('paid_count')) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($trend->sum('total_value'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($trend->sum('paid_value'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($trend->sum('outstanding_value'), 2) }}</th>
                                        <th class="text-end fw-bold">{{ number_format($trend->avg('avg_days_to_pay'), 1) }} {{ __('auth.days') }}</th>
                                        <th class="text-end fw-bold">{{ number_format($trend->avg('collection_rate'), 1) }}%</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- DETAIL TABLE --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2"></i>
                            {{ __('auth.invoice_lifecycle_detail') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-6">
                                {{ $invoicesPaginated->total() }} {{ __('auth.invoices') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="statusTrendsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 130px;">{{ __('auth.invoice_number') }}</th>
                                        <th style="min-width: 140px;">{{ __('auth.customer') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.issue_date') }}</th>
                                        <th style="min-width: 100px;">{{ __('auth.status') }}</th>
                                        <th class="text-center" style="min-width: 80px;">{{ __('auth.send_days') }}</th>
                                        <th class="text-center" style="min-width: 80px;">{{ __('auth.view_days') }}</th>
                                        <th class="text-center" style="min-width: 80px;">{{ __('auth.pay_days') }}</th>
                                        <th class="text-end" style="min-width: 110px;">{{ __('auth.total') }}</th>
                                        <th class="text-end" style="min-width: 110px;">{{ __('auth.balance_due') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoicesPaginated as $invoice)
                                    @php
                                        $paidLate = $invoice->days_to_pay !== null
                                            && $invoice->days_to_due !== null
                                            && $invoice->days_to_pay > $invoice->days_to_due;
                                    @endphp
                                    <tr>
                                        <td><span class="fw-bold text-primary">{{ $invoice->invoice_number }}</span></td>
                                        <td>
                                            <span class="fw-semibold">{{ $invoice->billing_name }}</span>
                                            @if(!$invoice->customer_id)
                                                <span class="badge badge-light-secondary ms-1">{{ __('auth.guest') }}</span>
                                            @endif
                                        </td>
                                        <td><span class="text-muted">{{ $invoice->issue_date->format('M d, Y') }}</span></td>
                                        <td>
                                            <span class="badge badge-light-{{ $invoice->status_color }} fs-7 py-2 px-3">
                                                {{ $invoice->status_label }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($invoice->days_to_send !== null)
                                                <span class="fw-semibold text-info">{{ $invoice->days_to_send }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($invoice->days_to_view !== null)
                                                <span class="fw-semibold text-primary">{{ $invoice->days_to_view }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($invoice->days_to_pay !== null)
                                                <span class="fw-semibold {{ $paidLate ? 'text-danger' : 'text-success' }}">
                                                    {{ $invoice->days_to_pay }}
                                                </span>
                                                @if($paidLate)
                                                    <br><small class="text-danger">{{ __('auth.late') }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($invoice->total, 2) }}</td>
                                        <td class="text-end fw-bold {{ $invoice->balance_due > 0 ? 'text-danger' : 'text-muted' }}">
                                            {{ currency_symbol() }}{{ number_format($invoice->balance_due, 2) }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
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