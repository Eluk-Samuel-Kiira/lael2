{{-- resources/views/reports/orders/summary.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.order_summary_report'))

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
                                {{ __('auth.order_summary_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">{{ __('accounting.dashboard') }}</a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.summary') }}</li>
                            </ul>
                        </div>
                        @if(isset($summary) && $summary->total_orders > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('dailyBreakdownTable', 'order_summary_daily')">
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
                        <form method="GET" action="{{ route('reports.orders.summary') }}" class="row g-3">
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
                                <label class="form-label">{{ __('auth.order_type') }}</label>
                                <select class="form-select" name="order_type">
                                    <option value="">{{ __('auth.all_types') }}</option>
                                    <option value="sale" {{ $orderType == 'sale' ? 'selected' : '' }}>{{ __('auth.sale') }}</option>
                                    <option value="return" {{ $orderType == 'return' ? 'selected' : '' }}>{{ __('auth.return') }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('auth.order_status') }}</label>
                                <select class="form-select" name="order_status">
                                    <option value="">{{ __('auth.all_statuses') }}</option>
                                    <option value="draft" {{ $orderStatus == 'draft' ? 'selected' : '' }}>{{ __('auth.draft') }}</option>
                                    <option value="confirmed" {{ $orderStatus == 'confirmed' ? 'selected' : '' }}>{{ __('auth.confirmed') }}</option>
                                    <option value="completed" {{ $orderStatus == 'completed' ? 'selected' : '' }}>{{ __('auth.completed') }}</option>
                                    <option value="cancelled" {{ $orderStatus == 'cancelled' ? 'selected' : '' }}>{{ __('auth.cancelled') }}</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">{{ __('accounting.apply_filters') }}</button>
                                <a href="{{ route('reports.orders.summary') }}" class="btn btn-light">{{ __('accounting.clear_filters') }}</a>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA MESSAGE --}}
                {{-- ============================================================ --}}
                @if(!isset($summary) || $summary->total_orders == 0)
                <div class="card">
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('auth.no_orders_found_for_period') }}</p>
                            <p class="text-muted fs-7">{{ __('auth.try_adjusting_date_range_or_filters') }}</p>
                        </div>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- KPI CARDS - Key Performance Indicators --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
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
                                        <div class="fs-2 fw-bold">{{ number_format($summary->total_orders) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_orders') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    <span class="badge badge-light-success me-1">{{ number_format($summary->completed_orders) }}</span> {{ __('auth.completed') }}
                                    <span class="badge badge-light-warning ms-2 me-1">{{ number_format($summary->confirmed_orders) }}</span> {{ __('auth.confirmed') }}
                                    <span class="badge badge-light-danger ms-2 me-1">{{ number_format($summary->cancelled_orders) }}</span> {{ __('auth.cancelled') }}
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
                                        <div class="fs-2 fw-bold">{{ currency_symbol() }}{{ number_format($summary->total_sales, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_sales') }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small text-muted">
                                    <i class="ki-duotone ki-arrow-up text-success"></i> 
                                    {{ __('auth.avg_order_value') }}: {{ currency_symbol() }}{{ number_format($summary->average_order_value, 2) }}
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
                                            <i class="ki-duotone ki-calculator fs-2x text-white"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="fs-4 fw-bold text-success">{{ currency_symbol() }}{{ number_format($summary->total_tax, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_tax') }}</div>
                                        <div class="fs-4 fw-bold text-danger mt-1">{{ currency_symbol() }}{{ number_format($summary->total_discount, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.total_discount') }}</div>
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
                                        <div class="fs-4 fw-bold text-success">{{ currency_symbol() }}{{ number_format($summary->max_order_value, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.highest_order') }}</div>
                                        <div class="fs-4 fw-bold text-danger mt-1">{{ currency_symbol() }}{{ number_format($summary->min_order_value, 2) }}</div>
                                        <div class="text-muted fs-6">{{ __('auth.lowest_order') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- PAYMENT SUMMARY CARDS --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-4">
                        <div class="card bg-light-info">
                            <div class="card-body text-center">
                                <div class="fs-2 fw-bold text-info">{{ currency_symbol() }}{{ number_format($summary->total_paid, 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_paid') }}</div>
                                <div class="progress mt-2" style="height: 6px;">
                                    @php 
                                        $paidPercent = $summary->total_sales > 0 ? ($summary->total_paid / $summary->total_sales) * 100 : 0;
                                    @endphp
                                    <div class="progress-bar bg-info" style="width: {{ min($paidPercent, 100) }}%;"></div>
                                </div>
                                <small class="text-muted">{{ number_format($paidPercent, 1) }}% {{ __('auth.of_total_sales') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-danger">
                            <div class="card-body text-center">
                                <div class="fs-2 fw-bold text-danger">{{ currency_symbol() }}{{ number_format($summary->total_balance, 2) }}</div>
                                <div class="text-muted">{{ __('auth.outstanding_balance') }}</div>
                                <div class="progress mt-2" style="height: 6px;">
                                    @php 
                                        $balancePercent = $summary->total_sales > 0 ? ($summary->total_balance / $summary->total_sales) * 100 : 0;
                                    @endphp
                                    <div class="progress-bar bg-danger" style="width: {{ min($balancePercent, 100) }}%;"></div>
                                </div>
                                <small class="text-muted">{{ number_format($balancePercent, 1) }}% {{ __('auth.of_total_sales') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light-success">
                            <div class="card-body text-center">
                                <div class="fs-2 fw-bold text-success">{{ number_format($summary->completed_orders) }}</div>
                                <div class="text-muted">{{ __('auth.completed_orders') }}</div>
                                <div class="progress mt-2" style="height: 6px;">
                                    @php 
                                        $completedPercent = $summary->total_orders > 0 ? ($summary->completed_orders / $summary->total_orders) * 100 : 0;
                                    @endphp
                                    <div class="progress-bar bg-success" style="width: {{ min($completedPercent, 100) }}%;"></div>
                                </div>
                                <small class="text-muted">{{ number_format($completedPercent, 1) }}% {{ __('auth.completion_rate') }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- ORDER STATUS BREAKDOWN TABLE --}}
                {{-- ============================================================ --}}
                @if(isset($statusBreakdown) && $statusBreakdown->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-chart-pie-3 fs-2 me-2"></i>
                            {{ __('auth.order_status_breakdown') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-6">{{ $summary->total_orders }} {{ __('auth.total_orders') }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 15%;">{{ __('accounting.status') }}</th>
                                        <th style="width: 15%;" class="text-center">{{ __('auth.order_count') }}</th>
                                        <th style="width: 20%;" class="text-end">{{ __('accounting.total_amount') }}</th>
                                        <th style="width: 20%;" class="text-end">{{ __('accounting.average_amount') }}</th>
                                        <th style="width: 30%;" class="text-center">{{ __('accounting.distribution') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($statusBreakdown as $status)
                                    @php
                                        $percentage = $summary->total_orders > 0 ? ($status->count / $summary->total_orders) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-{{ $status->color ?? 'secondary' }} fs-7">
                                                {{ ucfirst($status->status ?? 'Unknown') }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">{{ number_format($status->count) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($status->total_amount ?? 0, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($status->average_amount ?? 0, 2) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-3" style="height: 8px;">
                                                    <div class="progress-bar bg-{{ $status->color ?? 'secondary' }}" 
                                                         style="width: {{ $percentage }}%;" 
                                                         role="progressbar"
                                                         aria-valuenow="{{ $percentage }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="fw-bold min-w-55px text-end">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total') }}</th>
                                        <th class="text-center">{{ number_format($summary->total_orders) }}</th>
                                        <th class="text-end">{{ currency_symbol() }}{{ number_format($summary->total_sales, 2) }}</th>
                                        <th class="text-end">{{ currency_symbol() }}{{ number_format($summary->average_order_value, 2) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- ORDER TYPE BREAKDOWN TABLE --}}
                {{-- ============================================================ --}}
                @if(isset($typeBreakdown) && $typeBreakdown->count() > 0)
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-category fs-2 me-2"></i>
                            {{ __('auth.order_type_breakdown') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-info fs-6">{{ $summary->total_orders }} {{ __('auth.total_orders') }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 15%;">{{ __('accounting.type') }}</th>
                                        <th style="width: 15%;" class="text-center">{{ __('auth.order_count') }}</th>
                                        <th style="width: 20%;" class="text-end">{{ __('accounting.total_amount') }}</th>
                                        <th style="width: 20%;" class="text-end">{{ __('accounting.average_amount') }}</th>
                                        <th style="width: 30%;" class="text-center">{{ __('accounting.distribution') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($typeBreakdown as $type)
                                    @php
                                        $percentage = $summary->total_orders > 0 ? ($type->count / $summary->total_orders) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-light-{{ $type->color ?? 'primary' }} fs-7">
                                                {{ ucfirst($type->type ?? 'Unknown') }}
                                            </span>
                                        </td>
                                        <td class="text-center fw-bold">{{ number_format($type->count) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($type->total_amount ?? 0, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($type->average_amount ?? 0, 2) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-3" style="height: 8px;">
                                                    <div class="progress-bar bg-{{ $type->color ?? 'primary' }}" 
                                                         style="width: {{ $percentage }}%;"
                                                         role="progressbar"
                                                         aria-valuenow="{{ $percentage }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="fw-bold min-w-55px text-end">{{ number_format($percentage, 1) }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.total') }}</th>
                                        <th class="text-center">{{ number_format($summary->total_orders) }}</th>
                                        <th class="text-end">{{ currency_symbol() }}{{ number_format($summary->total_sales, 2) }}</th>
                                        <th class="text-end">{{ currency_symbol() }}{{ number_format($summary->average_order_value, 2) }}</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- DAILY BREAKDOWN TABLE --}}
                {{-- ============================================================ --}}
                @if(isset($dailyBreakdown) && $dailyBreakdown->count() > 0)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-calendar-8 fs-2 me-2"></i>
                            {{ __('auth.daily_breakdown') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-success fs-6">
                                {{ $dailyBreakdown->count() }} {{ __('auth.days') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0" id="dailyBreakdownTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.date') }}</th>
                                        <th>{{ __('accounting.day') }}</th>
                                        <th class="text-center">{{ __('auth.orders') }}</th>
                                        <th class="text-end">{{ __('auth.daily_total') }}</th>
                                        <th class="text-end">{{ __('auth.daily_tax') }}</th>
                                        <th class="text-end">{{ __('auth.daily_discount') }}</th>
                                        <th class="text-end">{{ __('auth.avg_order_value') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dailyBreakdown as $day)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $day->formatted_date ?? $day->date }}</span>
                                        </td>
                                        <td>
                                            @if($day->is_weekend ?? false)
                                                <span class="badge badge-light-danger">{{ $day->day_name ?? '' }}</span>
                                            @else
                                                <span class="badge badge-light-secondary">{{ $day->day_name ?? '' }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary fs-7">{{ number_format($day->order_count) }}</span>
                                        </td>
                                        <td class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($day->daily_total ?? 0, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($day->daily_tax ?? 0, 2) }}</td>
                                        <td class="text-end text-danger">{{ currency_symbol() }}{{ number_format($day->daily_discount ?? 0, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($day->daily_average ?? 0, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2">{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($dailyBreakdown->sum('order_count')) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($dailyBreakdown->sum('daily_total'), 2) }}</th>
                                        <th class="text-end">{{ currency_symbol() }}{{ number_format($dailyBreakdown->sum('daily_tax'), 2) }}</th>
                                        <th class="text-end">{{ currency_symbol() }}{{ number_format($dailyBreakdown->sum('daily_discount'), 2) }}</th>
                                        <th class="text-end">{{ currency_symbol() }}{{ number_format($dailyBreakdown->avg('daily_average') ?? 0, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer">
                        <small class="text-muted">
                            <i class="ki-duotone ki-information-4 fs-2"></i>
                            {{ __('accounting.showing') }} {{ $dailyBreakdown->count() }} {{ __('accounting.days_from') }} {{ $startDate ?? 'N/A' }} {{ __('accounting.to') }} {{ $endDate ?? 'N/A' }}
                        </small>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- HOURLY BREAKDOWN TABLE --}}
                {{-- ============================================================ --}}
                @if(isset($hourlyBreakdown) && $hourlyBreakdown->count() > 0)
                <div class="card mt-6">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ki-duotone ki-clock fs-2 me-2"></i>
                            {{ __('auth.hourly_order_distribution') }}
                        </h3>
                        <div class="card-toolbar">
                            <span class="badge badge-light-info fs-6">{{ $hourlyBreakdown->count() }} {{ __('auth.hours') }}</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('accounting.hour') }}</th>
                                        <th>{{ __('accounting.period') }}</th>
                                        <th class="text-center">{{ __('auth.orders') }}</th>
                                        <th class="text-end">{{ __('auth.hourly_total') }}</th>
                                        <th class="text-end">{{ __('auth.avg_order_value') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hourlyBreakdown as $hour)
                                    <tr>
                                        <td class="fw-bold">{{ $hour->hour_formatted ?? $hour->hour }}</td>
                                        <td>
                                            @php
                                                $periodColors = [
                                                    'Morning' => 'success',
                                                    'Afternoon' => 'primary',
                                                    'Evening' => 'warning',
                                                    'Night' => 'secondary'
                                                ];
                                            @endphp
                                            <span class="badge badge-light-{{ $periodColors[$hour->period ?? 'Night'] ?? 'secondary' }}">
                                                {{ $hour->period ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light-primary fs-7">{{ number_format($hour->order_count) }}</span>
                                        </td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($hour->hourly_total ?? 0, 2) }}</td>
                                        <td class="text-end">{{ currency_symbol() }}{{ number_format($hour->hourly_average ?? 0, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2">{{ __('accounting.total_average') }}</th>
                                        <th class="text-center fw-bold">{{ number_format($hourlyBreakdown->sum('order_count')) }}</th>
                                        <th class="text-end fw-bold">{{ currency_symbol() }}{{ number_format($hourlyBreakdown->sum('hourly_total'), 2) }}</th>
                                        <th class="text-end">{{ currency_symbol() }}{{ number_format($hourlyBreakdown->avg('hourly_average') ?? 0, 2) }}</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

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
{{-- JAVASCRIPT - Export Functions --}}
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

function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
        alert('{{ __('accounting.table_not_found') }}');
        return;
    }
    
    try {
        const rows = table.querySelectorAll('tr');
        let csv = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const rowData = Array.from(cols).map(col => col.innerText);
            csv.push(rowData.join(','));
        });
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename + '.csv';
        link.click();
        URL.revokeObjectURL(link.href);
    } catch (e) {
        alert('{{ __('accounting.export_error') }}: ' + e.message);
    }
}

function printReport() {
    window.print();
}
</script>
@endsection