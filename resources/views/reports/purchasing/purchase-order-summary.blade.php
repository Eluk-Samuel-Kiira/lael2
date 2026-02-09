{{-- resources/views/reports/purchasing/purchase-order-summary.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.purchase_order_summary'))

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
                                {{ __('pagination.purchase_order_summary') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchase_order_summary') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($summary['total_orders'] > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('pagination.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'purchaseOrdersTable', filename: 'purchase_order_summary_{{ date('Y_m_d') }}', sheetName: 'Purchase Orders'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'purchaseOrdersTable', filename: 'purchase_order_summary_{{ date('Y_m_d') }}', format: 'csv'})">
                                            <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                            {{ __('pagination.export_to_csv') }}
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
                                <form method="GET" action="{{ route('reports.purchasing.purchase-order-summary') }}" id="filterForm">
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
                                        
                                        {{-- Status --}}
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('passwords.order_status') }}</label>
                                            <select class="form-select" name="status">
                                                <option value="all">{{ __('passwords.all_statuses') }}</option>
                                                <option value="draft" {{ $status == 'draft' ? 'selected' : '' }}>{{ __('passwords.draft') }}</option>
                                                <option value="sent" {{ $status == 'sent' ? 'selected' : '' }}>{{ __('passwords.sent') }}</option>
                                                <option value="pending_approval" {{ $status == 'pending_approval' ? 'selected' : '' }}>{{ __('passwords.pending_approval') }}</option>
                                                <option value="approved" {{ $status == 'approved' ? 'selected' : '' }}>{{ __('passwords.approved') }}</option>
                                                <option value="partially_received" {{ $status == 'partially_received' ? 'selected' : '' }}>{{ __('passwords.partially_received') }}</option>
                                                <option value="received" {{ $status == 'received' ? 'selected' : '' }}>{{ __('passwords.received') }}</option>
                                                <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>{{ __('passwords.cancelled') }}</option>
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
                                                <a href="{{ route('reports.purchasing.purchase-order-summary') }}" class="btn btn-light btn-active-light-primary">
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
                                'title' => __('passwords.total_orders'),
                                'value' => $summary['total_orders'],
                                'color' => 'primary',
                                'icon' => 'ki-document'
                            ],
                            [
                                'title' => __('passwords.total_spent'),
                                'value' => '$' . number_format($summary['total_value'], 2),
                                'color' => 'success',
                                'icon' => 'ki-dollar'
                            ],
                            [
                                'title' => __('passwords.avg_order_value'),
                                'value' => '$' . number_format($summary['average_order_value'], 2),
                                'color' => 'info',
                                'icon' => 'ki-chart-line'
                            ],
                            [
                                'title' => __('passwords.pending_orders'),
                                'value' => $summary['pending_orders'],
                                'color' => 'warning',
                                'icon' => 'ki-clock'
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Purchase Orders Table --}}
                @if($purchaseOrders->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('passwords.purchase_orders') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $purchaseOrders->count() }} {{ __('pagination.of') }} {{ $purchaseOrders->total() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="purchaseOrdersTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('passwords.po_number') }}</th>
                                                <th>{{ __('passwords.supplier') }}</th>
                                                <th>{{ __('passwords.order_date') }}</th>
                                                <th>{{ __('passwords.expected_delivery') }}</th>
                                                <th>{{ __('pagination.status') }}</th>
                                                <th>{{ __('pagination.total_amount') }}</th>
                                                <th>{{ __('pagination.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($purchaseOrders as $order)
                                            @php
                                                $statusColors = [
                                                    'draft' => 'secondary',
                                                    'sent' => 'info',
                                                    'pending_approval' => 'warning',
                                                    'approved' => 'primary',
                                                    'partially_received' => 'success',
                                                    'received' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                $statusColor = $statusColors[$order->status] ?? 'dark';
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $loop->iteration }}</span>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold">{{ $order->po_number }}</div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $order->supplier->name }}</div>
                                                    <small class="text-muted">{{ $order->supplier->contact_person ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    {{ $order->created_at->format('Y-m-d') }}
                                                </td>
                                                <td>
                                                    @if($order->expected_delivery_date)
                                                        {{ $order->expected_delivery_date->format('Y-m-d') }}
                                                        @if($order->expected_delivery_date < now() && !in_array($order->status, ['received', 'cancelled']))
                                                            <span class="badge badge-light-danger ms-1">{{ __('pagination.overdue') }}</span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $statusColor }}">
                                                        {{ __('passwords.' . $order->status) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        ${{ number_format($order->total, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-light btn-active-light-primary view-order-details"
                                                            data-order-id="{{ $order->id }}"
                                                            title="{{ __('pagination.view_details') }}">
                                                        <i class="bi bi-eye fs-5"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($purchaseOrders->hasPages())
                                <div class="card-footer d-flex justify-content-end">
                                    {{ $purchaseOrders->withQueryString()->links() }}
                                </div>
                                @endif
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
                                        <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_purchase_orders') }}</h4>
                                        <p class="text-muted fs-6">{{ __('passwords.no_purchase_orders_in_period') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'supplier_id', 'status']))
                                        <a href="{{ route('reports.purchasing.purchase-order-summary') }}" class="btn btn-light-primary">
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

{{-- Purchase Order Details Modal --}}
<div class="modal fade" id="purchaseOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fw-bold" id="modalTitle">{{ __('passwords.purchase_order_details') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body" id="purchaseOrderDetails">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">{{ __('passwords.loading_details') }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('pagination.close') }}</button>
                <!-- <a href="#" id="fullDetailsLink" class="btn btn-primary" target="_blank">
                    <i class="ki-duotone ki-external-link fs-2 me-2"></i>
                    {{ __('passwords.view_full_details') }}
                </a> -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
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

        // Initialize modal
        const purchaseOrderModal = new bootstrap.Modal(document.getElementById('purchaseOrderModal'));
        
        // Event listener for view details buttons
        document.querySelectorAll('.view-order-details').forEach(button => {
            button.addEventListener('click', function() {
                const orderId = this.getAttribute('data-order-id');
                loadPurchaseOrderDetails(orderId);
            });
        });

        // Function to load purchase order details via AJAX
        function loadPurchaseOrderDetails(orderId) {
            // Show modal with loading state
            const modalBody = document.getElementById('purchaseOrderDetails');
            modalBody.innerHTML = `
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('pagination.loading...') }}</span>
                    </div>
                    <p class="mt-2">{{ __('pagination.loading_details') }}</p>
                </div>
            `;
            
            // Update full details link
            // document.getElementById('fullDetailsLink').href = `/purchase-orders/${orderId}`;
            
            // Show modal
            purchaseOrderModal.show();
            
            // Fetch order details via AJAX
            fetch(`/api/purchase-orders/${orderId}/details`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        renderPurchaseOrderDetails(data.data);
                    } else {
                        modalBody.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="ki-duotone ki-cross-circle fs-2 me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                ${data.message || '{{ __("pagination.failed_to_load_details") }}'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading purchase order details:', error);
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="ki-duotone ki-cross-circle fs-2 me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            {{ __("pagination.network_error") }}
                        </div>
                    `;
                });
        }

        // Function to render purchase order details
        function renderPurchaseOrderDetails(order) {
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('purchaseOrderDetails');
            
            // Update modal title
            modalTitle.textContent = `{{ __('passwords.purchase_order') }}: ${order.po_number}`;
            
            // Format dates
            const formatDate = (dateString) => {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            };
            
            // Determine status color
            const statusColors = {
                'draft': 'secondary',
                'sent': 'info',
                'pending_approval': 'warning',
                'approved': 'primary',
                'partially_received': 'success',
                'received': 'success',
                'cancelled': 'danger'
            };
            const statusColor = statusColors[order.status] || 'dark';
            
            // Render order details
            modalBody.innerHTML = `
                <div class="row">
                    <!-- Order Summary -->
                    <div class="col-md-6 mb-6">
                        <div class="card card-flush h-100">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('pagination.order_summary') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.po_number') }}</td>
                                                <td class="fw-bold">${order.po_number}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.supplier') }}</td>
                                                <td class="fw-bold">${order.supplier.name}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('pagination.status') }}</td>
                                                <td>
                                                    <span class="badge badge-light-${statusColor}">
                                                        ${order.status}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.order_date') }}</td>
                                                <td>${formatDate(order.created_at)}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.expected_delivery') }}</td>
                                                <td>${formatDate(order.expected_delivery_date)}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('pagination.location') }}</td>
                                                <td>${order.location ? order.location.name : '-'}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Financial Summary -->
                    <div class="col-md-6 mb-6">
                        <div class="card card-flush h-100">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('passwords.financial_summary') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted">{{ __('pagination.subtotal') }}</td>
                                                <td class="text-end fw-bold">$${parseFloat(order.subtotal).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('pagination.tax') }}</td>
                                                <td class="text-end fw-bold">$${parseFloat(order.tax_total).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('pagination.total_amount') }}</td>
                                                <td class="text-end fw-bold text-primary">
                                                    $${parseFloat(order.total).toLocaleString('en-US', {minimumFractionDigits: 2})}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Notes -->
                                ${order.notes ? `
                                <div class="mt-6">
                                    <h6 class="text-muted mb-2">{{ __('pagination.notes') }}</h6>
                                    <div class="bg-light rounded p-3">
                                        ${order.notes}
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="row">
                    <div class="col-12">
                        <div class="card card-flush">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('pagination.order_items') }}</h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.sku') }}</th>
                                                <th>{{ __('pagination.quantity') }}</th>
                                                <th>{{ __('pagination.unit_cost') }}</th>
                                                <th>{{ __('passwords.total_cost') }}</th>
                                                <th>{{ __('passwords.received') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${order.items && order.items.length > 0 ? 
                                                order.items.map((item, index) => `
                                                <tr>
                                                    <td class="ps-4">${index + 1}</td>
                                                    <td>
                                                        <div class="fw-bold">${item.product_name}</div>
                                                        ${item.product_variant ? `<small class="text-muted">${item.product_variant.product.name}</small>` : ''}
                                                    </td>
                                                    <td>${item.sku}</td>
                                                    <td>${item.quantity}</td>
                                                    <td>$${parseFloat(item.unit_cost).toFixed(2)}</td>
                                                    <td class="fw-bold">$${parseFloat(item.total_cost).toFixed(2)}</td>
                                                    <td>
                                                        <span class="badge badge-light-${item.received_quantity >= item.quantity ? 'success' : 'warning'}">
                                                            ${item.received_quantity} / ${item.quantity}
                                                        </span>
                                                    </td>
                                                </tr>
                                                `).join('') : 
                                                `<tr><td colspan="7" class="text-center text-muted py-6">{{ __('pagination.no_items_found') }}</td></tr>`
                                            }
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold fs-6 text-gray-800 border-top border-gray-200">
                                                <td colspan="5" class="text-end">{{ __('pagination.total') }}</td>
                                                <td class="text-primary">$${parseFloat(order.total).toFixed(2)}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    });
</script>
@endpush

@endsection