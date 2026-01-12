{{-- resources/views/reports/expenses/by-vendor.blade.php --}}
@extends('layouts.app')

@section('title', __('accounting.expenses_by_vendor'))

@section('content')
<div class="container-fluid">
    {{-- Toolbar Section --}}
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">
                    {{ __('accounting.expenses_by_vendor') }}
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                            {{ __('accounting.dashboard') }}
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-500 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">{{ __('accounting.vendor_analysis') }}</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                @if($vendorBreakdown->count() > 0)
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                               onclick="exportCurrentPage({tableId: 'vendorTable', filename: 'expenses_by_vendor_{{ date('Y_m_d') }}', sheetName: 'Vendor Breakdown'})">
                                <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                {{ __('accounting.export_to_excel') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                               onclick="exportCurrentPage({tableId: 'vendorTable', filename: 'expenses_by_vendor_{{ date('Y_m_d') }}', format: 'csv'})">
                                <i class="ki-duotone ki-file-csv fs-2 me-2 text-primary"></i>
                                {{ __('accounting.export_to_csv') }}
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
                        <h3 class="fw-bold m-0">{{ __('accounting.filter_by') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <form method="GET" action="{{ route('reports.expenses.by-vendor') }}" id="filterForm">
                        <div class="row g-6 mb-6">
                            {{-- Date Range --}}
                            <div class="col-md-6 col-lg-4">
                                <label class="form-label required fw-semibold">{{ __('accounting.date_range') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-calendar-8 fs-2"></i>
                                    </span>
                                    <input type="date" class="form-control" name="start_date" 
                                           value="{{ $startDate }}" required
                                           title="{{ __('accounting.start_date') }}">
                                    <span class="input-group-text bg-light">{{ __('accounting.to') }}</span>
                                    <input type="date" class="form-control" name="end_date" 
                                           value="{{ $endDate }}" required
                                           title="{{ __('accounting.end_date') }}">
                                </div>
                            </div>
                            
                            {{-- Vendor Name --}}
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">{{ __('accounting.vendor_name') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-shop fs-2"></i>
                                    </span>
                                    <input type="text" class="form-control" name="vendor_name" 
                                           value="{{ $vendorName }}" 
                                           placeholder="{{ __('accounting.search_vendor') }}"
                                           maxlength="200">
                                </div>
                            </div>
                            
                            {{-- Category --}}
                            <div class="col-md-6 col-lg-2">
                                <label class="form-label fw-semibold">{{ __('accounting.category') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-category fs-2"></i>
                                    </span>
                                    <select class="form-select" name="category_id">
                                        <option value="">{{ __('accounting.all_categories') }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}" 
                                                    {{ $categoryId == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            {{-- Payment Method --}}
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">{{ __('accounting.payment_method') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-credit-card fs-2"></i>
                                    </span>
                                    <select class="form-select" name="payment_method_id">
                                        <option value="">{{ __('accounting.all_payment_methods') }}</option>
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method->id }}" 
                                                    {{ $paymentMethodId == $method->id ? 'selected' : '' }}>
                                                {{ $method->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-6 mb-6">
                            {{-- Amount Range --}}
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">{{ __('accounting.min_amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-dollar fs-2"></i>
                                    </span>
                                    <input type="number" class="form-control" name="min_amount" 
                                           value="{{ $minAmount }}" 
                                           placeholder="{{ __('accounting.minimum_amount') }}"
                                           step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3">
                                <label class="form-label fw-semibold">{{ __('accounting.max_amount') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="ki-duotone ki-dollar fs-2"></i>
                                    </span>
                                    <input type="number" class="form-control" name="max_amount" 
                                           value="{{ $maxAmount }}" 
                                           placeholder="{{ __('accounting.maximum_amount') }}"
                                           step="0.01" min="0">
                                </div>
                            </div>
                            
                            {{-- Action Buttons --}}
                            <div class="col-md-6 col-lg-6 d-flex align-items-end justify-content-end">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="applyFilters">
                                        <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                        {{ __('accounting.apply_filters') }}
                                    </button>
                                    <a href="{{ route('reports.expenses.by-vendor') }}" class="btn btn-light btn-active-light-primary">
                                        <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                        {{ __('accounting.clear_filters') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Statistics --}}
    @if($vendorBreakdown->count() > 0)
    <div class="row mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="card-title d-flex align-items-center">
                        <i class="ki-duotone ki-chart-simple fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold m-0">{{ __('accounting.vendor_summary') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-6">
                        @foreach([
                            ['key' => 'total_vendors', 'color' => 'primary', 'icon' => 'ki-shop', 'label' => 'total_vendors', 'value' => $summary['total_vendors']],
                            ['key' => 'total_transactions', 'color' => 'success', 'icon' => 'ki-receipt', 'label' => 'total_transactions', 'value' => $summary['total_transactions']],
                            ['key' => 'total_amount', 'color' => 'info', 'icon' => 'ki-chart-simple', 'label' => 'grand_total', 'value' => '$' . number_format($summary['total_amount'], 2)],
                            ['key' => 'total_tax', 'color' => 'warning', 'icon' => 'ki-receipt-tax', 'label' => 'total_tax', 'value' => '$' . number_format($summary['total_tax'], 2)],
                            ['key' => 'avg_transaction', 'color' => 'danger', 'icon' => 'ki-calculator', 'label' => 'average_transaction', 'value' => '$' . number_format($summary['avg_transaction'], 2)],
                            ['key' => 'largest_single', 'color' => 'secondary', 'icon' => 'ki-arrow-up', 'label' => 'largest_transaction', 'value' => '$' . number_format($summary['largest_single'], 2)]
                        ] as $stat)
                        <div class="col-md-6 col-lg-2">
                            <div class="card card-flush bg-light-{{ $stat['color'] }} border border-{{ $stat['color'] }} border-dashed h-100">
                                <div class="card-body d-flex flex-column justify-content-center text-center">
                                    <div class="mb-4">
                                        <i class="ki-duotone {{ $stat['icon'] }} fs-2tx text-{{ $stat['color'] }}">
                                            @for($i = 1; $i <= 2; $i++)
                                            <span class="path{{ $i }}"></span>
                                            @endfor
                                        </i>
                                    </div>
                                    <div class="mb-1">
                                        <span class="fs-1 fw-bold text-gray-800">
                                            {{ $stat['value'] }}
                                        </span>
                                    </div>
                                    <div class="text-gray-600 fw-semibold">
                                        {{ __('accounting.' . $stat['label']) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Vendor Breakdown Table --}}
    <div class="row mb-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="card-title d-flex align-items-center justify-content-between w-100">
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-tablet-text-up fs-2 me-2 text-primary">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h3 class="fw-bold m-0">{{ __('accounting.vendor_breakdown') }}</h3>
                        </div>
                        @if($vendorBreakdown->count() > 0)
                        <span class="badge badge-light-primary fs-7">
                            {{ __('accounting.showing') }} {{ $vendorBreakdown->count() }} {{ __('accounting.vendors') }}
                        </span>
                        @endif
                    </div>
                </div>
                
                @if($vendorBreakdown->count() > 0)
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="vendorTable">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                        <th class="min-w-50px ps-4">{{ __('accounting.rank') }}</th>
                                        <th class="min-w-200px">{{ __('accounting.vendor_name') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.transactions') }}</th>
                                        <th class="min-w-120px">{{ __('accounting.total_amount') }}</th>
                                        <th class="min-w-120px">{{ __('accounting.tax_amount') }}</th>
                                        <th class="min-w-120px">{{ __('accounting.grand_total') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.average') }}</th>
                                        <th class="min-w-120px">{{ __('accounting.largest') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.smallest') }}</th>
                                        <th class="min-w-100px">{{ __('accounting.categories') }}</th>
                                        <th class="min-w-150px">{{ __('accounting.distribution') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vendorBreakdown as $index => $vendor)
                                    @php
                                        $percentage = $summary['total_amount'] > 0 ? ($vendor->grand_total / $summary['total_amount']) * 100 : 0;
                                        $paymentMethods = $vendorPaymentMethods[$vendor->vendor_name] ?? collect();
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <span class="fw-bold text-gray-800">{{ $index + 1 }}</span>
                                            @if($index < 3)
                                            <div class="mt-1">
                                                <span class="badge badge-light-{{ $index == 0 ? 'danger' : ($index == 1 ? 'warning' : 'info') }}">
                                                    <i class="ki-duotone ki-{{ $index == 0 ? 'medal' : ($index == 1 ? 'ranking' : 'ranking-2') }} fs-4 me-1"></i>
                                                    {{ __('accounting.top') }} {{ $index + 1 }}
                                                </span>
                                            </div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="symbol symbol-40px symbol-circle me-3">
                                                    <div class="symbol-label bg-light-{{ ['primary', 'success', 'info', 'warning', 'danger'][$index % 5] }}">
                                                        <i class="ki-duotone ki-shop fs-2"></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-start flex-column">
                                                    <span class="text-gray-800 fw-bold">{{ $vendor->vendor_name }}</span>
                                                    @if($paymentMethods->isNotEmpty())
                                                    <div class="mt-1">
                                                        @foreach($paymentMethods->take(2) as $method)
                                                        <span class="badge badge-light-primary badge-sm me-1">
                                                            {{ $method->payment_method }} ({{ $method->count }})
                                                        </span>
                                                        @endforeach
                                                        @if($paymentMethods->count() > 2)
                                                        <span class="badge badge-light-secondary badge-sm">
                                                            +{{ $paymentMethods->count() - 2 }}
                                                        </span>
                                                        @endif
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary">{{ $vendor->transaction_count }}</span>
                                        </td>
                                        <td>
                                            <span class="text-gray-800 fw-semibold">${{ number_format($vendor->total_amount, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-info">${{ number_format($vendor->total_tax, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">${{ number_format($vendor->grand_total, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-gray-600">${{ number_format($vendor->average_transaction, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-danger">${{ number_format($vendor->largest_transaction, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-secondary">${{ number_format($vendor->smallest_transaction, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-info">{{ $vendor->categories_used }}</span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress w-100 me-3" style="height: 8px;">
                                                    <div class="progress-bar bg-{{ ['primary', 'success', 'info', 'warning', 'danger'][$index % 5] }}" 
                                                         role="progressbar" 
                                                         style="width: {{ min($percentage, 100) }}%;" 
                                                         aria-valuenow="{{ $percentage }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <span class="fw-bold text-gray-700 min-w-60px text-end">
                                                    {{ number_format($percentage, 1) }}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="text-center py-10">
                            <i class="ki-duotone ki-document fs-4tx text-gray-400 mb-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h4 class="text-gray-600 fw-semibold mb-2">{{ __('accounting.no_data_available') }}</h4>
                            <p class="text-muted fs-6">{{ __('accounting.no_expenses_found_for_vendors') }}</p>
                            @if(request()->hasAny(['start_date', 'end_date', 'vendor_name', 'category_id', 'payment_method_id']))
                            <a href="{{ route('reports.expenses.by-vendor') }}" class="btn btn-light-primary">
                                <i class="ki-duotone ki-cross fs-2 me-2"></i>
                                {{ __('accounting.clear_filters_view_all') }}
                            </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    {{-- END Vendor Breakdown Table --}}
    
    {{-- Monthly Vendor Activity --}}
    @if($monthlyVendorActivity->count() > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="card-title d-flex align-items-center">
                        <i class="ki-duotone ki-chart-line fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold m-0">{{ __('accounting.monthly_vendor_activity') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div id="monthlyVendorChart" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Monthly Top Vendors Table --}}
    <div class="row mt-6">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="card-title d-flex align-items-center">
                        <i class="ki-duotone ki-table fs-2 me-2 text-primary">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold m-0">{{ __('accounting.monthly_top_vendors') }}</h3>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                    <th class="ps-4">{{ __('accounting.month_year') }}</th>
                                    <th>{{ __('accounting.top_vendor') }}</th>
                                    <th>{{ __('accounting.transactions') }}</th>
                                    <th>{{ __('accounting.monthly_total') }}</th>
                                    <th>{{ __('accounting.monthly_average') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $groupedByMonth = $monthlyVendorActivity->groupBy(function($item) {
                                        return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                                    });
                                @endphp
                                
                                @foreach($groupedByMonth->take(12) as $monthKey => $vendors)
                                @php
                                    $topVendor = $vendors->first();
                                    // Create date from year and month using DateTime (no Carbon needed)
                                    $date = DateTime::createFromFormat('Y-m', $monthKey);
                                @endphp
                                <tr>
                                    <td class="ps-4 fw-semibold">{{ $date ? $date->format('M Y') : '' }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-30px symbol-circle me-3">
                                                <div class="symbol-label bg-light-primary">
                                                    <i class="ki-duotone ki-shop fs-2"></i>
                                                </div>
                                            </div>
                                            <span class="text-gray-800 fw-bold">{{ $topVendor->vendor_name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-primary">{{ $topVendor->transaction_count }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">${{ number_format($topVendor->monthly_total, 2) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-gray-600">${{ number_format($topVendor->monthly_average, 2) }}</span>
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
    {{-- END Monthly Vendor Activity --}}
    
</div>
{{-- END Main Content --}}

@push('scripts')
@if($monthlyVendorActivity->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Group monthly data by vendor (top 5 vendors)
        const topVendors = @json($vendorBreakdown->take(5)->pluck('vendor_name'));
        const monthlyData = @json($monthlyVendorActivity);
        
        // Create series data for top 5 vendors
        const seriesData = [];
        const monthSet = new Set();
        
        // Get all unique months
        monthlyData.forEach(item => {
            const monthKey = `${item.year}-${String(item.month).padStart(2, '0')}`;
            monthSet.add(monthKey);
        });
        
        const sortedMonths = Array.from(monthSet).sort();
        
        // Prepare data for each top vendor
        topVendors.forEach((vendorName, vendorIndex) => {
            const vendorData = [];
            
            sortedMonths.forEach(monthKey => {
                const [year, month] = monthKey.split('-');
                const monthlyItem = monthlyData.find(item => 
                    item.vendor_name === vendorName && 
                    item.year == year && 
                    item.month == month
                );
                
                vendorData.push(monthlyItem ? monthlyItem.monthly_total : 0);
            });
            
            seriesData.push({
                name: vendorName,
                data: vendorData,
                type: 'line',
                color: getVendorColor(vendorIndex)
            });
        });
        
        // Format month labels
        const monthLabels = sortedMonths.map(monthKey => {
            const [year, month] = monthKey.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('en-US', { 
                month: 'short', 
                year: '2-digit' 
            });
        });
        
        // Initialize chart
        const chartOptions = {
            series: seriesData,
            chart: {
                type: 'line',
                height: 400,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                }
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            xaxis: {
                categories: monthLabels,
                labels: {
                    rotate: -45,
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Amount ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center',
                fontSize: '14px',
                fontFamily: 'Helvetica, Arial',
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            },
            markers: {
                size: 5
            },
            grid: {
                borderColor: '#f1f1f1',
            },
            dataLabels: {
                enabled: false
            }
        };
        
        const chart = new ApexCharts(document.querySelector("#monthlyVendorChart"), chartOptions);
        chart.render();
        
        // Function to get vendor color
        function getVendorColor(index) {
            const colors = ['#3E97FF', '#50CD89', '#7239EA', '#FFC700', '#F1416C'];
            return colors[index % colors.length];
        }
    });
</script>
@endif

<script>
    // Amount range validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const minAmount = parseFloat(document.querySelector('[name="min_amount"]').value) || 0;
        const maxAmount = parseFloat(document.querySelector('[name="max_amount"]').value) || 0;
        
        if (minAmount > 0 && maxAmount > 0 && minAmount > maxAmount) {
            e.preventDefault();
            alert('{{ __("accounting.min_amount_cannot_exceed_max") }}');
            return false;
        }
    });
</script>
@endpush

@endsection