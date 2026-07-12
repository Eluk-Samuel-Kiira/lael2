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
                    <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4 gap-lg-0">
                        <div class="page-title d-flex flex-column">
                            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-2hx fs-lg-1 flex-column my-0">
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
                        <div class="d-flex align-items-stretch align-items-sm-center w-100 w-lg-auto">
                            @if(isset($suppliers) && $suppliers->count() > 0)
                            <div class="dropdown w-100 w-sm-auto">
                                <button class="btn btn-sm btn-primary w-100 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2 me-1 me-sm-2"></i>
                                    <span class="d-none d-sm-inline">{{ __('pagination.export') }}</span>
                                    <span class="d-inline d-sm-none">{{ __('pagination.export') }}</span>
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
                                    {{-- First Line --}}
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
                                        {{-- Start Date --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('pagination.start_date') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="start_date" 
                                                    value="{{ $startDate }}" required>
                                            </div>
                                        </div>
                                        
                                        {{-- End Date --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label required fw-semibold">{{ __('pagination.end_date') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text bg-light">
                                                    <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                                </span>
                                                <input type="date" class="form-control" name="end_date" 
                                                    value="{{ $endDate }}" required>
                                            </div>
                                        </div>
                                        
                                        {{-- Period --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('pagination.period') }}</label>
                                            <div class="input-group w-100">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-clock fs-2"></i>
                                                </span>
                                                <select class="form-select" name="period">
                                                    <option value="monthly" {{ $period == 'monthly' ? 'selected' : '' }}>{{ __('pagination.monthly') }}</option>
                                                    <option value="quarterly" {{ $period == 'quarterly' ? 'selected' : '' }}>{{ __('pagination.quarterly') }}</option>
                                                    <option value="yearly" {{ $period == 'yearly' ? 'selected' : '' }}>{{ __('pagination.yearly') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Second Line --}}
                                    <div class="d-flex flex-column flex-xl-row gap-4 gap-xl-6 flex-wrap mb-4">
                                        {{-- Supplier --}}
                                        <div class="flex-grow-1">
                                            <label class="form-label fw-semibold">{{ __('passwords.supplier') }}</label>
                                            <div class="input-group w-100">
                                                <select class="form-select" name="supplier_id" data-control="select2" data-placeholder="{{ __('payments.all_status') }}">
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
                                        
                                        {{-- Action Buttons --}}
                                        <div class="d-flex gap-2" style="margin-top: auto;">
                                            <button type="submit" class="btn btn-primary" id="applyFilters">
                                                <i class="ki-duotone ki-filter fs-2 me-1"></i>
                                                {{ __('pagination.apply_filters') }}
                                            </button>
                                            <a href="{{ route('reports.purchasing.supplier-spend-analysis') }}" class="btn btn-light btn-active-light-primary">
                                                <i class="ki-duotone ki-cross fs-2 me-1"></i>
                                                {{ __('pagination.clear_filters') }}
                                            </a>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Spend Trend Chart --}}
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
                                        {{ __('pagination.period') }}: {{ $startDate }} {{ __('pagination.to') }} {{ $endDate }}
                                    </span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @if(isset($spendTrend) && $spendTrend->count() > 0)
                                <div id="spendTrendChart" style="height: 400px;"></div>
                                @else
                                <div class="text-center py-10">
                                    <i class="ki-duotone ki-chart-line-up fs-4tx text-gray-400 mb-4">
                                        <span class="path1"></span>
                                    </i>
                                    <h4 class="text-gray-600 fw-semibold mb-2">{{ __('passwords.no_trend_data') }}</h4>
                                    <p class="text-muted fs-6">{{ __('passwords.no_spend_data_for_selected_period') }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Supplier Spend Table --}}
                @if($supplierSpend->count() > 0)
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center justify-content-between w-100 flex-wrap gap-3">
                                    <div class="d-flex align-items-center">
                                        <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h3 class="fw-bold m-0">{{ __('passwords.supplier_spend_analysis') }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="supplierSpendTable">
                                        <thead>
                                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                <th class="ps-4 min-w-50px">#</th>
                                                <th style="min-width: 150px;">{{ __('passwords.supplier') }}</th>
                                                <th class="min-w-100px text-center">{{ __('passwords.total_orders') }}</th>
                                                <th class="min-w-150px text-end">{{ __('passwords.total_spent') }}</th>
                                                <th class="min-w-120px text-center">{{ __('passwords.spend_percentage') }}</th>
                                                <th class="min-w-150px text-end">{{ __('passwords.avg_order_value') }}</th>
                                                <th class="min-w-150px text-end">{{ __('passwords.min_order') }}</th>
                                                <th class="min-w-150px text-end">{{ __('passwords.max_order') }}</th>
                                                <th class="min-w-150px">{{ __('passwords.last_order') }}</th>
                                                <th class="min-w-80px text-center">{{ __('passwords.actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($supplierSpend as $index => $supplier)
                                            @php
                                                $globalIndex = $supplierSpendData->search(function($item) use ($supplier) {
                                                    return $item->id === $supplier->id;
                                                });
                                                $displayIndex = $globalIndex !== false ? $globalIndex + 1 : ($index + 1);
                                                
                                                $spendPercentage = $summary['total_spent'] > 0 ? ($supplier->total_spent / $summary['total_spent']) * 100 : 0;
                                                $classification = $spendPercentage >= 70 ? 'A' : ($spendPercentage >= 20 ? 'B' : 'C');
                                                $classColor = $spendPercentage >= 70 ? 'danger' : ($spendPercentage >= 20 ? 'warning' : 'success');
                                                
                                                $lastOrder = $supplier->last_order_date ? \Carbon\Carbon::parse($supplier->last_order_date) : null;
                                                $lastOrderColor = 'success';
                                                if ($lastOrder) {
                                                    $daysSinceLastOrder = $lastOrder->diffInDays(now());
                                                    if ($daysSinceLastOrder > 180) {
                                                        $lastOrderColor = 'danger';
                                                    } elseif ($daysSinceLastOrder > 90) {
                                                        $lastOrderColor = 'warning';
                                                    }
                                                }
                                            @endphp
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold">{{ $displayIndex }}</span>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-50px me-5 flex-shrink-0"> {{-- me-5 for more margin --}}
                                                            <div class="symbol-label bg-light-{{ $classColor }} text-{{ $classColor }} fw-bold">
                                                                {{ strtoupper(substr($supplier->name, 0, 2)) }}
                                                            </div>
                                                        </div>
                                                        <div class="ps-4"> {{-- ps-4 for padding-left --}}
                                                            <div class="fw-bold">{{ $supplier->name }}</div>
                                                            <small class="text-muted">{{ $supplier->contact_person ?? __('pagination.no_contact') }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="fw-bold fs-5">{{ number_format($supplier->order_count) }}</span>
                                                    <div class="text-muted fs-8">{{ __('passwords.orders') }}</div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-bold text-primary fs-5">
                                                        {{ currency_symbol() }}{{ number_format($supplier->total_spent, 2) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex align-items-center justify-content-end">
                                                        <span class="fw-bold me-2">{{ number_format($spendPercentage, 1) }}%</span>
                                                        <div class="progress w-80px" style="height: 6px;">
                                                            <div class="progress-bar bg-{{ $classColor }}" 
                                                                style="width: {{ min(100, $spendPercentage) }}%"></div>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted fs-8">
                                                        {{ __('passwords.classification') }}: 
                                                        <span class="fw-bold text-{{ $classColor }}">{{ $classification }}</span>
                                                    </small>
                                                </td>
                                                <td class="text-end">
                                                    <span class="fw-semibold">{{ currency_symbol() }}{{ number_format($supplier->avg_order_value, 2) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="text-muted">{{ currency_symbol() }}{{ number_format($supplier->min_order_value, 2) }}</span>
                                                </td>
                                                <td class="text-end">
                                                    <span class="text-success fw-semibold">{{ currency_symbol() }}{{ number_format($supplier->max_order_value, 2) }}</span>
                                                </td>
                                                <td>
                                                    <span class="text-{{ $lastOrderColor }} fw-semibold">
                                                        {{ $lastOrder ? $lastOrder->format('M d, Y') : __('passwords.never') }}
                                                    </span>
                                                    @if($lastOrder && $lastOrder->diffInDays(now()) > 30)
                                                    <div class="text-muted fs-8">
                                                        {{ $lastOrder->diffInDays(now()) }} {{ __('passwords.days_ago') }}
                                                    </div>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-icon btn-light-primary view-supplier-details" 
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
                                        @php
                                            $pageTotalSpent = $supplierSpend->sum('total_spent');
                                            $pageTotalOrders = $supplierSpend->sum('order_count');
                                        @endphp
                                        <tfoot class="bg-light">
                                            <tr>
                                                <td colspan="2" class="text-end fw-bold">{{ __('pagination.current_page') }}: </td>
                                                <td class="text-center fw-bold">{{ number_format($pageTotalOrders) }}</td>
                                                <td class="text-end fw-bold text-primary">{{ currency_symbol() }}{{ number_format($pageTotalSpent, 2) }}</td>
                                                <td colspan="5"></td>
                                            </tr>
                                            @if($supplierSpend->total() > $supplierSpend->count())
                                            <tr>
                                                <td colspan="2" class="text-end fw-bold text-muted">{{ __('pagination.grand_total') }}: </td>
                                                <td class="text-center fw-bold">{{ number_format($summary['total_orders']) }}</td>
                                                <td class="text-end fw-bold text-primary">{{ currency_symbol() }}{{ number_format($summary['total_spent'], 2) }}</td>
                                                <td colspan="5"></td>
                                            </tr>
                                            @endif
                                        </tfoot>
                                    </table>
                                </div>
                                
                                {{-- Pagination Component --}}
                                <div class="card-footer">
                                    @include('partials.pagination', [
                                        'paginator' => $supplierSpend,
                                        'pageName' => 'page',
                                        'perPageName' => 'per_page',
                                        'showPerPage' => true
                                    ])
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
    // ============================================
    // 1. Form Validation
    // ============================================
    const filterForm = document.getElementById('filterForm');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const startDateInput = document.querySelector('[name="start_date"]');
            const endDateInput = document.querySelector('[name="end_date"]');
            
            if (startDateInput && endDateInput) {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);
                
                if (startDate > endDate) {
                    e.preventDefault();
                    alert('{{ __("pagination.start_date_cannot_be_after_end_date") }}');
                    return false;
                }
            }
        });
    }

    // ============================================
    // 2. Spend Trend Chart
    // ============================================
    @if(isset($spendTrend) && $spendTrend->count() > 0)
    const spendTrendLabels = @json($spendTrend->keys());
    const spendTrendData = @json($spendTrend->values());
    
    const spendTrendChartElement = document.querySelector("#spendTrendChart");
    if (spendTrendChartElement && spendTrendData.length > 0) {
        const hasValidData = spendTrendData.some(value => value > 0);
        
        if (hasValidData) {
            const spendTrendChart = new ApexCharts(spendTrendChartElement, {
                series: [{
                    name: '{{ __("passwords.total_spent") }}',
                    data: spendTrendData
                }],
                chart: {
                    type: 'area',
                    height: 400,
                    width: '100%',
                    toolbar: {
                        show: true,
                        tools: {
                            download: true
                        }
                    },
                    zoom: {
                        enabled: true,
                        type: 'x'
                    }
                },
                colors: ['#3E97FF'],
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        if (val === 0) return '0';
                        return '{{ currency_symbol() }}' + val.toLocaleString('en-US', {minimumFractionDigits: 0});
                    },
                    offsetY: -10,
                    style: {
                        fontSize: '10px',
                        fontWeight: 'bold'
                    }
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
                    categories: spendTrendLabels,
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '11px',
                            colors: '#5B5B5B'
                        },
                        trim: true
                    },
                    title: {
                        text: '{{ __("pagination.period") }}',
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: '{{ __("passwords.total_spent") }} ({{ currency_symbol() }})',
                        style: {
                            fontSize: '12px',
                            fontWeight: 'bold'
                        }
                    },
                    labels: {
                        formatter: function(val) {
                            if (val === 0) return '{{ currency_symbol() }}0';
                            return '{{ currency_symbol() }}' + val.toLocaleString('en-US', {minimumFractionDigits: 0});
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return '{{ currency_symbol() }}' + val.toLocaleString('en-US', {minimumFractionDigits: 2});
                        }
                    },
                    x: {
                        formatter: function(val) {
                            return val;
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'center'
                },
                grid: {
                    borderColor: '#e7e7e7',
                    row: {
                        colors: ['#f3f3f3', 'transparent'],
                        opacity: 0.3
                    }
                },
                markers: {
                    size: 4,
                    colors: ['#3E97FF'],
                    strokeColors: '#fff',
                    strokeWidth: 2,
                    hover: {
                        size: 6
                    }
                }
            });
            spendTrendChart.render();
        } else {
            spendTrendChartElement.innerHTML = '<div class="text-center text-muted py-5">{{ __("passwords.no_trend_data") }}</div>';
        }
    }
    @endif

    // ============================================
    // 3. View Supplier Details
    // ============================================
    const viewSupplierButtons = document.querySelectorAll('.view-supplier-details');
    const supplierDetailsModalElement = document.getElementById('supplierDetailsModal');
    
    if (supplierDetailsModalElement && viewSupplierButtons.length > 0) {
        const supplierDetailsModal = new bootstrap.Modal(supplierDetailsModalElement);
        
        viewSupplierButtons.forEach(button => {
            button.addEventListener('click', function() {
                const supplierId = this.getAttribute('data-supplier-id');
                if (supplierId) {
                    loadSupplierSpendDetails(supplierId, supplierDetailsModal);
                }
            });
        });
    }
});

// ============================================
// 4. Load Supplier Spend Details (Global Function)
// ============================================
function loadSupplierSpendDetails(supplierId, modal) {
    const detailsContainer = document.getElementById('supplierDetailsContent');
    
    if (!detailsContainer) return;
    
    // Show loading state
    detailsContainer.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">{{ __("passwords.loading") }}</span>
            </div>
            <p class="mt-3">{{ __("passwords.loading_supplier_details") }}</p>
        </div>
    `;
    
    // Get current filter values from the form
    const startDate = document.querySelector('[name="start_date"]')?.value || '';
    const endDate = document.querySelector('[name="end_date"]')?.value || '';
    const periodType = document.querySelector('[name="period"]')?.value || 'monthly';
    
    // Build URL with query parameters
    const url = `/api/suppliers/${supplierId}/spend-details`;
    const params = new URLSearchParams();
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (periodType) params.append('period_type', periodType);
    
    const fullUrl = params.toString() ? url + '?' + params.toString() : url;
    
    fetch(fullUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        credentials: 'same-origin'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.data) {
            renderSupplierSpendDetails(data.data);
            if (modal) modal.show();
        } else {
            detailsContainer.innerHTML = `
                <div class="alert alert-danger text-center">
                    <i class="ki-duotone ki-cross-circle fs-2 me-2"></i>
                    {{ __("passwords.failed_to_load_supplier_details") }}
                    <br><small class="text-muted">${data.message || 'Unknown error'}</small>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading supplier details:', error);
        detailsContainer.innerHTML = `
            <div class="alert alert-danger text-center">
                <i class="ki-duotone ki-cross-circle fs-2 me-2"></i>
                {{ __("passwords.error_loading_supplier_details") }}
                <br><small class="text-muted">${error.message}</small>
            </div>
        `;
    });
}

// ============================================
// 5. Render Supplier Spend Details
// ============================================
function renderSupplierSpendDetails(supplierData) {
    const detailsContainer = document.getElementById('supplierDetailsContent');
    if (!detailsContainer) return;
    
    const supplier = supplierData.supplier || {};
    const purchaseOrders = supplierData.purchase_orders || [];
    
    const formatCurrency = (amount) => {
        const value = parseFloat(amount) || 0;
        return '{{ currency_symbol() }}' + value.toLocaleString('en-US', {minimumFractionDigits: 2});
    };
    
    const formatDate = (dateString) => {
        if (!dateString) return '{{ __("pagination.n_a") }}';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };
    
    // Get status color class
    const getStatusColor = (status) => {
        const colors = {
            'draft': 'dark',
            'sent': 'info',
            'pending_approval': 'warning',
            'approved': 'success',
            'cancelled': 'danger',
            'completed': 'primary'
        };
        return colors[status] || 'dark';
    };
    
    const getStatusText = (status) => {
        const texts = {
            'draft': '{{ __("passwords.draft") }}',
            'sent': '{{ __("passwords.sent") }}',
            'pending_approval': '{{ __("passwords.pending_approval") }}',
            'approved': '{{ __("passwords.approved") }}',
            'cancelled': '{{ __("passwords.cancelled") }}',
            'completed': '{{ __("passwords.completed") }}'
        };
        return texts[status] || status;
    };
    
    let ordersHtml = '';
    if (purchaseOrders.length > 0) {
        ordersHtml = `
            <div class="mt-6">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">{{ __("passwords.purchase_orders") }}</h5>
                    <span class="badge badge-light-primary">${purchaseOrders.length} {{ __("passwords.orders") }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-3">
                        <thead>
                            <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                <th class="ps-4">{{ __("passwords.po_number") }}</th>
                                <th>{{ __("passwords.order_date") }}</th>
                                <th>{{ __("passwords.location") }}</th>
                                <th>{{ __("passwords.status") }}</th>
                                <th class="text-end pe-4">{{ __("passwords.amount") }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${purchaseOrders.map(order => `
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">${order.po_number || 'N/A'}</div>
                                        ${order.notes ? `<small class="text-muted">${order.notes.substring(0, 50)}</small>` : ''}
                                    </td>
                                    <td>${formatDate(order.created_at)}</td>
                                    <td><span class="badge badge-light-info">${order.location?.name || 'N/A'}</span></td>
                                    <td><span class="badge badge-light-${getStatusColor(order.status)}">${getStatusText(order.status)}</span></td>
                                    <td class="text-end pe-4 fw-bold text-primary">${formatCurrency(order.total)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="4" class="text-end fw-bold">{{ __("passwords.grand_total") }}:</td>
                                <td class="text-end pe-4 fw-bold text-primary fs-5">${formatCurrency(supplierData.total_spent)}</td>
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
            <!-- Supplier Header -->
            <div class="d-flex align-items-center mb-6">
                <div class="symbol symbol-60px me-4">
                    <div class="symbol-label bg-light-primary text-primary fw-bold fs-2">
                        ${supplier.name ? supplier.name.substring(0, 2).toUpperCase() : 'SU'}
                    </div>
                </div>
                <div>
                    <h2 class="fw-bold mb-0">${supplier.name || '{{ __("pagination.unknown_supplier") }}'}</h2>
                    <span class="text-muted">{{ __("passwords.supplier") }}</span>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="row g-4 mb-6">
                <div class="col-md-4">
                    <div class="card card-flush bg-light-info h-100">
                        <div class="card-body">
                            <div class="text-muted fs-7">{{ __("passwords.contact_person") }}</div>
                            <div class="fw-bold fs-6">${supplier.contact_person || '{{ __("pagination.no_contact") }}'}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush bg-light-success h-100">
                        <div class="card-body">
                            <div class="text-muted fs-7">{{ __("passwords.email") }}</div>
                            <div class="fw-bold fs-6">${supplier.email || '{{ __("pagination.n_a") }}'}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-flush bg-light-warning h-100">
                        <div class="card-body">
                            <div class="text-muted fs-7">{{ __("passwords.phone") }}</div>
                            <div class="fw-bold fs-6">${supplier.phone || '{{ __("pagination.n_a") }}'}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Spend Summary Cards -->
            <div class="row g-4 mb-6">
                <div class="col-md-3">
                    <div class="card card-flush bg-light-dark">
                        <div class="card-body text-center">
                            <div class="text-muted fs-7">{{ __("passwords.total_orders") }}</div>
                            <div class="fw-bold fs-2">${supplierData.order_count || 0}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush bg-light-primary">
                        <div class="card-body text-center">
                            <div class="text-muted fs-7">{{ __("passwords.total_spent") }}</div>
                            <div class="fw-bold fs-2 text-primary">${formatCurrency(supplierData.total_spent)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush bg-light-info">
                        <div class="card-body text-center">
                            <div class="text-muted fs-7">{{ __("passwords.avg_order_value") }}</div>
                            <div class="fw-bold fs-2">${formatCurrency(supplierData.avg_order_value)}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-flush bg-light-success">
                        <div class="card-body text-center">
                            <div class="text-muted fs-7">{{ __("passwords.max_order") }}</div>
                            <div class="fw-bold fs-2 text-success">${formatCurrency(supplierData.max_order_value)}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Purchase Orders Table -->
            ${ordersHtml}
        </div>
    `;
}

// ============================================
// 6. Print Supplier Details
// ============================================
function printSupplierDetails() {
    const printContent = document.getElementById('supplierDetailsContent');
    if (!printContent) return;
    
    const originalContent = document.body.innerHTML;
    const printWindow = window.open('', '_blank');
    
    printWindow.document.write(`
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
                .summary-card { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .text-end { text-align: right; }
                .fw-bold { font-weight: bold; }
                @media print {
                    body { margin: 0; }
                    .no-break { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            ${printContent.innerHTML}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
    printWindow.close();
}


</script>
@endif
@endpush

@endsection