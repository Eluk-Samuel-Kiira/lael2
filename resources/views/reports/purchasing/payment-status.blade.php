{{-- resources/views/reports/purchasing/payment-status.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.payment_status_report'))

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
                                {{ __('pagination.payment_status_report') }}
                            </h1>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                                        {{ __('pagination.dashboard') }}
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchasing_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('pagination.payment_status_report') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($items->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('pagination.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}">
                                            <i class="ki-duotone ki-file-pdf fs-2 me-2 text-danger"></i>
                                            {{ __('pagination.export_to_pdf') }}
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
                                    <h3 class="fw-bold m-0">{{ __('pagination.filter_by') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form method="GET" action="{{ route('reports.purchasing.payment-status') }}" id="filterForm">
                                    <div class="row g-4 mb-4">
                                        {{-- Date Range --}}
                                        <div class="col-md-3">
                                            <label class="form-label required fw-semibold">{{ __('pagination.start_date') }}</label>
                                            <input type="date" class="form-control" name="start_date" 
                                                value="{{ $startDate }}" required>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <label class="form-label required fw-semibold">{{ __('pagination.end_date') }}</label>
                                            <input type="date" class="form-control" name="end_date" 
                                                value="{{ $endDate }}" required>
                                        </div>
                                        
                                        {{-- Payment Status --}}
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('passwords.payment_status') }}</label>
                                            <select class="form-select" name="payment_status">
                                                <option value="all">{{ __('passwords.all_statuses') }}</option>
                                                <option value="pending" {{ $paymentStatus == 'pending' ? 'selected' : '' }}>{{ __('passwords.pending') }}</option>
                                                <option value="partial" {{ $paymentStatus == 'partial' ? 'selected' : '' }}>{{ __('passwords.partial') }}</option>
                                                <option value="paid" {{ $paymentStatus == 'paid' ? 'selected' : '' }}>{{ __('passwords.paid') }}</option>
                                                <option value="overdue" {{ $paymentStatus == 'overdue' ? 'selected' : '' }}>{{ __('passwords.overdue') }}</option>
                                                <option value="cancelled" {{ $paymentStatus == 'cancelled' ? 'selected' : '' }}>{{ __('passwords.cancelled') }}</option>
                                            </select>
                                        </div>
                                        
                                        {{-- Supplier --}}
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                            <select class="form-select" name="supplier_id">
                                                <option value="">{{ __('passwords.all_suppliers') }}</option>
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" 
                                                            {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('pagination.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.purchasing.payment-status') }}" class="btn btn-light btn-active-light-primary">
                                                    <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                                    {{ __('pagination.clear_filters') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Summary Cards --}}
                <div class="row mb-6">
                    @php
                        $summaryCards = [
                            [
                                'title' => __('passwords.total_amount_due'),
                                'value' => '$' . number_format($summary['total_amount_due'], 2),
                                'color' => 'warning',
                                'icon' => 'ki-dollar',
                                'description' => __('passwords.amount_outstanding')
                            ],
                            [
                                'title' => __('passwords.total_amount_paid'),
                                'value' => '$' . number_format($summary['total_amount_paid'], 2),
                                'color' => 'success',
                                'icon' => 'ki-check',
                                'description' => __('passwords.amount_settled')
                            ],
                            [
                                'title' => __('passwords.overdue_amount'),
                                'value' => '$' . number_format($summary['overdue_amount'], 2),
                                'color' => 'danger',
                                'icon' => 'ki-exclamation',
                                'description' => __('passwords.overdue_payments') . ': ' . $summary['overdue_count']
                            ],
                            [
                                'title' => __('passwords.total_items'),
                                'value' => number_format($items->total()),
                                'color' => 'primary',
                                'icon' => 'ki-element-plus',
                                'description' => __('passwords.items_analyzed')
                            ]
                        ];
                    @endphp
                    
                    @foreach($summaryCards as $card)
                    <div class="col-md-6 col-lg-3 mb-4">
                        <div class="card card-flush bg-light-{{ $card['color'] }} border border-{{ $card['color'] }} border-dashed h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone {{ $card['icon'] }} fs-2tx text-{{ $card['color'] }} me-3">
                                        @for($i = 1; $i <= 2; $i++)
                                        <span class="path{{ $i }}"></span>
                                        @endfor
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ $card['title'] }}</div>
                                        <div class="fs-2 fw-bold text-{{ $card['color'] }}">{{ $card['value'] }}</div>
                                        @if($card['description'])
                                        <div class="text-muted fs-7">{{ $card['description'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Payment Status Distribution Chart --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-chart-pie fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.payment_status_distribution') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.period') }}: {{ $startDate }} {{ __('pagination.to') }} {{ $endDate }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="paymentStatusChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Overdue Payments Section --}}
                @if($overduePayments->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-exclamation fs-2 me-2 text-danger">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.overdue_payments') }}</h3>
                                    </div>
                                    <span class="badge badge-light-danger fs-7">
                                        {{ $overduePayments->count() }} {{ __('passwords.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">{{ __('passwords.po_number') }}</th>
                                                <th>{{ __('passwords.supplier') }}</th>
                                                <th>{{ __('passwords.product') }}</th>
                                                <th>{{ __('passwords.payment_date') }}</th>
                                                <th>{{ __('passwords.days_overdue') }}</th>
                                                <th>{{ __('passwords.amount_due') }}</th>
                                                <th>{{ __('passwords.payment_method') }}</th>
                                                <th class="text-end pe-4">{{ __('passwords.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($overduePayments as $item)
                                            @php
                                                $po = $item->purchaseOrder;
                                                $daysOverdue = \Carbon\Carbon::parse($item->payment_date)->diffInDays(now());
                                                
                                                // Payment status color
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'partial' => 'info',
                                                    'paid' => 'success',
                                                    'overdue' => 'danger',
                                                    'cancelled' => 'dark'
                                                ];
                                                $statusColor = $statusColors[$item->payment_status] ?? 'dark';
                                            @endphp
                                            <tr class="{{ $daysOverdue > 30 ? 'table-danger' : '' }}">
                                                <td class="ps-4">
                                                    <div class="fw-bold">{{ $po->po_number ?? 'N/A' }}</div>
                                                    <small class="text-muted">
                                                        {{ $item->created_at->format('M d, Y') }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $po && $po->supplier ? $po->supplier->name : 'N/A' }}</div>
                                                    <small class="text-muted">
                                                        {{ $po && $po->supplier && $po->supplier->contact_person ? $po->supplier->contact_person : 'N/A' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->product_name ?? ($item->productVariant->name ?? 'N/A') }}</div>
                                                    <small class="text-muted">
                                                        {{ $item->sku ?? ($item->productVariant->sku ?? 'N/A') }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-danger">
                                                        {{ $item->payment_date ? \Carbon\Carbon::parse($item->payment_date)->format('M d, Y') : 'N/A' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-danger">
                                                        {{ $daysOverdue }} {{ __('passwords.days') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        ${{ number_format($item->total_cost, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($item->paymentMethod)
                                                        <span class="badge badge-light-info">
                                                            {{ $item->paymentMethod->name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">{{ __('passwords.not_specified') }}</span>
                                                    @endif
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="badge badge-{{ $statusColor }}">
                                                        {{ __("passwords.{$item->payment_status}") }}
                                                    </span>
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

                {{-- Payment Items Table --}}
                @if($items->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.payment_transactions') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $items->count() }} {{ __('pagination.of') }} {{ $items->total() }} {{ __('passwords.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="paymentTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('passwords.po_number') }}</th>
                                                <th>{{ __('passwords.supplier') }}</th>
                                                <th>{{ __('passwords.product') }}</th>
                                                <th>{{ __('passwords.amount') }}</th>
                                                <th>{{ __('passwords.payment_date') }}</th>
                                                <th>{{ __('passwords.payment_method') }}</th>
                                                <th>{{ __('passwords.due_date') }}</th>
                                                <th>{{ __('passwords.days_remaining') }}</th>
                                                <th class="text-end pe-4">{{ __('passwords.payment_status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($items as $index => $item)
                                            @php
                                                $po = $item->purchaseOrder;
                                                $paymentDate = $item->payment_date ? \Carbon\Carbon::parse($item->payment_date) : null;
                                                $dueDate = $item->due_date ? \Carbon\Carbon::parse($item->due_date) : null;
                                                
                                                // Calculate days remaining/overdue
                                                $daysInfo = '';
                                                $daysColor = 'success';
                                                
                                                if ($item->payment_status == 'paid') {
                                                    $daysInfo = __('passwords.paid');
                                                    $daysColor = 'success';
                                                } elseif ($dueDate) {
                                                    $now = now();
                                                    if ($dueDate->isPast()) {
                                                        $daysOverdue = $dueDate->diffInDays($now);
                                                        $daysInfo = $daysOverdue . ' ' . __('passwords.days_overdue');
                                                        $daysColor = 'danger';
                                                    } else {
                                                        $daysRemaining = $now->diffInDays($dueDate);
                                                        $daysInfo = $daysRemaining . ' ' . __('passwords.days_left');
                                                        $daysColor = $daysRemaining <= 7 ? 'warning' : 'success';
                                                    }
                                                }
                                                
                                                // Payment status color
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'partial' => 'info',
                                                    'paid' => 'success',
                                                    'overdue' => 'danger',
                                                    'cancelled' => 'dark'
                                                ];
                                                $statusColor = $statusColors[$item->payment_status] ?? 'dark';
                                                
                                                // Row class for overdue items
                                                $rowClass = '';
                                                if ($item->payment_status == 'overdue' || ($dueDate && $dueDate->isPast() && $item->payment_status != 'paid')) {
                                                    $rowClass = 'table-danger';
                                                } elseif ($item->payment_status == 'partial') {
                                                    $rowClass = 'table-warning';
                                                }
                                            @endphp
                                            <tr class="{{ $rowClass }}">
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $loop->iteration }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-40px me-3">
                                                            <div class="symbol-label bg-light-primary text-primary fw-bold">
                                                                PO
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $po->po_number ?? 'N/A' }}</div>
                                                            <small class="text-muted">
                                                                {{ $item->created_at->format('M d, Y') }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $po && $po->supplier ? $po->supplier->name : 'N/A' }}</div>
                                                    <small class="text-muted">
                                                        {{ $po && $po->supplier && $po->supplier->contact_person ? $po->supplier->contact_person : 'N/A' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $item->product_name ?? ($item->productVariant->name ?? 'N/A') }}</div>
                                                    <small class="text-muted">
                                                        {{ $item->sku ?? ($item->productVariant->sku ?? 'N/A') }}
                                                        <br>
                                                        Qty: {{ $item->quantity }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        ${{ number_format($item->total_cost, 2) }}
                                                    </span>
                                                    @if($item->payment_status == 'partial')
                                                    <div class="text-muted fs-8">
                                                        {{ __('passwords.paid') }}: ${{ number_format($item->amount_paid ?? 0, 2) }}
                                                    </div>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($paymentDate)
                                                        <span class="fw-bold">
                                                            {{ $paymentDate->format('M d, Y') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">{{ __('passwords.not_paid') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item->paymentMethod)
                                                        <span class="badge badge-light-info">
                                                            {{ $item->paymentMethod->name }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">{{ __('passwords.not_specified') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($dueDate)
                                                        <span class="fw-bold {{ $dueDate->isPast() && $item->payment_status != 'paid' ? 'text-danger' : 'text-primary' }}">
                                                            {{ $dueDate->format('M d, Y') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">{{ __('passwords.not_set') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $daysColor }}">
                                                        {{ $daysInfo }}
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="badge badge-{{ $statusColor }}">
                                                        {{ __("passwords.{$item->payment_status}") }}
                                                    </span>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="card-footer d-flex justify-content-between align-items-center">
                                    <div class="text-muted">
                                        {{ __('pagination.showing') }} {{ $items->firstItem() }} - {{ $items->lastItem() }} {{ __('pagination.of') }} {{ $items->total() }} {{ __('passwords.items') }}
                                    </div>
                                    <div>
                                        {{ $items->appends(request()->query())->links() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                    {{-- No Data Message --}}
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="text-center py-10">
                                        <i class="ki-duotone ki-dollar fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_payments_found') }}</h4>
                                        <p class="text-muted fs-6">{{ __('passwords.no_payment_transactions_match_filters') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'payment_status', 'supplier_id']))
                                        <a href="{{ route('reports.purchasing.payment-status') }}" class="btn btn-light-primary">
                                            <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                            {{ __('passwords.clear_filters_view_all') }}
                                        </a>
                                        @endif
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
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.querySelector('[name="start_date"]').value);
            const endDate = new Date(document.querySelector('[name="end_date"]').value);
            
            if (startDate > endDate) {
                e.preventDefault();
                alert('{{ __("pagination.start_date_cannot_be_after_end_date") }}');
                return false;
            }
        });

        // Payment Status Chart
        @php
            $statusData = [];
            $statusLabels = [];
            $statusColors = [];
            
            $statusConfig = [
                'pending' => ['label' => __('passwords.pending'), 'color' => '#FFC700'],
                'partial' => ['label' => __('passwords.partial'), 'color' => '#17a2b8'],
                'paid' => ['label' => __('passwords.paid'), 'color' => '#50CD89'],
                'overdue' => ['label' => __('passwords.overdue'), 'color' => '#F1416C'],
                'cancelled' => ['label' => __('passwords.cancelled'), 'color' => '#6c757d']
            ];
            
            foreach ($statusConfig as $status => $config) {
                $statusItem = $statusSummary->get($status);
                if ($statusItem || $status == 'overdue') {
                    $statusLabels[] = $config['label'];
                    $amount = $status == 'overdue' ? $summary['overdue_amount'] : ($statusItem ? $statusItem->total_amount : 0);
                    $statusData[] = $amount;
                    $statusColors[] = $config['color'];
                }
            }
        @endphp

        const paymentStatusChart = new ApexCharts(document.querySelector("#paymentStatusChart"), {
            series: @json($statusData),
            chart: {
                type: 'donut',
                height: 350
            },
            labels: @json($statusLabels),
            colors: @json($statusColors),
            legend: {
                position: 'bottom',
                formatter: function(seriesName, opts) {
                    const total = opts.w.config.series.reduce((a, b) => a + b, 0);
                    const percentage = total > 0 ? ((opts.w.config.series[opts.seriesIndex] / total) * 100).toFixed(1) : 0;
                    return seriesName + ': ' + percentage + '%';
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: '{{ __("passwords.total_amount") }}',
                                formatter: function(w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return '$' + total.toLocaleString('en-US', {minimumFractionDigits: 0});
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString('en-US', {minimumFractionDigits: 2});
                    }
                }
            },
            dataLabels: {
                formatter: function(val, opts) {
                    return opts.w.config.series[opts.seriesIndex].toLocaleString('en-US', {minimumFractionDigits: 0});
                }
            }
        });
        paymentStatusChart.render();
    });
</script>
@endpush
@endsection