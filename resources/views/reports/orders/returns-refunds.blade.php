{{-- resources/views/reports/orders/returns-refunds.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.returns_refunds_report'))

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
                                {{ __('auth.returns_refunds_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('accounting.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.returns_refunds_report') }}</li>
                            </ul>
                        </div>
                        @if(($returnOrders && $returnOrders->count() > 0) || ($refundPayments && $refundPayments->count() > 0))
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-primary" onclick="exportTableToExcel('returnsTable', 'returns_refunds')">
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
                        <form method="GET" action="{{ route('reports.orders.returns-refunds') }}" id="filterForm">
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
                                    <select class="form-select" name="location_id" data-control="select2">
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
                                    <select class="form-select" name="department_id" data-control="select2">
                                        <option value="">{{ __('auth.all_departments') }}</option>
                                        @foreach($departments ?? [] as $department)
                                            <option value="{{ $department->id }}" {{ ($departmentId ?? '') == $department->id ? 'selected' : '' }}>
                                                {{ $department->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                {{-- Status --}}
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">{{ __('accounting.status') }}</label>
                                    <select class="form-select" name="status_filter">
                                        <option value="all">{{ __('auth.all_statuses') }}</option>
                                        <option value="pending" {{ ($statusFilter ?? '') == 'pending' ? 'selected' : '' }}>{{ __('auth.pending') }}</option>
                                        <option value="processing" {{ ($statusFilter ?? '') == 'processing' ? 'selected' : '' }}>{{ __('auth.processing') }}</option>
                                        <option value="completed" {{ ($statusFilter ?? '') == 'completed' ? 'selected' : '' }}>{{ __('auth.completed') }}</option>
                                        <option value="refunded" {{ ($statusFilter ?? '') == 'refunded' ? 'selected' : '' }}>{{ __('auth.refunded') }}</option>
                                        <option value="cancelled" {{ ($statusFilter ?? '') == 'cancelled' ? 'selected' : '' }}>{{ __('auth.cancelled') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="ki-duotone ki-filter fs-2 me-1"></i> {{ __('accounting.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.orders.returns-refunds') }}" class="btn btn-light">
                                        <i class="ki-duotone ki-cross fs-2 me-1"></i> {{ __('accounting.clear_filters') }}
                                    </a>
                                    <span class="text-muted ms-3 small">
                                        <i class="ki-duotone ki-information-4 fs-2"></i>
                                        {{ __('accounting.showing') }} {{ $returnOrders->count() ?? 0 }} {{ __('auth.returns') }}
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- NO DATA --}}
                {{-- ============================================================ --}}
                @if((!isset($returnOrders) || $returnOrders->count() == 0) && (!isset($refundPayments) || $refundPayments->count() == 0))
                <div class="card">
                    <div class="card-body text-center py-10">
                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4"></i>
                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                        <p class="text-muted fs-6">{{ __('auth.no_returns_found_for_period') }}</p>
                    </div>
                </div>
                @else

                {{-- ============================================================ --}}
                {{-- SUMMARY CARDS --}}
                {{-- ============================================================ --}}
                <div class="row g-6 mb-6">
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-danger border border-danger border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-danger">{{ number_format($metrics->total_return_orders) }}</div>
                                <div class="text-muted">{{ __('auth.total_returns') }}</div>
                                <span class="badge badge-light-danger mt-2">{{ number_format($metrics->return_rate, 2) }}% {{ __('auth.return_rate') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-warning border border-warning border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-warning">{{ currency_symbol() }}{{ number_format($metrics->total_refund_amount, 2) }}</div>
                                <div class="text-muted">{{ __('auth.total_refunded_amount') }}</div>
                                <span class="badge badge-light-warning mt-2">{{ number_format($metrics->refund_rate, 2) }}% {{ __('auth.refund_rate') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-info border border-info border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-info">{{ currency_symbol() }}{{ number_format($metrics->average_return_value, 2) }}</div>
                                <div class="text-muted">{{ __('auth.average_return_value') }}</div>
                                <span class="badge badge-light-info mt-2">{{ $metrics->total_return_orders }} {{ __('auth.returns') }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card bg-light-primary border border-primary border-dashed h-100">
                            <div class="card-body text-center">
                                <div class="fs-1 fw-bold text-primary">{{ number_format($topReturnedProducts->sum('return_quantity') ?? 0) }}</div>
                                <div class="text-muted">{{ __('auth.items_returned') }}</div>
                                <span class="badge badge-light-primary mt-2">{{ $topReturnedProducts->count() ?? 0 }} {{ __('auth.unique_products') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- RETURN REASONS ANALYSIS --}}
                {{-- ============================================================ --}}
                @if(isset($returnReasonsCollection) && $returnReasonsCollection->count() > 0)
                <div class="row g-6 mb-6">
                    <div class="col-lg-8">
                        <div class="card h-100">
                            <div class="card-header border-0">
                                <div class="card-title">
                                    <i class="ki-duotone ki-chart-pie-3 fs-2 me-2 text-primary"></i>
                                    <h3 class="fw-bold m-0">{{ __('auth.return_reasons_analysis') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="returnReasonsChart" style="height: 350px;"></div>
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
                                                <th class="ps-4">{{ __('auth.reason') }}</th>
                                                <th>{{ __('auth.count') }}</th>
                                                <th class="text-end">{{ __('auth.amount') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($returnReasonsCollection as $reason)
                                            @php
                                                $colors = [
                                                    'Damaged' => 'danger',
                                                    'Wrong Item' => 'warning',
                                                    'Defective' => 'info',
                                                    'Size Issue' => 'primary',
                                                    'Color Issue' => 'success',
                                                    'Not as described' => 'secondary',
                                                    'Other' => 'secondary'
                                                ];
                                                $color = $colors[$reason->reason] ?? 'secondary';
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="badge badge-light-{{ $color }}">
                                                        {{ Str::limit($reason->reason, 20) }}
                                                    </span>
                                                </td>
                                                <td>{{ number_format($reason->count) }}</td>
                                                <td class="text-end text-danger fw-bold">{{ currency_symbol() }}{{ number_format($reason->total_amount, 2) }}</td>
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

                {{-- ============================================================ --}}
                {{-- MONTHLY RETURN TRENDS --}}
                {{-- ============================================================ --}}
                @if(isset($monthlyReturnTrends) && $monthlyReturnTrends->count() > 0)
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-chart-line fs-2 me-2 text-info"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.monthly_return_trends') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div id="monthlyTrendsChart" style="height: 350px;"></div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- RETURNS BY PAYMENT METHOD --}}
                {{-- ============================================================ --}}
                @if(isset($returnsByPaymentMethod) && $returnsByPaymentMethod->count() > 0)
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-credit-card fs-2 me-2 text-warning"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.returns_by_payment_method') }}</h3>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light-warning">
                                        <th class="ps-4">{{ __('accounting.payment_method') }}</th>
                                        <th>{{ __('auth.return_count') }}</th>
                                        <th class="text-end">{{ __('auth.return_value') }}</th>
                                        <th>{{ __('accounting.percentage') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalReturnValue = $returnsByPaymentMethod->sum('return_value'); @endphp
                                    @foreach($returnsByPaymentMethod as $method)
                                    @php $percentage = $totalReturnValue > 0 ? ($method->return_value / $totalReturnValue) * 100 : 0; @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <i class="ki-duotone ki-credit-card fs-2 text-warning me-2"></i>
                                            {{ $method->payment_method }}
                                        </td>
                                        <td><span class="badge badge-light-primary">{{ number_format($method->return_count) }}</span></td>
                                        <td class="text-end text-danger fw-bold">{{ currency_symbol() }}{{ number_format($method->return_value, 2) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar bg-warning" style="width: {{ $percentage }}%;"></div>
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
                {{-- TOP RETURNED PRODUCTS --}}
                {{-- ============================================================ --}}
                @if(isset($topReturnedProducts) && $topReturnedProducts->count() > 0)
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-chart-bar fs-2 me-2 text-danger"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.top_returned_products') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-danger fs-7">{{ $topReturnedProducts->count() }} {{ __('auth.products') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0 p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="returnsTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light-danger">
                                        <th class="ps-4" style="width: 8%;">{{ __('accounting.rank') }}</th>
                                        <th style="width: 25%;">{{ __('auth.product') }}</th>
                                        <th style="width: 15%;">{{ __('accounting.sku') }}</th>
                                        <th style="width: 12%;" class="text-center">{{ __('auth.quantity_returned') }}</th>
                                        <th style="width: 15%;" class="text-end">{{ __('auth.return_value') }}</th>
                                        <th style="width: 12%;" class="text-center">{{ __('auth.return_count') }}</th>
                                        <th style="width: 13%;" class="text-end">{{ __('auth.average_return') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topReturnedProducts as $index => $product)
                                    @php $avgReturn = $product->return_count > 0 ? $product->return_value / $product->return_count : 0; @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold">{{ $index + 1 }}</span>
                                            @if($index < 3)
                                            <span class="badge badge-light-{{ $index == 0 ? 'danger' : ($index == 1 ? 'warning' : 'info') }}">
                                                {{ $index == 0 ? '🥇' : ($index == 1 ? '🥈' : '🥉') }}
                                            </span>
                                            @endif
                                        </td>
                                        <td class="fw-bold text-gray-800">{{ Str::limit($product->name, 30) }}</td>
                                        <td><span class="badge badge-light-primary">{{ $product->sku }}</span></td>
                                        <td class="text-center"><span class="fw-bold text-danger">{{ number_format($product->return_quantity) }}</span></td>
                                        <td class="text-end text-warning fw-bold">{{ currency_symbol() }}{{ number_format($product->return_value, 2) }}</td>
                                        <td class="text-center"><span class="badge badge-light-info">{{ number_format($product->return_count) }}</span></td>
                                        <td class="text-end text-gray-600">{{ currency_symbol() }}{{ number_format($avgReturn, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- RETURN ORDERS TABLE --}}
                {{-- ============================================================ --}}
                @if(isset($returnOrders) && $returnOrders->count() > 0)
                <div class="card mb-6">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.return_orders') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">{{ $returnOrders->count() }} {{ __('auth.returns') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0 p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4">{{ __('auth.order_number') }}</th>
                                        <th>{{ __('auth.customer') }}</th>
                                        <th>{{ __('accounting.status') }}</th>
                                        <th class="text-end">{{ __('auth.return_amount') }}</th>
                                        <th>{{ __('auth.return_reason') }}</th>
                                        <th>{{ __('auth.created_date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($returnOrdersPaginated ?? $returnOrders as $order)
                                    @php
                                        $statusColors = ['pending' => 'warning', 'refunded' => 'success', 'rejected' => 'danger', 'processing' => 'info', 'completed' => 'primary', 'cancelled' => 'secondary'];
                                        $returnReason = $order->notes ? (strpos($order->notes, ':') !== false ? trim(substr($order->notes, 0, strpos($order->notes, ':'))) : $order->notes) : 'Other';
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('orders.show', $order->id) }}" class="text-primary fw-bold">
                                                #{{ $order->order_number }}
                                            </a>
                                        </td>
                                        <td>
                                            @if($order->customer)
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-35px symbol-circle me-3">
                                                    <div class="symbol-label bg-light-primary">
                                                        <span class="text-primary fw-bold">{{ substr($order->customer->name ?? 'U', 0, 1) }}</span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold">{{ $order->customer->name ?? 'N/A' }}</div>
                                                    <small class="text-muted">{{ $order->customer->email ?? '' }}</small>
                                                </div>
                                            </div>
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td><span class="badge badge-light-{{ $statusColors[$order->status] ?? 'secondary' }}">{{ ucfirst($order->status) }}</span></td>
                                        <td class="text-end fw-bold text-danger">{{ currency_symbol() }}{{ number_format($order->total, 2) }}</td>
                                        <td><span class="badge badge-light-warning">{{ Str::limit($returnReason, 25) }}</span></td>
                                        <td>{{ optional($order->created_at)->format('M d, Y H:i') ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pagination for Return Orders --}}
                    @if(isset($returnOrdersPaginated) && $returnOrdersPaginated->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $returnOrdersPaginated,
                            'pageName' => 'returns_page',
                            'perPageName' => 'per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif
                </div>
                @endif

                {{-- ============================================================ --}}
                {{-- REFUND PAYMENTS TABLE --}}
                {{-- ============================================================ --}}
                @if(isset($refundPayments) && $refundPayments->count() > 0)
                <div class="card">
                    <div class="card-header border-0">
                        <div class="card-title">
                            <i class="ki-duotone ki-money fs-2 me-2 text-primary"></i>
                            <h3 class="fw-bold m-0">{{ __('auth.refund_payments') }}</h3>
                        </div>
                        <div class="card-toolbar">
                            <span class="badge badge-light-primary fs-7">{{ $refundPayments->count() }} {{ __('auth.refunds') }}</span>
                        </div>
                    </div>
                    <div class="card-body pt-0 p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 bg-light">
                                        <th class="ps-4">{{ __('auth.order_number') }}</th>
                                        <th>{{ __('auth.payment_method') }}</th>
                                        <th class="text-end">{{ __('auth.refund_amount') }}</th>
                                        <th class="text-end">{{ __('auth.order_total') }}</th>
                                        <th>{{ __('auth.refund_percentage') }}</th>
                                        <th>{{ __('auth.processed_date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($refundPaymentsPaginated ?? $refundPayments as $payment)
                                    @php
                                        $refundPercentage = $payment->order_total > 0 ? ($payment->amount / $payment->order_total) * 100 : 0;
                                        $methodColors = ['credit_card' => 'success', 'cash' => 'primary', 'bank_transfer' => 'info', 'check' => 'warning', 'digital_wallet' => 'danger'];
                                        $color = $methodColors[$payment->payment_method_type] ?? 'secondary';
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <a href="{{ route('orders.show', $payment->order_id) }}" class="text-primary fw-bold">
                                                #{{ $payment->order_number }}
                                            </a>
                                        </td>
                                        <td><span class="badge badge-light-{{ $color }}">{{ $payment->payment_method }}</span></td>
                                        <td class="text-end fw-bold text-danger">{{ currency_symbol() }}{{ number_format($payment->amount, 2) }}</td>
                                        <td class="text-end text-gray-600">{{ currency_symbol() }}{{ number_format($payment->order_total, 2) }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-3" style="height: 6px;">
                                                    <div class="progress-bar bg-warning" style="width: {{ min($refundPercentage, 100) }}%;"></div>
                                                </div>
                                                <span class="fw-bold min-w-45px text-end">{{ number_format($refundPercentage, 1) }}%</span>
                                            </div>
                                        </td>
                                        <td>{{ optional($payment->processed_at)->format('M d, Y H:i') ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pagination for Refund Payments --}}
                    @if(isset($refundPaymentsPaginated) && $refundPaymentsPaginated->hasPages())
                    <div class="card-footer">
                        @include('partials.pagination', [
                            'paginator' => $refundPaymentsPaginated,
                            'pageName' => 'refunds_page',
                            'perPageName' => 'per_page',
                            'showPerPage' => true
                        ])
                    </div>
                    @endif
                </div>
                @endif

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
                        | {{ $metrics->total_return_orders ?? 0 }} {{ __('auth.returns') }}
                        | {{ __('auth.total_refunded_amount') }}: {{ currency_symbol() }}{{ number_format($metrics->total_refund_amount ?? 0, 2) }}
                        | {{ __('auth.return_rate') }}: {{ number_format($metrics->return_rate ?? 0, 2) }}%
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
@if(isset($returnReasonsCollection) && $returnReasonsCollection->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Return Reasons Chart
    const reasonsData = @json($returnReasonsCollection);
    const labels = reasonsData.map(r => r.reason ? r.reason.substring(0, 20) : 'Unknown');
    const counts = reasonsData.map(r => r.count);
    const colors = ['#F64E60', '#FFA800', '#7239EA', '#3E97FF', '#50CD89', '#A1A5B7'];
    
    new ApexCharts(document.querySelector("#returnReasonsChart"), {
        series: counts,
        chart: { type: 'donut', height: 350, toolbar: { show: false } },
        labels: labels,
        colors: colors,
        plotOptions: {
            pie: {
                donut: {
                    size: '65%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Returns',
                            formatter: function(w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        },
        legend: { position: 'bottom', horizontalAlign: 'center' },
        dataLabels: { enabled: true, formatter: function(val) { return val.toFixed(1) + '%'; } },
        tooltip: { y: { formatter: function(val) { return val + ' returns'; } } }
    }).render();
    
    // Monthly Trends Chart
    @if(isset($monthlyReturnTrends) && $monthlyReturnTrends->count() > 0)
    const trendsData = @json($monthlyReturnTrends);
    const months = trendsData.map(item => item.month);
    const values = trendsData.map(item => parseFloat(item.return_value));
    const countsMonthly = trendsData.map(item => item.return_count);
    
    new ApexCharts(document.querySelector("#monthlyTrendsChart"), {
        series: [
            { name: 'Return Value', type: 'line', data: values },
            { name: 'Return Count', type: 'column', data: countsMonthly }
        ],
        chart: { height: 350, type: 'line', stacked: false, toolbar: { show: true } },
        colors: ['#F64E60', '#3E97FF'],
        stroke: { width: [3, 1], curve: 'smooth' },
        xaxis: { categories: months, labels: { rotate: -45 } },
        yaxis: [
            { title: { text: 'Return Value ($)' }, labels: { formatter: v => '$' + v.toFixed(2) } },
            { opposite: true, title: { text: 'Return Count' } }
        ],
        tooltip: { shared: true, intersect: false }
    }).render();
    @endif
});
</script>
@endif

<script>
function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return alert('{{ __("accounting.table_not_found") }}');
    try {
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, XLSX.utils.table_to_sheet(table), 'Returns');
        XLSX.writeFile(wb, filename + '.xlsx');
    } catch(e) { alert('{{ __("accounting.export_error") }}: ' + e.message); }
}

function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return alert('{{ __("accounting.table_not_found") }}');
    try {
        const rows = table.querySelectorAll('tr');
        let csv = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            csv.push(Array.from(cols).map(c => c.innerText.trim()).join(','));
        });
        const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename + '.csv';
        link.click();
        URL.revokeObjectURL(link.href);
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
@endpush

@endsection