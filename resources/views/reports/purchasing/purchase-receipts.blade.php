{{-- resources/views/reports/purchasing/purchase-receipts.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.purchase_receipts'))

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">
            <div class="container-fluid">
                {{-- Toolbar Section --}}
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
                                {{ __('pagination.purchase_receipts') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchase_receipts') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if($receipts->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('pagination.export') }}</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportTableToExcel({tableId: 'receiptsTable', filename: 'purchase_receipts_{{ date('Y_m_d') }}'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('pagination.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportTableToCSV({tableId: 'receiptsTable', filename: 'purchase_receipts_{{ date('Y_m_d') }}'})">
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
                                <form method="GET" action="{{ route('reports.purchasing.purchase-receipts') }}" id="filterForm">
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
                                        {{-- Start Date --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('pagination.start_date') }}</label>
                                            <input type="date" class="form-control w-100" name="start_date" 
                                                value="{{ $startDate }}" required>
                                        </div>
                                        
                                        {{-- End Date --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('pagination.end_date') }}</label>
                                            <input type="date" class="form-control w-100" name="end_date" 
                                                value="{{ $endDate }}" required>
                                        </div>
                                        
                                        {{-- Supplier --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                            <select class="form-select w-100" name="supplier_id">
                                                <option value="">{{ __('passwords.all_suppliers') }}</option>
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" 
                                                            {{ $supplierId == $supplier->id ? 'selected' : '' }}>
                                                        {{ $supplier->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        
                                        {{-- Location --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('passwords.location') }}</label>
                                            <select class="form-select w-100" name="location_id">
                                                <option value="">{{ __('passwords.all_locations') }}</option>
                                                @foreach($locations as $location)
                                                    <option value="{{ $location->id }}" 
                                                            {{ $locationId == $location->id ? 'selected' : '' }}>
                                                        {{ $location->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-end">
                                        <div class="d-flex flex-column flex-sm-row gap-2">
                                            <button type="submit" class="btn btn-primary flex-grow-1 flex-sm-grow-0" id="applyFilters">
                                                <i class="ki-duotone ki-filter fs-2 me-1 me-sm-2"></i>
                                                <span class="d-none d-sm-inline">{{ __('pagination.apply_filters') }}</span>
                                                <span class="d-inline d-sm-none">{{ __('pagination.apply') }}</span>
                                            </button>
                                            <a href="{{ route('reports.purchasing.purchase-receipts') }}" class="btn btn-light btn-active-light-primary flex-grow-1 flex-sm-grow-0">
                                                <i class="ki-duotone ki-cross fs-2 me-1 me-sm-2"></i>
                                                <span class="d-none d-sm-inline">{{ __('pagination.clear_filters') }}</span>
                                                <span class="d-inline d-sm-none">{{ __('pagination.clear') }}</span>
                                            </a>
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
                                'title' => __('passwords.total_receipts'),
                                'value' => $summary['total_receipts'],
                                'color' => 'primary',
                                'icon' => 'ki-document',
                                'description' => __('passwords.receipts_in_period')
                            ],
                            [
                                'title' => __('passwords.total_quantity'),
                                'value' => number_format($summary['total_quantity']),
                                'color' => 'success',
                                'icon' => 'ki-box',
                                'description' => __('passwords.items_received')
                            ],
                            [
                                'title' => __('passwords.unique_items'),
                                'value' => $summary['unique_items'],
                                'color' => 'info',
                                'icon' => 'ki-tag',
                                'description' => __('passwords.different_items')
                            ],
                            [
                                'title' => __('passwords.total_value'),
                                'value' => '$' . number_format($summary['total_value'], 2),
                                'color' => 'warning',
                                'icon' => 'ki-dollar',
                                'description' => __('passwords.total_cost')
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

                {{-- Purchase Receipts Table --}}
                @if($receipts->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('passwords.purchase_receipts') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $receipts->count() }} {{ __('pagination.of') }} {{ $receipts->total() }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="receiptsTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('passwords.receipt_number') }}</th>
                                                <th>{{ __('passwords.purchase_order') }}</th>
                                                <th>{{ __('passwords.supplier') }}</th>
                                                <th>{{ __('passwords.location') }}</th>
                                                <th>{{ __('passwords.received_date') }}</th>
                                                <th>{{ __('passwords.received_by') }}</th>
                                                <th>{{ __('passwords.items_received') }}</th>
                                                <th>{{ __('passwords.total_quantity') }}</th>
                                                <th>{{ __('pagination.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($receipts as $receipt)
                                            @php
                                                $po = $receipt->purchaseOrder;
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $loop->iteration + ($receipts->currentPage() - 1) * $receipts->perPage() }}</span>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $receipt->id }}</div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold">{{ $po->po_number ?? 'N/A' }}</div>
                                                    @if($po)
                                                    <small class="text-muted">{{ $po->created_at->format('Y-m-d') }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($po && $po->supplier)
                                                    <div class="fw-bold">{{ $po->supplier->name }}</div>
                                                    <small class="text-muted">{{ $po->supplier->contact_person ?? '-' }}</small>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($po && $po->location)
                                                    <span class="fw-semibold">{{ $po->location->name }}</span>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $receipt->received_at ? $receipt->received_at->format('Y-m-d H:i') : '-' }}
                                                </td>
                                                <td>
                                                    @if($receipt->receivedBy)
                                                    <div class="fw-semibold">{{ $receipt->receivedBy->name }}</div>
                                                    @else
                                                    <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-info">{{ $receipt->items_count ?? $receipt->items->count() }}</span>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        {{ $receipt->total_quantity ?? $receipt->items->sum('quantity_received') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-light btn-active-light-primary view-receipt-details"
                                                            data-receipt-id="{{ $receipt->id }}"
                                                            title="{{ __('passwords.view_details') }}">
                                                    <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                
                                {{-- Pagination --}}
                                @if($receipts->hasPages())
                                <div class="card-footer d-flex justify-content-end">
                                    {{ $receipts->withQueryString()->links() }}
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
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_receipts') }}</h4>
                                        <p class="text-muted fs-6">{{ __('passwords.no_receipts_in_period') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'supplier_id', 'location_id']))
                                        <a href="{{ route('reports.purchasing.purchase-receipts') }}" class="btn btn-light-primary">
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

{{-- Receipt Details Modal --}}
<div class="modal fade" id="receiptDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fw-bold" id="modalTitle">{{ __('passwords.receipt_details') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>
            <div class="modal-body" id="receiptDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('passwords.loading...') }}...</span>
                    </div>
                    <p class="mt-2">{{ __('passwords.loading_details') }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('pagination.close') }}</button>
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
        const receiptDetailsModal = new bootstrap.Modal(document.getElementById('receiptDetailsModal'));
        
        // Event listener for view details buttons
        document.querySelectorAll('.view-receipt-details').forEach(button => {
            button.addEventListener('click', function() {
                const receiptId = this.getAttribute('data-receipt-id');
                loadReceiptDetails(receiptId);
            });
        });

        // Function to load receipt details via AJAX
        function loadReceiptDetails(receiptId) {
            // Show modal with loading state
            const modalBody = document.getElementById('receiptDetailsContent');
            modalBody.innerHTML = `
                <div class="text-center py-10">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('pagination.loading') }}...</span>
                    </div>
                    <p class="mt-2">{{ __('passwords.loading_details') }}</p>
                </div>
            `;
            
            // Show modal
            receiptDetailsModal.show();
            
            // Fetch receipt details via AJAX
            fetch(`/api/purchase-receipts/${receiptId}/details`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        renderReceiptDetails(data.data);
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
                    console.error('Error loading receipt details:', error);
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

        // Function to render receipt details
        function renderReceiptDetails(receipt) {
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('receiptDetailsContent');
            
            // Update modal title
            modalTitle.textContent = `{{ __('passwords.receipt') }}: ${receipt.id}`;
            
            // Format date
            const formatDate = (dateString) => {
                if (!dateString) return '-';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            };
            
            // Calculate totals
            const totalItems = receipt.items?.length || 0;
            const totalQuantity = receipt.items?.reduce((sum, item) => sum + (item.quantity_received || 0), 0) || 0;
            const totalValue = receipt.items?.reduce((sum, item) => {
                const unitCost = item.unit_cost || item.purchase_order_item?.unit_cost || 0;
                return sum + (item.quantity_received || 0) * unitCost;
            }, 0) || 0;
            
            // Render receipt details
            modalBody.innerHTML = `
                <div class="row mb-6">
                    <!-- Receipt Summary -->
                    <div class="col-md-6">
                        <div class="card card-flush">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('passwords.receipt_summary') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.receipt_number') }}</td>
                                                <td class="fw-bold">${receipt.id}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.purchase_order') }}</td>
                                                <td class="fw-bold">${receipt.purchase_order?.po_number || '-'}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.supplier') }}</td>
                                                <td class="fw-bold">${receipt.purchase_order?.supplier?.name || '-'}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.location') }}</td>
                                                <td>${receipt.purchase_order?.location?.name || '-'}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.received_date') }}</td>
                                                <td>${formatDate(receipt.received_at)}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.received_by') }}</td>
                                                <td>${receipt.received_by?.name || '-'}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Financial Summary -->
                    <div class="col-md-6">
                        <div class="card card-flush">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('passwords.financial_summary') }}</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.total_items') }}</td>
                                                <td class="text-end fw-bold">${totalItems}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('passwords.total_quantity') }}</td>
                                                <td class="text-end fw-bold">${totalQuantity}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">{{ __('pagination.total_value') }}</td>
                                                <td class="text-end fw-bold text-primary">
                                                    $${totalValue.toLocaleString('en-US', {minimumFractionDigits: 2})}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Notes -->
                                ${receipt.notes ? `
                                <div class="mt-6">
                                    <h6 class="text-muted mb-2">{{ __('pagination.notes') }}</h6>
                                    <div class="bg-light rounded p-3">
                                        ${receipt.notes}
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Receipt Items -->
                <div class="row">
                    <div class="col-12">
                        <div class="card card-flush">
                            <div class="card-header">
                                <h3 class="card-title">{{ __('pagination.received_items') }}</h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('pagination.product') }}</th>
                                                <th>{{ __('pagination.sku') }}</th>
                                                <th>{{ __('passwords.ordered_qty') }}</th>
                                                <th>{{ __('passwords.received_qty') }}</th>
                                                <th>{{ __('pagination.unit_cost') }}</th>
                                                <th>{{ __('passwords.total_cost') }}</th>
                                                <th>{{ __('passwords.batch_number') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${receipt.items && receipt.items.length > 0 ? 
                                                receipt.items.map((item, index) => {
                                                    const poItem = item.purchase_order_item;
                                                    const unitCost = item.unit_cost || poItem?.unit_cost || 0;
                                                    const totalCost = (item.quantity_received || 0) * unitCost;
                                                    
                                                    return `
                                                    <tr>
                                                        <td class="ps-4">${index + 1}</td>
                                                        <td>
                                                            <div class="fw-bold">${item.product_name || poItem?.product_name || 'N/A'}</div>
                                                            ${poItem?.product_variant?.product?.name ? 
                                                                `<small class="text-muted">${poItem.product_variant.product.name}</small>` : ''}
                                                        </td>
                                                        <td>${item.sku || poItem?.sku || '-'}</td>
                                                        <td>${poItem?.quantity || 0}</td>
                                                        <td>
                                                            <span class="badge badge-light-success">
                                                                ${item.quantity_received || 0}
                                                            </span>
                                                        </td>
                                                        <td>$${unitCost.toFixed(2)}</td>
                                                        <td class="fw-bold">$${totalCost.toFixed(2)}</td>
                                                        <td>${item.batch_number || '-'}</td>
                                                    </tr>
                                                    `;
                                                }).join('') : 
                                                `<tr><td colspan="8" class="text-center text-muted py-6">{{ __('pagination.no_items_found') }}</td></tr>`
                                            }
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold fs-6 text-gray-800 border-top border-gray-200">
                                                <td colspan="5" class="text-end">{{ __('pagination.total') }}</td>
                                                <td></td>
                                                <td class="text-primary">$${totalValue.toFixed(2)}</td>
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

        // Export functions
        function exportTableToExcel({tableId, filename}) {
            const table = document.getElementById(tableId);
            const wb = XLSX.utils.table_to_book(table, {sheet: "Receipts"});
            XLSX.writeFile(wb, `${filename}.xlsx`);
        }

        function exportTableToCSV({tableId, filename}) {
            const table = document.getElementById(tableId);
            const wb = XLSX.utils.table_to_book(table, {sheet: "Receipts"});
            XLSX.writeFile(wb, `${filename}.csv`);
        }

        // Make export functions globally available
        window.exportTableToExcel = exportTableToExcel;
        window.exportTableToCSV = exportTableToCSV;
    });
</script>
@endpush

@endsection