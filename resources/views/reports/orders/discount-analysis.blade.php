{{-- resources/views/reports/orders/discount-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.discount_analysis_report'))

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
                                {{ __('auth.discount_analysis_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.discount_analysis_report') }}</li>
                            </ul>
                        </div>
                        @if($discountedOrders->count() > 0)
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('discountedOrdersTable', 'discount_analysis')">
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
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-filter-square fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <form method="GET" action="{{ route('reports.orders.discount-analysis') }}" id="filterForm">
                            <div class="row g-3">
                                {{-- Date Range --}}
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('accounting.start_date') }}</label>
                                    <input type="date" class="form-control" name="start_date" value="{{ $startDate }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">{{ __('accounting.end_date') }}</label>
                                    <input type="date" class="form-control" name="end_date" value="{{ $endDate }}">
                                </div>
                                
                                {{-- Location --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                    <select class="form-select" name="location_id"  data-control="select2">
                                        <option value="">{{ __('auth.all_locations') }}</option>
                                        @foreach($locations ?? [] as $location)
                                            <option value="{{ $location->id }}" {{ ($locationId ?? '') == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- Department --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('auth.department') }}</label>
                                    <select class="form-select" name="department_id"  data-control="select2">
                                        <option value="">{{ __('auth.all_departments') }}</option>
                                        @foreach($departments ?? [] as $department)
                                            <option value="{{ $department->id }}" {{ ($departmentId ?? '') == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- Employee --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('accounting.employee') }}</label>
                                    <select class="form-select" name="employee_id">
                                        <option value="">{{ __('auth.all_employees') }}</option>
                                        @foreach($employeesList ?? [] as $emp)
                                            <option value="{{ $emp->id }}" {{ ($employeeId ?? '') == $emp->id ? 'selected' : '' }}>
                                                {{ $emp->first_name }} {{ $emp->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.orders.discount-analysis') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </a>
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('accounting.showing') }} {{ $discountedOrders->count() }} {{ __('auth.discounted_orders') }}
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA --}}
                {{-- ============================================================ --}}
                @if($discountedOrders->count() == 0)
                <div class="card">
                    <div class="card-body text-center py-10">
                        <i class="ki-duotone ki-percentage fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_discounted_orders_found_for_period') }}</p>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- SUMMARY CARDS --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-primary">{{ number_format($discountSummary['total_discounted_orders']) }}</div>
                                <div class="text-muted">{{ __('auth.discounted_orders') }}</div>
                                <span class="badge badge-light-primary mt-2">{{ number_format($discountSummary['discount_rate'], 2) }}% {{ __('auth.discount_rate') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-success border border-success border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-success">{{ currency_symbol() }}{{ number_format($discountSummary['total_discount_amount'], 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_discount_given') }}</div>
                                <span class="badge badge-light-success mt-2">{{ currency_symbol() }}{{ number_format($discountSummary['average_discount_per_order'], 2) }} {{ __('auth.average') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-info border border-info border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-info">{{ number_format($discountEffectiveness->percentage_difference ?? 0, 1) }}%</div>
                                <div class="text-muted">{{ __('auth.discount_effectiveness') }}</div>
                                <span class="badge badge-light-info mt-2">{{ $discountEffectiveness->with_discount_count ?? 0 }} / {{ $discountEffectiveness->without_discount_count ?? 0 }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-warning">{{ $discountByEmployee->count() }}</div>
                                <div class="text-muted">{{ __('auth.employees_giving_discounts') }}</div>
                                <span class="badge badge-light-warning mt-2">
                                    @if($discountByEmployee->count() > 0)
                                        {{ $discountByEmployee->first()->first_name }}: {{ currency_symbol() }}{{ number_format($discountByEmployee->first()->total_discount_given, 2) }}
                                    @else
                                        {{ __('auth.none') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- DISCOUNT EFFECTIVENESS COMPARISON --}}
                {{-- ============================================================ --}}
                @if(isset($ordersWithDiscount) && isset($ordersWithoutDiscount))
                <div class="row g-6 mb-6">
                    <div class="col-md-4">
                        <div class="card bg-light-success border border-success border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-success">{{ number_format($ordersWithDiscount->order_count ?? 0) }}</div>
                                <div class="text-muted">{{ __('auth.orders_with_discount') }}</div>
                                <span class="badge badge-light-success mt-2">{{ currency_symbol() }}{{ number_format($ordersWithDiscount->average_order_value ?? 0, 2) }} {{ __('auth.avg_order_value') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-primary">{{ number_format($ordersWithoutDiscount->order_count ?? 0) }}</div>
                                <div class="text-muted">{{ __('auth.orders_without_discount') }}</div>
                                <span class="badge badge-light-primary mt-2">{{ currency_symbol() }}{{ number_format($ordersWithoutDiscount->average_order_value ?? 0, 2) }} {{ __('auth.avg_order_value') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light-{{ ($discountEffectiveness->percentage_difference ?? 0) >= 0 ? 'success' : 'danger' }} border border-{{ ($discountEffectiveness->percentage_difference ?? 0) >= 0 ? 'success' : 'danger' }} border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-{{ ($discountEffectiveness->percentage_difference ?? 0) >= 0 ? 'success' : 'danger' }}">
                                    {{ number_format($discountEffectiveness->percentage_difference ?? 0, 1) }}%
                                </div>
                                <div class="text-muted">{{ __('auth.value_difference') }}</div>
                                <span class="badge badge-light-{{ ($discountEffectiveness->percentage_difference ?? 0) >= 0 ? 'success' : 'danger' }} mt-2">
                                    {{ currency_symbol() }}{{ number_format($discountEffectiveness->difference ?? 0, 2) }} {{ __('auth.per_order') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- DISCOUNT CHARTS (2 columns) --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    {{-- Discount by Day --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discount_by_day_of_week') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="discountByDayChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Discount by Hour --}}
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title">
                                    <i class="ki-duotone ki-clock fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discount_by_hour') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="discountByHourChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- DISCOUNT RANGE DISTRIBUTION --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title">
                                    <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.discount_range_distribution') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="discountRangeChart" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title">
                                    <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.breakdown') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0 p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                                <th class="ps-4">{{ __('auth.discount_range') }}</th>
                                                <th>{{ __('auth.orders_count') }}</th>
                                                <th>{{ __('accounting.percentage') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($discountRanges as $range => $count)
                                            @php $percentage = $discountedOrders->count() > 0 ? ($count / $discountedOrders->count()) * 100 : 0; @endphp
                                            <tr>
                                                <td class="ps-4"><span class="badge badge-light-primary">{{ $range }}</span></td>
                                                <td>{{ number_format($count) }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress w-100 me-3" style="height: 6px;">
                                                            <div class="progress-bar bg-primary" style="width: {{ $percentage }}%;"></div>
                                                        </div>
                                                        <span class="fw-bold min-w-45px text-end">{{ number_format($percentage, 1) }}%</span>
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

                {{-- ============================================================ --}}
                {{-- DISCOUNT BY EMPLOYEE --}}
                {{-- ============================================================ --}}
                @if($discountByEmployee->count() > 0)
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-user-square fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.discount_by_employee') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">{{ $discountByEmployee->count() }} {{ __('accounting.employees') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0 p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4">{{ __('accounting.employee') }}</th>
                                        <th>{{ __('auth.discounted_orders') }}</th>
                                        <th>{{ __('auth.total_discount_given') }}</th>
                                        <th>{{ __('auth.average_discount') }}</th>
                                        <th>{{ __('auth.discount_per_order') }}</th>
                                        <th>{{ __('auth.discount_sales_percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($discountByEmployee as $employee)
                                    @php
                                        $percentage = $discountSummary['total_discount_amount'] > 0 ? 
                                            ($employee->total_discount_given / $discountSummary['total_discount_amount']) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-35px symbol-circle me-3">
                                                    <div class="symbol-label bg-light-primary">
                                                        <span class="text-primary fw-bold">{{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}</span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $employee->first_name }} {{ $employee->last_name }}</div>
                                                    <small class="text-muted">{{ $employee->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge badge-light-primary">{{ number_format($employee->order_count) }}</span></td>
                                        <td class="fw-bold text-success">{{ currency_symbol() }}{{ number_format($employee->total_discount_given, 2) }}</td>
                                        <td class="text-info">{{ currency_symbol() }}{{ number_format($employee->average_discount, 2) }}</td>
                                        <td class="text-warning">{{ currency_symbol() }}{{ number_format($employee->discount_per_order, 2) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar bg-primary" style="width: {{ min($percentage, 100) }}%;"></div>
                                                </div>
                                                <span class="fw-bold min-w-45px text-end">{{ number_format($percentage, 1) }}%</span>
                                            </div>
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
                {{-- DISCOUNTED ORDERS TABLE --}}
                {{-- ============================================================ --}}
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.discounted_orders_list') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">{{ $discountedOrders->count() }} {{ __('auth.orders') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0 p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="discountedOrdersTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4">{{ __('auth.order_number') }}</th>
                                        <th>{{ __('auth.customer') }}</th>
                                        <th class="text-end">{{ __('auth.order_total') }}</th>
                                        <th class="text-end">{{ __('auth.discount_amount') }}</th>
                                        <th>{{ __('auth.discount_percentage') }}</th>
                                        <th class="text-end">{{ __('auth.final_amount') }}</th>
                                        <th>{{ __('auth.processed_by') }}</th>
                                        <th>{{ __('auth.order_date') }}</th>
                                        <th>{{ __('accounting.status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($discountedOrdersPaginated ?? $discountedOrders as $order)
                                    @php
                                        $discountPercentage = $order->total > 0 ? ($order->discount_total / ($order->total + $order->discount_total)) * 100 : 0;
                                        $statusColors = ['completed' => 'success', 'pending' => 'warning', 'processing' => 'info', 'cancelled' => 'danger', 'refunded' => 'secondary'];
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('orders.show', $order->id) }}" class="text-primary fw-bold">
                                                #{{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $order->customer->name ?? 'Guest' }}</div>
                                            @if($order->customer)
                                                <small class="text-muted">{{ $order->customer->email ?? '' }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end text-gray-600">{{ currency_symbol() }}{{ number_format($order->total, 2) }}</td>
                                        <td class="text-end text-danger fw-bold">{{ currency_symbol() }}{{ number_format($order->discount_total, 2) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar bg-danger" style="width: {{ min($discountPercentage, 100) }}%;"></div>
                                                </div>
                                                <span class="fw-bold min-w-45px text-end">{{ number_format($discountPercentage, 1) }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-end text-success fw-bold">{{ currency_symbol() }}{{ number_format($order->total - $order->discount_total, 2) }}</td>
                                        <td>
                                            @if($order->orderCreater)
                                                {{ $order->orderCreater->name ?? ($order->orderCreater->first_name . ' ' . $order->orderCreater->last_name) }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($order->created_at)->format('M d, Y H:i') ?? '-' }}</td>
                                        <td><span class="badge badge-light-{{ $statusColors[$order->status] ?? 'secondary' }}">{{ ucfirst($order->status) }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pagination --}}
                    @if(isset($discountedOrdersPaginated) && $discountedOrdersPaginated->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $discountedOrdersPaginated,
                            'pageName' => 'page',
                            'perPageName' => 'per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif
                </div>

                {{-- ============================================================ --}}
                {{-- METADATA FOOTER --}}
                {{-- ============================================================ --}}
                <div class="mt-6 text-muted text-center fs-7">
                    <hr>
                    <p>
                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                        {{ __('auth.report_generated_on') }} {{ now()->format('F d, Y H:i:s') }} 
                        | {{ __('accounting.period') }}: {{ $startDate ?? 'N/A' }} {{ __('accounting.to') }} {{ $endDate ?? 'N/A' }}
                        @if(isset($locationId) && $locationId)
                            | {{ __('auth.location') }}: {{ $locations->where('id', $locationId)->first()->name ?? 'N/A' }}
                        @endif
                        @if(isset($departmentId) && $departmentId)
                            | {{ __('auth.department') }}: {{ $departments->where('id', $departmentId)->first()->name ?? 'N/A' }}
                        @endif
                        | {{ $discountedOrders->count() ?? 0 }} {{ __('auth.discounted_orders') }}
                        | {{ __('auth.total_discount_given') }}: {{ currency_symbol() }}{{ number_format($discountSummary['total_discount_amount'] ?? 0, 2) }}
                    </p>
                </div>
                
                @endif
                
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================================ --}}
@push('scripts')
@if($discountedOrders->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ─── Discount by Day Chart ──────────────────────────────────
    const dayData = @json($discountByDay);
    const dayLabels = dayData.map(item => item.day);
    const dayCounts = dayData.map(item => item.discount_count);
    const dayAmounts = dayData.map(item => parseFloat(item.total_amount));
    
    new ApexCharts(document.querySelector("#discountByDayChart"), {
        series: [
            { name: 'Discount Count', data: dayCounts, type: 'bar' },
            { name: 'Amount ($)', data: dayAmounts, type: 'line' }
        ],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
        stroke: { width: [0, 3], curve: 'smooth' },
        xaxis: { categories: dayLabels, labels: { rotate: -45 } },
        yaxis: [
            { title: { text: 'Count' } },
            { opposite: true, title: { text: 'Amount ($)' }, labels: { formatter: v => '$' + v.toFixed(2) } }
        ],
        colors: ['#3E97FF', '#50CD89'],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(val, { seriesIndex }) {
                    return seriesIndex === 0 ? val + ' orders' : '$' + val.toFixed(2);
                }
            }
        },
        legend: { position: 'top', horizontalAlign: 'center' }
    }).render();
    
    // ─── Discount by Hour Chart ──────────────────────────────────
    const hourData = @json($discountByHour);
    const hourLabels = hourData.map(item => item.hour_formatted);
    const hourCounts = hourData.map(item => item.discount_count);
    const hourAmounts = hourData.map(item => parseFloat(item.total_amount));
    
    new ApexCharts(document.querySelector("#discountByHourChart"), {
        series: [
            { name: 'Discount Count', data: hourCounts, type: 'bar' },
            { name: 'Amount ($)', data: hourAmounts, type: 'line' }
        ],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
        stroke: { width: [0, 3], curve: 'smooth' },
        xaxis: { categories: hourLabels, labels: { rotate: -45 } },
        yaxis: [
            { title: { text: 'Count' } },
            { opposite: true, title: { text: 'Amount ($)' }, labels: { formatter: v => '$' + v.toFixed(2) } }
        ],
        colors: ['#7239EA', '#FFC700'],
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(val, { seriesIndex }) {
                    return seriesIndex === 0 ? val + ' orders' : '$' + val.toFixed(2);
                }
            }
        },
        legend: { position: 'top', horizontalAlign: 'center' }
    }).render();
    
    // ─── Discount Range Chart ────────────────────────────────────
    const rangeData = @json($discountRanges);
    const rangeLabels = Object.keys(rangeData);
    const rangeCounts = Object.values(rangeData);
    
    new ApexCharts(document.querySelector("#discountRangeChart"), {
        series: rangeCounts,
        chart: { type: 'donut', height: 300, toolbar: { show: false } },
        labels: rangeLabels,
        colors: ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C', '#A1A5B7'],
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Orders',
                            formatter: function(w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        },
        legend: { position: 'bottom', horizontalAlign: 'center' },
        dataLabels: { enabled: true, formatter: function(val, { seriesIndex, w }) {
            const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
            return ((w.globals.series[seriesIndex] / total) * 100).toFixed(1) + '%';
        }},
        tooltip: { y: { formatter: function(val) { return val + ' orders'; } } }
    }).render();
});

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return alert('{{ __("accounting.table_not_found") }}');
    try {
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, XLSX.utils.table_to_sheet(table), 'Discount Analysis');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch(e) { alert('{{ __("accounting.export_error") }}: ' + e.message); }
}

document.getElementById('filterForm')?.addEventListener('submit', function(e) {
    const start = new Date(document.querySelector('[name="start_date"]').value);
    const end = new Date(document.querySelector('[name="end_date"]').value);
    if (start > end) {
        e.preventDefault();
        alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
    }
});
</script>
@endif
@endpush

@endsection