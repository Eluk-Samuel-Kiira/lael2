{{-- resources/views/reports/purchasing/purchase-order-items.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.purchase_order_items_analysis'))

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
                                {{ __('pagination.purchase_order_items_analysis') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.purchase_order_items_analysis') }}</li>
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
                                    <li>
                                        <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">
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
                                <form method="GET" action="{{ route('reports.purchasing.purchase-order-items') }}" id="filterForm">
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
                                        
                                        {{-- Product Variant --}}
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('passwords.product_variant') }}</label>
                                            <select class="form-select" name="variant_id">
                                                <option value="">{{ __('passwords.all_products') }}</option>
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}" 
                                                            {{ $productVariantId == $variant->id ? 'selected' : '' }}>
                                                        {{ $variant->name }} 
                                                        @if($variant->product)
                                                            ({{ $variant->product->name }})
                                                        @endif
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
                                                <a href="{{ route('reports.purchasing.purchase-order-items') }}" class="btn btn-light btn-active-light-primary">
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
                                'title' => __('passwords.total_items'),
                                'value' => number_format($summary['total_items']),
                                'color' => 'primary',
                                'icon' => 'ki-element-plus',
                                'description' => __('passwords.items_purchased')
                            ],
                            [
                                'title' => __('passwords.total_quantity'),
                                'value' => number_format($summary['total_quantity']),
                                'color' => 'success',
                                'icon' => 'ki-arrow-up',
                                'description' => __('passwords.total_units')
                            ],
                            [
                                'title' => __('passwords.total_value'),
                                'value' => '$' . number_format($summary['total_value'], 2),
                                'color' => 'info',
                                'icon' => 'ki-dollar',
                                'description' => __('passwords.total_spend')
                            ],
                            [
                                'title' => __('passwords.unique_products'),
                                'value' => number_format($summary['unique_products']),
                                'color' => 'warning',
                                'icon' => 'ki-shield-tick',
                                'description' => __('passwords.different_items')
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

                {{-- Additional Stats --}}
                <div class="row mb-6">
                    <div class="col-md-4 mb-4">
                        <div class="card card-flush bg-light-dark h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-line fs-2tx text-dark me-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ __('passwords.avg_unit_cost') }}</div>
                                        <div class="fs-2 fw-bold text-dark">${{ number_format($summary['avg_unit_cost'] ?? 0, 2) }}</div>
                                        <div class="text-muted fs-7">{{ __('passwords.per_unit_average') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card card-flush bg-light-danger h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-profile-user fs-2tx text-danger me-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ __('passwords.unique_suppliers') }}</div>
                                        <div class="fs-2 fw-bold text-danger">{{ number_format($summary['unique_suppliers']) }}</div>
                                        <div class="text-muted fs-7">{{ __('passwords.suppliers_used') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card card-flush bg-light-primary h-100">
                            <div class="card-body d-flex flex-column justify-content-center">
                                <div class="d-flex align-items-center">
                                    <i class="ki-duotone ki-calendar-8 fs-2tx text-primary me-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div>
                                        <div class="fs-4 fw-bold text-gray-800">{{ __('pagination.period') }}</div>
                                        <div class="fs-2 fw-bold text-primary">{{ $startDate }} - {{ $endDate }}</div>
                                        <div class="text-muted fs-7">{{ __('passwords.date_range') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Top Items by Quantity Chart --}}
                @if($topItemsByQuantity->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('passwords.top_items_by_quantity') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('passwords.top') }} 10 {{ __('passwords.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="topItemsChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Purchase Order Items Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('passwords.purchase_order_items') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $items->count() }} {{ __('pagination.of') }} {{ $items->total() }} {{ __('passwords.items') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="itemsTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('passwords.po_number') }}</th>
                                                <th>{{ __('passwords.product') }}</th>
                                                <th>{{ __('passwords.supplier') }}</th>
                                                <th>{{ __('passwords.quantity') }}</th>
                                                <th>{{ __('passwords.unit_cost') }}</th>
                                                <th>{{ __('passwords.total_cost') }}</th>
                                                <th>{{ __('passwords.received_qty') }}</th>
                                                <th>{{ __('passwords.balance') }}</th>
                                                <th>{{ __('passwords.order_date') }}</th>
                                                <th class="text-end pe-4">{{ __('passwords.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($items as $index => $item)
                                            @php
                                                $po = $item->purchaseOrder;
                                                $receivedQty = $item->received_quantity ?? 0;
                                                $balance = $item->quantity - $receivedQty;
                                                
                                                // Status colors
                                                if ($balance == 0) {
                                                    $statusColor = 'success';
                                                    $statusText = __('passwords.completed');
                                                } elseif ($receivedQty > 0 && $receivedQty < $item->quantity) {
                                                    $statusColor = 'warning';
                                                    $statusText = __('passwords.partial');
                                                } elseif ($po && $po->status == 'cancelled') {
                                                    $statusColor = 'danger';
                                                    $statusText = __('passwords.cancelled');
                                                } else {
                                                    $statusColor = 'info';
                                                    $statusText = __('passwords.pending');
                                                }
                                                
                                                // Balance color
                                                $balanceColor = $balance == 0 ? 'success' : ($balance > 0 ? 'warning' : 'danger');
                                            @endphp
                                            <tr>
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
                                                                {{ $po ? $po->status : 'N/A' }}
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($item->productVariant && $item->productVariant->product)
                                                        <div class="symbol symbol-40px me-3">
                                                            <div class="symbol-label bg-light-info text-info fw-bold">
                                                                {{ substr($item->productVariant->product->name, 0, 2) }}
                                                            </div>
                                                        </div>
                                                        @endif
                                                        <div>
                                                            <div class="fw-bold">{{ $item->product_name ?? ($item->productVariant->name ?? 'N/A') }}</div>
                                                            <small class="text-muted">
                                                                {{ $item->sku ?? ($item->productVariant->sku ?? 'N/A') }}
                                                                @if($item->productVariant && $item->productVariant->product)
                                                                    <br>{{ $item->productVariant->product->name }}
                                                                @endif
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
                                                    <span class="fw-bold">{{ number_format($item->quantity) }}</span>
                                                    <div class="text-muted fs-8">{{ __('passwords.units') }}</div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        ${{ number_format($item->unit_cost, 2) }}
                                                    </span>
                                                    <div class="text-muted fs-8">{{ __('passwords.per_unit') }}</div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">
                                                        ${{ number_format($item->total_cost, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="fw-bold me-2 {{ $receivedQty >= $item->quantity ? 'text-success' : 'text-warning' }}">
                                                            {{ number_format($receivedQty) }}
                                                        </span>
                                                        <div class="progress w-100px" style="height: 6px;">
                                                            <div class="progress-bar bg-{{ $receivedQty >= $item->quantity ? 'success' : ($receivedQty > 0 ? 'warning' : 'danger') }}" 
                                                                style="width: {{ $item->quantity > 0 ? ($receivedQty / $item->quantity * 100) : 0 }}%"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-light-{{ $balanceColor }}">
                                                        {{ number_format($balance) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="text-muted">
                                                        {{ $item->created_at->format('M d, Y') }}
                                                    </span>
                                                    <div class="text-muted fs-8">
                                                        {{ $item->created_at->format('h:i A') }}
                                                    </div>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <span class="badge badge-{{ $statusColor }}">
                                                        {{ $statusText }}
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
                                        <i class="ki-duotone ki-shopping-cart fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_items_found') }}</h4>
                                        <p class="text-muted fs-6">{{ __('passwords.no_purchase_order_items_match_filters') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'supplier_id', 'variant_id']))
                                        <a href="{{ route('reports.purchasing.purchase-order-items') }}" class="btn btn-light-primary">
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
@if($topItemsByQuantity->count() > 0)
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

        // Top Items Chart
        @php
            $chartLabels = [];
            $chartQuantities = [];
            $chartValues = [];
            $chartColors = ['#3E97FF', '#50CD89', '#FFC700', '#F1416C', '#7239EA', '#009EF7', '#FF9F1C', '#B5B5C3', '#181C32', '#00A3FF'];
            
            $counter = 0;
            foreach ($topItemsByQuantity as $item) {
                $productName = $item['product'] ? ($item['product']->name ?? 'N/A') : 'N/A';
                if ($item['product'] && $item['product']->product) {
                    $productName = $item['product']->product->name . ' - ' . $item['product']->name;
                }
                $chartLabels[] = strlen($productName) > 30 ? substr($productName, 0, 30) . '...' : $productName;
                $chartQuantities[] = $item['total_quantity'];
                $chartValues[] = $item['total_value'];
                $counter++;
            }
        @endphp

        const topItemsChart = new ApexCharts(document.querySelector("#topItemsChart"), {
            series: [
                {
                    name: '{{ __("passwords.quantity") }}',
                    type: 'column',
                    data: @json($chartQuantities)
                },
                {
                    name: '{{ __("passwords.total_value") }}',
                    type: 'line',
                    data: @json($chartValues)
                }
            ],
            chart: {
                height: 400,
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
            colors: ['#3E97FF', '#F1416C'],
            xaxis: {
                categories: @json($chartLabels),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: [
                {
                    title: {
                        text: '{{ __("passwords.quantity") }}'
                    },
                    labels: {
                        formatter: function(val) {
                            return val.toLocaleString('en-US', {minimumFractionDigits: 0})
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
                shared: true,
                intersect: false,
                y: [
                    {
                        formatter: function(val) {
                            return val.toLocaleString('en-US', {minimumFractionDigits: 0}) + ' {{ __("passwords.units") }}'
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
            },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    columnWidth: '60%',
                }
            }
        });
        topItemsChart.render();
    });
</script>
@endif

<script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });
</script>
@endpush
@endsection