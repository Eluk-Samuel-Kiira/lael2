{{-- resources/views/reports/purchasing/supplier-spend-analysis.blade.php --}}
@extends('layouts.app')

@section('title', __('pagination.supplier_spend_analysis'))

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
                                {{ __('pagination.supplier_spend_analysis') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('pagination.supplier_spend_analysis') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if(isset($suppliers) && $suppliers->count() > 0)
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
                                <form method="GET" action="{{ route('reports.purchasing.supplier-spend-analysis') }}" id="filterForm">
                                    <div class="row g-4 mb-4">
                                        {{-- Date Range --}}
                                        <div class="col-md-3">
                                            <label class="form-label required fw-semibold">{{ __('pagination.start_date') }}</label>
                                            <input type="date" class="form-control" name="start_date" 
                                                value="{{ $startDate ?? now()->subDays(90)->format('Y-m-d') }}" required>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <label class="form-label required fw-semibold">{{ __('pagination.end_date') }}</label>
                                            <input type="date" class="form-control" name="end_date" 
                                                value="{{ $endDate ?? now()->format('Y-m-d') }}" required>
                                        </div>
                                        
                                        {{-- Supplier --}}
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                            <select class="form-select" name="supplier_id">
                                                <option value="">{{ __('passwords.all_suppliers') }}</option>
                                                @if(isset($suppliers))
                                                    @foreach($suppliers as $supplier)
                                                        <option value="{{ $supplier->id }}" 
                                                                {{ (isset($supplierId) && $supplierId == $supplier->id) ? 'selected' : '' }}>
                                                            {{ $supplier->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                        
                                        {{-- Sort By --}}
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">{{ __('passwords.sort_by') }}</label>
                                            <select class="form-select" name="sort_by">
                                                <option value="total_spent" {{ (isset($sortBy) && $sortBy == 'total_spent') ? 'selected' : '' }}>{{ __('passwords.total_spent') }}</option>
                                                <option value="order_count" {{ (isset($sortBy) && $sortBy == 'order_count') ? 'selected' : '' }}>{{ __('passwords.order_count') }}</option>
                                                <option value="name" {{ (isset($sortBy) && $sortBy == 'name') ? 'selected' : '' }}>{{ __('passwords.supplier_name') }}</option>
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
                                                <a href="{{ route('reports.purchasing.supplier-spend-analysis') }}" class="btn btn-light btn-active-light-primary">
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
                @if(isset($summary))
                <div class="row mb-6">
                    @php
                        $summaryCards = [
                            [
                                'title' => __('passwords.total_suppliers'),
                                'value' => number_format($summary['total_suppliers'] ?? 0),
                                'color' => 'primary',
                                'icon' => 'ki-profile-user',
                                'description' => __('passwords.active_suppliers')
                            ],
                            [
                                'title' => __('passwords.total_spent'),
                                'value' => '$' . number_format($summary['total_spent'] ?? 0, 2),
                                'color' => 'success',
                                'icon' => 'ki-dollar',
                                'description' => __('passwords.total_purchases')
                            ],
                            [
                                'title' => __('passwords.avg_order_value'),
                                'value' => '$' . number_format($summary['avg_order_value'] ?? 0, 2),
                                'color' => 'info',
                                'icon' => 'ki-chart-line',
                                'description' => __('passwords.per_supplier_average')
                            ],
                            [
                                'title' => __('passwords.top_spender'),
                                'value' => isset($topSupplier) ? (strlen($topSupplier->name) > 20 ? substr($topSupplier->name, 0, 20) . '...' : $topSupplier->name) : __('passwords.none'),
                                'color' => 'warning',
                                'icon' => 'ki-crown',
                                'description' => isset($topSupplier) ? '$' . number_format($topSupplier->total_spent ?? 0, 2) : ''
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
                @endif

                {{-- Spend Trend Chart --}}
                @if(isset($spendTrend) && count($spendTrend) > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-chart-line-up fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.spend_trend') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.period') }}: {{ $startDate ?? '' }} {{ __('pagination.to') }} {{ $endDate ?? '' }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="spendTrendChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Supplier Spend Table --}}
                @if(isset($supplierSpend) && $supplierSpend->count() > 0)
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
                                        <h3 class="fw-bold m-0">{{ __('passwords.supplier_spend_analysis') }}</h3>
                                    </div>
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('pagination.showing') }} {{ $supplierSpend->count() }} {{ __('pagination.of') }} {{ $supplierSpend->total() }} {{ __('passwords.suppliers') }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="supplierSpendTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4">#</th>
                                                <th>{{ __('passwords.supplier') }}</th>
                                                <th>{{ __('passwords.total_orders') }}</th>
                                                <th>{{ __('passwords.total_spent') }}</th>
                                                <th>{{ __('passwords.spend_percentage') }}</th>
                                                <th>{{ __('passwords.avg_order_value') }}</th>
                                                <th>{{ __('passwords.min_order') }}</th>
                                                <th>{{ __('passwords.max_order') }}</th>
                                                <th>{{ __('passwords.last_order') }}</th>
                                                <th class="text-end pe-4">{{ __('passwords.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($supplierSpend as $index => $supplier)
                                            @php
                                                // Calculate ABC classification
                                                $spendPercentage = $supplier->total_spent / ($summary['total_spent'] ?? 1) * 100;
                                                $classification = 'C';
                                                $classColor = 'success';
                                                
                                                if ($spendPercentage >= 70) {
                                                    $classification = 'A';
                                                    $classColor = 'danger';
                                                } elseif ($spendPercentage >= 20) {
                                                    $classification = 'B';
                                                    $classColor = 'warning';
                                                }
                                                
                                                // Get last order date
                                                $lastOrder = $supplier->last_order_date ? \Carbon\Carbon::parse($supplier->last_order_date) : null;
                                                $daysSinceLastOrder = $lastOrder ? $lastOrder->diffInDays(now()) : null;
                                                
                                                // Last order color
                                                $lastOrderColor = 'success';
                                                $lastOrderText = $lastOrder ? $lastOrder->format('M d, Y') : __('passwords.never');
                                                
                                                if ($daysSinceLastOrder !== null) {
                                                    if ($daysSinceLastOrder > 180) {
                                                        $lastOrderColor = 'danger';
                                                    } elseif ($daysSinceLastOrder > 90) {
                                                        $lastOrderColor = 'warning';
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $loop->iteration }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-50px me-3">
                                                            <div class="symbol-label bg-light-{{ $classColor }} text-{{ $classColor }} fw-bold">
                                                                {{ strtoupper(substr($supplier->name, 0, 2)) }}
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $supplier->name }}</div>
                                                            <small class="text-muted">{{ $supplier->contact_person ?? __('pagination.no_contact') }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold">{{ $supplier->order_count ?? 0 }}</span>
                                                    <div class="text-muted fs-8">{{ __('passwords.orders') }}</div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-primary">
                                                        ${{ number_format($supplier->total_spent ?? 0, 2) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="fw-bold me-2">{{ number_format($spendPercentage, 1) }}%</span>
                                                        <div class="progress w-100px" style="height: 6px;">
                                                            <div class="progress-bar bg-{{ $classColor }}" 
                                                                style="width: {{ min(100, $spendPercentage) }}%"></div>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted fs-8">
                                                        {{ __('passwords.classification') }}: 
                                                        <span class="fw-bold text-{{ $classColor }}">{{ $classification }}</span>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="fw-semibold">${{ number_format($supplier->avg_order_value ?? 0, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-muted">${{ number_format($supplier->min_order_value ?? 0, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-success fw-semibold">${{ number_format($supplier->max_order_value ?? 0, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-{{ $lastOrderColor }} fw-semibold">
                                                        {{ $lastOrderText }}
                                                    </span>
                                                    @if($daysSinceLastOrder !== null && $daysSinceLastOrder > 30)
                                                    <div class="text-muted fs-8">
                                                        {{ $daysSinceLastOrder }} {{ __('passwords.days_ago') }}
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="text-end pe-4">
                                                    <button class="btn btn-icon btn-light-primary btn-sm me-2 view-supplier-details" 
                                                            data-supplier-id="{{ $supplier->id }}"
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
                                        {{ __('pagination.showing') }} {{ $supplierSpend->firstItem() }} - {{ $supplierSpend->lastItem() }} {{ __('pagination.of') }} {{ $supplierSpend->total() }} {{ __('passwords.suppliers') }}
                                    </div>
                                    <div>
                                        {{ $supplierSpend->appends(request()->query())->links() }}
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
                                        <i class="ki-duotone ki-profile-user fs-4tx text-gray-400 mb-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_supplier_data') }}</h4>
                                        <p class="text-muted fs-6">{{ __('passwords.no_supplier_spend_data_available') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'supplier_id', 'sort_by']))
                                        <a href="{{ route('reports.purchasing.supplier-spend-analysis') }}" class="btn btn-light-primary">
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

<!-- Supplier Details Modal -->
<div class="modal fade" id="supplierDetailsModal" tabindex="-1" aria-labelledby="supplierDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="supplierDetailsModalLabel">{{ __('passwords.supplier_spend_details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="supplierDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('passwords.loading') }}</span>
                        </div>
                        <p class="mt-3">{{ __('passwords.loading_supplier_details') }}</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('passwords.close') }}</button>
                <button type="button" class="btn btn-primary" onclick="printSupplierDetails()">
                    <i class="ki-duotone ki-printer fs-2 me-2"></i>
                    {{ __('passwords.print') }}
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if(isset($spendTrend) && count($spendTrend) > 0)
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

        // Spend Trend Chart
        @if(isset($spendTrend) && count($spendTrend) > 0)
        @php
            $chartLabels = [];
            $chartData = [];
            
            foreach ($spendTrend as $period => $spend) {
                $chartLabels[] = $period;
                $chartData[] = $spend;
            }
        @endphp

        const spendTrendChart = new ApexCharts(document.querySelector("#spendTrendChart"), {
            series: [{
                name: '{{ __("passwords.total_spent") }}',
                data: @json($chartData)
            }],
            chart: {
                type: 'area',
                height: 400,
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
            colors: ['#3E97FF'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: @json($chartLabels),
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: '{{ __("passwords.total_spent_usd") }}'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString('en-US', {minimumFractionDigits: 0})
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString('en-US', {minimumFractionDigits: 2})
                    }
                }
            },
            legend: {
                position: 'top'
            }
        });
        spendTrendChart.render();
        @endif

        // View Supplier Details
        const viewSupplierButtons = document.querySelectorAll('.view-supplier-details');
        const supplierDetailsModal = new bootstrap.Modal(document.getElementById('supplierDetailsModal'));
        
        viewSupplierButtons.forEach(button => {
            button.addEventListener('click', function() {
                const supplierId = this.getAttribute('data-supplier-id');
                loadSupplierSpendDetails(supplierId);
            });
        });
    });

    function loadSupplierSpendDetails(supplierId) {
        const detailsContainer = document.getElementById('supplierDetailsContent');
        
        detailsContainer.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">{{ __("passwords.loading") }}</span>
                </div>
                <p class="mt-3">{{ __("passwords.loading_supplier_details") }}</p>
            </div>
        `;
        
        // Determine period based on current filter or default to last month
        const currentDate = new Date();
        const period = currentDate.getFullYear() + '-' + String(currentDate.getMonth() + 1).padStart(2, '0');
        
        fetch(`/api/suppliers/${supplierId}/spend-details?period=${period}&period_type=monthly`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data) {
                    const supplierData = data.data;
                    renderSupplierSpendDetails(supplierData);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('supplierDetailsModal'));
                    modal.show();
                } else {
                    detailsContainer.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="ki-duotone ki-cross-circle fs-2 me-2"></i>
                            {{ __("passwords.failed_to_load_supplier_details") }}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                detailsContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="ki-duotone ki-cross-circle fs-2 me-2"></i>
                        {{ __("passwords.error_loading_supplier_details") }}
                    </div>
                `;
            });
    }

    function renderSupplierSpendDetails(supplierData) {
        const detailsContainer = document.getElementById('supplierDetailsContent');
        const supplier = supplierData.supplier;
        const purchaseOrders = supplierData.purchase_orders || [];
        
        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 2
            }).format(amount || 0);
        };
        
        let ordersHtml = '';
        if (purchaseOrders.length > 0) {
            ordersHtml = `
                <div class="mt-6">
                    <h5 class="mb-4">{{ __("passwords.purchase_orders") }} (${purchaseOrders.length})</h5>
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-dashed gy-4">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th>{{ __("passwords.po_number") }}</th>
                                    <th>{{ __("passwords.order_date") }}</th>
                                    <th>{{ __("passwords.location") }}</th>
                                    <th>{{ __("passwords.status") }}</th>
                                    <th class="text-end">{{ __("passwords.amount") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${purchaseOrders.map(order => `
                                    <tr>
                                        <td>
                                            <div class="fw-bold">${order.po_number || 'N/A'}</div>
                                            <small class="text-muted">${order.notes || ''}</small>
                                        </td>
                                        <td>
                                            <span class="text-muted">${new Date(order.created_at).toLocaleDateString()}</span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">${order.location ? order.location.name : 'N/A'}</span>
                                        </td>
                                        <td>
                                            ${(() => {
                                                const statusColors = {
                                                    'draft': 'dark',
                                                    'sent': 'info',
                                                    'pending_approval': 'warning',
                                                    'approved': 'success',
                                                    'cancelled': 'danger',
                                                    'completed': 'primary'
                                                };
                                                const statusColor = statusColors[order.status] || 'dark';
                                                const statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1).replace('_', ' ');
                                                return \`<span class="badge badge-\${statusColor}">\${statusText}</span>\`;
                                            })()}
                                        </td>
                                        <td class="text-end fw-bold text-primary">${formatCurrency(order.total)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold fs-5 text-gray-800">
                                    <td colspan="4" class="text-end">{{ __("passwords.grand_total") }}:</td>
                                    <td class="text-end">${formatCurrency(supplierData.total_spent)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            `;
        } else {
            ordersHtml = `
                <div class="text-center py-5">
                    <i class="ki-duotone ki-shopping-cart fs-4tx text-gray-400 mb-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <h5 class="text-gray-600 fw-semibold mb-2">{{ __("passwords.no_purchase_orders") }}</h5>
                    <p class="text-muted">{{ __("passwords.no_purchase_orders_found_for_period") }}</p>
                </div>
            `;
        }
        
        detailsContainer.innerHTML = `
            <div>
                <div class="row mb-6">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center mb-4">
                            <div class="symbol symbol-60px me-4">
                                <div class="symbol-label bg-light-primary text-primary fw-bold fs-2">
                                    ${supplier.name ? supplier.name.substring(0, 2).toUpperCase() : 'SU'}
                                </div>
                            </div>
                            <div>
                                <h2 class="fw-bold mb-0">${supplier.name}</h2>
                                <span class="text-muted">{{ __("passwords.supplier") }}</span>
                            </div>
                        </div>
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="card card-flush bg-light-info h-100">
                                    <div class="card-body">
                                        <div class="text-muted fs-7">{{ __("passwords.contact_person") }}</div>
                                        <div class="fw-bold">${supplier.contact_person || '{{ __("pagination.no_contact") }}'}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-flush bg-light-success h-100">
                                    <div class="card-body">
                                        <div class="text-muted fs-7">{{ __("passwords.email") }}</div>
                                        <div class="fw-bold">${supplier.email || '{{ __("pagination.n_a") }}'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card card-flush bg-light-warning h-100">
                                    <div class="card-body">
                                        <div class="text-muted fs-7">{{ __("passwords.phone") }}</div>
                                        <div class="fw-bold">${supplier.phone || '{{ __("pagination.n_a") }}'}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-flush bg-light-primary h-100">
                                    <div class="card-body">
                                        <div class="text-muted fs-7">{{ __("passwords.address") }}</div>
                                        <div class="fw-bold">${supplier.address || '{{ __("pagination.n_a") }}'}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-flush bg-light-dark">
                            <div class="card-body">
                                <h4 class="card-title fw-bold mb-4">{{ __("passwords.spend_summary") }}</h4>
                                
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="text-muted">{{ __("passwords.order_count") }}:</span>
                                    <span class="fw-bold fs-5">${supplierData.order_count || 0}</span>
                                </div>
                                
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="text-muted">{{ __("passwords.total_spent") }}:</span>
                                    <span class="fw-bold fs-5 text-primary">${formatCurrency(supplierData.total_spent)}</span>
                                </div>
                                
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="text-muted">{{ __("passwords.avg_order_value") }}:</span>
                                    <span class="fw-bold fs-5">${formatCurrency(supplierData.avg_order_value)}</span>
                                </div>
                                
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="text-muted">{{ __("passwords.min_order") }}:</span>
                                    <span class="fw-bold fs-5">${formatCurrency(supplierData.min_order_value)}</span>
                                </div>
                                
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="text-muted">{{ __("passwords.max_order") }}:</span>
                                    <span class="fw-bold fs-5 text-success">${formatCurrency(supplierData.max_order_value)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${ordersHtml}
            </div>
        `;
    }

    function printSupplierDetails() {
        const printContent = document.getElementById('supplierDetailsContent').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>{{ __("passwords.supplier_spend_details") }}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .section { margin-bottom: 20px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; }
                    .summary-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
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
</script>
@endif
@endpush
@endsection