{{-- resources/views/reports/orders/by-customer.blade.php --}}
@extends('layouts.app')

@section('title', __('auth.sales_by_customer'))

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
                                {{ __('auth.sales_by_customer') }}
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
                                <li class="breadcrumb-item text-muted">{{ __('auth.order_reports') }}</li>
                                <li class="breadcrumb-item">
                                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                                </li>
                                <li class="breadcrumb-item text-muted">{{ __('auth.sales_by_customer') }}</li>
                            </ul>
                        </div>
                        <div class="d-flex align-items-center gap-2 gap-lg-3">
                            @if($customerSales->count() > 0)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ki-duotone ki-file-down fs-2"></i> {{ __('accounting.export') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'customerSalesTable', filename: 'sales_by_customer_{{ date('Y_m_d') }}', sheetName: 'Customer Sales'})">
                                            <i class="ki-duotone ki-file-excel fs-2 me-2 text-success"></i>
                                            {{ __('accounting.export_to_excel') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="javascript:void(0)" 
                                        onclick="exportCurrentPage({tableId: 'customerSalesTable', filename: 'sales_by_customer_{{ date('Y_m_d') }}', format: 'csv'})">
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
                                <form method="GET" action="{{ route('reports.orders.by-customer') }}" id="filterForm">
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
                                                    title="{{ __('auth.start_date') }}">
                                                <span class="input-group-text bg-light">{{ __('accounting.to') }}</span>
                                                <input type="date" class="form-control" name="end_date" 
                                                    value="{{ $endDate }}" required
                                                    title="{{ __('auth.end_date') }}">
                                            </div>
                                        </div>
                                        
                                        {{-- Location --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.location') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-location fs-2"></i>
                                                </span>
                                                <select class="form-select" name="location_id">
                                                    <option value="">{{ __('auth.all_locations') }}</option>
                                                    @foreach($locations as $location)
                                                        <option value="{{ $location->id }}" 
                                                                {{ $locationId == $location->id ? 'selected' : '' }}>
                                                            {{ $location->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Department --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.department') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-building fs-2"></i>
                                                </span>
                                                <select class="form-select" name="department_id">
                                                    <option value="">{{ __('auth.all_departments') }}</option>
                                                    @foreach($departments as $department)
                                                        <option value="{{ $department->id }}" 
                                                                {{ $departmentId == $department->id ? 'selected' : '' }}>
                                                            {{ $department->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        
                                        {{-- Min/Max Spent --}}
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.min_spent') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-dollar fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="min_spent" 
                                                    value="{{ $minSpent }}" 
                                                    placeholder="0.00"
                                                    step="0.01" min="0">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 col-lg-2">
                                            <label class="form-label fw-semibold">{{ __('auth.max_spent') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-dollar fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="max_spent" 
                                                    value="{{ $maxSpent }}" 
                                                    placeholder="1000000.00"
                                                    step="0.01" min="0">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-6 mb-6">
                                        {{-- Min/Max Orders --}}
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label fw-semibold">{{ __('auth.min_orders') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-bag fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="min_orders" 
                                                    value="{{ $minOrders }}" 
                                                    placeholder="0"
                                                    step="1" min="0">
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 col-lg-3">
                                            <label class="form-label fw-semibold">{{ __('auth.max_orders') }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ki-duotone ki-bag fs-2"></i>
                                                </span>
                                                <input type="number" class="form-control" name="max_orders" 
                                                    value="{{ $maxOrders }}" 
                                                    placeholder="1000"
                                                    step="1" min="0">
                                            </div>
                                        </div>
                                        
                                        {{-- Action Buttons --}}
                                        <div class="col-md-6 col-lg-6 d-flex align-items-end justify-content-end">
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-primary" id="applyFilters">
                                                    <i class="ki-duotone ki-filter fs-2 me-2"></i>
                                                    {{ __('accounting.apply_filters') }}
                                                </button>
                                                <a href="{{ route('reports.orders.by-customer') }}" class="btn btn-light btn-active-light-primary">
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

                {{-- Customer Segments --}}
                @if($customerSales->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-user-tick fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.customer_segments') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="row g-6">
                                    @php
                                        $totalCustomers = $customerSales->count();
                                        $segments = [
                                            ['key' => 'new', 'color' => 'info', 'icon' => 'ki-user', 'label' => 'new_customers', 'value' => $customerSegments['new'] ?? 0],
                                            ['key' => 'returning', 'color' => 'primary', 'icon' => 'ki-user-square', 'label' => 'returning_customers', 'value' => $customerSegments['returning'] ?? 0],
                                            ['key' => 'regular', 'color' => 'success', 'icon' => 'ki-profile-circle', 'label' => 'regular_customers', 'value' => $customerSegments['regular'] ?? 0],
                                            ['key' => 'vip', 'color' => 'warning', 'icon' => 'ki-crown', 'label' => 'vip_customers', 'value' => $customerSegments['vip'] ?? 0],
                                            ['key' => 'total', 'color' => 'secondary', 'icon' => 'ki-users', 'label' => 'total_customers', 'value' => $totalCustomers],
                                            ['key' => 'avg_order', 'color' => 'danger', 'icon' => 'ki-calculator', 'label' => 'average_order_value', 'value' => '$' . number_format($customerSales->avg('average_order_value') ?? 0, 2)]
                                        ];
                                    @endphp
                                    
                                    @foreach($segments as $segment)
                                    <div class="col-md-6 col-lg-2">
                                        <div class="card card-flush bg-light-{{ $segment['color'] }} border border-{{ $segment['color'] }} border-dashed h-100">
                                            <div class="card-body d-flex flex-column justify-content-center text-center">
                                                <div class="mb-4">
                                                    <i class="ki-duotone {{ $segment['icon'] }} fs-2tx text-{{ $segment['color'] }}">
                                                        @for($i = 1; $i <= 2; $i++)
                                                        <span class="path{{ $i }}"></span>
                                                        @endfor
                                                    </i>
                                                </div>
                                                <div class="mb-1">
                                                    <span class="fs-1 fw-bold text-gray-800">
                                                        {{ $segment['value'] }}
                                                    </span>
                                                </div>
                                                <div class="text-gray-600 fw-semibold">
                                                    {{ __('auth.' . $segment['label']) }}
                                                </div>
                                                @if($segment['key'] !== 'total' && $segment['key'] !== 'avg_order')
                                                <div class="mt-2">
                                                    <span class="badge badge-light-{{ $segment['color'] }}">
                                                        {{ $totalCustomers > 0 ? number_format(($segment['value'] / $totalCustomers) * 100, 1) : 0 }}%
                                                    </span>
                                                </div>
                                                @endif
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

                {{-- Top Customers Chart --}}
                @if($topCustomers->count() > 0)
                <div class="row mb-6">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header border-0">
                                <div class="card-title d-flex align-items-center">
                                    <i class="ki-duotone ki-chart-bar fs-2 me-2 text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <h3 class="fw-bold m-0">{{ __('auth.top_customers') }}</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div id="topCustomersChart" style="height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Customer Sales Table --}}
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
                                        <h3 class="fw-bold m-0">{{ __('auth.customer_sales_report') }}</h3>
                                    </div>
                                    @if($customerSales->count() > 0)
                                    <span class="badge badge-light-primary fs-7">
                                        {{ __('accounting.showing') }} {{ $customerSales->count() }} {{ __('accounting.customers') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            @if($customerSales->count() > 0)
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-bordered table-row-dashed gy-4 align-middle gs-0" id="customerSalesTable">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800 border-bottom border-gray-200 bg-light">
                                                    <th class="min-w-50px ps-4">{{ __('accounting.rank') }}</th>
                                                    <th class="min-w-200px">{{ __('accounting.customer') }}</th>
                                                    <th class="min-w-150px">{{ __('accounting.contact') }}</th>
                                                    <th class="min-w-100px">{{ __('auth.order_count') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.total_spent') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.total_tax') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.total_discount') }}</th>
                                                    <th class="min-w-120px">{{ __('accounting.average_amount') }}</th>
                                                    <th class="min-w-120px">{{ __('auth.max_order_value') }}</th>
                                                    <th class="min-w-150px">{{ __('auth.last_order_date') }}</th>
                                                    <th class="min-w-100px">{{ __('accounting.percentage') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($customerSales as $index => $customer)
                                                @php
                                                    $totalSpentAll = $customerSales->sum('total_spent');
                                                    $percentage = $totalSpentAll > 0 ? ($customer->total_spent / $totalSpentAll) * 100 : 0;
                                                    $customerSegment = $customer->order_count == 1 ? 'new' : 
                                                                      ($customer->order_count <= 5 ? 'returning' : 
                                                                      ($customer->order_count <= 20 ? 'regular' : 'vip'));
                                                    $segmentColors = ['new' => 'info', 'returning' => 'primary', 'regular' => 'success', 'vip' => 'warning'];
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
                                                                <div class="symbol-label bg-light-{{ $segmentColors[$customerSegment] ?? 'secondary' }}">
                                                                    <i class="ki-duotone ki-user fs-2"></i>
                                                                </div>
                                                            </div>
                                                            <div class="d-flex justify-content-start flex-column">
                                                                <span class="text-gray-800 fw-bold">{{ $customer->first_name }} {{ $customer->last_name }}</span>
                                                                <span class="badge badge-light-{{ $segmentColors[$customerSegment] ?? 'secondary' }} badge-sm mt-1">
                                                                    {{ ucfirst($customerSegment) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if($customer->email)
                                                        <div class="mb-1">
                                                            <i class="ki-duotone ki-sms fs-3 text-gray-500 me-2"></i>
                                                            <small>{{ $customer->email }}</small>
                                                        </div>
                                                        @endif
                                                        @if($customer->phone)
                                                        <div>
                                                            <i class="ki-duotone ki-phone fs-3 text-gray-500 me-2"></i>
                                                            <small>{{ $customer->phone }}</small>
                                                        </div>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-primary">{{ $customer->order_count }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">${{ number_format($customer->total_spent, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-info">${{ number_format($customer->total_tax, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-warning">${{ number_format($customer->total_discount, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-gray-600">${{ number_format($customer->average_order_value, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="text-danger">${{ number_format($customer->max_order_value, 2) }}</span>
                                                    </td>
                                                    <td>
                                                        @if($customer->last_order_date)
                                                        <span class="text-gray-700">{{ \Carbon\Carbon::parse($customer->last_order_date)->format('M d, Y') }}</span>
                                                        @else
                                                        <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress w-100 me-3" style="height: 8px;">
                                                                <div class="progress-bar bg-{{ $segmentColors[$customerSegment] ?? 'secondary' }}" 
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
                                        <p class="text-muted fs-6">{{ __('auth.no_customers_found_for_period') }}</p>
                                        @if(request()->hasAny(['start_date', 'end_date', 'location_id', 'department_id', 'min_spent', 'max_spent', 'min_orders', 'max_orders']))
                                        <a href="{{ route('reports.orders.by-customer') }}" class="btn btn-light-primary">
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
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if($topCustomers->count() > 0)
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Top Customers Chart
        const topCustomersData = @json($topCustomers->take(10));
        const customerNames = topCustomersData.map(customer => `${customer.first_name} ${customer.last_name}`.substring(0, 15) + '...');
        const customerSales = topCustomersData.map(customer => parseFloat(customer.total_spent));
        
        const topCustomersChart = new ApexCharts(document.querySelector("#topCustomersChart"), {
            series: [{
                name: 'Total Sales',
                data: customerSales
            }],
            chart: {
                type: 'bar',
                height: 400,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 0});
                }
            },
            xaxis: {
                categories: customerNames,
                title: {
                    text: 'Sales Amount ($)'
                },
                labels: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString();
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            colors: ['#3E97FF'],
            tooltip: {
                y: {
                    formatter: function(val) {
                        return '$' + val.toLocaleString(undefined, {minimumFractionDigits: 2});
                    }
                }
            }
        });
        topCustomersChart.render();
    });
</script>
@endif

<script>
    // Form validation
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        const startDate = new Date(document.querySelector('[name="start_date"]').value);
        const endDate = new Date(document.querySelector('[name="end_date"]').value);
        const minSpent = parseFloat(document.querySelector('[name="min_spent"]').value) || 0;
        const maxSpent = parseFloat(document.querySelector('[name="max_spent"]').value) || 0;
        
        if (startDate > endDate) {
            e.preventDefault();
            alert('{{ __("auth.start_date_cannot_be_after_end_date") }}');
            return false;
        }
        
        if (minSpent > 0 && maxSpent > 0 && minSpent > maxSpent) {
            e.preventDefault();
            alert('{{ __("auth.min_spent_cannot_exceed_max_spent") }}');
            return false;
        }
    });
</script>
@endpush

@endsection