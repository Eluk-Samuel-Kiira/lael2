{{-- resources/views/reports/purchasing/purchase-order-status.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.purchase_order_status'))

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
                                {{ __('pagination.purchase_order_status') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchase_order_status') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
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
                                <form method="GET" action="{{ route('reports.purchasing.purchase-order-status') }}" id="filterForm">
                                    <div class="row g-4 mb-4">
                                        {{-- Date Range --}}
                                        <div class="col-md-4">
                                            <label class="form-label required fw-semibold">{{ __('pagination.start_date') }}</label>
                                            <input type="date" class="form-control" name="start_date" 
                                                value="{{ $startDate }}" required>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label class="form-label required fw-semibold">{{ __('pagination.end_date') }}</label>
                                            <input type="date" class="form-control" name="end_date" 
                                                value="{{ $endDate }}" required>
                                        </div>
                                        
                                        {{-- Supplier --}}
                                        <div class="col-md-4">
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
                                                <a href="{{ route('reports.purchasing.purchase-order-status') }}" class="btn btn-light btn-active-light-primary">
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
                        $totalOrders = $statusDistribution->sum('count');
                        $totalValue = $statusDistribution->sum('total_value');
                        $pendingCount = $pendingOrders->total();
                        $overdueCount = $overdueOrders->total();
                        $completedCount = $statusDistribution->get('completed')?->count ?? 0;
                    @endphp
                    
                    @php
                        $summaryCards = [
                            [
                                'title' => __('passwords.total_orders'),
                                'value' => number_format($totalOrders),
                                'color' => 'primary',
                                'icon' => 'ki-clipboard',
                                'description' => __('passwords.value') . ': $' . number_format($totalValue, 2)
                            ],
                            [
                                'title' => __('passwords.pending_orders'),
                                'value' => number_format($pendingCount),
                                'color' => 'warning',
                                'icon' => 'ki-clock',
                                'description' => __('passwords.needs_attention')
                            ],
                            [
                                'title' => __('passwords.overdue_orders'),
                                'value' => number_format($overdueCount),
                                'color' => 'danger',
                                'icon' => 'ki-exclamation',
                                'description' => __('passwords.urgent')
                            ],
                            [
                                'title' => __('passwords.completed_orders'),
                                'value' => number_format($completedCount),
                                'color' => 'success',
                                'icon' => 'ki-check',
                                'description' => __('passwords.value') . ': $' . number_format($statusDistribution->get('completed')?->total_value ?? 0, 2)
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

                {{-- Status Distribution Chart --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.order_status_distribution') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.period') }}: {{ $startDate }} {{ __('pagination.to') }} {{ $endDate }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="statusDistributionChart" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pending Orders Section --}}
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-clock fs-2 me-2 text-warning">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.pending_orders') }}</h3>
                                    </div>
                                    <span class="badge badge-light-warning fs-7">
                                        {{ $pendingOrders->total() }} {{ __('passwords.orders') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                @if($pendingOrders->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="ps-4">{{ __('passwords.po_number') }}</th>
                                                    <th>{{ __('passwords.supplier') }}</th>
                                                    <th>{{ __('passwords.location') }}</th>
                                                    <th>{{ __('passwords.total') }}</th>
                                                    <th>{{ __('passwords.status') }}</th>
                                                    <th>{{ __('passwords.expected_delivery') }}</th>
                                                    <th>{{ __('passwords.created') }}</th>
                                                    <th class="text-end pe-4">{{ __('passwords.actions') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($pendingOrders as $order)
                                                @php
                                                    $statusColors = [
                                                        'draft' => 'dark',
                                                        'sent' => 'info',
                                                        'pending_approval' => 'warning',
                                                        'approved' => 'success',
                                                        'cancelled' => 'danger',
                                                        'completed' => 'primary'
                                                    ];
                                                    $color = $statusColors[$order->status] ?? 'dark';
                                                    $statusText = __("passwords.{$order->status}");
                                                @endphp
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="fw-bold">{{ $order->po_number }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold">{{ $order->supplier->name ?? __('pagination.n_a') }}</div>
                                                        <small class="text-muted">{{ $order->supplier->contact_person ?? __('pagination.no_contact') }}</small>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold">{{ $order->location->name ?? __('pagination.n_a') }}</div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-primary">
                                                            ${{ number_format($order->total, 2) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-{{ $color }}">
                                                            {{ $statusText }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @if($order->expected_delivery_date)
                                                            <span class="fw-semibold">
                                                                {{ \Carbon\Carbon::parse($order->expected_delivery_date)->format('M d, Y') }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted">{{ __('passwords.not_set') }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="text-muted">
                                                            {{ $order->created_at->format('M d, Y') }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <button class="btn btn-icon btn-light-primary btn-sm me-2 view-order-btn" 
                                                                data-order-id="{{ $order->id }}"
                                                                title="{{ __('passwords.view_details') }}">
                                                            <i class="ki-duotone ki-eye fs-2">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $pendingOrders->firstItem() }} - {{ $pendingOrders->lastItem() }} {{ __('pagination.of') }} {{ $pendingOrders->total() }} {{ __('passwords.orders') }}
                                        </div>
                                        <div>
                                            {{ $pendingOrders->appends(request()->query())->links() }}
                                        </div>
                                    </div>
                                @else
                                    <div class="card-body">
                                        <div class="text-center py-10">
                                            <i class="ki-duotone ki-check fs-4tx text-success mb-4">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_pending_orders') }}</h4>
                                            <p class="text-muted fs-6">{{ __('passwords.all_purchase_orders_processed') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Overdue Orders Section --}}
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
                                        <h3 class="fw-bold m-0">{{ __('passwords.overdue_orders') }}</h3>
                                    </div>
                                    <span class="badge badge-light-danger fs-7">
                                        {{ $overdueOrders->total() }} {{ __('passwords.orders') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                @if($overdueOrders->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="ps-4">{{ __('passwords.po_number') }}</th>
                                                    <th>{{ __('passwords.supplier') }}</th>
                                                    <th>{{ __('passwords.location') }}</th>
                                                    <th>{{ __('passwords.total') }}</th>
                                                    <th>{{ __('passwords.status') }}</th>
                                                    <th>{{ __('passwords.expected_delivery') }}</th>
                                                    <th>{{ __('passwords.days_overdue') }}</th>
                                                    <th class="text-end pe-4">{{ __('passwords.actions') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($overdueOrders as $order)
                                                @php
                                                    $daysOverdue = \Carbon\Carbon::parse($order->expected_delivery_date)->diffInDays(now());
                                                    $rowClass = $daysOverdue > 7 ? 'table-danger' : '';
                                                @endphp
                                                <tr class="{{ $rowClass }}">
                                                    <td class="ps-4">
                                                        <span class="fw-bold">{{ $order->po_number }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold">{{ $order->supplier->name ?? __('pagination.n_a') }}</div>
                                                        <small class="text-muted">{{ $order->supplier->contact_person ?? __('pagination.no_contact') }}</small>
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold">{{ $order->location->name ?? __('pagination.n_a') }}</div>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-primary">
                                                            ${{ number_format($order->total, 2) }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-danger">
                                                            {{ __("passwords.{$order->status}") }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-danger">
                                                            {{ \Carbon\Carbon::parse($order->expected_delivery_date)->format('M d, Y') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-danger">
                                                            {{ $daysOverdue }} {{ __('passwords.days') }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <button class="btn btn-icon btn-light-primary btn-sm me-2 view-order-btn" 
                                                                data-order-id="{{ $order->id }}"
                                                                title="{{ __('passwords.view_details') }}">
                                                            <i class="ki-duotone ki-eye fs-2">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </button>
                                                        <button class="btn btn-icon btn-light-warning btn-sm follow-up-btn" 
                                                                data-order-id="{{ $order->id }}"
                                                                title="{{ __('passwords.follow_up') }}">
                                                            <i class="ki-duotone ki-envelope fs-2">
                                                                <span class="path1"></span>
                                                                <span class="path2"></span>
                                                            </i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer d-flex justify-content-between align-items-center">
                                        <div class="text-muted">
                                            {{ __('pagination.showing') }} {{ $overdueOrders->firstItem() }} - {{ $overdueOrders->lastItem() }} {{ __('pagination.of') }} {{ $overdueOrders->total() }} {{ __('passwords.orders') }}
                                        </div>
                                        <div>
                                            {{ $overdueOrders->appends(request()->query())->links() }}
                                        </div>
                                    </div>
                                @else
                                    <div class="card-body">
                                        <div class="text-center py-10">
                                            <i class="ki-duotone ki-check fs-4tx text-success mb-4">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_overdue_orders') }}</h4>
                                            <p class="text-muted fs-6">{{ __('passwords.all_orders_delivered_on_time') }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Purchase Order Details Modal -->
<div class="modal fade" id="purchaseOrderModal" tabindex="-1" aria-labelledby="purchaseOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="purchaseOrderModalLabel">{{ __('passwords.purchase_order_details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="purchaseOrderDetails">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('passwords.loading') }}</span>
                        </div>
                        <p class="mt-3">{{ __('passwords.loading_purchase_order_details') }}</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('passwords.close') }}</button>
                <button type="button" class="btn btn-primary" onclick="printPurchaseOrder()">
                    <i class="ki-duotone ki-printer fs-2 me-2"></i>
                    {{ __('passwords.print') }}
                </button>
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

        // Status Distribution Chart
        @php
            $statusLabels = [
                'draft' => __('passwords.draft'),
                'sent' => __('passwords.sent'),
                'pending_approval' => __('passwords.pending_approval'),
                'approved' => __('passwords.approved'),
                'cancelled' => __('passwords.cancelled'),
                'completed' => __('passwords.completed')
            ];
            
            $chartLabels = [];
            $chartCounts = [];
            $chartValues = [];
            $chartColors = [];
            
            $statusConfig = [
                'draft' => '#6c757d',
                'sent' => '#17a2b8',
                'pending_approval' => '#ffc107',
                'approved' => '#28a745',
                'cancelled' => '#dc3545',
                'completed' => '#007bff'
            ];
            
            foreach ($statusConfig as $status => $color) {
                $chartLabels[] = $statusLabels[$status];
                $statusItem = $statusDistribution->get($status);
                $chartCounts[] = $statusItem ? $statusItem->count : 0;
                $chartValues[] = $statusItem ? $statusItem->total_value : 0;
                $chartColors[] = $color;
            }
        @endphp

        const statusDistributionChart = new ApexCharts(document.querySelector("#statusDistributionChart"), {
            series: [
                {
                    name: '{{ __("passwords.number_of_orders") }}',
                    type: 'column',
                    data: @json($chartCounts)
                },
                {
                    name: '{{ __("passwords.total_value_usd") }}',
                    type: 'line',
                    data: @json($chartValues)
                }
            ],
            chart: {
                height: 350,
                type: 'line',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                }
            },
            stroke: {
                width: [0, 4]
            },
            dataLabels: {
                enabled: true,
                enabledOnSeries: [1]
            },
            labels: @json($chartLabels),
            colors: @json($chartColors),
            xaxis: {
                type: 'category'
            },
            yaxis: [
                {
                    title: {
                        text: '{{ __("passwords.number_of_orders") }}'
                    },
                    labels: {
                        formatter: function(val) {
                            return val.toFixed(0)
                        }
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: '{{ __("passwords.total_value_usd") }}'
                    },
                    labels: {
                        formatter: function(val) {
                            return '$' + val.toLocaleString('en-US', {minimumFractionDigits: 0})
                        }
                    }
                }
            ],
            tooltip: {
                y: [
                    {
                        formatter: function(val) {
                            return val + ' {{ __("passwords.orders") }}'
                        }
                    },
                    {
                        formatter: function(val) {
                            return '$' + val.toLocaleString('en-US', {minimumFractionDigits: 2})
                        }
                    }
                ]
            },
            legend: {
                position: 'top'
            }
        });
        statusDistributionChart.render();

        // View Purchase Order Details
        const viewOrderButtons = document.querySelectorAll('.view-order-btn');
        const purchaseOrderModal = new bootstrap.Modal(document.getElementById('purchaseOrderModal'));
        
        viewOrderButtons.forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                loadPurchaseOrderDetails(orderId);
            });
        });

        // Follow Up buttons
        const followUpButtons = document.querySelectorAll('.follow-up-btn');
        followUpButtons.forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                followUpOrder(orderId);
            });
        });
    });

    function loadPurchaseOrderDetails(orderId) {
        const detailsContainer = document.getElementById('purchaseOrderDetails');
        
        detailsContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">{{ __("passwords.loading") }}</span>
                </div>
                <p class="mt-3">{{ __("passwords.loading_purchase_order_details") }}</p>
            </div>
        `;
        
        fetch(`/api/purchase-orders/${orderId}/details`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    const order = data.data;
                    renderPurchaseOrderDetails(order);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('purchaseOrderModal'));
                    modal.show();
                } else {
                    detailsContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="ki-duotone ki-cross-circle fs-2 me-2"></i>
                            {{ __("passwords.failed_to_load_details") }}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                detailsContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="ki-duotone ki-cross-circle fs-2 me-2"></i>
                        {{ __("passwords.error_loading_details") }}
                    </div>
                `;
            });
    }

    function renderPurchaseOrderDetails(order) {
        const detailsContainer = document.getElementById('purchaseOrderDetails');
        
        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2
            }).format(amount);
        };
        
        const statusColors = {
            'draft': 'dark',
            'sent': 'info',
            'pending_approval': 'warning',
            'approved': 'success',
            'cancelled': 'danger',
            'completed': 'primary'
        };
        
        // Get status text from translations object
        const statusTranslations = {
            'draft': '{{ __("passwords.draft") }}',
            'sent': '{{ __("passwords.sent") }}',
            'pending_approval': '{{ __("passwords.pending_approval") }}',
            'approved': '{{ __("passwords.approved") }}',
            'cancelled': '{{ __("passwords.cancelled") }}',
            'completed': '{{ __("passwords.completed") }}'
        };
        
        const statusColor = statusColors[order.status] || 'dark';
        const statusText = statusTranslations[order.status] || order.status;
        
        let itemsHtml = '';
        if (order.items && order.items.length > 0) {
            itemsHtml = `
                <div class="mt-6">
                    <h5 class="mb-4">{{ __("passwords.order_items") }}</h5>
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-dashed gy-4">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th>{{ __("passwords.item") }}</th>
                                    <th>{{ __("passwords.sku") }}</th>
                                    <th class="text-end">{{ __("passwords.quantity") }}</th>
                                    <th class="text-end">{{ __("passwords.unit_cost") }}</th>
                                    <th class="text-end">{{ __("passwords.total") }}</th>
                                    <th class="text-end">{{ __("passwords.received") }}</th>
                                    <th class="text-end">{{ __("passwords.pending") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${order.items.map(item => `
                                    <tr>
                                        <td>${item.product_name || '{{ __("pagination.n_a") }}'}</td>
                                        <td>${item.sku || '{{ __("pagination.n_a") }}'}</td>
                                        <td class="text-end">${item.quantity}</td>
                                        <td class="text-end">${formatCurrency(item.unit_cost)}</td>
                                        <td class="text-end fw-bold">${formatCurrency(item.total_cost)}</td>
                                        <td class="text-end">${item.received_quantity || 0}</td>
                                        <td class="text-end">${item.quantity - (item.received_quantity || 0)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold fs-5 text-gray-800">
                                    <td colspan="4" class="text-end">{{ __("passwords.grand_total") }}:</td>
                                    <td class="text-end" colspan="3">${formatCurrency(order.total)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            `;
        }
        
        detailsContainer.innerHTML = `
            <div>
                <div class="row mb-6">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-4">
                            <div class="symbol symbol-60px me-4">
                                <div class="symbol-label bg-light-${statusColor} text-${statusColor} fw-bold fs-2">
                                    PO
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold mb-0">${order.po_number}</h2>
                                <span class="text-muted">{{ __("passwords.purchase_order") }}</span>
                            </div>
                        </div>
                        ${order.notes ? `
                            <div class="card card-flush bg-light-info">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-2">{{ __("passwords.notes") }}:</h6>
                                    <p class="mb-0">${order.notes}</p>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-${statusColor}">
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <span class="badge badge-${statusColor} fs-5 px-4 py-2">${statusText}</span>
                                </div>
                                <div class="fw-bold fs-2 text-${statusColor}">${formatCurrency(order.total)}</div>
                                <div class="text-muted">{{ __("passwords.total_amount") }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-6">
                    <div class="col-md-6">
                        <div class="card card-flush h-100">
                            <div class="card-header">
                                <h4 class="card-title fw-bold">{{ __("passwords.supplier_information") }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="text-muted fs-7">{{ __("passwords.name") }}</div>
                                    <div class="fw-bold">${order.supplier ? order.supplier.name : '{{ __("pagination.n_a") }}'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted fs-7">{{ __("passwords.contact") }}</div>
                                    <div class="fw-bold">${order.supplier ? order.supplier.contact_person : '{{ __("pagination.n_a") }}'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted fs-7">{{ __("passwords.email") }}</div>
                                    <div class="fw-bold">${order.supplier ? order.supplier.email : '{{ __("pagination.n_a") }}'}</div>
                                </div>
                                <div>
                                    <div class="text-muted fs-7">{{ __("passwords.phone") }}</div>
                                    <div class="fw-bold">${order.supplier ? order.supplier.phone : '{{ __("pagination.n_a") }}'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card card-flush h-100">
                            <div class="card-header">
                                <h4 class="card-title fw-bold">{{ __("passwords.delivery_information") }}</h4>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="text-muted fs-7">{{ __("passwords.location") }}</div>
                                    <div class="fw-bold">${order.location ? order.location.name : '{{ __("pagination.n_a") }}'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted fs-7">{{ __("passwords.expected_delivery") }}</div>
                                    <div class="fw-bold">${order.expected_delivery_date ? new Date(order.expected_delivery_date).toLocaleDateString() : '{{ __("passwords.not_set") }}'}</div>
                                </div>
                                <div class="mb-3">
                                    <div class="text-muted fs-7">{{ __("passwords.created") }}</div>
                                    <div class="fw-bold">${new Date(order.created_at).toLocaleDateString()}</div>
                                </div>
                                <div>
                                    <div class="text-muted fs-7">{{ __("passwords.updated") }}</div>
                                    <div class="fw-bold">${new Date(order.updated_at).toLocaleDateString()}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${itemsHtml}
            </div>
        `;
    }
    
    function printPurchaseOrder() {
        const printContent = document.getElementById('purchaseOrderDetails').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>{{ __("passwords.print_title") }}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .section { margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; }
                    .total { font-size: 18px; font-weight: bold; margin-top: 20px; }
                    @media print {
                        body { margin: 0; }
                    }
                </style>
            </head>
            <body>
                ${printContent}
            </body>
            </html>
        `;
        
        window.print();
        document.body.innerHTML = originalContent;
        window.location.reload();
    }

    function followUpOrder(orderId) {
        if (confirm('{{ __("passwords.send_follow_up_email") }}')) {
            // You'll need to implement this endpoint
            fetch(`/api/purchase-orders/${orderId}/follow-up`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("passwords.follow_up_email_sent") }}',
                        showConfirmButton: false,
                        timer: 1500
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("passwords.follow_up_email_failed") }}',
                        text: data.message || ''
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: '{{ __("passwords.error_occurred") }}',
                    text: error.message
                });
            });
        }
    }

    // Helper function for translations in JavaScript
    function __(key) {
        const translations = {
            'passwords.draft': '{{ __("passwords.draft") }}',
            'passwords.sent': '{{ __("passwords.sent") }}',
            'passwords.pending_approval': '{{ __("passwords.pending_approval") }}',
            'passwords.approved': '{{ __("passwords.approved") }}',
            'passwords.cancelled': '{{ __("passwords.cancelled") }}',
            'passwords.completed': '{{ __("passwords.completed") }}'
        };
        return translations[key] || key;
    }
</script>
@endpush
@endsection